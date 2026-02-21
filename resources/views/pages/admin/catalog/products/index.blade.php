<?php
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};

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

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with([
                'categories' => function ($query) {
                    $query->where('is_primary', true);
                },
            ])
            ->when($this->search, function ($q) {
                $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%");
            })
            ->latest()
            ->paginate(15);
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="mb-1">Products</flux:heading>
            <flux:subheading>Organize your products, manage inventory, and control product details and availability.
            </flux:subheading>
        </div>

        <flux:button href="{{ route('admin.products.create') }}" variant="primary" icon="plus" wire:navigate>
            Create Product
        </flux:button>
    </div>


    <div class="flex items-center gap-4 mb-4 mt-6">
        <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Search by name or SKU..." class="flex-1"
            class="max-w-md" clearable />
        {{-- You can add Category filters here later --}}
    </div>

    <flux:card class="p-0">
        <flux:table :paginate="$this->products">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Product</flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column>Stock</flux:table.column>
                <flux:table.column>Price</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->products as $product)
                    <flux:table.row :key="$product->id">
                        {{-- Product Info --}}
                        <flux:table.cell class="flex items-center gap-3 ps-4!">
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
                                <div class="text-xs text-red-500 line-through">
                                    {{ format_currency($product->sale_price) }}
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
