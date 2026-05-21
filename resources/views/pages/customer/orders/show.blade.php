<?php

use App\Enums\{OrderStatus, PaymentStatus};
use App\Models\Order;
use Livewire\Attributes\{Computed, Layout, On, Title};
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
                'items' => fn($q) => $q
                    ->select(['id', 'order_id', 'product_id', 'product_variant_id', 'product_snapshot', 'quantity', 'total_cents'])
                    ->with([
                        'product' => fn($q) => $q
                            ->select(['id', 'image_path', 'brand_id', 'stock_quantity'])
                            ->with(['brand:id,name']),
                    ]),
                'payment' => fn($q) => $q->select(['id', 'order_id', 'gateway', 'status', 'amount_cents', 'paid_at']),
                'quote' => fn($q) => $q->select(['id', 'reference']),
            ])
            ->loadCount('items');
    }

    // =====================================================
    // Real-time Updates
    // =====================================================

    #[On('echo-private:order.{order.id},.order.updated')]
    public function handleOrderUpdate(array $data): void
    {
        // Refresh the order from database
        $this->order = $this->order->fresh([
            'items' => fn($q) => $q
                ->select(['id', 'order_id', 'product_id', 'product_variant_id', 'product_snapshot', 'quantity', 'total_cents'])
                ->with([
                    'product' => fn($q) => $q
                        ->select(['id', 'image_path', 'brand_id', 'stock_quantity'])
                        ->with(['brand:id,name']),
                ]),
            'payment' => fn($q) => $q->select(['id', 'order_id', 'gateway', 'status', 'amount_cents', 'paid_at']),
            'quote' => fn($q) => $q->select(['id', 'reference']),
        ])->loadCount('items');

        // Clear computed caches
        unset($this->isPaid, $this->hasKraReceipt, $this->isAwaitingKraValidation, $this->hasSapSyncFailed);

    }

    // =====================================================
    // Computed
    // =====================================================

    #[Computed]
    public function isPaid(): bool
    {
        return $this->order->payment?->status?->value === PaymentStatus::PAID->value;
    }

    /**
     * True when the tax invoice PDF is ready for download.
     *
     * Requires both:
     *  - kra_cu_number: the CU number returned by eTIMS via SAP webhook
     *  - invoice_path: the generated PDF stored on disk
     *
     * The download button is hidden until both are present.
     * While waiting: sap_sync_status will be cu_pending.
     */
    #[Computed]
    public function hasKraReceipt(): bool
    {
        return $this->order->hasKraReceipt();
    }

    /**
     * True when SAP sync has completed but we are still
     * waiting for the eTIMS/KRA webhook to return the CU number.
     */
    #[Computed]
    public function isAwaitingKraValidation(): bool
    {
        return $this->order->isAwaitingKraValidation();
    }

    /**
     * True when SAP sync has permanently failed after all retries.
     */
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
    <div class="bg-white border border-zinc-200">
        <div class="p-4 bg-white border-b border-zinc-200 flex items-center justify-between flex-wrap gap-3">
            <a href="{{ route('customer.orders.index') }}" wire:navigate
                class="flex items-center gap-1.5 text-[12px] font-bold tracking-widest uppercase text-on-surface-variant transition-colors hover:text-primary cursor-pointer">
                <flux:icon.chevron-left class="w-3.5 h-3.5" />
                Back to Orders
            </a>
            <div class="font-serif text-[18px] font-black text-on-surface">
                ORDER <em class="text-primary not-italic">#{{ $order->reference }}</em>
            </div>
            <flux:badge :color="$order->status->color()">{{ $order->status->label() }}
            </flux:badge>
        </div>

        <div class="p-5 flex flex-col gap-5">

            {{-- ============================================================ --}}
            {{-- CONVERTED FROM QUOTE NOTICE                                   --}}
            {{-- Shown when this order was converted from an accepted quote.   --}}
            {{-- ============================================================ --}}
            @if ($order->wasConvertedFromQuote() && $order->quote)
                <div class="flex items-center gap-3 p-3 bg-blue-50 border border-blue-200 rounded-sm mb-6">
                    <flux:icon.tag class="size-4 shrink-0 text-blue-500" />
                    <flux:text class="text-sm text-blue-800 flex-1">
                        This order was created from quote
                        <flux:link :href="route('customer.quotations.show', $order->quote)" wire:navigate
                            class="font-medium">
                            {{ $order->quote->reference }}
                        </flux:link>
                    </flux:text>
                </div>
            @endif

            {{-- Order Quick Summary --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pb-6 border-b border-zinc-200">
                <div>
                    <div class="text-[10px] font-bold tracking-widest uppercase text-on-surface-variant mb-1">Placed On</div>
                    <div class="text-[14px] font-semibold text-on-surface">{{ $order->created_at->format('M j, Y') }}
                    </div>
                </div>
            </div>

            {{-- Items List --}}
            <div class="">
                <h3 class="text-base font-bold uppercase tracking-wider text-on-surface mb-4 font-serif">Items in Your
                    Order
                </h3>

                <div class="flex flex-col border border-zinc-200">
                    @foreach ($order->items as $item)
                        @php
                            $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                            $sku = $item->product_snapshot['sku'] ?? null;
                            $brand = $item->product?->brand?->name ?? null;
                            $imagePath = $item->product_image_url ?? $item->product?->image_url;
                            $inStock = ($item->product?->stock_quantity ?? 0) > 0;
                            $variantAttrs = $item->product_snapshot['variant']['attributes'] ?? [];
                        @endphp

                        <div class="flex items-center gap-3.5 p-3.5 border-b border-zinc-200 last:border-b-0">
                            <div class="w-14 h-14 bg-zinc-50 flex items-center justify-center shrink-0 relative">
                                @if ($imagePath)
                                    <img src="{{ asset($imagePath) }}" alt="{{ $name }}"
                                        class="w-[90%] h-[90%] object-contain">
                                @else
                                    <flux:icon.photo class="w-8 h-8 text-zinc-200" />
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-[9px] font-bold tracking-widest uppercase text-on-surface-variant mb-0.5">
                                    {{ $brand }} @if ($sku)
                                        · SKU: {{ $sku }}
                                    @endif
                                </div>
                                <div class="text-[13px] font-semibold text-on-surface mb-0.5 truncate">
                                    {{ $name }}
                                </div>
                                @if (!empty($variantAttrs))
                                    <div class="text-[10px] text-on-surface-variant mb-0.5">
                                        {{ collect($variantAttrs)->map(fn($v, $k) => "$k: $v")->join(' · ') }}
                                    </div>
                                @endif
                                <div class="text-[11px] text-on-surface-variant">Qty: {{ $item->quantity }}</div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <div class="text-[13px] font-bold text-on-surface shrink-0">
                                    {{ format_currency($item->total_cents / 100) }}</div>
                                <button
                                    class="text-[10px] font-bold uppercase text-primary tracking-widest hover:underline cursor-pointer"
                                    wire:click="buyAgain({{ $item->product_id }})"
                                    @if (!$inStock) disabled @endif>
                                    {{ $inStock ? 'Buy Again' : 'Out of Stock' }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-5">
                    {{-- Summary --}}
                    {{-- <div class="border border-zinc-200">
                        <div class="flex justify-between p-3 border-b border-zinc-200 text-[13px]">
                            <span class="text-on-surface-variant">Subtotal</span>
                            <span class="font-bold text-on-surface">{{ format_currency($order->subtotal) }}</span>
                        </div>
                        @if ($order->discount > 0)
                            <div class="flex justify-between p-3 border-b border-zinc-200 text-[13px]">
                                <span class="text-on-surface-variant">Discount</span>
                                <span class="font-bold text-green-600">- {{ format_currency($order->discount) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between p-3 border-b border-zinc-200 text-[13px]">
                            <span class="text-on-surface-variant">Shipping</span>
                            <span
                                class="font-bold text-on-surface">{{ $order->shipping == 0 ? 'FREE' : format_currency($order->shipping) }}</span>
                        </div>
                        <div class="flex justify-between p-3 bg-zinc-50 ">
                            <span class="font-sans text-[15px] font-bold uppercase">Total</span>
                            <span
                                class="font-sans text-[22px] font-bold text-primary">{{ format_currency($order->total) }}</span>
                        </div>
                    </div> --}}

                    {{-- Order Totals --}}
                    <div class="bg-zinc-50 border border-zinc-200 rounded-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-zinc-200 bg-white">
                            <h3 class="text-[13px] font-bold uppercase tracking-widest text-on-surface font-serif">Order
                                Summary</h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex justify-between text-[13px]">
                                <span class="text-on-surface-variant font-medium">Subtotal</span>
                                <span class="text-on-surface font-bold">{{ format_currency($order->subtotal) }}</span>
                            </div>
                            @if ($order->discount > 0)
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-green-600 font-medium">Discount</span>
                                    <span class="text-green-600 font-bold">−
                                        {{ format_currency($order->discount) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-[13px]">
                                <span class="text-on-surface-variant font-medium">Shipping</span>
                                <span class="text-on-surface font-bold">
                                    {{ $order->shipping == 0 ? 'FREE' : format_currency($order->shipping) }}
                                </span>
                            </div>
                            <div class="pt-3 border-t border-zinc-200 flex justify-between items-baseline">
                                <span class="text-[14px] font-bold uppercase tracking-widest text-on-surface">Total</span>
                                <span class="text-[24px] font-black text-primary font-barlow-condensed leading-none">
                                    {{ format_currency($order->total) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <x-customer.card title="Delivery Information" bodyClass="py-3 px-5 text-[13px]">
                        <div class="font-bold text-on-surface mb-1">
                            {{ trim(($order->shipping_address['first_name'] ?? '') . ' ' . ($order->shipping_address['last_name'] ?? '')) ?: $order->shipping_address['full_name'] ?? 'N/A' }}
                        </div>
                        <div class="text-on-surface-variant">
                            {{ format_phone($order->shipping_address['phone_number'] ?? '') }}<br>
                            {{ $order->shipping_address['address'] ?? 'N/A' }}<br>
                            {{ implode(', ', array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null])) }}
                        </div>
                        @if ($order->shipping_snapshot['method_name'] ?? null)
                            <div class="mt-2 pt-2 border-t border-zinc-100">
                                <span class="text-[11px] font-bold uppercase text-on-surface-variant block mb-0.5">Method</span>
                                <span class="font-semibold">{{ $order->shipping_snapshot['method_name'] }}</span>
                                @if ($order->shipping_snapshot['delivery_window'] ?? null)
                                    <span class="text-on-surface-variant block text-[11px]">Est.
                                        {{ $order->shipping_snapshot['delivery_window'] }}</span>
                                @endif
                            </div>
                        @endif
                    </x-customer.card>

                    <div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-zinc-200">
                            <h3 class="text-[12px] font-bold uppercase tracking-widest text-on-surface font-serif">
                                Payment Method</h3>
                        </div>
                        <div class="p-5">
                            @if ($order->payment)
                                <div class="flex items-center justify-between mb-4">
                                    <div class="text-[13px] font-bold text-on-surface uppercase tracking-tight">
                                        {{ $order->payment->gateway ?? '—' }}
                                    </div>
                                    <flux:badge size="sm" :color="$order->payment->status->color()">
                                        {{ $order->payment->status->label() }}
                                    </flux:badge>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-[12px]">
                                        <span class="text-on-surface-variant">Amount Paid</span>
                                        <span
                                            class="font-bold text-on-surface">{{ format_currency(($order->payment->amount_cents ?? 0) / 100) }}</span>
                                    </div>
                                    @if ($order->payment->paid_at)
                                        <div class="flex justify-between text-[12px]">
                                            <span class="text-on-surface-variant">Transaction Date</span>
                                            <span
                                                class="font-bold text-on-surface">{{ $order->payment->paid_at->format('M j, Y') }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Tax Invoice Section --}}
                                @if ($this->isPaid)
                                    <div class="mt-6 pt-5 border-t border-zinc-200">
                                        <h4 class="text-[11px] font-bold uppercase tracking-widest text-on-surface-variant mb-3">
                                            Tax Invoice</h4>

                                        @if ($this->hasKraReceipt)
                                            <flux:button tag="a" :href="route('customer.orders.receipt', $order)"
                                                size="sm" variant="customer-primary" icon="arrow-down-tray"
                                                class="w-full font-bold uppercase tracking-wider text-[10px]">
                                                Download Invoice
                                            </flux:button>
                                            <div class="mt-2 text-center">
                                                <div
                                                    class="text-[10px] text-emerald-600 font-bold uppercase tracking-wide">
                                                    KRA Validated</div>
                                                <div class="text-[9px] text-on-surface-variant mt-0.5">CU No:
                                                    {{ $order->kra_cu_number }}</div>
                                            </div>
                                        @elseif ($this->isAwaitingKraValidation)
                                            <div
                                                class="p-3 bg-purple-50 border border-purple-100 rounded-sm text-[11px] text-purple-700 leading-relaxed font-medium">
                                                Pending KRA validation. This usually takes a few minutes.
                                            </div>
                                        @elseif ($this->hasSapSyncFailed)
                                            <div
                                                class="p-3 bg-red-50 border border-red-100 rounded-sm text-[11px] text-red-700 leading-relaxed font-medium">
                                                Invoice generation issue. Support has been notified.
                                            </div>
                                        @else
                                            <div
                                                class="p-3 bg-zinc-50 border border-zinc-100 rounded-sm text-[11px] text-on-surface-variant leading-relaxed italic">
                                                Your invoice is being prepared...
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <div class="p-4 bg-zinc-50 border border-zinc-200 border-dashed text-center">
                                    <div class="text-[12px] text-on-surface-variant italic">No payment info available</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap gap-2.5">
                <flux:button tag="a" variant="customer-outline"
                    href="{{ route('customer.orders.tracking', $order) }}" wire:navigate size="customer"
                    class="px-5!">
                    <flux:icon.clock class="w-3.5 h-3.5" />
                    Track Order
                </flux:button>

                @if ($this->isPaid && $this->hasKraReceipt)
                    <flux:button tag="a" variant="customer-outline"
                        href="{{ route('customer.orders.receipt', $order) }}" size="customer" class="px-5!">
                        <flux:icon.arrow-down-tray class="w-3.5 h-3.5" />
                        Download Invoice
                    </flux:button>
                @endif

                @if ($order->status->value === OrderStatus::DELIVERED->value)
                    <flux:button size="customer" variant="customer-outline" class="px-5!">
                        <flux:icon.star class="w-3.5 h-3.5" />
                        Leave Review
                    </flux:button>
                @endif
            </div>

            <div class="text-center mt-6">
                <div class="text-[13px] text-on-surface-variant">
                    Need help with this order? <a href="#"
                        class="text-primary font-bold hover:underline">Contact
                        Support</a>
                </div>
            </div>
        </div>
    </div>
</div>

