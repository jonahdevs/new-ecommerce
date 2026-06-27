<?php

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Product analytics | Admin')] class extends Component {
    use WithPagination;

    /** Order statuses that represent a confirmed (paid) sale. */
    private const PAID_STATUSES = [OrderStatus::PROCESSING->value, OrderStatus::OUT_FOR_DELIVERY->value, OrderStatus::COMPLETED->value];

    #[Locked]
    public Product $product;

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    private ?array $metricCache = null;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['brand', 'primaryCategory', 'media']);

        [$from, $to] = $this->presetRange('30d');
        $this->dateFrom = $from->toDateString();
        $this->dateTo = $to->toDateString();
    }

    public function applyCustom(): void
    {
        $this->validate([
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
        ]);

        $this->refreshCharts();
    }

    private function refreshCharts(): void
    {
        $this->metricCache = null;
        $this->dispatch('product-analytics-updated', charts: $this->chartData());
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    private function presetRange(string $preset): array
    {
        return match ($preset) {
            '7d' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '90d' => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
            default => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
        };
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    private function dateRange(): array
    {
        return [\Illuminate\Support\Carbon::parse($this->dateFrom)->startOfDay(), \Illuminate\Support\Carbon::parse($this->dateTo)->endOfDay()];
    }

    public function periodLabel(): string
    {
        [$from, $to] = $this->dateRange();

        return $from->isSameDay($to) ? $from->format('M j, Y') : $from->format('M j') . ' – ' . $to->format('M j, Y');
    }

    private function trend(float $current, float $previous): ?float
    {
        return $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : null;
    }

    /**
     * Period-scoped sales, margin, views and conversion for this product, each
     * with a trend versus the immediately preceding period of equal length.
     */
    public function metrics(): array
    {
        return $this->metricCache ??= (function (): array {
            [$from, $to] = $this->dateRange();
            $lengthDays = (int) $from->diffInDays($to) + 1;
            $prevTo = $from->copy()->subDay()->endOfDay();
            $prevFrom = $from->copy()->subDays($lengthDays)->startOfDay();

            $sales = fn (CarbonInterface $a, CarbonInterface $b) => OrderItem::query()
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $this->product->id)
                ->whereIn('orders.status', self::PAID_STATUSES)
                ->whereBetween('orders.created_at', [$a, $b])
                ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as units, COALESCE(SUM(order_items.line_total_cents), 0) as revenue, COUNT(DISTINCT order_items.order_id) as orders')
                ->first();

            $now = $sales($from, $to);
            $prev = $sales($prevFrom, $prevTo);

            $units = (int) $now->units;
            $revenue = (int) $now->revenue;
            $cost = (int) ($this->product->cost_price ?? 0) * $units;
            $margin = $revenue - $cost;

            $views = ProductView::where('product_id', $this->product->id)->whereBetween('viewed_at', [$from, $to])->count();
            $prevViews = ProductView::where('product_id', $this->product->id)->whereBetween('viewed_at', [$prevFrom, $prevTo])->count();

            return [
                'units' => $units,
                'units_trend' => $this->trend($units, (int) $prev->units),
                'revenue_cents' => $revenue,
                'revenue_trend' => $this->trend($revenue, (int) $prev->revenue),
                'orders' => (int) $now->orders,
                'cost_cents' => $cost,
                'margin_cents' => $margin,
                'margin_pct' => $revenue > 0 ? round($margin / $revenue * 100, 1) : null,
                'has_cost' => $this->product->cost_price !== null,
                'views' => $views,
                'views_trend' => $this->trend($views, $prevViews),
                'conversion_pct' => $views > 0 ? round((int) $now->orders / $views * 100, 1) : null,
            ];
        })();
    }

    /**
     * Daily units + revenue series across the active range, zero-filled so the
     * x-axis is continuous. Bucketing is done in PHP off a portable DATE()
     * aggregation so it works on both MySQL and SQLite (tests).
     *
     * @return array{labels: list<string>, units: list<int>, revenue: list<int>}
     */
    public function chartData(): array
    {
        [$from, $to] = $this->dateRange();

        $rows = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $this->product->id)
            ->whereIn('orders.status', self::PAID_STATUSES)
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('DATE(orders.created_at) as d, SUM(order_items.quantity) as units, SUM(order_items.line_total_cents) as revenue')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $labels = $units = $revenue = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $guard = 0;

        while ($cursor <= $end && $guard++ < 400) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('M j');
            $units[] = (int) ($rows[$key]->units ?? 0);
            $revenue[] = (int) round((int) ($rows[$key]->revenue ?? 0) / 100);
            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'units' => $units,
            'revenue' => $revenue,
            'reviews' => array_values($this->reviewStats()['distribution']),
            'reviewTotal' => $this->reviewStats()['total'],
        ];
    }

    /** @return array{average: ?float, total: int, pending: int, distribution: array<int, int>} */
    #[Computed]
    public function reviewStats(): array
    {
        $approved = $this->product->reviews()->where('status', 'approved');

        // Merge count + avg into one query; distribution as a second; pending as a third.
        $agg = (clone $approved)
            ->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as avg_rating')
            ->first();

        $total = (int) ($agg->total ?? 0);
        $dist = (clone $approved)
            ->selectRaw('rating, COUNT(*) as c')
            ->groupBy('rating')
            ->pluck('c', 'rating');

        $distribution = [];
        for ($star = 5; $star >= 1; $star--) {
            $distribution[$star] = (int) ($dist[$star] ?? 0);
        }

        return [
            'average' => $total > 0 ? round((float) $agg->avg_rating, 1) : null,
            'total' => $total,
            'pending' => $this->product->reviews()->where('status', 'pending')->count(),
            'distribution' => $distribution,
        ];
    }

    /**
     * Inventory snapshot plus "days of cover" — how long current stock lasts at
     * the recent average daily sales rate. Null cover when nothing is selling.
     */
    #[Computed]
    public function inventory(): array
    {
        [$from, $to] = $this->dateRange();
        $lengthDays = max(1, (int) $from->diffInDays($to) + 1);

        $unitsInPeriod = (int) OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $this->product->id)
            ->whereIn('orders.status', self::PAID_STATUSES)
            ->whereBetween('orders.created_at', [$from, $to])
            ->sum('order_items.quantity');

        $dailyRate = $unitsInPeriod / $lengthDays;
        $stock = $this->product->stock_quantity;

        $lifetimeUnits = (int) OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $this->product->id)
            ->whereIn('orders.status', self::PAID_STATUSES)
            ->sum('order_items.quantity');

        return [
            'stock_quantity' => $stock,
            'low_stock_threshold' => $this->product->low_stock_threshold,
            'daily_rate' => round($dailyRate, 1),
            'days_cover' => ($stock !== null && $dailyRate > 0) ? (int) floor($stock / $dailyRate) : null,
            'lifetime_units' => $lifetimeUnits,
        ];
    }

    #[Computed]
    public function orderItems()
    {
        return OrderItem::query()
            ->with(['order:id,order_number,user_id,status,created_at', 'order.user:id,name'])
            ->where('product_id', $this->product->id)
            ->whereHas('order', fn ($q) => $q->whereIn('status', self::PAID_STATUSES))
            ->latest()
            ->paginate($this->perPage);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.products.index')" wire:navigate>Products</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ Str::limit($product->name, 32) }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    @assets
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @endassets

    @php $m = $this->metrics(); $inv = $this->inventory(); $rev = $this->reviewStats(); @endphp

    {{-- Header --}}
    <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Product analytics</flux:heading>
            <flux:subheading>Performance for this product · {{ $this->periodLabel() }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative" wire:ignore x-data="rangePicker(@js($dateFrom), @js($dateTo))">
                <flux:icon.calendar-days
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-zinc-400" />
                <input x-ref="input" type="text" readonly placeholder="Custom range"
                    class="w-56 cursor-pointer rounded-lg border border-zinc-200 bg-white py-1.5 pr-3 pl-8 text-sm text-zinc-700 transition-colors hover:border-zinc-400 focus:ring-2 focus:ring-zinc-300 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300" />
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-4 lg:items-start"
        x-data="productAnalytics(@js($this->chartData()))"
        @product-analytics-updated.window="update($event.detail.charts)">

        {{-- Left: product profile --}}
        <div class="lg:col-span-1">
            <flux:card class="overflow-hidden p-0">
                <div class="space-y-5 px-6 pb-6 pt-5">
                    <div class="flex flex-col items-center gap-3">
                        <div class="size-28 overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-700 dark:bg-zinc-800">
                            @if ($product->cover_url)
                                <img src="{{ $product->cover_url }}" alt="{{ $product->name }}"
                                    class="size-full object-contain" />
                            @else
                                <flux:icon.photo class="m-auto mt-7 size-10 text-zinc-300" />
                            @endif
                        </div>
                        <div class="text-center">
                            <div class="text-base font-semibold dark:text-white">{{ $product->name }}</div>
                            <div class="mt-0.5 font-mono text-xs text-zinc-400">{{ $product->sku ?: '—' }}</div>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-center gap-1.5">
                        <flux:badge size="sm" :color="$product->status->badgeColor()">{{ $product->status->label() }}</flux:badge>
                        <flux:badge size="sm" :color="$product->stock_status === \App\Enums\StockStatus::IN_STOCK ? 'green' : ($product->stock_status === \App\Enums\StockStatus::OUT_OF_STOCK ? 'red' : 'amber')">
                            {{ $product->stock_status->label() }}
                        </flux:badge>
                    </div>

                    {{-- Pricing --}}
                    <div class="space-y-2 border-t border-zinc-100 pt-4 text-sm dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">Price</span>
                            <span class="font-medium tabular-nums dark:text-white">{!! money($product->sale_price ?? $product->price) !!}</span>
                        </div>
                        @if ($product->sale_price)
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500">Regular</span>
                                <span class="tabular-nums text-zinc-400 line-through">{!! money($product->price) !!}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">Cost</span>
                            <span class="tabular-nums {{ $product->cost_price ? 'dark:text-white' : 'text-zinc-400' }}">
                                {{ $product->cost_price ? money($product->cost_price) : 'Not set' }}
                            </span>
                        </div>
                    </div>

                    {{-- Brand / category --}}
                    <div class="space-y-1.5 border-t border-zinc-100 pt-4 text-sm dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">Brand</span>
                            <span class="dark:text-zinc-300">{{ $product->brand?->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">Category</span>
                            <span class="dark:text-zinc-300">{{ $product->primaryCategory?->name ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:button size="sm" icon="pencil-square" class="flex-1"
                        :href="route('admin.products.edit', $product)" wire:navigate>Edit</flux:button>
                    <flux:button size="sm" icon="arrow-top-right-on-square" class="flex-1"
                        :href="route('product.show', $product)" target="_blank">View on store</flux:button>
                    <flux:button size="sm" variant="ghost" icon="clock" tooltip="Activity log"
                        :href="route('admin.activity.item', ['product', $product->id])" wire:navigate />
                </div>
            </flux:card>
        </div>

        {{-- Right: KPIs + charts + tables --}}
        <div class="space-y-5 lg:col-span-3">

            {{-- KPI cards --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @php
                    $kpis = [
                        ['label' => 'Units sold', 'value' => number_format($m['units']), 'trend' => $m['units_trend'], 'icon' => 'shopping-cart', 'tone' => 'text-blue-400'],
                        ['label' => 'Revenue', 'value' => money($m['revenue_cents']), 'trend' => $m['revenue_trend'], 'icon' => 'banknotes', 'tone' => 'text-emerald-400'],
                        ['label' => 'Views', 'value' => number_format($m['views']), 'trend' => $m['views_trend'], 'icon' => 'eye', 'tone' => 'text-violet-400'],
                    ];
                @endphp
                @foreach ($kpis as $kpi)
                    <flux:card class="space-y-1">
                        <div class="flex items-center justify-between">
                            <flux:text size="sm">{{ $kpi['label'] }}</flux:text>
                            <flux:icon :name="$kpi['icon']" class="size-5 {{ $kpi['tone'] }}" />
                        </div>
                        <div class="text-2xl font-semibold tabular-nums dark:text-white">{!! $kpi['value'] !!}</div>
                        @if ($kpi['trend'] !== null)
                            <div class="flex items-center gap-1 text-xs {{ $kpi['trend'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                <flux:icon :name="$kpi['trend'] >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" variant="micro" class="size-3.5" />
                                {{ abs($kpi['trend']) }}% vs prev.
                            </div>
                        @else
                            <div class="text-xs text-zinc-400">No prior data</div>
                        @endif
                    </flux:card>
                @endforeach

                {{-- Margin card --}}
                <flux:card class="space-y-1">
                    <div class="flex items-center justify-between">
                        <flux:text size="sm">Gross margin</flux:text>
                        <flux:icon name="chart-pie" class="size-5 text-amber-400" />
                    </div>
                    @if ($m['has_cost'])
                        <div class="text-2xl font-semibold tabular-nums dark:text-white">{!! money($m['margin_cents']) !!}</div>
                        <div class="text-xs text-zinc-400">{{ $m['margin_pct'] !== null ? $m['margin_pct'] . '% margin' : '—' }}</div>
                    @else
                        <div class="text-2xl font-semibold text-zinc-300">—</div>
                        <div class="text-xs text-amber-500">Set a cost price</div>
                    @endif
                </flux:card>
            </div>

            {{-- Conversion strip --}}
            <flux:card class="flex flex-wrap items-center justify-around gap-4 py-4">
                <div class="text-center">
                    <div class="text-xl font-semibold tabular-nums dark:text-white">{{ $m['conversion_pct'] !== null ? $m['conversion_pct'] . '%' : '—' }}</div>
                    <flux:text size="sm">View → purchase</flux:text>
                </div>
                <div class="h-8 w-px bg-zinc-200 dark:bg-zinc-700"></div>
                <div class="text-center">
                    <div class="text-xl font-semibold tabular-nums dark:text-white">{{ number_format($m['orders']) }}</div>
                    <flux:text size="sm">Orders (paid)</flux:text>
                </div>
                <div class="h-8 w-px bg-zinc-200 dark:bg-zinc-700"></div>
                <div class="text-center">
                    <div class="text-xl font-semibold tabular-nums dark:text-white">{{ number_format($inv['lifetime_units']) }}</div>
                    <flux:text size="sm">Lifetime units</flux:text>
                </div>
                <div class="h-8 w-px bg-zinc-200 dark:bg-zinc-700"></div>
                <div class="text-center">
                    <div class="text-xl font-semibold tabular-nums dark:text-white">{{ $rev['average'] ?? '—' }}</div>
                    <flux:text size="sm">Avg rating ({{ $rev['total'] }})</flux:text>
                </div>
            </flux:card>

            {{-- Sales trend --}}
            <flux:card class="overflow-hidden p-0">
                <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm" class="uppercase tracking-wide">Units &amp; revenue</flux:heading>
                </div>
                <div class="p-4">
                    <div wire:ignore x-ref="sales"></div>
                </div>
            </flux:card>

            {{-- Inventory + reviews --}}
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <flux:card class="overflow-hidden p-0 lg:col-span-2">
                    <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:heading size="sm" class="uppercase tracking-wide">Inventory health</flux:heading>
                    </div>
                    <div class="grid grid-cols-2 gap-4 p-6 sm:grid-cols-4">
                        <div>
                            <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $inv['stock_quantity'] ?? '—' }}</div>
                            <flux:text size="sm">In stock</flux:text>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $inv['low_stock_threshold'] ?? '—' }}</div>
                            <flux:text size="sm">Low threshold</flux:text>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $inv['daily_rate'] }}</div>
                            <flux:text size="sm">Units / day</flux:text>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold tabular-nums {{ $inv['days_cover'] !== null && $inv['days_cover'] < 14 ? 'text-amber-500' : 'dark:text-white' }}">
                                {{ $inv['days_cover'] ?? '∞' }}
                            </div>
                            <flux:text size="sm">Days of cover</flux:text>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="overflow-hidden p-0">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:heading size="sm" class="uppercase tracking-wide">Reviews</flux:heading>
                        @if ($rev['pending'] > 0)
                            <flux:badge size="sm" color="amber">{{ $rev['pending'] }} pending</flux:badge>
                        @endif
                    </div>
                    <div class="p-4">
                        <div class="relative flex items-center justify-center">
                            <div wire:ignore x-ref="reviews" class="w-full"></div>
                            <div class="pointer-events-none absolute flex flex-col items-center">
                                @if ($rev['average'])
                                    <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $rev['average'] }}</span>
                                    <span class="text-[10px] text-zinc-400">of 5 · {{ $rev['total'] }}</span>
                                @else
                                    <span class="text-xs text-zinc-400">No reviews</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </flux:card>
            </div>

            {{-- Recent orders --}}
            <flux:card class="overflow-hidden p-0">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm" class="uppercase tracking-wide">Orders with this product</flux:heading>
                    <flux:select wire:model.live="perPage" class="w-28">
                        <flux:select.option value="10">10 / page</flux:select.option>
                        <flux:select.option value="25">25 / page</flux:select.option>
                        <flux:select.option value="50">50 / page</flux:select.option>
                    </flux:select>
                </div>
                <flux:table
                    container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                    <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                        <flux:table.column>Order</flux:table.column>
                        <flux:table.column>Customer</flux:table.column>
                        <flux:table.column align="end">Qty</flux:table.column>
                        <flux:table.column align="end">Line total</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column align="end">Placed</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->orderItems as $item)
                            <flux:table.row :key="$item->id">
                                <flux:table.cell variant="strong"><span class="font-mono">{{ $item->order->order_number }}</span></flux:table.cell>
                                <flux:table.cell class="text-zinc-600 dark:text-zinc-300">{{ $item->order->user?->name ?? '—' }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $item->quantity }}</flux:table.cell>
                                <flux:table.cell align="end" class="font-medium tabular-nums">{!! money($item->line_total_cents) !!}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" inset="top bottom" :color="$item->order->status->badgeColor()">
                                        {{ $item->order->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-500">{{ $item->order->created_at->format('M j, Y') }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button size="xs" variant="ghost" icon="eye" tooltip="View order"
                                        :href="route('admin.orders.show', $item->order)" wire:navigate />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="py-12 text-center text-zinc-400">
                                    No paid orders contain this product yet.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
                @if ($this->orderItems->hasPages())
                    <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        {{ $this->orderItems->links() }}
                    </div>
                @endif
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
                        // Pass Date objects (not Y-m-d strings) so flatpickr doesn't try to
                        // parse them with the display dateFormat above — otherwise the input
                        // renders empty and the active default range isn't shown.
                        defaultDate: [new Date(from + 'T00:00:00'), new Date(to + 'T00:00:00')],
                        maxDate: 'today',
                        onClose: (dates) => {
                            if (dates.length === 2) {
                                this.$wire.set('dateFrom', this.fp.formatDate(dates[0], 'Y-m-d'));
                                this.$wire.set('dateTo', this.fp.formatDate(dates[1], 'Y-m-d'));
                                this.$wire.applyCustom();
                            }
                        },
                    });

                    // Keep the picker's display in sync if the range changes server-side.
                    this.$wire.$watch('dateTo', () => {
                        this.fp.setDate([
                            new Date(this.$wire.dateFrom + 'T00:00:00'),
                            new Date(this.$wire.dateTo + 'T00:00:00'),
                        ], false);
                    });
                },
            }));

            Alpine.data('productAnalytics', (initial) => ({
                charts: {},

                init() {
                    if (typeof ApexCharts === 'undefined') {
                        return;
                    }
                    this.render(initial);
                },

                money(v) {
                    return 'KES ' + Number(v || 0).toLocaleString();
                },

                salesOptions(d) {
                    return {
                        chart: { type: 'area', height: 300, fontFamily: 'inherit', toolbar: { show: false } },
                        series: [
                            { name: 'Revenue', type: 'area', data: d.revenue },
                            { name: 'Units', type: 'line', data: d.units },
                        ],
                        colors: ['#0d9488', '#7c3aed'],
                        stroke: { curve: 'smooth', width: [2, 2] },
                        fill: { type: ['gradient', 'solid'], gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
                        dataLabels: { enabled: false },
                        xaxis: { categories: d.labels, tickAmount: 8, labels: { rotate: 0, hideOverlappingLabels: true } },
                        yaxis: [
                            { seriesName: 'Revenue', labels: { formatter: (v) => this.money(Math.round(v)) } },
                            { seriesName: 'Units', opposite: true, labels: { formatter: (v) => Math.round(v) } },
                        ],
                        tooltip: { y: { formatter: (v, o) => o.seriesIndex === 0 ? this.money(v) : v } },
                        legend: { position: 'top', horizontalAlign: 'right' },
                        noData: { text: 'No sales in this period' },
                    };
                },

                reviewsOptions(d) {
                    return {
                        chart: { type: 'donut', height: 220, fontFamily: 'inherit' },
                        series: d.reviewTotal > 0 ? d.reviews : [1],
                        labels: ['5★', '4★', '3★', '2★', '1★'],
                        colors: d.reviewTotal > 0
                            ? ['#10b981', '#3b82f6', '#f59e0b', '#f97316', '#f43f5e']
                            : ['#e4e4e7'],
                        plotOptions: { pie: { donut: { size: '72%' } } },
                        legend: { position: 'bottom' },
                        dataLabels: { enabled: false },
                        tooltip: { enabled: d.reviewTotal > 0 },
                    };
                },

                render(d) {
                    this.charts.sales = new ApexCharts(this.$refs.sales, this.salesOptions(d));
                    this.charts.sales.render();

                    this.charts.reviews = new ApexCharts(this.$refs.reviews, this.reviewsOptions(d));
                    this.charts.reviews.render();
                },

                update(d) {
                    this.charts.sales?.updateOptions({
                        series: [
                            { name: 'Revenue', type: 'area', data: d.revenue },
                            { name: 'Units', type: 'line', data: d.units },
                        ],
                        xaxis: { categories: d.labels },
                    });
                },
            }));
        </script>
    @endscript
</div>
