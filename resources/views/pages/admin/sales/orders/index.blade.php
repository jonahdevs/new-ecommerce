<?php

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};
use Illuminate\Support\Facades\Response;
use App\Enums\OrdersStatus;

new #[Title('Orders')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $dateFrom = '';
    public string $dateTo = '';
    public array $selected = [];
    public bool $selectAll = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }
    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value ? $this->orders->pluck('id')->map(fn($id) => (string) $id)->all() : [];
    }

    //  Computed

    #[Computed]
    public function orders()
    {
        return Order::query()
            ->with(['user', 'payment'])
            ->withCount('items')
            ->when($this->search, fn($q) => $q->where('reference', 'like', "%{$this->search}%")->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function stats(): array
    {
        $today = now()->toDateString();

        return [
            'total' => Order::count(),
            'revenue' => Order::sum('total_cents') / 100,
            'today' => Order::whereDate('created_at', $today)->count(),
            'pending' => Order::whereIn('status', ['pending', 'processing'])->count(),
        ];
    }

    #[Computed]
    public function statusOptions(): array
    {
        return [
            'all' => 'All Orders',
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];
    }

    #[Computed]
    public function statusCounts(): array
    {
        $counts = Order::query()->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();

        return array_merge(['all' => array_sum($counts)], $counts);
    }

    //  Bulk Actions

    public function bulkMarkProcessing(): void
    {
        $this->bulkUpdateStatus('processing');
    }

    public function bulkMarkShipped(): void
    {
        $this->bulkUpdateStatus('shipped');
    }

    public function bulkMarkCancelled(): void
    {
        $this->bulkUpdateStatus('cancelled');
    }

    private function bulkUpdateStatus(string $status): void
    {
        if (empty($this->selected)) {
            return;
        }

        Order::whereIn('id', $this->selected)->update(['status' => $status]);

        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;
        unset($this->orders, $this->statusCounts, $this->stats);

        $this->dispatch('notify', variant: 'success', message: "{$count} orders updated to {$status}.");
    }

    //  CSV Export

    public function export()
    {
        $query = Order::query()
            ->with(['user', 'payment'])
            ->when($this->search, fn($q) => $q->where('reference', 'like', "%{$this->search}%")->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->get();

        $rows = [];
        $rows[] = ['Reference', 'Customer', 'Email', 'Status', 'Payment Status', 'Gateway', 'Total', 'Items', 'Date'];

        foreach ($query as $order) {
            $rows[] = [$order->reference, $order->user->name, $order->user->email, $order->status->label(), $order->payment?->status?->label() ?? 'N/A', ucfirst($order->payment?->gateway ?? 'N/A'), $order->total, $order->items_count ?? $order->items()->count(), $order->created_at->format('Y-m-d H:i')];
        }

        $filename = 'orders-' . now()->format('Y-m-d') . '.csv';
        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::streamDownload(fn() => print $csv, $filename, ['Content-Type' => 'text/csv']);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Orders</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="mb-1">Orders</flux:heading>
            <flux:subheading>Manage customer orders, track shipments, and process payments.</flux:subheading>
        </div>
        <flux:button wire:click="export" icon="arrow-down-tray" variant="ghost" size="sm">
            Export CSV
        </flux:button>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <flux:card class="p-4 border-l-4 border-l-blue-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Total Orders</flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!">
                        {{ number_format($this->stats['total']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">All time</flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                    <flux:icon.shopping-bag class="size-5 text-blue-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-emerald-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Total Revenue</flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!">
                        {{ format_currency($this->stats['revenue']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">All time</flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                    <flux:icon.banknotes class="size-5 text-emerald-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-violet-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Orders Today</flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!">
                        {{ number_format($this->stats['today']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">{{ now()->format('M j, Y') }}</flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-violet-50 flex items-center justify-center shrink-0">
                    <flux:icon.calendar-days class="size-5 text-violet-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-amber-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Pending / Processing
                    </flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!">
                        {{ number_format($this->stats['pending']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">Needs attention</flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                    <flux:icon.clock class="size-5 text-amber-500" />
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="flex gap-2 overflow-x-auto pb-1 mb-4">
        @foreach ($this->statusOptions as $status => $label)
            @php
                $isActive = $statusFilter === $status;
                $count = $this->statusCounts[$status] ?? 0;
            @endphp
            <button wire:click="$set('statusFilter', '{{ $status }}')" @class([
                'flex items-center gap-2 px-3.5 py-1.5 rounded-full text-sm font-medium transition-colors whitespace-nowrap border cursor-pointer',
                'bg-zinc-900 text-white border-zinc-900' => $isActive,
                'bg-white text-zinc-500 border-zinc-200 hover:border-zinc-400 hover:text-zinc-700' => !$isActive,
            ])>
                {{ $label }}
                <span @class([
                    'inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-xs font-medium min-w-[1.25rem]',
                    'bg-white text-zinc-900' => $isActive,
                    'bg-zinc-100 text-zinc-500' => !$isActive,
                ])>
                    {{ $count }}
                </span>
            </button>
        @endforeach
    </div>


    {{-- Filters Row --}}
    <flux:card class="p-0 mt-4">
        <div class="flex flex-wrap justify-between items-center gap-3 px-4 py-3 border-b">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Search reference, name or email..." class="max-w-xs" />

            <div class="flex items-center gap-3">
                <flux:input wire:model.live="dateFrom" type="date" class="max-w-40" />

                <flux:input wire:model.live="dateTo" type="date" class="max-w-40" />

                @if ($search || $dateFrom || $dateTo || $statusFilter !== 'all')
                    <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">
                        Clear
                    </flux:button>
                @endif

                {{-- Bulk Actions --}}
                @if (count($selected) > 0)
                    <div class="ml-auto flex items-center gap-2">
                        <flux:text class="text-sm text-zinc-500">
                            {{ count($selected) }} selected
                        </flux:text>
                        <flux:dropdown>
                            <flux:button variant="filled" size="sm" icon-trailing="chevron-down">
                                Bulk Actions
                            </flux:button>
                            <flux:menu>
                                <flux:menu.item wire:click="bulkMarkProcessing" icon="arrow-path">
                                    Mark as Processing
                                </flux:menu.item>
                                <flux:menu.item wire:click="bulkMarkShipped" icon="truck">
                                    Mark as Shipped
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item wire:click="bulkMarkCancelled" icon="x-circle" variant="danger">
                                    Mark as Cancelled
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                @endif
            </div>
        </div>

        {{-- Table --}}
        <flux:table :paginate="$this->orders">
            <flux:table.columns>
                <flux:table.column class="ps-4! w-8">
                    <input type="checkbox" wire:model.live="selectAll"
                        class="rounded border-zinc-300 accent-zinc-800" />
                </flux:table.column>
                <flux:table.column>Order</flux:table.column>
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column>Items</flux:table.column>
                <flux:table.column>Total</flux:table.column>
                <flux:table.column>Payment</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->orders as $order)
                    <flux:table.row :key="$order->id" @class(['bg-blue-50/50' => in_array((string) $order->id, $selected)])>

                        {{-- Checkbox --}}
                        <flux:table.cell class="ps-4!">
                            <input type="checkbox" wire:model.live="selected" value="{{ $order->id }}"
                                class="rounded border-zinc-300 accent-zinc-800" />
                        </flux:table.cell>

                        {{-- Reference --}}
                        <flux:table.cell>
                            <div class="font-medium text-zinc-800">#{{ $order->reference }}</div>
                            @if ($order->is_pickup)
                                <flux:badge size="sm" color="blue" class="mt-1">Pickup</flux:badge>
                            @endif
                        </flux:table.cell>

                        {{-- Customer --}}
                        <flux:table.cell>
                            <div class="font-medium">{{ $order->user->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $order->user->email }}</div>
                        </flux:table.cell>

                        {{-- Date --}}
                        <flux:table.cell>
                            <div>{{ $order->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-zinc-500">{{ $order->created_at->format('h:i A') }}</div>
                        </flux:table.cell>

                        {{-- Items --}}
                        <flux:table.cell>
                            {{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}
                        </flux:table.cell>

                        {{-- Total --}}
                        <flux:table.cell>
                            <div class="font-medium">{{ format_currency($order->total) }}</div>
                        </flux:table.cell>

                        {{-- Payment --}}
                        <flux:table.cell>
                            @if ($order->payment)
                                <flux:badge size="sm" variant="flat" :color="$order->payment->status->color()">
                                    {{ $order->payment->status?->label() }}
                                </flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">No Payment</flux:badge>
                            @endif
                        </flux:table.cell>

                        {{-- Order Status --}}
                        <flux:table.cell>
                            <flux:badge size="sm" variant="flat" :color="$order->status->color()">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Actions --}}
                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="eye" icon-variant="outline"
                                :href="route('admin.orders.show', $order)" wire:navigate />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" class="text-center py-12">
                            <div class="flex flex-col items-center justify-center text-zinc-500">
                                <flux:icon.inbox class="w-12 h-12 mb-3 text-zinc-400" />
                                <p class="text-lg font-medium">No orders found</p>
                                <p class="text-sm mt-1">Try adjusting your filters or search query.</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
