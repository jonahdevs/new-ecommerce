<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Defer;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;

new #[Defer] #[Layout('layouts.guest')] class extends Component {
    use WithPagination;

    #[Url(as: 'category')]
    public $categorySlug = null;

    #[Url(as: 'brand')]
    public $selectedBrandsString = '';

    public $selectedBrands = [];

    #[Url(as: 'min_price')]
    public $minPrice = null;

    #[Url(as: 'max_price')]
    public $maxPrice = null;

    #[Url(as: 'rating')]
    public $minRating = null;

    #[Url(as: 'sort')]
    public $sortBy = '';

    #[Url(as: 'in_stock')]
    public $inStock = false;

    #[Url(as: 'featured')]
    public $featured = false;

    #[Url(as: 'on_sale')]
    public $onSale = false;

    public $brandSearch = '';
    public $showMobileFilters = false;

    public function mount()
    {
        // Parse comma-separated brands from URL
        if (!empty($this->selectedBrandsString)) {
            $this->selectedBrands = explode(',', $this->selectedBrandsString);
        }

        if ($this->minPrice === null) {
            $this->minPrice = $this->priceRange->min_price ?? 0;
        }

        if ($this->maxPrice === null) {
            $this->maxPrice = $this->priceRange->max_price ?? 1000000;
        }
    }

    #[Computed]
    public function products()
    {
        $query = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description'])
            ->withAvg('reviews', 'rating')
            ->with(['brand:id,name'])
            ->active();

        // Category Filter
        $query->when($this->categorySlug, function (Builder $q) {
            $selectedCategory = is_numeric($this->categorySlug) ? Category::find($this->categorySlug) : Category::where('slug', $this->categorySlug)->first();

            if ($selectedCategory) {
                $childrenIds = $selectedCategory->children()->pluck('id')->toArray();
                $q->whereHas('categories', function (Builder $q2) use ($selectedCategory, $childrenIds) {
                    $q2->whereIn('categories.id', array_merge([$selectedCategory->id], $childrenIds));
                });
            }
        });

        // Brand Filter - Fixed to check for non-empty array
        $query->when(!empty($this->selectedBrands), function (Builder $q) {
            $q->whereHas('brand', function (Builder $q2) {
                $q2->whereIn('slug', $this->selectedBrands);
            });
        });

        // Price Range Filter
        $query->when($this->minPrice !== null, function (Builder $q) {
            $q->where('price', '>=', $this->minPrice);
        });
        $query->when($this->maxPrice !== null, function (Builder $q) {
            $q->where('price', '<=', $this->maxPrice);
        });

        // Rating Filter - Improved version
        $query->when($this->minRating, function (Builder $q) {
            $q->whereHas('reviews')->having('reviews_avg_rating', '>=', $this->minRating);
        });

        // Stock Filter
        $query->when($this->inStock, function (Builder $q) {
            $q->where('stock_quantity', '>', 0);
        });

        // Featured Filter
        $query->when($this->featured, function (Builder $q) {
            $q->where('is_featured', true);
        });

        // On Sale Filter
        $query->when($this->onSale, function (Builder $q) {
            $q->whereNotNull('sale_price')->where('sale_price', '<', DB::raw('price'));
        });

        // Sorting
        match ($this->sortBy) {
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->withAvg('reviews', 'rating')->orderBy('reviews_avg_rating', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            'popular' => $query->withCount('orderItems')->orderBy('order_items_count', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->inRandomOrder()->paginate(20);
    }

    #[Computed]
    public function selectedCategory()
    {
        if (!$this->categorySlug) {
            return null;
        }
        return Category::where('slug', $this->categorySlug)->first();
    }

    #[Computed]
    public function categories()
    {
        if ($this->selectedCategory) {
            return Category::active()->where('parent_id', $this->selectedCategory->id)->get();
        }
        return Category::active()->whereNull('parent_id')->get();
    }

    #[Computed]
    public function brands()
    {
        return Brand::active()->orderBy('name')->get();
    }

    #[Computed]
    public function filteredBrands()
    {
        if (empty($this->brandSearch)) {
            return $this->brands;
        }
        return $this->brands->filter(function ($brand) {
            return str_contains(strtolower($brand->name), strtolower($this->brandSearch));
        });
    }

    #[Computed]
    public function priceRange()
    {
        return Product::active()->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();
    }

    #[Computed]
    public function hasActiveFilters()
    {
        return $this->categorySlug || !empty($this->selectedBrands) || $this->minPrice != ($this->priceRange->min_price ?? 0) || $this->maxPrice != ($this->priceRange->max_price ?? 1000000) || $this->minRating || $this->inStock || $this->featured || $this->onSale;
    }

    public function selectCategory($slug)
    {
        $this->categorySlug = $slug;
        $this->resetPage();
    }

    public function clearCategory()
    {
        $this->categorySlug = null;
        $this->resetPage();
    }

    public function toggleBrand($slug)
    {
        if (in_array($slug, $this->selectedBrands)) {
            $this->selectedBrands = array_values(array_diff($this->selectedBrands, [$slug]));
        } else {
            $this->selectedBrands[] = $slug;
        }

        // Update URL string
        $this->selectedBrandsString = !empty($this->selectedBrands) ? implode(',', $this->selectedBrands) : '';

        $this->resetPage();
    }

    public function clearBrand($slug)
    {
        $this->selectedBrands = array_values(array_diff($this->selectedBrands, [$slug]));

        // Update URL string
        $this->selectedBrandsString = !empty($this->selectedBrands) ? implode(',', $this->selectedBrands) : '';

        $this->resetPage();
    }

    public function applyPriceFilter()
    {
        $this->resetPage();
    }

    public function clearPriceFilter()
    {
        $this->minPrice = $this->priceRange->min_price ?? 0;
        $this->maxPrice = $this->priceRange->max_price ?? 1000000;
        $this->resetPage();
    }

    public function setRating($rating)
    {
        $this->minRating = $rating;
        $this->resetPage();
    }

    public function clearRating()
    {
        $this->minRating = null;
        $this->resetPage();
    }

    public function clearAllFilters()
    {
        $this->reset(['categorySlug', 'selectedBrands', 'selectedBrandsString', 'minRating', 'sortBy', 'inStock', 'featured', 'onSale', 'minPrice', 'maxPrice']);
        $this->clearPriceFilter();
        $this->resetPage();
    }

    public function updatedSortBy()
    {
        $this->resetPage();
    }

    public function updatedInStock()
    {
        $this->resetPage();
    }

    public function updatedFeatured()
    {
        $this->resetPage();
    }

    public function updatedOnSale()
    {
        $this->resetPage();
    }

    public function updatedMinRating()
    {
        $this->resetPage();
    }
};
?>

@placeholder
    <div>
        <div class="bg-zinc-100">
            <div class="flex items-center gap-3 container mx-auto py-4 px-4">
                <flux:skeleton animate="shimmer" class="w-32 h-4" />
                <flux:skeleton animate="shimmer" class="w-8 h-4" />
                <flux:skeleton animate="shimmer" class="w-32 h-4" />
                <flux:skeleton animate="shimmer" class="w-8 h-4" />
                <flux:skeleton animate="shimmer" class="w-44 h-4" />
            </div>
        </div>

        <div class="container mx-auto px-4 py-4">

            <div class="flex gap-4 mt-4">
                {{-- left sidebar --}}

                <flux:skeleton animate="shimmer" class="hidden lg:block w-64 shrink-0 min-h-[80svh] sticky top-44" />

                {{-- Product section --}}
                <div class="flex-1 @container/main">
                    {{-- Page header  --}}
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="space-y-2">
                                <flux:skeleton animate="shimmer" class="w-48 h-8" />
                                <flux:skeleton animate="shimmer" class="w-40 h-5" />
                            </div>

                            <flux:skeleton animate="shimmer" class="w-32 h-8" />
                        </div>
                    </div>

                    {{-- products --}}
                    <div
                        class="grid grid-cols-1 @sm/main:grid-cols-2 @xl/main:grid-cols-3 @3xl/main:grid-cols-4 @5xl/main:grid-cols-5 gap-3">
                        @for ($i = 1; $i < 20; $i++)
                            <x-product-card-placeholder />
                        @endfor
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-8">
                        <div class="flex items-center justify-between">
                            <flux:skeleton animate="shimmer" class="w-24 h-8" />

                            <flux:skeleton animate="shimmer" class="w-44 h-10" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endplaceholder

<div>
    <div class="bg-zinc-100">
        {{-- Breadcrumb --}}
        <flux:breadcrumbs class="container px-4 py-4 mx-auto">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item>Products</flux:breadcrumbs.item>

            @if ($this->selectedCategory)
                <flux:breadcrumbs.item>{{ $this->selectedCategory->name }}</flux:breadcrumbs.item>
            @endif
        </flux:breadcrumbs>
    </div>

    <div class="container mx-auto px-4 py-4">
        <div class="flex gap-4 mt-4">
            {{-- Left sidebar  --}}
            <aside class="hidden lg:block w-64 shrink-0">
                <div class="sticky top-44">


                    <div class="bg-white rounded-sm border">
                        <div class="px-3 py-2 border-b">
                            <h2 class="font-medium text-lg">Filters</h2>
                        </div>

                        <div class="divide-y">
                            {{-- Category filter  --}}
                            <div class="p-4">
                                <h3 class="font-medium mb-3">Category</h3>

                                <div class="max-h-64 overflow-y-auto">
                                    @if ($this->selectedCategory)
                                        {{-- Back to all categories --}}
                                        <button wire:click="clearCategory"
                                            class="flex items-center gap-2 text-sm text-sheffield-blue hover:underline p-2 mb-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                            </svg>
                                            Back to All Categories
                                        </button>

                                        {{-- Current category --}}
                                        <div
                                            class="font-medium text-sm text-zinc-900 p-2 bg-sheffield-blue/10 rounded mb-3">
                                            {{ $this->selectedCategory->name }}
                                        </div>

                                        {{-- Subcategories --}}
                                        @if ($this->categories->isNotEmpty())
                                            <div class="text-xs text-zinc-500 px-2 mb-2 font-medium">Subcategories:
                                            </div>
                                            @foreach ($this->categories as $category)
                                                <button type="button"
                                                    class="flex items-center gap-2 cursor-pointer hover:bg-zinc-50 p-2 rounded w-full text-left"
                                                    wire:click="selectCategory('{{ $category->slug }}')">
                                                    <span
                                                        class="w-2 h-2 rounded-full border border-sheffield-blue bg-white"></span>
                                                    <span class="text-sm text-zinc-700">{{ $category->name }}</span>
                                                </button>
                                            @endforeach
                                        @else
                                            <p class="text-xs text-zinc-500 px-2 py-2">No subcategories available</p>
                                        @endif
                                    @else
                                        {{-- Root categories --}}
                                        @foreach ($this->categories as $category)
                                            <button type="button"
                                                class="text-sm capitalize px-2 py-2 hover:bg-zinc-100 rounded block w-full text-left"
                                                wire:click="selectCategory('{{ $category->slug }}')">
                                                {{ $category->name }}
                                            </button>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            {{-- Price filter --}}
                            <div class="p-4" x-data="{
                                localMin: {{ $minPrice ?? ($this->priceRange->min_price ?? 0) }},
                                localMax: {{ $maxPrice ?? ($this->priceRange->max_price ?? 1000000) }},
                                absoluteMin: {{ $this->priceRange->min_price ?? 0 }},
                                absoluteMax: {{ $this->priceRange->max_price ?? 1000000 }},
                            
                                // Ensure min doesn't exceed max
                                updateMin() {
                                    this.localMin = parseFloat(this.localMin);
                                    if (this.localMin > this.localMax) {
                                        this.localMin = this.localMax;
                                    }
                                    if (this.localMin < this.absoluteMin) {
                                        this.localMin = this.absoluteMin;
                                    }
                                },
                            
                                // Ensure max doesn't go below min
                                updateMax() {
                                    this.localMax = parseFloat(this.localMax);
                                    if (this.localMax < this.localMin) {
                                        this.localMax = this.localMin;
                                    }
                                    if (this.localMax > this.absoluteMax) {
                                        this.localMax = this.absoluteMax;
                                    }
                                },
                            
                                // Apply filter
                                apply() {
                                    $wire.minPrice = this.localMin;
                                    $wire.maxPrice = this.localMax;
                                    $wire.applyPriceFilter();
                                },
                            
                                // Reset to defaults
                                reset() {
                                    this.localMin = this.absoluteMin;
                                    this.localMax = this.absoluteMax;
                                    $wire.clearPriceFilter();
                                }
                            }">

                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="font-medium">Price (KES)</h3>

                                    <div class="flex items-center gap-2">
                                        <button @click="reset"
                                            x-show="localMin != absoluteMin || localMax != absoluteMax" x-transition
                                            class="text-zinc-500 text-xs hover:text-zinc-700 cursor-pointer font-medium"
                                            type="button">
                                            Reset
                                        </button>

                                        <button @click="apply"
                                            class="text-sheffield-blue text-sm hover:underline cursor-pointer font-medium"
                                            type="button">
                                            Apply
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    {{-- Price Display --}}
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-zinc-600">
                                            KES <span x-text="Math.round(localMin).toLocaleString()"></span>
                                        </span>
                                        <span class="text-zinc-600">
                                            KES <span x-text="Math.round(localMax).toLocaleString()"></span>
                                        </span>
                                    </div>

                                    {{-- Dual Range Slider --}}
                                    <div class="relative">
                                        {{-- Track Background --}}
                                        <div class="relative w-full h-2 bg-zinc-200 rounded pointer-events-none">
                                            <div class="absolute h-2 bg-sheffield-blue rounded"
                                                :style="`left: ${((localMin - absoluteMin) / (absoluteMax - absoluteMin)) * 100}%; right: ${100 - ((localMax - absoluteMin) / (absoluteMax - absoluteMin)) * 100}%`">
                                            </div>
                                        </div>

                                        {{-- Max Price Slider (render first, lower z-index) --}}
                                        <input type="range" x-model.number="localMax" @input="updateMax"
                                            :min="absoluteMin" :max="absoluteMax" step="1000"
                                            class="absolute inset-0 top-1/2 -translate-y-1/2 w-full h-2 bg-transparent appearance-none cursor-pointer [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-sheffield-blue [&::-webkit-slider-thumb]:cursor-pointer [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:hover:scale-110 [&::-webkit-slider-thumb]:transition-transform [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-white [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:bg-sheffield-red [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-white [&::-moz-range-thumb]:cursor-pointer [&::-moz-range-thumb]:shadow-md"
                                            style="z-index: 1;">

                                        {{-- Min Price Slider (render second, higher z-index) --}}
                                        <input type="range" x-model.number="localMin" @input="updateMin"
                                            :min="absoluteMin" :max="absoluteMax" step="1000"
                                            class="absolute inset-0 top-1/2 -translate-y-1/2 w-full h-2 bg-transparent appearance-none cursor-pointer [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-sheffield-blue [&::-webkit-slider-thumb]:cursor-pointer [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:hover:scale-110 [&::-webkit-slider-thumb]:transition-transform [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-white [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:bg-sheffield-blue [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-white [&::-moz-range-thumb]:cursor-pointer [&::-moz-range-thumb]:shadow-md"
                                            style="z-index: 2; pointer-events: none;">

                                    </div>

                                    {{-- Manual Input Fields (Optional) --}}
                                    <div class="flex items-center gap-2 text-sm">
                                        <input type="number" x-model.number="localMin" @blur="updateMin"
                                            :min="absoluteMin" :max="absoluteMax" step="1000"
                                            class="w-full px-2 py-1.5 border border-zinc-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-sheffield-blue focus:border-transparent"
                                            placeholder="Min">
                                        <span class="text-zinc-400">—</span>
                                        <input type="number" x-model.number="localMax" @blur="updateMax"
                                            :min="absoluteMin" :max="absoluteMax" step="1000"
                                            class="w-full px-2 py-1.5 border border-zinc-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-sheffield-red focus:border-transparent"
                                            placeholder="Max">
                                    </div>
                                </div>
                            </div>

                            {{-- Rating Filter --}}
                            <div class="p-4">
                                <h3 class="font-medium mb-3">Rating</h3>

                                <div class="space-y-2">
                                    <flux:radio.group wire:model.live="minRating">
                                        @for ($rating = 4; $rating >= 1; $rating--)
                                            <flux:field class="flex! items-center!">
                                                <flux:radio value="{{ $rating }}" />
                                                <flux:label class="">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        @if ($i <= $rating)
                                                            <flux:icon.star class="w-4 h-4 text-yellow-400"
                                                                variant="solid" />
                                                        @else
                                                            <flux:icon.star class="w-4 h-4 text-zinc-300"
                                                                variant="solid" />
                                                        @endif
                                                    @endfor

                                                    <span class="ms-1 font-normal">& above</span>
                                                </flux:label>
                                            </flux:field>
                                        @endfor
                                    </flux:radio.group>
                                </div>
                            </div>

                            {{-- Brand Filter --}}
                            <div class="p-4">
                                <h3 class="font-medium mb-3">Brand</h3>

                                {{-- Search Brands --}}
                                <div class="mb-3">
                                    <flux:input icon="magnifying-glass" placeholder="Search brands..." size="sm"
                                        wire:model.live.debounce.300ms="brandSearch" clearable />
                                </div>

                                <div class="max-h-64 overflow-y-auto">
                                    @forelse ($this->filteredBrands as $brand)
                                        <flux:field class="text-sm font-medium px-2 py-2 flex! items-center!">
                                            <flux:checkbox wire:key="brand-{{ $brand->slug }}"
                                                value="{{ $brand->slug }}"
                                                :checked="in_array($brand->slug, $selectedBrands)"
                                                wire:click="toggleBrand('{{ $brand->slug }}')" />
                                            <flux:label class="font-normal cursor-pointer"> {{ $brand->name }}
                                            </flux:label>
                                        </flux:field>
                                    @empty
                                        <p class="text-sm text-zinc-500 px-2 py-2">No brands found</p>
                                    @endforelse
                                </div>
                            </div>

                            {{-- More Filters --}}
                            <div class="p-4">
                                <h3 class="font-medium mb-3">More Filters</h3>

                                <div class="space-y-2">
                                    <flux:field class="flex! items-center!">
                                        <flux:checkbox wire:model="inStock" />
                                        <flux:label class="ms-2 font-normal">In Stock</flux:label>
                                    </flux:field>

                                    <flux:field class="flex! items-center! mt-2">
                                        <flux:checkbox wire:model="featured" />
                                        <flux:label class="ms-2 font-normal">Featured Products</flux:label>
                                    </flux:field>

                                    <flux:field class="flex! items-center! mt-2">
                                        <flux:checkbox wire:model="onSale" />
                                        <flux:label class="ms-2 font-normal">On Sale</flux:label>
                                    </flux:field>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Product section --}}
            <section class="flex-1 @container/main">

                {{-- Page Header with Dynamic Title --}}
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-zinc-900">
                                @if ($this->selectedCategory)
                                    {{ $this->selectedCategory->name }}
                                @else
                                    Products
                                @endif
                            </h1>

                            {{-- Subtitle with result count and filters --}}
                            <p class="text-sm text-zinc-600 mt-1">
                                @if ($this->products->total() > 0)
                                    <span class="font-medium">{{ number_format($this->products->total()) }}</span>
                                    {{ Str::plural('product', $this->products->total()) }} found

                                    @if ($this->hasActiveFilters)
                                        <span class="text-zinc-400">•</span>
                                        <button wire:click="clearAllFilters"
                                            class="text-sheffield-blue hover:underline">
                                            Clear all filters
                                        </button>
                                    @endif
                                @else
                                    No products found
                                @endif
                            </p>
                        </div>

                        {{-- Sort dropdown --}}
                        <flux:select wire:model.live="sortBy" class="w-fit">
                            <option value="">Sort By: Default</option>
                            <option value="name_asc">Name (A-Z)</option>
                            <option value="name_desc">Name (Z-A)</option>
                            <option value="price_asc">Price (Low to High)</option>
                            <option value="price_desc">Price (High to Low)</option>
                            <option value="rating">Rating</option>
                            <option value="newest">Newest</option>
                            <option value="popular">Most Popular</option>
                        </flux:select>
                    </div>

                    {{-- Active Filters Pills --}}
                    @if ($this->hasActiveFilters)
                        <div class="flex flex-wrap gap-2">
                            {{-- Category Filter --}}
                            @if ($this->selectedCategory)
                                <flux:badge color="zinc" size="sm">
                                    {{ $this->selectedCategory->name }}
                                    <button wire:click="clearCategory"
                                        class="ml-1.5 hover:text-red-600 cursor-pointer" type="button">
                                        <flux:icon.x-mark class="w-3 h-3" />
                                    </button>
                                </flux:badge>
                            @endif

                            {{-- Brand Filters --}}
                            @foreach ($selectedBrands as $brandSlug)
                                @php
                                    $brand = $this->brands->firstWhere('slug', $brandSlug);
                                @endphp
                                @if ($brand)
                                    <flux:badge color="zinc" size="sm">
                                        {{ $brand->name }}
                                        <button wire:click="clearBrand('{{ $brandSlug }}')"
                                            class="ml-1.5 hover:text-red-600 cursor-pointer" type="button">
                                            <flux:icon.x-mark class="w-3 h-3" />
                                        </button>
                                    </flux:badge>
                                @endif
                            @endforeach

                            {{-- Price Filter --}}
                            @if ($minPrice != ($this->priceRange->min_price ?? 0) || $maxPrice != ($this->priceRange->max_price ?? 1000000))
                                <flux:badge color="zinc" size="sm">
                                    KES {{ number_format($minPrice) }} - {{ number_format($maxPrice) }}
                                    <button wire:click="clearPriceFilter"
                                        class="ml-1.5 hover:text-red-600 cursor-pointer" type="button">
                                        <flux:icon.x-mark class="w-3 h-3" />
                                    </button>
                                </flux:badge>
                            @endif

                            {{-- Rating Filter --}}
                            @if ($minRating)
                                <flux:badge color="zinc" size="sm">
                                    {{ $minRating }}+ Stars
                                    <button wire:click="clearRating" class="ml-1.5 hover:text-red-600 cursor-pointer"
                                        type="button">
                                        <flux:icon.x-mark class="w-3 h-3" />
                                    </button>
                                </flux:badge>
                            @endif

                            {{-- Stock Filter --}}
                            @if ($inStock)
                                <flux:badge color="zinc" size="sm">
                                    In Stock
                                    <button wire:click="$set('inStock', false)"
                                        class="ml-1.5 hover:text-red-600 cursor-pointer" type="button">
                                        <flux:icon.x-mark class="w-3 h-3" />
                                    </button>
                                </flux:badge>
                            @endif

                            {{-- Featured Filter --}}
                            @if ($featured)
                                <flux:badge color="zinc" size="sm">
                                    Featured
                                    <button wire:click="$set('featured', false)"
                                        class="ml-1.5 hover:text-red-600 cursor-pointer" type="button">
                                        <flux:icon.x-mark class="w-3 h-3" />
                                    </button>
                                </flux:badge>
                            @endif

                            {{-- On Sale Filter --}}
                            @if ($onSale)
                                <flux:badge color="zinc" size="sm">
                                    On Sale
                                    <button wire:click="$set('onSale', false)"
                                        class="ml-1.5 hover:text-red-600 cursor-pointer" type="button">
                                        <flux:icon.x-mark class="w-3 h-3" />
                                    </button>
                                </flux:badge>
                            @endif
                        </div>
                    @endif
                </div>


                {{-- Products Grid --}}
                <div @class([
                    'grid grid-cols-1 @sm/main:grid-cols-2 @xl/main:grid-cols-3 @3xl/main:grid-cols-4 @5xl/main:grid-cols-5 gap-3' => $this->products->isNotEmpty(),
                ])>
                    @forelse ($this->products as $product)
                        <livewire:product-card :product="$product" :key="'product-' . $product->id" />
                    @empty
                        {{-- Empty State --}}
                        <section class="flex flex-col items-center justify-center min-h-100 text-center col-span-full">
                            <div class="text-zinc-300">
                                <svg class="w-32 h-32 mx-auto" fill="currentColor" stroke-width="1" version="1.1"
                                    viewBox="-5.0 -10.0 110.0 135.0">
                                    <g>
                                        <path
                                            d="m96.504 50.293-9.2812-13.922c-0.15234-0.22656-0.36719-0.38672-0.60938-0.47656v-0.003906l-19.035-7.0039c1.4141-2.7266 2.2148-5.8164 2.2148-9.0938 0.003906-10.914-8.8789-19.793-19.793-19.793s-19.797 8.8789-19.797 19.797c0 3.2773 0.80078 6.375 2.2188 9.1016l-19.004 6.9961v0.003907c-0.24219 0.089843-0.45703 0.25-0.60938 0.47656l-9.3164 13.906c-0.45313 0.64062-0.13672 1.6172 0.60547 1.8672l6.4414 2.3711v30.188c0 0.52344 0.32813 0.99219 0.81641 1.1719l38.164 14.047c0.28125 0.10156 0.58594 0.10156 0.86328-0.003906v0.003906l38.164-14.047c0.49219-0.17969 0.81641-0.64844 0.81641-1.1719l0.011719-30.148 6.5195-2.3984c0.74219-0.25 1.0586-1.2266 0.60938-1.8672zm-46.504-47.793c9.5391 0 17.297 7.7578 17.297 17.297 0 9.5352-7.7578 17.297-17.297 17.297s-17.297-7.7578-17.297-17.297 7.7578-17.297 17.297-17.297zm0 37.094c6.7305 0 12.684-3.375 16.262-8.5234l16.301 5.9961-32.543 11.973-32.547-11.973 16.27-5.9922c3.5781 5.1445 9.5312 8.5195 16.258 8.5195zm-35.656-1.0156 33.73 12.41-7.8477 11.781-33.766-12.422zm-1.2969 45.254v-28.395l27.242 10.023c0.53125 0.19922 1.1523 0.003906 1.4727-0.48047l6.9492-10.434v42.414zm73.828 0-35.664 13.129v-42.539l7.0703 10.559c0.32031 0.48438 0.9375 0.67578 1.4688 0.47656l27.121-9.9766zm-27.062-21.059-7.8828-11.77 33.762-12.422 7.8555 11.781z" />
                                        <path
                                            d="m42.023 27.77c0.48828 0.48828 1.2812 0.48828 1.7695 0l6.207-6.207 6.207 6.207c0.48828 0.48828 1.2812 0.48828 1.7695 0 0.48828-0.48828 0.48828-1.2812 0-1.7656l-6.207-6.207 6.207-6.207c0.48828-0.48828 0.48828-1.2812 0-1.7656-0.48828-0.48828-1.2812-0.48828-1.7695 0l-6.207 6.2031-6.207-6.207c-0.48828-0.48828-1.2812-0.48828-1.7695 0-0.48828 0.48828-0.48828 1.2812 0 1.7656l6.207 6.207-6.207 6.207c-0.48828 0.49219-0.48828 1.2812 0 1.7695z" />
                                    </g>
                                </svg>
                            </div>

                            <h3 class="text-xl font-semibold text-zinc-800 mb-2">No Products Found</h3>

                            <p class="text-zinc-600 mb-6 max-w-md">
                                We couldn't find any products matching your criteria. Try adjusting your filters or
                                search terms.
                            </p>

                            <flux:button href="{{ route('products') }}" variant="primary" wire:navigate>
                                Clear Filters
                            </flux:button>
                        </section>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if ($this->products->hasPages())
                    <div class="mt-8">
                        {{ $this->products->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>



{{-- Make only the thumb of min slider clickable --}}
<style>
    input[type="range"]::-webkit-slider-thumb {
        pointer-events: auto;
    }

    input[type="range"]::-moz-range-thumb {
        pointer-events: auto;
    }
</style>
