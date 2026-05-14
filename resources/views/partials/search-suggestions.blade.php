{{-- ══════════════════════════════════════════════════════════════
     Search suggestions partial
     Included by: livewire/search-bar.blade.php (desktop + mobile)

     Structure:
       1. Products   — up to 5 results with image + category label
       2. Categories — navigation refiners
       3. See all    — link to full search results
       4. Empty      — no results state
     ══════════════════════════════════════════════════════════════ --}}

{{-- 1. Products --}}
@if (!empty($suggestions['products']))
    <div class="py-1">
        <div class="px-3 py-1.5">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Products</span>
        </div>
        @foreach ($suggestions['products'] as $product)
            <a href="{{ route('products.show', $product['slug']) }}" wire:navigate
                @if ($mobileOpen) wire:click="closeMobile" @endif
                class="flex items-center gap-3 px-3 py-2 hover:bg-zinc-50 transition-colors group">

                {{-- Thumbnail --}}
                <div class="shrink-0 w-10 h-10 rounded border border-zinc-200 bg-white overflow-hidden flex items-center justify-center">
                    @if ($product['image'])
                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}"
                            class="w-full h-full object-contain" loading="lazy">
                    @else
                        <flux:icon.photo class="w-5 h-5 text-zinc-300" />
                    @endif
                </div>

                {{-- Name + category --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-zinc-900 truncate group-hover:text-primary transition-colors">
                        {{ $product['name'] }}
                    </p>
                    @if ($product['category'])
                        <p class="text-xs text-zinc-400 truncate">
                            in {{ $product['category'] }}
                        </p>
                    @endif
                </div>

                <flux:icon.chevron-right
                    class="size-4 text-zinc-300 group-hover:text-primary transition-colors shrink-0" />
            </a>
        @endforeach
    </div>
@endif

{{-- 2. Categories --}}
@if (!empty($suggestions['categories']))
    <div class="border-t border-zinc-100">
        <div class="px-3 py-1.5">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Categories</span>
        </div>
        <div class="px-3 pb-2 flex flex-wrap gap-1.5">
            @foreach ($suggestions['categories'] as $category)
                <a href="{{ route('shop.category', ['category' => $category['slug']]) }}" wire:navigate
                    @if ($mobileOpen) wire:click="closeMobile" @endif
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs
                        bg-zinc-100 text-zinc-600 hover:bg-primary hover:text-white
                        transition-colors duration-150">
                    {{ $category['name'] }}
                    <span class="text-zinc-400 group-hover:text-white/80">({{ $category['products_count'] }})</span>
                </a>
            @endforeach
        </div>
    </div>
@endif

{{-- 3. See all results --}}
@if (!empty($suggestions['products']))
    <div class="border-t border-zinc-200 bg-zinc-50">
        <a href="{{ route('shop.index') }}?search={{ urlencode($search) }}" wire:navigate
            @if ($mobileOpen) wire:click="closeMobile" @endif
            class="flex items-center justify-between px-3 py-2.5 text-sm text-primary hover:text-primary-hover hover:bg-zinc-100 transition-colors">
            <span>See all results for "<span class="font-medium text-zinc-700">{{ $search }}</span>"</span>
            <flux:icon.arrow-right class="w-4 h-4" />
        </a>
    </div>
@endif

{{-- 4. Empty state --}}
@if ($showSuggestions && empty($suggestions['products']) && empty($suggestions['categories']))
    <div class="px-4 py-8 text-center">
        <div class="w-12 h-12 rounded-full bg-zinc-100 flex items-center justify-center mx-auto mb-3">
            <flux:icon.magnifying-glass class="w-6 h-6 text-zinc-400" />
        </div>
        <p class="text-sm font-medium text-zinc-700">No results for "{{ $search }}"</p>
        <p class="text-xs text-zinc-500 mt-1">Try a different keyword or browse our categories</p>
        <a href="{{ route('shop.index') }}" wire:navigate
            class="inline-flex items-center gap-1.5 mt-4 px-3 py-1.5 text-xs font-medium text-white bg-primary hover:bg-primary-hover rounded-md transition-colors">
            Browse all products
            <flux:icon.arrow-right class="w-3.5 h-3.5" />
        </a>
    </div>
@endif
