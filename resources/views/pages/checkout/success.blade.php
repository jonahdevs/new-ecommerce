<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

new #[Layout('layouts.guest')] class extends Component {
    public ?Order $order = null;

    public function mount()
    {
        $token = request()->query('token');

        $sessionToken = session('payment_success_token');
        $orderId = session('payment_success_order_id');
        $expiresAt = session('payment_success_expires_at');

        if ($orderId) {
            $this->order = Order::with(['payment', 'items.product', 'user'])->find($orderId);
        }
    }
};
?>



<div class="mx-auto max-w-4xl px-4 py-12">

    @if ($order)
        <div class="flex flex-col gap-1 items-center text-center">
            <flux:icon.check-circle class="size-12 text-green-500" />
            <flux:heading class="text-2xl!">Thank you</flux:heading>
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
                    <flux:heading>Card</flux:heading>
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
                                <flux:text class="font-medium text-gray-900">
                                    {{ $item->quantity }} × {{ $item->product->name }}
                                </flux:text>
                                <flux:text class="font-medium text-gray-900">
                                    {{ format_currency($item->price * $item->quantity) }}
                                </flux:text>
                            </div>
                        </div>
                    </div>

                    @if (!$loop->last)
                        <div class="border-t border-gray-100"></div>
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
                        <span>{{ $order->user?->phone ?? ($order->shipping_address['phone'] ?? 'N/A') }}</span>
                    </flux:text>
                </div>

                <div class="space-y-1">
                    <flux:heading>Shipping Address</flux:heading>

                    <flux:text>
                        <span class="text-zinc-800">Email:</span>
                        {{ $order->user?->email ?? ($order->shipping_address['email'] ?? 'N/A') }}
                    </flux:text>

                    <flux:text>
                        <span class="text-zinc-800">Phone:</span>
                        <span>{{ $order->user?->phone ?? ($order->shipping_address['phone'] ?? 'N/A') }}</span>
                    </flux:text>
                </div>
            </div>
        </div>
    @endif
</div>
