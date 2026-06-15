<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Paystack\PaystackPaymentService;
use App\Services\PaymentCredentials;
use App\Settings\PaymentSettings;
use App\Support\StorefrontSession;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Payment')] class extends Component {
    public Order $order;

    public bool $paystackReady = false;

    public function mount(Order $order): void
    {
        SEOMeta::setRobots('noindex,nofollow');

        abort_if($order->user_id !== auth()->id(), 403);

        if ($order->isPaid()) {
            $this->redirectRoute('account.orders.show', $order, navigate: true);

            return;
        }

        $order->load(['items.product', 'items.product.images' => fn($q) => $q->where('is_cover', true)->limit(1)]);
        $this->order = $order;

        // Paystack is ready when the gateway is enabled and its keys are set.
        // The popup then offers whatever channels the account has activated.
        $this->paystackReady = app(PaymentSettings::class)->paystack_enabled
            && app(PaymentCredentials::class)->paystackSecretKey() !== '';
    }

    /**
     * Always re-queries items with images so product thumbnails survive
     * Livewire hydration cycles and work even when product_id is null
     * (quote items added by admin without a product link).
     *
     * @return Collection<int, OrderItem>
     */
    #[Computed]
    public function orderItems(): Collection
    {
        return $this->order
            ->items()
            ->with(['product', 'product.images' => fn($q) => $q->where('is_cover', true)->limit(1)])
            ->get();
    }

    /**
     * Initialize a Paystack transaction server-side, then hand the access code
     * to the browser so the inline popup can resume it. The popup presents every
     * channel the merchant has enabled on their Paystack account.
     */
    public function pay(): void
    {
        if (! $this->paystackReady) {
            $this->addError('payment', 'Online payment is currently unavailable. Please contact us to complete your order.');

            return;
        }

        try {
            $payment = app(PaystackPaymentService::class)->initialize($this->order);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('payment', 'We could not start the payment. Please try again.');

            return;
        }

        $this->order->update(['payment_method' => 'paystack']);

        $this->dispatch('paystack-open', accessCode: $payment->paystack_access_code);
    }

    /**
     * Called from JS once the Paystack popup reports success. Verifies the
     * transaction server-side (the authoritative check) before advancing.
     */
    public function verifyPayment(string $reference): void
    {
        $payment = app(PaystackPaymentService::class)->verify($reference);

        if (! $payment) {
            $this->addError('payment', 'Payment could not be confirmed. If you were charged, please contact support.');

            return;
        }

        StorefrontSession::clearCart();
        $this->dispatch('cart-updated');

        Flux::toast(heading: 'Payment confirmed', text: 'Order ' . $payment->account_reference . ' is being processed.', variant: 'success');

        $this->redirectRoute('account.orders.show', $payment->order_id, navigate: true);
    }
}; ?>

@assets
    <script src="https://js.paystack.co/v2/inline.js"></script>
@endassets

<div class="page-fade">
    <div class="shell pt-4 pb-20">

        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('checkout')" wire:navigate>Checkout</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Payment</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <h1 class="text-3xl font-semibold tracking-tight">Payment</h1>

        <div class="mt-6 flex flex-col gap-8 lg:flex-row lg:items-start">

            {{-- ================================================== --}}
            {{-- LEFT: PAYMENT --}}
            {{-- ================================================== --}}
            <div class="flex-1 min-w-0 space-y-4" x-data="paystackCheckout"
                @paystack-open.window="open($event.detail.accessCode)">

                @error('payment')
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-600">
                        {{ $message }}</div>
                @enderror

                @if ($paystackReady)
                    <div class="rounded-md border border-zinc-200 bg-white p-5">
                        <div class="flex items-start gap-3">
                            <flux:icon.lock-closed variant="micro" class="mt-0.5 size-5 text-brand-500" />
                            <div>
                                <flux:heading size="sm">Pay securely with Paystack</flux:heading>
                                <p class="mt-1 text-[13px] text-ink-3">
                                    Click pay to choose your method — card, M-Pesa, Airtel Money or bank transfer —
                                    in Paystack's secure window. You'll stay on this page.
                                </p>
                            </div>
                        </div>

                        <flux:button type="button" variant="customer-primary" size="customer-lg"
                            wire:click="pay" wire:loading.attr="disabled" wire:target="pay"
                            x-bind:disabled="processing" class="mt-4 w-full!">
                            <span wire:loading.remove wire:target="pay" x-show="!processing">Pay {{ money($order->total_cents) }}</span>
                            <span wire:loading wire:target="pay">Starting…</span>
                            <span x-show="processing" x-cloak>Confirming…</span>
                        </flux:button>

                        <p class="mt-3 flex items-center justify-center gap-1.5 text-[11px] text-ink-4">
                            <flux:icon.shield-check variant="micro" class="size-3" />
                            Payments are processed securely by Paystack.
                        </p>
                    </div>
                @else
                    <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-700">
                        No online payment methods are currently available. Please contact us to complete your order.
                    </div>
                @endif

            </div>

            {{-- ================================================== --}}
            {{-- RIGHT: ORDER SUMMARY --}}
            {{-- ================================================== --}}
            <aside class="w-full shrink-0 lg:sticky lg:top-44 lg:w-96">
                <div class="rounded-md border border-zinc-200 bg-white">
                    <div class="border-b border-zinc-200 px-5 py-3">
                        <flux:heading size="sm" class="uppercase tracking-wide">Order Summary</flux:heading>
                    </div>

                    <div class="p-6">
                        {{-- Items --}}
                        <div class="space-y-3">
                            @foreach ($this->orderItems as $item)
                                @php $coverUrl = $item->product?->cover_url ?? $item->product_snapshot['cover_url'] ?? null; @endphp
                                <div class="flex items-center gap-3">
                                    @if ($coverUrl)
                                        <img src="{{ $coverUrl }}" alt=""
                                            class="size-12 shrink-0 rounded object-contain" loading="lazy" />
                                    @else
                                        <div
                                            class="size-12 shrink-0 overflow-hidden rounded border border-zinc-100 bg-surface-sunken">
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-[12.5px] font-semibold text-ink">
                                            {{ $item->product_name }}</div>
                                        <div class="text-[11.5px] text-ink-4">Qty {{ $item->quantity }}</div>
                                    </div>
                                    <div class="text-[12.5px] font-semibold text-ink tabular-nums whitespace-nowrap">
                                        {!! money($item->line_total_cents) !!}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="my-5 h-px bg-zinc-100"></div>

                        <div class="flex flex-col gap-3">
                            <div class="flex items-center justify-between text-sm text-ink-2">
                                <span>Subtotal</span>
                                <span class="font-medium tabular-nums">{!! money($order->subtotal_cents) !!}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-ink-2">
                                <span>Shipping</span>
                                <span
                                    class="{{ $order->delivery_cents === 0 ? 'font-medium text-emerald-600' : 'font-medium tabular-nums' }}">
                                    {!! $order->delivery_cents === 0 ? 'Free' : money($order->delivery_cents) !!}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-ink-2">
                                <span>{{ $order->vatLabel() }}</span>
                                <span class="font-medium tabular-nums">{!! money($order->vat_cents) !!}</span>
                            </div>
                        </div>

                        <div class="my-5 h-px bg-zinc-100"></div>

                        <div class="flex items-center justify-between">
                            <span class="text-[13px] font-bold tracking-wide uppercase">Total</span>
                            <span class="text-2xl font-bold text-brand-500 tabular-nums">{!! money($order->total_cents) !!}</span>
                        </div>

                        <div class="mt-5 flex items-center justify-center gap-1.5 text-[11px] text-ink-4">
                            <flux:icon.shield-check variant="micro" class="size-3.5" />
                            SSL encrypted &amp; secure
                        </div>

                        <div class="mt-4 border-t border-zinc-100 pt-4 text-center text-[12px] text-ink-3">
                            Need a formal quote for a tender?
                            <a href="{{ route('quote.request') }}" wire:navigate
                                class="font-semibold text-brand-500 hover:text-brand-600">Request a quote</a>
                        </div>
                    </div>
                </div>
            </aside>

        </div>
    </div>
</div>

@script
    <script>
        Alpine.data('paystackCheckout', () => ({
            processing: false,

            open(accessCode) {
                if (!accessCode || typeof PaystackPop === 'undefined') {
                    return;
                }

                const popup = new PaystackPop();

                popup.resumeTransaction(accessCode, {
                    onSuccess: (transaction) => {
                        this.processing = true;
                        this.$wire.verifyPayment(transaction.reference);
                    },
                    onCancel: () => {
                        this.processing = false;
                    },
                    onError: () => {
                        this.processing = false;
                    },
                });
            },
        }));
    </script>
@endscript
