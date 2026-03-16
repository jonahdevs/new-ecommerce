<?php

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
    // =========================================================================
    //  DATE FILTER STATE
    //
    //  $period drives all computed metrics and charts.
    //  Switching period clears all computed caches so data recalculates.
    //
    //  Periods: today | this_month | last_month | this_year | last_year | custom
    //  Custom:  $dateFrom and $dateTo are used when period = 'custom'
    // =========================================================================

    public string $period = 'today';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function updatedPeriod(): void
    {
        $this->clearComputedCache();
    }

    public function updatedDateFrom(): void
    {
        if ($this->period === 'custom') {
            $this->clearComputedCache();
        }
    }

    public function updatedDateTo(): void
    {
        if ($this->period === 'custom') {
            $this->clearComputedCache();
        }
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;

        // Set sensible defaults for custom range
        if ($period === 'custom' && !$this->dateFrom) {
            $this->dateFrom = now()->startOfMonth()->toDateString();
            $this->dateTo = now()->toDateString();
        }

        $this->clearComputedCache();
    }

    private function clearComputedCache(): void
    {
        unset($this->dateRange, $this->salesStats, $this->quotationStats, $this->productStats, $this->customerStats, $this->revenueChartData, $this->orderStatusChartData, $this->topProductsChartData, $this->volumeComparisonChartData, $this->recentOrders, $this->recentQuotations);
    }

    // =========================================================================
    //  DATE RANGE RESOLVER
    //
    //  Returns [Carbon $from, Carbon $to] for the active period.
    //  All computed properties use this as their base filter.
    // =========================================================================

    #[Computed]
    public function dateRange(): array
    {
        return match ($this->period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'last_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            'custom' => [$this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : now()->startOfMonth(), $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : now()->endOfDay()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    // =========================================================================
    //  PERIOD LABEL — shown in chart subtitles
    // =========================================================================

    #[Computed]
    public function periodLabel(): string
    {
        [$from, $to] = $this->dateRange;

        return match ($this->period) {
            'today' => 'Today, ' . $from->format('M j, Y'),
            'this_month' => $from->format('F Y'),
            'last_month' => $from->format('F Y'),
            'this_year' => 'Year ' . $from->format('Y'),
            'last_year' => 'Year ' . $from->format('Y'),
            'custom' => $from->format('M j') . ' – ' . $to->format('M j, Y'),
            default => '',
        };
    }

    // =========================================================================
    //  SALES STATS
    // =========================================================================

    #[Computed]
    public function salesStats(): array
    {
        [$from, $to] = $this->dateRange;

        $base = Order::where('document_type', 'sales_order')->whereBetween('created_at', [$from, $to]);

        $revenue = (clone $base)->where('payment_status', PaymentStatus::PAID->value)->sum('total_cents') / 100;
        $orderCount = (clone $base)->count();
        $avgOrder = $orderCount > 0 ? $revenue / $orderCount : 0;
        $paid = (clone $base)->where('payment_status', PaymentStatus::PAID->value)->count();

        return [
            'revenue' => $revenue,
            'order_count' => $orderCount,
            'avg_order' => $avgOrder,
            'paid_count' => $paid,
        ];
    }

    // =========================================================================
    //  QUOTATION STATS
    // =========================================================================

    #[Computed]
    public function quotationStats(): array
    {
        [$from, $to] = $this->dateRange;

        $base = Order::where('document_type', 'quotation')->whereBetween('created_at', [$from, $to]);

        $total = (clone $base)->count();
        $pendingAdmin = (clone $base)->where('status', OrdersStatus::PENDING_QUOTE->value)->count();
        $sent = (clone $base)->where('status', OrdersStatus::QUOTE_SENT->value)->count();
        $accepted = (clone $base)->where('status', OrdersStatus::QUOTE_ACCEPTED->value)->count();
        $rejected = (clone $base)->where('status', OrdersStatus::QUOTE_REJECTED->value)->count();
        $expired = (clone $base)->where('status', OrdersStatus::QUOTE_EXPIRED->value)->count();

        // Conversion rate: accepted / (accepted + rejected + expired) — excludes still-open
        $resolved = $accepted + $rejected + $expired;
        $conversionRate = $resolved > 0 ? round(($accepted / $resolved) * 100, 1) : null;

        return [
            'total' => $total,
            'pending_admin' => $pendingAdmin,
            'sent' => $sent,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'expired' => $expired,
            'conversion_rate' => $conversionRate,
        ];
    }

    // =========================================================================
    //  PRODUCT STATS
    //  Not date-filtered — reflects current catalogue state
    // =========================================================================

    #[Computed]
    public function productStats(): array
    {
        $active = Product::where('status', 'published')->count();
        $lowStock = Product::where('status', 'published')->where('manage_stock', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->where('stock_quantity', '>', 0)->count();
        $outOfStock = Product::where('status', 'published')->where('manage_stock', true)->where('stock_quantity', 0)->count();
        $requiresQuote = Product::where('status', 'published')->where('requires_quotation', true)->count();

        return [
            'active' => $active,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'requires_quote' => $requiresQuote,
        ];
    }

    // =========================================================================
    //  CUSTOMER STATS
    // =========================================================================

    #[Computed]
    public function customerStats(): array
    {
        [$from, $to] = $this->dateRange;

        $total = User::customer()->count();
        $newInPeriod = User::customer()
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // Returning: placed more than one order (all time)
        $returning = User::customer()->whereHas('orders', fn($q) => $q->where('document_type', 'sales_order'), '>=', 2)->count();

        return [
            'total' => $total,
            'new' => $newInPeriod,
            'returning' => $returning,
        ];
    }

    // =========================================================================
    //  NEEDS ATTENTION — always current, not date-filtered
    // =========================================================================

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

    // =========================================================================
    //  CHART: Revenue over time (line)
    //
    //  Granularity adapts to the period:
    //    today / custom (≤7 days) → hourly
    //    this_month / last_month  → daily
    //    this_year / last_year    → monthly
    //    custom (> 7 days)        → daily
    // =========================================================================

    #[Computed]
    public function revenueChartData(): array
    {
        [$from, $to] = $this->dateRange;

        $daysDiff = $from->diffInDays($to);

        // Choose grouping format
        if ($this->period === 'today') {
            $format = '%H:00';
            $phpFormat = 'H:00';
            $groupBy = DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')");
        } elseif ($daysDiff <= 31) {
            $format = '%b %d';
            $phpFormat = 'M d';
            $groupBy = DB::raw('DATE(created_at)');
        } else {
            $format = '%b %Y';
            $phpFormat = 'M Y';
            $groupBy = DB::raw("DATE_FORMAT(created_at, '%Y-%m-01')");
        }

        $rows = Order::where('document_type', 'sales_order')
            ->where('payment_status', PaymentStatus::PAID->value)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("SUM(total_cents) / 100 as revenue, {$groupBy->getValue(DB::connection()->getQueryGrammar())} as period")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'labels' => $rows->map(fn($r) => Carbon::parse($r->period)->format($phpFormat))->toArray(),
            'values' => $rows->pluck('revenue')->map(fn($v) => round((float) $v, 2))->toArray(),
        ];
    }

    // =========================================================================
    //  CHART: Orders by status (donut)
    // =========================================================================

    #[Computed]
    public function orderStatusChartData(): array
    {
        [$from, $to] = $this->dateRange;

        $counts = Order::where('document_type', 'sales_order')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statuses = [OrdersStatus::PENDING, OrdersStatus::CONFIRMED, OrdersStatus::PROCESSING, OrdersStatus::SHIPPED, OrdersStatus::DELIVERED, OrdersStatus::CANCELLED, OrdersStatus::RETURNED];

        $colors = [
            'pending' => '#F59E0B',
            'confirmed' => '#3B82F6',
            'processing' => '#8B5CF6',
            'shipped' => '#6366F1',
            'delivered' => '#10B981',
            'cancelled' => '#F43F5E',
            'returned' => '#F97316',
        ];

        $labels = [];
        $values = [];
        $bgs = [];

        foreach ($statuses as $status) {
            $count = $counts[$status->value] ?? 0;
            if ($count > 0) {
                $labels[] = $status->label();
                $values[] = $count;
                $bgs[] = $colors[$status->value] ?? '#94A3B8';
            }
        }

        return ['labels' => $labels, 'values' => $values, 'colors' => $bgs];
    }

    // =========================================================================
    //  CHART: Top selling products (horizontal bar)
    // =========================================================================

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
                '
                order_items.product_id,
                JSON_UNQUOTE(JSON_EXTRACT(order_items.product_snapshot, "$.name")) as product_name,
                SUM(order_items.quantity) as units_sold,
                SUM(order_items.total_cents) / 100 as revenue
            ',
            )
            ->groupBy('order_items.product_id', 'product_name')
            ->orderByDesc('units_sold')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->map(fn($r) => $r->product_name ?? 'Unknown')->toArray(),
            'units' => $rows->pluck('units_sold')->map(fn($v) => (int) $v)->toArray(),
            'revenue' => $rows->pluck('revenue')->map(fn($v) => round((float) $v, 2))->toArray(),
        ];
    }

    // =========================================================================
    //  CHART: Order volume vs quotations (grouped bar)
    // =========================================================================

    #[Computed]
    public function volumeComparisonChartData(): array
    {
        [$from, $to] = $this->dateRange;

        $daysDiff = $from->diffInDays($to);

        if ($this->period === 'today') {
            $groupBy = DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')");
            $phpFormat = 'H:00';
        } elseif ($daysDiff <= 31) {
            $groupBy = DB::raw('DATE(created_at)');
            $phpFormat = 'M d';
        } else {
            $groupBy = DB::raw("DATE_FORMAT(created_at, '%Y-%m-01')");
            $phpFormat = 'M Y';
        }

        $groupByStr = $groupBy->getValue(DB::connection()->getQueryGrammar());

        $orders = Order::where('document_type', 'sales_order')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("COUNT(*) as count, {$groupByStr} as period")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('count', 'period');

        $quotations = Order::where('document_type', 'quotation')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("COUNT(*) as count, {$groupByStr} as period")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('count', 'period');

        // Merge all periods from both sets
        $allPeriods = $orders->keys()->merge($quotations->keys())->unique()->sort()->values();

        return [
            'labels' => $allPeriods->map(fn($p) => Carbon::parse($p)->format($phpFormat))->toArray(),
            'orders' => $allPeriods->map(fn($p) => (int) ($orders[$p] ?? 0))->toArray(),
            'quotations' => $allPeriods->map(fn($p) => (int) ($quotations[$p] ?? 0))->toArray(),
        ];
    }

    // =========================================================================
    //  RECENT ORDERS & QUOTATIONS
    // =========================================================================

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
    public function recentQuotations()
    {
        return Order::where('document_type', 'quotation')->with('user')->withCount('items')->latest()->limit(6)->get();
    }
};
?>

<div>

    {{-- ================================================================== --}}
    {{-- PAGE HEADER                                                         --}}
    {{-- ================================================================== --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight">Dashboard</flux:heading>
            <flux:subheading>{{ $this->periodLabel }}</flux:subheading>
        </div>

        {{-- ================================================================ --}}
        {{-- DATE FILTER                                                       --}}
        {{-- ================================================================ --}}
        <div x-data="{ showCustom: @entangle('period').live === 'custom' }" class="flex flex-wrap items-center gap-2">

            {{-- Quick period buttons --}}
            @foreach ([
        'today' => 'Today',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'this_year' => 'This Year',
        'last_year' => 'Last Year',
    ] as $key => $label)
                <button wire:click="setPeriod('{{ $key }}')" @class([
                    'px-3 py-1.5 text-xs font-medium rounded-md border transition-colors',
                    'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 border-zinc-900 dark:border-white' =>
                        $period === $key,
                    'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:border-zinc-400' =>
                        $period !== $key,
                ])>
                    {{ $label }}
                </button>
            @endforeach

            {{-- Custom range trigger --}}
            <button wire:click="setPeriod('custom')" @class([
                'px-3 py-1.5 text-xs font-medium rounded-md border transition-colors flex items-center gap-1.5',
                'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 border-zinc-900 dark:border-white' =>
                    $period === 'custom',
                'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:border-zinc-400' =>
                    $period !== 'custom',
            ])>
                <flux:icon.calendar-days class="size-3.5" />
                Custom
            </button>

            {{-- Custom date inputs — shown when period = custom --}}
            @if ($period === 'custom')
                <div class="flex items-center gap-2 mt-1 w-full sm:w-auto sm:mt-0">
                    <flux:input type="date" wire:model.live="dateFrom" size="sm" class="w-36" />
                    <span class="text-zinc-400 text-xs">to</span>
                    <flux:input type="date" wire:model.live="dateTo" size="sm" class="w-36" />
                </div>
            @endif
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- NEEDS ATTENTION BANNER                                              --}}
    {{-- Always current — not date-filtered                                  --}}
    {{-- ================================================================== --}}
    @if (
        $this->needsAttention['pending_orders'] > 0 ||
            $this->needsAttention['pending_quotes'] > 0 ||
            $this->needsAttention['expiring_quotes'] > 0)
        <div class="flex flex-wrap items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg mb-6">
            <flux:icon.exclamation-triangle class="size-4 shrink-0 text-amber-500" />
            <flux:text class="text-sm font-medium text-amber-800">Needs attention:</flux:text>

            @if ($this->needsAttention['pending_orders'] > 0)
                <flux:link :href="route('admin.orders.index')" wire:navigate
                    class="text-xs px-2 py-1 bg-amber-100 text-amber-800 rounded-md hover:bg-amber-200 transition-colors">
                    {{ $this->needsAttention['pending_orders'] }} pending
                    {{ Str::plural('order', $this->needsAttention['pending_orders']) }}
                </flux:link>
            @endif

            @if ($this->needsAttention['pending_quotes'] > 0)
                <flux:link :href="route('admin.orders.index')" wire:navigate
                    class="text-xs px-2 py-1 bg-amber-100 text-amber-800 rounded-md hover:bg-amber-200 transition-colors">
                    {{ $this->needsAttention['pending_quotes'] }}
                    {{ Str::plural('quote', $this->needsAttention['pending_quotes']) }} awaiting pricing
                </flux:link>
            @endif

            @if ($this->needsAttention['expiring_quotes'] > 0)
                <flux:link :href="route('admin.orders.index')" wire:navigate
                    class="text-xs px-2 py-1 bg-rose-100 text-rose-800 rounded-md hover:bg-rose-200 transition-colors">
                    {{ $this->needsAttention['expiring_quotes'] }}
                    {{ Str::plural('quote', $this->needsAttention['expiring_quotes']) }} expiring soon
                </flux:link>
            @endif
        </div>
    @endif

    {{-- ================================================================== --}}
    {{-- ROW 1: SALES STATS                                                  --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        <flux:card class="p-4 border-l-4 border-l-emerald-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Revenue</flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!">
                        {{ format_currency($this->salesStats['revenue']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">Paid orders only</flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                    <flux:icon.banknotes class="size-5 text-emerald-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-blue-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Orders</flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!">
                        {{ number_format($this->salesStats['order_count']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">
                        {{ $this->salesStats['paid_count'] }} paid
                    </flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                    <flux:icon.shopping-bag class="size-5 text-blue-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-violet-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Avg Order</flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!">
                        {{ format_currency($this->salesStats['avg_order']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">Per paid order</flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-violet-50 flex items-center justify-center shrink-0">
                    <flux:icon.chart-bar class="size-5 text-violet-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-cyan-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">New Customers</flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!">
                        {{ number_format($this->customerStats['new']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">
                        {{ number_format($this->customerStats['total']) }} total
                    </flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-cyan-50 flex items-center justify-center shrink-0">
                    <flux:icon.users class="size-5 text-cyan-500" />
                </div>
            </div>
        </flux:card>

    </div>

    {{-- ================================================================== --}}
    {{-- ROW 2: OPERATIONS + PRODUCT STATS                                   --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <flux:card class="p-4 border-l-4 border-l-amber-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Quotations</flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!">
                        {{ number_format($this->quotationStats['total']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">
                        @if ($this->quotationStats['conversion_rate'] !== null)
                            {{ $this->quotationStats['conversion_rate'] }}% conversion
                        @else
                            No resolved quotes
                        @endif
                    </flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                    <flux:icon.tag class="size-5 text-amber-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-teal-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Active Products</flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!">
                        {{ number_format($this->productStats['active']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">
                        {{ $this->productStats['requires_quote'] }} require quote
                    </flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center shrink-0">
                    <flux:icon.cube class="size-5 text-teal-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-orange-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Low Stock</flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!"
                        :class="$this->productStats['low_stock'] > 0 ? 'text-orange-600!' : ''">
                        {{ number_format($this->productStats['low_stock']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">Products near threshold</flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="size-5 text-orange-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-rose-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Out of Stock</flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!"
                        :class="$this->productStats['out_of_stock'] > 0 ? 'text-rose-600!' : ''">
                        {{ number_format($this->productStats['out_of_stock']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">Published products</flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center shrink-0">
                    <flux:icon.x-circle class="size-5 text-rose-500" />
                </div>
            </div>
        </flux:card>

    </div>

    {{-- ================================================================== --}}
    {{-- ROW 3: CHARTS — Revenue line + Orders donut                         --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        {{-- Revenue over time — takes 2/3 width --}}
        <flux:card class="p-0 col-span-2">
            <div class="px-5 py-3 border-b flex items-center justify-between">
                <flux:heading>Revenue Over Time</flux:heading>
                <flux:text class="text-xs text-zinc-400">{{ $this->periodLabel }}</flux:text>
            </div>
            <div class="p-4">
                <canvas id="revenueChart" height="120" wire:ignore
                    data-labels="{{ json_encode($this->revenueChartData['labels']) }}"
                    data-values="{{ json_encode($this->revenueChartData['values']) }}">
                </canvas>
            </div>
        </flux:card>

        {{-- Orders by status donut — takes 1/3 width --}}
        <flux:card class="p-0">
            <div class="px-5 py-3 border-b flex items-center justify-between">
                <flux:heading>Orders by Status</flux:heading>
                <flux:text class="text-xs text-zinc-400">{{ $this->periodLabel }}</flux:text>
            </div>
            <div class="p-4 flex items-center justify-center">
                @if (array_sum($this->orderStatusChartData['values']) > 0)
                    <canvas id="statusChart" height="200" wire:ignore
                        data-labels="{{ json_encode($this->orderStatusChartData['labels']) }}"
                        data-values="{{ json_encode($this->orderStatusChartData['values']) }}"
                        data-colors="{{ json_encode($this->orderStatusChartData['colors']) }}">
                    </canvas>
                @else
                    <div class="py-10 text-center text-zinc-400">
                        <flux:icon.shopping-bag class="size-10 mx-auto mb-2 stroke-1" />
                        <flux:text class="text-sm">No orders in this period</flux:text>
                    </div>
                @endif
            </div>
        </flux:card>

    </div>

    {{-- ================================================================== --}}
    {{-- ROW 4: CHARTS — Top products + Volume comparison                    --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

        {{-- Top selling products --}}
        <flux:card class="p-0">
            <div class="px-5 py-3 border-b flex items-center justify-between">
                <flux:heading>Top Selling Products</flux:heading>
                <flux:text class="text-xs text-zinc-400">By units sold · {{ $this->periodLabel }}</flux:text>
            </div>
            <div class="p-4">
                @if (!empty($this->topProductsChartData['labels']))
                    <canvas id="topProductsChart" height="200" wire:ignore
                        data-labels="{{ json_encode($this->topProductsChartData['labels']) }}"
                        data-units="{{ json_encode($this->topProductsChartData['units']) }}"
                        data-revenue="{{ json_encode($this->topProductsChartData['revenue']) }}">
                    </canvas>
                @else
                    <div class="py-10 text-center text-zinc-400">
                        <flux:icon.cube class="size-10 mx-auto mb-2 stroke-1" />
                        <flux:text class="text-sm">No sales data in this period</flux:text>
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- Order volume vs quotations --}}
        <flux:card class="p-0">
            <div class="px-5 py-3 border-b flex items-center justify-between">
                <flux:heading>Orders vs Quotations</flux:heading>
                <flux:text class="text-xs text-zinc-400">Volume · {{ $this->periodLabel }}</flux:text>
            </div>
            <div class="p-4">
                @if (!empty($this->volumeComparisonChartData['labels']))
                    <canvas id="volumeChart" height="200" wire:ignore
                        data-labels="{{ json_encode($this->volumeComparisonChartData['labels']) }}"
                        data-orders="{{ json_encode($this->volumeComparisonChartData['orders']) }}"
                        data-quotations="{{ json_encode($this->volumeComparisonChartData['quotations']) }}">
                    </canvas>
                @else
                    <div class="py-10 text-center text-zinc-400">
                        <flux:icon.chart-bar class="size-10 mx-auto mb-2 stroke-1" />
                        <flux:text class="text-sm">No data in this period</flux:text>
                    </div>
                @endif
            </div>
        </flux:card>

    </div>

    {{-- ================================================================== --}}
    {{-- ROW 5: RECENT ORDERS + RECENT QUOTATIONS                            --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Recent orders --}}
        <flux:card class="p-0">
            <div class="px-5 py-3 border-b flex items-center justify-between">
                <flux:heading>Recent Orders</flux:heading>
                <flux:link :href="route('admin.orders.index')" wire:navigate class="text-xs">
                    View all
                </flux:link>
            </div>
            <div class="divide-y">
                @forelse ($this->recentOrders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" wire:navigate
                        class="flex items-center justify-between px-5 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <div class="min-w-0">
                            <flux:text class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                {{ $order->reference }}
                            </flux:text>
                            <flux:text class="text-xs text-zinc-400 truncate">
                                {{ $order->user?->name }} · {{ $order->items_count }}
                                {{ Str::plural('item', $order->items_count) }}
                            </flux:text>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <flux:text class="text-sm font-semibold">
                                {{ format_currency($order->total) }}
                            </flux:text>
                            <flux:badge size="sm" :color="$order->status->color()">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center text-zinc-400">
                        <flux:text class="text-sm">No orders yet</flux:text>
                    </div>
                @endforelse
            </div>
        </flux:card>

        {{-- Recent quotations --}}
        <flux:card class="p-0">
            <div class="px-5 py-3 border-b flex items-center justify-between">
                <flux:heading>Recent Quotations</flux:heading>
                <flux:link :href="route('admin.orders.index')" wire:navigate class="text-xs">
                    View all
                </flux:link>
            </div>
            <div class="divide-y">
                @forelse ($this->recentQuotations as $quotation)
                    <a href="{{ route('admin.orders.quotations.show', $quotation) }}" wire:navigate
                        class="flex items-center justify-between px-5 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <div class="min-w-0">
                            <flux:text class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                {{ $quotation->reference }}
                            </flux:text>
                            <flux:text class="text-xs text-zinc-400 truncate">
                                {{ $quotation->user?->name }} ·
                                {{ ucfirst($quotation->quotation_type ?? '—') }} quote ·
                                {{ $quotation->created_at->diffForHumans() }}
                            </flux:text>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <flux:badge size="sm" :color="$quotation->status->color()">
                                {{ $quotation->status->label() }}
                            </flux:badge>
                            @if ($quotation->isAwaitingAdminAction())
                                <flux:badge size="sm" color="amber" variant="solid">Action needed</flux:badge>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center text-zinc-400">
                        <flux:text class="text-sm">No quotations yet</flux:text>
                    </div>
                @endforelse
            </div>
        </flux:card>

    </div>

</div>

{{-- ================================================================== --}}
{{-- CHART.JS INITIALIZATION                                             --}}
{{-- All four charts initialized after DOM is ready.                    --}}
{{-- wire:ignore on canvases prevents Livewire from destroying them     --}}
{{-- on re-renders. Charts are rebuilt via Livewire events instead.     --}}
{{-- ================================================================== --}}
@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        const chartInstances = {};

        // ── Shared defaults ──────────────────────────────────────────────────────
        const isDark = () => document.documentElement.classList.contains('dark');
        const gridColor = () => isDark() ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        const textColor = () => isDark() ? '#a1a1aa' : '#71717a';
        const fontFamily = '"Anthropic Sans", -apple-system, BlinkMacSystemFont, sans-serif';

        Chart.defaults.font.family = fontFamily;
        Chart.defaults.font.size = 11;

        function destroyChart(id) {
            if (chartInstances[id]) {
                chartInstances[id].destroy();
                delete chartInstances[id];
            }
        }

        // ── Revenue line chart ───────────────────────────────────────────────────
        function initRevenueChart() {
            const el = document.getElementById('revenueChart');
            if (!el) return;
            destroyChart('revenue');

            const labels = JSON.parse(el.dataset.labels || '[]');
            const values = JSON.parse(el.dataset.values || '[]');

            chartInstances['revenue'] = new Chart(el, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Revenue (KES)',
                        data: values,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.08)',
                        borderWidth: 2,
                        pointRadius: labels.length <= 10 ? 4 : 2,
                        pointBackgroundColor: '#10B981',
                        fill: true,
                        tension: 0.4,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' KES ' + ctx.parsed.y.toLocaleString('en-KE', {
                                    minimumFractionDigits: 2
                                })
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor()
                            },
                            ticks: {
                                color: textColor(),
                                maxTicksLimit: 8
                            }
                        },
                        y: {
                            grid: {
                                color: gridColor()
                            },
                            ticks: {
                                color: textColor(),
                                callback: v => 'KES ' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v)
                            }
                        }
                    }
                }
            });
        }

        // ── Orders by status donut ───────────────────────────────────────────────
        function initStatusChart() {
            const el = document.getElementById('statusChart');
            if (!el) return;
            destroyChart('status');

            const labels = JSON.parse(el.dataset.labels || '[]');
            const values = JSON.parse(el.dataset.values || '[]');
            const colors = JSON.parse(el.dataset.colors || '[]');

            chartInstances['status'] = new Chart(el, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: isDark() ? '#18181b' : '#ffffff',
                        hoverOffset: 6,
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor(),
                                padding: 12,
                                boxWidth: 10,
                                boxHeight: 10,
                            }
                        }
                    }
                }
            });
        }

        // ── Top selling products horizontal bar ──────────────────────────────────
        function initTopProductsChart() {
            const el = document.getElementById('topProductsChart');
            if (!el) return;
            destroyChart('topProducts');

            const labels = JSON.parse(el.dataset.labels || '[]');
            const units = JSON.parse(el.dataset.units || '[]');

            // Truncate long product names
            const shortLabels = labels.map(l => l.length > 28 ? l.substring(0, 28) + '…' : l);

            chartInstances['topProducts'] = new Chart(el, {
                type: 'bar',
                data: {
                    labels: shortLabels,
                    datasets: [{
                        label: 'Units Sold',
                        data: units,
                        backgroundColor: 'rgba(99, 102, 241, 0.75)',
                        borderColor: '#6366F1',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' ' + ctx.parsed.x + ' units sold'
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor()
                            },
                            ticks: {
                                color: textColor()
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: textColor()
                            }
                        }
                    }
                }
            });
        }

        // ── Order volume vs quotations grouped bar ────────────────────────────────
        function initVolumeChart() {
            const el = document.getElementById('volumeChart');
            if (!el) return;
            destroyChart('volume');

            const labels = JSON.parse(el.dataset.labels || '[]');
            const orders = JSON.parse(el.dataset.orders || '[]');
            const quotations = JSON.parse(el.dataset.quotations || '[]');

            chartInstances['volume'] = new Chart(el, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                            label: 'Sales Orders',
                            data: orders,
                            backgroundColor: 'rgba(59, 130, 246, 0.75)',
                            borderColor: '#3B82F6',
                            borderWidth: 1,
                            borderRadius: 3,
                        },
                        {
                            label: 'Quotations',
                            data: quotations,
                            backgroundColor: 'rgba(245, 158, 11, 0.75)',
                            borderColor: '#F59E0B',
                            borderWidth: 1,
                            borderRadius: 3,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: textColor(),
                                boxWidth: 10,
                                boxHeight: 10
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor()
                            },
                            ticks: {
                                color: textColor(),
                                maxTicksLimit: 10
                            }
                        },
                        y: {
                            grid: {
                                color: gridColor()
                            },
                            ticks: {
                                color: textColor(),
                                stepSize: 1
                            },
                            beginAtZero: true,
                        }
                    }
                }
            });
        }

        function initAllCharts() {
            initRevenueChart();
            initStatusChart();
            initTopProductsChart();
            initVolumeChart();
        }

        // Init on page load
        document.addEventListener('DOMContentLoaded', initAllCharts);

        // Re-init after every Livewire update (period change refreshes data)
        document.addEventListener('livewire:updated', () => {
            // Small delay so Livewire has finished updating the DOM
            setTimeout(initAllCharts, 50);
        });
    </script>
@endpush
