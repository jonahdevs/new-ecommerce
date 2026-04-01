<?php
use App\Models\{Product, Category};
use App\Enums\ProductStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};
use Illuminate\Support\Str;

new #[Title('Products')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $category = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    public int $perPage = 10;

    // Bulk action state
    public string $bulkActionType = '';
    public string $bulkCategoryId = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingStatus(): void
    {
        $this->resetPage();
    }
    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    //  Computed

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    #[Computed]
    public function statuses()
    {
        return ProductStatus::cases();
    }

    #[Computed]
    public function totalProducts()
    {
        return Product::count();
    }

    #[Computed]
    public function publishedProducts()
    {
        return Product::where('status', ProductStatus::PUBLISHED)->count();
    }

    #[Computed]
    public function draftProducts()
    {
        return Product::where('status', ProductStatus::DRAFT)->count();
    }

    #[Computed]
    public function lowStockProducts()
    {
        return Product::where(function ($q) {
            $q->where('manage_stock', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        })->count();
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with(['categories' => fn($q) => $q->where('is_primary', true)])
            ->withCount('orderItems')
            ->withAvg('reviews', 'rating')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%"))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->category, fn($q) => $q->whereHas('categories', fn($q) => $q->where('categories.id', $this->category)))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    //  Sorting

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    //  Row Actions

    public function trash(int $id): void
    {
        Product::findOrFail($id)->delete();
        $this->dispatch('notify', variant: 'success', message: 'Product moved to trash.');
    }

    public function duplicate(int $id): void
    {
        $original = Product::with(['categories', 'images'])->findOrFail($id);

        $copy = $original->replicate();
        $copy->name = $original->name . ' (Copy)';
        $copy->slug = Str::slug($original->name . ' copy ' . Str::random(4));
        $copy->status = ProductStatus::DRAFT;
        $copy->sku = $original->sku ? $original->sku . '-copy-' . Str::random(3) : null;
        $copy->published_at = null;
        $copy->views_count = 0;
        $copy->sales_count = 0;
        $copy->save();

        // Copy category relationships preserving pivot data
        $categoryData = $original->categories
            ->mapWithKeys(
                fn($cat) => [
                    $cat->id => [
                        'is_primary' => $cat->pivot->is_primary,
                        'sort_order' => $cat->pivot->sort_order,
                    ],
                ],
            )
            ->toArray();
        $copy->categories()->sync($categoryData);

        $this->dispatch('notify', variant: 'success', message: 'Product duplicated as draft.');
        $this->redirectRoute('admin.catalog.products.edit', $copy, navigate: true);
    }

    public function setStatus(int $id, string $status): void
    {
        $product = Product::findOrFail($id);
        $product->update([
            'status' => $status,
            'published_at' => $status === ProductStatus::PUBLISHED->value ? now() : $product->published_at,
        ]);
        $this->dispatch('notify', variant: 'success', message: "Status updated to {$product->fresh()->status->label()}.");
    }

    //  Bulk Actions

    public function executeBulkAction(string $action, array $ids, string $categoryId = ''): void
    {
        if (empty($ids)) {
            return;
        }

        $products = Product::whereIn('id', $ids);

        match ($action) {
            'publish' => $products->update(['status' => ProductStatus::PUBLISHED->value, 'published_at' => now()]),
            'unpublish' => $products->update(['status' => ProductStatus::DRAFT->value]),
            'archive' => $products->update(['status' => ProductStatus::ARCHIVED->value]),
            'trash' => $products->each(fn($p) => $p->delete()),
            'category' => $this->bulkAssignCategory($ids, $categoryId),
            default => null,
        };

        unset($this->products);
        $this->dispatch('notify', variant: 'success', message: count($ids) . ' products updated successfully.');
    }

    private function bulkAssignCategory(array $ids, string $categoryId): void
    {
        if (!$categoryId) {
            return;
        }

        Product::whereIn('id', $ids)
            ->with('categories')
            ->get()
            ->each(function ($product) use ($categoryId) {
                $product->categories()->syncWithoutDetaching([
                    $categoryId => ['is_primary' => false, 'sort_order' => 0],
                ]);
            });
    }

    public function rendered(): void
    {
        $this->dispatch('products-refreshed', ids: $this->products->pluck('id')->toArray());
    }
};
?>

<div x-data="{
    selected: [],
    allIds: @js($this->products->pluck('id')->toArray()),

    get allSelected() {
        return this.allIds.length > 0 && this.allIds.every(id => this.selected.includes(id));
    },
    get someSelected() {
        return this.selected.length > 0 && !this.allSelected;
    },
    toggleAll() {
        this.selected = this.allSelected ? [] : [...this.allIds];
    },
    toggle(id) {
        this.selected.includes(id) ?
            this.selected = this.selected.filter(i => i !== id) :
            this.selected.push(id);
    },
    isSelected(id) {
        return this.selected.includes(id);
    },
    clearSelection() {
        this.selected = [];
    },
    runBulkAction(action, categoryId = '') {
        if (this.selected.length === 0) return;
        $wire.executeBulkAction(action, this.selected, categoryId);
        this.clearSelection();
    },
    columns: JSON.parse(localStorage.getItem('products_columns') ?? 'null') ?? {
        category: true,
        stock: true,
        price: true,
        orders: true,
        rating: true,
        status: true,
    },
    toggleColumn(col) {
        this.columns[col] = !this.columns[col];
        localStorage.setItem('products_columns', JSON.stringify(this.columns));
    },
}"
    @products-refreshed.window="
        allIds = $event.detail.ids;
        selected = selected.filter(id => allIds.includes(id));
    ">

    {{-- Breadcrumb --}}
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Products</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="mb-1">Products</flux:heading>
            <flux:subheading>
                Manage your product catalog, inventory, and availability.
            </flux:subheading>
        </div>
        <flux:button href="{{ route('admin.catalog.products.create') }}" variant="primary" icon="plus-circle"
            wire:navigate>
            Create Product
        </flux:button>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="uppercase text-xs font-medium mb-3">Total Products</flux:text>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-50">{{ $this->totalProducts }}</p>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900 rounded-lg">
                    <flux:icon.inbox class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="uppercase text-xs font-medium mb-3">Published</flux:text>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $this->publishedProducts }}</p>
                </div>
                <div class="p-3 bg-green-50 dark:bg-green-900 rounded-lg">
                    <flux:icon.check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="uppercase text-xs font-medium mb-3">Draft</flux:text>
                    <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $this->draftProducts }}</p>
                </div>
                <div class="p-3 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
                    <flux:icon.pencil-square class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="uppercase text-xs font-medium mb-3">Low Stock</flux:text>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $this->lowStockProducts }}</p>
                </div>
                <div class="p-3 bg-red-50 dark:bg-red-900 rounded-lg">
                    <flux:icon.exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
            </div>
        </flux:card>

    </div>

    {{-- Main card --}}
    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">

        {{-- Toolbar --}}
        <div
            class="flex flex-wrap items-center gap-3 px-5 py-3 border-b dark:border-zinc-600 border-zinc-200 dark:border-zinc-600">

            {{-- Search --}}
            <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Search by name or SKU..."
                class="max-w-xs" clearable />

            {{-- Filters --}}
            <div class="flex items-center gap-2 ms-auto flex-wrap">

                <flux:select wire:model.live="status" class="w-36">
                    <flux:select.option value="">All Status</flux:select.option>
                    @foreach ($this->statuses as $s)
                        <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="category" class="w-44">
                    <flux:select.option value="">All Categories</flux:select.option>
                    @foreach ($this->categories as $cat)
                        <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-24">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>

                {{-- Column visibility --}}
                <flux:dropdown>
                    <flux:button icon="view-columns" variant="ghost" size="sm">
                        Columns
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item @click.prevent="toggleColumn('category')">
                            <span class="flex items-center gap-2">
                                <span x-text="columns.category ? '✓' : ''" class="w-4 text-green-600 font-bold"></span>
                                Category
                            </span>
                        </flux:menu.item>
                        <flux:menu.item @click.prevent="toggleColumn('stock')">
                            <span class="flex items-center gap-2">
                                <span x-text="columns.stock ? '✓' : ''" class="w-4 text-green-600 font-bold"></span>
                                Stock
                            </span>
                        </flux:menu.item>
                        <flux:menu.item @click.prevent="toggleColumn('price')">
                            <span class="flex items-center gap-2">
                                <span x-text="columns.price ? '✓' : ''" class="w-4 text-green-600 font-bold"></span>
                                Price
                            </span>
                        </flux:menu.item>
                        <flux:menu.item @click.prevent="toggleColumn('orders')">
                            <span class="flex items-center gap-2">
                                <span x-text="columns.orders ? '✓' : ''" class="w-4 text-green-600 font-bold"></span>
                                Orders
                            </span>
                        </flux:menu.item>
                        <flux:menu.item @click.prevent="toggleColumn('rating')">
                            <span class="flex items-center gap-2">
                                <span x-text="columns.rating ? '✓' : ''" class="w-4 text-green-600 font-bold"></span>
                                Rating
                            </span>
                        </flux:menu.item>
                        <flux:menu.item @click.prevent="toggleColumn('status')">
                            <span class="flex items-center gap-2">
                                <span x-text="columns.status ? '✓' : ''" class="w-4 text-green-600 font-bold"></span>
                                Status
                            </span>
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

            </div>
        </div>

        {{-- Bulk action bar — slides in when rows are selected --}}
        <div x-cloak x-show="selected.length > 0" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="flex flex-wrap items-center gap-2 px-5 py-2.5 bg-zinc-50 dark:bg-zinc-800 border-b dark:border-zinc-600 border-zinc-200 dark:border-zinc-600">
            {{-- Count --}}
            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 me-1">
                <span x-text="selected.length"></span> selected
            </span>

            {{-- Publish --}}
            <flux:button size="sm" variant="ghost" icon="check-circle" icon-variant="outline"
                class="cursor-pointer" @click="runBulkAction('publish')">
                Publish
            </flux:button>

            {{-- Unpublish --}}
            <flux:button size="sm" variant="ghost" icon="pencil-square" icon-variant="outline"
                class="cursor-pointer" @click="runBulkAction('unpublish')">
                Unpublish
            </flux:button>

            {{-- Archive --}}
            <flux:button size="sm" variant="ghost" icon="archive-box" icon-variant="outline"
                class="cursor-pointer" @click="runBulkAction('archive')">
                Archive
            </flux:button>

            {{-- Assign Category --}}
            <flux:dropdown>
                <flux:button size="sm" variant="ghost" icon="tag" icon-variant="outline"
                    class="cursor-pointer">
                    Assign Category
                </flux:button>
                <flux:menu class="max-h-60 overflow-y-auto">
                    @foreach ($this->categories as $cat)
                        <flux:menu.item @click="runBulkAction('category', '{{ $cat->id }}')">
                            {{ $cat->name }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>

            {{-- Trash --}}
            <flux:button size="sm" variant="ghost" icon="trash" icon-variant="outline"
                class="text-red-500! ms-auto cursor-pointer"
                @click="
                    if (confirm('Move ' + selected.length + ' product(s) to trash?')) {
                        runBulkAction('trash')
                    }
                ">
                Move to Trash
            </flux:button>

            {{-- Clear --}}
            <flux:button size="sm" variant="ghost" icon="x-mark" icon-variant="outline"
                class="cursor-pointer" @click="clearSelection()">
                Clear
            </flux:button>
        </div>

        {{-- Table --}}
        <flux:table :paginate="$this->products">
            <flux:table.columns>

                {{-- Select all checkbox --}}
                <flux:table.column class="w-10 ps-4!">
                    <flux:checkbox x-ref="selectAll"
                        x-effect="$refs.selectAll.querySelector('input').indeterminate = someSelected"
                        ::checked="allSelected" @change="toggleAll()" />
                </flux:table.column>

                {{-- Image --}}
                <flux:table.column class="w-12 ps-2!">
                    <span class="sr-only">Image</span>
                </flux:table.column>

                {{-- Product --}}
                <flux:table.column sortable :sorted="$this->sortBy === 'name'" :direction="$this->sortDirection"
                    wire:click="sort('name')">
                    Product
                </flux:table.column>

                {{-- Category --}}
                <flux:table.column x-show="columns.category">Category</flux:table.column>

                {{-- Stock --}}
                <flux:table.column x-show="columns.stock" sortable :sorted="$this->sortBy === 'stock_quantity'"
                    :direction="$this->sortDirection" wire:click="sort('stock_quantity')">
                    Stock
                </flux:table.column>

                {{-- Price --}}
                <flux:table.column x-show="columns.price" sortable :sorted="$this->sortBy === 'price'"
                    :direction="$this->sortDirection" wire:click="sort('price')">
                    Price
                </flux:table.column>

                {{-- Orders --}}
                <flux:table.column x-show="columns.orders">Orders</flux:table.column>

                {{-- Rating --}}
                <flux:table.column x-show="columns.rating">Rating</flux:table.column>

                {{-- Status --}}
                <flux:table.column x-show="columns.status">Status</flux:table.column>

                {{-- Actions --}}
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>

            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->products as $product)
                    <flux:table.row :key="$product->id">

                        {{-- Checkbox --}}
                        <flux:table.cell class="ps-4! w-10">
                            <flux:checkbox ::checked="isSelected({{ $product->id }})"
                                @change="toggle({{ $product->id }})" />
                        </flux:table.cell>

                        {{-- Image --}}
                        <flux:table.cell class="ps-2! w-12">
                            <div
                                class="w-10 h-10 rounded-lg border dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 overflow-hidden">
                                @if ($product->image_path)
                                    <img src="{{ $product->image_url }}" class="object-cover w-full h-full"
                                        alt="{{ $product->name }}">
                                @else
                                    <flux:icon.photo class="w-full h-full p-2 text-zinc-300" />
                                @endif
                            </div>
                        </flux:table.cell>

                        {{-- Product name + SKU --}}
                        <flux:table.cell>
                            <a href="{{ route('admin.catalog.products.edit', $product) }}" wire:navigate
                                class="font-semibold text-zinc-800 dark:text-white hover:text-sheffield-red transition-colors line-clamp-1">
                                {{ $product->name }}
                            </a>
                            <flux:text class="text-xs mt-0.5">SKU: {{ $product->sku ?? '—' }}</flux:text>
                        </flux:table.cell>

                        {{-- Category --}}
                        <flux:table.cell x-show="columns.category">
                            <flux:badge size="sm" variant="outline" color="zinc">
                                {{ $product->primaryCategory()?->name ?? 'Uncategorized' }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Stock --}}
                        <flux:table.cell x-show="columns.stock">
                            @if ($product->manage_stock)
                                <span @class([
                                    'text-sm font-medium',
                                    'text-red-600' => $product->stock_quantity <= $product->low_stock_threshold,
                                    'text-zinc-700 dark:text-zinc-300' =>
                                        $product->stock_quantity > $product->low_stock_threshold,
                                ])>
                                    {{ $product->stock_quantity }}
                                </span>
                                @if ($product->stock_quantity <= $product->low_stock_threshold)
                                    <flux:badge size="sm" color="red" class="ms-1">Low</flux:badge>
                                @endif
                            @else
                                <flux:text class="text-xs text-zinc-400">Unmanaged</flux:text>
                            @endif
                        </flux:table.cell>

                        {{-- Price --}}
                        <flux:table.cell x-show="columns.price">
                            <div class="font-medium text-sm">{{ format_currency($product->price ?? 0) }}</div>
                            @if ($product->sale_price)
                                <div class="text-xs text-green-600">
                                    Sale: {{ format_currency($product->sale_price) }}
                                </div>
                            @endif
                        </flux:table.cell>

                        {{-- Orders --}}
                        <flux:table.cell x-show="columns.orders">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $product->order_items_count }}
                            </span>
                        </flux:table.cell>

                        {{-- Rating --}}
                        <flux:table.cell x-show="columns.rating">
                            <flux:badge icon="star" icon-variant="solid" size="sm"
                                class="**:data-flux-badge-icon:text-yellow-500!">
                                {{ number_format($product->reviews_avg_rating ?? 0, 1) }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Status --}}
                        <flux:table.cell x-show="columns.status">
                            <flux:badge size="sm" variant="flat" :color="$product->status->color()"
                                :icon="$product->status->icon()">
                                {{ $product->status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Actions dropdown --}}
                        <flux:table.cell align="end" class="pe-4!">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                <flux:menu>

                                    {{-- Edit --}}
                                    <flux:menu.item icon="pencil-square" icon-variant="outline"
                                        href="{{ route('admin.catalog.products.edit', $product) }}" wire:navigate>
                                        Edit
                                    </flux:menu.item>

                                    {{-- View on store --}}
                                    <flux:menu.item icon="arrow-top-right-on-square" icon-variant="outline"
                                        href="{{ route('products.show', $product->slug) }}" target="_blank">
                                        View on Store
                                    </flux:menu.item>

                                    {{-- Duplicate --}}
                                    <flux:menu.item icon="document-duplicate" icon-variant="outline"
                                        wire:click="duplicate({{ $product->id }})">
                                        Duplicate
                                    </flux:menu.item>

                                    <flux:menu.separator />

                                    {{-- Quick status change --}}
                                    <flux:menu.submenu heading="Set Status">
                                        @foreach ($this->statuses as $s)
                                            <flux:menu.item
                                                wire:click="setStatus({{ $product->id }}, '{{ $s->value }}')"
                                                :icon="$product->status === $s ? 'check' : $s->icon()"
                                                icon-variant="outline">
                                                {{ $s->label() }}
                                            </flux:menu.item>
                                        @endforeach
                                    </flux:menu.submenu>

                                    <flux:menu.separator />

                                    {{-- Trash --}}
                                    <flux:menu.item icon="trash" variant="danger" icon-variant="outline"
                                        wire:click="trash({{ $product->id }})"
                                        wire:confirm="Move '{{ addslashes($product->name) }}' to trash?">
                                        Move to Trash
                                    </flux:menu.item>

                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>

                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="11" class="text-center py-16">
                            <div class="flex flex-col items-center justify-center text-zinc-400">
                                <flux:icon.cube class="size-12 stroke-1 mb-3" />
                                <flux:text class="font-medium text-zinc-500">No products found</flux:text>
                                <flux:text class="text-xs mt-1">Try adjusting your search or filters</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

    </flux:card>

</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
