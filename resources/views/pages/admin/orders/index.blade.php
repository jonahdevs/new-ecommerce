<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Orders — Admin')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterDate = '';

    #[Url]
    public int $perPage = 25;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDate(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filterStatus = '';
        $this->filterDate = '';
        $this->search = '';
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
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterDate === 'today', fn ($q) => $q->whereDate('created_at', today()))
            ->when($this->filterDate === 'week', fn ($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
            ->when($this->filterDate === 'month', fn ($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
            ->latest()
            ->paginate($this->perPage);
    }

    /** @return array<string, int> */
    #[Computed]
    public function stats(): array
    {
        return [
            'revenue' => (int) Payment::where('status', PaymentStatus::SUCCESS)->sum('amount_cents'),
            'pending' => Order::where('status', OrderStatus::PENDING)->count(),
            'processing' => Order::where('status', OrderStatus::PROCESSING)->count(),
            'out_for_delivery' => Order::where('status', OrderStatus::OUT_FOR_DELIVERY)->count(),
        ];
    }

    /** @return array<int, OrderStatus> */
    public function statuses(): array
    {
        return OrderStatus::cases();
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Orders</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush
            <flux:heading size="xl">Orders</flux:heading>
            <flux:subheading>Track and fulfil customer orders.</flux:subheading>
        </div>
    </div>

    {{-- Stat tiles --}}
    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <flux:card class="flex items-center gap-4">
            <flux:icon.banknotes class="size-9 text-emerald-400 shrink-0" />
            <div class="min-w-0">
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{!! money($this->stats['revenue']) !!}</div>
                <flux:text size="sm">Total revenue</flux:text>
            </div>
        </flux:card>
        <button type="button" wire:click="$set('filterStatus', '{{ OrderStatus::PENDING->value }}')"
            class="text-left transition-shadow hover:shadow-md focus:outline-none">
            <flux:card class="flex items-center gap-4 h-full">
                <flux:icon.clock class="size-9 text-amber-400 shrink-0" />
                <div>
                    <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['pending'] }}</div>
                    <flux:text size="sm">Pending</flux:text>
                </div>
            </flux:card>
        </button>
        <button type="button" wire:click="$set('filterStatus', '{{ OrderStatus::PROCESSING->value }}')"
            class="text-left transition-shadow hover:shadow-md focus:outline-none">
            <flux:card class="flex items-center gap-4 h-full">
                <flux:icon.arrow-path class="size-9 text-blue-400 shrink-0" />
                <div>
                    <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['processing'] }}</div>
                    <flux:text size="sm">Processing</flux:text>
                </div>
            </flux:card>
        </button>
        <button type="button" wire:click="$set('filterStatus', '{{ OrderStatus::OUT_FOR_DELIVERY->value }}')"
            class="text-left transition-shadow hover:shadow-md focus:outline-none">
            <flux:card class="flex items-center gap-4 h-full">
                <flux:icon.truck class="size-9 text-orange-400 shrink-0" />
                <div>
                    <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['out_for_delivery'] }}</div>
                    <flux:text size="sm">Out for delivery</flux:text>
                </div>
            </flux:card>
        </button>
    </div>

    <flux:card class="mt-6 overflow-hidden p-0">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search order # or customer…"
                icon="magnifying-glass"
                clearable
                class="max-w-xs" />

            <div class="flex flex-wrap items-center gap-2">

                {{-- Date filter --}}
                <div class="flex rounded-lg border border-zinc-200 text-sm dark:border-zinc-700 overflow-hidden">
                    @foreach ([''=>'All', 'today'=>'Today', 'week'=>'This week', 'month'=>'This month'] as $val => $label)
                        <button type="button" wire:click="$set('filterDate', '{{ $val }}')"
                            @class([
                                'px-3 py-1.5 font-medium transition-colors',
                                'bg-brand-500 text-white' => $filterDate === $val,
                                'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800' => $filterDate !== $val,
                            ])>
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <flux:select wire:model.live="filterStatus" class="w-44">
                    <flux:select.option value="">All statuses</flux:select.option>
                    @foreach ($this->statuses() as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($filterStatus || $filterDate || $search)
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">
                        Clear
                    </flux:button>
                @endif

                <flux:select wire:model.live="perPage" class="w-28">
                    <flux:select.option value="10">10 / page</flux:select.option>
                    <flux:select.option value="25">25 / page</flux:select.option>
                    <flux:select.option value="50">50 / page</flux:select.option>
                    <flux:select.option value="100">100 / page</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Order</flux:table.column>
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column>Items</flux:table.column>
                <flux:table.column>Total</flux:table.column>
                <flux:table.column>Payment</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Placed</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->orders as $order)
                    <flux:table.row :key="$order->id">
                        <flux:table.cell variant="strong">
                            <span class="font-mono">{{ $order->order_number }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($order->user)
                                <div class="text-sm font-medium dark:text-white">{{ $order->user->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $order->user->email }}</div>
                            @else
                                <span class="text-zinc-400">Guest</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums text-zinc-500">{{ $order->items_count }}</flux:table.cell>
                        <flux:table.cell class="font-medium tabular-nums">{!! money($order->total_cents) !!}</flux:table.cell>
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
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:tooltip content="Activity log">
                                    <flux:button size="xs" variant="ghost" icon="clock"
                                        :href="route('admin.activity.item', ['order', $order->id])"
                                        wire:navigate />
                                </flux:tooltip>
                                <flux:button size="xs" variant="ghost" icon="eye" tooltip="View order"
                                    :href="route('admin.orders.show', $order)" wire:navigate />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="py-12 text-center text-zinc-400">
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
