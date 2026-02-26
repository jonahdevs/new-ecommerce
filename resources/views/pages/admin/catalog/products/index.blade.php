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

    public function delete($id)
    {
        $product = Product::findOrFail($id);
        // Add logic here to clean up images from storage if necessary
        $product->delete();
        session()->flash('status', 'Product moved to trash.');
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
            ->latest()
            ->paginate(15);
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


    <flux:card class="p-0 mt-6 **:data-flux-columns:bg-zinc-50">
        {{-- Filters --}}
        <div class="flex items-center gap-4 px-5 py-3 border-b">
            <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Search by name or SKU..."
                class="flex-1" class="max-w-md" clearable />

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
                <flux:table.column class="ps-4! bg-zinc-50 dark:bg-zinc-900" sticky>
                    <span class="sr-only">Product Image</span>
                </flux:table.column>
                <flux:table.column>Product</flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column>Stock</flux:table.column>
                <flux:table.column>Price</flux:table.column>
                <flux:table.column>Orders</flux:table.column>
                <flux:table.column>Rating</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->products as $product)
                    <flux:table.row :key="$product->id">
                        {{-- Product Image --}}
                        <flux:table.cell sticky class="ps-4! bg-white dark:bg-zinc-900">
                            <div class="w-10 h-10 rounded border bg-zinc-50 overflow-hidden">
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
                            <div class="text-xs text-zinc-500">SKU: {{ $product->sku ?? 'N/A' }}</div>
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
                            <div class="font-medium">{{ format_currency($product->price) }}</div>
                            @if ($product->sale_price)
                                <div class="text-xs text-red-500 line-through">
                                    {{ format_currency($product->sale_price) }}
                                </div>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $product->order_items_count }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge icon="star" icon-variant="solid" size="sm"
                                class="[&_[data-flux-badge-icon]]:text-yellow-500!">
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
