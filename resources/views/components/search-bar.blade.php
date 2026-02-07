<?php

use Livewire\Component;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Livewire\Attributes\On;

new class extends Component {
    public $search = '';

    public $suggestions = [];
    public $showSuggestions = false;

    public function updatedSearch()
    {
        if (strlen($this->search) >= 2) {
            $this->loadSuggestions();
            $this->showSuggestions = true;
        } else {
            $this->suggestions = [];
            $this->showSuggestions = false;
        }
    }

    public function loadSuggestions()
    {
        $searchTerm = $this->search;

        $suggestions = [
            'products' => [],
            'categories' => [],
            'brands' => [],
        ];

        // Search Products
        $products = Product::active()
            ->where(function (Builder $q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('short_description', 'like', "%{$searchTerm}%")
                    ->orWhere('sku', 'like', "%{$searchTerm}%");
            })
            ->with(['categories:id,name', 'brand:id,name'])
            ->limit(8)
            ->get(['id', 'name', 'slug', 'image_path', 'price', 'sale_price']);

        $suggestions['products'] = $products
            ->map(
                fn($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image' => $product->image_url,
                    'price' => $product->formatted_final_price,
                    'sale_price' => $product->sale_price ? $product->formatted_sale_price : null,
                    'has_discount' => $product->hasDiscount(),
                    'category' => $product->categories->first()?->name,
                    'brand' => $product->brand?->name,
                ],
            )
            ->toArray();

        // Search Categories
        $categories = Category::query()
            ->active()
            ->where('name', 'like', "%{$searchTerm}%")
            ->withCount('activeProducts')
            ->limit(4)
            ->get(['id', 'name', 'slug', 'image_path']);

        $suggestions['categories'] = $categories
            ->map(
                fn($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'image' => $category->image_url,
                    'products_count' => $category->active_products_count,
                ],
            )
            ->toArray();

        // Search Brands
        $brands = Brand::query()
            ->where('name', 'like', "%{$searchTerm}%")
            ->withCount('activeProducts')
            ->limit(3)
            ->get(['id', 'name', 'slug', 'logo_url']);

        $suggestions['brands'] = $brands
            ->map(
                fn($brand) => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'logo' => $brand->logo_url,
                    'products_count' => $brand->active_products_count,
                ],
            )
            ->toArray();

        $this->suggestions = $suggestions;
    }
};
?>

<div class="w-full max-w-xl relative">
    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
        placeholder="Search for products, categories, or brands..." class="w-full" autocomplete="off" clearable />

    <div wire:show="showSuggestions" @click.outside="$wire.showSuggestions = false"
        class="absolute z-50 w-full bg-white rounded-sm top-full border max-h-96 overflow-y-auto">

        @if (!empty($suggestions['products']))
            <div class="py-2">
                <h6 class="px-4 py-2 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Products</h6>

                @foreach ($suggestions['products'] as $product)
                    <a href="{{ route('products.show', $product['slug']) }}" wire:navigate
                        class=" flex items-start gap-3 text-left px-4 py-2 hover:bg-zinc-50 transition-colors ">
                        <!-- Product Image -->
                        @if ($product['image'])
                            <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}"
                                class="w-12 h-12 object-cover rounded">
                        @else
                            <flux:icon.photo class="w-12 h-12 bg-zinc-100 rounded shrink-0" />
                        @endif

                        {{-- Product Details --}}
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-sm truncate">{{ $product['name'] }}</p>

                            @if ($product['category'] || $product['brand'])
                                <div class="text-xs text-zinc-500 mt-0.5 flex items-center gap-1">
                                    @if ($product['brand'])
                                        <span
                                            class="px-2 py-0.5 bg-zinc-100 rounded text-zinc-600">{{ $product['brand'] }}</span>
                                    @endif
                                    @if ($product['category'])
                                        <span
                                            class="px-2 py-0.5 bg-sheffield-blue/10 rounded text-sheffield-blue">{{ $product['category'] }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        @if (!empty($suggestions['categories']))
            <div class="border-t py-2">
                <h6 class="px-4 py-2 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Categories</h6>

                @foreach ($suggestions['categories'] as $category)
                    @if ($category['products_count'] >= 1)
                        <a href="{{ route('products', ['category' => $category['slug']]) }}" wire:navigate
                            class="w-full px-4 py-2 hover:bg-zinc-50 transition-colors flex items-center gap-3 text-left group">
                            <div
                                class="w-10 h-10 bg-sheffield-blue/10 rounded-lg flex items-center justify-center shrink-0">
                                <flux:icon.layout-panel-top icon-variant="solid" class="w-5 h-5 text-sheffield-blue" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-sm text-zinc-900 truncate">
                                    {{ $category['name'] }}
                                </div>
                                <div class="text-xs text-zinc-500 mt-0.5">
                                    {{ $category['products_count'] }}
                                    {{ $category['products_count'] === 1 ? 'product' : 'products' }}
                                </div>
                            </div>

                            <flux:icon.chevron-right
                                class="size-4 group-hover:translate-x-2 transition-transform duration-300 ease-in-out" />
                        </a>
                    @endif
                @endforeach
            </div>
        @endif

        <!-- Brands Section -->
        @if (!empty($suggestions['brands']))
            <div class="border-t py-2">
                <h6 class="px-4 py-2 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Brands</h6>

                @foreach ($suggestions['brands'] as $brand)
                    <a href="{{ route('products', ['brand' => $brand['slug']]) }}" wire:navigate
                        class="w-full px-4 py-3 hover:bg-zinc-50 transition-colors flex items-center gap-3 text-left group">
                        <div class="w-10 h-10 bg-zinc-100 rounded-lg flex items-center justify-center shrink-0">
                            <flux:icon.award class="w-5 h-5 text-zinc-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm text-zinc-900 truncate">
                                {{ $brand['name'] }}
                            </div>
                            <div class="text-xs text-zinc-500 mt-0.5">
                                {{ $brand['products_count'] }}
                                {{ $brand['products_count'] === 1 ? 'product' : 'products' }}
                            </div>
                        </div>

                        <flux:icon.chevron-right
                            class="size-4 group-hover:translate-x-2 transition-transform duration-300 ease-in-out" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
