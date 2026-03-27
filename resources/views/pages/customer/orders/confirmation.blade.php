<?php

use App\Enums\OrdersStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutSession;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\{Computed, Layout, Locked, On};
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component {
    #[Locked]
    public Order $order;

    #[Locked]
    public int $orderId;

    public bool $emailSent = false;
    public bool $justConfirmed = false;

    public function mount(Order $order): void
    {
        abort_if($order->user_id !== auth()->id(), 403);

        $this->order = $order->load(['items.product', 'payment', 'user']);
        $this->orderId = $order->id;

        // Handle 3DS redirect back from Stripe first
        // Must run before session check so 3DS return works correctly
        $this->verifyStripeIfNeeded();

        // Session-based page invalidation
        $this->handleSessionCheck();

        // Only runs on first legitimate visit
        if ($this->isPaid) {
            $this->sendConfirmationEmailOnce();
            $this->clearCartIfPaid();
            $this->dispatch('cart-updated');
        }
    }

    // Computed

    #[Computed]
    public function isPaid(): bool
    {
        // Use ?->value consistently — status may be cast as an enum on the model
        return $this->order->payment?->status?->value === PaymentStatus::PAID->value;
    }

    #[Computed]
    public function isFailed(): bool
    {
        return $this->order->payment?->status?->value === PaymentStatus::FAILED->value;
    }

    #[Computed]
    public function paymentMethodLabel(): string
    {
        return match ($this->order->payment?->gateway) {
            'mpesa' => 'M-Pesa',
            'stripe' => 'Card',
            'custom' => $this->resolveCustomPaymentLabel(),
            default => ucfirst($this->order->payment?->gateway ?? 'Unknown'),
        };
    }

    #[Computed]
    public function deliveryWindow(): ?string
    {
        return $this->order->shipping_snapshot['delivery_window'] ?? null;
    }

    #[Computed]
    public function shippingMethod(): ?string
    {
        return $this->order->shipping_snapshot['method_name'] ?? null;
    }

    #[Computed]
    public function stationName(): ?string
    {
        return $this->order->shipping_snapshot['station_name'] ?? null;
    }

    #[Computed]
    public function sessionKey(): string
    {
        return "order_confirmation_{$this->order->id}";
    }

    //  Public Methods

    public function viewOrderDetails(): void
    {
        $this->redirectRoute('customer.orders.show', ['order' => $this->order], navigate: true);
    }

    public function continueShopping(): void
    {
        $this->redirectRoute('home', navigate: true);
    }

    /**
     * Polling fallback — called every 3s while in pending/unknown state.
     * Catches cases where Echo broadcast was missed (e.g. page loaded after webhook fired).
     */
    public function refreshOrderStatus(): void
    {
        $this->order = $this->order->fresh(['items.product', 'payment', 'user']);
        unset($this->isPaid, $this->isFailed);

        if ($this->isPaid) {
            $this->justConfirmed = true;
            session()->put($this->sessionKey, true);
            $this->sendConfirmationEmailOnce();
            $this->clearCartIfPaid();
            $this->dispatch('cart-updated');
        }
    }

    public function getListeners(): array
    {
        return [
            "echo-private:order.{$this->orderId},PaymentConfirmed" => 'onPaymentConfirmed',
        ];
    }

    //  Echo Event Listener

    /**
     * Fires when Stripe webhook broadcasts PaymentConfirmed via Pusher.
     * Flips UI from pending → confirmed instantly without a page reload.
     */
    public function onPaymentConfirmed(): void
    {
        $this->order = $this->order->fresh(['items.product', 'payment', 'user']);
        unset($this->isPaid, $this->isFailed);

        if ($this->isPaid) {
            $this->justConfirmed = true;
            session()->put($this->sessionKey, true);
            $this->sendConfirmationEmailOnce();
            $this->clearCartIfPaid();
            $this->dispatch('cart-updated');
        }
    }

    // Private Helpers

    private function handleSessionCheck(): void
    {
        if (!$this->isPaid) {
            return;
        }

        if (session()->has($this->sessionKey)) {
            $this->redirectRoute('customer.orders.show', ['order' => $this->order], navigate: true);
            return;
        }

        session()->put($this->sessionKey, true);
    }

    private function verifyStripeIfNeeded(): void
    {
        $paymentIntent = request('payment_intent');
        $redirectStatus = request('redirect_status');

        if (!$paymentIntent || $this->order->payment?->gateway !== 'stripe') {
            return;
        }

        if ($redirectStatus === 'succeeded' && !$this->isPaid) {
            $status = app(PaymentService::class)->gateway('stripe')->verify($paymentIntent);

            if ($status->isPaid) {
                $this->order->payment->update([
                    'status' => PaymentStatus::PAID->value,
                    'transaction_id' => $status->transactionId,
                    'paid_at' => now(),
                ]);

                $this->order->transitionTo(OrdersStatus::CONFIRMED, notes: 'Payment confirmed via Stripe 3DS redirect', changedByType: 'system');

                $this->order->update(['payment_status' => PaymentStatus::PAID->value]);
                $this->order->refresh();

                unset($this->isPaid, $this->isFailed);

                app(CartService::class)->clear($this->order->user);
                app(CheckoutSession::class)->clear();
            }
        }
    }

    private function sendConfirmationEmailOnce(): void
    {
        if (!$this->isPaid) {
            return;
        }

        $meta = $this->order->payment?->meta ?? [];
        $alreadySent = $meta['confirmation_email_sent'] ?? false;

        if ($alreadySent || !$this->order->user?->email) {
            return;
        }

        try {
            Mail::to($this->order->user->email)->queue(new OrderConfirmationMail($this->order));

            $meta['confirmation_email_sent'] = true;
            $meta['confirmation_email_sent_at'] = now()->toISOString();

            $this->order->payment->update(['meta' => $meta]);

            $this->emailSent = true;
        } catch (\Throwable $e) {
            logger()->error('Failed to queue order confirmation email', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveCustomPaymentLabel(): string
    {
        $method = $this->order->payment?->meta['payment_method'] ?? null;
        return $method === 'card' ? 'Card' : 'M-Pesa';
    }

    private function clearCartIfPaid(): void
    {
        if (!$this->isPaid) {
            return;
        }

        $meta = $this->order->payment?->meta ?? [];
        $alreadyCleared = $meta['cart_cleared'] ?? false;

        if ($alreadyCleared) {
            return;
        }

        app(CartService::class)->clear($this->order->user);
        app(CheckoutSession::class)->clear();

        $meta['cart_cleared'] = true;
        $this->order->payment->update(['meta' => $meta]);
    }
};
?>

<div>
    {{-- Breadcrumb --}}
    <div class="bg-zinc-100">
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Order Confirmation</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="container mx-auto px-4 py-12 max-w-2xl min-h-[60svh]">

        {{-- ══════════════════════════════════════════════════ --}}
        {{-- PAID STATE                                         --}}
        {{-- ══════════════════════════════════════════════════ --}}
        @if ($this->isPaid)

            <div class="container mx-auto px-4 py-12 max-w-3xl">

                {{-- ══════════════════════════════════════ --}}
                {{-- 1. HERO                                --}}
                {{-- ══════════════════════════════════════ --}}
                <div class="text-center mb-10">

                    {{-- icon --}}
                    <flux:icon.check-circle class="size-14 mx-auto text-green-600 mb-6" />

                    {{-- Title --}}
                    <flux:heading level="1" class="text-3xl! font-bold! mb-3">
                        {{ $justConfirmed ? '🎉 Payment Confirmed!' : 'Thank You for Your Order!' }}
                    </flux:heading>

                    <flux:text class="text-zinc-500 text-base mb-2">
                        Hi <span class="font-medium text-zinc-700">{{ $order->user?->name }}</span>,
                        your order has been placed successfully.
                    </flux:text>


                    <div class="inline-flex items-center gap-2 bg-zinc-100 rounded-full px-4 py-1.5 mb-3">
                        <flux:icon.clipboard-document-check class="size-4 text-zinc-500" />
                        <span class="text-sm font-mono font-semibold text-zinc-700">
                            #{{ $order->reference }}
                        </span>
                    </div>

                    <flux:text class="text-zinc-400 text-sm block">
                        A confirmation email has been sent to
                        <span class="font-medium text-zinc-600">{{ $order->user?->email }}</span>.
                        If it doesn't arrive within a few minutes, please check your spam folder.
                    </flux:text>
                </div>

                {{-- ══════════════════════════════════════ --}}
                {{-- 2. ORDER ITEMS + TOTALS                --}}
                {{-- ══════════════════════════════════════ --}}
                <flux:card class="anim-4 p-0 mb-6">

                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-zinc-100">
                        <h2 class="text-sm font-semibold text-zinc-400 uppercase tracking-widest">
                            Items Ordered
                        </h2>
                    </div>

                    {{-- Items --}}
                    <div class="divide-y divide-zinc-100">
                        @foreach ($order->items as $item)
                            <div class="flex items-center gap-4 px-6 py-4">

                                {{-- Image --}}
                                <div
                                    class="w-16 h-16 rounded-xl border border-zinc-100 bg-zinc-50 overflow-hidden shrink-0">
                                    @php $img = $item->product_image_url ?? $item->product?->image_url; @endphp
                                    @if ($img)
                                        <img src="{{ asset($img) }}" alt="{{ $item->product_snapshot['name'] ?? '' }}"
                                            class="w-full h-full object-cover" />
                                    @else
                                        <flux:icon.photo class="w-full h-full p-3 text-zinc-300" />
                                    @endif
                                </div>

                                {{-- Name + qty --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-zinc-800 leading-snug line-clamp-2 mb-1">
                                        {{ $item->product_snapshot['name'] ?? $item->product?->name }}
                                    </p>
                                    <p class="text-xs text-zinc-400">Qty: {{ $item->quantity }}</p>
                                </div>

                                {{-- Price --}}
                                <p class="text-sm font-bold text-zinc-800 shrink-0">
                                    {{ format_currency($item->total_cents / 100) }}
                                </p>

                            </div>
                        @endforeach
                    </div>

                    {{-- Totals --}}
                    <div class="px-6 py-4 bg-white\80 border-t border-zinc-100 space-y-2">
                        <div class="flex justify-between text-xs text-zinc-500">
                            <span>Subtotal</span>
                            <span>{{ format_currency($order->subtotal) }}</span>
                        </div>

                        @if ($order->discount > 0)
                            <div class="flex justify-between text-xs font-medium text-green-600">
                                <span>Discount</span>
                                <span>− {{ format_currency($order->discount) }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-xs text-zinc-500">
                            <span>Shipping</span>
                            <span>
                                {{ $order->shipping == 0 ? 'Free' : format_currency($order->shipping) }}
                            </span>
                        </div>

                        <div
                            class="flex justify-between text-sm font-bold text-zinc-900 border-t border-zinc-200 pt-3 mt-1">
                            <span>Total</span>
                            <span>{{ format_currency($order->total) }}</span>
                        </div>
                    </div>
                </flux:card>

                {{-- ══════════════════════════════════════ --}}
                {{-- 3. ACTIONS                             --}}
                {{-- ══════════════════════════════════════ --}}
                <div class="anim-5 flex flex-col sm:flex-row gap-3">
                    <flux:button wire:click="viewOrderDetails" variant="primary" icon="clipboard-document-list"
                        class="cursor-pointer w-full">
                        View Order
                    </flux:button>

                    <flux:button wire:click="continueShopping" variant="ghost" icon="shopping-bag"
                        class="cursor-pointer w-full">
                        Continue Shopping
                    </flux:button>
                </div>

                {{-- Support link --}}
                <p class="anim-5 text-center text-xs text-zinc-400 mt-4">
                    Questions about your order?
                    <a href="#"
                        class="text-zinc-600 underline underline-offset-2 hover:text-zinc-900 transition-colors">
                        Contact support
                    </a>
                </p>

            </div>

            {{-- ══════════════════════════════════════════════════ --}}
            {{-- FAILED STATE                                       --}}
            {{-- ══════════════════════════════════════════════════ --}}
        @elseif ($this->isFailed)
            <div class="text-center py-16">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <flux:icon.x-circle class="size-10 text-red-500" />
                </div>

                <flux:heading level="1" class="text-2xl! font-bold! mb-3">
                    Payment Failed
                </flux:heading>

                <flux:text class="text-zinc-500 mb-2">
                    Your payment could not be processed.
                </flux:text>

                <flux:text class="text-zinc-400 text-sm mb-8">
                    Don't worry — your order is saved. Please try again
                    with a different card or payment method.
                </flux:text>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <flux:button :href="route('checkout.pay', ['order' => $order->reference])" wire:navigate
                        variant="primary" icon="arrow-path" class="cursor-pointer w-full sm:w-auto">
                        Try Again
                    </flux:button>

                    <flux:button :href="route('customer.orders.index')" wire:navigate variant="ghost"
                        class="cursor-pointer w-full sm:w-auto">
                        View My Orders
                    </flux:button>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════ --}}
            {{-- PENDING / PROCESSING / UNKNOWN                     --}}
            {{-- All non-failed, non-paid states show the spinner.  --}}
            {{-- wire:poll refreshes every 3s as a fallback in case --}}
            {{-- the Echo broadcast was missed.                      --}}
            {{-- ══════════════════════════════════════════════════ --}}
        @else
            <div wire:poll.3s="refreshOrderStatus" class="text-center py-16">
                <flux:icon.loading class="text-sheffield-red mx-auto mb-6" />

                <flux:heading level="1" class="text-2xl! font-bold! mb-3">
                    Confirming Your Payment...
                </flux:heading>

                <flux:text class="text-zinc-500 mb-2">
                    Please wait while we confirm your payment.
                </flux:text>

                <flux:text class="text-zinc-400 text-sm mb-6">
                    This usually takes just a few seconds.
                    Please don't close this page.
                </flux:text>

                <div class="inline-flex items-center gap-2 bg-zinc-100 rounded-full px-4 py-1.5">
                    <flux:icon.clipboard-document-check class="size-4 text-zinc-500" />
                    <span class="text-sm font-mono font-semibold text-zinc-700">
                        #{{ $order->reference }}
                    </span>
                </div>
            </div>
        @endif

    </div>
</div>


<style>
    @keyframes pop-in {
        0% {
            transform: scale(0.4);
            opacity: 0;
        }

        70% {
            transform: scale(1.12);
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    @keyframes fade-up {
        from {
            opacity: 0;
            transform: translateY(14px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes ping-once {
        0% {
            transform: scale(1);
            opacity: 0.6;
        }

        100% {
            transform: scale(2.4);
            opacity: 0;
        }
    }

    .anim-pop {
        animation: pop-in 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }

    .anim-ping {
        animation: ping-once 0.9s ease-out 0.2s both;
    }

    .anim-1 {
        animation: fade-up 0.45s ease both;
        animation-delay: 0.10s;
    }

    .anim-2 {
        animation: fade-up 0.45s ease both;
        animation-delay: 0.22s;
    }

    .anim-3 {
        animation: fade-up 0.45s ease both;
        animation-delay: 0.34s;
    }

    .anim-4 {
        animation: fade-up 0.45s ease both;
        animation-delay: 0.46s;
    }

    .anim-5 {
        animation: fade-up 0.45s ease both;
        animation-delay: 0.58s;
    }
</style>
