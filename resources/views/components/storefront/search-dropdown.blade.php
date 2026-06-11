<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $query = '';

    #[Computed]
    public function products(): Collection
    {
        if (strlen(trim($this->query)) < 2) {
            return collect();
        }

        return Product::query()
            ->with(['brand', 'images' => fn($q) => $q->where('is_cover', true)->limit(1)])
            ->visibleInSearch()
            ->published()
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->query}%")
                    ->orWhere('sku', 'like', "%{$this->query}%")
                    ->orWhere('model_number', 'like', "%{$this->query}%")
                    ->orWhereHas('brand', fn($q2) => $q2->where('name', 'like', "%{$this->query}%"));
            })
            ->take(5)
            ->get();
    }

    #[Computed]
    public function categories(): Collection
    {
        if (strlen(trim($this->query)) < 2) {
            return collect();
        }

        return Category::query()
            ->withCount('products')
            ->where('name', 'like', "%{$this->query}%")
            ->take(3)
            ->get();
    }

    #[Computed]
    public function brands(): Collection
    {
        if (strlen(trim($this->query)) < 2) {
            return collect();
        }

        return Brand::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$this->query}%")
            ->take(3)
            ->get();
    }

    public function hasResults(): bool
    {
        return $this->products->isNotEmpty() || $this->categories->isNotEmpty() || $this->brands->isNotEmpty();
    }
}; ?>

@php
    $trending = ['Combi oven', 'Blast chiller', 'Rational', 'Hobart mixer', 'Espresso machine', 'Refrigeration'];
@endphp

<div class="relative w-full" x-data="{
    show: false,
    recent: JSON.parse(localStorage.getItem('sheffield-recent') || '[]'),
    saveRecent(term) {
        this.recent = [term, ...this.recent.filter(r => r !== term)].slice(0, 6);
        localStorage.setItem('sheffield-recent', JSON.stringify(this.recent));
    },
    clearRecent() {
        this.recent = [];
        localStorage.removeItem('sheffield-recent');
    },
}" x-on:keydown.escape.window="show = false"
    x-on:mousedown.outside="show = false">

    {{-- Input --}}
    <flux:input wire:model.live.debounce.200ms="query" @focus="show = true"
        @keydown.enter.prevent="
            if ($wire.query.trim()) {
                saveRecent($wire.query.trim());
                window.location = '{{ route('catalog') }}?q=' + encodeURIComponent($wire.query.trim());
            }
        "
        autocomplete="off" spellcheck="false" icon="magnifying-glass" clearable kbd="⌘K"
        placeholder="Search ovens, refrigeration, brands, SKU..." />

    {{-- Dropdown panel --}}
    <div x-show="show" x-cloak x-transition:enter="transition duration-[140ms] ease-out"
        x-transition:enter-start="opacity-0 -translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="scrollbar-thin static mt-3 max-h-[calc(100vh-180px)] overflow-y-auto border-t border-zinc-100 bg-white
               lg:absolute lg:top-[calc(100%+8px)] lg:left-0 lg:right-0 lg:z-50 lg:mt-0 lg:max-h-[calc(100vh-120px)] lg:rounded-md lg:border lg:border-zinc-200 lg:shadow-xl">

        @if (strlen(trim($query)) < 2)
            {{-- Empty state: recent + trending --}}
            <div x-show="recent.length > 0">
                <div class="flex items-center justify-between px-4 py-2.5">
                    <span class="text-[10.5px] font-bold tracking-[0.1em] text-ink-4 uppercase">Recent</span>
                    <button type="button" @click="clearRecent()"
                        class="cursor-pointer text-[11px] text-ink-4 underline underline-offset-2 hover:text-ink">Clear</button>
                </div>
                <template x-for="term in recent" :key="term">
                    <button type="button" @click="$wire.set('query', term)"
                        class="flex w-full cursor-pointer items-center gap-3 px-4 py-2.5 text-left text-[13.5px] text-ink-2 hover:bg-surface-sunken">
                        <flux:icon.clock variant="micro" class="size-3.5 shrink-0 text-ink-4" />
                        <span x-text="term"></span>
                    </button>
                </template>
            </div>

            <div class="border-t border-zinc-100 px-4 pb-4 pt-3">
                <div class="mb-2.5 text-[10.5px] font-bold tracking-[0.1em] text-ink-4 uppercase">Trending</div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($trending as $term)
                        <button type="button" wire:click="$set('query', '{{ $term }}')"
                            class="inline-flex h-7 cursor-pointer items-center rounded-full bg-surface-sunken px-3 text-[12.5px] font-medium text-ink-2 transition hover:bg-zinc-200">
                            {{ $term }}
                        </button>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Search for "query" header row --}}
            <a href="{{ route('catalog') }}?q={{ urlencode($query) }}" wire:navigate
                @click="saveRecent('{{ addslashes($query) }}')"
                class="flex items-center gap-3 px-4 py-3 text-[13.5px] text-ink-2 hover:bg-surface-sunken">
                <flux:icon.magnifying-glass variant="micro" class="size-3.5 shrink-0 text-ink-4" />
                <span>Search Sheffield for <strong class="text-ink">"{{ $query }}"</strong></span>
                <flux:icon.arrow-right variant="micro" class="ml-auto size-3.5 text-ink-4" />
            </a>

            @if ($this->hasResults())

                {{-- Products --}}
                @if ($this->products->isNotEmpty())
                    <div class="border-t border-zinc-100">
                        <div class="px-4 py-2 text-[10.5px] font-bold tracking-[0.1em] text-ink-4 uppercase">
                            Products <span class="text-ink-5 ml-1">({{ $this->products->count() }})</span>
                        </div>
                        @foreach ($this->products as $product)
                            @php $price = $product->sale_price ?? $product->price; @endphp
                            <a href="{{ route('product.show', $product) }}" wire:navigate
                                @click="saveRecent('{{ addslashes($product->name) }}')"
                                class="grid cursor-pointer grid-cols-[44px_1fr_auto] items-center gap-3 px-4 py-2.5 hover:bg-surface-sunken">
                                <div
                                    class="size-11 overflow-hidden rounded border border-zinc-100 bg-surface-sunken p-1">
                                    @if ($product->cover_url)
                                        <img src="{{ $product->cover_url }}" alt=""
                                            class="size-full object-contain" loading="lazy" />
                                    @else
                                        <flux:icon.photo class="size-full text-ink-4" />
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    @if ($product->brand)
                                        <div
                                            class="text-[10.5px] font-bold tracking-[0.08em] text-brand-blue-600 uppercase">
                                            {{ $product->brand->name }}</div>
                                    @endif
                                    <div class="truncate text-[13.5px] text-ink">{{ $product->name }}</div>
                                    <div class="text-[11px] text-ink-4 tabular-nums">{{ $product->sku }}</div>
                                </div>
                                @if ($price)
                                    <div
                                        class="text-right text-[13px] font-semibold text-ink tabular-nums whitespace-nowrap">
                                        {{ money($price) }}
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Categories --}}
                @if ($this->categories->isNotEmpty())
                    <div class="border-t border-zinc-100">
                        <div class="px-4 py-2 text-[10.5px] font-bold tracking-[0.1em] text-ink-4 uppercase">Categories
                        </div>
                        @foreach ($this->categories as $category)
                            <a href="{{ route('category.show', $category) }}" wire:navigate
                                @click="saveRecent('{{ addslashes($category->name) }}')"
                                class="grid cursor-pointer grid-cols-[20px_1fr_auto] items-center gap-3 px-4 py-2.5 hover:bg-surface-sunken">
                                <flux:icon.squares-2x2 variant="micro" class="size-3.5 text-ink-4" />
                                <span class="text-[13.5px] text-ink">{{ $category->name }}</span>
                                <span
                                    class="text-[11px] text-ink-4 tabular-nums">{{ $category->products_count }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Brands --}}
                @if ($this->brands->isNotEmpty())
                    <div class="border-t border-zinc-100">
                        <div class="px-4 py-2 text-[10.5px] font-bold tracking-[0.1em] text-ink-4 uppercase">Brands
                        </div>
                        @foreach ($this->brands as $brand)
                            <a href="{{ route('catalog') }}?brand={{ $brand->id }}" wire:navigate
                                @click="saveRecent('{{ addslashes($brand->name) }}')"
                                class="grid cursor-pointer grid-cols-[20px_1fr_auto] items-center gap-3 px-4 py-2.5 hover:bg-surface-sunken">
                                <span
                                    class="inline-flex size-4 items-center justify-center rounded bg-surface-sunken text-[9px] font-bold text-ink-3 border border-zinc-200">
                                    {{ strtoupper($brand->name[0]) }}
                                </span>
                                <span class="text-[13.5px] text-ink">{{ $brand->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @else
                {{-- No results --}}
                <div class="border-t border-zinc-100 px-4 py-8 text-center">
                    <div class="text-[13.5px] font-medium text-ink-2">No matches for "{{ $query }}"</div>
                    <div class="mt-1 text-[12px] text-ink-4">Try a brand, category or SKU</div>
                </div>
            @endif
        @endif

        {{-- Keyboard hint footer — desktop only --}}
        <div class="hidden items-center justify-end gap-2 border-t border-zinc-100 px-4 py-2 text-[11px] text-ink-4 lg:flex">
            <kbd class="inline-flex h-4.5 items-center rounded border border-zinc-200 bg-zinc-50 px-1">↑</kbd>
            <kbd class="inline-flex h-4.5 items-center rounded border border-zinc-200 bg-zinc-50 px-1">↓</kbd>
            <span class="mr-2">navigate</span>
            <kbd class="inline-flex h-4.5 items-center rounded border border-zinc-200 bg-zinc-50 px-1">esc</kbd>
            <span>close</span>
        </div>
    </div>
</div>
