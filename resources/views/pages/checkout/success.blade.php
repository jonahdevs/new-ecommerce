<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;

new #[Layout('layouts.guest')] class extends Component {
    public ?Order $order = null;
    public bool $verifying = false;

    public function mount()
    {
        $orderId = session('payment_success_order_id');

        if (!$orderId) {
            return redirect()->route('checkout.summary')->with('error', 'Session expired. Please contact support if you were charged.');
        }

        $this->order = Order::with(['payment', 'items.product', 'user'])->find($orderId);

        if (!$this->order) {
            return redirect()->route('checkout.summary')->with('error', 'Order not found. Please contact support.');
        }

        // If webhook hasn't fired yet, show verifying state
        // $this->verifying = $this->order->payment_status !== 'paid';
    }

    public function checkPaymentStatus()
    {
        // Stop polling once confirmed
        // if (!$this->verifying) {
        //     return;
        // }

        $this->order->refresh();

        // if ($this->order->payment->status === 'paid') {
        //     $this->verifying = false;
        // }
    }
};
?>

<div class="mx-auto max-w-4xl px-4 py-12 min-h-[77svh]">
    @if ($verifying)
        <div wire:poll.2000ms="checkPaymentStatus" class="flex flex-col items-center text-center py-12">
            <flux:icon.loading class="size-10 text-primary" />
            <flux:heading class="text-2xl! mt-4">Verifying Payment</flux:heading>
            <flux:text class="text-zinc-500 mt-1">Please wait while we confirm your payment...</flux:text>
        </div>
    @else
        <div class="flex flex-col items-center text-center">
            <flux:icon.check-circle class="size-12 text-green-500" />
            <flux:heading class="text-2xl! mt-1">Thank you</flux:heading>
            <flux:text class="text-lg">Your order has been received</flux:text>
            <flux:text class="text-xs!">You will receive an email confirmation shortly</flux:text>
        </div>

        <flux:card class="mt-3">
            <flux:heading>Order details</flux:heading>

            <div class="space-y-3 mt-3">
                <div class="flex items-center justify-between">
                    <flux:text>Order number:</flux:text>
                    <flux:heading>{{ $order->reference }}</flux:heading>
                </div>

                <div class="flex items-center justify-between">
                    <flux:text>Date:</flux:text>
                    <flux:heading>{{ $order->created_at->format('M j, Y') }}</flux:heading>
                </div>

                <div class="flex items-center justify-between">
                    <flux:text>Payment method:</flux:text>
                    <flux:heading>{{ ucfirst($order->payment->method ?? 'Card') }}</flux:heading>
                </div>

                <flux:separator />

                <div class="flex items-center">
                    <div class="flex-1"></div>
                    <div class="w-full max-w-sm space-y-2">
                        <div class="flex items-center justify-between">
                            <flux:text>Subtotal:</flux:text>
                            <flux:heading>{{ format_currency($order->subtotal) }}</flux:heading>
                        </div>
                        <div class="flex items-center justify-between">
                            <flux:text class="font-semibold text-zinc-800">Total:</flux:text>
                            <flux:heading>{{ format_currency($order->total) }}</flux:heading>
                        </div>
                    </div>
                </div>
            </div>
        </flux:card>

        <div class="py-6">
            <flux:heading size="lg" class="mb-6">Products</flux:heading>

            <div class="space-y-6">
                @foreach ($order->items as $item)
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex justify-between items-start mb-2">
                                <flux:text class="font-medium">
                                    {{ $item->quantity }} × {{ $item->product->name }}
                                </flux:text>
                                <flux:text class="font-medium">
                                    {{ format_currency($item->price * $item->quantity) }}
                                </flux:text>
                            </div>
                        </div>
                    </div>

                    @if (!$loop->last)
                        <div class="border-t border-dotted border-zinc-200"></div>
                    @endif
                @endforeach
            </div>
        </div>

        <flux:separator />

        <div class="py-6">
            <flux:heading size="lg" class="mb-6">Customer Details</flux:heading>

            <div class="grid grid-cols-2">
                <div class="space-y-1">
                    <flux:heading>Contact</flux:heading>
                    <flux:text>
                        <span class="text-zinc-800">Email:</span>
                        {{ $order->user?->email ?? ($order->shipping_address['email'] ?? 'N/A') }}
                    </flux:text>
                    <flux:text>
                        <span class="text-zinc-800">Phone:</span>
                        {{ $order->user?->phone_number ?? ($order->shipping_address['phone_number'] ?? 'N/A') }}
                    </flux:text>
                </div>

                <div class="space-y-1">
                    <flux:heading>Shipping Address</flux:heading>
                    <flux:text>
                        {{ trim(($order->shipping_address['first_name'] ?? '') . ' ' . ($order->shipping_address['last_name'] ?? '')) ?: 'N/A' }}
                    </flux:text>
                    <flux:text>{{ $order->shipping_address['address'] ?? 'N/A' }}</flux:text>
                </div>
            </div>
        </div>
    @endif
</div>
