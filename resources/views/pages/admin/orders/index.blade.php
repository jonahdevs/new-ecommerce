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
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

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

    /** Called from the range picker once both ends are chosen. */
    public function applyDateRange(): void
    {
        $this->validate([
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date', 'after_or_equal:dateFrom'],
        ]);

        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filterStatus = '';
        $this->dateFrom = '';
        $this->dateTo = '';
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
            ->when($this->dateFrom !== '' && $this->dateTo !== '', fn ($q) => $q->whereBetween('created_at', [
                \Illuminate\Support\Carbon::parse($this->dateFrom)->startOfDay(),
                \Illuminate\Support\Carbon::parse($this->dateTo)->endOfDay(),
            ]))
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

@assets
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endassets

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
        <flux:card class="flex items-center gap-4">
            <flux:icon.clock class="size-9 text-amber-400 shrink-0" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['pending'] }}</div>
                <flux:text size="sm">Pending</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.arrow-path class="size-9 text-blue-400 shrink-0" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['processing'] }}</div>
                <flux:text size="sm">Processing</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.truck class="size-9 text-orange-400 shrink-0" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['out_for_delivery'] }}</div>
                <flux:text size="sm">Out for delivery</flux:text>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6 overflow-hidden p-0">

        {{-- Export --}}
        <div class="flex flex-wrap items-center justify-end gap-2 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:dropdown>
                <flux:button size="sm" icon="arrow-down-tray" icon-trailing="chevron-down">Export</flux:button>
                <flux:menu>
                    <flux:menu.item icon="table-cells"
                        href="{{ route('admin.orders.export', array_filter(['format' => 'xlsx', 'q' => $search, 'status' => $filterStatus, 'from' => $dateFrom, 'to' => $dateTo])) }}">
                        Excel (.xlsx)
                    </flux:menu.item>
                    <flux:menu.item icon="document-text"
                        href="{{ route('admin.orders.export', array_filter(['format' => 'csv', 'q' => $search, 'status' => $filterStatus, 'from' => $dateFrom, 'to' => $dateTo])) }}">
                        CSV (.csv)
                    </flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item icon="document-chart-bar"
                        href="{{ route('admin.orders.pdf', array_filter(['q' => $search, 'status' => $filterStatus, 'from' => $dateFrom, 'to' => $dateTo])) }}">
                        PDF report
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search order # or customer…"
                icon="magnifying-glass"
                clearable
                class="max-w-xs" />

            <div class="flex flex-wrap items-center gap-2">

                {{-- Date range filter (same flatpickr range picker as the dashboard) --}}
                <div class="relative" wire:ignore x-data="rangePicker(@js($dateFrom), @js($dateTo))">
                    <flux:icon.calendar-days class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-zinc-400" />
                    <input x-ref="input" type="text" readonly placeholder="Date range"
                        class="w-52 cursor-pointer rounded-lg border border-zinc-200 bg-white py-1.5 pr-3 pl-8 text-sm text-zinc-700 transition-colors hover:border-zinc-400 focus:ring-2 focus:ring-zinc-300 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300" />
                </div>

                <flux:select wire:model.live="filterStatus" class="w-44">
                    <flux:select.option value="">All statuses</flux:select.option>
                    @foreach ($this->statuses() as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($filterStatus || $dateFrom || $dateTo || $search)
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
                        <flux:table.cell class="text-sm text-zinc-500">
                            {{ $order->user?->email ?? '—' }}
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

@script
    <script>
        Alpine.data('rangePicker', (from, to) => ({
            fp: null,

            init() {
                if (typeof flatpickr === 'undefined') {
                    return;
                }

                this.fp = flatpickr(this.$refs.input, {
                    mode: 'range',
                    dateFormat: 'M j, Y',
                    defaultDate: from && to ? [from, to] : null,
                    maxDate: 'today',
                    onClose: (dates) => {
                        if (dates.length === 2) {
                            this.$wire.set('dateFrom', this.fp.formatDate(dates[0], 'Y-m-d'));
                            this.$wire.set('dateTo', this.fp.formatDate(dates[1], 'Y-m-d'));
                            this.$wire.applyDateRange();
                        }
                    },
                });

                // Keep the picker in sync when "Clear" empties the range.
                this.$wire.$watch('dateTo', () => {
                    if (! this.$wire.dateFrom || ! this.$wire.dateTo) {
                        this.fp.clear();
                        return;
                    }
                    this.fp.setDate([
                        new Date(this.$wire.dateFrom + 'T00:00:00'),
                        new Date(this.$wire.dateTo + 'T00:00:00'),
                    ], false);
                });
            },
        }));
    </script>
@endscript
