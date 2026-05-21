<?php

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Models\PickupStation;
use Livewire\Attributes\{Title, Computed};
use Livewire\Component;

new #[Title('Logistics Overview')] class extends Component {
    public string $dateFrom = '';
    public string $dateTo = '';

    public function setDateRange(string $from, string $to): void
    {
        $this->dateFrom = $from;
        $this->dateTo = $to;
        unset($this->stats, $this->recentOrders);
    }

    #[Computed]
    public function stats(): array
    {
        $activeStatuses = [DeliveryOrderStatus::PENDING->value, DeliveryOrderStatus::PICKED_UP->value, DeliveryOrderStatus::IN_TRANSIT->value, DeliveryOrderStatus::OUT_FOR_DELIVERY->value];

        // Revenue window: use date range if set, otherwise current month
        $hasDateRange = $this->dateFrom && $this->dateTo;
        $rangeStart = $hasDateRange ? \Carbon\Carbon::parse($this->dateFrom)->startOfDay() : now()->startOfMonth();
        $rangeEnd = $hasDateRange ? \Carbon\Carbon::parse($this->dateTo)->endOfDay() : now()->endOfDay();

        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $periodRevenue = DeliveryOrder::whereBetween('created_at', [$rangeStart, $rangeEnd])->sum('shipping_cost');
        $lastMonthRevenue = DeliveryOrder::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->sum('shipping_cost');
        $revenueChange = $lastMonthRevenue > 0 ? round((($periodRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) : null;

        return [
            'active' => DeliveryOrder::whereIn('status', $activeStatuses)->where('is_return', false)->count(),
            'at_station' => DeliveryOrder::where('status', DeliveryOrderStatus::AT_STATION->value)->count(),
            'needs_attention' => DeliveryOrder::whereIn('status', [DeliveryOrderStatus::FAILED->value, DeliveryOrderStatus::RETURNING->value])->count(),
            'delivered_today' => DeliveryOrder::whereDate('delivered_at', today())
                ->whereIn('status', [DeliveryOrderStatus::DELIVERED->value, DeliveryOrderStatus::COLLECTED->value])
                ->count(),
            'this_month_revenue' => $periodRevenue,
            'last_month_revenue' => $lastMonthRevenue,
            'revenue_change' => $hasDateRange ? null : $revenueChange,
            'period_label' => $hasDateRange
                ? $rangeStart->format('M j') . ' – ' . $rangeEnd->format('M j, Y')
                : now()->format('F Y'),
        ];
    }

    #[Computed]
    public function statusBreakdown(): array
    {
        return DeliveryOrder::where('is_return', false)
            ->selectRaw('status, count(*) as total')
            ->whereNotIn('status', [DeliveryOrderStatus::DELIVERED->value, DeliveryOrderStatus::CANCELLED->value, DeliveryOrderStatus::COLLECTED->value])
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    #[Computed]
    public function recentOrders()
    {
        return DeliveryOrder::with(['shippingMethod', 'shippingZone'])
            ->where('is_return', false)
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->take(8)
            ->get();
    }

    #[Computed]
    public function pusAlerts()
    {
        return DeliveryOrder::with(['pickupStation'])
            ->where('status', DeliveryOrderStatus::AT_STATION->value)
            ->where(fn($q) => $q->where('collection_deadline_at', '<', now()->addDays(2)))
            ->orderBy('collection_deadline_at')
            ->take(6)
            ->get();
    }

    #[Computed]
    public function attentionOrders()
    {
        return DeliveryOrder::with(['shippingMethod', 'shippingZone'])
            ->whereIn('status', [DeliveryOrderStatus::FAILED->value, DeliveryOrderStatus::RETURNING->value])
            ->latest()
            ->take(5)
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
            ->with('shippingZone')
            ->get()
            ->map(
                fn($row) => [
                    'zone' => $row->shippingZone?->name ?? 'Unknown',
                    'total' => $row->total,
                    'revenue' => $row->revenue,
                ],
            )
            ->toArray();
    }
}; ?>

<x-admin.logistics.layout heading="Logistics Overview" subheading="Shipping, delivery, and logistics at a glance.">

    {{-- Header --}}
    <div class="flex items-end justify-between mb-6">
        <div>
            <p class="text-sm text-zinc-500">{{ now()->format('l, d F Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:icon.loading wire:loading wire:target="setDateRange" class="size-3.5 text-zinc-400" />

            <div class="relative" wire:ignore>
                <input type="text" readonly
                    class="logistics-date-range w-60 pl-8 pr-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-300 hover:border-zinc-400 transition-colors"
                    placeholder="All time" />
                <flux:icon.calendar-days class="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" />
            </div>

            <flux:button variant="ghost" size="sm" icon="arrow-path" wire:click="$refresh"
                class="cursor-pointer text-zinc-400">
                Refresh
            </flux:button>
        </div>
    </div>

    <div class="space-y-6">

        {{--  Top stat cards  --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- Active Deliveries --}}
            <a href="{{ route('admin.logistics.operations.delivery-orders') }}" wire:navigate class="group block">
                <flux:card class="p-5 hover:border-zinc-400 dark:hover:border-zinc-500 transition-colors">
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">Active</p>
                        <div class="w-7 h-7 rounded-md bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                            <flux:icon.truck class="w-4 h-4 text-blue-500" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white tabular-nums"
                        x-data="countUp({ to: {{ $this->stats['active'] }} })" x-text="display"></p>
                    <p class="text-xs text-zinc-400 mt-1">forward deliveries in progress</p>
                </flux:card>
            </a>

            {{-- At Pickup Station --}}
            <a href="{{ route('admin.logistics.operations.pus-tracker') }}" wire:navigate class="group block">
                <flux:card class="p-5 hover:border-zinc-400 dark:hover:border-zinc-500 transition-colors">
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">At Station</p>
                        <div
                            class="w-7 h-7 rounded-md bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center">
                            <flux:icon.building-storefront class="w-4 h-4 text-orange-500" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white tabular-nums"
                        x-data="countUp({ to: {{ $this->stats['at_station'] }} })" x-text="display"></p>
                    <p class="text-xs text-zinc-400 mt-1">awaiting customer collection</p>
                </flux:card>
            </a>

            {{-- Needs Attention --}}
            <a href="{{ route('admin.logistics.operations.delivery-orders', ['filterStatus' => 'failed']) }}"
                wire:navigate class="group block">
                <flux:card
                    class="p-5 transition-colors
                {{ $this->stats['needs_attention'] > 0
                    ? 'border-red-200 dark:border-red-900 hover:border-red-400'
                    : 'hover:border-zinc-400 dark:hover:border-zinc-500' }}">
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">Attention</p>
                        <div
                            class="w-7 h-7 rounded-md
                        {{ $this->stats['needs_attention'] > 0 ? 'bg-red-50 dark:bg-red-900/30' : 'bg-zinc-50 dark:bg-zinc-800' }}
                        flex items-center justify-center">
                            <flux:icon.exclamation-triangle
                                class="w-4 h-4
                            {{ $this->stats['needs_attention'] > 0 ? 'text-red-500' : 'text-zinc-400' }}" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold tabular-nums {{ $this->stats['needs_attention'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}"
                        x-data="countUp({ to: {{ $this->stats['needs_attention'] }} })" x-text="display"></p>
                    <p class="text-xs text-zinc-400 mt-1">failed or returning</p>
                </flux:card>
            </a>

            {{-- Revenue --}}
            <flux:card class="p-5">
                <div class="flex items-start justify-between mb-3">
                    <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">
                        Revenue @if($dateFrom || $dateTo)(filtered)@else (MTD)@endif
                    </p>
                    <div class="w-7 h-7 rounded-md bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
                        <flux:icon.banknotes class="w-4 h-4 text-green-500" />
                    </div>
                </div>
                <p class="text-3xl font-bold text-zinc-900 dark:text-white tabular-nums"
                    x-data="countUp({ to: {{ $this->stats['this_month_revenue'] }}, decimals: 2, prefix: 'KES ' })" x-text="display"></p>
                <div class="flex items-center gap-1.5 mt-1">
                    @if ($this->stats['revenue_change'] !== null)
                        @php $up = $this->stats['revenue_change'] >= 0; @endphp
                        <span @class([
                            'text-sm font-medium flex items-center gap-0.5',
                            'text-green-600' => $up,
                            'text-red-500' => !$up,
                        ])>
                            @if ($up)
                                <flux:icon.arrow-long-up class="size-3.5" />
                            @else
                                <flux:icon.arrow-long-down class="size-3.5" />
                            @endif
                            {{ abs($this->stats['revenue_change']) }}%
                        </span>
                        <span class="text-xs text-zinc-400">vs last month</span>
                    @else
                        <span class="text-xs text-zinc-400">{{ get_currency_symbol() }} · first month</span>
                    @endif
                </div>
            </flux:card>
        </div>

        {{--  Middle row  --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- Recent Orders (spans 2 cols) --}}
            <flux:card class="p-0 lg:col-span-2">
                <div
                    class="flex items-center justify-between px-5 pt-5 pb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Recent Orders</h3>
                        <p class="text-xs text-zinc-400 mt-0.5">Last 8 forward deliveries</p>
                    </div>
                    <a href="{{ route('admin.logistics.operations.delivery-orders') }}" wire:navigate
                        class="text-xs text-zinc-400 hover:text-zinc-600 transition-colors flex items-center gap-2">
                        View all
                        <flux:icon.arrow-long-right class="size-5 text-inherit" />
                    </a>
                </div>

                <div class="divide-y divide-zinc-50 dark:divide-zinc-800/60">
                    @forelse ($this->recentOrders as $order)
                        @php
                            $status =
                                $order->status instanceof \App\Enums\DeliveryOrderStatus
                                    ? $order->status
                                    : \App\Enums\DeliveryOrderStatus::from($order->status);
                        @endphp
                        <div
                            class="flex items-center justify-between px-5 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                            <div class="flex items-center gap-3">
                                <div>
                                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                        #{{ $order->order_id }}
                                    </span>
                                    <span class="text-xs text-zinc-400 ml-2">
                                        {{ $order->shippingMethod->name }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-zinc-400 hidden sm:block">
                                    {{ $order->created_at->diffForHumans() }}
                                </span>
                                <span
                                    class="text-xs font-medium text-zinc-600 dark:text-zinc-300 tabular-nums hidden sm:block">
                                    {{ format_currency($order->shipping_cost) }}
                                </span>
                                <flux:badge :color="$status->color()" variant="flat" size="sm">
                                    {{ $status->label() }}
                                </flux:badge>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <p class="text-sm text-zinc-400">No orders yet.</p>
                        </div>
                    @endforelse
                </div>
            </flux:card>

            {{-- Right column: PUS Alerts + Status Breakdown --}}
            <div class="space-y-4">

                {{-- PUS Alerts --}}
                <flux:card class="p-0">
                    <div
                        class="flex items-center justify-between px-5 pt-5 pb-4 border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">PUS Alerts</h3>
                            @if ($this->pusAlerts->count())
                                <span
                                    class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs font-bold">
                                    {{ $this->pusAlerts->count() }}
                                </span>
                            @endif
                        </div>
                        <a href="{{ route('admin.logistics.operations.pus-tracker') }}" wire:navigate
                            class="text-xs text-zinc-400 hover:text-zinc-600 transition-colors flex items-center gap-2">
                            Tracker
                            <flux:icon.arrow-long-right class="size-5 text-inherit" />
                        </a>
                    </div>

                    <div class="divide-y divide-zinc-50 dark:divide-zinc-800/60">
                        @forelse ($this->pusAlerts as $parcel)
                            @php
                                $deadline = $parcel->collection_deadline_at;
                                $isOverdue = $deadline?->isPast();
                                $isToday = $deadline?->isToday();
                            @endphp
                            <div class="flex items-center justify-between px-5 py-3">
                                <div>
                                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                        #{{ $parcel->order_id }}
                                    </span>
                                    <p class="text-xs text-zinc-400 mt-0.5">
                                        {{ $parcel->pickupStation?->name ?? '—' }}
                                    </p>
                                </div>
                                @if ($deadline)
                                    <span
                                        class="text-xs font-medium
                                    {{ $isOverdue ? 'text-red-500' : ($isToday ? 'text-orange-500' : 'text-yellow-600') }}">
                                        {{ $isOverdue ? 'Overdue' : ($isToday ? 'Due today' : $deadline->format('d M')) }}
                                    </span>
                                @endif
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center">
                                <flux:icon.check-circle class="w-6 h-6 text-green-400 mx-auto mb-1" />
                                <p class="text-xs text-zinc-400">No urgent collections</p>
                            </div>
                        @endforelse
                    </div>
                </flux:card>

                {{-- Status Breakdown --}}
                <flux:card class="p-5">
                    <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Pipeline</h3>
                    @php
                        $breakdown = $this->statusBreakdown;
                        $total = array_sum($breakdown);
                        $stages = [
                            'pending' => ['Pending', 'bg-zinc-300 dark:bg-zinc-600'],
                            'picked_up' => ['Picked Up', 'bg-blue-400'],
                            'in_transit' => ['In Transit', 'bg-blue-500'],
                            'out_for_delivery' => ['Out for Delivery', 'bg-purple-500'],
                            'at_station' => ['At Station', 'bg-orange-400'],
                            'failed' => ['Failed', 'bg-red-500'],
                            'returning' => ['Returning', 'bg-yellow-500'],
                        ];
                    @endphp

                    @if ($total > 0)
                        {{-- Stacked bar --}}
                        <div class="flex h-2 rounded-full overflow-hidden mb-4 gap-px">
                            @foreach ($stages as $key => [$label, $color])
                                @php $count = $breakdown[$key] ?? 0; @endphp
                                @if ($count > 0)
                                    <div class="{{ $color }} transition-all"
                                        style="width: {{ round(($count / $total) * 100, 1) }}%"
                                        title="{{ $label }}: {{ $count }}">
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <div class="space-y-2">
                            @foreach ($stages as $key => [$label, $color])
                                @php $count = $breakdown[$key] ?? 0; @endphp
                                @if ($count > 0)
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full {{ $color }}"></div>
                                            <span class="text-xs text-zinc-500">{{ $label }}</span>
                                        </div>
                                        <span
                                            class="text-xs font-semibold tabular-nums text-zinc-700 dark:text-zinc-300">
                                            {{ $count }}
                                        </span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-zinc-400 text-center py-4">No active orders in pipeline.</p>
                    @endif
                </flux:card>
            </div>
        </div>

        {{--  Bottom row ─ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            {{-- Needs Attention --}}
            <flux:card class="p-0">
                <div
                    class="flex items-center justify-between px-5 pt-5 pb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Needs Attention</h3>
                        <p class="text-xs text-zinc-400 mt-0.5">Failed deliveries & active returns</p>
                    </div>
                    <a href="{{ route('admin.logistics.operations.delivery-orders', ['filterStatus' => 'failed']) }}"
                        wire:navigate
                        class="text-xs text-zinc-400 hover:text-zinc-600 transition-colors flex items-center gap-2">
                        View all
                        <flux:icon.arrow-long-right class="size-5 text-inherit" />
                    </a>
                </div>

                <div class="divide-y divide-zinc-50 dark:divide-zinc-800/60">
                    @forelse ($this->attentionOrders as $order)
                        @php
                            $status =
                                $order->status instanceof \App\Enums\DeliveryOrderStatus
                                    ? $order->status
                                    : \App\Enums\DeliveryOrderStatus::from($order->status);
                        @endphp
                        <div class="flex items-center justify-between px-5 py-3">
                            <div>
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                    #{{ $order->order_id }}
                                </span>
                                <span class="text-xs text-zinc-400 ml-2">{{ $order->shippingZone->name }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-zinc-400">{{ $order->updated_at->diffForHumans() }}</span>
                                <flux:badge :color="$status->color()" variant="flat" size="sm">
                                    {{ $status->label() }}
                                </flux:badge>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center">
                            <flux:icon.check-circle class="w-6 h-6 text-green-400 mx-auto mb-1" />
                            <p class="text-xs text-zinc-400">All clear — nothing needs attention</p>
                        </div>
                    @endforelse
                </div>
            </flux:card>

            {{-- Zone Breakdown + Quick Nav --}}
            <div class="space-y-4">

                {{-- Zone Breakdown --}}
                <flux:card class="p-5">
                    <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-4">
                        This Month by Zone
                    </h3>

                    @if (!empty($this->zoneBreakdown))
                        <div class="space-y-3">
                            @php
                                $maxZoneTotal = collect($this->zoneBreakdown)->max('total') ?: 1;
                            @endphp
                            @foreach ($this->zoneBreakdown as $row)
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                            {{ $row['zone'] }}
                                        </span>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs text-zinc-400 tabular-nums">
                                                {{ $row['total'] }} orders
                                            </span>
                                            <span
                                                class="text-xs font-semibold text-zinc-700 dark:text-zinc-200 tabular-nums">
                                                {{ format_currency($row['revenue']) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-zinc-800 dark:bg-zinc-300 rounded-full transition-all"
                                            style="width: {{ round(($row['total'] / $maxZoneTotal) * 100) }}%">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-zinc-400 text-center py-4">No orders this month yet.</p>
                    @endif
                </flux:card>

                {{-- Quick navigation --}}
                <flux:card class="p-5">
                    <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-3">Quick Access</h3>
                    <div class="grid grid-cols-2 gap-2">
                        @php
                            $links = [
                                [
                                    'route' => 'admin.logistics.operations.delivery-orders',
                                    'label' => 'Delivery Orders',
                                    'icon' => 'clipboard-document-list',
                                ],
                                [
                                    'route' => 'admin.logistics.operations.returns',
                                    'label' => 'Returns',
                                    'icon' => 'arrow-uturn-left',
                                ],
                                [
                                    'route' => 'admin.logistics.operations.pus-tracker',
                                    'label' => 'PUS Tracker',
                                    'icon' => 'building-storefront',
                                ],
                                [
                                    'route' => 'admin.logistics.configuration.rates.flat',
                                    'label' => 'Flat Rates',
                                    'icon' => 'table-cells',
                                ],
                                [
                                    'route' => 'admin.logistics.configuration.pickup-stations',
                                    'label' => 'Stations',
                                    'icon' => 'map-pin',
                                ],
                                [
                                    'route' => 'admin.logistics.configuration.providers',
                                    'label' => 'Providers',
                                    'icon' => 'building-office-2',
                                ],
                            ];
                        @endphp
                        @foreach ($links as $link)
                            <a href="{{ route($link['route']) }}" wire:navigate
                                class="flex items-center gap-2 px-3 py-2 rounded-lg
                                text-xs font-medium text-zinc-600 dark:text-zinc-400
                                hover:bg-zinc-100 dark:hover:bg-zinc-800
                                hover:text-zinc-900 dark:hover:text-zinc-100
                                transition-colors group">
                                {{-- <flux:icon::dynamic :icon="$link['icon']"
                                class="w-4 h-4 text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 transition-colors" /> --}}
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>
                </flux:card>
            </div>
        </div>

    </div>

</x-admin.logistics.layout>

@assets
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
@endassets

@script
<script>
    function waitForLibraries(cb) {
        if (typeof jQuery !== 'undefined' && typeof moment !== 'undefined' && typeof jQuery.fn.daterangepicker !== 'undefined') {
            cb();
        } else {
            setTimeout(() => waitForLibraries(cb), 100);
        }
    }

    function initDateRangePicker() {
        const el = $('.logistics-date-range').first();
        if (!el.length) return;

        if (el.data('daterangepicker')) {
            el.data('daterangepicker').remove();
        }

        el.daterangepicker({
            autoUpdateInput: false,
            opens: 'left',
            showDropdowns: true,
            alwaysShowCalendars: false,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            },
            locale: {
                format: 'MMM DD, YYYY',
                separator: ' – ',
                cancelLabel: 'Clear',
            },
        }, function(start, end) {
            $wire.setDateRange(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
            el.val(start.format('MMM DD, YYYY') + ' – ' + end.format('MMM DD, YYYY'));
        });

        el.on('cancel.daterangepicker', function() {
            $wire.setDateRange('', '');
            el.val('');
        });

        if ($wire.dateFrom && $wire.dateTo) {
            el.val(moment($wire.dateFrom).format('MMM DD, YYYY') + ' – ' + moment($wire.dateTo).format('MMM DD, YYYY'));
        }
    }

    waitForLibraries(() => initDateRangePicker());
</script>
@endscript
