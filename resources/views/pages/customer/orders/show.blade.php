<?php

use Livewire\Component;
use App\Models\Order;
use Livewire\Attributes\{Layout, Computed, Title};

new #[Title('Order Details')] #[Layout('layouts.customer')] class extends Component {
    public Order $order;
};
?>

<div>
    <div class="bg-white border rounded-lg">
        <!-- Header -->
        <div class="px-4 py-2 flex items-center gap-3 border-b">
            <a href="{{ route('customer.orders.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700">
                <flux:icon.arrow-left class="w-5 h-5" />
            </a>
            <h1 class="text-lg font-medium text-zinc-900">Order Details</h1>
        </div>

        <section class="p-5">
            <div class="space-y-1">
                <p class="text-lg font-semibold text-zinc-900">Order n° {{ $order->reference }}</p>
                <p class="text-sm text-zinc-500">{{ $order->items->count() }}
                    {{ Str::plural('Item', $order->items->count()) }}</p>
                <p class="text-sm text-zinc-500">
                    Placed on
                    {{ $order->placed_at ? $order->placed_at->format('d-m-Y') : $order->created_at->format('d-m-Y') }}
                </p>
                <p class="text-sm text-zinc-500">Total: {{ $order->formatted_total }}</p>
            </div>

            <flux:separator class="my-4" />


            <!-- Items Section -->
            <div>
                <h2 class="text-sm font-semibold text-zinc-700 uppercase tracking-wide mb-4">Items In Your Order</h2>

                <div class="space-y-4">
                    @foreach ($order->items as $item)
                        <div class="border rounded-lg p-4">
                            <!-- Status Badges & Date -->
                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                <flux:badge :color="$order->status_badge_color" size="sm">
                                    {{ $order->status_label }}
                                </flux:badge>
                                @if ($order->status === 'delivered')
                                    <span class="text-xs text-zinc-500">
                                        On
                                        {{ $order->actual_delivery_date ? $order->actual_delivery_date->format('d-m') : '' }}
                                    </span>
                                @endif
                            </div>

                            <!-- Item Content -->
                            <div class="flex gap-4">
                                <!-- Product Image -->
                                <div class="shrink-0">
                                    @if ($item->product?->image_path)
                                        <a href="{{ route('products.show', $item->product) }}">
                                            <img src="{{ $item->product->image_url }}" alt="{{ $item->name }}"
                                                class="w-20 h-20 object-contain rounded">
                                        </a>
                                    @else
                                        <div class="w-20 h-20 bg-zinc-100 rounded flex items-center justify-center">
                                            <flux:icon.photo class="w-8 h-8 text-zinc-300" />
                                        </div>
                                    @endif
                                </div>

                                <!-- Product Details -->
                                <div class="flex-1 min-w-0">
                                    @if ($item->product)
                                        <a href="{{ route('products.show', $item->product) }}"
                                            class="text-sm text-sheffield-blue hover:underline line-clamp-2">
                                            {{ $item->name }}
                                        </a>
                                    @else
                                        <p class="text-sm text-zinc-900 line-clamp-2">{{ $item->name }}</p>
                                    @endif

                                    <p class="text-sm text-zinc-500 mt-1">QTY: {{ $item->quantity }}</p>

                                    <div class="flex items-center gap-2 mt-1">
                                        <span
                                            class="text-sm font-semibold text-zinc-900">{{ $item->formatted_total }}</span>
                                        @if ($item->discount_cents > 0)
                                            <span
                                                class="text-sm text-zinc-400 line-through">{{ $item->formatted_unit_price }}</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="shrink-0 flex flex-col items-end gap-2">
                                    @if ($item->product)
                                        @php
                                            $isOutOfStock = $item->product->manage_stock
                                                ? $item->product->stock_quantity <= 0
                                                : $item->product->stock_status !== 'in_stock';
                                        @endphp
                                        <div x-data="{ loading: false }">
                                            <button type="button"
                                                @click="
                                                if (loading) return;
                                                loading = true;
                                                $dispatch('add-to-cart', { productId: {{ $item->product->id }} });
                                                setTimeout(() => loading = false, 1000);
                                            "
                                                @disabled($isOutOfStock)
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $isOutOfStock ? 'bg-zinc-100 text-zinc-400 cursor-not-allowed' : 'bg-sheffield-blue text-white hover:bg-sheffield-blue/90' }}">
                                                <template x-if="loading">
                                                    <svg class="animate-spin w-4 h-4" fill="none"
                                                        viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                        </path>
                                                    </svg>
                                                </template>
                                                <template x-if="!loading">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                </template>
                                                <span
                                                    x-text="loading ? 'Adding...' : '{{ $isOutOfStock ? 'Out of Stock' : 'Buy Again' }}'"></span>
                                            </button>
                                        </div>
                                    @endif
                                    <a href="{{ route('customer.orders.tracking', $order) }}" wire:navigate
                                        class="text-sm text-sheffield-blue hover:underline">
                                        See Status History
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Payment & Delivery Information -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Payment Information -->
                <div class="border rounded-md">
                    <h2 class="text-sm font-semibold border-b px-5 py-2 text-zinc-700 uppercase tracking-wide mb-4">
                        Payment Information
                    </h2>

                    <div class="space-y-4 p-5">
                        <div>
                            <h3 class="font-medium text-zinc-900 mb-1">Payment Method</h3>
                            <p class="text-sm text-zinc-600">
                                {{-- @if ($order->payment->isNotEmpty())
                                    {{ ucfirst($order->payment->first()->method ?? 'N/A') }}
                                @else
                                    Pay on delivery
                                @endif --}}
                            </p>
                        </div>

                        <div>
                            <h3 class="font-medium text-zinc-900 mb-2">Payment Details</h3>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-zinc-600">Items total:</span>
                                    <span class="text-zinc-900">{{ $order->formatted_subtotal }}</span>
                                </div>
                                @if ($order->discount_cents > 0)
                                    <div class="flex justify-between">
                                        <span class="text-zinc-600">Discount:</span>
                                        <span class="text-green-600">-{{ $order->formatted_discount }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-zinc-600">Delivery Fees:</span>
                                    <span
                                        class="text-zinc-900">{{ $order->shipping_cents > 0 ? $order->formatted_shipping : 'Free' }}</span>
                                </div>
                                @if ($order->tax_cents > 0)
                                    <div class="flex justify-between">
                                        <span class="text-zinc-600">Tax:</span>
                                        <span class="text-zinc-900">{{ $order->formatted_tax }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between pt-2 border-t mt-2">
                                    <span class="font-medium text-zinc-900">Total:</span>
                                    <span class="font-semibold text-zinc-900">{{ $order->formatted_total }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Information -->
                <div class="border rounded-md">
                    <h2 class="text-sm font-semibold border-b px-5 py-2 text-zinc-700 uppercase tracking-wide mb-4">
                        Delivery Information
                    </h2>

                    <div class="space-y-4 p-5">
                        <div>
                            <h3 class="font-medium text-zinc-900 mb-1">Delivery Method</h3>
                            <p class="text-sm text-zinc-600">
                                {{ $order->is_pickup ? 'Pick-up Station' : 'Door Delivery' }}
                            </p>
                        </div>

                        @if ($order->is_pickup && $order->warehouse)
                            <div>
                                <h3 class="font-medium text-zinc-900 mb-1">Pick-up Station Address</h3>
                                <div class="text-sm text-zinc-600 space-y-1">
                                    <p class="font-medium">{{ $order->warehouse->name }}</p>
                                    @if ($order->warehouse->address)
                                        <p>{{ $order->warehouse->address }}</p>
                                    @endif
                                </div>
                                @if ($order->pickup_status)
                                    <p class="text-sm mt-2">
                                        <span class="text-zinc-600">Status:</span>
                                        <span
                                            class="{{ $order->pickup_status === 'Collected' ? 'text-green-600' : 'text-blue-600' }} font-medium">
                                            {{ $order->pickup_status }}
                                        </span>
                                    </p>
                                @endif
                            </div>
                        @else
                            <div>
                                <h3 class="font-medium text-zinc-900 mb-1">Shipping Address</h3>
                                @if ($order->shipping_address)
                                    <div class="text-sm text-zinc-600 space-y-1">
                                        @if (isset($order->shipping_address['first_name']) || isset($order->shipping_address['last_name']))
                                            <p class="font-medium text-zinc-900">
                                                {{ $order->shipping_address['first_name'] ?? '' }}
                                                {{ $order->shipping_address['last_name'] ?? '' }}
                                            </p>
                                        @endif
                                        @if (isset($order->shipping_address['address']))
                                            <p>{{ $order->shipping_address['address'] }}</p>
                                        @endif
                                        @if (isset($order->shipping_address['city']) || isset($order->shipping_address['region']))
                                            <p>
                                                {{ $order->shipping_address['city'] ?? '' }}{{ isset($order->shipping_address['city']) && isset($order->shipping_address['region']) ? ', ' : '' }}{{ $order->shipping_address['region'] ?? '' }}
                                            </p>
                                        @endif
                                        @if (isset($order->shipping_address['phone']))
                                            <p class="pt-1">{{ $order->shipping_address['phone'] }}</p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div>
                            <h3 class="font-medium text-zinc-900 mb-1">Shipping Details</h3>
                            <div class="text-sm text-zinc-600 space-y-1">
                                @if ($order->shippingZone)
                                    <p>{{ $order->shippingZone->name }}</p>
                                @endif
                                @if ($order->status === 'delivered' && $order->actual_delivery_date)
                                    <p>
                                        Delivered on
                                        <span
                                            class="text-green-600 font-medium">{{ $order->actual_delivery_date->format('d F Y') }}</span>
                                    </p>
                                @elseif ($order->estimated_delivery_range)
                                    <p>
                                        Delivery scheduled on
                                        <span
                                            class="text-sheffield-blue font-medium">{{ $order->estimated_delivery_range }}</span>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
