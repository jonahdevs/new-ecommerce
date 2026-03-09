<?php
use App\Models\{Product, Category};
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};
use App\Enums\ProductStatus;

new #[Title('Products')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $category = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

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
        return Product::where('status', 'published')->count();
    }

    #[Computed]
    public function draftProducts()
    {
        return Product::where('status', 'draft')->count();
    }

    #[Computed]
    public function lowStockProducts()
    {
        return Product::where(function ($query) {
            $query->where('manage_stock', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        })->count();
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with([
                'categories' => fn($q) => $q->where('is_primary', true),
            ])
            ->withCount('orderItems')
            ->withAvg('reviews', 'rating')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%"))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->category, fn($q) => $q->whereHas('categories', fn($q) => $q->where('categories.id', $this->category)))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    public function delete($id)
    {
        $product = Product::findOrFail($id);
        // Add logic here to clean up images from storage if necessary
        $product->delete();
        session()->flash('status', 'Product moved to trash.');
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
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Products</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="mb-1">Products</flux:heading>
            <flux:subheading>Organize your products, manage inventory, and control product details and availability.
            </flux:subheading>
        </div>

        <flux:button href="{{ route('admin.products.create') }}" variant="primary" icon="plus-circle" wire:navigate>
            Create Product
        </flux:button>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        {{-- Total Products --}}
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="uppercase text-xs font-medium mb-3">Total Products</flux:text>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-50 mt-1">{{ $this->totalProducts }}</p>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg dark:bg-blue-800">
                    <flux:icon.inbox class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </flux:card>

        {{-- Published Products --}}
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="uppercase text-xs font-medium mb-3">Published</flux:text>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $this->publishedProducts }}
                    </p>
                </div>
                <div class="p-3 bg-green-50 rounded-lg dark:bg-green-800 ">
                    <flux:icon.check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </flux:card>

        {{-- Draft Products --}}
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="uppercase text-xs font-medium mb-3">Draft</flux:text>
                    <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $this->draftProducts }}
                    </p>
                </div>
                <div class="p-3 bg-yellow-50 dark:bg-yellow-800 rounded-lg">
                    <flux:icon.check-circle class="w-6 h-6 text-yellow-600 dark:text-yellow-300" />
                </div>
            </div>
        </flux:card>

        {{-- Low Stock Products --}}
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="uppercase text-xs font-medium mb-3">Low Stock</flux:text>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $this->lowStockProducts }}</p>
                </div>
                <div class="p-3 bg-red-50 rounded-lg dark:bg-red-800">
                    <flux:icon.exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-300" />
                </div>
            </div>
        </flux:card>
    </div>

    <flux:card class="p-0 mt-6 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        {{-- Filters --}}
        <div class="flex items-center gap-4 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">
            <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Search by name or SKU..."
                class="max-w-md" clearable />

            {{-- You can add Category filters here later --}}
            <div class="flex items-center gap-3 ms-auto">
                <flux:select wire:model.live="status" class="max-w-40">
                    <flux:select.option value="">All Status</flux:select.option>
                    @foreach ($this->statuses as $s)
                        <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="category" class="max-w-48">
                    <flux:select.option value="">All Categories</flux:select.option>
                    @foreach ($this->categories as $cat)
                        <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        {{-- Table --}}
        <flux:table :paginate="$this->products">
            <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
                <flux:table.column class="ps-4! bg-zinc-50  dark:bg-zinc-800" sticky>
                    <span class="sr-only">Product Image</span>
                </flux:table.column>
                <flux:table.column sortable :sorted="$this->sortBy === 'name'" :direction="!$this->sortDirection"
                    wire:click="sort('name')">
                    Product
                </flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column sortable :sorted="$this->sortBy === 'name'" :direction="!$this->sortDirection"
                    wire:click="sort('stock_quantity')">
                    Stock
                </flux:table.column>
                <flux:table.column sortable :sorted="$this->sortBy === 'name'" :direction="!$this->sortDirection"
                    wire:click="sort('price')">
                    Price
                </flux:table.column>
                <flux:table.column>Orders</flux:table.column>
                <flux:table.column>Rating</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->products as $product)
                    <flux:table.row :key="$product->id">
                        {{-- Product Image --}}
                        <flux:table.cell sticky class="ps-4! bg-white dark:bg-zinc-700">
                            <div
                                class="w-10 h-10 rounded border dark:border-zinc-600  bg-zinc-50 dark:bg-zinc-800 overflow-hidden">
                                @if ($product->image_path)
                                    <img src="{{ $product->image_url }}" class="object-cover w-full h-full">
                                @else
                                    <flux:icon.photo class="w-full h-full p-2 text-zinc-300" />
                                @endif
                            </div>
                        </flux:table.cell>

                        {{-- Product Info --}}
                        <flux:table.cell>
                            <div class="font-medium text-zinc-800 dark:text-white">{{ $product->name }}</div>
                            <flux:text class="text-xs">SKU: {{ $product->sku ?? 'N/A' }}</flux:text>
                        </flux:table.cell>

                        {{-- Category --}}
                        <flux:table.cell>

                            <flux:badge size="sm" variant="outline" color="zinc">
                                {{ $product->primaryCategory()?->name ?? 'Uncategorized' }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Type --}}
                        <flux:table.cell>
                            {{ $product->stock_quantity }}
                        </flux:table.cell>

                        {{-- Price --}}
                        <flux:table.cell>
                            <div class="font-medium">{{ format_currency($product->price ?? 0) }}</div>
                            @if ($product->sale_price)
                                <div class="text-xs text-red-500 line-through">
                                    {{ format_currency($product->sale_price ?? 0) }}
                                </div>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $product->order_items_count }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge icon="star" icon-variant="solid" size="sm"
                                class="**:data-flux-badge-icon:text-yellow-500!">
                                {{ number_format($product->reviews_avg_rating, 1) }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Status --}}
                        <flux:table.cell>
                            <flux:badge size="sm" variant="flat" :color="$product->status->color()"
                                :icon="$product->status->icon()">
                                {{ $product->status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Actions --}}
                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                href="{{ route('admin.products.edit', $product) }}" wire:navigate />

                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                class="text-red-500!" wire:confirm="Move this product to trash?"
                                wire:click="delete({{ $product->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center py-12">
                            <div class="flex flex-col items-center justify-center text-zinc-500">
                                <flux:icon.cube class="size-12 text-zinc-500 stroke-1 mb-3" />
                                <flux:text class="font-medium">No products found</flux:text>
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
