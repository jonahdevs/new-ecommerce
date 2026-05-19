<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\County;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Title('Dashboard')] class extends Component
{
    public string $preset = 'today';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        // Read from query parameters if available, otherwise default to today
        $this->preset = request()->query('preset', 'today');
        $this->dateFrom = request()->query('from', now()->startOfDay()->toDateString());
        $this->dateTo = request()->query('to', now()->endOfDay()->toDateString());
    }

    public function setDateRange(string $preset, string $from, string $to): void
    {
        $this->preset = $preset;
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->clearComputedCache();

        // Update URL with query parameters to maintain state on reload
        $this->js(sprintf("window.history.replaceState({}, '', '?preset=%s&from=%s&to=%s')", urlencode($preset), urlencode($from), urlencode($to)));
    }

    #[On('echo-private:admin.orders,.order.updated')]
    public function handleOrderUpdate(array $data): void
    {
        if ($data['update_type'] === 'created') {
            $this->dispatch('notify',
                title: 'New Order!',
                variant: 'success',
                message: "Order {$data['reference']} received from {$data['customer_name']}",
            );
        }

        $this->clearComputedCache();
    }

    private function clearComputedCache(): void
    {
        unset($this->dateRange, $this->periodLabel, $this->salesStats, $this->quotationStats, $this->productStats, $this->customerStats, $this->revenueChartData, $this->topProductsChartData, $this->recentOrders, $this->recentDeliveries, $this->recentCustomers, $this->satisfactionStats, $this->categoryStats, $this->stockReport, $this->topSalesLocations);
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

        return $from->isSameDay($to) ? $from->format('M j, Y') : $from->format('M j').' – '.$to->format('M j, Y');
    }

    #[Computed]
    public function salesStats(): array
    {
        [$from, $to] = $this->dateRange;

        $base = Order::whereBetween('created_at', [$from, $to]);
        $revenue = (clone $base)->where('payment_status', PaymentStatus::PAID->value)->sum('total_cents') / 100;
        $count = (clone $base)->count();
        $paid = (clone $base)->where('payment_status', PaymentStatus::PAID->value)->count();

        $diff = Carbon::parse($this->dateFrom)->diffInSeconds(Carbon::parse($this->dateTo));
        $prevFrom = Carbon::parse($this->dateFrom)
            ->subSeconds($diff + 1)
            ->startOfDay();
        $prevTo = Carbon::parse($this->dateFrom)->subSecond()->endOfDay();
        $prevBase = Order::whereBetween('created_at', [$prevFrom, $prevTo]);
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
        $base = Quote::whereBetween('created_at', [$from, $to]);
        $accepted = (clone $base)->where('status', QuoteStatus::ACCEPTED->value)->count();
        $rejected = (clone $base)->where('status', QuoteStatus::REJECTED->value)->count();
        $expired = (clone $base)->where('status', QuoteStatus::EXPIRED->value)->count();
        $resolved = $accepted + $rejected + $expired;

        return [
            'total' => (clone $base)->count(),
            'pending_admin' => (clone $base)->where('status', QuoteStatus::PENDING->value)->count(),
            'sent' => (clone $base)->where('status', QuoteStatus::SENT->value)->count(),
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
            'returning' => User::customer()->has('orders', '>=', 2)->count(),
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
        $revenueRows = Order::where('payment_status', PaymentStatus::PAID->value)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("SUM(total_cents) / 100 as revenue, {$groupBy} as period")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('revenue', 'period');

        // Order counts (all statuses)
        $orderRows = Order::whereBetween('created_at', [$from, $to])
            ->selectRaw("COUNT(*) as cnt, {$groupBy} as period")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('cnt', 'period');

        // Cancelled/failed as "refunds" proxy
        $refundRows = Order::whereIn('status', [OrderStatus::CANCELLED->value, OrderStatus::RETURNED->value])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("COUNT(*) as cnt, {$groupBy} as period")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('cnt', 'period');

        // Union all periods so every series has the same x-axis labels
        $allPeriods = collect($revenueRows->keys())->merge($orderRows->keys())->merge($refundRows->keys())->unique()->sort()->values();

        $labels = $allPeriods->map(fn ($p) => Carbon::parse($p)->format($phpFormat))->toArray();
        $revenueVals = $allPeriods->map(fn ($p) => round((float) ($revenueRows[$p] ?? 0), 2))->toArray();
        $orderVals = $allPeriods->map(fn ($p) => (int) ($orderRows[$p] ?? 0))->toArray();
        $refundVals = $allPeriods->map(fn ($p) => (int) ($refundRows[$p] ?? 0))->toArray();

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

        $query = fn ($from, $to) => Order::where('payment_status', PaymentStatus::PAID->value)
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
                    fn ($r) => [
                        'name' => $r->category,
                        'units' => (int) $r->units,
                        'revenue' => round((float) $r->revenue, 2),
                        'pct' => $total > 0 ? round(($r->units / $total) * 100) : 0,
                    ],
                )
                ->toArray(),
        ];
    }

    /**
     * Revenue aggregated per Kenya county for the current period.
     *
     * Pulls county name from `orders.shipping_address` JSON snapshot, joins
     * against the counties table to resolve the official KNBS code, and
     * returns rows keyed by both name and code so the Unovis map can match
     * features regardless of which property the TopoJSON uses.
     *
     * @return array<int, array{name: string, code: ?string, orders: int, revenue: float}>
     */
    #[Computed]
    public function topSalesLocations(): array
    {
        [$from, $to] = $this->dateRange;

        $rows = DB::table('orders')
            ->where('payment_status', PaymentStatus::PAID->value)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('shipping_address')
            ->selectRaw('
                JSON_UNQUOTE(JSON_EXTRACT(shipping_address, "$.county")) as county_name,
                COUNT(*) as orders,
                SUM(total_cents) / 100 as revenue
            ')
            ->groupBy('county_name')
            ->orderByDesc('revenue')
            ->get()
            ->filter(fn ($r) => ! empty($r->county_name) && $r->county_name !== 'null');

        $codesByName = County::pluck('code', 'name')->toArray();

        return $rows->map(fn ($r) => [
            'name' => $r->county_name,
            'code' => $codesByName[$r->county_name] ?? null,
            'orders' => (int) $r->orders,
            'revenue' => round((float) $r->revenue, 2),
        ])->values()->toArray();
    }

    /**
     * Low-stock items only — products at or below their low_stock_threshold,
     * sorted with out-of-stock first then ascending by remaining quantity.
     */
    #[Computed]
    public function stockReport()
    {
        return Product::where('status', 'published')
            ->where('manage_stock', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->limit(6)
            ->get(['id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold']);
    }

    #[Computed]
    public function topProductsChartData(): array
    {
        [$from, $to] = $this->dateRange;

        $rows = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
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
                    fn ($r) => [
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
        return Order::with(['user', 'payment'])
            ->withCount('items')
            ->latest()
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function recentActivities()
    {
        return Activity::with(['subject', 'causer'])
            ->whereIn('description', ['order_created', 'order_marked_paid', 'order_cancelled', 'payment_initiated', 'payment_confirmed', 'payment_failed', 'inventory_deducted', 'inventory_reserved', 'sap_sync_success', 'sap_sync_failed', 'quote_requested', 'quote_sent', 'quote_accepted', 'user_registered', 'webhook_received_mpesa', 'webhook_received_pesawise'])
            ->latest()
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function recentDeliveries()
    {
        return Order::whereIn('status', [OrderStatus::SHIPPED->value, OrderStatus::DELIVERED->value, OrderStatus::PROCESSING->value, OrderStatus::CONFIRMED->value])
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

    // ─────────────────────────────────────────────────────────────────────────
    // CSV export — snapshots the current Dashboard view for the active period
    // ─────────────────────────────────────────────────────────────────────────

    public function exportCsv(): StreamedResponse
    {
        $sales = $this->salesStats;
        $quotes = $this->quotationStats;
        $products = $this->productStats;
        $customers = $this->customerStats;
        $categories = $this->categoryStats;
        $topProducts = $this->topProductsChartData;
        $revenueChart = $this->revenueChartData;

        $rows = [];

        // Header
        $rows[] = ['Sheffield Africa — Dashboard Export'];
        $rows[] = ['Period', $this->periodLabel];
        $rows[] = ['Generated at', now()->format('Y-m-d H:i:s')];
        $rows[] = [];

        // Sales
        $rows[] = ['Sales'];
        $rows[] = ['Metric', 'Value', 'vs Prior Period'];
        $rows[] = ['Revenue (KES)', number_format($sales['revenue'], 2), $sales['revenue_trend'] !== null ? $sales['revenue_trend'].'%' : '—'];
        $rows[] = ['Orders', $sales['order_count'], $sales['orders_trend'] !== null ? $sales['orders_trend'].'%' : '—'];
        $rows[] = ['Paid Orders', $sales['paid_count'], '—'];
        $rows[] = ['Avg Order Value (KES)', number_format($sales['avg_order'], 2), '—'];
        $rows[] = [];

        // Quotations
        $rows[] = ['Quotations'];
        $rows[] = ['Metric', 'Value'];
        $rows[] = ['Total', $quotes['total']];
        $rows[] = ['Awaiting Pricing', $quotes['pending_admin']];
        $rows[] = ['Sent', $quotes['sent']];
        $rows[] = ['Accepted', $quotes['accepted']];
        $rows[] = ['Conversion Rate (%)', $quotes['conversion_rate'] ?? '—'];
        $rows[] = [];

        // Products
        $rows[] = ['Products'];
        $rows[] = ['Metric', 'Value'];
        $rows[] = ['Active', $products['active']];
        $rows[] = ['Low Stock', $products['low_stock']];
        $rows[] = ['Out of Stock', $products['out_of_stock']];
        $rows[] = ['Requires Quote', $products['requires_quote']];
        $rows[] = [];

        // Customers
        $rows[] = ['Customers'];
        $rows[] = ['Metric', 'Value', 'vs Prior Period'];
        $rows[] = ['Total', $customers['total'], '—'];
        $rows[] = ['New in Period', $customers['new'], $customers['new_trend'] !== null ? $customers['new_trend'].'%' : '—'];
        $rows[] = ['Returning (2+ orders)', $customers['returning'], '—'];
        $rows[] = [];

        // Revenue trend
        $rows[] = ['Revenue Trend'];
        $rows[] = ['Period', 'Revenue (KES)'];
        foreach ($revenueChart['labels'] ?? [] as $i => $label) {
            $rows[] = [$label, number_format($revenueChart['values'][$i] ?? 0, 2)];
        }
        $rows[] = [];

        // Top products
        $rows[] = ['Top Products'];
        $rows[] = ['Product', 'Units Sold', 'Revenue (KES)'];
        foreach ($topProducts['products'] ?? [] as $p) {
            $rows[] = [$p['name'] ?? '—', $p['units_sold'] ?? 0, number_format($p['revenue'] ?? 0, 2)];
        }
        $rows[] = [];

        // Top categories
        $rows[] = ['Top Categories'];
        $rows[] = ['Category', 'Units', 'Revenue (KES)', 'Share (%)'];
        foreach ($categories['categories'] ?? [] as $c) {
            $rows[] = [$c['name'], $c['units'], number_format($c['revenue'], 2), $c['pct']];
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::streamDownload(
            fn () => print $csv,
            'dashboard-'.now()->format('Y-m-d').'.csv',
            ['Content-Type' => 'text/csv'],
        );
    }
};
