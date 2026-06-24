<?php

use App\Models\Order;
use App\Support\StorefrontSession;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account')] #[Title('Order')] class extends Component
{
    #[Locked]
    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 403);
        SEOMeta::setTitle('Order '.$order->order_number);
        SEOMeta::setRobots('noindex,follow');
        $this->order = $order->load([
            'items.product.media',
            'address',
            'latestPayment',
            'quote',
            'shippingMethod',
        ]);
    }

    #[Computed]
    public function isPaid(): bool
    {
        return $this->order->latestPayment?->status->value === 'success';
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

    public function buyAgain(string $slug): void
    {
        StorefrontSession::addToCart($slug, 1);
        $this->skipRender();
        $this->dispatch('cart-updated');
        Flux::toast(heading: 'Added to cart', text: 'Item has been added to your cart.', variant: 'success');
    }
}; ?>

<div class="page-fade space-y-5">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('account.orders.index')" wire:navigate>Orders</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $order->order_number }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    {{-- Quote-origin callout --}}
    @if ($order->quote)
        <flux:callout icon="tag" color="blue">
            <flux:callout.heading>Created from a quotation</flux:callout.heading>
            <flux:callout.text>
                This order was converted from quote
                <flux:callout.link :href="route('account.quotes.show', $order->quote)" wire:navigate>
                    {{ $order->quote->quote_number }}
                </flux:callout.link>.
            </flux:callout.text>
        </flux:callout>
    @endif

    {{-- Main container --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">

        {{-- ── Header bar ── --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-white px-5 py-4">
            <h1 class="font-serif text-lg font-black tracking-tight text-ink">
                ORDER <em class="not-italic text-brand-500">{{ $order->order_number }}</em>
            </h1>
            <flux:badge :color="$order->status->badgeColor()" size="sm">{{ $order->status->label() }}</flux:badge>
        </div>

        <div class="flex flex-col gap-6 p-5">

            {{-- ── Quick summary ── --}}
            <div class="space-y-1.5 border-b border-zinc-100 pb-6">
                <p class="text-[13px] text-ink-2">
                    <span class="font-bold text-ink">{{ $order->items->sum('quantity') }}</span>
                    {{ Str::plural('item', $order->items->sum('quantity')) }}
                </p>
                <p class="text-[13px] text-ink-2">
                    Placed on <span class="font-bold text-ink">{{ $order->created_at->format('M j, Y') }}</span>
                </p>
                <p class="text-[13px] text-ink-2">
                    Total: <span class="font-bold text-ink">{!! money($order->total_cents) !!}</span>
                </p>
            </div>

            {{-- ── Items ── --}}
            <div>
                <h2 class="mb-4 font-serif text-base font-black uppercase tracking-wider text-ink">
                    Items in your order
                </h2>

                <div class="flex flex-col divide-y divide-zinc-100 rounded border border-zinc-200">
                    @foreach ($order->items as $item)
                        <div class="flex items-center gap-3.5 p-3.5" wire:key="item-{{ $item->id }}">

                            {{-- Thumbnail --}}
                            @if ($item->product?->cover_url)
                                <img
                                    src="{{ $item->product->cover_url }}"
                                    alt="{{ $item->product_name }}"
                                    class="size-14 shrink-0 rounded object-contain"
                                />
                            @else
                                <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded border border-zinc-100 bg-zinc-50">
                                    <flux:icon.photo variant="outline" class="size-7 text-zinc-200" />
                                </div>
                            @endif

                            {{-- Details --}}
                            <div class="min-w-0 flex-1">
                                <p class="mb-0.5 text-[9px] font-bold uppercase tracking-widest text-ink-3">
                                    @if ($item->product_sku)SKU: {{ $item->product_sku }}@endif
                                    @if ($item->product_model_number) · {{ $item->product_model_number }}@endif
                                </p>
                                @if ($item->product)
                                    <a href="{{ route('product.show', $item->product) }}" wire:navigate
                                        class="truncate text-[13px] font-semibold text-ink transition-colors hover:text-brand-500">
                                        {{ $item->product_name }}
                                    </a>
                                @else
                                    <p class="truncate text-[13px] font-semibold text-ink">{{ $item->product_name }}</p>
                                @endif
                                <p class="mt-0.5 text-[11px] text-ink-3">Qty: {{ $item->quantity }}</p>
                                @if ($order->hasMixedTaxRates() && (float) $item->tax_rate > 0)
                                    <p class="text-[10px] text-ink-4">VAT {{ rtrim(rtrim(number_format((float) $item->tax_rate, 2), '0'), '.') }}%</p>
                                @elseif ($order->hasMixedTaxRates())
                                    <p class="text-[10px] text-ink-4">VAT exempt</p>
                                @endif
                            </div>

                            {{-- Price + Buy Again --}}
                            <div class="flex shrink-0 flex-col items-end gap-2">
                                <div class="text-right">
                                    <p class="text-sm font-bold text-ink">{!! money($item->line_total_cents) !!}</p>
                                    @if ($item->quantity > 1)
                                        <p class="text-[11px] text-ink-4">{!! money($item->unit_price_cents) !!} each</p>
                                    @endif
                                </div>
                                @if ($item->product)
                                    @php $inStock = ($item->product->stock_quantity ?? 0) > 0; @endphp
                                    <button
                                        wire:click="buyAgain('{{ $item->product->slug }}')"
                                        @disabled(! $inStock)
                                        class="text-[10px] font-bold uppercase tracking-widest transition-colors {{ $inStock ? 'cursor-pointer text-brand-500 hover:underline' : 'cursor-not-allowed text-ink-4' }}"
                                    >
                                        {{ $inStock ? 'Buy Again' : 'Out of Stock' }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Summary + Delivery + Payment 2-col grid --}}
                <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">

                    {{-- Order totals --}}
                    <div class="overflow-hidden rounded border border-zinc-200 bg-zinc-50">
                        <div class="border-b border-zinc-200 bg-white px-5 py-3">
                            <flux:heading size="sm" class="uppercase tracking-wide">Order Summary</flux:heading>
                        </div>
                        <div class="space-y-2.5 p-5">
                            <div class="flex justify-between text-[13px]">
                                <span class="font-medium text-ink-3">Subtotal</span>
                                <span class="font-bold tabular-nums text-ink">{!! money($order->subtotal_cents) !!}</span>
                            </div>
                            @if ($order->discount_cents > 0)
                                <div class="flex justify-between text-[13px]">
                                    <span class="font-medium text-ink-3">
                                        Discount
                                        @if ($order->coupon_code)
                                            <span class="ml-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-widest text-emerald-700">{{ $order->coupon_code }}</span>
                                        @endif
                                    </span>
                                    <span class="font-bold tabular-nums text-emerald-600">− {!! money($order->discount_cents) !!}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-[13px]">
                                <span class="font-medium text-ink-3">Delivery</span>
                                @if ($order->delivery_cents > 0)
                                    <span class="font-bold tabular-nums text-ink">{!! money($order->delivery_cents) !!}</span>
                                @else
                                    <span class="font-bold text-emerald-600">Free</span>
                                @endif
                            </div>
                            @if ($order->installation_cents > 0)
                                <div class="flex justify-between text-[13px]">
                                    <span class="font-medium text-ink-3">Installation</span>
                                    <span class="font-bold tabular-nums text-ink">{!! money($order->installation_cents) !!}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-[13px]">
                                <span class="font-medium text-ink-3">{!! $order->vatLabel() !!}</span>
                                <span class="font-bold tabular-nums text-ink">{!! money($order->vat_cents) !!}</span>
                            </div>
                            <div class="flex items-baseline justify-between border-t border-zinc-200 pt-3">
                                <span class="text-sm font-bold uppercase tracking-widest text-ink">Total</span>
                                <span class="font-serif text-2xl font-black leading-none text-brand-500 tabular-nums">
                                    {!! money($order->total_cents) !!}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Delivery info --}}
                    @if ($order->address)
                        <div class="overflow-hidden rounded border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-5 py-3">
                                <flux:heading size="sm" class="uppercase tracking-wide">Delivery Information</flux:heading>
                            </div>
                            <div class="p-5 text-[13px] leading-relaxed text-ink-2">
                                <p class="font-bold text-ink">{{ $order->address->fullName() }}</p>
                                <p>{{ $order->address->line1 }}{{ $order->address->line2 ? ', '.$order->address->line2 : '' }}</p>
                                <p>{{ $order->address->city }}{{ $order->address->postal_code ? ', '.$order->address->postal_code : '' }}</p>
                                @if ($order->address->phone)
                                    <p class="mt-1 text-xs text-ink-4">{{ $order->address->phone }}</p>
                                @endif
                                @if ($order->shippingMethod)
                                    <div class="mt-3 border-t border-zinc-100 pt-3">
                                        <p class="mb-0.5 text-[11px] font-bold uppercase tracking-widest text-ink-3">Shipping method</p>
                                        <p class="font-semibold text-ink">{{ $order->shippingMethod->name }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Payment method --}}
                    <div class="overflow-hidden rounded border border-zinc-200 bg-white">
                        <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3">
                            <flux:heading size="sm" class="uppercase tracking-wide">Payment Method</flux:heading>
                            @if ($order->latestPayment)
                                <flux:badge :color="$order->latestPayment->status->badgeColor()" size="sm">
                                    {{ $order->latestPayment->status->label() }}
                                </flux:badge>
                            @endif
                        </div>
                        <div class="p-5">
                            @if ($order->latestPayment)
                                @php $payment = $order->latestPayment; @endphp
                                <div class="space-y-2 text-[13px]">
                                    <div class="flex justify-between">
                                        <span class="font-medium text-ink-3">Method</span>
                                        <span class="font-bold uppercase tracking-tight text-ink">
                                            {{ $payment->methodLabel() }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="font-medium text-ink-3">Amount paid</span>
                                        <span class="font-bold tabular-nums text-ink">{!! money($payment->amount_cents) !!}</span>
                                    </div>
                                    @if ($payment->paid_at)
                                        <div class="flex justify-between">
                                            <span class="font-medium text-ink-3">Transaction date</span>
                                            <span class="font-bold text-ink">{{ $payment->paid_at->format('M j, Y') }}</span>
                                        </div>
                                    @endif
                                    @if ($payment->card_last4)
                                        <div class="flex justify-between">
                                            <span class="font-medium text-ink-3">Card</span>
                                            <span class="font-mono text-sm text-ink">
                                                {{ strtoupper($payment->card_brand ?? '') }} ···· {{ $payment->card_last4 }}
                                            </span>
                                        </div>
                                    @endif
                                    @if ($payment->mpesa_receipt)
                                        <div class="flex justify-between">
                                            <span class="font-medium text-ink-3">M-Pesa receipt</span>
                                            <span class="font-mono text-sm text-ink">{{ $payment->mpesa_receipt }}</span>
                                        </div>
                                    @endif
                                </div>

                            @else
                                <div class="rounded border border-dashed border-zinc-200 bg-zinc-50 py-4 text-center">
                                    <p class="text-sm italic text-ink-4">No payment info available</p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            {{-- ── Order notes ── --}}
            @if ($order->notes)
                <div class="rounded border border-zinc-200 bg-zinc-50 px-5 py-4">
                    <p class="mb-1 text-[11px] font-bold uppercase tracking-widest text-ink-3">Order notes</p>
                    <p class="text-sm leading-relaxed text-ink-2">{{ $order->notes }}</p>
                </div>
            @endif

            {{-- ── Actions ── --}}
            <div class="flex flex-wrap gap-2">
                <flux:button
                    size="customer"
                    variant="customer-outline"
                    icon="truck"
                    :href="route('account.orders.tracking', $order)"
                    wire:navigate
                >
                    Track order
                </flux:button>
                @if ($order->status->value === 'completed')
                    @foreach ($order->items->filter(fn ($i) => $i->product) as $item)
                        <flux:button
                            size="sm"
                            variant="outline"
                            icon="star"
                            :href="route('account.reviews.form', $item->product)"
                            wire:navigate
                        >
                            Review {{ $item->product_name }}
                        </flux:button>
                    @endforeach
                @endif
                @if ($this->hasKraReceipt)
                    <flux:button
                        size="sm"
                        variant="outline"
                        icon="arrow-down-tray"
                        tag="a"
                        :href="route('account.orders.receipt', $order)"
                    >
                        Download Invoice
                    </flux:button>
                @endif
            </div>

            {{-- ── Contact support ── --}}
            <div class="border-t border-zinc-100 pt-2 text-center">
                <p class="text-[13px] text-ink-3">
                    Need help with this order?
                    <a
                        class="font-bold text-brand-500 transition-colors hover:underline"
                        href="mailto:orders@sheffieldsteelsystems.com?subject=Order%20{{ urlencode($order->order_number) }}%20enquiry"
                    >
                        Contact support
                    </a>
                </p>
            </div>

        </div>
    </div>

</div>
