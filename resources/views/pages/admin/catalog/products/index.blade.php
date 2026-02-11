<?php
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

new #[Title('Products')] class extends Component {
    use WithPagination;

    public $search = '';

    public function delete($id)
    {
        $product = Product::findOrFail($id);
        // Add logic here to clean up images from storage if necessary
        $product->delete();
        session()->flash('status', 'Product moved to trash.');
    }

    public function with()
    {
        return [
            'products' => Product::query()
                ->with([
                    'categories' => function ($query) {
                        $query->where('is_primary', true);
                    },
                ])
                ->when($this->search, function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%");
                })
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="mb-1">Products</flux:heading>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="#" icon="home" icon-variant="outline"></flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Products</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <flux:button href="{{ route('admin.products.create') }}" variant="primary" icon="plus" wire:navigate>
            Create Product
        </flux:button>
    </div>


    <div class="flex items-center gap-4 mb-4 mt-6">
        <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Search by name or SKU..."
            class="flex-1" class="max-w-md" />
        {{-- You can add Category filters here later --}}
    </div>

    <flux:table :paginate="$products">
        <flux:table.columns>
            <flux:table.column>Product</flux:table.column>
            <flux:table.column>Category</flux:table.column>
            <flux:table.column>Stock</flux:table.column>
            <flux:table.column>Price</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($products as $product)
                <flux:table.row :key="$product->id">
                    {{-- Product Info --}}
                    <flux:table.cell class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded border bg-zinc-50 overflow-hidden">
                            @if ($product->image_path)
                                <img src="{{ $product->image_url }}" class="object-cover w-full h-full">
                            @else
                                <flux:icon name="photo" class="w-full h-full p-2 text-zinc-300" />
                            @endif
                        </div>
                        <div>
                            <div class="font-medium text-zinc-800 dark:text-white">{{ $product->name }}</div>
                            <div class="text-xs text-zinc-500">SKU: {{ $product->sku ?? 'N/A' }}</div>
                        </div>
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
                            <div class="text-xs text-red-500 line-through">{{ format_currency($product->sale_price) }}
                            </div>
                        @endif
                    </flux:table.cell>

                    {{-- Status --}}
                    <flux:table.cell>
                        <flux:badge size="sm" variant="flat"
                            :color="match($product->status) {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            'draft' => 'gray',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            'scheduled' => 'blue',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            'published' => 'green',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            'archived' => 'amber',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            default => 'gray',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        }">
                            {{ ucfirst($product->status) }}
                        </flux:badge>
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell align="end">
                        <flux:button variant="ghost" size="sm" icon="pencil-square"
                            href="{{ route('admin.products.edit', $product) }}" wire:navigate />

                        <flux:button variant="ghost" size="sm" icon="trash" color="red"
                            wire:confirm="Move this product to trash?" wire:click="delete({{ $product->id }})" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
