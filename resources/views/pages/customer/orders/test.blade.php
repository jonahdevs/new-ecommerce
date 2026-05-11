<?php

use App\Enums\{OrderStatus, PaymentStatus};
use App\Models\Order;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;
use App\Services\CartService;

new #[Title('Order Details')] #[Layout('layouts.customer')] class extends Component {
    public Order $order;

    public function mount(Order $order): void
    {
        // Guard: order must belong to the authenticated customer
        if ($order->user_id !== auth()->id()) {
            $this->redirectRoute('customer.orders.index', navigate: true);
            return;
        }

        $this->order = $order
            ->load([
                'items.product.brand',
                'payment',
                'statusHistories',
                'quote',
            ])
            ->loadCount('items');
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
    public function hasKraReceipt(): bool
    {
        return $this->order->hasKraReceipt();
    }

    #[Computed]
    public function isAwaitingKraValidation(): bool
    {
        return $this->order->isAwaitingKraValidation();
    }

    #[Computed]
    public function hasSapSyncFailed(): bool
    {
        return $this->order->hasSapSyncFailed();
    }

    // =====================================================
    // Actions
    // =====================================================

    public function buyAgain(int $productId): void
    {
        try {
            app(CartService::class)->addItem($productId, 1);
            $this->dispatch('cart-updated');
            $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: 'Item added to your cart');
        } catch (\RuntimeException $th) {
            $this->dispatch('notify', title: 'Add to Cart Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add item to cart');
        }
    }
};
?>

<div>
    <x-customer.card title="Order" :titleEm="'#' . $order->reference">
        <x-slot:icon>
            <flux:icon.package />
        </x-slot:icon>
        <x-slot:action>
            <a href="{{ route('customer.orders.index') }}" wire:navigate
                class="flex items-center gap-1.5 text-xs font-bold tracking-wider uppercase text-zinc-500 hover:text-primary transition-colors">
                <flux:icon.chevron-left class="w-3.5 h-3.5 stroke-2" />
                Back to Orders
            </a>
        </x-slot:action>

        <div class="flex flex-col gap-8">
            {{-- Tracking Timeline --}}
            @php
                $mainPath = [
                    OrderStatus::PENDING,
                    OrderStatus::CONFIRMED,
                    OrderStatus::PROCESSING,
                    OrderStatus::SHIPPED,
                    OrderStatus::DELIVERED,
                ];
                $currentStatus = $order->status;
                $currentStatusIndex = array_search($currentStatus, $mainPath);
                $isTerminal = in_array($currentStatus, [OrderStatus::CANCELLED, OrderStatus::RETURNED]);
                $histories = $order->statusHistories->keyBy('to_status');
            @endphp

            @if (!$isTerminal)
                <div class="relative w-full py-4 px-2 overflow-x-auto scrollbar-hide">
                    <div class="min-w-[600px] flex justify-between relative">
                        {{-- Connector Line Background --}}
                        <div class="absolute top-4.5 left-0 w-full h-1 bg-zinc-100 -z-0"></div>
                        {{-- Connector Line Active --}}
                        @if ($currentStatusIndex !== false && $currentStatusIndex > 0)
                            <div class="absolute top-4.5 left-0 h-1 bg-primary -z-0 transition-all duration-500"
                                style="width: {{ ($currentStatusIndex / (count($mainPath) - 1)) * 100 }}%"></div>
                        @endif

                        @foreach ($mainPath as $index => $step)
                            @php
                                $reached = $currentStatusIndex !== false && $index <= $currentStatusIndex;
                                $isCurrent = $currentStatusIndex !== false && $index === $currentStatusIndex;
                                $history = $histories->get($step->value);
                            @endphp
                            <div class="relative z-10 flex flex-col items-center group w-24">
                                <div @class([
                                    'w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300',
                                    'bg-primary text-white shadow-lg ring-4 ring-[#fff8f6]' => $reached,
                                    'bg-white text-zinc-300 border-2 border-zinc-100' => !$reached,
                                ])>
                                    <flux:icon :name="$step->icon()" class="size-5" />
                                </div>
                                <div class="mt-3 text-center">
                                    <div @class([
                                        'text-[11px] font-bold uppercase tracking-wider',
                                        'text-zinc-950' => $reached,
                                        'text-zinc-400' => !$reached,
                                    ])>{{ $step->label() }}</div>
                                    @if ($history)
                                        <div class="text-[9px] text-zinc-500 mt-0.5 font-medium whitespace-nowrap">
                                            {{ $history->created_at->format('d M, g:i A') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                {{-- Special view for Cancelled/Returned --}}
                <div @class([
                    'flex items-center gap-4 p-4 rounded-sm border',
                    'bg-red-50 border-red-100 text-red-700' => $currentStatus === OrderStatus::CANCELLED,
                    'bg-orange-50 border-orange-100 text-orange-700' => $currentStatus === OrderStatus::RETURNED,
                ])>
                    <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center shrink-0">
                        <flux:icon :name="$currentStatus->icon()" class="size-6" />
                    </div>
                    <div>
                        <div class="text-sm font-bold uppercase tracking-wider">Order {{ $currentStatus->label() }}</div>
                        <div class="text-xs opacity-80 mt-1">
                            This order was {{ strtolower($currentStatus->label()) }} on
                            {{ $order->updated_at->format('M j, Y \a\t g:i A') }}.
                        </div>
                    </div>
                </div>
            @endif

            {{-- Main Info Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Left: Items --}}
                <div class="lg:col-span-2 flex flex-col gap-6">
                    <div>
                        <h3 class="text-[15px] font-black uppercase tracking-wider text-zinc-950 mb-4 font-serif">
                            Items in Your Order <span class="text-primary ml-1">({{ $order->items_count }})</span>
                        </h3>

                        <div class="flex flex-col border border-zinc-200 divide-y divide-zinc-200 rounded-sm">
                            @foreach ($order->items as $item)
                                @php
                                    $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                                    $sku = $item->product_snapshot['sku'] ?? null;
                                    $brand = $item->product?->brand?->name ?? null;
                                    $imagePath = $item->product_image_url ?? $item->product?->image_url;
                                    $inStock = ($item->product?->stock_quantity ?? 0) > 0;
                                @endphp

                                <div class="flex items-center gap-4 p-4 hover:bg-zinc-50 transition-colors">
                                    <div class="w-16 h-16 bg-zinc-50 flex items-center justify-center shrink-0 border border-zinc-100 relative">
                                        @if ($imagePath)
                                            <img src="{{ asset($imagePath) }}" alt="{{ $name }}"
                                                class="w-[85%] h-[85%] object-contain">
                                        @else
                                            <flux:icon.photo class="w-8 h-8 text-zinc-200" />
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-[10px] font-bold tracking-widest uppercase text-zinc-500 mb-0.5">
                                            {{ $brand }} @if ($sku) · SKU: {{ $sku }} @endif
                                        </div>
                                        <div class="text-[14px] font-bold text-zinc-950 truncate mb-1">
                                            {{ $name }}
                                        </div>
                                        <div class="text-[12px] text-zinc-600 font-medium">
                                            {{ $item->quantity }} × {{ format_currency($item->unit_price_cents / 100) }}
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-2 shrink-0">
                                        <div class="text-[14px] font-black text-primary font-barlow-condensed tracking-tight">
                                            {{ format_currency($item->total_cents / 100) }}
                                        </div>
                                        <button wire:click="buyAgain({{ $item->product_id }})" @if (!$inStock) disabled @endif
                                            class="text-[10px] font-bold uppercase tracking-widest text-zinc-500 hover:text-primary disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                            {{ $inStock ? 'Buy Again' : 'Out of Stock' }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Converted from quote notice --}}
                    @if ($order->wasConvertedFromQuote() && $order->quote)
                        <div class="flex items-center gap-3 p-4 bg-[#f0f9ff] border border-[#bae6fd] rounded-sm text-blue-800">
                            <flux:icon.tag class="size-5 shrink-0" />
                            <div class="text-xs font-medium leading-relaxed">
                                This order was created from quote <flux:link :href="route('customer.quotes.show', $order->quote)" wire:navigate class="font-bold underline">{{ $order->quote->reference }}</flux:link>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Right: Summary & Info --}}
                <div class="flex flex-col gap-6">
                    {{-- Order Totals --}}
                    <div class="bg-zinc-50 border border-zinc-200 rounded-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-zinc-200 bg-white">
                            <h3 class="text-[13px] font-bold uppercase tracking-widest text-zinc-950 font-serif">Order Summary</h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex justify-between text-[13px]">
                                <span class="text-zinc-500 font-medium">Subtotal</span>
                                <span class="text-zinc-950 font-bold">{{ format_currency($order->subtotal) }}</span>
                            </div>
                            @if ($order->discount > 0)
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-green-600 font-medium">Discount</span>
                                    <span class="text-green-600 font-bold">− {{ format_currency($order->discount) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-[13px]">
                                <span class="text-zinc-500 font-medium">Shipping</span>
                                <span class="text-zinc-950 font-bold">
                                    {{ $order->shipping == 0 ? 'FREE' : format_currency($order->shipping) }}
                                </span>
                            </div>
                            <div class="pt-3 border-t border-zinc-200 flex justify-between items-baseline">
                                <span class="text-[14px] font-bold uppercase tracking-widest text-zinc-950">Total</span>
                                <span class="text-[24px] font-black text-primary font-barlow-condensed leading-none">
                                    {{ format_currency($order->total) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Delivery Info --}}
                    <div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-zinc-200">
                            <h3 class="text-[12px] font-bold uppercase tracking-widest text-zinc-950 font-serif">Shipping Address</h3>
                        </div>
                        <div class="p-5 text-[13px] leading-relaxed">
                            <div class="font-bold text-zinc-950 mb-1">
                                {{ trim(($order->shipping_address['first_name'] ?? '') . ' ' . ($order->shipping_address['last_name'] ?? '')) ?: $order->shipping_address['full_name'] ?? 'N/A' }}
                            </div>
                            <div class="text-zinc-500">
                                {{ format_phone($order->shipping_address['phone_number'] ?? '') }}<br>
                                {{ $order->shipping_address['address'] ?? 'N/A' }}<br>
                                {{ implode(', ', array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null])) }}
                            </div>
                            @if ($order->shipping_snapshot['method_name'] ?? null)
                                <div class="mt-4 pt-4 border-t border-zinc-100 flex items-start gap-3">
                                    <flux:icon.truck class="size-5 text-zinc-400 shrink-0 mt-0.5" />
                                    <div>
                                        <div class="text-[11px] font-bold uppercase text-zinc-400 mb-0.5">Method</div>
                                        <div class="font-bold text-zinc-900 leading-tight">{{ $order->shipping_snapshot['method_name'] }}</div>
                                        @if ($order->shipping_snapshot['delivery_window'] ?? null)
                                            <div class="text-[11px] text-zinc-500 mt-1 italic font-medium">Est. {{ $order->shipping_snapshot['delivery_window'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            @if ($order->tracking_number)
                                <div class="mt-3 pt-3 border-t border-zinc-100">
                                    <div class="text-[11px] font-bold uppercase text-zinc-400 mb-0.5">Tracking Number</div>
                                    <div class="font-mono text-[13px] font-bold text-primary">{{ $order->tracking_number }}</div>
                                    @if ($order->courier_name)
                                        <div class="text-[11px] text-zinc-500 mt-0.5">via {{ $order->courier_name }}</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Payment Info --}}
                    <div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-zinc-200">
                            <h3 class="text-[12px] font-bold uppercase tracking-widest text-zinc-950 font-serif">Payment Method</h3>
                        </div>
                        <div class="p-5">
                            @if ($order->payment)
                                <div class="flex items-center justify-between mb-4">
                                    <div class="text-[13px] font-bold text-zinc-950 uppercase tracking-tight">
                                        {{ $order->payment->gateway ?? '—' }}
                                    </div>
                                    <flux:badge size="sm" :color="$order->payment->status->color()">
                                        {{ $order->payment->status->label() }}
                                    </flux:badge>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-[12px]">
                                        <span class="text-zinc-500">Amount Paid</span>
                                        <span class="font-bold text-zinc-950">{{ format_currency(($order->payment->amount_cents ?? 0) / 100) }}</span>
                                    </div>
                                    @if ($order->payment->paid_at)
                                        <div class="flex justify-between text-[12px]">
                                            <span class="text-zinc-500">Transaction Date</span>
                                            <span class="font-bold text-zinc-950">{{ $order->payment->paid_at->format('M j, Y') }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Tax Invoice Section --}}
                                @if ($this->isPaid)
                                    <div class="mt-6 pt-5 border-t border-zinc-200">
                                        <h4 class="text-[11px] font-bold uppercase tracking-widest text-zinc-400 mb-3">Tax Invoice</h4>

                                        @if ($this->hasKraReceipt)
                                            <flux:button tag="a" :href="route('customer.orders.receipt', $order)" size="sm" variant="customer-primary" icon="arrow-down-tray" class="w-full font-bold uppercase tracking-wider text-[10px]">
                                                Download Invoice
                                            </flux:button>
                                            <div class="mt-2 text-center">
                                                <div class="text-[10px] text-emerald-600 font-bold uppercase tracking-wide">KRA Validated</div>
                                                <div class="text-[9px] text-zinc-500 mt-0.5">CU No: {{ $order->kra_cu_number }}</div>
                                            </div>
                                        @elseif ($this->isAwaitingKraValidation)
                                            <div class="p-3 bg-purple-50 border border-purple-100 rounded-sm text-[11px] text-purple-700 leading-relaxed font-medium">
                                                Pending KRA validation. This usually takes a few minutes.
                                            </div>
                                        @elseif ($this->hasSapSyncFailed)
                                            <div class="p-3 bg-red-50 border border-red-100 rounded-sm text-[11px] text-red-700 leading-relaxed font-medium">
                                                Invoice generation issue. Support has been notified.
                                            </div>
                                        @else
                                            <div class="p-3 bg-zinc-50 border border-zinc-100 rounded-sm text-[11px] text-zinc-500 leading-relaxed italic">
                                                Your invoice is being prepared...
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <div class="p-4 bg-zinc-50 border border-zinc-200 border-dashed text-center">
                                    <div class="text-[12px] text-zinc-400 italic">No payment info available</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Help --}}
            <div class="mt-4 pt-8 border-t border-zinc-200 text-center pb-4">
                <div class="text-[13px] text-zinc-500">
                    Need help with this order? <a href="#" class="font-bold text-zinc-950 hover:text-primary transition-colors ml-1">Contact Support</a>
                </div>
            </div>
        </div>
    </x-customer.card>
</div>
