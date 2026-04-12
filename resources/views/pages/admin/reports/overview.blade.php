<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;

new #[Title('Business Overview')] class extends Component {

    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfYear()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function setDateRange(string $from, string $to): void
    {
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->clearComputedCache();
    }

    private function clearComputedCache(): void
    {
        unset(
            $this->dateRange,
            $this->periodLabel,
            $this->kpiStats,
            $this->monthlyBreakdown,
            $this->fulfillmentFunnel,
            $this->b2bVsB2c,
            $this->revenueByZone,
        );
    }

    #[Computed]
    public function dateRange(): array
    {
        return [
            Carbon::parse($this->dateFrom)->startOfDay(),
            Carbon::parse($this->dateTo)->endOfDay(),
        ];
    }

    #[Computed]
    public function periodLabel(): string
    {
        [$from, $to] = $this->dateRange;

        return $from->isSameDay($to)
            ? $from->format('M j, Y')
            : $from->format('M j, Y') . ' – ' . $to->format('M j, Y');
    }

    #[Computed]
    public function kpiStats(): array
    {
        [$from, $to] = $this->dateRange;

        $yoyFrom = $from->copy()->subYear()->startOfDay();
        $yoyTo = $to->copy()->subYear()->endOfDay();

        // ── Current period ────────────────────────────────────────────────────
        $revenue = Order::where('payment_status', PaymentStatus::PAID->value)
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_cents') / 100;

        $orderCount = Order::whereBetween('created_at', [$from, $to])->count();
        $aov = $orderCount > 0 ? round($revenue / $orderCount, 2) : 0;

        $newCustomers = User::customer()->whereBetween('created_at', [$from, $to])->count();

        $activeCustomers = User::customer()
            ->whereHas('orders', fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->count();
        $returningCustomers = User::customer()
            ->whereHas('orders', fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->whereHas('orders', fn($q) => $q->where('created_at', '<', $from))
            ->count();
        $returningRate = $activeCustomers > 0
            ? round(($returningCustomers / $activeCustomers) * 100, 1)
            : 0;

        $quotes = Quote::whereBetween('created_at', [$from, $to]);
        $resolvedQuotes = (clone $quotes)
            ->whereIn('status', [QuoteStatus::ACCEPTED->value, QuoteStatus::REJECTED->value, QuoteStatus::EXPIRED->value])
            ->count();
        $acceptedQuotes = (clone $quotes)->where('status', QuoteStatus::ACCEPTED->value)->count();
        $quoteConversion = $resolvedQuotes > 0
            ? round(($acceptedQuotes / $resolvedQuotes) * 100, 1)
            : null;

        // ── Same period last year ─────────────────────────────────────────────
        $yoyRevenue = Order::where('payment_status', PaymentStatus::PAID->value)
            ->whereBetween('created_at', [$yoyFrom, $yoyTo])
            ->sum('total_cents') / 100;

        $yoyOrderCount = Order::whereBetween('created_at', [$yoyFrom, $yoyTo])->count();
        $yoyAov = $yoyOrderCount > 0 ? round($yoyRevenue / $yoyOrderCount, 2) : 0;

        $yoyNewCustomers = User::customer()->whereBetween('created_at', [$yoyFrom, $yoyTo])->count();

        $yoyActiveCustomers = User::customer()
            ->whereHas('orders', fn($q) => $q->whereBetween('created_at', [$yoyFrom, $yoyTo]))
            ->count();
        $yoyReturningCustomers = User::customer()
            ->whereHas('orders', fn($q) => $q->whereBetween('created_at', [$yoyFrom, $yoyTo]))
            ->whereHas('orders', fn($q) => $q->where('created_at', '<', $yoyFrom))
            ->count();
        $yoyReturningRate = $yoyActiveCustomers > 0
            ? round(($yoyReturningCustomers / $yoyActiveCustomers) * 100, 1)
            : 0;

        $yoyQuotes = Quote::whereBetween('created_at', [$yoyFrom, $yoyTo]);
        $yoyResolved = (clone $yoyQuotes)
            ->whereIn('status', [QuoteStatus::ACCEPTED->value, QuoteStatus::REJECTED->value, QuoteStatus::EXPIRED->value])
            ->count();
        $yoyAccepted = (clone $yoyQuotes)->where('status', QuoteStatus::ACCEPTED->value)->count();
        $yoyQuoteConversion = $yoyResolved > 0
            ? round(($yoyAccepted / $yoyResolved) * 100, 1)
            : null;

        $pct = fn($now, $prev) => $prev > 0 ? round((($now - $prev) / $prev) * 100, 1) : null;

        return [
            'yoy_label' => $yoyFrom->format('M j, Y') . ' – ' . $yoyTo->format('M j, Y'),

            'revenue'         => round($revenue, 2),
            'revenue_yoy'     => round($yoyRevenue, 2),
            'revenue_change'  => $pct($revenue, $yoyRevenue),

            'orders'          => $orderCount,
            'orders_yoy'      => $yoyOrderCount,
            'orders_change'   => $pct($orderCount, $yoyOrderCount),

            'aov'             => $aov,
            'aov_yoy'         => $yoyAov,
            'aov_change'      => $pct($aov, $yoyAov),

            'new_customers'         => $newCustomers,
            'new_customers_yoy'     => $yoyNewCustomers,
            'new_customers_change'  => $pct($newCustomers, $yoyNewCustomers),

            'returning_rate'        => $returningRate,
            'returning_rate_yoy'    => $yoyReturningRate,
            'returning_rate_change' => round($returningRate - $yoyReturningRate, 1),

            'quote_conversion'        => $quoteConversion,
            'quote_conversion_yoy'    => $yoyQuoteConversion,
            'quote_conversion_change' => ($quoteConversion !== null && $yoyQuoteConversion !== null)
                ? round($quoteConversion - $yoyQuoteConversion, 1)
                : null,
        ];
    }

    #[Computed]
    public function monthlyBreakdown(): array
    {
        [$from, $to] = $this->dateRange;

        $revenueRows = Order::where('payment_status', PaymentStatus::PAID->value)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_cents) / 100 as revenue, COUNT(*) as orders")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $newCustomerRows = User::customer()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month');

        $months = [];
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $row = $revenueRows[$key] ?? null;
            $orders = $row ? (int) $row->orders : 0;
            $revenue = $row ? round((float) $row->revenue, 2) : 0;

            $months[] = [
                'month'         => $cursor->format('M Y'),
                'orders'        => $orders,
                'revenue'       => $revenue,
                'aov'           => $orders > 0 ? round($revenue / $orders, 2) : 0,
                'new_customers' => (int) ($newCustomerRows[$key] ?? 0),
            ];

            $cursor->addMonth();
        }

        return $months;
    }

    #[Computed]
    public function fulfillmentFunnel(): array
    {
        [$from, $to] = $this->dateRange;

        $counts = Order::whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $total = array_sum($counts);

        if ($total === 0) {
            return ['total' => 0, 'stages' => [], 'lost' => 0, 'lost_pct' => 0, 'success_pct' => 0];
        }

        $stages = [
            OrderStatus::PENDING,
            OrderStatus::CONFIRMED,
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
        ];

        $lost = ($counts[OrderStatus::CANCELLED->value] ?? 0)
            + ($counts[OrderStatus::RETURNED->value] ?? 0);

        return [
            'total'       => $total,
            'stages'      => collect($stages)->map(fn($s) => [
                'label' => $s->label(),
                'value' => $s->value,
                'color' => $s->color(),
                'count' => $counts[$s->value] ?? 0,
                'pct'   => round((($counts[$s->value] ?? 0) / $total) * 100, 1),
            ])->toArray(),
            'cancelled'   => $counts[OrderStatus::CANCELLED->value] ?? 0,
            'returned'    => $counts[OrderStatus::RETURNED->value] ?? 0,
            'lost'        => $lost,
            'lost_pct'    => round(($lost / $total) * 100, 1),
            'success_pct' => round((($counts[OrderStatus::DELIVERED->value] ?? 0) / $total) * 100, 1),
        ];
    }

    #[Computed]
    public function b2bVsB2c(): array
    {
        [$from, $to] = $this->dateRange;

        $base = Order::where('payment_status', PaymentStatus::PAID->value)
            ->whereBetween('created_at', [$from, $to]);

        $b2bRevenue = (clone $base)->whereNotNull('quote_id')->sum('total_cents') / 100;
        $b2cRevenue = (clone $base)->whereNull('quote_id')->sum('total_cents') / 100;
        $b2bOrders  = (clone $base)->whereNotNull('quote_id')->count();
        $b2cOrders  = (clone $base)->whereNull('quote_id')->count();
        $total      = $b2bRevenue + $b2cRevenue;

        return [
            'b2b_revenue' => round($b2bRevenue, 2),
            'b2c_revenue' => round($b2cRevenue, 2),
            'b2b_orders'  => $b2bOrders,
            'b2c_orders'  => $b2cOrders,
            'b2b_pct'     => $total > 0 ? round(($b2bRevenue / $total) * 100, 1) : 0,
            'b2c_pct'     => $total > 0 ? round(($b2cRevenue / $total) * 100, 1) : 0,
            'total'       => round($total, 2),
        ];
    }

    #[Computed]
    public function revenueByZone(): array
    {
        [$from, $to] = $this->dateRange;

        $rows = DB::table('orders')
            ->join('delivery_orders', 'orders.id', '=', 'delivery_orders.order_id')
            ->join('shipping_zones', 'delivery_orders.shipping_zone_id', '=', 'shipping_zones.id')
            ->where('orders.payment_status', PaymentStatus::PAID->value)
            ->whereNotNull('delivery_orders.shipping_zone_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('shipping_zones.name as zone, COUNT(orders.id) as orders, SUM(orders.total_cents) / 100 as revenue')
            ->groupBy('shipping_zones.id', 'shipping_zones.name')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        $total = (float) $rows->sum('revenue');

        return $rows->map(fn($r) => [
            'zone'    => $r->zone,
            'orders'  => (int) $r->orders,
            'revenue' => round((float) $r->revenue, 2),
            'pct'     => $total > 0 ? round(((float) $r->revenue / $total) * 100, 1) : 0,
        ])->toArray();
    }

    public function exportCsv(): mixed
    {
        $rows = [['Month', 'Orders', 'Revenue (KES)', 'Avg Order Value (KES)', 'New Customers']];

        foreach ($this->monthlyBreakdown as $row) {
            $rows[] = [
                $row['month'],
                $row['orders'],
                number_format($row['revenue'], 2),
                number_format($row['aov'], 2),
                $row['new_customers'],
            ];
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::streamDownload(
            fn() => print $csv,
            'business-overview-' . now()->format('Y-m-d') . '.csv',
            ['Content-Type' => 'text/csv'],
        );
    }
};
?>

<div>
    {{-- Breadcrumb --}}
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Reports</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Business Overview</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Page header --}}
    <div class="flex items-start justify-between mb-4">
        <div>
            <flux:heading size="xl">Business Overview</flux:heading>
            <flux:subheading>{{ $this->periodLabel }} · Strategic performance summary.</flux:subheading>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
            <flux:icon.loading wire:loading wire:target="setDateRange" class="size-3.5 text-zinc-400" />

            <div class="relative" wire:ignore>
                <input type="text" readonly
                    class="overview-date-range w-64 pl-8 pr-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-300 hover:border-zinc-400 transition-colors"
                    placeholder="Select period" />
                <flux:icon.calendar-days class="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" />
            </div>

            <flux:button wire:click="exportCsv" icon="arrow-down-tray" variant="ghost" size="sm">
                Export CSV
            </flux:button>
        </div>
    </div>

    {{-- YoY context --}}
    <p class="text-xs text-zinc-400 mb-5">
        Comparing to same period last year: {{ $this->kpiStats['yoy_label'] }}
    </p>

    {{-- ================================================================== --}}
    {{-- KPI CARDS                                                            --}}
    {{-- ================================================================== --}}
    @php
        $kpi = $this->kpiStats;

        $kpiCards = [
            [
                'label'        => 'Revenue',
                'value'        => format_currency($kpi['revenue']),
                'raw'          => $kpi['revenue'],
                'countup'      => "countUp({ to: {$kpi['revenue']}, decimals: 2, prefix: 'KES ' })",
                'yoy'          => format_currency($kpi['revenue_yoy']),
                'change'       => $kpi['revenue_change'],
                'change_suffix'=> '%',
                'icon'         => 'banknotes',
                'color'        => 'emerald',
            ],
            [
                'label'        => 'Orders',
                'value'        => number_format($kpi['orders']),
                'raw'          => $kpi['orders'],
                'countup'      => "countUp({ to: {$kpi['orders']} })",
                'yoy'          => number_format($kpi['orders_yoy']),
                'change'       => $kpi['orders_change'],
                'change_suffix'=> '%',
                'icon'         => 'shopping-bag',
                'color'        => 'blue',
            ],
            [
                'label'        => 'Avg Order Value',
                'value'        => format_currency($kpi['aov']),
                'raw'          => $kpi['aov'],
                'countup'      => "countUp({ to: {$kpi['aov']}, decimals: 2, prefix: 'KES ' })",
                'yoy'          => format_currency($kpi['aov_yoy']),
                'change'       => $kpi['aov_change'],
                'change_suffix'=> '%',
                'icon'         => 'receipt-text',
                'color'        => 'violet',
            ],
            [
                'label'        => 'New Customers',
                'value'        => number_format($kpi['new_customers']),
                'raw'          => $kpi['new_customers'],
                'countup'      => "countUp({ to: {$kpi['new_customers']} })",
                'yoy'          => number_format($kpi['new_customers_yoy']),
                'change'       => $kpi['new_customers_change'],
                'change_suffix'=> '%',
                'icon'         => 'user-plus',
                'color'        => 'teal',
            ],
            [
                'label'        => 'Returning Rate',
                'value'        => $kpi['returning_rate'] . '%',
                'raw'          => $kpi['returning_rate'],
                'countup'      => "countUp({ to: {$kpi['returning_rate']}, decimals: 1, suffix: '%' })",
                'yoy'          => $kpi['returning_rate_yoy'] . '%',
                'change'       => $kpi['returning_rate_change'],
                'change_suffix'=> 'pp',
                'icon'         => 'arrow-path',
                'color'        => 'amber',
            ],
            [
                'label'        => 'Quote Conversion',
                'value'        => $kpi['quote_conversion'] !== null ? $kpi['quote_conversion'] . '%' : '—',
                'raw'          => $kpi['quote_conversion'],
                'countup'      => $kpi['quote_conversion'] !== null
                    ? "countUp({ to: {$kpi['quote_conversion']}, decimals: 1, suffix: '%' })"
                    : null,
                'yoy'          => $kpi['quote_conversion_yoy'] !== null ? $kpi['quote_conversion_yoy'] . '%' : '—',
                'change'       => $kpi['quote_conversion_change'],
                'change_suffix'=> 'pp',
                'icon'         => 'document-check',
                'color'        => 'rose',
            ],
        ];

        $iconColorMap = [
            'emerald' => ['icon' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-50 dark:bg-emerald-950/50'],
            'blue'    => ['icon' => 'text-blue-600 dark:text-blue-400',    'bg' => 'bg-blue-50 dark:bg-blue-950/50'],
            'violet'  => ['icon' => 'text-violet-600 dark:text-violet-400', 'bg' => 'bg-violet-50 dark:bg-violet-950/50'],
            'teal'    => ['icon' => 'text-teal-600 dark:text-teal-400',    'bg' => 'bg-teal-50 dark:bg-teal-950/50'],
            'amber'   => ['icon' => 'text-amber-600 dark:text-amber-400',  'bg' => 'bg-amber-50 dark:bg-amber-950/50'],
            'rose'    => ['icon' => 'text-rose-600 dark:text-rose-400',    'bg' => 'bg-rose-50 dark:bg-rose-950/50'],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
        @foreach ($kpiCards as $card)
            @php $colors = $iconColorMap[$card['color']]; @endphp
            <flux:card class="p-4">
                <div class="flex items-start justify-between mb-3">
                    <flux:text class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">
                        {{ $card['label'] }}
                    </flux:text>
                    <div class="w-9 h-9 rounded-lg {{ $colors['bg'] }} flex items-center justify-center shrink-0">
                        <flux:icon :name="$card['icon']" class="size-4 {{ $colors['icon'] }}" />
                    </div>
                </div>

                @if ($card['countup'])
                    <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5"
                        wire:key="kpi-{{ $card['color'] }}-{{ $card['raw'] }}"
                        x-data="{{ $card['countup'] }}" x-text="display">
                    </flux:heading>
                @else
                    <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5">
                        {{ $card['value'] }}
                    </flux:heading>
                @endif

                <div class="flex items-center gap-1.5 flex-wrap">
                    @if ($card['change'] !== null)
                        <span class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full {{ $card['change'] >= 0 ? 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400' }}">
                            {{ $card['change'] >= 0 ? '▲' : '▼' }} {{ abs($card['change']) }}{{ $card['change_suffix'] }}
                        </span>
                    @endif
                    <flux:text class="text-[10px] text-zinc-400">
                        {{ $card['yoy'] }} last year
                    </flux:text>
                </div>
            </flux:card>
        @endforeach
    </div>

    {{-- ================================================================== --}}
    {{-- MONTHLY TREND + B2B vs B2C                                         --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        {{-- Monthly trend chart — 2 cols --}}
        <flux:card class="p-0 lg:col-span-2">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <div>
                    <flux:heading>Monthly Revenue Trend</flux:heading>
                    <flux:text class="text-[11px] text-zinc-400">Revenue (bars) and order volume (line) by month</flux:text>
                </div>
            </div>

            <div class="p-5">
                {{-- Data bridge — updated by Livewire on each re-render --}}
                <div id="monthlyChartBridge"
                    data-labels="{{ json_encode(array_column($this->monthlyBreakdown, 'month')) }}"
                    data-revenue="{{ json_encode(array_column($this->monthlyBreakdown, 'revenue')) }}"
                    data-orders="{{ json_encode(array_column($this->monthlyBreakdown, 'orders')) }}">
                </div>
                {{-- Canvas is wire:ignored so Chart.js controls it --}}
                <div wire:ignore style="position:relative; height:280px;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </flux:card>

        {{-- B2B vs B2C --}}
        <flux:card class="p-0">
            <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading size="sm">B2B vs B2C</flux:heading>
                <flux:text class="text-[10px] text-zinc-400">Paid revenue split by order source</flux:text>
            </div>

            @php $split = $this->b2bVsB2c; @endphp

            @if ($split['total'] > 0)
                {{-- Stacked bar --}}
                <div class="px-5 pt-4 pb-2">
                    <div class="flex rounded-full overflow-hidden h-3">
                        @if ($split['b2b_pct'] > 0)
                            <div class="bg-blue-500 transition-all" style="width: {{ $split['b2b_pct'] }}%"></div>
                        @endif
                        @if ($split['b2c_pct'] > 0)
                            <div class="bg-violet-500 transition-all" style="width: {{ $split['b2c_pct'] }}%"></div>
                        @endif
                    </div>
                </div>

                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    {{-- B2B --}}
                    <div class="px-5 py-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-blue-500 shrink-0"></div>
                            <flux:text class="text-xs font-semibold text-zinc-700 dark:text-zinc-200">B2B — Quote Orders</flux:text>
                            <flux:badge size="sm" color="blue" class="ms-auto">{{ $split['b2b_pct'] }}%</flux:badge>
                        </div>
                        <flux:heading size="sm" class="font-bold! mb-0.5">{{ format_currency($split['b2b_revenue']) }}</flux:heading>
                        <flux:text class="text-xs text-zinc-400">{{ number_format($split['b2b_orders']) }} {{ Str::plural('order', $split['b2b_orders']) }}</flux:text>
                    </div>

                    {{-- B2C --}}
                    <div class="px-5 py-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-violet-500 shrink-0"></div>
                            <flux:text class="text-xs font-semibold text-zinc-700 dark:text-zinc-200">B2C — Direct Orders</flux:text>
                            <flux:badge size="sm" color="purple" class="ms-auto">{{ $split['b2c_pct'] }}%</flux:badge>
                        </div>
                        <flux:heading size="sm" class="font-bold! mb-0.5">{{ format_currency($split['b2c_revenue']) }}</flux:heading>
                        <flux:text class="text-xs text-zinc-400">{{ number_format($split['b2c_orders']) }} {{ Str::plural('order', $split['b2c_orders']) }}</flux:text>
                    </div>

                    {{-- Total --}}
                    <div class="px-5 py-3 bg-zinc-50 dark:bg-zinc-800/50">
                        <div class="flex items-center justify-between">
                            <flux:text class="text-xs text-zinc-500">Total paid revenue</flux:text>
                            <flux:heading size="sm" class="font-bold!">{{ format_currency($split['total']) }}</flux:heading>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 px-5">
                    <flux:icon.chart-bar class="size-8 stroke-1 mb-2 text-zinc-300" />
                    <flux:text class="text-xs text-zinc-400 text-center">No paid orders in this period</flux:text>
                </div>
            @endif
        </flux:card>

    </div>

    {{-- ================================================================== --}}
    {{-- FULFILLMENT FUNNEL + REVENUE BY ZONE                               --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        {{-- Order Fulfillment Funnel --}}
        <flux:card class="p-0">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <div>
                    <flux:heading size="sm">Order Fulfillment Funnel</flux:heading>
                    <flux:text class="text-[10px] text-zinc-400">Status distribution for orders placed in this period</flux:text>
                </div>
                @if ($this->fulfillmentFunnel['total'] > 0)
                    <flux:badge color="emerald" size="sm">
                        {{ $this->fulfillmentFunnel['success_pct'] }}% delivered
                    </flux:badge>
                @endif
            </div>

            @php
                $funnel = $this->fulfillmentFunnel;
                $funnelHex = [
                    'amber'   => '#F59E0B',
                    'blue'    => '#3B82F6',
                    'purple'  => '#A855F7',
                    'indigo'  => '#6366F1',
                    'emerald' => '#10B981',
                ];
                $funnelIcon = [
                    'pending'    => 'clock',
                    'confirmed'  => 'check-badge',
                    'processing' => 'loader-circle',
                    'shipped'    => 'truck',
                    'delivered'  => 'package-check',
                ];
            @endphp

            @if ($funnel['total'] > 0)
                <div class="p-5 space-y-4">
                    @foreach ($funnel['stages'] as $stage)
                        @php
                            $hex  = $funnelHex[$stage['color']] ?? '#94A3B8';
                            $icon = $funnelIcon[$stage['value']] ?? 'circle';
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md flex items-center justify-center shrink-0"
                                        style="background-color: {{ $hex }}20;">
                                        <flux:icon :name="$icon" class="size-3.5" style="color: {{ $hex }};" />
                                    </div>
                                    <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $stage['label'] }}
                                    </flux:text>
                                </div>
                                <div class="flex items-center gap-3">
                                    <flux:text class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                                        {{ number_format($stage['count']) }}
                                    </flux:text>
                                    <span class="text-[10px] text-zinc-400 w-10 text-right">{{ $stage['pct'] }}%</span>
                                </div>
                            </div>
                            <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all"
                                    style="width: {{ max($stage['pct'], 0.5) }}%; background-color: {{ $hex }};"></div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Lost orders --}}
                    @if ($funnel['lost'] > 0)
                        <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md bg-rose-50 dark:bg-rose-950/30 flex items-center justify-center shrink-0">
                                        <flux:icon.x-circle class="size-3.5 text-rose-500" />
                                    </div>
                                    <flux:text class="text-xs font-medium text-rose-600 dark:text-rose-400">
                                        Lost (Cancelled + Returned)
                                    </flux:text>
                                </div>
                                <div class="flex items-center gap-3">
                                    <flux:text class="text-xs font-semibold text-rose-600 dark:text-rose-400">
                                        {{ number_format($funnel['lost']) }}
                                    </flux:text>
                                    <span class="text-[10px] text-rose-400 w-10 text-right">{{ $funnel['lost_pct'] }}%</span>
                                </div>
                            </div>
                            <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2">
                                <div class="h-2 rounded-full bg-rose-400 transition-all"
                                    style="width: {{ max($funnel['lost_pct'], 0.5) }}%;"></div>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:text class="text-xs text-zinc-400">Total orders in period</flux:text>
                        <flux:heading size="sm" class="font-bold!">{{ number_format($funnel['total']) }}</flux:heading>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12">
                    <flux:icon.inbox class="size-8 stroke-1 mb-2 text-zinc-300" />
                    <flux:text class="text-xs text-zinc-400">No orders in this period</flux:text>
                </div>
            @endif
        </flux:card>

        {{-- Revenue by Shipping Zone --}}
        <flux:card class="p-0">
            <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading size="sm">Revenue by Shipping Zone</flux:heading>
                <flux:text class="text-[10px] text-zinc-400">Paid orders grouped by delivery zone</flux:text>
            </div>

            @php
                $zones = $this->revenueByZone;
                $zoneColors = ['#3B82F6', '#10B981', '#F59E0B', '#A855F7', '#F43F5E', '#14B8A6', '#6366F1', '#EC4899'];
            @endphp

            @if (!empty($zones))
                <div class="p-5 space-y-4">
                    @foreach ($zones as $i => $zone)
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300 truncate max-w-[180px]">
                                    {{ $zone['zone'] }}
                                </flux:text>
                                <div class="flex items-center gap-3 shrink-0">
                                    <flux:text class="text-[10px] text-zinc-400 whitespace-nowrap">
                                        {{ number_format($zone['orders']) }} {{ Str::plural('order', $zone['orders']) }}
                                    </flux:text>
                                    <flux:text class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 whitespace-nowrap">
                                        {{ format_currency($zone['revenue']) }}
                                    </flux:text>
                                    <span class="text-[10px] text-zinc-400 w-8 text-right">{{ $zone['pct'] }}%</span>
                                </div>
                            </div>
                            <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all"
                                    style="width: {{ max($zone['pct'], 0.5) }}%; background-color: {{ $zoneColors[$i % count($zoneColors)] }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12">
                    <flux:icon.map-pin class="size-8 stroke-1 mb-2 text-zinc-300" />
                    <flux:text class="text-xs text-zinc-400">No zone data for this period</flux:text>
                </div>
            @endif
        </flux:card>

    </div>

    {{-- ================================================================== --}}
    {{-- MONTHLY BREAKDOWN TABLE                                             --}}
    {{-- ================================================================== --}}
    <flux:card class="p-0">
        <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
            <div>
                <flux:heading>Monthly Breakdown</flux:heading>
                <flux:text class="text-[11px] text-zinc-400">Month-by-month performance — export for management reporting</flux:text>
            </div>
            <flux:button wire:click="exportCsv" icon="arrow-down-tray" variant="ghost" size="sm">
                Export CSV
            </flux:button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                        @foreach (['Month', 'Orders', 'Revenue', 'Avg Order Value', 'New Customers'] as $col)
                            <th class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                                {{ $col }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->monthlyBreakdown as $row)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                            <td class="px-5 py-3 text-xs font-medium text-zinc-800 dark:text-zinc-200 whitespace-nowrap">
                                {{ $row['month'] }}
                            </td>
                            <td class="px-5 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                                {{ number_format($row['orders']) }}
                            </td>
                            <td class="px-5 py-3 text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ format_currency($row['revenue']) }}
                            </td>
                            <td class="px-5 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                                {{ format_currency($row['aov']) }}
                            </td>
                            <td class="px-5 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                                {{ number_format($row['new_customers']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-zinc-400 text-sm">
                                No data available for this period
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if (count($this->monthlyBreakdown) > 1)
                    @php
                        $totals = [
                            'orders'        => array_sum(array_column($this->monthlyBreakdown, 'orders')),
                            'revenue'       => array_sum(array_column($this->monthlyBreakdown, 'revenue')),
                            'new_customers' => array_sum(array_column($this->monthlyBreakdown, 'new_customers')),
                        ];
                        $totals['aov'] = $totals['orders'] > 0
                            ? round($totals['revenue'] / $totals['orders'], 2)
                            : 0;
                    @endphp
                    <tfoot class="border-t-2 border-zinc-200 dark:border-zinc-700">
                        <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                            <td class="px-5 py-3 text-xs font-bold text-zinc-800 dark:text-zinc-200">Total</td>
                            <td class="px-5 py-3 text-xs font-bold text-zinc-800 dark:text-zinc-200">{{ number_format($totals['orders']) }}</td>
                            <td class="px-5 py-3 text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ format_currency($totals['revenue']) }}</td>
                            <td class="px-5 py-3 text-xs font-bold text-zinc-600 dark:text-zinc-400">{{ format_currency($totals['aov']) }}</td>
                            <td class="px-5 py-3 text-xs font-bold text-zinc-800 dark:text-zinc-200">{{ number_format($totals['new_customers']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </flux:card>

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
    const currencySymbol  = '{{ get_currency_symbol() }}';
    const isDark          = () => document.documentElement.classList.contains('dark');
    const gridColor       = () => isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
    const textColor       = () => isDark() ? '#71717a' : '#a1a1aa';

    function destroyChart(id) {
        if (chartInstances[id]) {
            chartInstances[id].destroy();
            delete chartInstances[id];
        }
        const el = document.getElementById(id);
        if (el) {
            el.removeAttribute('style');
            el.removeAttribute('width');
            el.removeAttribute('height');
        }
    }

    // -----------------------------------------------------------------------
    //  Monthly trend — bar (revenue) + line (orders), dual y-axis
    // -----------------------------------------------------------------------
    function initMonthlyChart() {
        const bridge = document.getElementById('monthlyChartBridge');
        const canvas  = document.getElementById('monthlyChart');
        if (!bridge || !canvas) return;

        destroyChart('monthlyChart');

        const labels  = JSON.parse(bridge.dataset.labels  || '[]');
        const revenue = JSON.parse(bridge.dataset.revenue || '[]');
        const orders  = JSON.parse(bridge.dataset.orders  || '[]');

        const fmtRevenue = v => {
            if (v === 0) return '0';
            if (v >= 1_000_000) return (v / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
            if (v >= 1_000)     return (v / 1_000).toFixed(1).replace(/\.0$/, '') + 'k';
            return v.toFixed(0);
        };

        chartInstances['monthlyChart'] = new Chart(canvas, {
            data: {
                labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Revenue',
                        data: revenue,
                        backgroundColor: 'rgba(16,185,129,0.65)',
                        borderColor: '#10B981',
                        borderWidth: 1,
                        borderRadius: 4,
                        yAxisID: 'yRevenue',
                        order: 2,
                    },
                    {
                        type: 'line',
                        label: 'Orders',
                        data: orders,
                        borderColor: '#8B5CF6',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#8B5CF6',
                        yAxisID: 'yCount',
                        order: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            color: textColor(),
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            font: { size: 11 },
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ctx.datasetIndex === 0
                                ? `Revenue: ${currencySymbol} ${ctx.parsed.y.toLocaleString('en', { minimumFractionDigits: 2 })}`
                                : `Orders: ${ctx.parsed.y}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { color: gridColor() },
                        ticks: { color: textColor(), font: { size: 11 } },
                    },
                    yRevenue: {
                        position: 'left',
                        grid: { color: gridColor() },
                        ticks: {
                            color: textColor(),
                            font: { size: 11 },
                            callback: v => fmtRevenue(v),
                        },
                    },
                    yCount: {
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { color: textColor(), font: { size: 11 }, stepSize: 1 },
                    },
                },
            },
        });
    }

    initMonthlyChart();

    // -----------------------------------------------------------------------
    //  Date range picker — longer presets suited for strategic reporting
    // -----------------------------------------------------------------------
    function waitForLibraries(cb) {
        if (typeof jQuery !== 'undefined' && typeof moment !== 'undefined' && typeof jQuery.fn.daterangepicker !== 'undefined') {
            cb();
        } else {
            setTimeout(() => waitForLibraries(cb), 100);
        }
    }

    function initDateRangePicker() {
        const el = $('.overview-date-range').first();
        if (!el.length) return;

        if (el.data('daterangepicker')) {
            el.data('daterangepicker').remove();
        }

        const yr    = moment().year();
        const q     = moment().quarter();
        const prevQ = q > 1 ? q - 1 : 4;
        const prevQYear = q > 1 ? yr : yr - 1;
        const qS    = (n, y) => moment().year(y).quarter(n).startOf('quarter');
        const qE    = (n, y) => moment().year(y).quarter(n).endOf('quarter');

        el.daterangepicker({
            autoUpdateInput: false,
            opens: 'left',
            showDropdowns: true,
            alwaysShowCalendars: false,
            startDate: moment($wire.dateFrom),
            endDate: moment($wire.dateTo),
            ranges: {
                'This Month':                  [moment().startOf('month'), moment().endOf('month')],
                'Last Month':                  [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                [`Q${q} ${yr}`]:               [qS(q, yr), qE(q, yr)],
                [`Q${prevQ} ${prevQYear}`]:     [qS(prevQ, prevQYear), qE(prevQ, prevQYear)],
                [`This Year (${yr})`]:          [moment().startOf('year'), moment()],
                [`Last Year (${yr - 1})`]:      [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
            },
            locale: {
                format: 'MMM DD, YYYY',
                separator: ' – ',
                cancelLabel: 'Clear',
            },
        }, function (start, end) {
            $wire.setDateRange(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
            el.val(start.format('MMM DD, YYYY') + ' – ' + end.format('MMM DD, YYYY'));
        });

        el.on('cancel.daterangepicker', function () {
            $wire.setDateRange(
                moment().startOf('year').format('YYYY-MM-DD'),
                moment().format('YYYY-MM-DD'),
            );
            el.val('');
        });

        if ($wire.dateFrom && $wire.dateTo) {
            el.val(moment($wire.dateFrom).format('MMM DD, YYYY') + ' – ' + moment($wire.dateTo).format('MMM DD, YYYY'));
        }
    }

    waitForLibraries(() => initDateRangePicker());
</script>
@endscript
