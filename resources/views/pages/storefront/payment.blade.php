<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\Mpesa\MpesaPaymentService;
use App\Services\Stripe\StripePaymentService;
use App\Settings\PaymentSettings;
use App\Support\StorefrontSession;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Payment')] class extends Component {
    public Order $order;

    public string $selectedMethod = 'card';

    public bool $cardEnabled = true;

    public bool $mpesaEnabled = true;

    // ==================================================
    // STRIPE CARD
    // ==================================================
    public ?string $stripeClientSecret = null;

    public ?int $stripePaymentId = null;

    // ==================================================
    // M-PESA
    // ==================================================
    public string $mpesaPhone = '';

    public bool $awaitingPayment = false;

    public ?int $pendingPaymentId = null;

    public int $pollAttempts = 0;

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

        $payments = app(PaymentSettings::class);
        $this->cardEnabled = $payments->card_enabled;
        $this->mpesaEnabled = $payments->mpesa_enabled;
        $this->selectedMethod = $this->cardEnabled ? 'card' : 'mpesa';

        if ($this->cardEnabled) {
            $stripePayment = app(StripePaymentService::class)->createPaymentIntent($order);

            // If a previously-crashed finalization was recovered, the order is
            // now paid — redirect straight to the confirmation page.
            if ($order->fresh()->isPaid()) {
                $this->redirectRoute('account.orders.show', $order, navigate: true);

                return;
            }

            $this->stripeClientSecret = $stripePayment->stripe_client_secret;
            $this->stripePaymentId = $stripePayment->id;
        }

        $defaultPhone = auth()->user()->addresses()->orderByDesc('is_default')->value('phone');
        $this->mpesaPhone = (string) ($defaultPhone ?? '');
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

    public function selectMethod(string $method): void
    {
        if ($method === 'card' && $this->cardEnabled) {
            $this->selectedMethod = 'card';
        } elseif ($method === 'mpesa' && $this->mpesaEnabled) {
            $this->selectedMethod = 'mpesa';
        }
    }

    // ==================================================
    // STRIPE CARD
    // ==================================================

    /**
     * Called from Alpine via $wire after Stripe.js reports payment_intent.succeeded.
     * Verifies server-side and finalises the order.
     */
    #[On('stripe-payment-confirmed')]
    public function stripePaymentConfirmed(string $paymentIntentId): void
    {
        $payment = app(StripePaymentService::class)->confirmPaymentIntent($paymentIntentId);

        if (!$payment) {
            $this->addError('card', 'Payment could not be confirmed. If you were charged, please contact support.');

            return;
        }

        $this->order->update(['payment_method' => 'card']);
        StorefrontSession::clearCart();
        $this->dispatch('cart-updated');

        Flux::toast(heading: 'Payment confirmed', text: 'Order ' . $this->order->order_number . ' is being processed.', variant: 'success');

        $this->redirectRoute('account.orders.show', $this->order, navigate: true);
    }

    // ==================================================
    // M-PESA
    // ==================================================

    public function payWithMpesa(): void
    {
        if (!MpesaPaymentService::isValidKenyanMobile($this->mpesaPhone)) {
            $this->addError('mpesaPhone', 'Enter a valid M-Pesa number, e.g. 0712 345 678.');

            return;
        }

        $this->order->update(['payment_method' => 'mpesa']);

        $payment = app(MpesaPaymentService::class)->initiate($this->order, $this->mpesaPhone);

        if ($payment->status === PaymentStatus::FAILED) {
            $this->addError('mpesaPhone', $payment->result_desc ?: 'Could not start the M-Pesa prompt. Please try again.');

            return;
        }

        $this->pendingPaymentId = $payment->id;
        $this->awaitingPayment = true;
        $this->pollAttempts = 0;
    }

    /**
     * Polled every 4s while awaiting M-Pesa PIN entry.
     */
    public function pollPayment(): void
    {
        if (!$this->awaitingPayment || !$this->pendingPaymentId) {
            return;
        }

        $payment = Payment::find($this->pendingPaymentId);

        if (!$payment) {
            $this->awaitingPayment = false;

            return;
        }

        $status = app(MpesaPaymentService::class)->syncFromQuery($payment);
        $this->pollAttempts++;

        if ($status === PaymentStatus::SUCCESS) {
            $this->awaitingPayment = false;
            StorefrontSession::clearCart();
            $this->dispatch('cart-updated');
            Flux::toast(heading: 'Payment received', text: 'Order ' . $payment->account_reference . ' is confirmed.', variant: 'success');
            $this->redirectRoute('account.orders.show', $payment->order_id, navigate: true);

            return;
        }

        if (in_array($status, [PaymentStatus::FAILED, PaymentStatus::CANCELLED], true)) {
            $this->awaitingPayment = false;
            $this->addError('mpesaPhone', $status === PaymentStatus::CANCELLED ? 'Payment was cancelled on your phone. You can try again.' : ($payment->result_desc ?: 'Payment failed. Please try again.'));

            return;
        }

        if ($this->pollAttempts >= 20) {
            $this->awaitingPayment = false;
            $this->addError('mpesaPhone', 'No response from M-Pesa yet. If you were charged your order will update shortly — otherwise try again.');
        }
    }

    public function cancelPayment(): void
    {
        $this->awaitingPayment = false;
        $this->pendingPaymentId = null;
    }
}; ?>

@php
    $stripeKey = app(\App\Services\PaymentCredentials::class)->stripeKey();
@endphp

@if ($cardEnabled)
    @assets
        <script src="https://js.stripe.com/v3/"></script>
    @endassets
@endif

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
            {{-- LEFT: PAYMENT METHODS --}}
            {{-- ================================================== --}}
            <div class="flex-1 min-w-0 space-y-3"
                @if ($cardEnabled) x-data="stripeCardForm(@js($stripeKey), @js($this->stripeClientSecret))"
                 @stripe-payment-confirmed.window="$wire.stripePaymentConfirmed($event.detail.paymentIntentId)" @endif>

                @error('card')
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-600">
                        {{ $message }}</div>
                @enderror

                @if ($cardEnabled)
                    {{-- ================================================== --}}
                    {{-- CARD PAYMENT --}}
                    {{-- ================================================== --}}
                    <div
                        class="overflow-hidden rounded-md border {{ $this->selectedMethod === 'card' ? 'border-brand-200 bg-brand-50' : 'border-zinc-200 bg-white' }}">
                        {{-- Header row --}}
                        <div wire:click="selectMethod('card')" class="flex cursor-pointer items-center gap-3 px-5 py-3">
                            <span
                                class="flex size-4 shrink-0 items-center justify-center rounded-full border-2 {{ $this->selectedMethod === 'card' ? 'border-brand-500' : 'border-zinc-300' }}">
                                @if ($this->selectedMethod === 'card')
                                    <span class="size-2 rounded-full bg-brand-500"></span>
                                @endif
                            </span>
                            <flux:icon.credit-card variant="micro" class="size-4 text-ink-3" />
                            <flux:heading size="sm" class="flex-1">Card Payment</flux:heading>
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="rounded border border-zinc-200 bg-zinc-50 px-1.5 py-0.5 text-[9px] font-bold tracking-widest text-ink-4 uppercase">Visa</span>
                                <span
                                    class="rounded border border-zinc-200 bg-zinc-50 px-1.5 py-0.5 text-[9px] font-bold tracking-widest text-ink-4 uppercase">MC</span>
                                <span
                                    class="rounded border border-zinc-200 bg-zinc-50 px-1.5 py-0.5 text-[9px] font-bold tracking-widest text-ink-4 uppercase">Amex</span>
                            </div>
                        </div>

                        {{-- Card form body.
                         wire:ignore keeps Livewire's DOM morphing away from this subtree so
                         Stripe's iframe-based Elements are never destroyed on re-renders.
                         x-show + $wire handles visibility without touching the DOM. --}}
                        <div wire:ignore>
                            <div x-show="$wire.selectedMethod === 'card'"
                                class="border-t border-zinc-100 px-5 pb-5 pt-4 space-y-4">

                                {{-- Cardholder name --}}
                                <flux:input label="Cardholder name" type="text" x-model="cardholderName"
                                    placeholder="Name on card" />

                                {{-- Card number — Stripe mounts an iframe here; keep raw div --}}
                                <flux:field>
                                    <flux:label>Card number</flux:label>
                                    <div x-ref="cardNumber"
                                        class="w-full rounded-md border border-zinc-200 bg-white px-3 py-[11px] transition focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500">
                                    </div>
                                </flux:field>

                                {{-- Expiry + CVC — Stripe-mounted, keep raw divs --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Expiry date</flux:label>
                                        <div x-ref="cardExpiry"
                                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-[11px] transition focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500">
                                        </div>
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>CVC</flux:label>
                                        <div x-ref="cardCvc"
                                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-[11px] transition focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500">
                                        </div>
                                    </flux:field>
                                </div>

                                {{-- Stripe error message --}}
                                <p x-show="error" x-text="error" class="text-[12.5px] text-red-500"></p>

                                {{-- Pay button --}}
                                <flux:button type="button" variant="customer-primary" size="customer-lg" @click="pay()"
                                    x-bind:disabled="processing || !isComplete" class="mt-1! w-full!">
                                    <span
                                        x-text="processing ? 'Processing' : 'Pay {{ money($order->total_cents) }}'"></span>
                                    <flux:icon.loading x-show="processing" x-cloak variant="micro" class="size-4" />
                                </flux:button>

                                <p class="flex items-center justify-center gap-1.5 text-[11px] text-ink-4">
                                    <flux:icon.lock-closed variant="micro" class="size-3" />
                                    Secured by Stripe. We never store your card details.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($mpesaEnabled)
                    {{-- ================================================== --}}
                    {{-- M-PESA --}}
                    {{-- ================================================== --}}
                    <div
                        class="overflow-hidden rounded-md border {{ $this->selectedMethod === 'mpesa' ? 'border-brand-200 bg-brand-50' : 'border-zinc-200 bg-white' }}">
                        {{-- Header row --}}
                        <div wire:click="selectMethod('mpesa')"
                            class="flex cursor-pointer items-center gap-3 px-5 py-3">
                            <span
                                class="flex size-4 shrink-0 items-center justify-center rounded-full border-2 {{ $this->selectedMethod === 'mpesa' ? 'border-brand-500' : 'border-zinc-300' }}">
                                @if ($this->selectedMethod === 'mpesa')
                                    <span class="size-2 rounded-full bg-brand-500"></span>
                                @endif
                            </span>
                            <flux:icon.device-phone-mobile variant="micro" class="size-4 text-ink-3" />
                            <flux:heading size="sm" class="flex-1">M-Pesa</flux:heading>
                            <span
                                class="text-[10px] font-bold tracking-widest text-emerald-600 uppercase">Safaricom</span>
                        </div>

                        {{-- M-Pesa form (shown when selected) --}}
                        @if ($this->selectedMethod === 'mpesa')
                            <div class="border-t border-zinc-100 px-5 pb-5 pt-4 space-y-4">
                                <flux:input wire:model="mpesaPhone" label="M-Pesa phone number" type="tel"
                                    inputmode="tel" placeholder="0712 345 678"
                                    description:trailing="You'll receive an STK push — enter your PIN to confirm." />

                                <flux:button variant="customer-primary" size="customer-lg" wire:click="payWithMpesa"
                                    wire:loading.attr="disabled" wire:target="payWithMpesa" class="w-full!">
                                    Pay {!! money($order->total_cents) !!} via M-Pesa
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endif

                @unless ($cardEnabled || $mpesaEnabled)
                    <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-700">
                        No online payment methods are currently available. Please contact us to complete your order.
                    </div>
                @endunless

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
                                    <div
                                        class="size-12 shrink-0 overflow-hidden rounded border border-zinc-100 bg-surface-sunken p-1">
                                        @if ($coverUrl)
                                            <img src="{{ $coverUrl }}" alt=""
                                                class="size-full object-contain" loading="lazy" />
                                        @endif
                                    </div>
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

    {{-- M-Pesa STK Push awaiting modal --}}
    <flux:modal wire:model.self="awaitingPayment" class="md:w-[440px]" :dismissible="false" :closable="false">
        <div wire:poll.4s="pollPayment" class="text-center">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-50">
                <flux:icon.device-phone-mobile variant="outline" class="size-7 text-emerald-600" />
            </div>
            <flux:heading class="mt-4">Check your phone</flux:heading>
            <flux:subheading class="mt-1">
                We sent an M-Pesa request to <span class="font-semibold text-ink">{{ $this->mpesaPhone }}</span>.
                Enter your PIN to confirm payment.
            </flux:subheading>

            <div class="mt-5 flex items-center justify-center gap-2 text-[12.5px] text-ink-3">
                <flux:icon.arrow-path variant="micro" class="size-4 animate-spin text-brand-500" />
                Waiting for confirmation…
            </div>

            <flux:button type="button" variant="ghost" size="sm" wire:click="cancelPayment" class="mt-5">
                Cancel</flux:button>
        </div>
    </flux:modal>
</div>

@script
    <script>
        Alpine.data('stripeCardForm', (publishableKey, clientSecret) => ({
            stripe: null,
            cardNumber: null,
            cardExpiry: null,
            cardCvc: null,
            cardholderName: '',
            error: null,
            processing: false,
            complete: {
                number: false,
                expiry: false,
                cvc: false
            },

            get isComplete() {
                return this.complete.number && this.complete.expiry && this.complete.cvc && this
                    .cardholderName.trim().length > 0;
            },

            init() {
                if (!publishableKey || !clientSecret) return;

                this.stripe = Stripe(publishableKey);
                const elements = this.stripe.elements();

                const style = {
                    base: {
                        fontSize: '13.5px',
                        fontFamily: 'inherit',
                        color: '#1a1a1a',
                        '::placeholder': {
                            color: '#9ca3af'
                        },
                    },
                    invalid: {
                        color: '#ef4444'
                    },
                };

                this.cardNumber = elements.create('cardNumber', {
                    style,
                    showIcon: true
                });
                this.cardNumber.mount(this.$refs.cardNumber);
                this.cardNumber.on('change', e => {
                    this.complete.number = e.complete;
                    if (e.error) this.error = e.error.message;
                    else if (this.complete.number) this.error = null;
                });

                this.cardExpiry = elements.create('cardExpiry', {
                    style
                });
                this.cardExpiry.mount(this.$refs.cardExpiry);
                this.cardExpiry.on('change', e => {
                    this.complete.expiry = e.complete;
                    if (e.error) this.error = e.error.message;
                });

                this.cardCvc = elements.create('cardCvc', {
                    style
                });
                this.cardCvc.mount(this.$refs.cardCvc);
                this.cardCvc.on('change', e => {
                    this.complete.cvc = e.complete;
                    if (e.error) this.error = e.error.message;
                });
            },

            async pay() {
                if (this.processing || !this.isComplete) return;

                this.processing = true;
                this.error = null;

                const {
                    error,
                    paymentIntent
                } = await this.stripe.confirmCardPayment(clientSecret, {
                    payment_method: {
                        card: this.cardNumber,
                        billing_details: {
                            name: this.cardholderName
                        },
                    },
                });

                if (error) {
                    this.error = error.message;
                    this.processing = false;
                } else if (paymentIntent.status === 'succeeded') {
                    window.dispatchEvent(new CustomEvent('stripe-payment-confirmed', {
                        detail: {
                            paymentIntentId: paymentIntent.id
                        },
                    }));
                }
            },
        }));
    </script>
@endscript
