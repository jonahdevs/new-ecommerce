<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\SyncOrderToSapJob;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutSession;
use App\Services\Payment\PaymentService;
use Livewire\Attributes\{Computed, Layout, Locked, On};
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component {
    #[Locked]
    public Order $order;

    #[Locked]
    public int $orderId;

    public bool $justConfirmed = false;

    public function mount(Order $order): void
    {
        abort_if($order->user_id !== auth()->id(), 403);

        $this->order = $order->load([
            'items' => fn($q) => $q
                ->select(['id', 'order_id', 'product_id', 'product_snapshot', 'quantity', 'total_cents'])
                ->with(['product' => fn($q) => $q->select(['id', 'image_path'])]),
            'payment' => fn($q) => $q->select(['id', 'order_id', 'gateway', 'status']),
            'user' => fn($q) => $q->select(['id', 'name', 'email']),
        ]);
        $this->orderId = $order->id;

        // Handle 3DS redirect back from Stripe first.
        // Must run before session check so 3DS return works correctly.
        $this->verifyStripeIfNeeded();

        // Session-based page invalidation — redirects away if already seen
        $this->handleSessionCheck();
    }

    // =====================================================
    // Computed
    // =====================================================

    #[Computed]
    public function isPaid(): bool
    {
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

    // =====================================================
    // Public methods
    // =====================================================

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
     * Catches cases where Echo broadcast was missed (e.g. page loaded
     * after webhook fired but before Echo delivered the event).
     */
    public function refreshOrderStatus(): void
    {
        $this->order = $this->order->fresh([
            'items' => fn($q) => $q
                ->select(['id', 'order_id', 'product_id', 'product_snapshot', 'quantity', 'total_cents'])
                ->with(['product' => fn($q) => $q->select(['id', 'image_path'])]),
            'payment' => fn($q) => $q->select(['id', 'order_id', 'gateway', 'status']),
            'user' => fn($q) => $q->select(['id', 'name', 'email']),
        ]);
        unset($this->isPaid, $this->isFailed);

        if ($this->isPaid) {
            $this->justConfirmed = true;
            session()->put($this->sessionKey, true);
            $this->dispatchSapSyncIfNeeded();
            $this->dispatch('cart-updated');
        }
    }

    public function getListeners(): array
    {
        return [
            "echo-private:order.{$this->orderId},PaymentConfirmed" => 'onPaymentConfirmed',
        ];
    }

    // =====================================================
    // Echo event listener
    // =====================================================

    /**
     * Fires when gateway webhook broadcasts PaymentConfirmed via Pusher.
     * Flips UI from pending → confirmed instantly without a page reload.
     */
    public function onPaymentConfirmed(): void
    {
        $this->order = $this->order->fresh([
            'items' => fn($q) => $q
                ->select(['id', 'order_id', 'product_id', 'product_snapshot', 'quantity', 'total_cents'])
                ->with(['product' => fn($q) => $q->select(['id', 'image_path'])]),
            'payment' => fn($q) => $q->select(['id', 'order_id', 'gateway', 'status']),
            'user' => fn($q) => $q->select(['id', 'name', 'email']),
        ]);
        unset($this->isPaid, $this->isFailed);

        if ($this->isPaid) {
            $this->justConfirmed = true;
            session()->put($this->sessionKey, true);
            $this->dispatchSapSyncIfNeeded();
            $this->dispatch('cart-updated');
        }
    }

    // =====================================================
    // Private helpers
    // =====================================================

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

    /**
     * Handles the redirect back from Stripe after 3DS authentication.
     *
     * The normal Stripe webhook (payment_intent.succeeded) handles most
     * payments. But for 3DS cards the customer is redirected back to this
     * page with ?payment_intent=pi_xxx&redirect_status=succeeded in the
     * URL. The webhook may not have fired yet by the time they land here,
     * so we verify and confirm inline.
     *
     * Fixes:
     *  - SAP sync was missing for 3DS payments (now dispatched here)
     *  - Invoice generation was missing for 3DS payments (now called here)
     *  - Cart/session clearing removed — gateway webhooks handle that;
     *    this path only fires when the webhook hasn't arrived yet, so
     *    we clear here as a fallback only when we confirm inline.
     */
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
                // Update payment record
                $this->order->payment->update([
                    'status' => PaymentStatus::PAID->value,
                    'transaction_id' => $status->transactionId,
                    'paid_at' => now(),
                ]);

                // Transition order to confirmed
                $this->order->transitionTo(OrderStatus::CONFIRMED, notes: 'Payment confirmed via Stripe 3DS redirect', changedByType: 'system');

                $this->order->update(['payment_status' => PaymentStatus::PAID->value]);
                $this->order->refresh();
                unset($this->isPaid, $this->isFailed);

                // Invoice is generated later when SAP webhook returns KRA data

                // Clear cart and session — fallback since webhook may not
                // have fired yet for this 3DS payment
                app(CartService::class)->clear($this->order->user);
                app(CheckoutSession::class)->clear();

                // Dispatch SAP sync — this was missing for 3DS payments.
                // The Stripe webhook (handleSucceeded) also dispatches this,
                // but SyncOrderToSapJob has an idempotency guard via
                // sap_sync_status — if the webhook fires later and the job
                // is already running/done, the duplicate dispatch is harmless.
                SyncOrderToSapJob::dispatch($this->order->fresh());

                $this->dispatch('cart-updated');
            }
        }
    }

    private function resolveCustomPaymentLabel(): string
    {
        $method = $this->order->payment?->meta['payment_method'] ?? null;
        return $method === 'card' ? 'Card' : 'M-Pesa';
    }

    /**
     * Dispatch SAP sync only if it hasn't been triggered yet.
     * Guards against double-dispatch when both the webhook and the
     * confirmation page try to fire the job.
     */
    private function dispatchSapSyncIfNeeded(): void
    {
        $order = $this->order->fresh();

        if ($order->sap_sync_status?->value === \App\Enums\SapSyncStatus::PENDING->value) {
            SyncOrderToSapJob::dispatch($order);
        }
    }
};
?>

<div>
    {{-- Breadcrumb --}}
    <div class="bg-white border-b border-zinc-200 py-3">
        <flux:breadcrumbs class="container mx-auto px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
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

                {{-- Hero --}}
                <div class="text-center mb-10">
                    <flux:icon.check-circle class="size-14 mx-auto text-green-600 mb-6" />

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
                        Your order confirmation and tax invoice will be sent to
                        <span class="font-medium text-zinc-600">{{ $order->user?->email }}</span>
                        shortly.
                    </flux:text>
                </div>

                {{-- Order items + totals --}}
                <flux:card class="anim-4 p-0 mb-6">
                    <div class="px-6 py-4 border-b border-zinc-100">
                        <h2 class="text-sm font-semibold text-zinc-400 uppercase tracking-widest">
                            Items Ordered
                        </h2>
                    </div>

                    <div class="divide-y divide-zinc-100">
                        @foreach ($order->items as $item)
                            @php
                                $variantAttrs = $item->product_snapshot['variant']['attributes'] ?? [];
                            @endphp
                            <div class="flex items-center gap-4 px-6 py-4">
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

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-zinc-800 leading-snug line-clamp-2 mb-1">
                                        {{ $item->product_snapshot['name'] ?? $item->product?->name }}
                                    </p>
                                    @if (!empty($variantAttrs))
                                        <p class="text-xs text-zinc-400 mb-1">
                                            {{ collect($variantAttrs)->map(fn($v, $k) => "$k: $v")->join(' · ') }}
                                        </p>
                                    @endif
                                    <p class="text-xs text-zinc-400">Qty: {{ $item->quantity }}</p>
                                </div>

                                <p class="text-sm font-bold text-zinc-800 shrink-0">
                                    {{ format_currency($item->total_cents / 100) }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    <div class="px-6 py-4 bg-white/80 border-t border-zinc-100 space-y-2">
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
                            <span>{{ $order->shipping == 0 ? 'Free' : format_currency($order->shipping) }}</span>
                        </div>

                        <div
                            class="flex justify-between text-sm font-bold text-zinc-900 border-t border-zinc-200 pt-3 mt-1">
                            <span>Total</span>
                            <span>{{ format_currency($order->total) }}</span>
                        </div>
                    </div>
                </flux:card>

                {{-- Actions --}}
                <div class="anim-5 flex flex-col sm:flex-row gap-3">
                    <flux:button wire:click="viewOrderDetails" variant="customer-primary" icon="clipboard-document-list"
                        class="cursor-pointer w-full">
                        View Order
                    </flux:button>

                    <flux:button wire:click="continueShopping" variant="customer-outline" icon="shopping-bag"
                        class="cursor-pointer w-full">
                        Continue Shopping
                    </flux:button>
                </div>

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
                        variant="customer-primary" icon="arrow-path" class="cursor-pointer w-full sm:w-auto">
                        Try Again
                    </flux:button>

                    <flux:button :href="route('customer.orders.index')" wire:navigate variant="customer-outline"
                        class="cursor-pointer w-full sm:w-auto">
                        View My Orders
                    </flux:button>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════ --}}
            {{-- PENDING / PROCESSING STATE                        --}}
            {{-- wire:poll.3s fires refreshOrderStatus() as a     --}}
            {{-- fallback if the Echo broadcast was missed.        --}}
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
