<?php

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Models\LogisticsProvider;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Livewire\Attributes\{Computed, Title, Url};
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

new #[Title('Logistics')] class extends Component {
    use WithPagination;

    // KPI date range
    public string $dateFrom = '';

    public string $dateTo = '';

    // Table filters
    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterStatus = '';

    #[Url(history: true)]
    public string $filterMethod = '';

    #[Url(history: true)]
    public string $filterZone = '';

    #[Url(history: true)]
    public string $filterProvider = '';

    #[Url(history: true)]
    public string $filterDateFrom = '';

    #[Url(history: true)]
    public string $filterDateTo = '';

    #[Url(history: true)]
    public string $filterType = '';

    public int $perPage = 15;

    // Bulk action bridge
    public array $pendingBulkIds = [];

    public string $pendingBulkStatus = '';

    // ─── Pagination resets ────────────────────────────────────────────────────

    public function updatingSearch(): void { $this->resetPage(); }

    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function updatingFilterMethod(): void { $this->resetPage(); }

    public function updatingFilterZone(): void { $this->resetPage(); }

    public function updatingFilterProvider(): void { $this->resetPage(); }

    public function updatingFilterDateFrom(): void { $this->resetPage(); }

    public function updatingFilterDateTo(): void { $this->resetPage(); }

    public function updatingFilterType(): void { $this->resetPage(); }

    public function updatingPerPage(): void { $this->resetPage(); }

    public function setKpiDateRange(string $from, string $to): void
    {
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->filterDateFrom = $from;
        $this->filterDateTo = $to;
        $this->resetPage();
        unset($this->kpiStats, $this->statusBreakdown, $this->zoneBreakdown);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterType = '';
        $this->filterStatus = '';
        $this->filterMethod = '';
        $this->filterZone = '';
        $this->filterProvider = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        unset($this->kpiStats, $this->statusBreakdown, $this->zoneBreakdown);
        $this->resetPage();
    }

    // ─── KPI stats ────────────────────────────────────────────────────────────

    #[Computed]
    public function kpiStats(): array
    {
        $activeStatuses = [
            DeliveryOrderStatus::PENDING->value,
            DeliveryOrderStatus::PICKED_UP->value,
            DeliveryOrderStatus::IN_TRANSIT->value,
            DeliveryOrderStatus::OUT_FOR_DELIVERY->value,
        ];

        $hasRange   = $this->dateFrom && $this->dateTo;
        $rangeStart = $hasRange ? \Carbon\Carbon::parse($this->dateFrom)->startOfDay() : now()->startOfMonth();
        $rangeEnd   = $hasRange ? \Carbon\Carbon::parse($this->dateTo)->endOfDay()     : now()->endOfDay();

        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd   = now()->subMonth()->endOfMonth();

        $periodRevenue    = DeliveryOrder::whereBetween('created_at', [$rangeStart, $rangeEnd])->sum('shipping_cost');
        $lastMonthRevenue = DeliveryOrder::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->sum('shipping_cost');
        $revenueChange    = $lastMonthRevenue > 0
            ? round((($periodRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : null;

        return [
            'active'          => DeliveryOrder::whereIn('status', $activeStatuses)->where('is_return', false)->count(),
            'at_station'      => DeliveryOrder::where('status', DeliveryOrderStatus::AT_STATION->value)->count(),
            'needs_attention' => DeliveryOrder::whereIn('status', [DeliveryOrderStatus::FAILED->value, DeliveryOrderStatus::RETURNING->value])->count(),
            'delivered_today' => DeliveryOrder::whereDate('delivered_at', today())
                ->whereIn('status', [DeliveryOrderStatus::DELIVERED->value, DeliveryOrderStatus::COLLECTED->value])
                ->count(),
            'period_revenue'      => $periodRevenue,
            'last_month_revenue'  => $lastMonthRevenue,
            'revenue_change'      => $hasRange ? null : $revenueChange,
            'period_label'        => $hasRange
                ? $rangeStart->format('M j') . ' – ' . $rangeEnd->format('M j, Y')
                : now()->format('F Y'),
        ];
    }

    // ─── Charts ───────────────────────────────────────────────────────────────

    #[Computed]
    public function statusBreakdown(): array
    {
        return DeliveryOrder::where('is_return', false)
            ->selectRaw('status, count(*) as total')
            ->whereNotIn('status', [
                DeliveryOrderStatus::DELIVERED->value,
                DeliveryOrderStatus::CANCELLED->value,
                DeliveryOrderStatus::COLLECTED->value,
            ])
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    #[Computed]
    public function pusAlerts()
    {
        return DeliveryOrder::with('pickupStation')
            ->where('status', DeliveryOrderStatus::AT_STATION->value)
            ->where('collection_deadline_at', '<', now()->addDays(2))
            ->orderBy('collection_deadline_at')
            ->take(6)
            ->get();
    }

    #[Computed]
    public function zoneBreakdown(): array
    {
        return DeliveryOrder::with('shippingZone')
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('is_return', false)
            ->selectRaw('shipping_zone_id, count(*) as total, sum(shipping_cost) as revenue')
            ->groupBy('shipping_zone_id')
            ->get()
            ->map(fn($row) => [
                'zone'    => $row->shippingZone?->name ?? 'Unknown',
                'total'   => (int) $row->total,
                'revenue' => (float) $row->revenue,
            ])
            ->toArray();
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    #[Computed]
    public function orders()
    {
        return DeliveryOrder::with(['shippingMethod', 'shippingZone', 'logisticsProvider', 'pickupStation'])
            ->when($this->filterType === 'delivery', fn($q) => $q->where('is_return', false)->whereNull('pickup_station_id'))
            ->when($this->filterType === 'pus', fn($q) => $q->where('is_return', false)->whereNotNull('pickup_station_id'))
            ->when($this->filterType === 'returns', fn($q) => $q->where('is_return', true))
            ->when($this->search, fn($q) => $q->where(
                fn($q) => $q
                    ->where('id', 'like', "%{$this->search}%")
                    ->orWhere('provider_reference', 'like', "%{$this->search}%")
                    ->orWhere('order_id', 'like', "%{$this->search}%"),
            ))
            ->when($this->filterStatus,   fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterMethod,   fn($q) => $q->where('shipping_method_id', $this->filterMethod))
            ->when($this->filterZone,     fn($q) => $q->where('shipping_zone_id', $this->filterZone))
            ->when($this->filterProvider, fn($q) => $q->where('logistics_provider_id', $this->filterProvider))
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo,   fn($q) => $q->whereDate('created_at', '<=', $this->filterDateTo))
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function statusCounts(): array
    {
        $counts = DeliveryOrder::query()
            ->when($this->filterType === 'delivery', fn($q) => $q->where('is_return', false)->whereNull('pickup_station_id'))
            ->when($this->filterType === 'pus', fn($q) => $q->where('is_return', false)->whereNotNull('pickup_station_id'))
            ->when($this->filterType === 'returns', fn($q) => $q->where('is_return', true))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return array_merge(['all' => array_sum($counts)], $counts);
    }

    #[Computed]
    public function statuses(): array
    {
        return DeliveryOrderStatus::cases();
    }

    #[Computed]
    public function methods()
    {
        return ShippingMethod::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function providers()
    {
        return LogisticsProvider::where('status', 'active')->orderBy('name')->get();
    }

    // ─── Bulk actions ─────────────────────────────────────────────────────────

    public function prepareBulkAction(string $status, array $ids): void
    {
        if (empty($ids)) {
            $this->dispatch('notify', title: 'No Selection', variant: 'warning', message: 'No orders selected.');

            return;
        }

        $this->pendingBulkIds = $ids;
        $this->pendingBulkStatus = $status;
        Flux::modal('bulk-confirm')->show();
    }

    public function applyBulkStatus(): void
    {
        if (empty($this->pendingBulkIds) || ! $this->pendingBulkStatus) {
            return;
        }

        try {
            $count = DeliveryOrder::whereIn('id', $this->pendingBulkIds)
                ->where('is_return', false)
                ->update(['status' => $this->pendingBulkStatus]);

            $label = DeliveryOrderStatus::tryFrom($this->pendingBulkStatus)?->label() ?? $this->pendingBulkStatus;

            $this->pendingBulkIds = [];
            $this->pendingBulkStatus = '';

            unset($this->orders, $this->statusCounts, $this->kpiStats);
            Flux::modal('bulk-confirm')->close();

            $this->dispatch('notify', title: 'Bulk Update Complete', variant: 'success', message: "{$count} orders marked as {$label}.");
            $this->dispatch('delivery-bulk-done');
        } catch (\Throwable $e) {
            logger()->error('Bulk delivery status update failed.', [
                'exception' => $e->getMessage(),
                'ids'       => $this->pendingBulkIds,
                'user_id'   => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Bulk Update Failed', variant: 'danger', message: 'Bulk update failed. Please try again.');
        }
    }

    public function rendered(): void
    {
        $this->dispatch('delivery-orders-refreshed', ids: $this->orders->pluck('id')->toArray());
    }
};
?>

<div
    x-data="{
        selected: [],
        allIds: @js($this->orders->pluck('id')->toArray()),

        get allSelected() { return this.allIds.length > 0 && this.allIds.every(id => this.selected.includes(id)); },
        get someSelected() { return this.selected.length > 0 && !this.allSelected; },
        toggleAll() { this.selected = this.allSelected ? [] : [...this.allIds]; },
        toggle(id) { this.selected.includes(id) ? this.selected = this.selected.filter(i => i !== id) : this.selected.push(id); },
        isSelected(id) { return this.selected.includes(id); },
        clearSelection() { this.selected = []; },
        runBulkAction(status) { if (this.selected.length === 0) return; $wire.prepareBulkAction(status, this.selected); },

        columns: JSON.parse(localStorage.getItem('delivery_orders_columns') ?? 'null') ?? { method: true, zone: true, provider: true, estimated: true, deadline: false },
        toggleColumn(col) { this.columns[col] = !this.columns[col]; localStorage.setItem('delivery_orders_columns', JSON.stringify(this.columns)); },
    }"
    @delivery-orders-refreshed.window="allIds = [...$event.detail.ids]; selected = [];"
    @delivery-bulk-done.window="clearSelection()">

    <x-admin.logistics.layout heading="Logistics" subheading="Overview, pipeline, and delivery orders at a glance.">

        <x-slot:actions>
            <flux:icon.loading wire:loading wire:target="setKpiDateRange,clearFilters" class="size-3.5 text-zinc-400" />
            <div class="relative" wire:ignore>
                <input type="text" readonly
                    class="logistics-kpi-date-range w-56 pl-8 pr-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-300 hover:border-zinc-400 transition-colors"
                    placeholder="Filter by date" />
                <flux:icon.calendar-days class="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" />
            </div>
            <flux:button variant="ghost" size="sm" icon="arrow-path" wire:click="$refresh" class="cursor-pointer text-zinc-400">
                Refresh
            </flux:button>
        </x-slot:actions>

        <div class="space-y-6">

            {{-- ================================================================ --}}
            {{-- KPI CARDS                                                        --}}
            {{-- ================================================================ --}}
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">

                {{-- Active --}}
                <flux:card class="p-5">
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">Active</p>
                        <div class="w-7 h-7 rounded-md bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                            <flux:icon.truck class="w-4 h-4 text-blue-500" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white tabular-nums"
                        x-data="countUp({ to: {{ $this->kpiStats['active'] }} })" x-text="display"></p>
                    <p class="text-xs text-zinc-400 mt-1">in progress</p>
                </flux:card>

                {{-- At Station --}}
                <flux:card class="p-5 h-full hover:border-zinc-400 dark:hover:border-zinc-500 transition-colors cursor-pointer"
                    wire:click="$set('filterType', 'pus')">
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">At Station</p>
                        <div class="w-7 h-7 rounded-md bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center">
                            <flux:icon.building-storefront class="w-4 h-4 text-orange-500" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white tabular-nums"
                        x-data="countUp({ to: {{ $this->kpiStats['at_station'] }} })" x-text="display"></p>
                    <p class="text-xs text-zinc-400 mt-1">awaiting collection</p>
                </flux:card>

                {{-- Delivered Today --}}
                <flux:card class="p-5">
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">Delivered Today</p>
                        <div class="w-7 h-7 rounded-md bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                            <flux:icon.check-circle class="w-4 h-4 text-emerald-500" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 tabular-nums"
                        x-data="countUp({ to: {{ $this->kpiStats['delivered_today'] }} })" x-text="display"></p>
                    <p class="text-xs text-zinc-400 mt-1">{{ now()->format('M j, Y') }}</p>
                </flux:card>

                {{-- Needs Attention --}}
                <flux:card @class([
                    'p-5 transition-colors',
                    'border-red-200 dark:border-red-900' => $this->kpiStats['needs_attention'] > 0,
                ])>
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">Attention</p>
                        <div @class([
                            'w-7 h-7 rounded-md flex items-center justify-center',
                            'bg-red-50 dark:bg-red-900/30' => $this->kpiStats['needs_attention'] > 0,
                            'bg-zinc-50 dark:bg-zinc-800' => $this->kpiStats['needs_attention'] === 0,
                        ])>
                            <flux:icon.exclamation-triangle @class([
                                'w-4 h-4',
                                'text-red-500' => $this->kpiStats['needs_attention'] > 0,
                                'text-zinc-400' => $this->kpiStats['needs_attention'] === 0,
                            ]) />
                        </div>
                    </div>
                    <p @class([
                        'text-3xl font-bold tabular-nums',
                        'text-red-600 dark:text-red-400' => $this->kpiStats['needs_attention'] > 0,
                        'text-zinc-900 dark:text-white' => $this->kpiStats['needs_attention'] === 0,
                    ]) x-data="countUp({ to: {{ $this->kpiStats['needs_attention'] }} })" x-text="display"></p>
                    <p class="text-xs text-zinc-400 mt-1">failed or returning</p>
                </flux:card>

                {{-- Revenue --}}
                <flux:card class="p-5">
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider truncate pe-2">
                            Revenue
                            @if ($dateFrom || $dateTo)
                                <span class="text-blue-400 normal-case">(filtered)</span>
                            @else
                                (MTD)
                            @endif
                        </p>
                        <div class="w-7 h-7 rounded-md bg-green-50 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                            <flux:icon.banknotes class="w-4 h-4 text-green-500" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white tabular-nums"
                        x-data="countUp({ to: {{ $this->kpiStats['period_revenue'] }}, decimals: 2, prefix: 'KES ' })" x-text="display"></p>
                    <div class="flex items-center gap-1.5 mt-1">
                        @if ($this->kpiStats['revenue_change'] !== null)
                            @php $up = $this->kpiStats['revenue_change'] >= 0; @endphp
                            <span @class(['text-sm font-medium flex items-center gap-0.5', 'text-green-600' => $up, 'text-red-500' => !$up])>
                                @if ($up)
                                    <flux:icon.arrow-long-up class="size-3.5" />
                                @else
                                    <flux:icon.arrow-long-down class="size-3.5" />
                                @endif
                                {{ abs($this->kpiStats['revenue_change']) }}%
                            </span>
                            <span class="text-xs text-zinc-400">vs last month</span>
                        @else
                            <span class="text-xs text-zinc-400">{{ $this->kpiStats['period_label'] }}</span>
                        @endif
                    </div>
                </flux:card>

            </div>

            {{-- ================================================================ --}}
            {{-- CHARTS ROW                                                       --}}
            {{-- ================================================================ --}}
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">

                {{-- Pipeline (spans 2 cols) --}}
                <flux:card class="p-5 lg:col-span-2">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Pipeline</h3>
                            <p class="text-xs text-zinc-400 mt-0.5">Active delivery stages</p>
                        </div>
                        @php
                            $breakdown = $this->statusBreakdown;
                            $pipelineTotal = array_sum($breakdown);
                            $stages = [
                                'pending'          => ['Pending',          '#d1d5db'],
                                'picked_up'        => ['Picked Up',        '#93c5fd'],
                                'in_transit'       => ['In Transit',       '#3b82f6'],
                                'out_for_delivery' => ['Out for Delivery', '#a855f7'],
                                'at_station'       => ['At Station',       '#fb923c'],
                                'failed'           => ['Failed',           '#ef4444'],
                                'returning'        => ['Returning',        '#eab308'],
                            ];

                            $chartLabels = [];
                            $chartValues = [];
                            $chartColors = [];
                            foreach ($stages as $key => [$label, $hex]) {
                                $count = $breakdown[$key] ?? 0;
                                if ($count > 0) {
                                    $chartLabels[] = $label;
                                    $chartValues[] = $count;
                                    $chartColors[] = $hex;
                                }
                            }
                        @endphp
                        @if ($pipelineTotal > 0)
                            <flux:badge variant="outline" size="sm">{{ $pipelineTotal }} active</flux:badge>
                        @endif
                    </div>

                    {{-- Data bridge --}}
                    <div id="pipelineChartData"
                        data-labels="{{ json_encode($chartLabels) }}"
                        data-values="{{ json_encode($chartValues) }}"
                        data-colors="{{ json_encode($chartColors) }}"
                        data-total="{{ $pipelineTotal }}">
                    </div>

                    @if ($pipelineTotal > 0)
                        <div class="flex items-center gap-8">

                            {{-- Donut chart --}}
                            <div class="relative shrink-0 flex items-center justify-center" style="height:120px; width:120px;">
                                <div wire:ignore style="position:relative; height:120px; width:120px; z-index:20;">
                                    <canvas id="pipelineChart"></canvas>
                                </div>
                                <div class="absolute flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-xl font-bold text-zinc-800 dark:text-zinc-100 tabular-nums leading-none">
                                        {{ $pipelineTotal }}
                                    </span>
                                    <span class="text-[9px] text-zinc-400 uppercase tracking-wide mt-0.5">active</span>
                                </div>
                            </div>

                            {{-- Legend --}}
                            <div class="flex-1 space-y-2.5">
                                @foreach ($stages as $key => [$label, $hex])
                                    @php $count = $breakdown[$key] ?? 0; @endphp
                                    @if ($count > 0)
                                        <div class="flex items-center gap-2">
                                            <div class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $hex }}"></div>
                                            <span class="text-xs text-zinc-500 flex-1">{{ $label }}</span>
                                            <span class="text-xs text-zinc-400 tabular-nums w-8 text-right">
                                                {{ round(($count / $pipelineTotal) * 100) }}%
                                            </span>
                                            <span class="text-xs font-semibold tabular-nums text-zinc-700 dark:text-zinc-300 w-5 text-right">
                                                {{ $count }}
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-zinc-400">
                            <flux:icon.check-circle class="size-8 mb-2 text-green-400" />
                            <p class="text-sm">No active orders in pipeline</p>
                        </div>
                    @endif
                </flux:card>

                {{-- Zone Breakdown --}}
                <flux:card class="p-5">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">By Zone</h3>
                        <p class="text-xs text-zinc-400 mt-0.5">This month</p>
                    </div>

                    @if (!empty($this->zoneBreakdown))
                        @php $maxZoneTotal = collect($this->zoneBreakdown)->max('total') ?: 1; @endphp
                        <div class="space-y-3">
                            @foreach ($this->zoneBreakdown as $row)
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300 truncate pe-2">
                                            {{ $row['zone'] }}
                                        </span>
                                        <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-200 tabular-nums shrink-0">
                                            {{ $row['total'] }}
                                        </span>
                                    </div>
                                    <div class="h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-zinc-700 dark:bg-zinc-300 rounded-full transition-all"
                                            style="width: {{ round(($row['total'] / $maxZoneTotal) * 100) }}%">
                                        </div>
                                    </div>
                                    <div class="text-[10px] text-zinc-400 mt-0.5 text-right">
                                        {{ format_currency($row['revenue']) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-zinc-400">
                            <flux:icon.map class="size-7 mb-2" />
                            <p class="text-xs">No orders this month yet</p>
                        </div>
                    @endif
                </flux:card>

                {{-- PUS Alerts --}}
                <flux:card class="p-0">
                    <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">PUS Alerts</h3>
                            @if ($this->pusAlerts->count())
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs font-bold">
                                    {{ $this->pusAlerts->count() }}
                                </span>
                            @endif
                        </div>
                        <button wire:click="$set('filterType', 'pus')"
                            class="text-xs text-zinc-400 hover:text-zinc-600 transition-colors flex items-center gap-1">
                            All
                            <flux:icon.arrow-long-right class="size-4" />
                        </button>
                    </div>

                    <div class="divide-y divide-zinc-50 dark:divide-zinc-800/60">
                        @forelse ($this->pusAlerts as $parcel)
                            @php
                                $deadline = $parcel->collection_deadline_at;
                                $isOverdue = $deadline?->isPast();
                                $isToday   = $deadline?->isToday();
                            @endphp
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <div>
                                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">#{{ $parcel->order_id }}</span>
                                    <p class="text-[10px] text-zinc-400 mt-0.5">{{ $parcel->pickupStation?->name ?? '—' }}</p>
                                </div>
                                @if ($deadline)
                                    <span @class([
                                        'text-xs font-semibold',
                                        'text-red-500' => $isOverdue,
                                        'text-orange-500' => !$isOverdue && $isToday,
                                        'text-yellow-600' => !$isOverdue && !$isToday,
                                    ])>
                                        {{ $isOverdue ? 'Overdue' : ($isToday ? 'Due today' : $deadline->format('d M')) }}
                                    </span>
                                @endif
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center">
                                <flux:icon.check-circle class="w-7 h-7 text-green-400 mx-auto mb-1.5" />
                                <p class="text-xs text-zinc-400">No urgent collections</p>
                            </div>
                        @endforelse
                    </div>
                </flux:card>

            </div>

            {{-- ================================================================ --}}
            {{-- DELIVERY ORDERS TABLE                                            --}}
            {{-- ================================================================ --}}
            <div>
                <flux:card class="p-0">

                    {{-- Card header --}}
                    <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-600">
                        <h2 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Delivery Orders</h2>
                    </div>

                    {{-- Toolbar: search + per-page + columns + clear --}}
                    <div class="flex flex-wrap items-center gap-3 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">

                        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                            placeholder="Search by order ID, reference..." class="max-w-xs" clearable />

                        <div class="flex items-center gap-2 ms-auto">

                            <flux:select wire:model.live="perPage" class="w-24">
                                <flux:select.option value="10">10</flux:select.option>
                                <flux:select.option value="25">25</flux:select.option>
                                <flux:select.option value="50">50</flux:select.option>
                                <flux:select.option value="100">100</flux:select.option>
                            </flux:select>

                            {{-- Column visibility --}}
                            <flux:dropdown>
                                <flux:button icon="view-columns" icon-variant="outline" variant="ghost" size="sm">Columns</flux:button>
                                <flux:menu>
                                    @foreach (['method' => 'Method', 'zone' => 'Zone', 'provider' => 'Provider', 'estimated' => 'Estimated', 'deadline' => 'Collection Deadline'] as $col => $colLabel)
                                        <flux:menu.item @click.prevent="toggleColumn('{{ $col }}')">
                                            <span class="flex items-center gap-2">
                                                <span x-text="columns.{{ $col }} ? '✓' : ''" class="w-4 text-green-600 font-bold"></span>
                                                {{ $colLabel }}
                                            </span>
                                        </flux:menu.item>
                                    @endforeach
                                </flux:menu>
                            </flux:dropdown>

                            @if ($search || $filterType || $filterStatus || $filterMethod || $filterZone || $filterProvider || $filterDateFrom || $filterDateTo)
                                <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">Clear all</flux:button>
                            @endif

                        </div>
                    </div>

                    {{-- Inline filters row --}}
                    <div class="flex flex-wrap items-center gap-3 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50/50 dark:bg-zinc-800/30">
                        <flux:select wire:model.live="filterType" placeholder="All Types" class="w-44">
                            <flux:select.option value="delivery">Standard Delivery</flux:select.option>
                            <flux:select.option value="pus">Pickup Station</flux:select.option>
                            <flux:select.option value="returns">Returns</flux:select.option>
                        </flux:select>
                        <flux:select wire:model.live="filterStatus" placeholder="All Statuses" class="w-44">
                            @foreach ($this->statuses as $s)
                                <flux:select.option value="{{ $s->value }}">
                                    {{ $s->label() }} ({{ $this->statusCounts[$s->value] ?? 0 }})
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="filterMethod" placeholder="All Methods" class="w-44">
                            @foreach ($this->methods as $method)
                                <flux:select.option value="{{ $method->id }}">{{ $method->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="filterZone" placeholder="All Zones" class="w-44">
                            @foreach ($this->zones as $zone)
                                <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="filterProvider" placeholder="All Providers" class="w-44">
                            @foreach ($this->providers as $provider)
                                <flux:select.option value="{{ $provider->id }}">{{ $provider->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>


                    {{-- Bulk action bar --}}
                    <div x-cloak x-show="selected.length > 0"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="flex flex-wrap items-center gap-2 px-5 py-2.5 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
                        <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 me-1">
                            <span x-text="selected.length"></span> selected
                        </span>
                        <flux:button size="sm" variant="ghost" icon="hand-raised" icon-variant="outline"
                            @click="runBulkAction('{{ DeliveryOrderStatus::PICKED_UP->value }}')">Mark Picked Up</flux:button>
                        <flux:button size="sm" variant="ghost" icon="arrow-path" icon-variant="outline"
                            @click="runBulkAction('{{ DeliveryOrderStatus::IN_TRANSIT->value }}')">In Transit</flux:button>
                        <flux:button size="sm" variant="ghost" icon="truck" icon-variant="outline"
                            @click="runBulkAction('{{ DeliveryOrderStatus::OUT_FOR_DELIVERY->value }}')">Out for Delivery</flux:button>
                        <flux:button size="sm" variant="ghost" icon="check-circle" icon-variant="outline"
                            @click="runBulkAction('{{ DeliveryOrderStatus::DELIVERED->value }}')">Mark Delivered</flux:button>
                        <flux:button size="sm" variant="ghost" icon="x-circle" icon-variant="outline"
                            class="text-red-500! ms-auto"
                            @click="runBulkAction('{{ DeliveryOrderStatus::CANCELLED->value }}')">Cancel</flux:button>
                        <flux:button size="sm" variant="ghost" icon="x-mark" icon-variant="outline"
                            @click="clearSelection()">Clear</flux:button>
                    </div>

                    {{-- Table --}}
                    <flux:table :paginate="$this->orders">
                        <flux:table.columns>
                            <flux:table.column class="w-10 ps-4!">
                                <flux:checkbox x-ref="selectAll"
                                    x-effect="const cb = $refs.selectAll?.querySelector('input'); if (cb) cb.indeterminate = someSelected"
                                    ::checked="allSelected" @change="toggleAll()" />
                            </flux:table.column>
                            <flux:table.column>Order</flux:table.column>
                            <flux:table.column x-show="columns.method">Method</flux:table.column>
                            <flux:table.column x-show="columns.zone">Zone</flux:table.column>
                            <flux:table.column x-show="columns.provider">Provider</flux:table.column>
                            <flux:table.column>Cost</flux:table.column>
                            <flux:table.column x-show="columns.estimated">Estimated</flux:table.column>
                            <flux:table.column x-show="columns.deadline">Deadline</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @forelse ($this->orders as $order)
                                <flux:table.row :key="$order->id"
                                    x-bind:class="isSelected({{ $order->id }}) ? 'bg-zinc-50 dark:bg-zinc-800/40' : ''">

                                    <flux:table.cell class="ps-4! w-10">
                                        <flux:checkbox ::checked="isSelected({{ $order->id }})" @change="toggle({{ $order->id }})" />
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <span class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">#{{ $order->order_id }}</span>
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns.method">
                                        <span class="text-sm">{{ $order->shippingMethod?->name ?? '—' }}</span>
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns.zone">
                                        <span class="text-sm">{{ $order->shippingZone?->name ?? '—' }}</span>
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns.provider">
                                        @if ($order->logisticsProvider)
                                            <flux:badge size="sm" variant="outline" color="zinc">{{ $order->logisticsProvider->name }}</flux:badge>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                            {{ format_currency($order->shipping_cost) }}
                                        </span>
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns.estimated">
                                        @if ($order->estimated_delivery_at)
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-sm">{{ $order->estimated_delivery_at->format('M d, Y') }}</span>
                                                @if ($order->estimated_delivery_at->isPast() && $order->status->isActive())
                                                    <flux:tooltip content="Due {{ $order->estimated_delivery_at->diffForHumans() }}">
                                                        <flux:icon.exclamation-triangle class="size-3.5 text-rose-500 shrink-0" />
                                                    </flux:tooltip>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell x-show="columns.deadline">
                                        @if ($order->collection_deadline_at)
                                            <span class="text-sm">{{ $order->collection_deadline_at->format('M d, Y') }}</span>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:badge size="sm" variant="flat" color="zinc">
                                            {{ $order->status->label() }}
                                        </flux:badge>
                                        @if ($order->isOverdueCollection())
                                            <div class="text-xs text-zinc-500 mt-0.5 font-medium">Collection overdue</div>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell align="end" class="pe-4!">
                                        <flux:button variant="ghost" size="sm" icon="arrow-top-right-on-square"
                                            :href="route('admin.logistics.delivery-orders.show', $order)"
                                            wire:navigate class="cursor-pointer" />
                                    </flux:table.cell>

                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="10" class="py-16 text-center">
                                        <div class="flex flex-col items-center gap-3 text-zinc-400">
                                            <flux:icon.clipboard-document-list class="size-12 stroke-1" />
                                            <div>
                                                <flux:text class="font-medium text-zinc-500">No delivery orders found</flux:text>
                                                <flux:text class="text-xs mt-0.5">
                                                    @if ($search || $filterType || $filterStatus || $filterMethod || $filterZone || $filterProvider || $filterDateFrom || $filterDateTo)
                                                        No orders match your current filters.
                                                    @else
                                                        Delivery orders will appear here once customers place orders.
                                                    @endif
                                                </flux:text>
                                            </div>
                                            @if ($search || $filterType || $filterStatus || $filterMethod || $filterZone || $filterProvider || $filterDateFrom || $filterDateTo)
                                                <flux:button variant="ghost" size="sm" wire:click="clearFilters">Clear filters</flux:button>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>

                </flux:card>
            </div>

        </div>

        {{-- Bulk confirm --}}
        <flux:modal name="bulk-confirm" class="md:w-96 space-y-6">
            <flux:heading size="lg">Confirm Bulk Update</flux:heading>
            <flux:subheading>
                <span x-text="selected.length"></span> order(s) will be marked as
                <strong>{{ $pendingBulkStatus ? DeliveryOrderStatus::tryFrom($pendingBulkStatus)?->label() : '' }}</strong>.
                This cannot be undone.
            </flux:subheading>
            <div class="flex gap-3">
                <flux:modal.close class="flex-1">
                    <flux:button variant="ghost" class="w-full">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="applyBulkStatus" variant="primary" class="flex-1">Confirm</flux:button>
            </div>
        </flux:modal>

    </x-admin.logistics.layout>

</div>

@assets
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
@endassets

@script
<script>
    const isDark = () => document.documentElement.classList.contains('dark');
    const chartInstances = {};

    function waitForLibraries(cb) {
        if (typeof jQuery !== 'undefined' && typeof moment !== 'undefined' &&
            typeof jQuery.fn.daterangepicker !== 'undefined' && typeof Chart !== 'undefined') {
            cb();
        } else {
            setTimeout(() => waitForLibraries(cb), 100);
        }
    }

    // ─── Pipeline doughnut ────────────────────────────────────────────────────

    function destroyChart(id) {
        if (chartInstances[id]) {
            chartInstances[id].destroy();
            delete chartInstances[id];
        }
        const el = document.getElementById(id);
        if (el) { el.removeAttribute('style'); el.removeAttribute('width'); el.removeAttribute('height'); }
    }

    function initPipelineChart() {
        const bridge = document.getElementById('pipelineChartData');
        const canvas = document.getElementById('pipelineChart');
        if (!bridge || !canvas) return;

        destroyChart('pipelineChart');

        const labels = JSON.parse(bridge.dataset.labels || '[]');
        const values = JSON.parse(bridge.dataset.values || '[]');
        const colors = JSON.parse(bridge.dataset.colors || '[]');
        const total  = parseInt(bridge.dataset.total  || '0');

        if (total === 0) return;

        chartInstances['pipelineChart'] = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: isDark() ? '#18181b' : '#ffffff',
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark() ? '#18181b' : '#ffffff',
                        borderColor: isDark() ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)',
                        borderWidth: 1,
                        titleColor: isDark() ? '#e4e4e7' : '#3f3f46',
                        bodyColor: isDark() ? '#a1a1aa' : '#71717a',
                        padding: 10,
                        boxPadding: 4,
                        usePointStyle: true,
                        callbacks: {
                            label: ctx => {
                                const pct = Math.round((ctx.parsed / total) * 100);
                                return `  ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                            },
                        },
                    },
                },
            },
        });
    }

    // ─── Date range pickers ───────────────────────────────────────────────────

    const dateRangeOptions = {
        autoUpdateInput: false,
        opens: 'left',
        showDropdowns: true,
        alwaysShowCalendars: false,
        ranges: {
            'Today':       [moment(), moment()],
            'Yesterday':   [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days':[moment().subtract(29, 'days'), moment()],
            'This Month':  [moment().startOf('month'), moment().endOf('month')],
            'Last Month':  [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
        },
        locale: { format: 'MMM DD, YYYY', separator: ' – ', cancelLabel: 'Clear' },
    };

    function initKpiDatePicker() {
        const el = $('.logistics-kpi-date-range').first();
        if (!el.length) return;
        if (el.data('daterangepicker')) { el.data('daterangepicker').remove(); }

        el.daterangepicker(dateRangeOptions, function(start, end) {
            $wire.setKpiDateRange(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
            el.val(start.format('MMM DD, YYYY') + ' – ' + end.format('MMM DD, YYYY'));
        });
        el.on('cancel.daterangepicker', function() { $wire.setKpiDateRange('', ''); el.val(''); });
        if ($wire.dateFrom && $wire.dateTo) {
            el.val(moment($wire.dateFrom).format('MMM DD, YYYY') + ' – ' + moment($wire.dateTo).format('MMM DD, YYYY'));
        }
    }

    // ─── Boot ─────────────────────────────────────────────────────────────────

    let rafId = null;
    function scheduleInit() {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => { rafId = null; initPipelineChart(); });
    }

    waitForLibraries(() => {
        initPipelineChart();
        initKpiDatePicker();
    });

    $wire.interceptMessage(({ onSuccess }) => {
        onSuccess(({ onMorph }) => {
            onMorph(() => scheduleInit());
        });
    });
</script>
@endscript
