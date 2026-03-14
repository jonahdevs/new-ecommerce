<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;
use App\Services\CartService;

new #[Title('Order Details')] #[Layout('layouts.customer')] class extends Component {
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order->load(['items.product', 'payment'])->loadCount('items');
    }

    #[Computed]
    public function isPaid(): bool
    {
        return $this->order->payment?->status?->value === PaymentStatus::PAID->value;
    }

    public function buyAgain(int $productId): void
    {
        try {
            app(CartService::class)->addItem($productId, 1);
            $this->dispatch('cart-updated');
            $this->dispatch('notify', variant: 'success', message: 'Item added to cart.');
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', variant: 'danger', message: $e->getMessage());
        }
    }
};
?>

<div>
    <flux:card class="rounded-md p-0">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-3 py-2 border-b">
            <flux:button size="xs" icon="arrow-long-left" variant="ghost" class="cursor-pointer"
                :href="route('customer.orders.index')" wire:navigate />
            <flux:heading size="lg">Order Details</flux:heading>
        </div>

        <section class="p-5">

            {{-- Order meta --}}
            <div class="space-y-1">
                <flux:heading>Order n° {{ $order->reference }}</flux:heading>
                <flux:text>
                    {{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}
                </flux:text>
                <flux:text>Placed on {{ $order->created_at->format('M j, Y') }}</flux:text>
                <flux:heading>{{ format_currency($order->total) }}</flux:heading>
            </div>

            <flux:separator class="my-5" />

            {{-- ── Items ── --}}
            <flux:heading class="text-lg mb-4">Items in Your Order</flux:heading>

            <div class="space-y-4">
                @foreach ($order->items as $item)
                    @php
                        $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                        $sku = $item->product_snapshot['sku'] ?? null;
                        $imagePath = $item->product_image_url ?? $item->product?->image_url;
                    @endphp

                    <div class="border rounded-md p-4">

                        {{-- Status badge --}}
                        <div class="mb-3">
                            <flux:badge size="sm" :color="$order->status->color()">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </div>

                        <div class="flex gap-4">

                            {{-- Image --}}
                            <div class="shrink-0">
                                @if ($imagePath)
                                    <a href="{{ route('products.show', $item->product) }}" wire:navigate>
                                        <img src="{{ asset($imagePath) }}" alt="{{ $name }}"
                                            class="w-20 h-20 object-contain rounded" />
                                    </a>
                                @else
                                    <div class="w-20 h-20 bg-zinc-100 rounded flex items-center justify-center">
                                        <flux:icon.photo class="w-8 h-8 text-zinc-300" />
                                    </div>
                                @endif
                            </div>

                            {{-- Details --}}
                            <div class="flex-1">
                                <flux:heading size="sm">{{ $name }}</flux:heading>
                                @if ($sku)
                                    <flux:text size="sm" class="text-zinc-400">SKU: {{ $sku }}
                                    </flux:text>
                                @endif
                                <flux:text size="sm" class="text-zinc-500 mt-1">
                                    {{ $item->quantity }} × {{ format_currency($item->unit_price_cents / 100) }}
                                </flux:text>
                                <flux:text size="sm" class="font-semibold mt-1">
                                    {{ format_currency($item->total_cents / 100) }}
                                </flux:text>
                            </div>

                            {{-- Actions --}}
                            <div class="shrink-0 flex flex-col items-end gap-2">
                                @php
                                    $inStock = ($item->product?->stock_quantity ?? 0) > 0;
                                @endphp

                                @if ($order->status !== \App\Enums\OrdersStatus::PENDING_QUOTE)
                                    <flux:button size="sm" variant="primary" icon="shopping-cart"
                                        class="cursor-pointer" wire:click="buyAgain({{ $item->product_id }})"
                                        :disabled="!$inStock">
                                        {{ $inStock ? 'Buy Again' : 'Out of Stock' }}
                                    </flux:button>
                                @endif

                                <flux:link href="{{ route('customer.orders.tracking', $order) }}" wire:navigate
                                    class="text-xs!">
                                    See Status History
                                </flux:link>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ── Order total breakdown ── --}}
            <div class="mt-6 space-y-1.5 max-w-xs ml-auto">
                <div class="flex justify-between text-sm">
                    <flux:text>Subtotal</flux:text>
                    <span>{{ format_currency($order->subtotal) }}</span>
                </div>

                @if ($order->discount > 0)
                    <div class="flex justify-between text-sm text-green-600">
                        <span>Discount</span>
                        <span>− {{ format_currency($order->discount) }}</span>
                    </div>
                @endif

                <div class="flex justify-between text-sm">
                    <flux:text>Shipping</flux:text>
                    <span>
                        @if ($order->shipping_snapshot['method_type'] === 'quote')
                            <span class="text-amber-500">TBD</span>
                        @elseif($order->shipping == 0)
                            Free
                        @else
                            {{ format_currency($order->shipping) }}
                        @endif
                    </span>
                </div>

                <div class="flex justify-between font-semibold border-t pt-2">
                    <span>Total</span>
                    <div class="text-right">
                        <span>{{ format_currency($order->total) }}</span>
                        @if ($order->shipping_snapshot['method_type'] === 'quote')
                            <p class="text-xs text-amber-500 font-normal">Excludes delivery</p>
                        @endif
                    </div>
                </div>
            </div>

            <flux:separator class="my-8" />

            {{-- ── Payment & Delivery ── --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-0 md:divide-x">

                {{-- Payment Information --}}
                <div class="px-4">
                    <flux:heading class="text-lg mb-4">Payment Information</flux:heading>

                    @if ($order->shipping_snapshot['method_type'] === 'quote')
                        <div class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                            <flux:icon.information-circle class="size-5 shrink-0 mt-0.5 text-amber-500" />
                            <div class="text-sm">
                                <p class="font-medium text-amber-800">Awaiting delivery quote</p>
                                <p class="text-amber-700 mt-0.5">
                                    Our team will contact you with a delivery cost.
                                    Payment will be collected once you confirm the quote.
                                </p>
                            </div>
                        </div>
                    @else
                        {{-- existing payment info block unchanged --}}
                        <div class="space-y-2">
                            ...
                        </div>
                    @endif
                </div>

                {{-- Delivery Information --}}
                <div class="px-4">
                    <flux:heading class="text-lg mb-4">Delivery Information</flux:heading>

                    <div class="space-y-1">
                        {{-- Full name --}}
                        <flux:text class="font-medium">
                            {{ trim(($order->shipping_address['first_name'] ?? '') . ' ' . ($order->shipping_address['last_name'] ?? '')) ?: $order->shipping_address['full_name'] ?? 'N/A' }}
                        </flux:text>

                        {{-- Phone --}}
                        <flux:text class="text-sm text-zinc-500">
                            {{ format_phone($order->shipping_address['phone_number'] ?? '') }}
                        </flux:text>

                        {{-- Address --}}
                        <flux:text class="text-sm text-zinc-500">
                            {{ $order->shipping_address['address'] ?? 'N/A' }}
                        </flux:text>

                        {{-- Area & County --}}
                        <flux:text class="text-sm text-zinc-500">
                            {{ implode(
                                ', ',
                                array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null]),
                            ) }}
                        </flux:text>
                    </div>

                    {{-- Shipping method from snapshot --}}
                    @if ($order->shipping_snapshot['method_name'] ?? null)
                        <flux:separator class="my-3" />
                        <flux:text class="text-xs text-zinc-400 mb-1">Shipping method</flux:text>
                        <flux:text class="text-sm font-medium">
                            {{ $order->shipping_snapshot['method_name'] }}
                        </flux:text>

                        @if ($order->shipping_snapshot['delivery_window'] ?? null)
                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                Est. {{ $order->shipping_snapshot['delivery_window'] }}
                            </flux:text>
                        @endif

                        @if ($order->shipping_snapshot['station_name'] ?? null)
                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                Pickup: {{ $order->shipping_snapshot['station_name'] }}
                            </flux:text>
                        @endif
                    @endif
                </div>

            </div>

            <flux:separator class="my-8" />

            {{-- Footer actions --}}
            <div class="flex flex-col items-center gap-3">
                <flux:text>
                    Need help?
                    <flux:link>Contact Support</flux:link>
                </flux:text>

                @if ($this->isPaid)
                    <flux:button size="sm" icon="arrow-down-tray" class="cursor-pointer" tag="a"
                        href="{{ route('customer.orders.receipt', $order) }}">
                        Download Receipt
                    </flux:button>
                @endif
            </div>

        </section>
    </flux:card>
</div>
