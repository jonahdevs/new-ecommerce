{{-- ══════════════════════════════════════════════════════════════
     Search suggestions partial
     Included by: livewire/search-bar.blade.php (desktop + mobile)

     Structure:
       1. Categories — navigation refiners at the top
       2. Products   — up to 5 results with image + category label
       3. See all    — link to full search results
       4. Empty      — no results state
     ══════════════════════════════════════════════════════════════ --}}

{{-- 1. Categories --}}
@if (!empty($suggestions['categories']))
    <div class="px-4 pt-3 pb-2 flex flex-wrap gap-2">
        @foreach ($suggestions['categories'] as $category)
            <a href="{{ route('shop.category', ['category' => $category['slug']]) }}" wire:navigate
                @if ($mobileOpen) wire:click="closeMobile" @endif
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium
                    bg-zinc-100 text-zinc-600 hover:bg-sheffield-blue hover:text-white
                    border border-zinc-200 hover:border-sheffield-blue transition-colors duration-150">
                <flux:icon.squares-2x2 class="w-3 h-3" />
                {{ $category['name'] }}
                <span class="opacity-60">({{ $category['products_count'] }})</span>
            </a>
        @endforeach
    </div>
    <div class="mx-4 border-t border-zinc-100"></div>
@endif

{{-- 2. Products --}}
@if (!empty($suggestions['products']))
    <div class="py-1">
        @foreach ($suggestions['products'] as $product)
            <a href="{{ route('products.show', $product['slug']) }}" wire:navigate
                @if ($mobileOpen) wire:click="closeMobile" @endif
                class="flex items-center gap-3 px-4 py-2.5 hover:bg-zinc-50 transition-colors group">

                {{-- Thumbnail --}}
                <div class="shrink-0 w-10 h-10 rounded-md border border-zinc-100 bg-zinc-50 overflow-hidden">
                    @if ($product['image'])
                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}"
                            class="w-full h-full object-contain" loading="lazy">
                    @else
                        <flux:icon.photo class="w-full h-full p-2 text-zinc-300 stroke-1" />
                    @endif
                </div>

                {{-- Name + category --}}
                <div class="flex-1 min-w-0">
                    <p
                        class="text-sm font-medium text-zinc-800 truncate group-hover:text-sheffield-blue transition-colors">
                        {{ $product['name'] }}
                    </p>
                    @if ($product['category'])
                        <p class="text-xs text-zinc-400 mt-0.5 truncate">
                            {{ $product['category'] }}
                        </p>
                    @endif
                </div>

                <flux:icon.chevron-right
                    class="size-4 text-zinc-300 group-hover:text-sheffield-blue group-hover:translate-x-0.5 transition-all duration-150 shrink-0" />
            </a>
        @endforeach
    </div>
@endif

{{-- 3. See all results --}}
@if (!empty($suggestions['products']))
    <div class="border-t border-zinc-100 px-4 py-2.5">
        <a href="{{ route('shop.index') }}?search={{ urlencode($search) }}" wire:navigate
            @if ($mobileOpen) wire:click="closeMobile" @endif
            class="flex items-center justify-between text-xs font-medium text-sheffield-blue hover:underline">
            <span>See all results for "{{ $search }}"</span>
            <flux:icon.arrow-right class="w-3.5 h-3.5" />
        </a>
    </div>
@endif

{{-- 4. Empty state --}}
@if ($showSuggestions && empty($suggestions['products']) && empty($suggestions['categories']))
    <div class="px-4 py-10 text-center">
        <flux:icon.magnifying-glass class="w-8 h-8 text-zinc-300 mx-auto mb-2" />
        <p class="text-sm font-medium text-zinc-500">No results for "{{ $search }}"</p>
        <p class="text-xs text-zinc-400 mt-1">Try a different keyword or browse all products</p>
        <a href="{{ route('shop.index') }}" wire:navigate
            class="inline-flex items-center gap-1.5 mt-4 text-xs font-medium text-sheffield-blue hover:underline">
            Browse all products
            <flux:icon.arrow-right class="w-3.5 h-3.5" />
        </a>
    </div>
@endif
