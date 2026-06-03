<?php

use App\Enums\ProductStatus;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Products — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterVisibility = '';

    #[Url]
    public string $filterStock = '';

    #[Url]
    public string $sortBy = 'updated_at';

    #[Url]
    public string $sortDirection = 'desc';

    #[Url]
    public int $perPage = 10;

    /** @var array<int, string> */
    public array $selected = [];

    public bool $selectAll = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }
    public function updatedFilterVisibility(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }
    public function updatedFilterStock(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }
    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value
            ? $this->products->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
        $this->clearSelection();
    }

    /** @return array<string, int> */
    #[Computed]
    public function stats(): array
    {
        return [
            'published' => Product::where('status', ProductStatus::PUBLISHED)->count(),
            'draft' => Product::where('status', ProductStatus::DRAFT)->count(),
            'out' => Product::where('stock_status', StockStatus::OUT_OF_STOCK)->count(),
            'low' => Product::whereNotNull('low_stock_threshold')
                ->whereNotNull('stock_quantity')
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->count(),
        ];
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with(['brand', 'primaryCategory', 'images' => fn($q) => $q->where('is_cover', true)->limit(1)])
            ->when(
                $this->search,
                fn($q) => $q->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')->orWhere('sku', 'like', '%' . $this->search . '%');
                }),
            )
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterVisibility, fn($q) => $q->where('visibility', $this->filterVisibility))
            ->when($this->filterStock, fn($q) => $q->where('stock_status', $this->filterStock))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function deleteProduct(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->delete();
        unset($this->products, $this->stats);

        Flux::toast(heading: 'Product deleted', text: $product->name . ' has been removed.', variant: 'success');
    }

    public function bulkSetVisibility(string $visibility): void
    {
        if ($this->selected === [] || ! in_array($visibility, array_column(ProductVisibility::cases(), 'value'), true)) {
            return;
        }

        $count = Product::whereIn('id', $this->selected)->update(['visibility' => $visibility]);
        $this->afterBulk();

        Flux::toast(heading: 'Visibility updated', text: $count . ' product(s) set to ' . ProductVisibility::from($visibility)->label() . '.', variant: 'success');
    }

    public function bulkSetStock(string $status): void
    {
        if ($this->selected === [] || ! in_array($status, array_column(StockStatus::cases(), 'value'), true)) {
            return;
        }

        $count = Product::whereIn('id', $this->selected)->update(['stock_status' => $status]);
        $this->afterBulk();

        Flux::toast(heading: 'Stock updated', text: $count . ' product(s) marked ' . StockStatus::from($status)->label() . '.', variant: 'success');
    }

    public function bulkDelete(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = Product::whereIn('id', $this->selected)->delete();
        $this->afterBulk();

        Flux::toast(heading: 'Products deleted', text: $count . ' product(s) have been removed.', variant: 'success');
    }

    private function afterBulk(): void
    {
        $this->clearSelection();
        unset($this->products, $this->stats);
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
<flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Products</flux:breadcrumbs.item>
            </flux:breadcrumbs>
@endpush
            <flux:heading size="xl">Products</flux:heading>
            <flux:subheading>Manage your catalog — pricing, stock and visibility.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="clock" :href="route('admin.activity.show', 'product')" wire:navigate>
                Activity log
            </flux:button>
            <flux:button variant="primary" icon="plus" :href="route('admin.products.create')" wire:navigate>
                Add product
            </flux:button>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <flux:card class="flex items-center gap-4">
            <flux:icon.check-circle class="size-9 text-emerald-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ number_format($this->stats['published']) }}</div>
                <flux:text size="sm">Published</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.pencil-square class="size-9 text-zinc-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ number_format($this->stats['draft']) }}</div>
                <flux:text size="sm">Drafts</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.exclamation-triangle class="size-9 text-amber-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ number_format($this->stats['low']) }}</div>
                <flux:text size="sm">Low stock</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.x-circle class="size-9 text-red-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ number_format($this->stats['out']) }}</div>
                <flux:text size="sm">Out of stock</flux:text>
            </div>
        </flux:card>
    </div>

    {{-- Card: toolbar + table --}}
    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name or SKU…" icon="magnifying-glass"
                clearable class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterStatus" class="w-36">
                    <flux:select.option value="">All statuses</flux:select.option>
                    @foreach (ProductStatus::cases() as $s)
                        <flux:select.option :value="$s->value">{{ $s->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterVisibility" class="w-40">
                    <flux:select.option value="">All visibility</flux:select.option>
                    @foreach (ProductVisibility::cases() as $v)
                        <flux:select.option :value="$v->value">{{ $v->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterStock" class="w-36">
                    <flux:select.option value="">All stock</flux:select.option>
                    @foreach (StockStatus::cases() as $s)
                        <flux:select.option :value="$s->value">{{ $s->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-28">
                    <flux:select.option value="10">10 / page</flux:select.option>
                    <flux:select.option value="25">25 / page</flux:select.option>
                    <flux:select.option value="50">50 / page</flux:select.option>
                    <flux:select.option value="100">100 / page</flux:select.option>
                    <flux:select.option value="250">250 / page</flux:select.option>
                </flux:select>
            </div>
        </div>

        {{-- Bulk action bar --}}
        @if (count($selected) > 0)
            <div class="flex flex-wrap items-center gap-3 border-b border-zinc-200 bg-brand-50 px-6 py-2.5 dark:border-zinc-700 dark:bg-brand-500/10">
                <flux:text class="font-medium">{{ count($selected) }} selected</flux:text>

                <flux:dropdown>
                    <flux:button size="sm" variant="ghost" icon-trailing="chevron-down">Set visibility</flux:button>
                    <flux:menu>
                        @foreach (ProductVisibility::cases() as $v)
                            <flux:menu.item wire:click="bulkSetVisibility('{{ $v->value }}')">{{ $v->label() }}</flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>

                <flux:dropdown>
                    <flux:button size="sm" variant="ghost" icon-trailing="chevron-down">Set stock</flux:button>
                    <flux:menu>
                        @foreach (StockStatus::cases() as $s)
                            <flux:menu.item wire:click="bulkSetStock('{{ $s->value }}')">{{ $s->label() }}</flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>

                <flux:button size="sm" variant="ghost" icon="trash"
                    wire:click="bulkDelete"
                    wire:confirm="Delete {{ count($selected) }} selected product(s)? This cannot be undone."
                    class="text-red-500! hover:text-red-600!">Delete</flux:button>

                <flux:spacer />

                <flux:button size="sm" variant="ghost" wire:click="clearSelection">Clear</flux:button>
            </div>
        @endif

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column class="w-10">
                    <flux:checkbox wire:model.live="selectAll" />
                </flux:table.column>
                <flux:table.column class="w-14"></flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                    wire:click="sort('name')">Product</flux:table.column>
                <flux:table.column>Brand / Category</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'price'" :direction="$sortDirection"
                    wire:click="sort('price')">Price</flux:table.column>
                <flux:table.column>Stock</flux:table.column>
                <flux:table.column>Visibility</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->products as $product)
                    <flux:table.row :key="$product->id">
                        <flux:table.cell>
                            <flux:checkbox wire:model.live="selected" value="{{ $product->id }}" />
                        </flux:table.cell>
                        <flux:table.cell>
                            <div
                                class="size-10 overflow-hidden rounded border border-zinc-200 bg-zinc-50 p-0.5 dark:border-zinc-700 dark:bg-zinc-800">
                                @if ($product->cover_url)
                                    <img src="{{ $product->cover_url }}" alt=""
                                        class="size-full object-contain" />
                                @else
                                    <flux:icon.photo variant="micro" class="m-auto mt-2 size-5 text-zinc-300" />
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell variant="strong">
                            <a href="{{ route('admin.products.edit', $product) }}" wire:navigate
                                class="hover:text-brand-500">{{ $product->name }}</a>
                            <span
                                class="block font-mono text-xs font-normal text-zinc-400">{{ $product->sku ?: '—' }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-zinc-700 dark:text-zinc-300">{{ $product->brand?->name ?? '—' }}</span>
                            @if ($product->primaryCategory)
                                <span class="block text-xs text-zinc-400">{{ $product->primaryCategory->name }}</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell variant="strong" class="tabular-nums">
                            @if ($product->price)
                                KES {{ number_format(intdiv($product->price, 100), 0, '.', ',') }}
                                @if ($product->sale_price)
                                    <span class="block text-xs font-normal text-emerald-600">
                                        Sale KES {{ number_format(intdiv($product->sale_price, 100), 0, '.', ',') }}
                                    </span>
                                @endif
                            @else
                                <span class="font-normal text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $stockColor = match ($product->stock_status) {
                                    StockStatus::IN_STOCK => 'green',
                                    StockStatus::OUT_OF_STOCK => 'red',
                                    StockStatus::BACKORDER => 'yellow',
                                };
                            @endphp
                            <flux:badge size="sm" :color="$stockColor" inset="top bottom">
                                {{ $product->stock_status->label() }}
                            </flux:badge>
                            @if ($product->stock_quantity !== null)
                                <span class="ml-1 text-xs text-zinc-400">{{ $product->stock_quantity }}</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $visColor = match ($product->visibility) {
                                    ProductVisibility::VISIBLE => 'green',
                                    ProductVisibility::HIDDEN => 'zinc',
                                    default => 'yellow',
                                };
                            @endphp
                            <flux:badge size="sm" :color="$visColor" inset="top bottom">
                                {{ $product->visibility->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:tooltip content="Activity log">
                                    <flux:button size="xs" variant="ghost" icon="clock"
                                        :href="route('admin.activity.item', ['product', $product->id])" wire:navigate />
                                </flux:tooltip>
                                <flux:button size="xs" variant="ghost" icon="pencil-square"
                                    :href="route('admin.products.edit', $product)" wire:navigate />
                                <flux:button size="xs" variant="ghost" icon="trash"
                                    wire:click="deleteProduct({{ $product->id }})"
                                    wire:confirm="Delete '{{ addslashes($product->name) }}'? This cannot be undone."
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="py-16 text-center text-zinc-400">
                            @if ($search || $filterVisibility || $filterStock)
                                No products match your filters.
                            @else
                                No products yet.
                                <a href="{{ route('admin.products.create') }}" wire:navigate
                                    class="ml-1 text-brand-500 hover:underline">Add your first product</a>.
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->products->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->products" />
            </div>
        @endif
    </flux:card>
</div>
