<?php

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\{Computed, Layout, Locked};
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component {
    #[Locked]
    public Order $order;

    public bool $emailSent = false;

    public function mount(Order $order): void
    {
        // Only the order owner can view this page
        abort_if($order->user_id !== auth()->id(), 403);

        $this->order = $order->load(['items.product', 'payment', 'deliveryOrder.shippingMethod', 'deliveryOrder.pickupStation', 'deliveryOrder.shippingRate', 'user']);

        // Verify Stripe payment if returning from 3DS
        $this->verifyStripeIfNeeded();

        // Send confirmation email on first visit only
        $this->sendConfirmationEmailOnce();
    }

    //  Computed

    #[Computed]
    public function isPaid(): bool
    {
        return $this->order->payment?->status === 'paid';
    }

    #[Computed]
    public function isPending(): bool
    {
        return in_array($this->order->payment?->status, ['pending', 'processing']);
    }

    #[Computed]
    public function isFailed(): bool
    {
        return $this->order->payment?->status === 'failed';
    }

    #[Computed]
    public function paymentMethodLabel(): string
    {
        return match ($this->order->payment?->gateway) {
            'mpesa' => 'M-Pesa',
            'stripe' => 'Card',
            'pesawise' => 'Pesawise',
            'pesapal' => 'Pesapal',
            'paypal' => 'PayPal',
            'custom' => session('checkout.payment_method') === 'card' ? 'Card' : 'M-Pesa',
            default => ucfirst($this->order->payment?->gateway ?? 'Unknown'),
        };
    }

    #[Computed]
    public function deliveryWindow(): ?string
    {
        $delivery = $this->order->deliveryOrder;

        if (!$delivery) {
            return null;
        }

        $min = $delivery->shippingRate?->estimated_days_min;
        $max = $delivery->shippingRate?->estimated_days_max;

        if ($min && $max) {
            return $min === $max ? "{$min} days" : "{$min}–{$max} days";
        }

        if ($delivery->estimated_delivery_at) {
            return 'By ' . $delivery->estimated_delivery_at->format('D, M j');
        }

        return null;
    }

    //  Private

    /**
     * When Stripe redirects back after 3DS, the URL contains
     * ?payment_intent=pi_xxx&payment_intent_client_secret=...&redirect_status=succeeded
     * We verify the payment and update the record.
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
                    'status' => 'paid',
                    'transaction_id' => $status->transactionId,
                    'paid_at' => now(),
                ]);

                $this->order->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                ]);

                // Refresh the model
                $this->order->refresh();
                unset($this->isPaid);
            }
        }
    }

    /**
     * Send confirmation email only on the first visit.
     * Uses the payment record to track whether it's been sent.
     */
    private function sendConfirmationEmailOnce(): void
    {
        // Only send if paid and not yet sent
        if (!$this->isPaid) {
            return;
        }

        $alreadySent = $this->order->payment?->meta['confirmation_email_sent'] ?? false;

        if ($alreadySent) {
            return;
        }

        if (!$this->order->user?->email) {
            return;
        }

        try {
            Mail::to($this->order->user->email)->queue(new OrderConfirmationMail($this->order));

            // Mark as sent in payment meta
            $meta = $this->order->payment->meta ?? [];
            $meta['confirmation_email_sent'] = true;
            $meta['confirmation_email_sent_at'] = now()->toISOString();

            $this->order->payment->update(['meta' => $meta]);

            $this->emailSent = true;
        } catch (\Throwable $e) {
            logger()->error('Failed to send order confirmation email', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);
        }
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

    <div class="container mx-auto px-4 py-8 max-w-3xl">

        {{-- ── Status banner ── --}}
        @if ($this->isPaid)
            <div class="flex flex-col items-center text-center mb-8">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                    <flux:icon.check-circle variant="solid" class="size-9 text-green-500" />
                </div>
                <flux:heading level="1" class="text-2xl! font-bold! mb-1">
                    Order Confirmed!
                </flux:heading>
                <flux:text class="text-zinc-500">
                    Thank you, {{ $order->user?->first_name ?? ($order->shipping_address['first_name'] ?? 'there') }}.
                    Your order has been placed successfully.
                </flux:text>
                <div class="mt-2 flex items-center gap-2">
                    <flux:badge color="zinc" size="sm">{{ $order->reference }}</flux:badge>
                    <flux:text class="text-xs text-zinc-400">
                        {{ $order->placed_at?->format('M j, Y · g:i A') }}
                    </flux:text>
                </div>
            </div>
        @elseif ($this->isPending)
            <div class="flex flex-col items-center text-center mb-8">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mb-4">
                    <flux:icon.clock class="size-9 text-amber-500" />
                </div>
                <flux:heading level="1" class="text-2xl! font-bold! mb-1">
                    Payment Pending
                </flux:heading>
                <flux:text class="text-zinc-500">
                    Your order {{ $order->reference }} has been placed. We're waiting for payment confirmation.
                </flux:text>
            </div>
        @elseif ($this->isFailed)
            <div class="flex flex-col items-center text-center mb-8">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                    <flux:icon.x-circle variant="solid" class="size-9 text-red-500" />
                </div>
                <flux:heading level="1" class="text-2xl! font-bold! mb-1">
                    Payment Failed
                </flux:heading>
                <flux:text class="text-zinc-500 mb-4">
                    Unfortunately your payment could not be processed. Your order has not been confirmed.
                </flux:text>
                <flux:button :href="route('checkout.summary')" wire:navigate variant="primary" class="cursor-pointer">
                    Try Again
                </flux:button>
            </div>
        @endif

        {{-- ── What happens next (paid only) ── --}}
        @if ($this->isPaid)
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-6">
                <flux:heading level="3" class="text-sm! font-semibold! text-blue-800 mb-3">
                    What happens next
                </flux:heading>
                <div class="space-y-2">
                    @foreach ([['icon' => 'clipboard-document-check', 'text' => 'We\'re preparing your order for dispatch.'], ['icon' => 'truck', 'text' => 'You\'ll receive a notification when your order is on its way.'], ['icon' => 'map-pin', 'text' => $this->deliveryWindow ? 'Estimated delivery: ' . $this->deliveryWindow . '.' : 'Delivery time will be communicated shortly.']] as $step)
                        <div class="flex items-start gap-2.5">
                            <flux:icon :name="$step['icon']" class="size-4 text-blue-500 shrink-0 mt-0.5" />
                            <flux:text class="text-sm text-blue-700">{{ $step['text'] }}</flux:text>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── Order items ── --}}
        <div class="bg-white border rounded-lg mb-4 overflow-hidden">
            <div class="px-4 py-2.5 border-b">
                <flux:heading level="3" class="font-medium!">
                    Order Items ({{ $order->items->count() }})
                </flux:heading>
            </div>

            <div class="divide-y">
                @foreach ($order->items as $item)
                    <div class="flex items-start gap-3 p-4">
                        {{-- Product image --}}
                        <div class="w-14 h-14 rounded border bg-zinc-50 overflow-hidden shrink-0">
                            @if ($item->product?->image_path)
                                <img src="{{ $item->product->image_url }}" alt="{{ $item->name }}"
                                    class="w-full h-full object-cover" />
                            @else
                                <flux:icon.photo class="w-full h-full p-2 text-zinc-300" />
                            @endif
                        </div>

                        {{-- Details --}}
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-sm truncate">{{ $item->name }}</p>
                            @if ($item->sku)
                                <flux:text class="text-xs text-zinc-400">SKU: {{ $item->sku }}</flux:text>
                            @endif
                            <flux:text class="text-xs text-zinc-500 mt-0.5">
                                Qty: {{ $item->quantity }}
                                × {{ format_currency($item->unit_price_cents / 100) }}
                            </flux:text>
                        </div>

                        {{-- Line total --}}
                        <span class="font-semibold text-sm shrink-0">
                            {{ format_currency($item->total_cents / 100) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── Two column: address + shipping ── --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">

            {{-- Delivery address --}}
            <div class="bg-white border rounded-lg p-4">
                <div class="flex items-center gap-1.5 mb-3">
                    <flux:icon.map-pin class="size-4 text-zinc-400" />
                    <flux:heading level="3" class="text-sm! font-semibold!">Delivering to</flux:heading>
                </div>
                <div class="space-y-1">
                    <p class="font-medium text-sm">
                        {{ $order->shipping_address['full_name'] ?? '' }}
                    </p>
                    <flux:text class="text-sm text-zinc-500">
                        {{ $order->shipping_address['address'] ?? '' }}
                    </flux:text>
                    <flux:text class="text-sm text-zinc-500">
                        {{ implode(
                            ', ',
                            array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null]),
                        ) }}
                    </flux:text>
                    <flux:text class="text-xs text-zinc-400">
                        {{ format_phone($order->shipping_address['phone_number'] ?? '') }}
                    </flux:text>
                </div>
            </div>

            {{-- Shipping method --}}
            <div class="bg-white border rounded-lg p-4">
                <div class="flex items-center gap-1.5 mb-3">
                    <flux:icon.truck class="size-4 text-zinc-400" />
                    <flux:heading level="3" class="text-sm! font-semibold!">Shipping</flux:heading>
                </div>
                @if ($order->deliveryOrder)
                    <p class="font-medium text-sm">
                        {{ $order->deliveryOrder->shippingMethod?->name }}
                    </p>
                    @if ($this->deliveryWindow)
                        <flux:text class="text-sm text-zinc-500">
                            Est. {{ $this->deliveryWindow }}
                        </flux:text>
                    @endif
                    @if ($order->deliveryOrder->pickupStation)
                        <flux:text class="text-xs text-zinc-400 mt-1">
                            Pickup: {{ $order->deliveryOrder->pickupStation->name }}
                        </flux:text>
                    @endif
                @else
                    <flux:text class="text-sm text-zinc-400">—</flux:text>
                @endif
            </div>
        </div>

        {{-- ── Payment + order totals ── --}}
        <div class="bg-white border rounded-lg mb-6 overflow-hidden">
            <div class="px-4 py-2.5 border-b">
                <flux:heading level="3" class="font-medium!">Order Summary</flux:heading>
            </div>

            <div class="p-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <flux:text>Subtotal</flux:text>
                    <span>{{ format_currency($order->subtotal) }}</span>
                </div>

                @if ($order->discount > 0)
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-green-600">Discount</flux:text>
                        <span class="text-green-600">− {{ format_currency($order->discount) }}</span>
                    </div>
                @endif

                <div class="flex justify-between text-sm">
                    <flux:text>Shipping</flux:text>
                    <span>
                        {{ $order->shipping == 0 ? 'Free' : format_currency($order->shipping) }}
                    </span>
                </div>

                <div class="flex justify-between font-semibold border-t pt-2 mt-2">
                    <span>Total</span>
                    <span>{{ format_currency($order->total) }}</span>
                </div>
            </div>

            {{-- Payment method row --}}
            <div class="flex items-center justify-between px-4 py-3 border-t bg-zinc-50">
                <div class="flex items-center gap-1.5">
                    <flux:icon.credit-card class="size-4 text-zinc-400" />
                    <flux:text class="text-sm">{{ $this->paymentMethodLabel }}</flux:text>
                </div>

                @if ($this->isPaid)
                    <flux:badge color="green" size="sm" icon="check">Paid</flux:badge>
                @elseif ($this->isPending)
                    <flux:badge color="amber" size="sm">Pending</flux:badge>
                @else
                    <flux:badge color="red" size="sm">Failed</flux:badge>
                @endif
            </div>
        </div>

        {{-- ── Actions ── --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <flux:button :href="route('home')" wire:navigate variant="ghost" class="cursor-pointer w-full sm:w-auto">
                Continue Shopping
            </flux:button>

            <flux:button :href="route('customer.orders.index')" wire:navigate variant="primary"
                class="cursor-pointer w-full sm:w-auto">
                <flux:icon.clipboard-document-list class="size-4 me-2" />
                View All Orders
            </flux:button>
        </div>

    </div>
</div>
