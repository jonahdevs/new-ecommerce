<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Orders — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function orders()
    {
        return Order::query()
            ->with(['user', 'latestPayment'])
            ->withCount('items')
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('order_number', 'like', $term)
                        ->orWhereHas('user', function ($u) use ($term) {
                            $u->where('name', 'like', $term)->orWhere('email', 'like', $term);
                        });
                });
            })
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate($this->perPage);
    }

    /** @return array<string, int> */
    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Order::count(),
            'pending' => Order::where('status', OrderStatus::PENDING)->count(),
            'processing' => Order::where('status', OrderStatus::PROCESSING)->count(),
        ];
    }

    /** @return array<int, OrderStatus> */
    public function statuses(): array
    {
        return OrderStatus::cases();
    }
}; ?>

@php
    $kes = fn ($cents) => 'KES&nbsp;'.number_format(intdiv($cents, 100), 0, '.', ',');
@endphp

<div>
    <div class="flex items-center justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Orders</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl" class="mt-2">Orders</flux:heading>
            <flux:subheading>Track and fulfil customer orders.</flux:subheading>
        </div>
    </div>

    {{-- Stat tiles --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card class="flex items-center gap-4">
            <flux:icon.shopping-cart class="size-9 text-zinc-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['total'] }}</div>
                <flux:text size="sm">Total orders</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.clock class="size-9 text-amber-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['pending'] }}</div>
                <flux:text size="sm">Pending</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.arrow-path class="size-9 text-blue-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['processing'] }}</div>
                <flux:text size="sm">Processing</flux:text>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search order # or customer…"
                icon="magnifying-glass"
                clearable
                class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterStatus" class="w-44">
                    <flux:select.option value="">All statuses</flux:select.option>
                    @foreach ($this->statuses() as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-28">
                    <flux:select.option value="15">15 / page</flux:select.option>
                    <flux:select.option value="25">25 / page</flux:select.option>
                    <flux:select.option value="50">50 / page</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Order</flux:table.column>
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column>Items</flux:table.column>
                <flux:table.column>Total</flux:table.column>
                <flux:table.column>Payment</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Placed</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->orders as $order)
                    <flux:table.row :key="$order->id" class="cursor-pointer"
                        wire:click="$navigate('{{ route('admin.orders.show', $order) }}')">
                        <flux:table.cell variant="strong">
                            <span class="font-mono">{{ $order->order_number }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($order->user)
                                <div class="font-medium text-sm dark:text-white">{{ $order->user->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $order->user->email }}</div>
                            @else
                                <span class="text-zinc-400">Guest</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums text-zinc-500">{{ $order->items_count }}</flux:table.cell>
                        <flux:table.cell class="font-medium tabular-nums">{!! $kes($order->total_cents) !!}</flux:table.cell>
                        <flux:table.cell>
                            @if ($order->latestPayment)
                                <flux:badge size="sm" inset="top bottom" :color="$order->latestPayment->status->badgeColor()">
                                    {{ $order->latestPayment->status->label() }}
                                </flux:badge>
                            @else
                                <flux:badge size="sm" inset="top bottom" color="zinc">Unpaid</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" inset="top bottom" :color="$order->status->badgeColor()">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="text-sm text-zinc-500">
                            {{ $order->created_at->format('M j, Y') }}
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-12 text-center text-zinc-400">
                            No orders found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->orders->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->orders" />
            </div>
        @endif
    </flux:card>
</div>
