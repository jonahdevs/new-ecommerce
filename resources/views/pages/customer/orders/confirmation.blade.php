<?php

use App\Enums\OrdersStatus;
use App\Enums\PaymentStatus;
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

        $this->order = $order->load(['items.product', 'payment', 'user']);

        // Verify Stripe payment if returning from 3DS
        $this->verifyStripeIfNeeded();

        // Send confirmation email on first visit only
        $this->sendConfirmationEmailOnce();
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
        return in_array($this->order->payment?->status, [PaymentStatus::PENDING->value, PaymentStatus::PROCESSING->value]);
    }

    #[Computed]
    public function isFailed(): bool
    {
        return $this->order->payment?->status === PaymentStatus::FAILED->value;
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
            'custom' => $this->resolveCustomPaymentLabel(),
            default => ucfirst($this->order->payment?->gateway ?? 'Unknown'),
        };
    }

    #[Computed]
    public function deliveryWindow(): ?string
    {
        // Read from shipping_snapshot — DeliveryOrder not created yet at this stage
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

    // Private

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
                    'status' => PaymentStatus::PAID->value,
                    'transaction_id' => $status->transactionId,
                    'paid_at' => now(),
                ]);

                // Use transitionTo — records status history
                $this->order->transitionTo(OrdersStatus::CONFIRMED, notes: 'Payment confirmed via Stripe 3DS redirect', changedByType: 'system');
                $this->order->update(['payment_status' => PaymentStatus::PAID->value]);

                // Refresh model + clear computed cache
                $this->order->refresh();
                unset($this->isPaid);

                // Clear cart + session if not already cleared by webhook
                app(\App\Services\CartService::class)->clear(\App\Models\User::find($this->order->user_id));
                app(\App\Services\CheckoutSession::class)->clear();
            }
        }
    }

    /**
     * Send confirmation email only on the first visit.
     * Uses the payment record meta to track whether it's been sent.
     */
    private function sendConfirmationEmailOnce(): void
    {
        if (!$this->isPaid) {
            return;
        }

        $alreadySent = $this->order->payment?->meta['confirmation_email_sent'] ?? false;

        if ($alreadySent || !$this->order->user?->email) {
            return;
        }

        try {
            Mail::to($this->order->user->email)->queue(new OrderConfirmationMail($this->order));

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

    /**
     * For custom gateway, the payment method (card/mpesa) is stored
     * in payment meta since session is cleared after payment.
     */
    private function resolveCustomPaymentLabel(): string
    {
        $method = $this->order->payment?->meta['payment_method'] ?? null;
        return $method === 'card' ? 'Card' : 'M-Pesa';
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
        {{-- Print Button --}}
        <div class="flex justify-end mb-4 print:hidden">
            <flux:button variant="ghost" onclick="window.print()" icon="printer">Print Receipt</flux:button>
        </div>

        {{-- The "Paper" Container --}}
        <div class="bg-white border border-zinc-200 shadow-sm p-8 print:border-none print:shadow-none">

            {{-- Receipt Header --}}
            <div class="flex justify-between items-start mb-8">
                <div>
                    <flux:heading level="1" class="text-3xl! font-bold! text-zinc-900 mb-1">Receipt
                    </flux:heading>
                    <flux:text class="text-zinc-500">Order #{{ $order->reference }}</flux:text>
                </div>
                <div class="text-right">
                    <img src="{{ asset('logo.png') }}" alt="{{ config('site.site.name') }} Logo"
                        class="h-8 sm:h-10 lg:h-12 w-auto transition-transform duration-300 hover:scale-105" />
                </div>
            </div>

            {{-- Status / Date --}}
            <div class="border-b-2 border-dashed border-zinc-200 pb-6 mb-6">
                <div class="flex justify-between text-sm text-zinc-600 mb-2">
                    <span>Date:</span>
                    <span class="font-medium text-zinc-900">{{ $order->created_at->format('M j, Y') }}</span>
                </div>
                <div class="flex justify-between text-sm text-zinc-600">
                    <span>Status:</span>
                    <flux:badge color="{{ $this->isPaid ? 'green' : 'amber' }}" size="sm">
                        {{ ucfirst($order->payment_status) }}</flux:badge>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="space-y-4 mb-8">
                <div class="grid grid-cols-12 text-xs uppercase tracking-wider text-zinc-400 font-bold mb-2">
                    <div class="col-span-7">Item</div>
                    <div class="col-span-2 text-center">Qty</div>
                    <div class="col-span-3 text-right">Price</div>
                </div>

                @foreach ($order->items as $item)
                    <div class="grid grid-cols-12 text-sm items-center">
                        <div class="col-span-7">
                            <div class="font-medium text-zinc-800">{{ $item->product_snapshot['name'] ?? 'Product' }}
                            </div>
                            <div class="text-xs text-zinc-400">SKU: {{ $item->product_snapshot['sku'] ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="col-span-2 text-center text-zinc-600">{{ $item->quantity }}</div>
                        <div class="col-span-3 text-right font-medium text-zinc-900">
                            {{ format_currency($item->total_cents / 100) }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Totals Grid: Pushed to the right --}}
            <div class="flex justify-end pt-6 border-t border-zinc-100">
                <div class="w-full sm:w-64 space-y-2">
                    {{-- Subtotal --}}
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-zinc-500">Subtotal</flux:text>
                        <flux:text class="font-mono text-zinc-700">{{ format_currency($order->subtotal) }}</flux:text>
                    </div>

                    {{-- Discount --}}
                    @if ($order->discount > 0)
                        <div class="flex justify-between text-sm">
                            <flux:text class="text-green-600">Discount</flux:text>
                            <flux:text class="font-mono text-green-600">-{{ format_currency($order->discount) }}
                            </flux:text>
                        </div>
                    @endif

                    {{-- Shipping --}}
                    <div class="flex justify-between text-sm pb-2">
                        <flux:text class="text-zinc-500">Shipping</flux:text>
                        <flux:text class="font-mono text-zinc-700">
                            {{ $order->shipping == 0 ? 'FREE' : format_currency($order->shipping) }}
                        </flux:text>
                    </div>

                    {{-- Final Total with a more distinct separator --}}
                    <div class="border-t border-dashed border-zinc-300 pt-3 flex justify-between items-baseline">
                        <flux:heading class="text-base! font-bold text-zinc-900">Total</flux:heading>
                        <div class="text-right">
                            <flux:heading class="text-xl! font-mono text-zinc-900">
                                {{ format_currency($order->total) }}
                            </flux:heading>
                            <flux:text class="text-[10px] text-zinc-400 block -mt-1 uppercase">Inclusive of VAT
                            </flux:text>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Details --}}
            <div class="mt-10 pt-6 border-t border-zinc-100 grid grid-cols-2 gap-8 text-xs text-zinc-500">
                <div>
                    <p class="font-bold text-zinc-700 mb-1">Delivering To:</p>
                    <p>{{ $order->shipping_address['full_name'] }}</p>
                    <p>{{ $order->shipping_address['address'] }}</p>
                </div>
                <div>
                    <p class="font-bold text-zinc-700 mb-1">Payment Method:</p>
                    <p>{{ $this->paymentMethodLabel }}</p>
                    <p>Transaction: {{ $order->payment->transaction_id ?? 'PENDING' }}</p>
                </div>
            </div>
        </div>

        {{-- Actions (Not printed) --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3  print:hidden mt-5">
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
