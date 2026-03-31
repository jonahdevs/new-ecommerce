<?php

use App\Models\Brand;
use App\Models\{Product, Category};
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;

new #[Layout('layouts.guest')] class extends Component {
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'brand')]
    public string $selectedBrandsString = '';
    public array $selectedBrands = [];

    #[Url(as: 'min_price')]
    public $minPriceUrl = null;

    #[Url(as: 'max_price')]
    public $maxPriceUrl = null;

    // Internal working values — NOT bound to URL
    public $minPrice = null;
    public $maxPrice = null;

    #[Url(as: 'rating')]
    public $minRating = null;

    #[Url(as: 'sort')]
    public string $sortBy = '';

    #[Url(as: 'in_stock')]
    public bool $inStock = false;

    #[Url(as: 'featured')]
    public bool $featured = false;

    #[Url(as: 'on_sale')]
    public bool $onSale = false;

    public string $brandSearch = '';
    public bool $showMobileFilters = false;

    public function mount(): void
    {
        if (!empty($this->selectedBrandsString)) {
            $this->selectedBrands = explode(',', $this->selectedBrandsString);
        }

        $range = $this->priceRange;
        $this->minPrice = $this->minPriceUrl ?? ($range->min_price ?? 0);
        $this->maxPrice = $this->maxPriceUrl ?? ($range->max_price ?? 1000000);

        // SEO Setup
        $title = !empty($this->search) ? "Search Results for '{$this->search}' - Commercial Kitchen Equipment" : 'Shop Commercial Kitchen Equipment - Restaurant & Bakery Supplies';

        $description = !empty($this->search) ? "Browse our selection of {$this->search} for commercial kitchens. Quality equipment for restaurants, bakeries, and hotels." : 'Browse our complete range of commercial kitchen equipment. Restaurant equipment, bakery machines, refrigeration solutions, and professional kitchen supplies.';

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::addKeyword(['commercial kitchen equipment', 'restaurant equipment', 'bakery equipment', 'kitchen supplies', 'shop kitchen equipment']);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setUrl(route('shop.index'));

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);
    }

    // =========================================================================
    // COMPUTED
    // =========================================================================

    #[Computed(persist: true)]
    public function priceRange()
    {
        return Cache::remember('shop_price_range', 300, fn() => Product::active()->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first());
    }

    #[Computed(persist: true)]
    public function brands()
    {
        return Cache::remember(
            'all_active_brands',
            300,
            fn() => Brand::active()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        );
    }

    #[Computed]
    public function filteredBrands()
    {
        if (empty($this->brandSearch)) {
            return $this->brands;
        }
        return $this->brands->filter(fn($brand) => str_contains(strtolower($brand->name), strtolower($this->brandSearch)));
    }

    #[Computed]
    public function products()
    {
        $query = Product::query()
            ->select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description', 'type', 'requires_quotation', 'reviews_enabled', 'stock_status', 'manage_stock', 'stock_quantity', 'average_rating', 'reviews_count', 'sales_count', 'created_at'])
            ->with([
                'brand:id,name,slug',
                'images' => fn($q) => $q->select(['id', 'product_id', 'image_path', 'alt_text', 'sort_order'])->limit(1),
                'variants' => fn($q) => $q
                    ->where('is_active', true)
                    ->whereNotNull('price')
                    ->select(['id', 'product_id', 'price', 'sale_price', 'is_active']),
            ])
            ->active();

        // Apply visibility based on whether user is searching or browsing
        if (!empty($this->search)) {
            $query->visibleInSearch();
            $term = $this->search;
            $query->where(
                fn(Builder $q2) => $q2
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%"),
            );
        } else {
            $query->visibleInCatalog();
        }

        $query->when(!empty($this->selectedBrands), fn(Builder $q) => $q->whereHas('brand', fn(Builder $q2) => $q2->whereIn('slug', $this->selectedBrands)));

        $query->when($this->minPrice !== null, fn(Builder $q) => $q->where('price', '>=', $this->minPrice));
        $query->when($this->maxPrice !== null, fn(Builder $q) => $q->where('price', '<=', $this->maxPrice));

        $query->when($this->minRating, fn(Builder $q) => $q->where('average_rating', '>=', $this->minRating));

        $query->when($this->inStock, fn(Builder $q) => $q->where(fn($q2) => $q2->where('stock_quantity', '>', 0)->orWhere('stock_status', 'in_stock')));
        $query->when($this->featured, fn(Builder $q) => $q->where('is_featured', true));
        $query->when($this->onSale, fn(Builder $q) => $q->whereNotNull('sale_price')->where('sale_price', '<', DB::raw('price')));

        match ($this->sortBy) {
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderBy('average_rating', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            'popular' => $query->orderBy('sales_count', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate(20);
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return !empty($this->search) || !empty($this->selectedBrands) || $this->minPriceUrl !== null || $this->maxPriceUrl !== null || $this->minRating || $this->inStock || $this->featured || $this->onSale;
    }

    #[Computed(persist: true)]
    public function categories()
    {
        return Cache::remember(
            'root_active_categories',
            300,
            fn() => Category::active()
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        );
    }

    // =========================================================================
    // ACTIONS
    // =========================================================================

    public function toggleBrand(string $slug): void
    {
        if (in_array($slug, $this->selectedBrands)) {
            $this->selectedBrands = array_values(array_diff($this->selectedBrands, [$slug]));
        } else {
            $this->selectedBrands[] = $slug;
        }
        $this->selectedBrandsString = !empty($this->selectedBrands) ? implode(',', $this->selectedBrands) : '';
        $this->resetPage();
    }

    public function clearBrand(string $slug): void
    {
        $this->selectedBrands = array_values(array_diff($this->selectedBrands, [$slug]));
        $this->selectedBrandsString = !empty($this->selectedBrands) ? implode(',', $this->selectedBrands) : '';
        $this->resetPage();
    }

    public function applyPriceFilter(): void
    {
        $this->minPriceUrl = $this->minPrice;
        $this->maxPriceUrl = $this->maxPrice;
        $this->resetPage();
    }

    public function clearPriceFilter(): void
    {
        $range = $this->priceRange;
        $this->minPrice = $range->min_price ?? 0;
        $this->maxPrice = $range->max_price ?? 1000000;
        $this->minPriceUrl = null;
        $this->maxPriceUrl = null;
        $this->resetPage();
    }

    public function setRating(int $rating): void
    {
        $this->minRating = $rating;
        $this->resetPage();
    }

    public function clearRating(): void
    {
        $this->minRating = null;
        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->reset(['search', 'selectedBrands', 'selectedBrandsString', 'minRating', 'sortBy', 'inStock', 'featured', 'onSale', 'minPriceUrl', 'maxPriceUrl']);
        $this->clearPriceFilter();
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }
    public function updatedInStock(): void
    {
        $this->resetPage();
    }
    public function updatedFeatured(): void
    {
        $this->resetPage();
    }
    public function updatedOnSale(): void
    {
        $this->resetPage();
    }
    public function updatedMinRating(): void
    {
        $this->resetPage();
    }
    public function updatedSearch(): void
    {
        $this->resetPage();
    }
};
?>

@placeholder
    <div>
        <div class="bg-zinc-100">
            <div class="flex items-center gap-3 container mx-auto py-2.5 px-4">
                <flux:skeleton animate="shimmer" class="w-4 h-4" />
                <flux:skeleton animate="shimmer" class="w-14 h-4" />
            </div>
        </div>
        <div class="container mx-auto px-4 py-4">
            <div class="flex gap-4">
                <aside class="hidden lg:block w-64 shrink-0">
                    <div class="sticky top-44">
                        <div class="bg-white rounded-sm border">
                            <div class="px-3 py-2 border-b">
                                <flux:skeleton animate="shimmer" class="w-20 h-6" />
                            </div>
                            <div class="divide-y">
                                @for ($i = 0; $i < 4; $i++)
                                    <div class="p-4 space-y-2">
                                        <flux:skeleton animate="shimmer" class="w-24 h-5 mb-3" />
                                        @for ($j = 0; $j < 4; $j++)
                                            <flux:skeleton animate="shimmer" class="w-full h-7" />
                                        @endfor
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </aside>
                <div class="flex-1 @container/main">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="space-y-2">
                            <flux:skeleton animate="shimmer" class="w-48 h-8" />
                            <flux:skeleton animate="shimmer" class="w-40 h-5" />
                        </div>
                        <flux:skeleton animate="shimmer" class="w-32 h-8" />
                    </div>
                    <div
                        class="grid grid-cols-1 @xs/main:grid-cols-2 @xl/main:grid-cols-3 @3xl/main:grid-cols-4 @5xl/main:grid-cols-5 gap-3">
                        @for ($i = 0; $i < 20; $i++)
                            <x-product-card-placeholder />
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
@endplaceholder

<div>
    {{-- Breadcrumb --}}
    <div class="bg-zinc-100">
        <flux:breadcrumbs class="container px-4 py-2.5 mx-auto">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Products</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="container mx-auto px-4 py-4">

        {{-- Mobile: filter toggle + sort --}}
        <div class="lg:hidden flex items-center justify-between mb-4 gap-3">
            <button wire:click="$set('showMobileFilters', true)" type="button"
                class="flex items-center gap-2 px-4 py-2 bg-white border border-zinc-200 rounded-md text-sm font-medium text-zinc-700 hover:bg-zinc-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                </svg>
                Filters
                @if ($this->hasActiveFilters)
                    <span class="w-2 h-2 rounded-full bg-brand-secondary"></span>
                @endif
            </button>

            <flux:select wire:model.live="sortBy" class="w-fit text-sm">
                <option value="">Sort: Default</option>
                <option value="name_asc">Name (A-Z)</option>
                <option value="name_desc">Name (Z-A)</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="rating">Top Rated</option>
                <option value="newest">Newest</option>
                <option value="popular">Most Popular</option>
            </flux:select>
        </div>

        {{-- Mobile filter drawer --}}
        <template x-teleport="body">
            <div x-show="$wire.showMobileFilters" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 z-200 flex lg:hidden"
                @keydown.escape.window="$wire.showMobileFilters = false">

                <div class="absolute inset-0 bg-black/40" @click="$wire.showMobileFilters = false"></div>

                <div x-show="$wire.showMobileFilters" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="-translate-x-full"
                    class="relative w-64 md:w-80 max-w-[85vw] bg-white h-full overflow-y-auto flex flex-col shadow-xl">

                    <div class="flex items-center justify-between px-4 py-3 border-b sticky top-0 bg-white z-10">
                        <h2 class="font-semibold text-zinc-900">Filters</h2>
                        <div class="flex items-center gap-3">
                            @if ($this->hasActiveFilters)
                                <button wire:click="clearAllFilters" type="button"
                                    class="text-xs text-brand-secondary hover:underline font-medium">
                                    Clear all
                                </button>
                            @endif
                            <button wire:click="$set('showMobileFilters', false)" type="button"
                                class="text-zinc-500 hover:text-zinc-900 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 divide-y">
                        @include('partials.product-filters')
                    </div>

                    <div class="sticky bottom-0 bg-white border-t px-4 py-3">
                        <button wire:click="$set('showMobileFilters', false)" type="button"
                            class="w-full py-2.5 bg-brand-secondary text-white font-medium rounded-md text-sm cursor-pointer">
                            View {{ $this->products->total() }} Results
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex gap-6">

            {{-- Desktop sidebar --}}
            <aside class="hidden lg:block w-64 shrink-0">
                <div class="sticky top-44">
                    <div class="bg-white rounded-sm border">
                        <div class="px-3 py-2 border-b flex items-center justify-between">
                            <h2 class="font-medium text-lg">Filters</h2>
                            @if ($this->hasActiveFilters)
                                <button wire:click="clearAllFilters" type="button"
                                    class="text-xs text-brand-secondary hover:underline font-medium cursor-pointer">
                                    Clear all
                                </button>
                            @endif
                        </div>
                        <div class="divide-y">
                            @include('partials.product-filters')
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Product section --}}
            <section class="flex-1 @container/main min-w-0">

                <div class="mb-4">
                    <div class="hidden lg:flex items-center justify-between mb-2">
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-zinc-900">
                                @if (!empty($search))
                                    Results for "{{ $search }}"
                                @else
                                    Products
                                @endif
                            </h1>
                            <p class="text-sm text-zinc-600 mt-1">
                                @if ($this->products->total() > 0)
                                    <span class="font-medium">{{ number_format($this->products->total()) }}</span>
                                    {{ Str::plural('product', $this->products->total()) }} found
                                    @if ($this->hasActiveFilters)
                                        <span class="text-zinc-400 mx-1">•</span>
                                        <button wire:click="clearAllFilters"
                                            class="text-brand-secondary hover:underline">
                                            Clear all filters
                                        </button>
                                    @endif
                                @else
                                    No products found
                                @endif
                            </p>
                        </div>
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

                    <p class="lg:hidden text-sm text-zinc-500 mb-3">
                        <span class="font-medium text-zinc-900">{{ number_format($this->products->total()) }}</span>
                        {{ Str::plural('product', $this->products->total()) }} found
                    </p>

                    {{-- Active filter pills --}}
                    @if ($this->hasActiveFilters)
                        <div class="flex flex-wrap gap-2">
                            @if (!empty($search))
                                <flux:badge color="zinc" size="sm">
                                    "{{ $search }}"
                                    <button wire:click="$set('search', '')"
                                        class="ml-1.5 hover:text-red-600 cursor-pointer" type="button">
                                        <flux:icon.x-mark class="w-3 h-3" />
                                    </button>
                                </flux:badge>
                            @endif
                            @foreach ($selectedBrands as $brandSlug)
                                @php $brand = $this->brands->firstWhere('slug', $brandSlug); @endphp
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
                            @if ($minPriceUrl !== null || $maxPriceUrl !== null)
                                <flux:badge color="zinc" size="sm">
                                    {{ get_currency_symbol() }} {{ number_format($minPrice) }} –
                                    {{ number_format($maxPrice) }}
                                    <button wire:click="clearPriceFilter"
                                        class="ml-1.5 hover:text-red-600 cursor-pointer" type="button">
                                        <flux:icon.x-mark class="w-3 h-3" />
                                    </button>
                                </flux:badge>
                            @endif
                            @if ($minRating)
                                <flux:badge color="zinc" size="sm">
                                    {{ $minRating }}+ Stars
                                    <button wire:click="clearRating" class="ml-1.5 hover:text-red-600 cursor-pointer"
                                        type="button">
                                        <flux:icon.x-mark class="w-3 h-3" />
                                    </button>
                                </flux:badge>
                            @endif
                            @if ($inStock)
                                <flux:badge color="zinc" size="sm">
                                    In Stock
                                    <button wire:click="$set('inStock', false)"
                                        class="ml-1.5 hover:text-red-600 cursor-pointer" type="button">
                                        <flux:icon.x-mark class="w-3 h-3" />
                                    </button>
                                </flux:badge>
                            @endif
                            @if ($featured)
                                <flux:badge color="zinc" size="sm">
                                    Featured
                                    <button wire:click="$set('featured', false)"
                                        class="ml-1.5 hover:text-red-600 cursor-pointer" type="button">
                                        <flux:icon.x-mark class="w-3 h-3" />
                                    </button>
                                </flux:badge>
                            @endif
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

                {{-- Products grid --}}
                <div @class([
                    'grid grid-cols-1 @xs/main:grid-cols-2 @xl/main:grid-cols-3 @3xl/main:grid-cols-4 @5xl/main:grid-cols-5 gap-3' => $this->products->isNotEmpty(),
                ])>
                    @forelse ($this->products as $product)
                        <livewire:product-card :product="$product" :key="'product-' . $product->id" />
                    @empty
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
                            <flux:button wire:click="clearAllFilters" variant="primary">
                                Clear Filters
                            </flux:button>
                        </section>
                    @endforelse
                </div>

                @if ($this->products->hasPages())
                    <div class="mt-8">
                        {{ $this->products->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>

<style>
    input[type="range"]::-webkit-slider-thumb {
        pointer-events: auto;
    }

    input[type="range"]::-moz-range-thumb {
        pointer-events: auto;
    }
</style>
