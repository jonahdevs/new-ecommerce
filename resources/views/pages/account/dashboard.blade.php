<?php

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use Artesaos\SEOTools\Facades\SEOMeta;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account')] #[Title('My Account — Sheffield')] class extends Component
{
    public function mount(): void
    {
        SEOMeta::setRobots('noindex,follow');
    }

    #[Computed]
    public function openOrdersCount(): int
    {
        return auth()->user()->orders()
            ->whereNotIn('status', [OrderStatus::COMPLETED->value, OrderStatus::CANCELLED->value])
            ->count();
    }

    #[Computed]
    public function pendingQuotesCount(): int
    {
        return auth()->user()->quotes()
            ->where('status', QuoteStatus::AWAITING_APPROVAL->value)
            ->count();
    }

    #[Computed]
    public function recentOrders()
    {
        return auth()->user()->orders()
            ->with('items')
            ->latest()
            ->take(3)
            ->get();
    }
}; ?>

@php
    $firstName = str(auth()->user()->name)->before(' ');
@endphp

<div class="page-fade space-y-8">

    {{-- Header --}}
    <div>
        <flux:heading size="xl">Welcome back, {{ $firstName }}.</flux:heading>
        <flux:text class="mt-1">Here's what's happening across your account.</flux:text>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('account.orders.index') }}" wire:navigate>
            <flux:card class="transition hover:shadow-md">
                <flux:text size="sm" class="font-semibold uppercase tracking-wide">Open orders</flux:text>
                <div class="mt-2 font-serif text-5xl font-normal text-ink">{{ $this->openOrdersCount }}</div>
                <flux:text size="sm" class="mt-1 text-ink-4">View all orders →</flux:text>
            </flux:card>
        </a>
        <a href="{{ route('account.quotes.index') }}" wire:navigate>
            <flux:card class="transition hover:shadow-md">
                <flux:text size="sm" class="font-semibold uppercase tracking-wide">Pending quotes</flux:text>
                <div class="mt-2 font-serif text-5xl font-normal text-ink">{{ $this->pendingQuotesCount }}</div>
                <flux:text size="sm" class="mt-1 text-ink-4">Awaiting your approval →</flux:text>
            </flux:card>
        </a>
    </div>

    {{-- Recent orders --}}
    <div>
        <div class="mb-3 flex items-center justify-between">
            <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Recent orders</flux:heading>
            <a href="{{ route('account.orders.index') }}" wire:navigate
               class="text-[12px] font-semibold text-brand-500 hover:text-brand-600">View all</a>
        </div>

        @if ($this->recentOrders->isEmpty())
            <flux:card class="py-10 text-center">
                <flux:icon.shopping-bag variant="outline" class="mx-auto size-8 text-ink-4" />
                <flux:text class="mt-3">No orders yet. Browse the catalog to get started.</flux:text>
                <flux:button variant="customer-primary" size="customer" :href="route('catalog')" wire:navigate class="mt-4">
                    Shop the catalog
                </flux:button>
            </flux:card>
        @else
            <flux:card class="p-0">
                <flux:table container:class="px-6">
                    <flux:table.columns>
                        <flux:table.column>Order</flux:table.column>
                        <flux:table.column>Date</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column align="end">Total</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->recentOrders as $order)
                            <flux:table.row wire:key="recent-{{ $order->id }}">
                                <flux:table.cell>
                                    <a href="{{ route('account.orders.show', $order) }}" wire:navigate
                                       class="text-[13px] font-semibold text-ink hover:text-brand-500">
                                        {{ $order->order_number }}
                                    </a>
                                    <flux:text size="sm" class="mt-0.5 text-ink-4">
                                        {{ $order->items->count() }} item{{ $order->items->count() === 1 ? '' : 's' }}
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm">{{ $order->created_at->format('d M Y') }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$order->status->badgeColor()" size="sm" inset="top bottom">
                                        {{ $order->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:text size="sm" class="font-semibold tabular-nums">
                                        {!! money($order->total_cents) !!}
                                    </flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @endif
    </div>

    {{-- Quick links --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach ([
            ['icon' => 'map-pin',     'label' => 'Addresses', 'sub' => 'Manage delivery locations', 'route' => 'account.addresses.index'],
            ['icon' => 'heart',       'label' => 'Wishlist',  'sub' => 'Saved items',               'route' => 'wishlist'],
            ['icon' => 'user',        'label' => 'Profile',   'sub' => 'Update your details',       'route' => 'profile.edit'],
        ] as $link)
            <a href="{{ route($link['route']) }}" wire:navigate>
                <flux:card class="transition hover:shadow-md">
                    <flux:icon :icon="$link['icon']" variant="outline" class="size-5 text-ink-3" />
                    <flux:heading size="sm" class="mt-2">{{ $link['label'] }}</flux:heading>
                    <flux:text size="sm" class="mt-0.5 text-ink-4">{{ $link['sub'] }}</flux:text>
                </flux:card>
            </a>
        @endforeach
    </div>

</div>
