<div class="container-dashboard">

    {{-- ================================================================== --}}
    {{-- PAGE HEADER                                                         --}}
    {{-- ================================================================== --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" class="font-bold tracking-tight">Dashboard</flux:heading>
                {{-- Real-time connection indicator --}}
                <div x-data="{ connected: false }" x-init="if (window.Echo) {
                    window.Echo.connector.pusher.connection.bind('connected', () => { connected = true; });
                    window.Echo.connector.pusher.connection.bind('disconnected', () => { connected = false; });
                    connected = window.Echo.connector.pusher.connection.state === 'connected';
                }"
                    class="flex items-center gap-1.5 px-2 py-1 rounded-full text-xs"
                    :class="connected ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' :
                        'bg-zinc-100 dark:bg-zinc-800 text-zinc-500'"
                    :title="connected ? 'Real-time updates active' : 'Connecting...'">
                    <span class="relative flex h-2 w-2">
                        <span x-show="connected"
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2"
                            :class="connected ? 'bg-green-500' : 'bg-zinc-400'"></span>
                    </span>
                    <span x-text="connected ? 'Live' : 'Offline'"></span>
                </div>
            </div>
            <flux:subheading>{{ $this->periodLabel }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
            <flux:icon.loading wire:loading wire:target="setDateRange,exportCsv" class="dark:text-white! size-3.5" />

            <div class="relative" wire:ignore>
                <input type="text" readonly
                    class="dashboard-date-range w-64 pl-8 pr-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-300 hover:border-zinc-400 transition-colors" />
                <flux:icon.calendar-days
                    class="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" />
            </div>

            <flux:button wire:click="exportCsv" icon="arrow-down-tray" variant="ghost" size="sm">
                Export CSV
            </flux:button>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- ROW 1: KPI CARDS                                                    --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-2 @lg:grid-cols-4 gap-4 mb-4">

        <flux:card class="p-4">
            <div class="flex items-start justify-between mb-3">
                <flux:text class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">
                    Total revenue</flux:text>
                <div
                    class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center shrink-0">
                    <flux:icon.banknotes class="size-4 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>
            <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5"
                wire:key="kpi-revenue-{{ $this->salesStats['revenue'] }}" x-data="countUp({ to: {{ $this->salesStats['revenue'] }}, decimals: 2, prefix: 'KES ' })" x-intersect.once="start()" x-text="display">
            </flux:heading>
            <div class="flex items-center gap-1.5">
                @if ($this->salesStats['revenue_trend'] !== null)
                    <span
                        class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full {{ $this->salesStats['revenue_trend'] >= 0 ? 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400' }}">
                        {{ $this->salesStats['revenue_trend'] >= 0 ? '▲' : '▼' }}
                        {{ abs($this->salesStats['revenue_trend']) }}%
                    </span>
                @endif
                <flux:text class="text-xs text-zinc-400">than last period</flux:text>
            </div>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-start justify-between mb-3">
                <flux:text class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">
                    Orders</flux:text>
                <div
                    class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center shrink-0">
                    <flux:icon.shopping-bag class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5"
                wire:key="kpi-orders-{{ $this->salesStats['order_count'] }}" x-data="countUp({ to: {{ $this->salesStats['order_count'] }} })" x-intersect.once="start()" x-text="display">
            </flux:heading>
            <div class="flex items-center gap-1.5">
                @if ($this->salesStats['orders_trend'] !== null)
                    <span
                        class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full {{ $this->salesStats['orders_trend'] >= 0 ? 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400' }}">
                        {{ $this->salesStats['orders_trend'] >= 0 ? '▲' : '▼' }}
                        {{ abs($this->salesStats['orders_trend']) }}%
                    </span>
                @endif
                <flux:text class="text-xs text-zinc-400">{{ $this->salesStats['paid_count'] }} paid
                </flux:text>
            </div>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-start justify-between mb-3">
                <flux:text class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">
                    Customers</flux:text>
                <div
                    class="w-9 h-9 rounded-lg bg-violet-50 dark:bg-violet-950/50 flex items-center justify-center shrink-0">
                    <flux:icon.users class="size-4 text-violet-600 dark:text-violet-400" />
                </div>
            </div>
            <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5"
                wire:key="kpi-customers-{{ $this->customerStats['total'] }}" x-data="countUp({ to: {{ $this->customerStats['total'] }} })" x-intersect.once="start()" x-text="display">
            </flux:heading>
            <div class="flex items-center gap-1.5">
                @if ($this->customerStats['new_trend'] !== null)
                    <span
                        class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full {{ $this->customerStats['new_trend'] >= 0 ? 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400' }}">
                        {{ $this->customerStats['new_trend'] >= 0 ? '▲' : '▼' }}
                        {{ $this->customerStats['new'] }} new
                    </span>
                @endif
                <flux:text class="text-xs text-zinc-400">than last period</flux:text>
            </div>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-start justify-between mb-3">
                <flux:text class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">
                    Products</flux:text>
                <div
                    class="w-9 h-9 rounded-lg bg-teal-50 dark:bg-teal-950/50 flex items-center justify-center shrink-0">
                    <flux:icon.cube class="size-4 text-teal-600 dark:text-teal-400" />
                </div>
            </div>
            <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5"
                wire:key="kpi-products-{{ $this->productStats['active'] }}" x-data="countUp({ to: {{ $this->productStats['active'] }} })" x-intersect.once="start()" x-text="display">
            </flux:heading>
            <div class="flex items-center gap-1.5">
                @if ($this->productStats['low_stock'] + $this->productStats['out_of_stock'] > 0)
                    <span
                        class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400">
                        {{ $this->productStats['low_stock'] + $this->productStats['out_of_stock'] }} need
                        attention
                    </span>
                @else
                    <span
                        class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400">All
                        stocked</span>
                @endif
                <flux:text class="text-xs text-zinc-400">active</flux:text>
            </div>
        </flux:card>

    </div>

    {{-- ================================================================== --}}
    {{-- ROW 2: REVENUE CHART + TOP SALES LOCATION                          --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 @lg:grid-cols-3 gap-4 mb-4">

        {{-- Revenue chart — spans 2 cols --}}
        <flux:card class="p-0 @lg:col-span-2 h-full flex flex-col ">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <div>
                    <flux:heading>Revenue</flux:heading>
                </div>
                <div class="flex items-center gap-1 p-0.5 rounded-lg bg-zinc-100 dark:bg-zinc-800"
                    x-data="{ activeType: 'area' }">
                    <button @click="activeType = 'area'; window.switchRevenueChartType('area')" title="Area chart"
                        :class="activeType === 'area'
                            ?
                            'bg-white dark:bg-zinc-700 shadow-sm text-zinc-800 dark:text-zinc-100' :
                            'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300'"
                        class="p-1.5 rounded-md transition-colors">
                        <flux:icon.presentation-chart-line class="size-4" />
                    </button>
                    <button @click="activeType = 'bar'; window.switchRevenueChartType('bar')" title="Bar chart"
                        :class="activeType === 'bar'
                            ?
                            'bg-white dark:bg-zinc-700 shadow-sm text-zinc-800 dark:text-zinc-100' :
                            'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300'"
                        class="p-1.5 rounded-md transition-colors">
                        <flux:icon.chart-bar class="size-4" />
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 px-5 pt-4 pb-5">
                <div id="revenueChartData" data-labels="{{ json_encode($this->revenueChartData['labels']) }}"
                    data-revenue="{{ json_encode($this->revenueChartData['values']) }}"
                    data-orders="{{ json_encode($this->revenueChartData['order_counts']) }}"
                    data-refunds="{{ json_encode($this->revenueChartData['refund_counts']) }}">
                </div>
                <div wire:ignore style="position:relative; height:100%; width:100%;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </flux:card>


        {{-- Top Sales Locations — Unovis choropleth of Kenya counties --}}
        @php $salesLocations = $this->topSalesLocations; @endphp

        <flux:card class="p-0 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-100 dark:border-zinc-800">
                <div>
                    <flux:heading size="sm">Top Sales Locations</flux:heading>
                </div>
            </div>

            {{-- Map (rendered by Unovis on the client) --}}
            <div class="px-4 py-4">
                <div wire:ignore id="dashboard-sales-map"
                    data-locations="{{ json_encode($salesLocations) }}"
                    class="relative w-full overflow-hidden" style="height: 280px;">
                </div>
            </div>
        </flux:card>

        {{-- Trigger the Unovis map after the markup mounts; re-trigger on Livewire morph. --}}
        <script>
            (function() {
                function paintSalesMap() {
                    const el = document.getElementById('dashboard-sales-map');
                    if (!el || typeof window.__initSalesMap !== 'function') return;
                    let data = [];
                    try {
                        data = JSON.parse(el.dataset.locations || '[]');
                    } catch (e) {
                        data = [];
                    }
                    window.__initSalesMap('dashboard-sales-map', data);
                }
                document.addEventListener('DOMContentLoaded', paintSalesMap);
                document.addEventListener('livewire:navigated', paintSalesMap);
                document.addEventListener('livewire:initialized', () => {
                    Livewire.hook('morph.updated', paintSalesMap);
                });
                window.addEventListener('load', paintSalesMap);
            })();
        </script>
    </div>


    {{-- ================================================================== --}}
    {{-- ROW 3: RECENT ACTIVITY + RECENT ORDERS                              --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 @lg:grid-cols-3 gap-4 mb-4">

        {{-- Recent Activity — vertical timeline (Left, 1 col) --}}
        <flux:card class="p-0 flex flex-col">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading>Recent Activity</flux:heading>
                <flux:link :href="route('admin.activity-logs.index')" wire:navigate class="text-xs">View all</flux:link>
            </div>

            <div class="px-5 py-4">
                @forelse($this->recentActivities as $activity)
                    @php
                        $desc = $activity->description;

                        $bgClass = match (true) {
                            str_contains($desc, 'failed') || str_contains($desc, 'cancelled')    => 'bg-rose-100 dark:bg-rose-950/60',
                            str_contains($desc, 'confirmed') || str_contains($desc, 'paid') ||
                            str_contains($desc, 'success') || str_contains($desc, 'accepted')    => 'bg-emerald-100 dark:bg-emerald-950/60',
                            str_contains($desc, 'initiated') || str_contains($desc, 'requested') => 'bg-amber-100 dark:bg-amber-950/60',
                            default                                                               => 'bg-blue-100 dark:bg-blue-950/60',
                        };

                        $iconClass = match (true) {
                            str_contains($desc, 'failed') || str_contains($desc, 'cancelled')    => 'text-rose-600 dark:text-rose-400',
                            str_contains($desc, 'confirmed') || str_contains($desc, 'paid') ||
                            str_contains($desc, 'success') || str_contains($desc, 'accepted')    => 'text-emerald-600 dark:text-emerald-400',
                            str_contains($desc, 'initiated') || str_contains($desc, 'requested') => 'text-amber-600 dark:text-amber-400',
                            default                                                               => 'text-blue-600 dark:text-blue-400',
                        };

                        $textClass = $iconClass;
                    @endphp

                    <div class="relative flex gap-3 {{ $loop->last ? '' : 'pb-5' }}">
                        {{-- Connecting line --}}
                        @unless ($loop->last)
                            <div class="absolute left-[13px] top-8 bottom-0 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                        @endunless

                        {{-- Icon badge --}}
                        <div class="relative shrink-0 size-7 rounded-full {{ $bgClass }} flex items-center justify-center">
                            @if (str_contains($desc, 'payment'))
                                <flux:icon.currency-dollar class="size-3.5 {{ $iconClass }}" />
                            @elseif (str_contains($desc, 'order'))
                                <flux:icon.shopping-bag class="size-3.5 {{ $iconClass }}" />
                            @elseif (str_contains($desc, 'inventory') || str_contains($desc, 'stock'))
                                <flux:icon.chart-bar class="size-3.5 {{ $iconClass }}" />
                            @elseif (str_contains($desc, 'sap') || str_contains($desc, 'sync'))
                                <flux:icon.arrow-path class="size-3.5 {{ $iconClass }}" />
                            @elseif (str_contains($desc, 'quote'))
                                <flux:icon.document-text class="size-3.5 {{ $iconClass }}" />
                            @elseif (str_contains($desc, 'user') || str_contains($desc, 'customer'))
                                <flux:icon.user class="size-3.5 {{ $iconClass }}" />
                            @elseif (str_contains($desc, 'webhook'))
                                <flux:icon.bell class="size-3.5 {{ $iconClass }}" />
                            @elseif (str_contains($desc, 'product'))
                                <flux:icon.cube class="size-3.5 {{ $iconClass }}" />
                            @elseif (str_contains($desc, 'shipping') || str_contains($desc, 'delivery'))
                                <flux:icon.truck class="size-3.5 {{ $iconClass }}" />
                            @elseif (str_contains($desc, 'login') || str_contains($desc, 'auth'))
                                <flux:icon.lock-closed class="size-3.5 {{ $iconClass }}" />
                            @else
                                <flux:icon.bolt class="size-3.5 {{ $iconClass }}" />
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0 -mt-0.5">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 leading-snug">
                                    {{ str_replace('_', ' ', ucwords($desc, '_')) }}
                                </p>
                                <time class="text-[10px] text-zinc-400 whitespace-nowrap shrink-0">
                                    {{ $activity->created_at->diffForHumans() }}
                                </time>
                            </div>

                            @if ($activity->causer)
                                <p class="text-[10px] text-zinc-400 mt-0.5">{{ $activity->causer->name ?? 'System' }}</p>
                            @endif

                            @if ($activity->subject)
                                <p class="text-[10px] text-zinc-500 mt-0.5">
                                    @if ($activity->subject_type === 'App\Models\Order')
                                        Order #{{ $activity->subject->reference ?? 'N/A' }}
                                        @if ($activity->properties->has('total'))
                                            · {{ format_currency($activity->properties->get('total')) }}
                                        @endif
                                    @elseif ($activity->subject_type === 'App\Models\Payment')
                                        @if ($activity->properties->has('order_reference'))
                                            Order #{{ $activity->properties->get('order_reference') }}
                                        @endif
                                        @if ($activity->properties->has('amount'))
                                            · {{ format_currency($activity->properties->get('amount')) }}
                                        @endif
                                    @elseif ($activity->subject_type === 'App\Models\Quote')
                                        Quote #{{ $activity->subject->reference ?? 'N/A' }}
                                    @elseif ($activity->subject_type === 'App\Models\User')
                                        {{ $activity->subject->email ?? 'User' }}
                                    @else
                                        {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                                    @endif
                                </p>
                            @endif

                            @if ($activity->properties->has('reason') || $activity->properties->has('error'))
                                <p class="text-[10px] text-rose-500 mt-0.5">
                                    {{ $activity->properties->get('reason') ?? $activity->properties->get('error') }}
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-zinc-400">No recent activity</p>
                @endforelse
            </div>
        </flux:card>

        {{-- Recent Orders Table (Right, 2 cols) — compact view; full detail on the Orders page --}}
        <flux:card class="p-0 @lg:col-span-2">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading>Recent orders</flux:heading>
                <flux:link :href="route('admin.orders.index')" wire:navigate class="text-xs">View all
                </flux:link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                                Reference</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                                Customer</th>
                            <th
                                class="text-right px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                                Amount</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($this->recentOrders as $order)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" wire:navigate
                                        class="block text-blue-600 dark:text-blue-400 font-medium text-xs hover:underline">{{ $order->reference }}</a>
                                    <span class="text-[10px] text-zinc-400 whitespace-nowrap">
                                        {{ $order->created_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-xs text-zinc-800 dark:text-zinc-200">
                                    {{ $order->user?->name ?? '—' }}
                                </td>
                                <td class="px-5 py-3 text-xs font-semibold text-zinc-900 dark:text-zinc-100 text-right">
                                    {{ format_currency($order->total) }}</td>
                                <td class="px-5 py-3">
                                    <flux:badge size="sm" :color="$order->status->color()">
                                        {{ $order->status->label() }}</flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-zinc-400 text-sm">No
                                    orders yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>

    </div>

    {{-- ================================================================== --}}
    {{-- ROW 4: STOCK REPORT + CUSTOMER SATISFACTION                        --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 @lg:grid-cols-3 gap-4 mb-4">

        {{-- Low Stock Report (Left, 2 cols) — only products at or below low_stock_threshold --}}
        <flux:card class="p-0 @lg:col-span-2">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading>Low stock report</flux:heading>
                <flux:link :href="route('admin.catalog.products.index')" wire:navigate class="text-xs">Manage stock
                </flux:link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">
                                Product</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">
                                SKU</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">
                                Status</th>
                            <th
                                class="text-right px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">
                                Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($this->stockReport as $product)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.catalog.products.edit', $product) }}" wire:navigate
                                        class="text-xs font-medium text-zinc-800 dark:text-zinc-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                        {{ Str::limit($product->name, 45) }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-xs text-zinc-400 font-mono">
                                    {{ $product->sku ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    @if ($product->stock_quantity === 0)
                                        <span
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400">Out
                                            of stock</span>
                                    @else
                                        <span
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400">Low
                                            stock</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-xs font-semibold text-zinc-800 dark:text-zinc-200 text-right">
                                    {{ $product->stock_quantity }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-zinc-400 text-sm">
                                    All stock levels are above the low-stock threshold.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>

        {{-- Customer Satisfaction (Right, 1 col) --}}
        <flux:card class="p-0 flex flex-col">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading>Customer satisfaction</flux:heading>
                <flux:link
                    :href="route('admin.coming-soon', ['feature' => 'Customer Satisfaction Report', 'description' =>
                        'Comprehensive customer satisfaction metrics and trends over time.'
                    ])"
                    wire:navigate class="text-xs">Report</flux:link>
            </div>

            <div class="p-5 flex flex-col flex-1">
                {{-- Data bridge --}}
                <div id="satisfactionChartData"
                    data-distribution="{{ json_encode(array_values($this->satisfactionStats['distribution'])) }}"
                    data-average="{{ $this->satisfactionStats['average'] ?? 0 }}"
                    data-total="{{ $this->satisfactionStats['total'] }}">
                </div>

                {{-- Donut chart + centre overlay --}}
                <div class="relative flex items-center justify-center" style="height:180px;">
                    <div wire:ignore style="position:relative; height:180px; width:180px;">
                        <canvas id="satisfactionChart"></canvas>
                    </div>
                    <div class="absolute flex flex-col items-center justify-center pointer-events-none">
                        @if ($this->satisfactionStats['average'])
                            <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 leading-none">
                                {{ $this->satisfactionStats['average'] }}
                            </span>
                            <span class="text-[10px] text-zinc-400 mt-0.5">out of 5</span>
                        @else
                            <span class="text-xs text-zinc-400">No reviews</span>
                        @endif
                    </div>
                </div>

                {{-- Star breakdown --}}
                @php
                    $ratingColors = [5 => '#10B981', 4 => '#3B82F6', 3 => '#F59E0B', 2 => '#F97316', 1 => '#F43F5E'];
                    $reviewTotal = $this->satisfactionStats['total'];
                @endphp
                <div class="mt-5 flex flex-col gap-2">
                    @foreach ($ratingColors as $star => $color)
                        @php
                            $count = $this->satisfactionStats['distribution'][$star] ?? 0;
                            $pct = $reviewTotal > 0 ? round(($count / $reviewTotal) * 100) : 0;
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-zinc-500 w-3 shrink-0">{{ $star }}</span>
                            <flux:icon.star class="size-3 shrink-0" style="color: {{ $color }};" />
                            <div class="flex-1 h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                    style="width: {{ $pct }}%; background-color: {{ $color }};"></div>
                            </div>
                            <span class="text-[10px] text-zinc-400 w-5 text-right shrink-0">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>

            </div>
        </flux:card>

    </div>

    {{-- ================================================================== --}}
    {{-- ROW 5: DELIVERIES · TOP CATEGORIES · NEW CUSTOMERS · TOP PRODUCTS  --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 @sm:grid-cols-2 @lg:grid-cols-4 gap-4">

        <flux:card class="p-0">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading>Deliveries</flux:heading>
                <flux:link :href="route('admin.orders.index')" wire:navigate class="text-xs">View all
                </flux:link>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->recentDeliveries as $delivery)
                    <a href="{{ route('admin.orders.show', $delivery) }}" wire:navigate
                        class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                        <div
                            class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-950/50 flex items-center justify-center shrink-0">
                            <flux:icon.truck class="size-3.5 text-teal-600 dark:text-teal-400" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-zinc-800 dark:text-zinc-200 truncate">
                                {{ $delivery->items->first()?->product?->name ?? $delivery->reference }}
                            </p>
                            <p class="text-[10px] text-zinc-400 truncate">by
                                {{ $delivery->user?->name ?? '—' }}</p>
                        </div>
                        <flux:badge size="sm" :color="$delivery->status->color()">
                            {{ $delivery->status->label() }}</flux:badge>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-zinc-400 text-xs">No deliveries yet</div>
                @endforelse
            </div>
        </flux:card>

        <flux:card class="p-0">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading>Top categories</flux:heading>
                <flux:link
                    :href="route('admin.coming-soon', ['feature' => 'Category Performance Report', 'description' =>
                        'Detailed breakdown of sales performance by product category.'
                    ])"
                    wire:navigate class="text-xs">Report</flux:link>
            </div>

            <div class="p-4 flex flex-col items-center gap-4">

                @php
                    $cats = $this->categoryStats['categories'];
                    $catTotal = $this->categoryStats['total'];
                    $catColors = ['#3B82F6', '#F43F5E', '#10B981', '#8B5CF6']; // blue, rose, green, purple
                    $radii = [88, 74, 60, 46];
                    $strokeW = 10;
                @endphp

                @if (!empty($cats))

                    {{-- Rainbow semicircle SVG --}}
                    <div style="position:relative; width:200px; height:116px;">
                        <svg viewBox="0 0 200 110" width="200" height="110" style="overflow:visible;">
                            @foreach ($cats as $i => $cat)
                                @php
                                    $r = $radii[$i] ?? max(32, 46 - ($i - 3) * 14);
                                    $color = $catColors[$i] ?? '#94A3B8';
                                    $pct = $cat['pct'] / 100; // 0.0–1.0
                                    // Circumference of a semicircle = π * r
                                    $circum = M_PI * $r;
                                    // Filled dash = pct * circumference, gap = remainder of full circle
                                    $filled = round($pct * $circum, 2);
                                    $total = round(2 * M_PI * $r, 2); // full circle circumference
                                    $gap = round($total - $filled, 2);
                                @endphp

                                {{-- Ghost arc (full grey semicircle background) --}}
                                <path
                                    d="M {{ 100 - $r }},100 A {{ $r }},{{ $r }} 0 0,1 {{ 100 + $r }},100"
                                    fill="none" stroke="rgba(0,0,0,0.07)" stroke-width="{{ $strokeW }}"
                                    stroke-linecap="round" class="dark:stroke-white/10" />

                                {{-- Filled arc — dasharray over the semicircle path --}}
                                {{-- We use stroke-dasharray on the path length --}}
                                <path id="arc-{{ $i }}"
                                    d="M {{ 100 - $r }},100 A {{ $r }},{{ $r }} 0 0,1 {{ 100 + $r }},100"
                                    fill="none" stroke="{{ $color }}" stroke-width="{{ $strokeW }}"
                                    stroke-linecap="round" pathLength="100"
                                    stroke-dasharray="{{ $cat['pct'] }} 100" />
                            @endforeach

                            {{-- Centre label --}}
                            <text x="100" y="94" text-anchor="middle" font-size="11" font-weight="600"
                                fill="{{ $catColors[0] ?? '#3B82F6' }}">Sales</text>
                            <text x="100" y="108" text-anchor="middle" font-size="14" font-weight="700"
                                style="fill:currentColor;">{{ number_format($catTotal) }}</text>
                        </svg>
                    </div>

                    {{-- 2×2 value grid --}}
                    <div class="w-full grid grid-cols-2 @sm:gap-2 gap-2">
                        @foreach ($cats as $i => $cat)
                            <div class="rounded-xl border border-zinc-100 dark:border-zinc-800 p-3 text-center">
                                <p class="text-base font-bold text-zinc-900 dark:text-zinc-100 mb-1"
                                    x-data="countUp({ to: {{ $cat['units'] }} })" x-intersect.once="start()" x-text="display">
                                </p>
                                <p
                                    class="flex items-center justify-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    <span class="inline-block w-2.5 h-2.5 rounded-sm shrink-0"
                                        style="background:{{ $catColors[$i] ?? '#94A3B8' }};"></span>
                                    {{ $cat['name'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center text-zinc-400 text-xs">No sales data in this period</div>
                @endif

            </div>
        </flux:card>

        <flux:card class="p-0">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading>New customers</flux:heading>
                <flux:link :href="route('admin.customers.index')" wire:navigate class="text-xs">View all
                </flux:link>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->recentCustomers as $customer)
                    <a href="{{ route('admin.customers.show', $customer) }}" wire:navigate
                        class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                        <div
                            class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center text-[10px] font-semibold text-zinc-500 shrink-0">
                            {{ strtoupper(substr($customer->name, 0, 2)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-zinc-800 dark:text-zinc-200 truncate">
                                {{ $customer->name }}</p>
                            <p class="text-[10px] text-zinc-400">
                                {{ $customer->created_at->diffForHumans() }}</p>
                        </div>
                        <flux:icon.chevron-right class="size-3.5 text-zinc-300 shrink-0" />
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-zinc-400 text-xs">No customers yet</div>
                @endforelse
            </div>
        </flux:card>

        <flux:card class="p-0">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading>Top products</flux:heading>
                <flux:link
                    :href="route('admin.coming-soon', ['feature' => 'Product Performance Report', 'description' =>
                        'In-depth analysis of best-selling products and revenue trends.'
                    ])"
                    wire:navigate class="text-xs">Report</flux:link>
            </div>
            <div class="p-4 flex flex-col gap-3">
                @php $prodColors = ['#10B981', '#3B82F6', '#F59E0B', '#8B5CF6', '#F43F5E', '#06B6D4']; @endphp
                @forelse ($this->topProductsChartData['items'] as $i => $item)
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs text-zinc-700 dark:text-zinc-300 truncate flex-1 min-w-0 pr-2">{{ Str::limit($item['name'], 22) }}</span>
                            <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 shrink-0"
                                x-data="countUp({ to: {{ $item['pct'] }}, suffix: '%' })" x-intersect.once="start()" x-text="display"></span>
                        </div>
                        <div class="h-1 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                            <div class="h-1 rounded-full transition-all duration-500"
                                style="width:{{ $item['pct'] }}%; background:{{ $prodColors[$i] ?? '#94A3B8' }};">
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-zinc-400 text-xs">No sales data in this period</div>
                @endforelse
            </div>
        </flux:card>

    </div>



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
        const chartInstances = {};
        const currencySymbol = '{{ get_currency_symbol() }}';
        const isDark = () => document.documentElement.classList.contains('dark');
        const gridColor = () => isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
        const textColor = () => isDark() ? '#71717a' : '#a1a1aa';

        // Persists the active chart type across Livewire DOM morphs (server always renders "area")
        let revenueChartType = 'area';

        // Deduplicates chart re-init when onMorph fires multiple times per update cycle
        let chartInitRafId = null;

        function scheduleInitAllCharts() {
            if (chartInitRafId) cancelAnimationFrame(chartInitRafId);
            // requestAnimationFrame runs after the browser has committed all DOM changes,
            // guaranteeing the #revenueChartData bridge has its new data-* attributes
            chartInitRafId = requestAnimationFrame(() => {
                chartInitRafId = null;
                initAllCharts();
            });
        }

        function destroyChart(canvasId) {
            const key = canvasId;
            if (chartInstances[key]) {
                chartInstances[key].destroy();
                delete chartInstances[key];
            }
            // Strip all inline styles/attributes Chart.js wrote onto the canvas
            // so the next instantiation starts from a clean slate
            const el = document.getElementById(canvasId);
            if (el) {
                el.removeAttribute('style');
                el.removeAttribute('width');
                el.removeAttribute('height');
            }
        }

        // -----------------------------------------------------------------------
        //  Revenue — reads from #revenueChartData bridge, draws into fixed wrapper
        //  Supports two chart types: 'area' (line+fill) and 'bar'
        // -----------------------------------------------------------------------
        function initRevenueChart() {
            const bridge = document.getElementById('revenueChartData');
            const canvas = document.getElementById('revenueChart');
            if (!bridge || !canvas) return;

            destroyChart('revenueChart');

            const labels = JSON.parse(bridge.dataset.labels || '[]');
            const revenue = JSON.parse(bridge.dataset.revenue || '[]');
            const orders = JSON.parse(bridge.dataset.orders || '[]');
            const refunds = JSON.parse(bridge.dataset.refunds || '[]');
            const isBar = revenueChartType === 'bar';

            // Smart y-axis number formatter — no currency symbol, just clean numbers
            const fmtRevenue = v => {
                if (v === 0) return '0';
                if (v >= 1_000_000) return (v / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
                if (v >= 1_000) return (v / 1_000).toFixed(1).replace(/\.0$/, '') + 'k';
                return v.toFixed(0);
            };

            const areaDatasets = [{
                    label: 'Earnings',
                    data: revenue,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16,185,129,0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#10B981',
                    pointHoverBorderWidth: 2,
                    yAxisID: 'yRevenue',
                },
                {
                    label: 'Orders',
                    data: orders,
                    borderColor: '#8B5CF6',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#8B5CF6',
                    pointHoverBorderWidth: 2,
                    yAxisID: 'yCount',
                },
                {
                    label: 'Refunds',
                    data: refunds,
                    borderColor: '#F43F5E',
                    backgroundColor: 'rgba(244,63,94,0.06)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#F43F5E',
                    pointHoverBorderWidth: 2,
                    yAxisID: 'yCount',
                },
            ];

            const barDatasets = [{
                    label: 'Earnings',
                    data: revenue,
                    type: 'bar',
                    backgroundColor: isDark() ? 'rgba(16,185,129,0.65)' : 'rgba(16,185,129,0.80)',
                    borderColor: '#10B981',
                    borderWidth: 0,
                    borderRadius: 4,
                    borderSkipped: false,
                    yAxisID: 'yRevenue',
                    order: 2,
                },
                {
                    label: 'Orders',
                    data: orders,
                    type: 'line',
                    borderColor: '#8B5CF6',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#8B5CF6',
                    pointHoverBorderWidth: 2,
                    yAxisID: 'yCount',
                    order: 1,
                },
            ];

            const sharedOptions = {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 8,
                        bottom: 0
                    },
                },
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: false
                    },
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
                                if (ctx.dataset.yAxisID === 'yRevenue') {
                                    return `  ${ctx.dataset.label}: ${currencySymbol} ${ctx.parsed.y.toLocaleString('en-KE', { minimumFractionDigits: 2 })}`;
                                }
                                return `  ${ctx.dataset.label}: ${ctx.parsed.y}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: textColor(),
                            font: {
                                size: 11
                            },
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 12,
                        },
                    },
                    yRevenue: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        grid: {
                            color: gridColor(),
                            drawTicks: false,
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: textColor(),
                            font: {
                                size: 11
                            },
                            padding: 8,
                            callback: v => fmtRevenue(v),
                        },
                    },
                    yCount: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: textColor(),
                            font: {
                                size: 11
                            },
                            padding: 8,
                            stepSize: 1,
                            callback: v => Number.isInteger(v) ? v : '',
                        },
                    },
                },
            };

            chartInstances['revenueChart'] = new Chart(canvas, {
                type: isBar ? 'bar' : 'line',
                data: {
                    labels,
                    datasets: isBar ? barDatasets : areaDatasets,
                },
                options: sharedOptions,
            });
        }

        // -----------------------------------------------------------------------
        //  Switch Revenue Chart Type — client-side only, no server round-trip
        // -----------------------------------------------------------------------
        window.switchRevenueChartType = function(type) {
            revenueChartType = type;
            initRevenueChart();
        };

        // -----------------------------------------------------------------------
        //  Satisfaction — donut chart of star-rating distribution
        // -----------------------------------------------------------------------
        function initSatisfactionChart() {
            const bridge = document.getElementById('satisfactionChartData');
            const canvas = document.getElementById('satisfactionChart');
            if (!bridge || !canvas) return;

            destroyChart('satisfactionChart');

            const distribution = JSON.parse(bridge.dataset.distribution || '[0,0,0,0,0]');
            const total = parseInt(bridge.dataset.total || '0');
            const colors = ['#10B981', '#3B82F6', '#F59E0B', '#F97316', '#F43F5E'];

            chartInstances['satisfactionChart'] = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
                    datasets: [{
                        data: total > 0 ? distribution : [1],
                        backgroundColor: total > 0 ? colors : [isDark() ? '#3f3f46' : '#e4e4e7'],
                        borderWidth: 0,
                        hoverOffset: total > 0 ? 6 : 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: total > 0,
                            backgroundColor: isDark() ? '#18181b' : '#ffffff',
                            borderColor: isDark() ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)',
                            borderWidth: 1,
                            titleColor: isDark() ? '#e4e4e7' : '#3f3f46',
                            bodyColor: isDark() ? '#a1a1aa' : '#71717a',
                            padding: 10,
                            callbacks: {
                                label: ctx => {
                                    const pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                                    return `  ${ctx.parsed} reviews (${pct}%)`;
                                },
                            },
                        },
                    },
                },
            });
        }

        function initAllCharts() {
            initRevenueChart();
            initSatisfactionChart();
        }

        function waitForLibraries(callback) {
            if (typeof jQuery !== 'undefined' && typeof moment !== 'undefined' && typeof jQuery.fn.daterangepicker !==
                'undefined' && typeof Chart !== 'undefined') {
                callback();
            } else {
                setTimeout(() => waitForLibraries(callback), 100);
            }
        }

        function initDateRangePicker() {
            const el = $('.dashboard-date-range').first();
            if (!el.length || typeof $.fn.daterangepicker === 'undefined') return;

            // Destroy existing instance if any
            if (el.data('daterangepicker')) {
                el.data('daterangepicker').remove();
            }

            el.daterangepicker({
                startDate: moment($wire.dateFrom),
                endDate: moment($wire.dateTo),
                opens: 'left',
                showDropdowns: true,
                alwaysShowCalendars: false,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month')
                        .endOf('month')
                    ]
                },
                locale: {
                    format: 'MMM DD, YYYY',
                    separator: ' – '
                },
            }, function(start, end, label) {
                console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format(
                    'YYYY-MM-DD') + ' (predefined range: ' + label + ')');
                const preset = label === 'Custom Range' ? 'custom' : label.toLowerCase().replace(/\s+/g, '_');
                $wire.setDateRange(preset, start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
            });

            // Set initial display value
            updateDateRangeDisplay();
        }

        function updateDateRangeDisplay() {
            const el = $('.dashboard-date-range').first();
            if (!el.length || !el.data('daterangepicker')) return;

            const picker = el.data('daterangepicker');
            const start = moment($wire.dateFrom);
            const end = moment($wire.dateTo);

            picker.setStartDate(start);
            picker.setEndDate(end);
            el.val(start.format('MMM DD, YYYY') + ' – ' + end.format('MMM DD, YYYY'));
        }

        // Boot - wait for libraries to load before initializing
        waitForLibraries(() => {
            initAllCharts();
            initDateRangePicker();
        });

        // Livewire 4 — fires during DOM morph; we defer via rAF so the bridge
        // element's data-* attributes are fully committed before re-initializing charts
        $wire.interceptMessage(({
            onSuccess
        }) => {
            onSuccess(({
                onMorph
            }) => {
                onMorph(async () => {
                    scheduleInitAllCharts();
                    waitForLibraries(() => {
                        updateDateRangeDisplay();
                    });
                });
            });
        });

    </script>
@endscript