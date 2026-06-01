<?php

use App\Enums\StockStatus;
use App\Livewire\Concerns\InteractsWithStorefront;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Shop — Sheffield')] class extends Component {
    use InteractsWithStorefront;

    public int $perPage = 24;

    public function mount(): void
    {
        $description = 'Browse Sheffield\'s full commercial kitchen catalog — ovens, refrigeration, preparation, warewashing, beverage and more. Filter by brand, price, stock and category.';

        SEOMeta::setDescription($description);
        OpenGraph::setDescription($description)->setType('website');
        TwitterCard::setDescription($description);
    }

    /** @var array<int, string> */
    #[Url(as: 'cat', history: true)]
    public array $selectedCategories = [];

    /** @var array<int, int> */
    #[Url(as: 'brand', history: true)]
    public array $selectedBrands = [];

    /** Price slider max in KES (whole units). DB stores cents. */
    #[Url(history: true)]
    public int $priceMax = 6000000;

    #[Url(as: 'stock', history: true)]
    public bool $inStockOnly = false;

    #[Url(history: true)]
    public string $sort = 'popularity';

    public function updating(string $prop): void
    {
        $this->perPage = 24;
        unset($this->products);
    }

    public function loadMore(): void
    {
        $this->perPage += 12;
        unset($this->products);
    }

    public function clearFilters(): void
    {
        $this->reset(['selectedCategories', 'selectedBrands', 'inStockOnly']);
        $this->priceMax = 6000000;
        $this->perPage = 24;
        unset($this->products);
    }

    public function removeCategory(string $slug): void
    {
        $this->selectedCategories = array_values(array_filter($this->selectedCategories, fn($s) => $s !== $slug));
    }

    public function removeBrand(int $id): void
    {
        $this->selectedBrands = array_values(array_filter($this->selectedBrands, fn($b) => $b !== $id));
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['brand', 'taxClass', 'images' => fn($q) => $q->where('is_cover', true)->limit(1)])
            ->where('visibility', 'visible')
            ->honorStockVisibility();

        if ($this->selectedCategories) {
            $catIds = Category::whereIn('slug', $this->selectedCategories)->pluck('id');
            $query->where(function ($q) use ($catIds) {
                $q->whereIn('primary_category_id', $catIds)->orWhereHas('categories', fn($q2) => $q2->whereIn('categories.id', $catIds));
            });
        }

        if ($this->selectedBrands) {
            $query->whereIn('brand_id', $this->selectedBrands);
        }

        if ($this->inStockOnly) {
            $query->where('stock_status', StockStatus::IN_STOCK->value);
        }

        // priceMax in KES → cents
        $query->where(function ($q) {
            $q->whereNull('price')->orWhere('price', '<=', $this->priceMax * 100);
        });

        match ($this->sort) {
            'price-asc' => $query->orderByRaw('price IS NULL, price ASC'),
            'price-desc' => $query->orderByRaw('price IS NULL, price DESC'),
            'name-asc' => $query->orderBy('name'),
            'newest' => $query->latest('id'),
            default => $query->orderBy('sort_order')->orderByDesc('id'), // popularity proxy
        };

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function categoriesList(): Collection
    {
        return Category::query()->withCount('products')->orderBy('sort_order')->orderBy('name')->get();
    }

    #[Computed]
    public function brandsList(): Collection
    {
        return Brand::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function hasActiveFilters(): bool
    {
        return !empty($this->selectedCategories) || !empty($this->selectedBrands) || $this->inStockOnly || $this->priceMax < 6000000;
    }
}; ?>

<div class="page-fade">
    <div class="shell pt-4 pb-20">
        {{-- Breadcrumb --}}
        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Shop</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-end justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Catalog</h1>
                <p class="mt-2 max-w-xl text-[14.5px] text-ink-3">
                    Commercial kitchen equipment across {{ $this->categoriesList->count() }} categories from
                    {{ $this->brandsList->count() }} authorised brands.
                </p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[260px_1fr]">
            {{-- Filters sidebar --}}
            <aside class="lg:sticky lg:top-32 lg:self-start" x-data="{ openBrands: false }">
                <div class="flex flex-col gap-7 text-sm">
                    {{-- Category --}}
                    <div>
                        <div
                            class="mb-3 border-b border-zinc-200 pb-1.5 text-[12px] font-bold tracking-[0.08em] text-ink-2 uppercase">
                            Category</div>
                        <div class="flex max-h-72 flex-col gap-2 overflow-y-auto pr-1">
                            @foreach ($this->categoriesList as $cat)
                                {{-- Verbose form: flux:label's `trailing` slot pushes the product count
                                     to `ml-auto`, giving us the same name + count layout we want. --}}
                                <flux:field variant="inline">
                                    <flux:checkbox wire:model.live="selectedCategories" value="{{ $cat->slug }}" />
                                    <flux:label>
                                        {{ $cat->name }}
                                        <x-slot:trailing>
                                            <span
                                                class="text-xs text-ink-4 tabular-nums">{{ $cat->products_count }}</span>
                                        </x-slot:trailing>
                                    </flux:label>
                                </flux:field>
                            @endforeach
                        </div>
                    </div>

                    {{-- Brand --}}
                    <div>
                        <div
                            class="mb-3 border-b border-zinc-200 pb-1.5 text-[12px] font-bold tracking-[0.08em] text-ink-2 uppercase">
                            Brand</div>
                        <div class="flex flex-col gap-2" :class="openBrands ? 'max-h-96 overflow-y-auto pr-1' : ''">
                            @foreach ($this->brandsList as $i => $brand)
                                <div @if ($i >= 6) x-show="openBrands" x-cloak @endif>
                                    <flux:checkbox wire:model.live="selectedBrands" value="{{ $brand->id }}"
                                        :label="$brand->name" />
                                </div>
                            @endforeach
                        </div>
                        @if ($this->brandsList->count() > 6)
                            <button type="button" @click="openBrands = !openBrands"
                                class="mt-2 cursor-pointer text-[12.5px] text-brand-500 hover:underline">
                                <span x-show="!openBrands" class="inline-flex items-center gap-1">Show all
                                    {{ $this->brandsList->count() }} brands <flux:icon.arrow-right variant="micro"
                                        class="size-3.5" /></span>
                                <span x-show="openBrands" x-cloak>Show fewer</span>
                            </button>
                        @endif
                    </div>

                    {{-- Price --}}
                    <div>
                        <div
                            class="mb-3 border-b border-zinc-200 pb-1.5 text-[12px] font-bold tracking-[0.08em] text-ink-2 uppercase">
                            Price</div>
                        <div class="flex justify-between text-[12.5px] text-ink-3">
                            <span>{{ money(0) }}</span>
                            <span class="font-semibold text-ink">up to {{ money($priceMax * 100) }}</span>
                        </div>
                        <input type="range" min="50000" max="6000000" step="50000"
                            wire:model.live.debounce.300ms="priceMax" class="mt-2 w-full accent-brand-500" />
                    </div>

                    {{-- Availability --}}
                    <div>
                        <div
                            class="mb-3 border-b border-zinc-200 pb-1.5 text-[12px] font-bold tracking-[0.08em] text-ink-2 uppercase">
                            Availability</div>
                        <flux:checkbox wire:model.live="inStockOnly" label="In stock — ships now" />
                    </div>
                </div>
            </aside>

            {{-- Results --}}
            <div>
                {{-- Toolbar --}}
                <div
                    class="mb-5 flex flex-col gap-3 border-b border-zinc-200 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-[13.5px] text-ink-3">
                        Showing <span class="font-semibold text-ink">{{ $this->products->total() }}</span>
                        {{ \Illuminate\Support\Str::plural('product', $this->products->total()) }}
                        @if ($this->hasActiveFilters())
                            <button type="button" wire:click="clearFilters"
                                class="ml-2.5 cursor-pointer text-[13px] text-brand-500 underline-offset-2 hover:underline">
                                Clear filters
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center gap-2.5">
                        <label class="text-[13px] text-ink-3">Sort:</label>
                        <select wire:model.live="sort"
                            class="h-9 rounded border border-zinc-200 bg-white px-2.5 text-[13px] focus:border-brand-500 focus:ring-0 focus:outline-none">
                            <option value="popularity">Most popular</option>
                            <option value="newest">Newest</option>
                            <option value="name-asc">Name (A–Z)</option>
                            <option value="price-asc">Price — low to high</option>
                            <option value="price-desc">Price — high to low</option>
                        </select>
                    </div>
                </div>

                {{-- Active filter chips --}}
                @if ($this->hasActiveFilters())
                    <div class="mb-5 flex flex-wrap gap-2">
                        @foreach ($selectedCategories as $slug)
                            @php $cat = $this->categoriesList->firstWhere('slug', $slug); @endphp
                            @if ($cat)
                                <button type="button" wire:click="removeCategory('{{ $slug }}')"
                                    class="inline-flex h-7 cursor-pointer items-center gap-1.5 rounded-full bg-surface-sunken px-3 text-[12.5px] font-medium text-ink-2 hover:bg-zinc-200">
                                    {{ $cat->name }}
                                    <flux:icon.x variant="micro" class="size-3 text-ink-3" />
                                </button>
                            @endif
                        @endforeach
                        @foreach ($selectedBrands as $id)
                            @php $br = $this->brandsList->firstWhere('id', $id); @endphp
                            @if ($br)
                                <button type="button" wire:click="removeBrand({{ $id }})"
                                    class="inline-flex h-7 cursor-pointer items-center gap-1.5 rounded-full bg-surface-sunken px-3 text-[12.5px] font-medium text-ink-2 hover:bg-zinc-200">
                                    {{ $br->name }}
                                    <flux:icon.x variant="micro" class="size-3 text-ink-3" />
                                </button>
                            @endif
                        @endforeach
                        @if ($inStockOnly)
                            <button type="button" wire:click="$toggle('inStockOnly')"
                                class="inline-flex h-7 cursor-pointer items-center gap-1.5 rounded-full bg-surface-sunken px-3 text-[12.5px] font-medium text-ink-2 hover:bg-zinc-200">
                                In stock only
                                <flux:icon.x variant="micro" class="size-3 text-ink-3" />
                            </button>
                        @endif
                        @if ($priceMax < 6000000)
                            <button type="button" wire:click="$set('priceMax', 6000000)"
                                class="inline-flex h-7 cursor-pointer items-center gap-1.5 rounded-full bg-surface-sunken px-3 text-[12.5px] font-medium text-ink-2 hover:bg-zinc-200">
                                Up to {{ money($priceMax * 100) }}
                                <flux:icon.x variant="micro" class="size-3 text-ink-3" />
                            </button>
                        @endif
                    </div>
                @endif

                {{-- Results body --}}
                @if ($this->products->isEmpty())
                    <div class="rounded-md bg-surface-sunken p-16 text-center">
                        <div class="font-serif text-2xl text-ink">No products match these filters</div>
                        <p class="mt-2 text-ink-3">Try widening your price range, or removing brand/category
                            constraints.</p>
                        <flux:button variant="primary" wire:click="clearFilters" class="mt-5">Clear all filters
                        </flux:button>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                        @foreach ($this->products as $product)
                            <x-storefront.product-card :product="$product" wire:key="prod-{{ $product->id }}" />
                        @endforeach
                    </div>
                @endif

                {{-- Infinite scroll sentinel --}}
                @if ($this->products->hasMorePages())
                    <div wire:intersect.margin.200px="loadMore" class="mt-10 flex justify-center py-6">
                        <flux:icon.loading class="size-6 text-ink-4" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
