<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Customer — Admin')] class extends Component {
    #[Locked]
    public User $customer;

    public function mount(User $customer): void
    {
        $this->customer = $customer->load('addresses');
    }

    #[Computed]
    public function orders()
    {
        return $this->customer->orders()->withCount('items')->latest()->get();
    }

    #[Computed]
    public function totalSpentCents(): int
    {
        return (int) $this->customer->orders()->sum('total_cents');
    }
}; ?>

@php
    $kes = fn ($cents) => 'KES&nbsp;'.number_format(intdiv((int) $cents, 100), 0, '.', ',');
@endphp

<div>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.customers.index')" wire:navigate>Customers</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $customer->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mt-2 flex items-center gap-4">
        <flux:avatar :name="$customer->name" :initials="$customer->initials()" size="lg" />
        <div>
            <flux:heading size="xl">{{ $customer->name }}</flux:heading>
            <flux:subheading>{{ $customer->email }} · Joined {{ $customer->created_at->format('d F Y') }}</flux:subheading>
        </div>
    </div>

    <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start">

        {{-- Orders --}}
        <div class="min-w-0 flex-1">
            <flux:card class="p-0 overflow-hidden">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Orders</flux:heading>
                    <flux:text size="sm">Lifetime spend: <span class="font-semibold">{!! $kes($this->totalSpentCents) !!}</span></flux:text>
                </div>
                <flux:table
                    container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                    <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                        <flux:table.column>Order</flux:table.column>
                        <flux:table.column align="end">Items</flux:table.column>
                        <flux:table.column align="end">Total</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column align="end">Placed</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->orders as $order)
                            <flux:table.row :key="$order->id" class="cursor-pointer"
                                wire:click="$navigate('{{ route('admin.orders.show', $order) }}')">
                                <flux:table.cell variant="strong"><span class="font-mono">{{ $order->order_number }}</span></flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $order->items_count }}</flux:table.cell>
                                <flux:table.cell align="end" class="font-medium tabular-nums">{!! $kes($order->total_cents) !!}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" inset="top bottom" :color="$order->status->badgeColor()">
                                        {{ $order->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-500">{{ $order->created_at->format('M j, Y') }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="py-12 text-center text-zinc-400">
                                    This customer hasn't placed any orders yet.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        </div>

        {{-- Addresses --}}
        <aside class="w-full shrink-0 lg:w-80">
            <flux:card>
                <flux:heading size="sm">Addresses</flux:heading>
                @forelse ($customer->addresses as $address)
                    <div class="mt-3 rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span class="font-medium dark:text-white">{{ $address->label ?: $address->fullName() }}</span>
                            @if ($address->is_default)
                                <flux:badge size="sm" inset="top bottom" color="green">Default</flux:badge>
                            @endif
                        </div>
                        <div class="mt-1 text-zinc-500">{{ $address->oneLiner() }}</div>
                        @if ($address->phone)
                            <div class="text-zinc-500">{{ $address->phone }}</div>
                        @endif
                    </div>
                @empty
                    <flux:text class="mt-3" size="sm">No saved addresses.</flux:text>
                @endforelse
            </flux:card>
        </aside>
    </div>
</div>
