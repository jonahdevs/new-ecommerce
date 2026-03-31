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
                'items.product',
                'payment',
                'quote', // loaded to show "converted from quote" notice when quote system is built
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
    <flux:card class="rounded-md p-0">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-3 py-2 border-b">
            <flux:button size="xs" icon="arrow-long-left" variant="ghost" class="cursor-pointer"
                :href="route('customer.orders.index')" wire:navigate />
            <flux:heading size="lg">Order Details</flux:heading>
        </div>

        <section class="p-5">

            {{-- ============================================================ --}}
            {{-- CONVERTED FROM QUOTE NOTICE                                   --}}
            {{-- Shown when this order was converted from an accepted quote.   --}}
            {{-- $order->quote relationship will be populated once the         --}}
            {{-- Quote system is built. Hidden until then.                     --}}
            {{-- ============================================================ --}}
            @if ($order->wasConvertedFromQuote() && $order->quote)
                <div class="flex items-center gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg mb-5">
                    <flux:icon.tag class="size-4 shrink-0 text-blue-500" />
                    <flux:text class="text-sm text-blue-800 flex-1">
                        This order was created from quote
                        <flux:link :href="route('customer.quotes.show', $order->quote)" wire:navigate
                            class="font-medium">
                            {{ $order->quote->reference }}
                        </flux:link>
                    </flux:text>
                </div>
            @endif

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
                        $inStock = ($item->product?->stock_quantity ?? 0) > 0;
                    @endphp

                    <div class="border rounded-md p-4">

                        <div class="mb-3">
                            <flux:badge size="sm" :color="$order->status->color()">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4">

                            {{-- Image --}}
                            <div class="shrink-0">
                                @if ($imagePath)
                                    <a href="{{ route('products.show', $item->product) }}" wire:navigate>
                                        <img src="{{ asset($imagePath) }}" alt="{{ $name }}"
                                            class="w-full sm:w-20 sm:h-20 h-48 object-contain rounded" />
                                    </a>
                                @else
                                    <div
                                        class="w-full sm:w-20 sm:h-20 h-48 bg-zinc-100 rounded flex items-center justify-center">
                                        <flux:icon.photo class="w-8 h-8 text-zinc-300" />
                                    </div>
                                @endif
                            </div>

                            {{-- Details + Actions --}}
                            <div class="flex flex-1 gap-4 justify-between">
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

                                <div class="shrink-0 flex flex-col items-end gap-2">
                                    <flux:button size="sm" variant="primary" icon="shopping-cart"
                                        class="cursor-pointer" wire:click="buyAgain({{ $item->product_id }})"
                                        :disabled="!$inStock">
                                        {{ $inStock ? 'Buy Again' : 'Out of Stock' }}
                                    </flux:button>

                                    <flux:link href="{{ route('customer.orders.tracking', $order) }}" wire:navigate
                                        class="text-xs!">
                                        See Status History
                                    </flux:link>
                                </div>
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
                        @if ($order->shipping == 0)
                            <span class="text-green-600">Free</span>
                        @else
                            {{ format_currency($order->shipping) }}
                        @endif
                    </span>
                </div>

                <div class="flex justify-between font-semibold border-t pt-2">
                    <span>Total</span>
                    <span>{{ format_currency($order->total) }}</span>
                </div>
            </div>

            <flux:separator class="my-8" />

            {{-- ── Payment & Delivery ── --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-0 md:divide-x">

                {{-- Payment Information --}}
                <div class="px-4">
                    <flux:heading class="text-lg mb-4">Payment Information</flux:heading>

                    @if ($order->payment)
                        <div class="space-y-3">

                            <div class="flex items-center justify-between">
                                <flux:text class="text-sm text-zinc-500">Status</flux:text>
                                <flux:badge size="sm" :color="$order->payment->status->color()">
                                    {{ $order->payment->status->label() }}
                                </flux:badge>
                            </div>

                            <div class="flex items-center justify-between">
                                <flux:text class="text-sm text-zinc-500">Method</flux:text>
                                <flux:text class="text-sm font-medium uppercase">
                                    {{ $order->payment->gateway ?? '—' }}
                                </flux:text>
                            </div>

                            <div class="flex items-center justify-between">
                                <flux:text class="text-sm text-zinc-500">Amount</flux:text>
                                <flux:text class="text-sm font-medium">
                                    {{ format_currency(($order->payment->amount_cents ?? 0) / 100) }}
                                    {{ $order->currency }}
                                </flux:text>
                            </div>

                            @if ($order->payment->paid_at)
                                <div class="flex items-center justify-between">
                                    <flux:text class="text-sm text-zinc-500">Paid on</flux:text>
                                    <flux:text class="text-sm font-medium">
                                        {{ $order->payment->paid_at->format('M j, Y') }}
                                        <span class="text-zinc-400 font-normal">
                                            {{ $order->payment->paid_at->format('g:i A') }}
                                        </span>
                                    </flux:text>
                                </div>
                            @endif

                            @if ($order->payment->card_brand && $order->payment->card_last4)
                                <div class="flex items-center justify-between">
                                    <flux:text class="text-sm text-zinc-500">Card</flux:text>
                                    <flux:text class="text-sm font-medium">
                                        {{ ucfirst($order->payment->card_brand) }}
                                        ending {{ $order->payment->card_last4 }}
                                    </flux:text>
                                </div>
                            @endif

                        </div>

                        @if (in_array($order->payment->status->value, [PaymentStatus::PENDING->value, PaymentStatus::PROCESSING->value]))
                            <div class="mt-3 flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                <flux:icon.clock class="size-4 shrink-0 mt-0.5 text-amber-500" />
                                <flux:text class="text-xs text-amber-700">
                                    Payment is being processed. Your order will be confirmed shortly.
                                </flux:text>
                            </div>
                        @endif

                        @if ($order->payment->status->value === PaymentStatus::FAILED->value)
                            <div class="mt-3 flex items-start gap-2 p-3 bg-rose-50 border border-rose-200 rounded-lg">
                                <flux:icon.x-circle class="size-4 shrink-0 mt-0.5 text-rose-500" />
                                <flux:text class="text-xs text-rose-700">
                                    Payment was not completed. Please contact support if you believe this is an error.
                                </flux:text>
                            </div>
                        @endif
                    @else
                        <div class="flex items-start gap-3 p-3 bg-zinc-50 border border-zinc-200 rounded-lg">
                            <flux:icon.information-circle class="size-5 shrink-0 mt-0.5 text-zinc-400" />
                            <flux:text class="text-sm text-zinc-500">
                                No payment information available yet.
                            </flux:text>
                        </div>
                    @endif

                    {{-- ============================================================ --}}
                    {{-- INVOICE SECTION                                              --}}
                    {{-- Shows different states depending on SAP sync progress.       --}}
                    {{--                                                              --}}
                    {{-- State 1 — cu_received: invoice ready, show download button  --}}
                    {{-- State 2 — cu_pending:  SAP done, waiting for KRA webhook    --}}
                    {{-- State 3 — failed:      SAP sync failed, show support notice --}}
                    {{-- State 4 — pending/syncing: SAP not yet started              --}}
                    {{-- ============================================================ --}}
                    @if ($this->isPaid)
                        <div class="mt-5">
                            <flux:separator class="mb-4" />
                            <flux:heading class="text-sm mb-3">Tax Invoice</flux:heading>

                            {{-- State 1: Invoice ready --}}
                            @if ($this->hasKraReceipt)
                                <div
                                    class="flex items-start gap-3 p-3 bg-emerald-50 border border-emerald-200 rounded-lg mb-3">
                                    <flux:icon.check-circle class="size-4 shrink-0 mt-0.5 text-emerald-600" />
                                    <div>
                                        <flux:text class="text-xs font-medium text-emerald-800">
                                            KRA validated
                                        </flux:text>
                                        <flux:text class="text-xs text-emerald-700 mt-0.5">
                                            CU No: {{ $order->kra_cu_number }}
                                        </flux:text>
                                        @if ($order->kra_validated_at)
                                            <flux:text class="text-xs text-emerald-600 mt-0.5">
                                                {{ $order->kra_validated_at->format('M j, Y g:i A') }}
                                            </flux:text>
                                        @endif
                                    </div>
                                </div>

                                <flux:button size="sm" icon="arrow-down-tray" class="cursor-pointer w-full"
                                    tag="a" href="{{ route('customer.orders.receipt', $order) }}">
                                    Download Invoice
                                </flux:button>

                                {{-- State 2: Waiting for CU number --}}
                            @elseif ($this->isAwaitingKraValidation)
                                <div
                                    class="flex items-start gap-2 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                                    <flux:icon.clock class="size-4 shrink-0 mt-0.5 text-purple-500" />
                                    <flux:text class="text-xs text-purple-700">
                                        Invoice pending KRA validation. This usually completes within a few minutes.
                                        We'll email you once it's ready.
                                    </flux:text>
                                </div>

                                {{-- State 3: SAP sync failed --}}
                            @elseif ($this->hasSapSyncFailed)
                                <div class="flex items-start gap-2 p-3 bg-rose-50 border border-rose-200 rounded-lg">
                                    <flux:icon.exclamation-triangle class="size-4 shrink-0 mt-0.5 text-rose-500" />
                                    <flux:text class="text-xs text-rose-700">
                                        Invoice generation encountered an issue. Our team has been notified.
                                        Please contact support if this persists.
                                    </flux:text>
                                </div>

                                {{-- State 4: SAP sync pending or in progress --}}
                            @else
                                <div class="flex items-start gap-2 p-3 bg-zinc-50 border border-zinc-200 rounded-lg">
                                    <flux:icon.clock class="size-4 shrink-0 mt-0.5 text-zinc-400" />
                                    <flux:text class="text-xs text-zinc-500">
                                        Your invoice is being prepared. Check back shortly.
                                    </flux:text>
                                </div>
                            @endif
                        </div>
                    @endif

                </div>

                {{-- Delivery Information --}}
                <div class="px-4">
                    <flux:heading class="text-lg mb-4">Delivery Information</flux:heading>

                    <div class="space-y-1">
                        <flux:text class="font-medium">
                            {{ trim(($order->shipping_address['first_name'] ?? '') . ' ' . ($order->shipping_address['last_name'] ?? '')) ?: $order->shipping_address['full_name'] ?? 'N/A' }}
                        </flux:text>
                        <flux:text class="text-sm text-zinc-500">
                            {{ format_phone($order->shipping_address['phone_number'] ?? '') }}
                        </flux:text>
                        <flux:text class="text-sm text-zinc-500">
                            {{ $order->shipping_address['address'] ?? 'N/A' }}
                        </flux:text>
                        <flux:text class="text-sm text-zinc-500">
                            {{ implode(
                                ', ',
                                array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null]),
                            ) }}
                        </flux:text>
                    </div>

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
            </div>

        </section>
    </flux:card>
</div>
