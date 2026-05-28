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
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::storefront')] class extends Component {
    use InteractsWithStorefront;

    public Category $category;

    public int $perPage = 24;

    /** @var array<int, int> */
    #[Url(as: 'brand', history: true)]
    public array $selectedBrands = [];

    #[Url(history: true)]
    public int $priceMax = 6000000;

    #[Url(as: 'stock', history: true)]
    public bool $inStockOnly = false;

    #[Url(history: true)]
    public string $sort = 'popularity';

    public function mount(Category $category): void
    {
        $this->category = $category;

        $title = $category->meta_title ?: $category->name . ' — Sheffield';
        $description = $category->meta_description ?: ($category->description ? \Illuminate\Support\Str::limit(strip_tags($category->description), 160) : null) ?: 'Browse ' . $category->name . ' from authorised distributors. Stock in Nairobi, install & service across East Africa.';

        SEOMeta::setTitle($title)->setDescription($description);
        OpenGraph::setTitle($title)->setDescription($description)->setType('website');
        TwitterCard::setTitle($title)->setDescription($description);

        if ($category->image) {
            $url = \Illuminate\Support\Facades\Storage::url($category->image);
            OpenGraph::addImage($url);
            TwitterCard::setImage($url);
        }

        if ($category->canonical_url) {
            SEOMeta::setCanonical($category->canonical_url);
        }
    }

    public function rendering($view): void
    {
        // Title was set in mount() via SEOMeta; mirror for layouts that read $title.
        $view->title($this->category->meta_title ?: $this->category->name . ' — Sheffield');
    }

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
        $this->reset(['selectedBrands', 'inStockOnly']);
        $this->priceMax = 6000000;
        $this->perPage = 24;
        unset($this->products);
    }

    public function removeBrand(int $id): void
    {
        $this->selectedBrands = array_values(array_filter($this->selectedBrands, fn($b) => $b !== $id));
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['brand', 'images' => fn($q) => $q->where('is_cover', true)->limit(1)])
            ->where('visibility', 'visible')
            ->where(function ($q) {
                $q->where('primary_category_id', $this->category->id)->orWhereHas('categories', fn($q2) => $q2->where('categories.id', $this->category->id));
            });

        if ($this->selectedBrands) {
            $query->whereIn('brand_id', $this->selectedBrands);
        }

        if ($this->inStockOnly) {
            $query->where('stock_status', StockStatus::IN_STOCK->value);
        }

        $query->where(function ($q) {
            $q->whereNull('price')->orWhere('price', '<=', $this->priceMax * 100);
        });

        match ($this->sort) {
            'price-asc' => $query->orderByRaw('price IS NULL, price ASC'),
            'price-desc' => $query->orderByRaw('price IS NULL, price DESC'),
            'name-asc' => $query->orderBy('name'),
            'newest' => $query->latest('id'),
            default => $query->orderBy('sort_order')->orderByDesc('id'),
        };

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function brandsList(): Collection
    {
        // Only brands that have products in this category
        return Brand::query()
            ->where('is_active', true)
            ->whereHas('products', function ($q) {
                $q->where('visibility', 'visible')->where(function ($q2) {
                    $q2->where('primary_category_id', $this->category->id)->orWhereHas('categories', fn($q3) => $q3->where('categories.id', $this->category->id));
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function hasActiveFilters(): bool
    {
        return !empty($this->selectedBrands) || $this->inStockOnly || $this->priceMax < 6000000;
    }
}; ?>

@php
    $kes = fn($cents) => 'KES&nbsp;' . number_format(intdiv($cents, 100), 0, '.', ',');
    $kesWhole = fn($whole) => 'KES&nbsp;' . number_format($whole, 0, '.', ',');
@endphp

<div class="page-fade">
    {{-- Category hero --}}
    <div class="border-b border-zinc-200 bg-surface-sunken py-8">
        <div class="shell grid grid-cols-1 items-center gap-6 md:grid-cols-[1fr_200px]">
            <div class="flex items-center gap-4">
                @if ($category->icon)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($category->icon) }}" alt=""
                        class="size-12 shrink-0 object-contain" />
                @endif
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight">{{ $category->name }}</h1>
                    @if ($category->description)
                        <p class="mt-2 max-w-xl text-[14px] text-ink-3">{{ $category->description }}</p>
                    @endif
                </div>
            </div>
            @if ($category->image)
                <div class="hidden h-30 w-50 overflow-hidden rounded md:block">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($category->image) }}" alt=""
                        class="size-full object-cover" />
                </div>
            @endif
        </div>
    </div>

    <div class="shell pt-4 pb-20">
        {{-- Breadcrumb --}}
        <nav class="mb-4 flex items-center gap-1.5 text-[12.5px] text-ink-3" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink" wire:navigate>Home</a>
            <flux:icon.chevron-right variant="micro" class="size-3" />
            <a href="{{ route('catalog') }}" class="hover:text-ink" wire:navigate>Shop</a>
            <flux:icon.chevron-right variant="micro" class="size-3" />
            <span class="text-ink">{{ $category->name }}</span>
        </nav>

        <div class="mt-4 grid grid-cols-1 gap-8 lg:grid-cols-[260px_1fr]">
            {{-- Filters sidebar (no category filter — already scoped) --}}
            <aside class="lg:sticky lg:top-32 lg:self-start" x-data="{ openBrands: false }">
                <div class="flex flex-col gap-7 text-sm">
                    @if ($this->brandsList->isNotEmpty())
                        <div>
                            <div
                                class="mb-3 border-b border-zinc-200 pb-1.5 text-[12px] font-bold tracking-[0.08em] text-ink-2 uppercase">
                                Brand</div>
                            <div class="flex flex-col gap-2">
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
                    @endif

                    <div>
                        <div
                            class="mb-3 border-b border-zinc-200 pb-1.5 text-[12px] font-bold tracking-[0.08em] text-ink-2 uppercase">
                            Price</div>
                        <div class="flex justify-between text-[12.5px] text-ink-3">
                            <span>KES 0</span>
                            <span class="font-semibold text-ink">up to {!! $kesWhole($priceMax) !!}</span>
                        </div>
                        <input type="range" min="50000" max="6000000" step="50000"
                            wire:model.live.debounce.300ms="priceMax" class="mt-2 w-full accent-brand-500" />
                    </div>

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

                @if ($this->products->isEmpty())
                    <div class="rounded-md bg-surface-sunken p-16 text-center">
                        <div class="font-serif text-2xl text-ink">No products in this category yet</div>
                        <p class="mt-2 text-ink-3">Try removing filters, or browse the full catalog.</p>
                        <div class="mt-5 flex justify-center gap-2">
                            @if ($this->hasActiveFilters())
                                <flux:button wire:click="clearFilters">Clear filters</flux:button>
                            @endif
                            <flux:button variant="primary" href="{{ route('catalog') }}" wire:navigate>Browse all
                                products</flux:button>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                        @foreach ($this->products as $product)
                            <x-storefront.product-card :product="$product" wire:key="prod-{{ $product->id }}" />
                        @endforeach
                    </div>
                @endif

                @if ($this->products->hasMorePages())
                    <div wire:intersect.margin.200px="loadMore" class="mt-10 flex justify-center py-6">
                        <flux:icon.loading class="size-6 text-ink-4" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
