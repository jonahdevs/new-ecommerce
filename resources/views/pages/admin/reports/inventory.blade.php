<?php

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;

new #[Title('Inventory Health')] class extends Component {

    public int $deadStockDays = 90;

    public function updatedDeadStockDays(): void
    {
        $this->clearComputedCache();
    }

    private function clearComputedCache(): void
    {
        unset(
            $this->kpiStats,
            $this->stockVelocity,
            $this->deadStock,
            $this->reorderCandidates,
        );
    }

    /** @return array{out_of_stock: int, low_stock: int, dead_stock_count: int, total_stock_value: float} */
    #[Computed]
    public function kpiStats(): array
    {
        $outOfStock = Product::where('manage_stock', true)
            ->where(fn ($q) => $q->where('stock_status', 'out_of_stock')->orWhere('stock_quantity', '<=', 0))
            ->count();

        $lowStock = Product::where('manage_stock', true)
            ->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        $activeProductIds = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', now()->subDays($this->deadStockDays))
            ->whereNotNull('order_items.product_id')
            ->pluck('order_items.product_id')
            ->unique();

        $deadStockCount = Product::where('manage_stock', true)
            ->where('stock_quantity', '>', 0)
            ->whereNotIn('id', $activeProductIds)
            ->count();

        $totalStockValue = (float) (Product::where('manage_stock', true)
            ->where('stock_quantity', '>', 0)
            ->where(fn($q) => $q->whereNotNull('sale_price')->orWhereNotNull('price'))
            ->selectRaw('SUM(stock_quantity * COALESCE(sale_price, price)) as total_value')
            ->value('total_value') ?? 0);

        return compact('outOfStock', 'lowStock', 'deadStockCount', 'totalStockValue');
    }

    #[Computed]
    public function stockVelocity(): \Illuminate\Support\Collection
    {
        $weeks = max(1, $this->deadStockDays / 7);

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.created_at', '>=', now()->subDays($this->deadStockDays))
            ->whereNotNull('order_items.product_id')
            ->whereNull('products.deleted_at')
            ->selectRaw('
                products.id,
                products.name,
                products.sku,
                products.stock_quantity,
                COALESCE(products.sale_price, products.price) as price,
                SUM(order_items.quantity) as units_sold
            ')
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.stock_quantity', 'products.sale_price', 'products.price')
            ->orderByDesc('units_sold')
            ->limit(15)
            ->get()
            ->map(function ($row) use ($weeks) {
                $row->units_per_week = round($row->units_sold / $weeks, 1);
                $row->weeks_remaining = $row->units_per_week > 0
                    ? round($row->stock_quantity / $row->units_per_week, 1)
                    : null;

                return $row;
            });
    }

    #[Computed]
    public function deadStock(): \Illuminate\Support\Collection
    {
        $activeProductIds = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', now()->subDays($this->deadStockDays))
            ->whereNotNull('order_items.product_id')
            ->pluck('order_items.product_id')
            ->unique();

        return Product::where('manage_stock', true)
            ->where('stock_quantity', '>', 0)
            ->whereNotIn('id', $activeProductIds)
            ->selectRaw('id, name, sku, stock_quantity, COALESCE(sale_price, price) as price, cost_price')
            ->orderByDesc('stock_quantity')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function reorderCandidates(): \Illuminate\Support\Collection
    {
        return $this->stockVelocity
            ->filter(fn ($row) => $row->weeks_remaining !== null && $row->weeks_remaining < 4)
            ->sortBy('weeks_remaining')
            ->values();
    }

    public function exportDeadStock(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'dead-stock-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return Response::streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'SKU', 'Stock Qty', 'Unit Price (KES)', 'Stock Value (KES)']);

            foreach ($this->deadStock as $row) {
                fputcsv($handle, [
                    $row->name,
                    $row->sku ?? '-',
                    $row->stock_quantity,
                    number_format((float) $row->price, 2),
                    number_format($row->stock_quantity * (float) ($row->price ?? 0), 2),
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }
};

?>

<div>
    {{-- Breadcrumb --}}
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Reports</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Inventory Health</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Page header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <flux:heading size="xl">Inventory Health</flux:heading>
            <flux:subheading>Stock levels, velocity, dead stock, and reorder candidates</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:icon.loading wire:loading wire:target="updatedDeadStockDays" class="size-3.5 text-zinc-400" />
            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Analysis period:</flux:text>
            <flux:select wire:model.live="deadStockDays" class="w-36">
                <flux:select.option value="30">Last 30 days</flux:select.option>
                <flux:select.option value="60">Last 60 days</flux:select.option>
                <flux:select.option value="90">Last 90 days</flux:select.option>
                <flux:select.option value="180">Last 180 days</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- KPI CARDS                                                            --}}
    {{-- ================================================================== --}}
    @php $kpi = $this->kpiStats; @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        <flux:card class="p-4">
            <div class="flex items-start justify-between mb-3">
                <flux:text class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">Out of Stock</flux:text>
                <div class="w-9 h-9 rounded-lg bg-red-50 dark:bg-red-950/50 flex items-center justify-center shrink-0">
                    <flux:icon.x-circle class="size-4 text-red-600 dark:text-red-400" />
                </div>
            </div>
            <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5"
                x-data="countUp({ to: {{ $kpi['outOfStock'] }} })" x-text="display">
            </flux:heading>
            <flux:text class="text-[10px] text-red-500 dark:text-red-400">Products with no available stock</flux:text>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-start justify-between mb-3">
                <flux:text class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">Low Stock</flux:text>
                <div class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="size-4 text-amber-600 dark:text-amber-400" />
                </div>
            </div>
            <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5"
                x-data="countUp({ to: {{ $kpi['lowStock'] }} })" x-text="display">
            </flux:heading>
            <flux:text class="text-[10px] text-amber-500 dark:text-amber-400">At or below reorder threshold</flux:text>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-start justify-between mb-3">
                <flux:text class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">Dead Stock</flux:text>
                <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-700/50 flex items-center justify-center shrink-0">
                    <flux:icon.archive-box class="size-4 text-zinc-500 dark:text-zinc-400" />
                </div>
            </div>
            <flux:heading size="xl" class="text-2xl! font-bold! mb-1.5"
                x-data="countUp({ to: {{ $kpi['deadStockCount'] }} })" x-text="display">
            </flux:heading>
            <flux:text class="text-[10px] text-zinc-400">No sales in {{ $deadStockDays }} days</flux:text>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-start justify-between mb-3">
                <flux:text class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">Stock Value</flux:text>
                <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center shrink-0">
                    <flux:icon.banknotes class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="text-xl! font-bold! mb-1.5"
                x-data="countUp({ to: {{ $kpi['totalStockValue'] }}, decimals: 2, prefix: 'KES ' })" x-text="display">
            </flux:heading>
            <flux:text class="text-[10px] text-blue-500 dark:text-blue-400">Total on-hand stock value</flux:text>
        </flux:card>

    </div>

    {{-- ================================================================== --}}
    {{-- STOCK VELOCITY + REORDER CANDIDATES                                 --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        {{-- Stock Velocity Table --}}
        <flux:card class="p-0 lg:col-span-2">
            <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <flux:heading>Stock Velocity</flux:heading>
                <flux:text class="text-[11px] text-zinc-400">Top selling products — units/week and weeks of stock remaining</flux:text>
            </div>

            @if ($this->stockVelocity->isEmpty())
                <div class="flex flex-col items-center justify-center py-12">
                    <flux:icon.chart-bar class="size-8 stroke-1 mb-2 text-zinc-300" />
                    <flux:text class="text-xs text-zinc-400">No sales data in this period</flux:text>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <th class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">#</th>
                                <th class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Product</th>
                                <th class="text-right px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Units Sold</th>
                                <th class="text-right px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Units/Week</th>
                                <th class="text-right px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">In Stock</th>
                                <th class="text-right px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Weeks Left</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($this->stockVelocity as $i => $row)
                                @php
                                    $weeksLeft = $row->weeks_remaining;
                                    $weeksColor = match (true) {
                                        $weeksLeft === null => 'text-zinc-400',
                                        $weeksLeft < 2      => 'font-bold text-red-600 dark:text-red-400',
                                        $weeksLeft < 4      => 'font-semibold text-amber-600 dark:text-amber-400',
                                        default             => 'text-emerald-600 dark:text-emerald-400',
                                    };
                                @endphp
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                                    <td class="px-5 py-3 text-xs text-zinc-400">{{ $i + 1 }}</td>
                                    <td class="px-5 py-3">
                                        <flux:text class="text-xs font-medium text-zinc-800 dark:text-zinc-200">{{ $row->name }}</flux:text>
                                        @if ($row->sku)
                                            <flux:text class="text-[10px] text-zinc-400 block">{{ $row->sku }}</flux:text>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs text-zinc-600 dark:text-zinc-400">{{ number_format($row->units_sold) }}</td>
                                    <td class="px-5 py-3 text-right text-xs text-zinc-600 dark:text-zinc-400">{{ $row->units_per_week }}</td>
                                    <td class="px-5 py-3 text-right text-xs text-zinc-600 dark:text-zinc-400">{{ number_format($row->stock_quantity) }}</td>
                                    <td class="px-5 py-3 text-right text-xs {{ $weeksColor }}">
                                        {{ $weeksLeft !== null ? $weeksLeft : '∞' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </flux:card>

        {{-- Reorder Candidates --}}
        <flux:card class="p-0">
            <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <flux:heading size="sm">Reorder Now</flux:heading>
                    @if ($this->reorderCandidates->isNotEmpty())
                        <flux:badge color="red" size="sm">{{ $this->reorderCandidates->count() }}</flux:badge>
                    @endif
                </div>
                <flux:text class="text-[10px] text-zinc-400">Less than 4 weeks of stock remaining</flux:text>
            </div>

            @if ($this->reorderCandidates->isEmpty())
                <div class="flex flex-col items-center justify-center py-12">
                    <flux:icon.check-circle class="size-8 stroke-1 mb-2 text-emerald-400" />
                    <flux:heading size="sm" class="font-medium! mb-1">All good</flux:heading>
                    <flux:text class="text-xs text-zinc-400">No urgent reorders needed</flux:text>
                </div>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->reorderCandidates as $row)
                        @php
                            $urgency = match (true) {
                                $row->weeks_remaining < 1 => ['bg' => 'bg-red-50 dark:bg-red-900/20',     'badge' => 'red',    'label' => 'Critical'],
                                $row->weeks_remaining < 2 => ['bg' => 'bg-red-50 dark:bg-red-900/20',     'badge' => 'red',    'label' => 'Urgent'],
                                $row->weeks_remaining < 4 => ['bg' => 'bg-amber-50 dark:bg-amber-900/20', 'badge' => 'yellow', 'label' => 'Soon'],
                                default                   => ['bg' => '',                                  'badge' => 'zinc',   'label' => 'Low'],
                            };
                        @endphp
                        <div class="flex items-start justify-between px-5 py-3 {{ $urgency['bg'] }}">
                            <div class="min-w-0 flex-1">
                                <flux:text class="text-xs font-medium text-zinc-800 dark:text-zinc-200 truncate block">{{ $row->name }}</flux:text>
                                <flux:text class="text-[10px] text-zinc-400">
                                    {{ number_format($row->stock_quantity) }} left · {{ $row->units_per_week }}/wk
                                </flux:text>
                            </div>
                            <div class="ml-3 flex flex-col items-end gap-1 shrink-0">
                                <flux:badge color="{{ $urgency['badge'] }}" size="sm">{{ $urgency['label'] }}</flux:badge>
                                <flux:text class="text-[10px] font-semibold text-zinc-600 dark:text-zinc-300">{{ $row->weeks_remaining }}w</flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

    </div>

    {{-- ================================================================== --}}
    {{-- DEAD STOCK TABLE                                                     --}}
    {{-- ================================================================== --}}
    <flux:card class="p-0">
        <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
            <div>
                <flux:heading>Dead Stock</flux:heading>
                <flux:text class="text-[11px] text-zinc-400">
                    Products with stock on hand but no sales in the last {{ $deadStockDays }} days
                </flux:text>
            </div>
            @if ($this->deadStock->isNotEmpty())
                <flux:button wire:click="exportDeadStock" icon="arrow-down-tray" variant="ghost" size="sm">
                    Export CSV
                </flux:button>
            @endif
        </div>

        @if ($this->deadStock->isEmpty())
            <div class="flex flex-col items-center justify-center py-12">
                <flux:icon.check-circle class="size-8 stroke-1 mb-2 text-emerald-400" />
                <flux:heading size="sm" class="font-medium! mb-1">No dead stock in this period</flux:heading>
                <flux:text class="text-xs text-zinc-400">All stocked products had sales in the last {{ $deadStockDays }} days</flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Product</th>
                            <th class="text-left px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">SKU</th>
                            <th class="text-right px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Qty in Stock</th>
                            <th class="text-right px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Unit Price</th>
                            <th class="text-right px-5 py-3 text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Stock Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($this->deadStock as $row)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="px-5 py-3">
                                    <flux:text class="text-xs font-medium text-zinc-800 dark:text-zinc-200">{{ $row->name }}</flux:text>
                                </td>
                                <td class="px-5 py-3">
                                    <flux:text class="text-[10px] font-mono text-zinc-400">{{ $row->sku ?? '—' }}</flux:text>
                                </td>
                                <td class="px-5 py-3 text-right text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ number_format($row->stock_quantity) }}
                                </td>
                                <td class="px-5 py-3 text-right text-xs text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                                    @if ($row->price)
                                        KES {{ number_format((float) $row->price, 2) }}
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right text-xs font-semibold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                    @if ($row->price)
                                        KES {{ number_format($row->stock_quantity * (float) $row->price, 2) }}
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-zinc-200 dark:border-zinc-700">
                            <td colspan="4" class="px-5 py-3 text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                                Total Dead Stock Value
                            </td>
                            <td class="px-5 py-3 text-right text-xs font-bold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                KES {{ number_format($this->deadStock->sum(fn ($r) => $r->stock_quantity * (float) ($r->price ?? 0)), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </flux:card>

</div>
