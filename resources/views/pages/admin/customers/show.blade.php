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

<div>
    @push('breadcrumbs')
<flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.customers.index')" wire:navigate>Customers</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $customer->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
@endpush

    <div class="mt-2 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <flux:avatar :name="$customer->name" :initials="$customer->initials()" size="lg" />
            <div>
                <flux:heading size="xl">{{ $customer->name }}</flux:heading>
                <flux:subheading>{{ $customer->email }} · Joined {{ $customer->created_at->format('d F Y') }}</flux:subheading>
            </div>
        </div>
        <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('admin.customers.edit', $customer)" wire:navigate>Edit</flux:button>
    </div>

    <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start">

        {{-- Orders --}}
        <div class="min-w-0 flex-1">
            <flux:card class="p-0 overflow-hidden">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Orders</flux:heading>
                    <flux:text size="sm">Lifetime spend: <span class="font-semibold">{!! money($this->totalSpentCents) !!}</span></flux:text>
                </div>
                <flux:table
                    container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                    <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                        <flux:table.column>Order</flux:table.column>
                        <flux:table.column align="end">Items</flux:table.column>
                        <flux:table.column align="end">Total</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column align="end">Placed</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->orders as $order)
                            <flux:table.row :key="$order->id">
                                <flux:table.cell variant="strong"><span class="font-mono">{{ $order->order_number }}</span></flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $order->items_count }}</flux:table.cell>
                                <flux:table.cell align="end" class="font-medium tabular-nums">{!! money($order->total_cents) !!}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" inset="top bottom" :color="$order->status->badgeColor()">
                                        {{ $order->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-500">{{ $order->created_at->format('M j, Y') }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button size="xs" variant="ghost" icon="eye" tooltip="View order" :href="route('admin.orders.show', $order)" wire:navigate />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="py-12 text-center text-zinc-400">
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
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Addresses</flux:heading>
                </div>
                <div class="space-y-3 p-6">
                @forelse ($customer->addresses as $address)
                    <div class="rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-700">
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
                    <flux:text size="sm">No saved addresses.</flux:text>
                @endforelse
                </div>
            </flux:card>
        </aside>
    </div>
</div>
