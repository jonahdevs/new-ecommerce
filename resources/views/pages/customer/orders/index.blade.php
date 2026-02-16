<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, Computed};
use Livewire\WithPagination;

new #[Layout('layouts.customer')] class extends Component {
    use WithPagination;

    #[Computed]
    public function orders()
    {
        return auth()->user()->orders()->with('items.product')->latest('placed_at')->latest()->paginate(10);
    }
};
?>

<div>
    <flux:card class="bg-white border rounded-md p-0">
        <!-- Page Header -->
        <div class="px-3 py-2 border-b">
            <flux:heading>Orders</flux:heading>
        </div>

        <section class="px-4 py-6 pt-4">
            @if ($this->orders->isEmpty())
                <!-- Empty State -->
                <div class="bg-white border rounded-lg p-12 text-center">
                    <div class="mx-auto w-24 h-24 bg-zinc-100 rounded-full flex items-center justify-center mb-6">
                        <flux:icon.shopping-bag class="w-12 h-12 text-zinc-400" />
                    </div>
                    <h2 class="text-xl font-semibold text-zinc-900 mb-2">No orders yet</h2>
                    <p class="text-zinc-600 mb-6 max-w-md mx-auto">
                        When you place an order, it will appear here. Start shopping to discover amazing products!
                    </p>
                    <flux:button href="{{ route('products.index') }}" variant="primary" wire:navigate>
                        Start Shopping
                    </flux:button>
                </div>
            @else
                <!-- Tabs -->
                <div x-data="{ tab: 'active' }" class="space-y-6">
                    <div class="flex border-b">
                        <button @click="tab = 'active'"
                            :class="tab === 'active' ? 'border-b-2 border-orange-500 text-orange-600' :
                                'text-zinc-600 hover:text-zinc-900'"
                            class="flex-1 sm:flex-none px-4 py-2 font-medium text-sm uppercase tracking-wide transition-colors cursor-pointer">
                            Ongoing/Delivered
                            <span class="ml-2 text-xs text-zinc-500">
                                ({{ $this->orders->filter(fn($o) => in_array($o->status, ['pending', 'processing', 'shipped', 'delivered']))->count() }})
                            </span>
                        </button>
                        <button @click="tab = 'cancelled'"
                            :class="tab === 'cancelled' ? 'border-b-2 border-orange-500 text-orange-600' :
                                'text-zinc-600 hover:text-zinc-900'"
                            class="flex-1 sm:flex-none px-4 py-2 font-medium text-sm uppercase tracking-wide transition-colors cursor-pointer">
                            Cancelled/Returned
                            <span class="ml-2 text-xs text-zinc-500">
                                ({{ $this->orders->filter(fn($o) => in_array($o->status, ['cancelled', 'returned']))->count() }})
                            </span>
                        </button>
                    </div>

                    <!-- Tab Content: Active Orders -->
                    <div x-show="tab === 'active'" class="divide-y space-y-2">
                        @php
                            $activeOrders = $this->orders->filter(
                                fn($o) => in_array($o->status, ['pending', 'processing', 'shipped', 'delivered']),
                            );
                        @endphp

                        @forelse ($activeOrders as $order)
                            <div
                                class="border rounded-md p-5 hover:bg-zinc-50 transition-colors flex items-center justify-between gap-4">
                                <!-- Product Image -->
                                <div class="shrink-0">
                                    @if ($order->items->first()?->product?->image_path)
                                        <img src="{{ $order->items->first()->product->image_url }}"
                                            alt="{{ $order->items->first()->name }}"
                                            class="w-16 h-16 rounded object-cover">
                                    @else
                                        <div
                                            class="w-16 h-16 rounded bg-zinc-100 border flex items-center justify-center">
                                            <flux:icon.photo class="w-8 h-8 text-zinc-300" />
                                        </div>
                                    @endif
                                </div>

                                <!-- Order Details -->
                                <div class="flex-1 min-w-0">
                                    <!-- Product Name -->
                                    <h3 class="text-sm font-medium text-zinc-900 truncate">
                                        {{ $order->items->first()?->name ?? 'Order' }}
                                    </h3>

                                    <!-- Order Info -->
                                    <div class="flex items-center gap-4 mt-1 text-xs text-zinc-600">
                                        <span>Order {{ $order->reference }}</span>
                                        <flux:badge :color="$order->status_badge_color" size="sm">
                                            {{ $order->status_label }}
                                        </flux:badge>
                                    </div>

                                    <!-- Delivery Date -->
                                    <p class="text-xs text-zinc-600 mt-1">
                                        @if ($order->status === 'delivered' && $order->actual_delivery_date)
                                            On {{ $order->actual_delivery_date->format('m-d') }}
                                        @elseif ($order->status === 'shipped' && $order->estimated_delivery_range)
                                            Est. {{ $order->estimated_delivery_range }}
                                        @else
                                            Placed
                                            {{ $order->placed_at ? $order->placed_at->format('m-d-Y') : $order->created_at->format('m-d-Y') }}
                                        @endif
                                    </p>
                                </div>

                                <!-- Action Link -->
                                <div class="shrink-0">
                                    <a href="{{ route('customer.orders.show', $order) }}" wire:navigate
                                        class="text-sm font-medium text-orange-500 hover:text-orange-600">
                                        See details
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="bg-white p-8 text-center text-zinc-600">
                                <p>No ongoing or delivered orders</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Tab Content: Cancelled/Returned Orders -->
                    <div x-show="tab === 'cancelled'" class="divide-y">
                        @php
                            $cancelledOrders = $this->orders->filter(
                                fn($o) => in_array($o->status, ['cancelled', 'returned']),
                            );
                        @endphp

                        @forelse ($cancelledOrders as $order)
                            <div
                                class="bg-white p-5 hover:bg-zinc-50 transition-colors flex items-center justify-between gap-4">
                                <!-- Product Image -->
                                <div class="shrink-0">
                                    @if ($order->items->first()?->product?->image_path)
                                        <img src="{{ $order->items->first()->product->image_url }}"
                                            alt="{{ $order->items->first()->name }}"
                                            class="w-16 h-16 rounded object-cover border">
                                    @else
                                        <div
                                            class="w-16 h-16 rounded bg-zinc-100 border flex items-center justify-center">
                                            <flux:icon.photo class="w-8 h-8 text-zinc-300" />
                                        </div>
                                    @endif
                                </div>

                                <!-- Order Details -->
                                <div class="flex-1 min-w-0">
                                    <!-- Product Name -->
                                    <h3 class="text-sm font-medium text-zinc-900 truncate">
                                        {{ $order->items->first()?->name ?? 'Order' }}
                                    </h3>

                                    <!-- Order Info -->
                                    <div class="flex items-center gap-4 mt-1 text-xs text-zinc-600">
                                        <span>Order {{ $order->reference }}</span>
                                        <flux:badge :color="$order->status_badge_color" size="sm">
                                            {{ $order->status_label }}
                                        </flux:badge>
                                    </div>

                                    <!-- Delivery Date -->
                                    <p class="text-xs text-zinc-600 mt-1">
                                        @if ($order->status === 'delivered' && $order->actual_delivery_date)
                                            On {{ $order->actual_delivery_date->format('m-d') }}
                                        @elseif ($order->status === 'shipped' && $order->estimated_delivery_range)
                                            Est. {{ $order->estimated_delivery_range }}
                                        @else
                                            Placed
                                            {{ $order->placed_at ? $order->placed_at->format('m-d-Y') : $order->created_at->format('m-d-Y') }}
                                        @endif
                                    </p>
                                </div>

                                <!-- Action Link -->
                                <div class="shrink-0">
                                    <a href="{{ route('customer.orders.show', $order) }}" wire:navigate
                                        class="text-sm font-medium text-orange-500 hover:text-orange-600">
                                        See details
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="bg-white p-8 text-center text-zinc-600">
                                <p>No cancelled or returned orders</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Pagination -->
                @if ($this->orders->hasPages())
                    <div class="mt-6">
                        {{ $this->orders->links() }}
                    </div>
                @endif
            @endif
        </section>
    </flux:card>
</div>
