<?php

use Livewire\Component;
use App\Models\Order;
use Livewire\Attributes\{Layout, Computed, Title};

new #[Title('Order Details')] #[Layout('layouts.customer')] class extends Component {
    public Order $order;

    public function mount(Order $order)
    {
        $this->order = $order->loadCount('items');
    }
};
?>

<div>
    <flux:card class="rounded-md p-0">
        <div class="flex items-center gap-3 px-3 py-2 border-b">
            <flux:button size="xs" icon="arrow-long-left" variant="ghost" class="cursor-pointer"
                :href="route('customer.orders.index')" wire:navigate></flux:button>

            <flux:heading size="lg">Order Details</flux:heading>
        </div>

        <section class="p-5">

            <div class="space-y-1">
                <flux:heading>Order n° {{ $order->reference }}</flux:heading>
                <flux:text>{{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}</flux:text>
                <flux:text>Placed on {{ $order->created_at->format('M j, Y') }}</flux:text>
                <flux:heading>{{ format_currency($order->total) }}</flux:heading>
            </div>

            <flux:separator class="my-5" />

            {{-- Items Section --}}
            <div>
                <flux:heading class="text-lg" class="mb-4">Items in Your Order</flux:heading>

                <div class="space-y-4">
                    @foreach ($order->items as $item)
                        <div class="border rounded-md p-4">
                            {{-- Status Badges & Date --}}
                            <div>
                                <flux:badge size="sm">
                                    {{ ucfirst($order->status) }}
                                </flux:badge>
                                {{-- @if ($order->status === 'delivered')
                                    <span class="text-xs text-zinc-500">
                                        On
                                        {{ $order->actual_delivery_date ? $order->actual_delivery_date->format('d-m') : '' }}
                                    </span>
                                @endif --}}
                            </div>

                            {{-- Item Content --}}
                            <div class="flex gap-4">
                                {{-- Product Image --}}
                                <div class="shrink-0">
                                    @if ($item->product?->image_path)
                                        <a :href=" route('products.show', $item->product)" wire:navigate>
                                            <img src="{{ $item->product->image_url }}" alt="{{ $item->name }}"
                                                class="w-20 h-20 object-contain rounded">
                                        </a>
                                    @else
                                        <div class="w-20 h-20 bg-zinc-100 rounded flex items-center justify-center">
                                            <flux:icon.photo class="w-8 h-8 text-zinc-300" />
                                        </div>
                                    @endif
                                </div>

                                {{-- Product Details --}}
                                <div class="flex-1">
                                    <flux:heading size="sm">{{ $item->name }}</flux:heading>
                                    <flux:text size="sm" class="text-zinc-500">{{ $item->quantity }} ×
                                        {{ format_currency($item->unit_price) }}</flux:text>
                                </div>

                                {{-- Actions --}}
                                <div class="shrink-0 flex flex-col items-end gap-2">
                                    <flux:button size="sm" variant="primary" icon="shopping-cart">Buy Again
                                    </flux:button>

                                    <flux:link href="{{ route('customer.orders.tracking', $order) }}" wire:navigate
                                        class="text-sm!">
                                        See
                                        Status History</flux:link>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Payment & Delivery Information --}}
            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 divide-x">
                {{-- Payment Information --}}

                <div class="px-4">
                    <flux:heading class="text-lg mb-4">Payment Information</flux:heading>

                    <div class="space-y-4">
                        <flux:text>Payment Method:</flux:text>
                    </div>
                </div>

                <div class="px-4">
                    <flux:heading class="text-lg mb-4">Delivery Information</flux:heading>

                    <div class="space-y-4">
                        <flux:text>Shipping Address:</flux:text>
                    </div>

                </div>

            </div>

            <flux:separator class="my-8" />

            <div class="flex flex-col items-center gap-3">
                <flux:text>Need help? <flux:link>Contact Support</flux:link>
                </flux:text>

                <flux:button size="sm" icon="arrow-down-tray" class="cursor-pointer">Download Receipt</flux:button>
            </div>
        </section>
    </flux:card>
</div>
