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

    // ── Session key for this order's confirmation page
    // Format: order_confirmation_{order_id}
    // Set on first legitimate visit
    // Checked on every subsequent visit → redirect if exists

    public function mount(Order $order): void
    {
        abort_if($order->user_id !== auth()->id(), 403);

        $this->order = $order->load(['items.product', 'payment', 'user']);
        $this->orderId = $order->id;

        \Log::info('Confirmation page:' . json_encode($this->order, JSON_PRETTY_PRINT));

        // Handle 3DS redirect back from Stripe first
        // Must run before session check so 3DS return works correctly
        $this->verifyStripeIfNeeded();

        // Session-based page invalidation
        // If session exists → page already consumed → redirect
        // If not → first visit → set session → show page
        $this->handleSessionCheck();

        // Only runs on first legitimate visit
        if ($this->isPaid) {
            $this->sendConfirmationEmailOnce();
            $this->clearCartIfPaid();
            $this->dispatch('cart-updated');
        }
    }

    //  Computed

    #[Computed]
    public function isPaid(): bool
    {
        return $this->order->payment?->status === PaymentStatus::PAID->value;
    }

    #[Computed]
    public function isPending(): bool
    {
        return in_array($this->order->payment?->status?->value, [PaymentStatus::PENDING->value, PaymentStatus::PROCESSING->value]);
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

    // Public Methods

    /**
     * Customer clicks "View Order Details"
     * Session already set — just redirect
     */
    public function viewOrderDetails(): void
    {
        $this->redirectRoute('customer.orders.show', ['order' => $this->order], navigate: true);
    }

    /**
     * Customer clicks "Continue Shopping"
     * Session already set — just redirect
     */
    public function continueShopping(): void
    {
        $this->redirectRoute('home', navigate: true);
    }

    public function getListeners(): array
    {
        return [
            "echo-private:order.{$this->orderId},payment.confirmed" => 'onPaymentConfirmed',
        ];
    }

    // Echo Event Listener

    /**
     * Fires when Stripe webhook broadcasts PaymentConfirmed
     * via Pusher. Customer is on pending UI waiting for payment.
     * This flips the UI from pending → confirmed instantly.
     */
    public function onPaymentConfirmed(): void
    {
        $this->order = $this->order->fresh(['items.product', 'payment', 'user']);

        unset($this->isPaid, $this->isPending, $this->isFailed);

        \Log::info('Testing the Payment event');

        if ($this->isPaid) {
            $this->justConfirmed = true;

            // Set session now — customer is seeing the page
            session()->put($this->sessionKey, true);

            $this->sendConfirmationEmailOnce();
            $this->clearCartIfPaid();
            $this->dispatch('cart-updated');
        }
    }

    //  Private Helpers

    /**
     * Core page invalidation logic using session.
     *
     * First visit (isPaid):
     *   → session doesn't exist → set it → show page
     *
     * Revisit / refresh / shared URL (isPaid):
     *   → session exists → redirect to order details
     *
     * Pending payment:
     *   → skip session check entirely
     *   → show waiting UI, Echo will flip to paid when ready
     *
     * Failed payment:
     *   → skip session check
     *   → show failed UI with retry option
     */
    private function handleSessionCheck(): void
    {
        // Don't invalidate if payment not yet confirmed
        // Customer needs to stay on page waiting for confirmation
        if (!$this->isPaid) {
            return;
        }

        // Session exists → page already viewed → redirect away
        if (session()->has($this->sessionKey)) {
            $this->redirectRoute('customer.orders.show', ['order' => $this->order], navigate: true);
            return;
        }

        // First legitimate visit → set session → show page
        session()->put($this->sessionKey, true);
    }

    /**
     * Verify Stripe payment when customer returns from 3DS.
     * URL contains ?payment_intent=pi_xxx&redirect_status=succeeded
     * Must run BEFORE handleSessionCheck()
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
                $this->order->payment->update([
                    'status' => PaymentStatus::PAID->value,
                    'transaction_id' => $status->transactionId,
                    'paid_at' => now(),
                ]);

                $this->order->transitionTo(OrdersStatus::CONFIRMED, notes: 'Payment confirmed via Stripe 3DS redirect', changedByType: 'system');

                $this->order->update(['payment_status' => PaymentStatus::PAID->value]);
                $this->order->refresh();

                unset($this->isPaid, $this->isPending, $this->isFailed);

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

    <div class="container mx-auto px-4 py-12 max-w-2xl">

        {{-- ══════════════════════════════════════════════════ --}}
        {{-- PAID STATE                                         --}}
        {{-- ══════════════════════════════════════════════════ --}}
        @if ($this->isPaid)

            {{-- Success Icon + Heading --}}
            <div class="text-center mb-10">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <flux:icon.check-circle class="size-10 text-green-600" />
                </div>

                <flux:heading level="1" class="text-3xl! font-bold! mb-3">
                    {{ $justConfirmed ? '🎉 Payment Confirmed!' : 'Thank You for Your Order!' }}
                </flux:heading>

                <flux:text class="text-zinc-500 text-base mb-2">
                    Hi <span class="font-medium text-zinc-700">{{ $order->user?->first_name }}</span>,
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
                    <span class="font-medium text-zinc-600">{{ $order->user?->email }}</span>
                </flux:text>
            </div>

            {{-- ── What Happens Next ── --}}
            <div class="bg-white border border-zinc-200 rounded-xl p-6 mb-6">
                <flux:heading level="2" class="text-base! font-semibold! mb-5 text-zinc-800">
                    What happens next?
                </flux:heading>

                <div class="space-y-0">

                    {{-- Step 1 --}}
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                                <flux:icon.check class="size-4 text-green-600" />
                            </div>
                            <div class="w-px flex-1 bg-zinc-200 my-1"></div>
                        </div>
                        <div class="pb-6">
                            <p class="text-sm font-semibold text-zinc-800 mb-0.5">
                                Order Placed
                            </p>
                            <p class="text-xs text-zinc-400">
                                {{ $order->created_at->format('M j, Y · g:i A') }}
                            </p>
                        </div>
                    </div>

                    {{-- Step 2 --}}
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                                <flux:icon.check class="size-4 text-green-600" />
                            </div>
                            <div class="w-px flex-1 bg-zinc-200 my-1"></div>
                        </div>
                        <div class="pb-6">
                            <p class="text-sm font-semibold text-zinc-800 mb-0.5">
                                Payment Confirmed
                            </p>
                            <p class="text-xs text-zinc-400">
                                Paid via {{ $this->paymentMethodLabel }}
                                @if ($order->payment?->paid_at)
                                    · {{ $order->payment->paid_at->format('M j, Y · g:i A') }}
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Step 3 --}}
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                                <flux:icon.arrow-path class="size-4 text-amber-500 animate-spin" />
                            </div>
                            <div class="w-px flex-1 bg-zinc-200 my-1"></div>
                        </div>
                        <div class="pb-6">
                            <p class="text-sm font-semibold text-zinc-800 mb-0.5">
                                Preparing Your Order
                            </p>
                            <p class="text-xs text-zinc-400">
                                Our team is verifying and packing your items.
                                Usually within 1–2 business days.
                            </p>
                        </div>
                    </div>

                    {{-- Step 4 --}}
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-zinc-100 flex items-center justify-center shrink-0">
                                <flux:icon.truck class="size-4 text-zinc-400" />
                            </div>
                            <div class="w-px flex-1 bg-zinc-200 my-1"></div>
                        </div>
                        <div class="pb-6">
                            <p class="text-sm font-semibold text-zinc-400 mb-0.5">
                                Shipped
                            </p>
                            <p class="text-xs text-zinc-400">
                                You'll receive a tracking notification
                                once your order ships.
                                @if ($this->shippingMethod)
                                    via {{ $this->shippingMethod }}.
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Step 5 --}}
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-zinc-100 flex items-center justify-center shrink-0">
                                <flux:icon.home class="size-4 text-zinc-400" />
                            </div>
                        </div>
                        <div class="pb-2">
                            <p class="text-sm font-semibold text-zinc-400 mb-0.5">
                                Delivered
                            </p>
                            <p class="text-xs text-zinc-400">
                                @if ($this->deliveryWindow)
                                    Estimated delivery: {{ $this->deliveryWindow }}
                                    @if ($this->stationName)
                                        · Pickup: {{ $this->stationName }}
                                    @endif
                                @else
                                    Estimated delivery time will be
                                    confirmed once your order ships.
                                @endif
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ── Order Snapshot ── --}}
            <div class="bg-white border border-zinc-200 rounded-xl p-6 mb-6">
                <flux:heading level="2" class="text-base! font-semibold! mb-4 text-zinc-800">
                    Order Summary
                </flux:heading>

                {{-- Items --}}
                <div class="space-y-3 mb-4">
                    @foreach ($order->items as $item)
                        <div class="flex items-center gap-3">
                            {{-- Product image --}}
                            <div class="w-12 h-12 rounded-lg border bg-zinc-50 overflow-hidden shrink-0">
                                @php $img = $item->product_image_url ?? $item->product?->image_url; @endphp
                                @if ($img)
                                    <img src="{{ asset($img) }}" alt="{{ $item->product_snapshot['name'] ?? '' }}"
                                        class="w-full h-full object-cover" />
                                @else
                                    <flux:icon.photo class="w-full h-full p-2 text-zinc-300" />
                                @endif
                            </div>

                            {{-- Name + qty --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-800 truncate">
                                    {{ $item->product_snapshot['name'] ?? $item->product?->name }}
                                </p>
                                <p class="text-xs text-zinc-400">
                                    Qty: {{ $item->quantity }}
                                </p>
                            </div>

                            {{-- Price --}}
                            <span class="text-sm font-semibold text-zinc-800 shrink-0">
                                {{ format_currency($item->total_cents / 100) }}
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Totals --}}
                <div class="border-t border-zinc-100 pt-3 space-y-1.5">
                    <div class="flex justify-between text-xs text-zinc-500">
                        <span>Subtotal</span>
                        <span>{{ format_currency($order->subtotal) }}</span>
                    </div>

                    @if ($order->discount > 0)
                        <div class="flex justify-between text-xs text-green-600">
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

                    <div class="flex justify-between font-semibold text-sm border-t border-zinc-100 pt-2 mt-1">
                        <span>Total</span>
                        <span>{{ format_currency($order->total) }}</span>
                    </div>
                </div>
            </div>

            {{-- ── SAP / eTIMS Placeholder ── --}}
            {{--
            TODO: SAP Integration
            Once SAP is integrated, display the legal tax invoice number
            and QR code here. This section is a placeholder.

            <div class="bg-zinc-50 border border-dashed border-zinc-300 rounded-xl p-5 mb-6">
                <div class="flex items-start gap-3">
                    <flux:icon.document-text class="size-5 text-zinc-400 shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-semibold text-zinc-700 mb-0.5">
                            Tax Invoice
                        </p>
                        <p class="text-xs text-zinc-400 mb-2">
                            Invoice #: {{ $order->sap_invoice_number ?? 'Pending generation' }}
                        </p>
                        <p class="text-xs text-zinc-400">
                            KRA eTIMS compliant invoice will be available
                            in your order details once generated.
                        </p>
                    </div>
                </div>
            </div>
            --}}

            {{-- ── Actions ── --}}
            <div class="flex flex-col sm:flex-row gap-3">
                <flux:button wire:click="continueShopping" variant="ghost" class="cursor-pointer w-full sm:w-auto"
                    icon="shopping-bag">
                    Continue Shopping
                </flux:button>

                <flux:button wire:click="viewOrderDetails" variant="primary"
                    class="cursor-pointer w-full sm:w-auto flex-1" icon="clipboard-document-list">
                    View Order Details
                </flux:button>
            </div>

            {{-- ══════════════════════════════════════════════════ --}}
            {{-- PENDING / PROCESSING STATE                         --}}
            {{-- ══════════════════════════════════════════════════ --}}
        @elseif ($this->isPending)
            <div class="text-center py-16">

                <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="animate-spin size-8 text-amber-500" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                </div>

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
            {{-- UNKNOWN STATE — safety net                         --}}
            {{-- ══════════════════════════════════════════════════ --}}
        @else
            <div class="text-center py-16">

                <div class="w-20 h-20 bg-zinc-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <flux:icon.question-mark-circle class="size-10 text-zinc-400" />
                </div>

                <flux:heading level="1" class="text-2xl! font-bold! mb-3">
                    Something Went Wrong
                </flux:heading>

                <flux:text class="text-zinc-500 mb-8">
                    We couldn't determine your payment status.
                    Please check your orders or contact support.
                </flux:text>

                <flux:button :href="route('customer.orders.index')" wire:navigate variant="primary"
                    class="cursor-pointer">
                    View My Orders
                </flux:button>
            </div>
        @endif

    </div>
</div>
