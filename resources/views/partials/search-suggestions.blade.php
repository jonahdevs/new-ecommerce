{{-- ==========================================================================
     Search Suggestions Partial
     Included by: livewire/search-bar.blade.php (desktop + mobile)

     Structure:
       1. Products        — primary results with image, price, category label
       2. Search within   — category refiners at the bottom (not top)
     ========================================================================== --}}

{{-- 1. Products --}}
@if (!empty($suggestions['products']))
    <div class="py-2">
        <h6 class="px-4 py-2 text-xs font-semibold text-zinc-400 uppercase tracking-wider">
            Products
        </h6>

        @foreach ($suggestions['products'] as $product)
            <a href="{{ route('products.show', $product['slug']) }}" wire:navigate
                @if ($mobileOpen) wire:click="closeMobile" @endif
                class="flex items-center gap-3 px-4 py-2.5 hover:bg-zinc-50 transition-colors group">

                {{-- Thumbnail --}}
                <div class="shrink-0 w-11 h-11 rounded border border-zinc-100 bg-zinc-50 overflow-hidden">
                    @if ($product['image'])
                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="w-full h-full object-cover">
                    @else
                        <flux:icon.photo class="w-full h-full p-2 text-zinc-300 stroke-1" />
                    @endif
                </div>

                {{-- Details --}}
                <div class="flex-1 min-w-0">
                    <p
                        class="text-sm font-medium text-zinc-900 truncate group-hover:text-brand-primary transition-colors">
                        {{ $product['name'] }}
                    </p>
                    <div class="flex items-center gap-2 mt-0.5">
                        {{-- Price --}}
                        <span
                            class="text-xs font-semibold @if ($product['has_discount']) text-brand-primary @else text-zinc-600 @endif">
                            {{ $product['price'] }}
                        </span>
                        {{-- Category as subtle label — not a separate section --}}
                        @if ($product['category'])
                            <span class="text-xs text-zinc-400">
                                › in {{ $product['category'] }}
                            </span>
                        @endif
                    </div>
                </div>

                <flux:icon.chevron-right
                    class="size-4 text-zinc-300 group-hover:text-brand-primary group-hover:translate-x-1 transition-all duration-200 shrink-0" />
            </a>
        @endforeach
    </div>
@endif

{{-- 2. Search within (category refiners) — bottom, not top --}}
@if (!empty($suggestions['categories']))
    <div class="border-t border-zinc-100 py-2">
        <h6 class="px-4 py-2 text-xs font-semibold text-zinc-400 uppercase tracking-wider">
            Search within
        </h6>

        <div class="px-3 pb-1 flex flex-wrap gap-2">
            @foreach ($suggestions['categories'] as $category)
                <a href="{{ route('shop.index', ['category' => $category['slug']]) }}?search={{ urlencode($search) }}"
                    wire:navigate @if ($mobileOpen) wire:click="closeMobile" @endif
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                        bg-brand-secondary/10 text-brand-secondary
                        hover:bg-brand-secondary hover:text-brand-secondary-content
                        transition-colors duration-150 border border-brand-secondary/20">
                    <flux:icon.layout-panel-top class="w-3.5 h-3.5" />
                    {{ $category['name'] }}
                    <span class="opacity-60">({{ $category['products_count'] }})</span>
                </a>
            @endforeach
        </div>
    </div>
@endif

{{-- Empty state — search typed but nothing found --}}
@if ($showSuggestions && empty($suggestions['products']) && empty($suggestions['categories']))
    <div class="px-4 py-8 text-center">
        <flux:icon.magnifying-glass class="w-8 h-8 text-zinc-300 mx-auto mb-2" />
        <p class="text-sm font-medium text-zinc-500">No results for "{{ $search }}"</p>
        <p class="text-xs text-zinc-400 mt-1">Try a different keyword or browse categories</p>
        <a href="{{ route('shop.index') }}" wire:navigate
            class="inline-flex items-center gap-1.5 mt-4 text-xs font-medium text-brand-primary hover:underline">
            Browse all products
            <flux:icon.arrow-right class="w-3.5 h-3.5" />
        </a>
    </div>
@endif
