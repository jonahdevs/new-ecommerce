<?php

use App\Models\Order;
use Artesaos\SEOTools\Facades\SEOMeta;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account')] #[Title('Order — Sheffield')] class extends Component
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

@php
    $kes = fn ($cents) => 'KES&nbsp;' . number_format(intdiv($cents, 100), 0, '.', ',');
@endphp

<div class="page-fade space-y-6">

    {{-- Back --}}
    <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('account.orders.index')" wire:navigate inset="left">
        Back to orders
    </flux:button>

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $order->order_number }}</flux:heading>
            <flux:text class="mt-1">Placed {{ $order->created_at->format('d F Y') }}</flux:text>
        </div>
        <flux:badge :color="$order->status->badgeColor()">
            {{ $order->status->label() }}
        </flux:badge>
    </div>

    <div class="flex flex-col gap-6 lg:flex-row lg:items-start">

        {{-- Items + address --}}
        <div class="min-w-0 flex-1 space-y-4">

            {{-- Items --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Items ordered</flux:heading>
                </div>
                <flux:table container:class="px-5">
                    <flux:table.rows>
                        @foreach ($order->items as $item)
                            <flux:table.row wire:key="item-{{ $item->id }}">
                                <flux:table.cell class="w-14">
                                    <div class="size-14 overflow-hidden rounded border border-zinc-100 bg-surface-sunken p-1.5">
                                        @if ($item->product?->cover_url)
                                            <img src="{{ $item->product->cover_url }}" alt="" class="size-full object-contain" loading="lazy" />
                                        @else
                                            <flux:icon.shopping-bag variant="outline" class="size-full p-1 text-ink-4" />
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($item->product)
                                        <a href="{{ route('product.show', $item->product) }}" wire:navigate
                                           class="text-[13.5px] font-semibold leading-snug text-ink hover:text-brand-500">
                                            {{ $item->product_name }}
                                        </a>
                                    @else
                                        <flux:text class="font-semibold">{{ $item->product_name }}</flux:text>
                                    @endif
                                    @if ($item->product_sku)
                                        <flux:text size="sm" class="mt-0.5 text-ink-4">SKU: {{ $item->product_sku }}</flux:text>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:text class="font-semibold tabular-nums">{!! $kes($item->line_total_cents) !!}</flux:text>
                                    <flux:text size="sm" class="mt-0.5 text-ink-4">Qty {{ $item->quantity }}</flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>

            {{-- Delivery address --}}
            @if ($order->address)
                <flux:card>
                    <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Delivery address</flux:heading>
                    <div class="mt-3 space-y-0.5 text-[13.5px] leading-relaxed text-ink-2">
                        <div class="font-semibold">{{ $order->address->fullName() }}</div>
                        <div>{{ $order->address->line1 }}{{ $order->address->line2 ? ', ' . $order->address->line2 : '' }}</div>
                        <div>{{ $order->address->city }}{{ $order->address->postal_code ? ', ' . $order->address->postal_code : '' }}</div>
                        @if ($order->address->phone)
                            <flux:text size="sm" class="mt-1 text-ink-3">{{ $order->address->phone }}</flux:text>
                        @endif
                    </div>
                </flux:card>
            @endif
        </div>

        {{-- Summary sidebar --}}
        <aside class="w-full shrink-0 lg:sticky lg:top-44 lg:w-80">
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Order summary</flux:heading>
                </div>
                <div class="space-y-3 px-5 py-4">
                    <div class="flex justify-between text-sm text-ink-2">
                        <flux:text size="sm">Subtotal</flux:text>
                        <flux:text size="sm" class="font-medium tabular-nums">{!! $kes($order->subtotal_cents) !!}</flux:text>
                    </div>
                    <div class="flex justify-between">
                        <flux:text size="sm">Delivery</flux:text>
                        @if ($order->delivery_cents > 0)
                            <flux:text size="sm" class="font-medium tabular-nums">{!! $kes($order->delivery_cents) !!}</flux:text>
                        @else
                            <flux:text size="sm" class="font-medium text-emerald-600">Free</flux:text>
                        @endif
                    </div>
                    @if ($order->installation_cents > 0)
                        <div class="flex justify-between">
                            <flux:text size="sm">Installation</flux:text>
                            <flux:text size="sm" class="font-medium tabular-nums">{!! $kes($order->installation_cents) !!}</flux:text>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <flux:text size="sm">VAT (16%)</flux:text>
                        <flux:text size="sm" class="font-medium tabular-nums">{!! $kes($order->vat_cents) !!}</flux:text>
                    </div>
                </div>
                <flux:separator />
                <div class="flex items-baseline justify-between px-5 py-4">
                    <flux:text class="text-[12px] font-bold uppercase tracking-wide">Total</flux:text>
                    <span class="font-serif text-2xl text-brand-500 tabular-nums">{!! $kes($order->total_cents) !!}</span>
                </div>
                @if ($order->payment_method)
                    <flux:separator />
                    <div class="px-5 py-3">
                        <flux:text size="sm" class="text-ink-3">
                            Paid via <span class="font-semibold capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</span>
                        </flux:text>
                    </div>
                @endif
            </flux:card>
        </aside>

    </div>

</div>
