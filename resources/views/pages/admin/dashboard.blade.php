<?php

namespace App\Livewire\Admin;

use App\Enums\OrdersStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

new #[Title('Dashboard')] class extends Component {
    public string $preset = 'today';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfDay()->toDateString();
        $this->dateTo = now()->endOfDay()->toDateString();
    }

    public function setDateRange(string $preset, string $from, string $to): void
    {
        $this->preset = $preset;
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->clearComputedCache();
    }

    private function clearComputedCache(): void
    {
        unset($this->dateRange, $this->periodLabel, $this->salesStats, $this->quotationStats, $this->productStats, $this->customerStats, $this->revenueChartData, $this->topProductsChartData, $this->recentOrders, $this->recentDeliveries, $this->recentCustomers, $this->satisfactionStats, $this->categoryStats, $this->stockReport, $this->needsAttention);
    }

    #[Computed]
    public function dateRange(): array
    {
        return [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()];
    }

    #[Computed]
    public function periodLabel(): string
    {
        [$from, $to] = $this->dateRange;
        return $from->isSameDay($to) ? $from->format('M j, Y') : $from->format('M j') . ' – ' . $to->format('M j, Y');
    }

    #[Computed]
    public function needsAttention(): array
    {
        return [
            'pending_orders' => Order::where('document_type', 'sales_order')->where('status', OrdersStatus::PENDING->value)->count(),
            'pending_quotes' => Order::where('document_type', 'quotation')->where('status', OrdersStatus::PENDING_QUOTE->value)->count(),
            'expiring_quotes' => Order::where('document_type', 'quotation')
                ->where('status', OrdersStatus::QUOTE_SENT->value)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addHours(48))
                ->where('expires_at', '>', now())
                ->count(),
        ];
    }

    #[Computed]
    public function salesStats(): array
    {
        [$from, $to] = $this->dateRange;

        $base = Order::where('document_type', 'sales_order')->whereBetween('created_at', [$from, $to]);
        $revenue = (clone $base)->where('payment_status', PaymentStatus::PAID->value)->sum('total_cents') / 100;
        $count = (clone $base)->count();
        $paid = (clone $base)->where('payment_status', PaymentStatus::PAID->value)->count();

        $diff = Carbon::parse($this->dateFrom)->diffInSeconds(Carbon::parse($this->dateTo));
        $prevFrom = Carbon::parse($this->dateFrom)
            ->subSeconds($diff + 1)
            ->startOfDay();
        $prevTo = Carbon::parse($this->dateFrom)->subSecond()->endOfDay();
        $prevBase = Order::where('document_type', 'sales_order')->whereBetween('created_at', [$prevFrom, $prevTo]);
        $prevRevenue = (clone $prevBase)->where('payment_status', PaymentStatus::PAID->value)->sum('total_cents') / 100;
        $prevCount = (clone $prevBase)->count();

        return [
            'revenue' => $revenue,
            'order_count' => $count,
            'avg_order' => $count > 0 ? $revenue / $count : 0,
            'paid_count' => $paid,
            'revenue_trend' => $prevRevenue > 0 ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1) : null,
            'orders_trend' => $prevCount > 0 ? round((($count - $prevCount) / $prevCount) * 100, 1) : null,
        ];
    }

    #[Computed]
    public function quotationStats(): array
    {
        [$from, $to] = $this->dateRange;
        $base = Order::where('document_type', 'quotation')->whereBetween('created_at', [$from, $to]);
        $accepted = (clone $base)->where('status', OrdersStatus::QUOTE_ACCEPTED->value)->count();
        $rejected = (clone $base)->where('status', OrdersStatus::QUOTE_REJECTED->value)->count();
        $expired = (clone $base)->where('status', OrdersStatus::QUOTE_EXPIRED->value)->count();
        $resolved = $accepted + $rejected + $expired;

        return [
            'total' => (clone $base)->count(),
            'pending_admin' => (clone $base)->where('status', OrdersStatus::PENDING_QUOTE->value)->count(),
            'sent' => (clone $base)->where('status', OrdersStatus::QUOTE_SENT->value)->count(),
            'accepted' => $accepted,
            'conversion_rate' => $resolved > 0 ? round(($accepted / $resolved) * 100, 1) : null,
        ];
    }

    #[Computed]
    public function productStats(): array
    {
        return [
            'active' => Product::where('status', 'published')->count(),
            'low_stock' => Product::where('status', 'published')->where('manage_stock', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->where('stock_quantity', '>', 0)->count(),
            'out_of_stock' => Product::where('status', 'published')->where('manage_stock', true)->where('stock_quantity', 0)->count(),
            'requires_quote' => Product::where('status', 'published')->where('requires_quotation', true)->count(),
        ];
    }

    #[Computed]
    public function customerStats(): array
    {
        [$from, $to] = $this->dateRange;
        $total = User::customer()->count();
        $new = User::customer()
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $diff = Carbon::parse($this->dateFrom)->diffInSeconds(Carbon::parse($this->dateTo));
        $prevFrom = Carbon::parse($this->dateFrom)->subSeconds($diff + 1);
        $prevNew = User::customer()
            ->whereBetween('created_at', [$prevFrom, Carbon::parse($this->dateFrom)->subSecond()])
            ->count();

        return [
            'total' => $total,
            'new' => $new,
            'returning' => User::customer()->whereHas('orders', fn($q) => $q->where('document_type', 'sales_order'), '>=', 2)->count(),
            'new_trend' => $prevNew > 0 ? round((($new - $prevNew) / $prevNew) * 100, 1) : null,
        ];
    }

    #[Computed]
    public function revenueChartData(): array
    {
        [$from, $to] = $this->dateRange;
        $daysDiff = $from->diffInDays($to);

        if ($daysDiff < 1) {
            $groupBy = "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')";
            $phpFormat = 'H:00';
        } elseif ($daysDiff <= 60) {
            $groupBy = 'DATE(created_at)';
            $phpFormat = 'M d';
        } else {
            $groupBy = "DATE_FORMAT(created_at, '%Y-%m-01')";
            $phpFormat = 'M Y';
        }

        // Revenue (paid orders)
        $revenueRows = Order::where('document_type', 'sales_order')
            ->where('payment_status', PaymentStatus::PAID->value)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("SUM(total_cents) / 100 as revenue, {$groupBy} as period")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('revenue', 'period');

        // Order counts (all statuses)
        $orderRows = Order::where('document_type', 'sales_order')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("COUNT(*) as cnt, {$groupBy} as period")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('cnt', 'period');

        // Cancelled/failed as "refunds" proxy
        $refundRows = Order::where('document_type', 'sales_order')
            ->whereIn('status', [OrdersStatus::CANCELLED->value, OrdersStatus::RETURNED->value])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("COUNT(*) as cnt, {$groupBy} as period")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('cnt', 'period');

        // Union all periods so every series has the same x-axis labels
        $allPeriods = collect($revenueRows->keys())->merge($orderRows->keys())->merge($refundRows->keys())->unique()->sort()->values();

        $labels = $allPeriods->map(fn($p) => Carbon::parse($p)->format($phpFormat))->toArray();
        $revenueVals = $allPeriods->map(fn($p) => round((float) ($revenueRows[$p] ?? 0), 2))->toArray();
        $orderVals = $allPeriods->map(fn($p) => (int) ($orderRows[$p] ?? 0))->toArray();
        $refundVals = $allPeriods->map(fn($p) => (int) ($refundRows[$p] ?? 0))->toArray();

        return [
            'labels' => $labels,
            'values' => $revenueVals, // kept for backwards compat
            'order_counts' => $orderVals,
            'refund_counts' => $refundVals,
        ];
    }

    #[Computed]
    public function satisfactionStats(): array
    {
        $thisStart = now()->startOfMonth();
        $lastStart = now()->subMonth()->startOfMonth();
        $lastEnd = now()->subMonth()->endOfMonth();

        $query = fn($from, $to) => Order::where('document_type', 'sales_order')
            ->where('payment_status', PaymentStatus::PAID->value)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, SUM(total_cents) / 100 as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('revenue', 'day');

        $thisRows = $query($thisStart, now()->endOfDay());
        $lastRows = $query($lastStart, $lastEnd);
        $thisDays = $thisStart->daysInMonth();
        $lastDays = $lastStart->daysInMonth();
        $thisSeries = [];
        $lastSeries = [];

        for ($d = 1; $d <= max($thisDays, $lastDays); $d++) {
            if ($d <= $thisDays) {
                $thisSeries[] = round((float) ($thisRows[$thisStart->copy()->setDay($d)->toDateString()] ?? 0), 2);
            }
            if ($d <= $lastDays) {
                $lastSeries[] = round((float) ($lastRows[$lastStart->copy()->setDay($d)->toDateString()] ?? 0), 2);
            }
        }

        return [
            'this_month' => round(array_sum($thisSeries), 2),
            'last_month' => round(array_sum($lastSeries), 2),
            'this_series' => $thisSeries,
            'last_series' => $lastSeries,
            'days_this_month' => $thisDays,
            'month_label' => $thisStart->format('M Y'),
            'last_month_label' => $lastStart->format('M Y'),
        ];
    }

    #[Computed]
    public function categoryStats(): array
    {
        [$from, $to] = $this->dateRange;

        $rows = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('category_product', 'order_items.product_id', '=', 'category_product.product_id')
            ->join('categories', 'category_product.category_id', '=', 'categories.id')
            ->where('orders.document_type', 'sales_order')
            ->where('orders.payment_status', PaymentStatus::PAID->value)
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotNull('order_items.product_id')
            ->where(function ($q) {
                $q->where('category_product.is_primary', true)->orWhereNotExists(function ($sub) {
                    $sub->from('category_product as cp2')->whereColumn('cp2.product_id', 'order_items.product_id')->where('cp2.is_primary', true);
                });
            })
            ->selectRaw(
                'categories.id, categories.name as category, SUM(order_items.quantity) as units,
        SUM(order_items.total_cents) / 100 as revenue',
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('units')
            ->limit(4)
            ->get();

        $total = (int) $rows->sum('units');

        return [
            'total' => $total,
            'categories' => $rows
                ->map(
                    fn($r) => [
                        'name' => $r->category,
                        'units' => (int) $r->units,
                        'revenue' => round((float) $r->revenue, 2),
                        'pct' => $total > 0 ? round(($r->units / $total) * 100) : 0,
                    ],
                )
                ->toArray(),
        ];
    }

    #[Computed]
    public function stockReport()
    {
        return Product::where('status', 'published')
            ->where('manage_stock', true)
            ->orderByRaw(
                'CASE WHEN stock_quantity
        = 0 THEN 0 WHEN stock_quantity <= low_stock_threshold THEN 1 ELSE 2 END',
            )
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function topProductsChartData(): array
    {
        [$from, $to] = $this->dateRange;

        $rows = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.document_type', 'sales_order')
            ->where('orders.payment_status', PaymentStatus::PAID->value)
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotNull('order_items.product_id')
            ->selectRaw(
                'order_items.product_id, JSON_UNQUOTE(JSON_EXTRACT(order_items.product_snapshot, "$.name")) as
            product_name, SUM(order_items.quantity) as units_sold, SUM(order_items.total_cents) / 100 as revenue',
            )
            ->groupBy('order_items.product_id', 'product_name')
            ->orderByDesc('units_sold')
            ->limit(6)
            ->get();

        $max = (int) ($rows->first()?->units_sold ?? 1);

        return [
            'items' => $rows
                ->map(
                    fn($r) => [
                        'name' => $r->product_name ?? 'Unknown',
                        'units' => (int) $r->units_sold,
                        'revenue' => round((float) $r->revenue, 2),
                        'pct' => $max > 0 ? round(($r->units_sold / $max) * 100) : 0,
                    ],
                )
                ->toArray(),
        ];
    }

    #[Computed]
    public function recentOrders()
    {
        return Order::where('document_type', 'sales_order')
            ->with(['user', 'payment'])
            ->withCount('items')
            ->latest()
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function recentDeliveries()
    {
        return Order::where('document_type', 'sales_order')
            ->whereIn('status', [OrdersStatus::SHIPPED->value, OrdersStatus::DELIVERED->value, OrdersStatus::PROCESSING->value, OrdersStatus::CONFIRMED->value])
            ->with(['user', 'items.product'])
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function recentCustomers()
    {
        return User::customer()->withCount('orders')->latest()->limit(5)->get();
    }
};
?>

<div>

    {{-- ================================================================== --}}
    {{-- NEEDS ATTENTION / PAGE HEADER                                       --}}
    {{-- ================================================================== --}}
    @if (
        $this->needsAttention['pending_orders'] > 0 ||
            $this->needsAttention['pending_quotes'] > 0 ||
            $this->needsAttention['expiring_quotes'] > 0)
        <div
            class="flex flex-wrap items-center gap-2 px-4 py-2.5 mb-5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl">
            <flux:icon.exclamation-triangle class="size-4 text-amber-500 shrink-0" />
            <span class="text-xs font-semibold text-amber-800 dark:text-amber-300">Needs attention</span>
            @if ($this->needsAttention['pending_orders'] > 0)
                <a href="{{ route('admin.orders.index') }}" wire:navigate
                    class="text-xs px-2.5 py-1 bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300 rounded-full hover:bg-amber-200 dark:hover:bg-amber-900 transition-colors font-medium">
                    {{ $this->needsAttention['pending_orders'] }} pending
                    {{ Str::plural('order', $this->needsAttention['pending_orders']) }}
                </a>
            @endif
            @if ($this->needsAttention['pending_quotes'] > 0)
                <a href="{{ route('admin.orders.index') }}" wire:navigate
                    class="text-xs px-2.5 py-1 bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300 rounded-full hover:bg-amber-200 dark:hover:bg-amber-900 transition-colors font-medium">
                    {{ $this->needsAttention['pending_quotes'] }}
                    {{ Str::plural('quote', $this->needsAttention['pending_quotes']) }} to price
                </a>
            @endif
            @if ($this->needsAttention['expiring_quotes'] > 0)
                <a href="{{ route('admin.orders.index') }}" wire:navigate
                    class="text-xs px-2.5 py-1 bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300 rounded-full hover:bg-rose-200 dark:hover:bg-rose-900 transition-colors font-medium">
                    {{ $this->needsAttention['expiring_quotes'] }} expiring soon
                </a>
            @endif
            <div class="ml-auto flex items-center gap-2">
                <div class="relative">
                    <input id="dashboardDateRange" type="text" readonly
                        class="w-64 pl-8 pr-3 py-1.5 text-xs border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-300 hover:border-zinc-400 transition-colors" />
                    <flux:icon.calendar-days
                        class="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" />
                </div>
                <div wire:loading wire:target="setDateRange" class="flex items-center gap-1 text-xs text-zinc-400">
                    <div class="w-3 h-3 border-2 border-zinc-300 border-t-zinc-500 rounded-full animate-spin">
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="flex items-center justify-between mb-5">
            <div>
                <flux:heading size="xl" class="font-bold tracking-tight">Dashboard</flux:heading>
                <flux:subheading>{{ $this->periodLabel }}</flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <input id="dashboardDateRange" type="text" readonly
                        class="w-64 pl-8 pr-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-300 hover:border-zinc-400 transition-colors" />
                    <flux:icon.calendar-days
                        class="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" />
                </div>
                <div wire:loading wire:target="setDateRange" class="flex items-center gap-1.5 text-xs text-zinc-400">
                    <div class="w-3.5 h-3.5 border-2 border-zinc-300 border-t-zinc-600 rounded-full animate-spin">
                    </div>
                    Updating...
                </div>
            </div>
        </div>
    @endif

    {{-- ================================================================== --}}
    {{-- ROW 1: KPI CARDS                                                    --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        <flux:card class="p-4">
            <div class="flex items-start justify-between mb-3">
                <flux:text class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">
                    Total revenue</flux:text>
                <div
                    class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center shrink-0">
                    <flux:icon.banknotes class="size-4 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>
            <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5">
                {{ format_currency($this->salesStats['revenue']) }}</flux:heading>
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
            <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5">
                {{ number_format($this->salesStats['order_count']) }}</flux:heading>
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
            <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5">
                {{ number_format($this->customerStats['total']) }}</flux:heading>
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
            <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5">
                {{ number_format($this->productStats['active']) }}</flux:heading>
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        {{-- Revenue chart — spans 2 cols --}}
        <flux:card class="p-0 lg:col-span-2 h-full flex flex-col ">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading>Revenue</flux:heading>
                <div class="flex items-center gap-1">
                    @foreach (['today' => 'Today', 'last_7_days' => '7d', 'this_month' => '1M', 'last_6_months' => '6M', 'this_year' => '1Y'] as $key => $label)
                        <button
                            wire:click="setDateRange('{{ $key }}', '{{ match ($key) {'today' => now()->startOfDay()->toDateString(),'last_7_days' => now()->subDays(6)->toDateString(),'this_month' => now()->startOfMonth()->toDateString(),'last_6_months' => now()->subMonths(5)->startOfMonth()->toDateString(),'this_year' => now()->startOfYear()->toDateString()} }}', '{{ now()->toDateString() }}')"
                            class="text-xs px-3 py-1 rounded-full transition-colors {{ $preset === $key ? 'bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 font-medium' : 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- flex instead of grid so the chart area truly fills remaining space --}}
            <div class="flex flex-col md:flex-row min-h-0 flex-1">

                {{-- Chart — flex-1 so it fills all space the sidebar doesn't take --}}
                <div
                    class="flex-1 min-w-0 px-5 pt-4 pb-5 border-b md:border-b-0 md:border-r border-zinc-100 dark:border-zinc-800">
                    <div id="revenueChartData" data-labels="{{ json_encode($this->revenueChartData['labels']) }}"
                        data-revenue="{{ json_encode($this->revenueChartData['values']) }}"
                        data-orders="{{ json_encode($this->revenueChartData['order_counts']) }}"
                        data-refunds="{{ json_encode($this->revenueChartData['refund_counts']) }}">
                    </div>
                    <div wire:ignore style="position:relative; height:100%; width:100%;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                {{-- Right sidebar — fixed width, each stat in its own bordered row --}}
                <div class="w-full md:w-48 shrink-0 flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">

                    <div class="px-5 py-4">
                        <p class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest mb-1.5">Orders</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 leading-none">
                            {{ number_format($this->salesStats['order_count']) }}
                        </p>
                    </div>

                    <div class="px-5 py-4">
                        <p class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest mb-1.5">Earnings</p>
                        <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100 leading-none break-all">
                            {{ format_currency($this->salesStats['revenue']) }}
                        </p>
                    </div>

                    <div class="px-5 py-4">
                        <p class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest mb-1.5">Paid orders
                        </p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 leading-none">
                            {{ number_format($this->salesStats['paid_count']) }}
                        </p>
                    </div>

                    <div class="px-5 py-4">
                        <p class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest mb-1.5">Conversion
                        </p>
                        @if ($this->quotationStats['conversion_rate'] !== null)
                            <p class="text-2xl font-bold text-emerald-500 leading-none">
                                {{ $this->quotationStats['conversion_rate'] }}%
                            </p>
                        @else
                            <p class="text-2xl font-bold text-zinc-300 dark:text-zinc-600 leading-none">—</p>
                        @endif
                    </div>

                    {{-- Only render when something genuinely needs action --}}
                    @if ($this->needsAttention['pending_orders'] > 0 || $this->needsAttention['expiring_quotes'] > 0)
                        <div class="px-5 py-4 bg-sky-50 dark:bg-sky-950/40 flex-1">
                            <p class="text-xs font-semibold text-sky-900 dark:text-sky-200 mb-1">
                                @if ($this->needsAttention['pending_orders'] > 0)
                                    {{ $this->needsAttention['pending_orders'] }}
                                    {{ Str::plural('order', $this->needsAttention['pending_orders']) }} pending
                                @else
                                    {{ $this->needsAttention['expiring_quotes'] }}
                                    {{ Str::plural('quote', $this->needsAttention['expiring_quotes']) }} expiring soon
                                @endif
                            </p>
                            <p class="text-[11px] text-sky-700 dark:text-sky-300 mb-3 leading-relaxed">
                                @if ($this->needsAttention['pending_orders'] > 0)
                                    Review and confirm pending orders to keep fulfilment on track.
                                @else
                                    Follow up with customers before their quotes expire.
                                @endif
                            </p>
                            <a href="{{ route('admin.orders.index') }}" wire:navigate
                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1.5 rounded-lg bg-sky-500 text-white hover:bg-sky-600 transition-colors">
                                See orders <flux:icon.arrow-right class="size-3" />
                            </a>
                        </div>
                    @endif

                </div>
            </div>
        </flux:card>


        {{-- Top Sales Location --}}
        <flux:card class="p-0 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-100 dark:border-zinc-800">
                <div>
                    <flux:heading size="sm">Top Sales Locations</flux:heading>
                    <flux:text class="text-[10px] text-zinc-400">Distribution by city</flux:text>
                </div>
                <flux:button variant="ghost" size="sm" class="text-blue-500 text-xs!">View Report</flux:button>
            </div>
            @php
                $locations = [
                    ['name' => 'Nairobi', 'count' => 234, 'color' => 'bg-emerald-500'],
                    ['name' => 'Mombasa', 'count' => 98, 'color' => 'bg-blue-500'],
                    ['name' => 'Kisumu', 'count' => 67, 'color' => 'bg-amber-500'],
                    ['name' => 'Nakuru', 'count' => 41, 'color' => 'bg-violet-500'],
                    ['name' => 'Eldoret', 'count' => 28, 'color' => 'bg-rose-500'],
                ];
                $maxCount = $locations[0]['count'];
            @endphp


            <div class="px-4 py-4">
                {{-- Visual Area: Simplified Geo-Context --}}
                <div
                    class="relative flex items-center justify-center h-32 overflow-hidden rounded-xl bg-zinc-50 dark:bg-white/5">
                    {{-- Abstract Map Suggestion (Replaces the Ellipses) --}}
                    <svg viewBox="0 0 200 100"
                        class="absolute inset-0 w-full h-full opacity-10 dark:opacity-20 stroke-current text-zinc-400">
                        <path d="M40,20 Q60,10 80,30 T120,20 T160,40" fill="none" stroke-width="0.5" />
                        <circle cx="150" cy="60" r="40" fill="none" stroke-dasharray="2 2" />
                    </svg>

                    <div class="relative flex gap-6">
                        @foreach (array_slice($locations, 0, 3) as $loc)
                            <div class="flex flex-col items-center">
                                <div class="relative">
                                    <div class="w-3 h-3 rounded-full {{ $loc['color'] }} shadow-lg shadow-current/20">
                                    </div>
                                    <div
                                        class="absolute inset-0 w-3 h-3 rounded-full {{ $loc['color'] }} animate-ping opacity-20">
                                    </div>
                                </div>
                                <span class="mt-2 text-[10px] font-medium text-zinc-500">{{ $loc['name'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="px-4 pb-4 space-y-3">

                @foreach ($locations as $loc)
                    <div class="group">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full {{ $loc['color'] }}"></div>
                                <flux:text class="text-xs font-medium">{{ $loc['name'] }}</flux:text>
                            </div>
                            <flux:text class="text-xs text-zinc-400">{{ $loc['count'] }}</flux:text>
                        </div>
                        {{-- Full width progress bar for better visual comparison --}}
                        <div class="w-full h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                            <div class="{{ $loc['color'] }} h-full rounded-full transition-all duration-500"
                                style="width: {{ ($loc['count'] / $maxCount) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="px-5 py-3 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-white/5">
                <div class="flex items-center justify-between">
                    <flux:text class="text-[10px] uppercase tracking-wider font-semibold text-zinc-400">Total Volume
                    </flux:text>
                    <flux:text class="text-xs font-bold">{{ array_sum(array_column($locations, 'count')) }}
                    </flux:text>
                </div>
            </div>
        </flux:card>
    </div>


    {{-- ================================================================== --}}
    {{-- ROW 3: RECENT ORDERS TABLE                                          --}}
    {{-- ================================================================== --}}
    <flux:card class="p-0 mb-4">
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
                            class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                            Items</th>
                        <th
                            class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                            Amount</th>
                        <th
                            class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                            Date</th>
                        <th
                            class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest whitespace-nowrap">
                            Payment</th>
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
                                    class="text-blue-600 dark:text-blue-400 font-medium text-xs hover:underline">{{ $order->reference }}</a>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-7 h-7 rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center text-[10px] font-semibold text-zinc-500 shrink-0">
                                        {{ strtoupper(substr($order->user?->name ?? '?', 0, 2)) }}
                                    </div>
                                    <span
                                        class="text-xs text-zinc-800 dark:text-zinc-200">{{ $order->user?->name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-xs text-zinc-500">{{ $order->items_count }}</td>
                            <td class="px-5 py-3 text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ format_currency($order->total) }}</td>
                            <td class="px-5 py-3 text-xs text-zinc-400 whitespace-nowrap">
                                {{ $order->created_at->diffForHumans() }}</td>
                            <td class="px-5 py-3">
                                @php
                                    $pStatus = $order->payment_status->value;
                                    $pColor = match ($pStatus) {
                                        'paid'
                                            => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400',
                                        'failed' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400',
                                        'processing'
                                            => 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400',
                                        default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                                    };
                                @endphp
                                <span
                                    class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $pColor }}">{{ ucfirst($pStatus) }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <flux:badge size="sm" :color="$order->status->color()">
                                    {{ $order->status->label() }}</flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-zinc-400 text-sm">No
                                orders yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>

    {{-- ================================================================== --}}
    {{-- ROW 4: CUSTOMER SATISFACTION + STOCK REPORT                        --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        <flux:card class="p-0 flex flex-col">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading>Customer satisfaction</flux:heading>
                <flux:text class="text-xs text-blue-500 cursor-pointer hover:underline">Report</flux:text>
            </div>

            <div class="p-4 flex flex-col flex-1">
                {{-- Data bridge — morphed by Livewire, read by JS --}}
                <div id="satisfactionChartData"
                    data-this-series="{{ json_encode($this->satisfactionStats['this_series']) }}"
                    data-last-series="{{ json_encode($this->satisfactionStats['last_series']) }}"
                    data-days="{{ $this->satisfactionStats['days_this_month'] }}">
                </div>
                {{-- Canvas — fixed height, owned by Chart.js --}}
                <div class="flex-1">
                    <div wire:ignore style="position:relative; height:100%; width:100%;">
                        <canvas id="satisfactionChart"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 mt-4">
                    <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/60 p-3">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-1">
                            {{ format_currency($this->satisfactionStats['this_month']) }}
                        </p>
                        <p class="flex items-center gap-1.5 text-[10px] text-zinc-400">
                            <flux:icon.arrow-path class="size-3 text-blue-500 shrink-0" />
                            {{ $this->satisfactionStats['month_label'] }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/60 p-3">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-1">
                            {{ format_currency($this->satisfactionStats['last_month']) }}
                        </p>
                        <p class="flex items-center gap-1.5 text-[10px] text-zinc-400">
                            <flux:icon.arrow-path class="size-3 text-emerald-500 shrink-0" />
                            {{ $this->satisfactionStats['last_month_label'] }}
                        </p>
                    </div>
                </div>
            </div>
        </flux:card>

        <flux:card class="p-0 lg:col-span-2">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading>Stock report</flux:heading>
                <a href="{{ route('admin.catalog.products.index') }}" wire:navigate
                    class="text-xs px-3 py-1 rounded-full border border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                    Manage stock
                </a>
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
                                Price</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">
                                Stock status</th>
                            <th
                                class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">
                                Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($this->stockReport as $product)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.catalog.products.edit', $product) }}" wire:navigate
                                        class="text-xs font-medium text-zinc-800 dark:text-zinc-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                        {{ Str::limit($product->name, 35) }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-xs text-zinc-400 font-mono">
                                    {{ $product->sku ?? '—' }}</td>
                                <td class="px-5 py-3 text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ format_currency($product->price) }}</td>
                                <td class="px-5 py-3">
                                    @if ($product->stock_quantity === 0)
                                        <span
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400">Out
                                            of stock</span>
                                    @elseif ($product->stock_quantity <= $product->low_stock_threshold)
                                        <span
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400">Low
                                            stock</span>
                                    @else
                                        <span
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">In
                                            stock</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $product->stock_quantity }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-zinc-400 text-sm">No
                                    products
                                    with stock management enabled</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>

    </div>

    {{-- ================================================================== --}}
    {{-- ROW 5: DELIVERIES · TOP CATEGORIES · NEW CUSTOMERS · TOP PRODUCTS  --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

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
                <flux:text class="text-xs text-zinc-400 cursor-pointer hover:text-zinc-600">Report ▾</flux:text>
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
                    <div class="w-full grid grid-cols-2 gap-2">
                        @foreach ($cats as $i => $cat)
                            <div class="rounded-xl border border-zinc-100 dark:border-zinc-800 p-3 text-center">
                                <p class="text-base font-bold text-zinc-900 dark:text-zinc-100 mb-1">
                                    {{ number_format($cat['units']) }}
                                </p>
                                <p
                                    class="flex items-center justify-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    <span class="inline-block w-2.5 h-2.5 rounded-sm flex-shrink-0"
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
                <flux:text class="text-xs text-blue-500 cursor-pointer hover:underline">Report</flux:text>
            </div>
            <div class="p-4 flex flex-col gap-3">
                @php $prodColors = ['#10B981', '#3B82F6', '#F59E0B', '#8B5CF6', '#F43F5E', '#06B6D4']; @endphp
                @forelse ($this->topProductsChartData['items'] as $i => $item)
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs text-zinc-700 dark:text-zinc-300 truncate flex-1 min-w-0 pr-2">{{ Str::limit($item['name'], 22) }}</span>
                            <span
                                class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 shrink-0">{{ $item['pct'] }}%</span>
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
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1.0/daterangepicker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1.0/daterangepicker.min.js"></script>
@endassets

@script
    <script>
        const chartInstances = {};
        const isDark = () => document.documentElement.classList.contains('dark');
        const gridColor = () => isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
        const textColor = () => isDark() ? '#71717a' : '#a1a1aa';

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

            // Smart y-axis number formatter — no currency symbol, just clean numbers
            const fmtRevenue = v => {
                if (v === 0) return '0';
                if (v >= 1_000_000) return (v / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
                if (v >= 1_000) return (v / 1_000).toFixed(1).replace(/\.0$/, '') + 'k';
                return v.toFixed(0);
            };

            chartInstances['revenueChart'] = new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                            label: 'Earnings',
                            data: revenue,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16,185,129,0.06)',
                            borderWidth: 2.5,
                            fill: false,
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
                    ],
                },
                options: {
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
                                        return `  ${ctx.dataset.label}: KES ${ctx.parsed.y.toLocaleString('en-KE', { minimumFractionDigits: 2 })}`;
                                    }
                                    return `  ${ctx.dataset.label}: ${ctx.parsed.y}`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            display: true, // ← was missing / getting hidden
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
                                maxRotation: 0, // keep labels horizontal like the reference
                                autoSkip: true,
                                maxTicksLimit: 12, // enough labels without crowding
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
                                callback: v => fmtRevenue(v), // ← clean: 0 / 500 / 2.5k / 1M
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
                },
            });
        }

        // -----------------------------------------------------------------------
        //  Satisfaction — reads from #satisfactionChartData bridge
        // -----------------------------------------------------------------------
        function initSatisfactionChart() {
            const bridge = document.getElementById('satisfactionChartData');
            const canvas = document.getElementById('satisfactionChart');
            if (!bridge || !canvas) return;

            destroyChart('satisfactionChart');

            const days = parseInt(bridge.dataset.days || '30');
            const labels = Array.from({
                length: days
            }, (_, i) => i + 1);

            chartInstances['satisfactionChart'] = new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                            label: 'This month',
                            data: JSON.parse(bridge.dataset.thisSeries || '[]'),
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59,130,246,0.10)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#3B82F6',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#ffffff',
                            pointHoverBorderColor: '#3B82F6',
                            pointHoverBorderWidth: 2,
                        },
                        {
                            label: 'Last month',
                            data: JSON.parse(bridge.dataset.lastSeries || '[]'),
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16,185,129,0.10)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#10B981',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#ffffff',
                            pointHoverBorderColor: '#10B981',
                            pointHoverBorderWidth: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
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
                                title: ctx => `Day ${ctx[0].label}`,
                                label: ctx =>
                                    `  ${ctx.dataset.label}: KES ${ctx.parsed.y.toLocaleString('en-KE', { minimumFractionDigits: 2 })}`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            display: false
                        }, // ← no x-axis at all
                        y: {
                            display: false
                        }, // ← no y-axis at all
                    },
                },
            });
        }

        function initAllCharts() {
            initRevenueChart();
            initSatisfactionChart();
        }

        function initDateRangePicker() {
            const el = $('#dashboardDateRange');
            if (!el.length || typeof $.fn.daterangepicker === 'undefined') return;
            if (el.data('daterangepicker')) el.data('daterangepicker').remove();
            el.daterangepicker({
                startDate: moment($wire.dateFrom),
                endDate: moment($wire.dateTo),
                opens: 'left',
                showDropdowns: true,
                alwaysShowCalendars: true,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'This Week': [moment().startOf('isoWeek'), moment().endOf('isoWeek')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month')
                        .endOf('month')
                    ],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                    'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf(
                        'year')],
                },
                locale: {
                    format: 'MMM DD, YYYY',
                    separator: ' – '
                },
            }, function(start, end, label) {
                const preset = label === 'Custom Range' ? 'custom' : label.toLowerCase().replace(/\s+/g, '_');
                $wire.setDateRange(preset, start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
            });
        }

        // Boot
        initAllCharts();
        initDateRangePicker();

        // Livewire 4 — fires once per component after full DOM morph
        $wire.interceptMessage(({
            onSuccess
        }) => {
            onSuccess(({
                onMorph
            }) => {
                onMorph(async () => {
                    initAllCharts();
                    const picker = $('#dashboardDateRange').data('daterangepicker');
                    if (picker) {
                        picker.setStartDate(moment($wire.dateFrom));
                        picker.setEndDate(moment($wire.dateTo));
                    }
                });
            });
        });
    </script>
@endscript
