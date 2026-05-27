<?php

use App\Enums\StockStatus;
use App\Livewire\Concerns\InteractsWithStorefront;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::storefront')] #[Title('Shop — Sheffield')] class extends Component
{
    use InteractsWithStorefront;
    use WithPagination;

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

    #[Url(history: true)]
    public string $view = 'grid';

    public function updating(string $prop): void
    {
        // Reset pagination when any filter or sort changes
        if ($prop !== 'view') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['selectedCategories', 'selectedBrands', 'inStockOnly']);
        $this->priceMax = 6000000;
        $this->resetPage();
    }

    public function removeCategory(string $slug): void
    {
        $this->selectedCategories = array_values(array_filter($this->selectedCategories, fn ($s) => $s !== $slug));
        $this->resetPage();
    }

    public function removeBrand(int $id): void
    {
        $this->selectedBrands = array_values(array_filter($this->selectedBrands, fn ($b) => $b !== $id));
        $this->resetPage();
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['brand', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->where('visibility', 'visible');

        if ($this->selectedCategories) {
            $catIds = Category::whereIn('slug', $this->selectedCategories)->pluck('id');
            $query->where(function ($q) use ($catIds) {
                $q->whereIn('primary_category_id', $catIds)
                    ->orWhereHas('categories', fn ($q2) => $q2->whereIn('categories.id', $catIds));
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
            'price-asc'  => $query->orderByRaw('price IS NULL, price ASC'),
            'price-desc' => $query->orderByRaw('price IS NULL, price DESC'),
            'name-asc'   => $query->orderBy('name'),
            'newest'     => $query->latest('id'),
            default      => $query->orderBy('sort_order')->orderByDesc('id'), // popularity proxy
        };

        return $query->paginate(12)->withQueryString();
    }

    #[Computed]
    public function categoriesList(): Collection
    {
        return Category::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function brandsList(): Collection
    {
        return Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function hasActiveFilters(): bool
    {
        return ! empty($this->selectedCategories)
            || ! empty($this->selectedBrands)
            || $this->inStockOnly
            || $this->priceMax < 6000000;
    }
}; ?>

@php
    $kes = fn ($cents) => 'KES&nbsp;' . number_format(intdiv($cents, 100), 0, '.', ',');
    $kesWhole = fn ($whole) => 'KES&nbsp;' . number_format($whole, 0, '.', ',');
@endphp

<div class="page-fade">
    <div class="shell pt-8 pb-20">
        {{-- Breadcrumb --}}
        <nav class="mb-4 flex items-center gap-1.5 text-[12.5px] text-ink-3" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink" wire:navigate>Home</a>
            <flux:icon.chevron-right variant="micro" class="size-3" />
            <span class="text-ink">Shop</span>
        </nav>

        {{-- Header --}}
        <div class="flex items-end justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Catalog</h1>
                <p class="mt-2 max-w-xl text-[14.5px] text-ink-3">
                    Commercial kitchen equipment across {{ $this->categoriesList->count() }} categories from {{ $this->brandsList->count() }} authorised brands.
                </p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[260px_1fr]">
            {{-- Filters sidebar --}}
            <aside class="lg:sticky lg:top-44 lg:self-start" x-data="{ openBrands: false }">
                <div class="flex flex-col gap-7 text-sm">
                    {{-- Category --}}
                    <div>
                        <div class="mb-3 border-b border-zinc-200 pb-1.5 text-[12px] font-bold tracking-[0.08em] text-ink-2 uppercase">Category</div>
                        <div class="flex max-h-72 flex-col gap-2 overflow-y-auto pr-1">
                            @foreach ($this->categoriesList as $cat)
                                <label class="group flex cursor-pointer items-center gap-2.5 text-[13.5px] text-ink-2 hover:text-ink">
                                    <input type="checkbox" wire:model.live="selectedCategories" value="{{ $cat->slug }}"
                                        class="size-4 rounded-sm border-1.5 border-line-strong accent-brand-500" />
                                    <span class="flex-1">{{ $cat->name }}</span>
                                    <span class="text-xs text-ink-4">{{ $cat->products_count }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Brand --}}
                    <div>
                        <div class="mb-3 border-b border-zinc-200 pb-1.5 text-[12px] font-bold tracking-[0.08em] text-ink-2 uppercase">Brand</div>
                        <div class="flex flex-col gap-2"
                            :class="openBrands ? 'max-h-96 overflow-y-auto pr-1' : ''">
                            @foreach ($this->brandsList as $i => $brand)
                                <label class="flex cursor-pointer items-center gap-2.5 text-[13.5px] text-ink-2 hover:text-ink"
                                    @if ($i >= 6) x-show="openBrands" @endif>
                                    <input type="checkbox" wire:model.live="selectedBrands" value="{{ $brand->id }}"
                                        class="size-4 rounded-sm border-1.5 border-line-strong accent-brand-500" />
                                    <span class="flex-1">{{ $brand->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @if ($this->brandsList->count() > 6)
                            <button type="button" @click="openBrands = !openBrands"
                                class="mt-2 text-[12.5px] text-brand-500 hover:underline">
                                <span x-show="!openBrands">Show all {{ $this->brandsList->count() }} brands →</span>
                                <span x-show="openBrands" x-cloak>Show fewer</span>
                            </button>
                        @endif
                    </div>

                    {{-- Price --}}
                    <div>
                        <div class="mb-3 border-b border-zinc-200 pb-1.5 text-[12px] font-bold tracking-[0.08em] text-ink-2 uppercase">Price</div>
                        <div class="flex justify-between text-[12.5px] text-ink-3">
                            <span>KES 0</span>
                            <span class="font-semibold text-ink">up to {!! $kesWhole($priceMax) !!}</span>
                        </div>
                        <input type="range" min="50000" max="6000000" step="50000"
                            wire:model.live.debounce.300ms="priceMax"
                            class="mt-2 w-full accent-brand-500" />
                    </div>

                    {{-- Availability --}}
                    <div>
                        <div class="mb-3 border-b border-zinc-200 pb-1.5 text-[12px] font-bold tracking-[0.08em] text-ink-2 uppercase">Availability</div>
                        <label class="flex cursor-pointer items-center gap-2.5 text-[13.5px] text-ink-2 hover:text-ink">
                            <input type="checkbox" wire:model.live="inStockOnly"
                                class="size-4 rounded-sm border-1.5 border-line-strong accent-brand-500" />
                            <span>In stock — ships now</span>
                        </label>
                    </div>
                </div>
            </aside>

            {{-- Results --}}
            <div>
                {{-- Toolbar --}}
                <div class="mb-5 flex flex-col gap-3 border-b border-zinc-200 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-[13.5px] text-ink-3">
                        Showing <span class="font-semibold text-ink">{{ $this->products->total() }}</span>
                        {{ \Illuminate\Support\Str::plural('product', $this->products->total()) }}
                        @if ($this->hasActiveFilters())
                            <button type="button" wire:click="clearFilters"
                                class="ml-2.5 text-[13px] text-brand-500 underline-offset-2 hover:underline">
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
                        <div class="inline-flex rounded border border-zinc-200">
                            <button wire:click="$set('view', 'grid')" aria-label="Grid view"
                                class="inline-flex size-9 items-center justify-center transition {{ $view === 'grid' ? 'bg-surface-sunken text-ink' : 'text-ink-3 hover:text-ink' }}">
                                <flux:icon.layout-grid variant="micro" class="size-4" />
                            </button>
                            <button wire:click="$set('view', 'rows')" aria-label="List view"
                                class="inline-flex size-9 items-center justify-center transition {{ $view === 'rows' ? 'bg-surface-sunken text-ink' : 'text-ink-3 hover:text-ink' }}">
                                <flux:icon.list variant="micro" class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Active filter chips --}}
                @if ($this->hasActiveFilters())
                    <div class="mb-5 flex flex-wrap gap-2">
                        @foreach ($selectedCategories as $slug)
                            @php $cat = $this->categoriesList->firstWhere('slug', $slug); @endphp
                            @if ($cat)
                                <button type="button" wire:click="removeCategory('{{ $slug }}')"
                                    class="inline-flex h-7 items-center gap-1.5 rounded-full bg-surface-sunken px-3 text-[12.5px] font-medium text-ink-2 hover:bg-zinc-200">
                                    {{ $cat->name }}
                                    <flux:icon.x variant="micro" class="size-3 text-ink-3" />
                                </button>
                            @endif
                        @endforeach
                        @foreach ($selectedBrands as $id)
                            @php $br = $this->brandsList->firstWhere('id', $id); @endphp
                            @if ($br)
                                <button type="button" wire:click="removeBrand({{ $id }})"
                                    class="inline-flex h-7 items-center gap-1.5 rounded-full bg-surface-sunken px-3 text-[12.5px] font-medium text-ink-2 hover:bg-zinc-200">
                                    {{ $br->name }}
                                    <flux:icon.x variant="micro" class="size-3 text-ink-3" />
                                </button>
                            @endif
                        @endforeach
                        @if ($inStockOnly)
                            <button type="button" wire:click="$toggle('inStockOnly')"
                                class="inline-flex h-7 items-center gap-1.5 rounded-full bg-surface-sunken px-3 text-[12.5px] font-medium text-ink-2 hover:bg-zinc-200">
                                In stock only
                                <flux:icon.x variant="micro" class="size-3 text-ink-3" />
                            </button>
                        @endif
                        @if ($priceMax < 6000000)
                            <button type="button" wire:click="$set('priceMax', 6000000)"
                                class="inline-flex h-7 items-center gap-1.5 rounded-full bg-surface-sunken px-3 text-[12.5px] font-medium text-ink-2 hover:bg-zinc-200">
                                Up to {!! $kesWhole($priceMax) !!}
                                <flux:icon.x variant="micro" class="size-3 text-ink-3" />
                            </button>
                        @endif
                    </div>
                @endif

                {{-- Results body --}}
                @if ($this->products->isEmpty())
                    <div class="rounded-lg bg-surface-sunken p-16 text-center">
                        <div class="font-serif text-2xl text-ink">No products match these filters</div>
                        <p class="mt-2 text-ink-3">Try widening your price range, or removing brand/category constraints.</p>
                        <flux:button variant="primary" wire:click="clearFilters" class="mt-5">Clear all filters</flux:button>
                    </div>
                @elseif ($view === 'grid')
                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($this->products as $product)
                            <x-storefront.product-card :product="$product" wire:key="prod-{{ $product->id }}" />
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col gap-3">
                        @foreach ($this->products as $product)
                            @php
                                $cover = $product->images->first();
                                $price = $product->sale_price ?? $product->price;
                                $compareAt = $product->sale_price ? $product->price : null;
                                $inStock = $product->stock_status === StockStatus::IN_STOCK;
                            @endphp
                            <article wire:key="row-{{ $product->id }}"
                                class="grid cursor-pointer grid-cols-[140px_1fr_auto_auto] items-center gap-5 rounded border border-zinc-200 bg-white p-4 transition hover:border-zinc-400 hover:shadow-sm">
                                <div class="h-28 w-[140px] overflow-hidden rounded bg-surface-sunken p-2">
                                    @if ($cover)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($cover->path) }}" alt="" class="size-full object-contain" loading="lazy" />
                                    @endif
                                </div>
                                <div>
                                    @if ($product->brand)
                                        <div class="text-[11.5px] font-bold tracking-[0.06em] text-brand-blue-600 uppercase">{{ $product->brand->name }}</div>
                                    @endif
                                    <div class="mt-1 text-[15px] font-medium leading-tight">{{ $product->name }}</div>
                                    @if ($product->short_description)
                                        <div class="mt-1 line-clamp-2 max-w-2xl text-[13px] text-ink-3">{{ $product->short_description }}</div>
                                    @endif
                                    <div class="mt-2 text-[11.5px] text-ink-4 tabular-nums">{{ $product->sku }}</div>
                                </div>
                                <div class="text-right">
                                    @if ($compareAt)
                                        <div class="text-[12px] text-ink-4 line-through">{!! $kes($compareAt) !!}</div>
                                    @endif
                                    <div class="font-serif text-[22px]">
                                        {!! $price ? $kes($price) : 'Request quote' !!}
                                    </div>
                                    <div class="text-[11.5px] {{ $inStock ? 'text-emerald-700' : 'text-ink-3' }}">
                                        {{ $inStock ? '● In stock' : 'Made to order' }}
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <flux:button variant="primary" size="sm">Add to cart</flux:button>
                                    <flux:button size="sm">Compare</flux:button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif

                {{-- Pagination --}}
                @if ($this->products->hasPages())
                    <div class="mt-10">
                        {{ $this->products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
