<?php

use App\Models\Order;
use Artesaos\SEOTools\Facades\SEOMeta;
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
        SEOMeta::setRobots('noindex,follow');
        $this->order = $order->load('items.product', 'address');
    }
}; ?>

<div class="page-fade">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('account.orders.index')" wire:navigate>Orders</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $order->order_number }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="font-mono text-2xl font-semibold tracking-tight text-ink">{{ $order->order_number }}</h1>
                <flux:badge :color="$order->status->badgeColor()" size="sm">{{ $order->status->label() }}</flux:badge>
            </div>
            <p class="mt-1 text-sm text-ink-3">Placed {{ $order->created_at->format('d F Y') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Left column --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Items --}}
            <flux:card class="overflow-hidden p-0">
                <div class="border-b border-zinc-100 px-6 py-4">
                    <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Items</flux:heading>
                </div>
                <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                    <flux:table.columns class="bg-zinc-50">
                        <flux:table.column>Product</flux:table.column>
                        <flux:table.column class="w-32">SKU</flux:table.column>
                        <flux:table.column class="w-36" align="end">Unit price</flux:table.column>
                        <flux:table.column class="w-16" align="end">Qty</flux:table.column>
                        <flux:table.column class="w-36" align="end">Total</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($order->items as $item)
                            <flux:table.row wire:key="item-{{ $item->id }}">
                                <flux:table.cell>
                                    @if ($item->product)
                                        <a href="{{ route('product.show', $item->product) }}" wire:navigate
                                           class="text-[13.5px] font-semibold leading-snug text-ink hover:text-brand-500">
                                            {{ $item->product_name }}
                                        </a>
                                    @else
                                        <span class="text-[13.5px] font-semibold leading-snug text-ink">{{ $item->product_name }}</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="font-mono text-xs text-ink-4">{{ $item->product_sku ?: '—' }}</span>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <span class="tabular-nums text-sm text-ink-2">{!! money($item->unit_price_cents) !!}</span>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <span class="tabular-nums text-sm text-ink-3">{{ $item->quantity }}</span>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <span class="font-semibold tabular-nums text-ink">{!! money($item->line_total_cents) !!}</span>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>

        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6">

            {{-- Summary --}}
            <flux:card class="overflow-hidden p-0">
                <div class="border-b border-zinc-100 px-6 py-4">
                    <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Summary</flux:heading>
                </div>
                <div class="space-y-3 px-5 py-4">
                    <div class="flex justify-between">
                        <flux:text size="sm">Subtotal</flux:text>
                        <flux:text size="sm" class="font-medium tabular-nums">{!! money($order->subtotal_cents) !!}</flux:text>
                    </div>
                    <div class="flex justify-between">
                        <flux:text size="sm">Delivery</flux:text>
                        @if ($order->delivery_cents > 0)
                            <flux:text size="sm" class="font-medium tabular-nums">{!! money($order->delivery_cents) !!}</flux:text>
                        @else
                            <flux:text size="sm" class="font-medium text-emerald-600">Free</flux:text>
                        @endif
                    </div>
                    @if ($order->installation_cents > 0)
                        <div class="flex justify-between">
                            <flux:text size="sm">Installation</flux:text>
                            <flux:text size="sm" class="font-medium tabular-nums">{!! money($order->installation_cents) !!}</flux:text>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <flux:text size="sm">{{ $order->vatLabel() }}</flux:text>
                        <flux:text size="sm" class="font-medium tabular-nums">{!! money($order->vat_cents) !!}</flux:text>
                    </div>
                </div>
                <flux:separator />
                <div class="flex items-baseline justify-between px-5 py-4">
                    <flux:text class="text-[12px] font-bold uppercase tracking-wide">Total</flux:text>
                    <span class="font-serif text-2xl text-brand-500 tabular-nums">{!! money($order->total_cents) !!}</span>
                </div>
                @if ($order->payment_method)
                    <div class="border-t border-zinc-100 px-5 py-3">
                        <flux:text size="sm" class="text-ink-3">
                            Paid via <span class="font-semibold capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</span>
                        </flux:text>
                    </div>
                @endif
            </flux:card>

            {{-- Delivery address --}}
            @if ($order->address)
                <flux:card class="overflow-hidden p-0">
                    <div class="border-b border-zinc-100 px-6 py-4">
                        <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Delivery address</flux:heading>
                    </div>
                    <div class="space-y-0.5 px-5 py-4 text-[13.5px] leading-relaxed text-ink-2">
                        <div class="font-semibold">{{ $order->address->fullName() }}</div>
                        <div>{{ $order->address->line1 }}{{ $order->address->line2 ? ', '.$order->address->line2 : '' }}</div>
                        <div>{{ $order->address->city }}{{ $order->address->postal_code ? ', '.$order->address->postal_code : '' }}</div>
                        @if ($order->address->phone)
                            <flux:text size="sm" class="mt-1 text-ink-3">{{ $order->address->phone }}</flux:text>
                        @endif
                    </div>
                </flux:card>
            @endif

        </aside>

    </div>

</div>
