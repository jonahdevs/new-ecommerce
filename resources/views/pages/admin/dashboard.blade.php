<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductVisibility;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

new #[Layout('layouts::app')] #[Title('Dashboard — Admin')] class extends Component {
    /** Order statuses that represent a confirmed (paid) sale. */
    private const PAID_STATUSES = [
        OrderStatus::PROCESSING->value,
        OrderStatus::OUT_FOR_DELIVERY->value,
        OrderStatus::COMPLETED->value,
    ];

    public string $preset = '30d';

    public string $dateFrom = '';

    public string $dateTo = '';

    private ?array $chartCache = null;

    private ?array $metricCache = null;

    public function mount(): void
    {
        [$from, $to] = $this->presetRange('30d');
        $this->dateFrom = $from->toDateString();
        $this->dateTo = $to->toDateString();
    }

    public function setPreset(string $preset): void
    {
        [$from, $to] = $this->presetRange($preset);
        $this->preset = $preset;
        $this->dateFrom = $from->toDateString();
        $this->dateTo = $to->toDateString();
        $this->refreshCharts();
    }

    public function applyCustom(): void
    {
        $this->validate([
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
        ]);

        $this->preset = 'custom';
        $this->refreshCharts();
    }

    private function refreshCharts(): void
    {
        $this->chartCache = $this->metricCache = null;
        $this->dispatch('dashboard-updated', charts: $this->chartData());
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    private function presetRange(string $preset): array
    {
        return match ($preset) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '7d' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '90d' => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            default => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
        };
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    private function dateRange(): array
    {
        return [
            \Illuminate\Support\Carbon::parse($this->dateFrom)->startOfDay(),
            \Illuminate\Support\Carbon::parse($this->dateTo)->endOfDay(),
        ];
    }

    public function periodLabel(): string
    {
        [$from, $to] = $this->dateRange();

        return $from->isSameDay($to)
            ? $from->format('M j, Y')
            : $from->format('M j').' – '.$to->format('M j, Y');
    }

    /** Time-of-day greeting for the dashboard header. */
    public function greeting(): string
    {
        return match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    private function trend(float $current, float $previous): ?float
    {
        return $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : null;
    }

    public function metrics(): array
    {
        return $this->metricCache ??= (function (): array {
            [$from, $to] = $this->dateRange();
            $lengthDays = (int) $from->diffInDays($to) + 1;
            $prevTo = $from->copy()->subDay()->endOfDay();
            $prevFrom = $from->copy()->subDays($lengthDays)->startOfDay();

            $revenue = (int) Order::whereIn('status', self::PAID_STATUSES)
                ->whereBetween('created_at', [$from, $to])->sum('total_cents');
            $prevRevenue = (int) Order::whereIn('status', self::PAID_STATUSES)
                ->whereBetween('created_at', [$prevFrom, $prevTo])->sum('total_cents');

            $orders = Order::whereBetween('created_at', [$from, $to])
                ->where('status', '!=', OrderStatus::CANCELLED->value)->count();
            $prevOrders = Order::whereBetween('created_at', [$prevFrom, $prevTo])
                ->where('status', '!=', OrderStatus::CANCELLED->value)->count();

            $paidOrders = Order::whereIn('status', self::PAID_STATUSES)
                ->whereBetween('created_at', [$from, $to])->count();

            $newCustomers = User::doesntHave('roles')->whereBetween('created_at', [$from, $to])->count();
            $prevNew = User::doesntHave('roles')->whereBetween('created_at', [$prevFrom, $prevTo])->count();

            return [
                'revenue_cents' => $revenue,
                'revenue_trend' => $this->trend($revenue, $prevRevenue),
                'orders' => $orders,
                'orders_trend' => $this->trend($orders, $prevOrders),
                'paid_orders' => $paidOrders,
                'aov_cents' => $paidOrders > 0 ? intdiv($revenue, $paidOrders) : 0,
                'customers_total' => User::doesntHave('roles')->count(),
                'customers_new' => $newCustomers,
                'customers_trend' => $this->trend($newCustomers, $prevNew),
                'customers_returning' => User::doesntHave('roles')->has('orders', '>=', 2)->count(),
                'products_active' => Product::where('visibility', ProductVisibility::VISIBLE->value)->count(),
                'low_stock' => Product::whereNotNull('stock_quantity')->whereNotNull('low_stock_threshold')
                    ->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
                'out_of_stock' => Product::whereNotNull('stock_quantity')->where('stock_quantity', 0)->count(),
                'pending_orders' => Order::where('status', OrderStatus::PENDING->value)->count(),
                'quotes_awaiting' => Quote::where('status', QuoteStatus::AWAITING_APPROVAL->value)->count(),
                'sap_failed' => Order::where('sap_sync_status', 'failed')->count(),
            ];
        })();
    }

    public function chartData(): array
    {
        return $this->chartCache ??= (function (): array {
            [$from, $to] = $this->dateRange();

            $revenue = $this->revenueSeries($from, $to);

            $channels = Payment::where('status', PaymentStatus::SUCCESS->value)->where('paid_at', '>=', $from)
                ->where('paid_at', '<=', $to)
                ->selectRaw('channel, COALESCE(SUM(amount_cents), 0) as amt')->groupBy('channel')->get();

            $statuses = Order::whereBetween('created_at', [$from, $to])
                ->selectRaw('status, COUNT(*) as c')->groupBy('status')->get();

            $cats = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('categories', 'products.primary_category_id', '=', 'categories.id')
                ->whereIn('orders.status', self::PAID_STATUSES)->whereBetween('orders.created_at', [$from, $to])
                ->selectRaw('categories.name as name, SUM(order_items.quantity) as units')
                ->groupBy('categories.name')->orderByDesc('units')->limit(5)->get();

            // Sales by county — geo-resolved from each order's address pin.
            $countyRows = DB::table('orders')
                ->join('addresses', 'orders.address_id', '=', 'addresses.id')
                ->whereIn('orders.status', self::PAID_STATUSES)->whereBetween('orders.created_at', [$from, $to])
                ->whereNotNull('addresses.county')
                ->selectRaw('addresses.county as c, COALESCE(SUM(orders.total_cents), 0) as rev')
                ->groupBy('addresses.county')->orderByDesc('rev')->get();

            $q = Quote::whereBetween('created_at', [$from, $to]);

            $reviews = Review::approved();
            $reviewTotal = (clone $reviews)->count();
            $dist = (clone $reviews)->selectRaw('rating, COUNT(*) as c')->groupBy('rating')->pluck('c', 'rating');
            $distribution = [];
            for ($star = 5; $star >= 1; $star--) {
                $distribution[$star] = (int) ($dist[$star] ?? 0);
            }

            return [
                'revenue' => $revenue,
                'channel' => [
                    'labels' => $channels->map(fn ($r) => $this->channelLabel($r->channel))->all(),
                    'data' => $channels->map(fn ($r) => (int) round(((int) $r->amt) / 100))->all(),
                ],
                'status' => [
                    'labels' => $statuses->map(fn ($r) => $r->status->label())->all(),
                    'data' => $statuses->map(fn ($r) => (int) $r->c)->all(),
                ],
                'categories' => [
                    'labels' => $cats->map(fn ($r) => $r->name)->all(),
                    'data' => $cats->map(fn ($r) => (int) $r->units)->all(),
                ],
                // County → KES map for the choropleth (all counties with sales).
                'countyMap' => $countyRows->mapWithKeys(fn ($r) => [$r->c => (int) round(((int) $r->rev) / 100)])->all(),
                'funnel' => [
                    'labels' => ['Requested', 'Sent', 'Approved', 'Ordered', 'Paid'],
                    'data' => [
                        (clone $q)->count(),
                        (clone $q)->where('status', '!=', QuoteStatus::DRAFT->value)->count(),
                        (clone $q)->where('status', QuoteStatus::APPROVED->value)->count(),
                        (clone $q)->whereNotNull('order_id')->count(),
                        (clone $q)->whereNotNull('order_id')
                            ->whereHas('order', fn ($o) => $o->whereHas('payments', fn ($p) => $p->where('status', PaymentStatus::SUCCESS->value)))->count(),
                    ],
                ],
                'satisfaction' => [
                    'total' => $reviewTotal,
                    'average' => $reviewTotal > 0 ? round((float) (clone $reviews)->avg('rating'), 1) : null,
                    'distribution' => array_values($distribution),
                ],
            ];
        })();
    }

    /**
     * Revenue + order-count series for the chart, bucketed to a sensible
     * granularity so the x-axis never crowds: hourly (≤1 day), daily (≤31),
     * weekly (≤92), otherwise monthly. Bucketing is done in PHP off a portable
     * DATE() aggregation so it works on both SQLite (tests) and MySQL.
     *
     * @return array{labels: list<string>, revenue: list<int>, orders: list<int>}
     */
    private function revenueSeries(CarbonInterface $from, CarbonInterface $to): array
    {
        $days = (int) $from->diffInDays($to) + 1;

        if ($days <= 1) {
            return $this->hourlyRevenueSeries($from, $to);
        }

        $revRows = Order::whereIn('status', self::PAID_STATUSES)->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as d, COALESCE(SUM(total_cents), 0) as rev')->groupBy('d')->pluck('rev', 'd');
        $ordRows = Order::whereBetween('created_at', [$from, $to])->where('status', '!=', OrderStatus::CANCELLED->value)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');

        $granularity = $days <= 31 ? 'day' : ($days <= 92 ? 'week' : 'month');

        $buckets = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $guard = 0;
        while ($cursor <= $end && $guard++ < 1200) {
            $key = match ($granularity) {
                'week' => $cursor->copy()->startOfWeek()->toDateString(),
                'month' => $cursor->format('Y-m'),
                default => $cursor->toDateString(),
            };

            $buckets[$key] ??= [
                'label' => match ($granularity) {
                    'week' => $cursor->copy()->startOfWeek()->format('M j'),
                    'month' => $cursor->format('M Y'),
                    default => $cursor->format('M j'),
                },
                'rev' => 0,
                'ord' => 0,
            ];

            $d = $cursor->toDateString();
            $buckets[$key]['rev'] += (int) ($revRows[$d] ?? 0);
            $buckets[$key]['ord'] += (int) ($ordRows[$d] ?? 0);
            $cursor->addDay();
        }

        $rows = array_values($buckets);

        return [
            'labels' => array_map(fn ($b) => $b['label'], $rows),
            'revenue' => array_map(fn ($b) => (int) round($b['rev'] / 100), $rows),
            'orders' => array_map(fn ($b) => $b['ord'], $rows),
        ];
    }

    /**
     * @return array{labels: list<string>, revenue: list<int>, orders: list<int>}
     */
    private function hourlyRevenueSeries(CarbonInterface $from, CarbonInterface $to): array
    {
        $rev = array_fill(0, 24, 0);
        $ord = array_fill(0, 24, 0);

        Order::whereIn('status', self::PAID_STATUSES)->whereBetween('created_at', [$from, $to])
            ->get(['total_cents', 'created_at'])
            ->each(function ($o) use (&$rev): void {
                $rev[(int) $o->created_at->format('G')] += (int) $o->total_cents;
            });

        Order::whereBetween('created_at', [$from, $to])->where('status', '!=', OrderStatus::CANCELLED->value)
            ->get(['created_at'])
            ->each(function ($o) use (&$ord): void {
                $ord[(int) $o->created_at->format('G')] += 1;
            });

        $labels = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = sprintf('%02d:00', $h);
        }

        return [
            'labels' => $labels,
            'revenue' => array_map(fn ($c) => (int) round($c / 100), array_values($rev)),
            'orders' => array_values($ord),
        ];
    }

    #[Computed]
    public function recentOrders()
    {
        return Order::with('user')->latest()->limit(5)->get();
    }

    #[Computed]
    public function recentActivity()
    {
        return Activity::with(['causer', 'subject'])->latest()->limit(6)->get();
    }

    #[Computed]
    public function stockReport()
    {
        return Product::whereNotNull('stock_quantity')->whereNotNull('low_stock_threshold')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')->limit(6)->get(['id', 'name', 'sku', 'stock_quantity']);
    }

    #[Computed]
    public function topProducts(): array
    {
        [$from, $to] = $this->dateRange();

        $rows = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('orders.status', self::PAID_STATUSES)->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('products.name as name, SUM(order_items.quantity) as units')
            ->groupBy('products.id', 'products.name')->orderByDesc('units')->limit(6)->get();

        $max = (int) ($rows->first()->units ?? 1);

        return $rows->map(fn ($r) => [
            'name' => $r->name ?? '—',
            'units' => (int) $r->units,
            'pct' => $max > 0 ? (int) round(($r->units / $max) * 100) : 0,
        ])->all();
    }

    private function channelLabel(?string $channel): string
    {
        return match ($channel) {
            'card' => 'Card',
            'mobile_money', 'mpesa' => 'M-Pesa / Mobile',
            'airtel' => 'Airtel Money',
            'bank', 'bank_transfer' => 'Bank Transfer',
            null, '' => 'Other',
            default => ucwords(str_replace('_', ' ', $channel)),
        };
    }

    /** Icon for an activity row, by its log name. */
    public function activityIcon(string $logName): string
    {
        return match ($logName) {
            'order' => 'shopping-bag',
            'payment' => 'banknotes',
            'quote' => 'document-text',
            'user' => 'user',
            'review' => 'star',
            default => 'bolt',
        };
    }

    /**
     * Current status of the activity's subject (Order/Payment/Quote/Review).
     * Read from the subject rather than the logged properties, which this app's
     * Activitylog config doesn't populate.
     */
    private function activityStatus(Activity $a): ?string
    {
        $status = $a->subject?->status ?? null;

        if ($status instanceof \BackedEnum) {
            return $status->value;
        }

        return is_string($status) ? $status : null;
    }

    /** A human, status-aware label — "Payment received", "Order cancelled"… */
    public function activityLabel(Activity $a): string
    {
        $created = $a->description === 'created';
        $status = $this->activityStatus($a);

        return match ($a->log_name) {
            'order' => match (true) {
                $created => 'Order placed',
                $status === 'processing' => 'Order confirmed',
                $status === 'out_for_delivery' => 'Order out for delivery',
                $status === 'completed' => 'Order completed',
                $status === 'cancelled' => 'Order cancelled',
                $status === 'refunded' => 'Order refunded',
                default => 'Order updated',
            },
            'payment' => match (true) {
                $status === 'success' => 'Payment received',
                $status === 'refunded' => 'Payment refunded',
                $status === 'failed' => 'Payment failed',
                $status === 'cancelled' => 'Payment cancelled',
                $created || $status === 'pending' => 'Payment started',
                default => 'Payment updated',
            },
            'quote' => match (true) {
                $created => 'Quote requested',
                $status === 'sent' => 'Quote sent',
                $status === 'awaiting_approval' => 'Quote sent for approval',
                $status === 'approved' => 'Quote approved',
                $status === 'declined' => 'Quote declined',
                $status === 'expired' => 'Quote expired',
                default => 'Quote updated',
            },
            'review' => match (true) {
                $status === 'approved' => 'Review approved',
                $status === 'rejected' => 'Review rejected',
                $created => 'Review submitted',
                default => 'Review updated',
            },
            'user' => $created ? 'Customer registered' : 'Customer updated',
            default => ucfirst($a->log_name).' '.$a->description,
        };
    }

    /** Colour tone for the icon badge, derived from the outcome. */
    public function activityTone(Activity $a): string
    {
        $status = $this->activityStatus($a);

        return match (true) {
            in_array($status, ['success', 'processing', 'completed', 'approved'], true) => 'green',
            in_array($status, ['failed', 'cancelled', 'declined'], true) => 'red',
            $status === 'refunded' => 'purple',
            in_array($status, ['awaiting_approval', 'pending'], true) => 'amber',
            $a->description === 'created' => 'blue',
            default => 'zinc',
        };
    }

    /** "Order SHF-… · Jane Doe" — what was acted on and by whom. */
    public function activityTarget(Activity $a): ?string
    {
        $subject = $a->subject;

        $ref = match ($a->subject_type) {
            Order::class => $subject?->order_number ? 'Order '.$subject->order_number : null,
            Quote::class => $subject?->quote_number ? 'Quote '.$subject->quote_number : null,
            Payment::class => $subject?->account_reference ? 'Order '.$subject->account_reference : null,
            User::class => $subject?->email,
            default => null,
        };

        return collect([$ref, $a->causer?->name])->filter()->implode(' · ') ?: null;
    }
}; ?>

@assets
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>.leaflet-container { background: transparent; font: inherit; }</style>
@endassets

@php $m = $this->metrics(); @endphp

<div class="flex flex-col gap-4" x-data="dashboardCharts(@js($this->chartData()))"
    @dashboard-updated.window="update($event.detail.charts)">

    {{-- Header + date range --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">{{ $this->greeting() }}, {{ str(auth()->user()->name)->before(' ') }} 👋</flux:heading>
            <flux:subheading>Here's your store overview · {{ $this->periodLabel() }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative" wire:ignore x-data="rangePicker(@js($dateFrom), @js($dateTo))">
                <flux:icon.calendar-days class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-zinc-400" />
                <input x-ref="input" type="text" readonly placeholder="Custom range"
                    class="w-56 cursor-pointer rounded-lg border border-zinc-200 bg-white py-1.5 pr-3 pl-8 text-sm text-zinc-700 transition-colors hover:border-zinc-400 focus:ring-2 focus:ring-zinc-300 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300" />
            </div>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.dashboard.stat label="Revenue" :value="money($m['revenue_cents'])" icon="banknotes" tone="emerald"
            :trend="$m['revenue_trend']" />
        <x-admin.dashboard.stat label="Orders" :value="number_format($m['orders'])" icon="shopping-bag" tone="blue"
            :trend="$m['orders_trend']" hint="{{ $m['paid_orders'] }} paid · AOV {{ money($m['aov_cents']) }}" />
        <x-admin.dashboard.stat label="Customers" :value="number_format($m['customers_total'])" icon="users" tone="violet"
            :trend="$m['customers_trend']" hint="{{ $m['customers_new'] }} new · {{ $m['customers_returning'] }} returning" />
        <x-admin.dashboard.stat label="Active products" :value="number_format($m['products_active'])" icon="cube"
            :tone="$m['low_stock'] + $m['out_of_stock'] > 0 ? 'amber' : 'teal'"
            hint="{{ $m['low_stock'] + $m['out_of_stock'] > 0 ? ($m['low_stock'] + $m['out_of_stock']).' need attention' : 'all stocked' }}" />
    </div>

    {{-- Attention strip --}}
    @if ($m['pending_orders'] > 0 || $m['quotes_awaiting'] > 0 || $m['sap_failed'] > 0)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @if ($m['pending_orders'] > 0)
                <a href="{{ route('admin.orders.index') }}" wire:navigate
                    class="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
                    <flux:icon.clock class="size-5 text-amber-600" />
                    <div class="text-sm"><span class="font-semibold text-amber-800 dark:text-amber-300">{{ $m['pending_orders'] }}</span>
                        <span class="text-amber-700 dark:text-amber-400"> pending order(s)</span></div>
                </a>
            @endif
            @if ($m['quotes_awaiting'] > 0)
                <a href="{{ route('admin.quotes.index') }}" wire:navigate
                    class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-900/20">
                    <flux:icon.document-text class="size-5 text-blue-600" />
                    <div class="text-sm"><span class="font-semibold text-blue-800 dark:text-blue-300">{{ $m['quotes_awaiting'] }}</span>
                        <span class="text-blue-700 dark:text-blue-400"> quote(s) awaiting approval</span></div>
                </a>
            @endif
            @if ($m['sap_failed'] > 0)
                <a href="{{ route('admin.orders.index') }}" wire:navigate
                    class="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-900/20">
                    <flux:icon.exclamation-triangle class="size-5 text-red-600" />
                    <div class="text-sm"><span class="font-semibold text-red-800 dark:text-red-300">{{ $m['sap_failed'] }}</span>
                        <span class="text-red-700 dark:text-red-400"> SAP sync failure(s)</span></div>
                </a>
            @endif
        </div>
    @endif

    {{-- Revenue chart + funnel --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <flux:card class="p-0 overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Revenue & orders</flux:heading>
                <div class="flex items-center gap-1 rounded-lg bg-zinc-100 p-0.5 dark:bg-zinc-800" x-data="{ t: 'area' }">
                    <button type="button" @click="t = 'area'; toggleRevenue('area')" title="Area"
                        :class="t === 'area' ? 'bg-white shadow-sm dark:bg-zinc-700' : 'text-zinc-400'"
                        class="rounded-md p-1.5 transition-colors"><flux:icon.presentation-chart-line class="size-4" /></button>
                    <button type="button" @click="t = 'bar'; toggleRevenue('bar')" title="Bar"
                        :class="t === 'bar' ? 'bg-white shadow-sm dark:bg-zinc-700' : 'text-zinc-400'"
                        class="rounded-md p-1.5 transition-colors"><flux:icon.chart-bar class="size-4" /></button>
                </div>
            </div>
            <div class="p-4"><div wire:ignore x-ref="revenue"></div></div>
        </flux:card>

        <flux:card class="p-0 overflow-hidden">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Quotes → orders</flux:heading>
            </div>
            <div class="p-4"><div wire:ignore x-ref="funnel"></div></div>
        </flux:card>
    </div>

    {{-- Recent orders + activity --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <flux:card class="p-0 overflow-hidden">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Recent activity</flux:heading>
            </div>
            @php
                $activityTones = [
                    'green' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400',
                    'red' => 'bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400',
                    'amber' => 'bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400',
                    'purple' => 'bg-purple-100 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400',
                    'blue' => 'bg-blue-100 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400',
                    'zinc' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
                ];
            @endphp
            <div class="flex flex-col gap-4 p-5">
                @forelse ($this->recentActivity as $a)
                    <div class="flex gap-3">
                        <div class="flex size-7 shrink-0 items-center justify-center rounded-full {{ $activityTones[$this->activityTone($a)] ?? $activityTones['zinc'] }}">
                            <flux:icon :name="$this->activityIcon($a->log_name)" class="size-3.5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ $this->activityLabel($a) }}</p>
                                <time class="shrink-0 text-[10px] text-zinc-400">{{ $a->created_at->diffForHumans() }}</time>
                            </div>
                            @if ($this->activityTarget($a))
                                <p class="mt-0.5 truncate text-[11px] text-zinc-400">{{ $this->activityTarget($a) }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-zinc-400">No recent activity</p>
                @endforelse
            </div>
        </flux:card>

        <flux:card class="p-0 overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Recent orders</flux:heading>
                <flux:link :href="route('admin.orders.index')" wire:navigate class="text-xs">View all</flux:link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($this->recentOrders as $order)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" wire:navigate
                                        class="font-medium text-blue-600 hover:underline dark:text-blue-400">{{ $order->order_number }}</a>
                                    <div class="text-[11px] text-zinc-400">{{ $order->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="px-3 py-3 text-zinc-700 dark:text-zinc-300">{{ $order->user?->name ?? '—' }}</td>
                                <td class="px-3 py-3 text-right font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{!! money($order->total_cents) !!}</td>
                                <td class="px-6 py-3 text-right">
                                    <flux:badge size="sm" :color="$order->status->badgeColor()">{{ $order->status->label() }}</flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-10 text-center text-zinc-400">No orders yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    </div>

    {{-- Low stock + top products --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <flux:card class="p-0 overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Low stock report</flux:heading>
                <flux:link :href="route('admin.products.index')" wire:navigate class="text-xs">Manage stock</flux:link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($this->stockReport as $product)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="px-6 py-3 text-zinc-800 dark:text-zinc-200">{{ Str::limit($product->name, 45) }}</td>
                                <td class="px-3 py-3 font-mono text-xs text-zinc-400">{{ $product->sku ?? '—' }}</td>
                                <td class="px-3 py-3">
                                    <flux:badge size="sm" :color="$product->stock_quantity === 0 ? 'red' : 'amber'">
                                        {{ $product->stock_quantity === 0 ? 'Out of stock' : 'Low' }}</flux:badge>
                                </td>
                                <td class="px-6 py-3 text-right font-semibold tabular-nums">{{ $product->stock_quantity }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-10 text-center text-zinc-400">All stock above the low-stock threshold.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>

        <flux:card class="p-0 overflow-hidden">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Top products</flux:heading>
            </div>
            <div class="flex flex-col gap-3 p-5">
                @forelse ($this->topProducts as $i => $item)
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-300">{{ Str::limit($item['name'], 24) }}</span>
                            <span class="shrink-0 font-semibold tabular-nums text-zinc-800 dark:text-zinc-200">{{ $item['units'] }}</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-brand-500" style="width: {{ $item['pct'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-zinc-400">No sales in this period</p>
                @endforelse
            </div>
        </flux:card>
    </div>

    {{-- Payment + status donuts, with the county map in the old delivery-zone slot --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        @foreach (['channel' => 'Revenue by payment method', 'status' => 'Orders by status'] as $ref => $title)
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm" class="uppercase tracking-wide">{{ $title }}</flux:heading>
                </div>
                <div class="p-4"><div wire:ignore x-ref="{{ $ref }}"></div></div>
            </flux:card>
        @endforeach
        <flux:card class="p-0 overflow-hidden">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Sales by county</flux:heading>
            </div>
            <div class="p-4"><div wire:ignore x-ref="countyMap" class="h-[300px] w-full rounded-md"></div></div>
        </flux:card>
    </div>

    {{-- Satisfaction + categories --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <flux:card class="p-0 overflow-hidden">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Customer satisfaction</flux:heading>
            </div>
            <div class="p-4">
                <div class="relative flex items-center justify-center">
                    <div wire:ignore x-ref="satisfaction" class="w-full"></div>
                    <div class="pointer-events-none absolute flex flex-col items-center">
                        @if ($this->chartData()['satisfaction']['average'])
                            <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->chartData()['satisfaction']['average'] }}</span>
                            <span class="text-[10px] text-zinc-400">of 5 · {{ $this->chartData()['satisfaction']['total'] }} reviews</span>
                        @else
                            <span class="text-xs text-zinc-400">No reviews</span>
                        @endif
                    </div>
                </div>
            </div>
        </flux:card>

        <flux:card class="p-0 overflow-hidden lg:col-span-2">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Top categories (units sold)</flux:heading>
            </div>
            <div class="p-4"><div wire:ignore x-ref="categories"></div></div>
        </flux:card>
    </div>
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
                    defaultDate: [from, to],
                    maxDate: 'today',
                    onClose: (dates) => {
                        if (dates.length === 2) {
                            this.$wire.set('dateFrom', this.fp.formatDate(dates[0], 'Y-m-d'));
                            this.$wire.set('dateTo', this.fp.formatDate(dates[1], 'Y-m-d'));
                            this.$wire.applyCustom();
                        }
                    },
                });

                // Keep the picker's display in sync when a preset button changes the range.
                this.$wire.$watch('dateTo', () => {
                    this.fp.setDate([
                        new Date(this.$wire.dateFrom + 'T00:00:00'),
                        new Date(this.$wire.dateTo + 'T00:00:00'),
                    ], false);
                });
            },
        }));

        Alpine.data('dashboardCharts', (initial) => ({
            charts: {},
            revData: null,

            init() {
                if (typeof ApexCharts === 'undefined') {
                    return;
                }
                this.render(initial);
            },

            money(v) {
                return 'KES ' + Number(v || 0).toLocaleString();
            },

            palette: ['#0d9488', '#2563eb', '#f59e0b', '#7c3aed', '#64748b', '#dc2626'],

            revenueSeries(d) {
                return [
                    { name: 'Revenue', type: this._revType || 'area', data: d.revenue.revenue },
                    { name: 'Orders', type: 'area', data: d.revenue.orders },
                ];
            },

            revenueFill() {
                // Solid fill for bars; soft gradient for the area view.
                return this._revType === 'bar'
                    ? { type: 'solid', opacity: [0.9, 0.2] }
                    : { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } };
            },

            revenueStroke() {
                // No outline on bars; a 2px line keeps the area/line readable.
                return { curve: 'smooth', width: this._revType === 'bar' ? [0, 2] : 2 };
            },

            revenueOptions(d) {
                return {
                    chart: { type: 'area', height: 320, fontFamily: 'inherit', toolbar: { show: false } },
                    series: this.revenueSeries(d),
                    colors: ['#0d9488', '#7c3aed'],
                    plotOptions: { bar: { columnWidth: '55%', borderRadius: 3 } },
                    stroke: this.revenueStroke(),
                    fill: this.revenueFill(),
                    dataLabels: { enabled: false },
                    xaxis: { categories: d.revenue.labels, tickAmount: 8, labels: { rotate: 0, hideOverlappingLabels: true } },
                    yaxis: [
                        { seriesName: 'Revenue', labels: { formatter: (v) => this.money(Math.round(v)) } },
                        { seriesName: 'Orders', opposite: true, labels: { formatter: (v) => Math.round(v) } },
                    ],
                    tooltip: { y: { formatter: (v, o) => o.seriesIndex === 0 ? this.money(v) : v } },
                    legend: { position: 'top', horizontalAlign: 'right' },
                };
            },

            donut(refName, d, money = false) {
                this.charts[refName] = new ApexCharts(this.$refs[refName], {
                    chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
                    series: d.data,
                    labels: d.labels,
                    colors: this.palette,
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: true },
                    tooltip: money ? { y: { formatter: (v) => this.money(v) } } : {},
                    noData: { text: 'No data in this period' },
                });
                this.charts[refName].render();
            },

            bar(refName, d, money = false) {
                this.charts[refName] = new ApexCharts(this.$refs[refName], {
                    chart: { type: 'bar', height: 300, fontFamily: 'inherit', toolbar: { show: false } },
                    series: [{ name: money ? 'Revenue' : 'Units', data: d.data }],
                    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
                    colors: ['#2563eb'],
                    xaxis: { categories: d.labels },
                    dataLabels: { enabled: false },
                    tooltip: money ? { y: { formatter: (v) => this.money(v) } } : {},
                    noData: { text: 'No data in this period' },
                });
                this.charts[refName].render();
            },

            render(d) {
                this.revData = d;
                this._revType = 'area';

                this.charts.revenue = new ApexCharts(this.$refs.revenue, this.revenueOptions(d));
                this.charts.revenue.render();

                this.charts.funnel = new ApexCharts(this.$refs.funnel, {
                    chart: { type: 'bar', height: 320, fontFamily: 'inherit', toolbar: { show: false } },
                    series: [{ name: 'Count', data: d.funnel.data }],
                    plotOptions: { bar: { horizontal: true, distributed: true, barHeight: '70%', isFunnel: true } },
                    colors: this.palette,
                    xaxis: { categories: d.funnel.labels },
                    legend: { show: false },
                    dataLabels: { enabled: true, formatter: (val, o) => o.w.globals.labels[o.dataPointIndex] + ': ' + val },
                });
                this.charts.funnel.render();

                this.donut('channel', d.channel, true);
                this.donut('status', d.status);
                this.bar('categories', d.categories);
                this.initCountyMap(d);

                this.charts.satisfaction = new ApexCharts(this.$refs.satisfaction, {
                    chart: { type: 'donut', height: 220, fontFamily: 'inherit' },
                    series: d.satisfaction.total > 0 ? d.satisfaction.distribution : [1],
                    labels: ['5★', '4★', '3★', '2★', '1★'],
                    colors: d.satisfaction.total > 0 ? ['#10b981', '#3b82f6', '#f59e0b', '#f97316', '#f43f5e'] : ['#e4e4e7'],
                    plotOptions: { pie: { donut: { size: '72%' } } },
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: false },
                    tooltip: { enabled: d.satisfaction.total > 0 },
                });
                this.charts.satisfaction.render();
            },

            toggleRevenue(type) {
                this._revType = type;
                this.charts.revenue?.updateOptions({
                    series: this.revenueSeries(this.revData),
                    fill: this.revenueFill(),
                    stroke: this.revenueStroke(),
                });
            },

            // ---- County choropleth (Leaflet) ----
            lmap: null,
            geoLayer: null,
            countyData: {},
            countyMax: 1,

            async initCountyMap(d) {
                if (typeof L === 'undefined' || ! this.$refs.countyMap) {
                    return;
                }

                this.countyData = d.countyMap || {};
                this.countyMax = Math.max(1, ...Object.values(this.countyData));

                this.lmap = L.map(this.$refs.countyMap, {
                    attributionControl: false,
                    zoomControl: true,
                    scrollWheelZoom: true,
                });

                try {
                    const geo = await fetch('/maps/kenya-counties.geojson').then((r) => r.json());
                    this.geoLayer = L.geoJSON(geo, {
                        style: (f) => this.countyStyle(f),
                        onEachFeature: (f, layer) => {
                            layer.bindTooltip(this.countyTooltip(f.properties.shapeName), { sticky: true });
                            layer.on({
                                mouseover: (e) => e.target.setStyle({ weight: 2, fillOpacity: 1 }),
                                mouseout: (e) => this.geoLayer.resetStyle(e.target),
                            });
                        },
                    }).addTo(this.lmap);

                    const bounds = this.geoLayer.getBounds();
                    this.lmap.fitBounds(bounds, { padding: [8, 8] });
                    // Keep panning/zooming anchored around Kenya.
                    this.lmap.setMaxBounds(bounds.pad(0.3));
                    this.lmap.setMinZoom(this.lmap.getZoom() - 1);
                } catch (e) {
                    // Leaving the map blank is fine if the GeoJSON can't load.
                }
            },

            countyColor(v) {
                if (! v) return '#eef2f6';
                const t = v / this.countyMax;
                if (t > 0.66) return '#0f766e';
                if (t > 0.33) return '#14b8a6';
                return '#5eead4';
            },

            countyStyle(f) {
                return {
                    fillColor: this.countyColor(this.countyData[f.properties.shapeName] || 0),
                    weight: 1, color: '#ffffff', fillOpacity: 0.85,
                };
            },

            countyTooltip(name) {
                return `<strong>${name}</strong><br>${this.money(this.countyData[name] || 0)}`;
            },

            updateMap(d) {
                if (! this.geoLayer) return;
                this.countyData = d.countyMap || {};
                this.countyMax = Math.max(1, ...Object.values(this.countyData));
                this.geoLayer.setStyle((f) => this.countyStyle(f));
                this.geoLayer.eachLayer((l) => l.setTooltipContent(this.countyTooltip(l.feature.properties.shapeName)));
            },

            update(d) {
                this.revData = d;
                this.charts.revenue?.updateOptions({
                    series: this.revenueSeries(d),
                    xaxis: { categories: d.revenue.labels },
                });
                this.charts.funnel?.updateOptions({ series: [{ name: 'Count', data: d.funnel.data }], xaxis: { categories: d.funnel.labels } });
                this.charts.channel?.updateOptions({ series: d.channel.data, labels: d.channel.labels });
                this.charts.status?.updateOptions({ series: d.status.data, labels: d.status.labels });
                this.charts.categories?.updateOptions({ series: [{ name: 'Units', data: d.categories.data }], xaxis: { categories: d.categories.labels } });
                this.updateMap(d);
            },
        }));
    </script>
@endscript
