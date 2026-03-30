<?php

use Livewire\Component;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Product;
use App\Models\Category;

new class extends Component {
    public string $search = '';
    public array $suggestions = [];
    public bool $showSuggestions = false;
    public bool $mobileOpen = false;

    public function updatedSearch(): void
    {
        if (strlen($this->search) >= 2) {
            $this->loadSuggestions();
            $this->showSuggestions = true;
        } else {
            $this->suggestions = [];
            $this->showSuggestions = false;
        }
    }

    public function loadSuggestions(): void
    {
        $term = $this->search;

        // Products — name, slug, image and category only — no price
        $products = Product::active()
            ->visibleInSearch()
            ->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%");
            })
            ->with(['categories:id,name,slug'])
            ->limit(5)
            ->get(['id', 'name', 'slug', 'image_path']);

        // Categories — only those with at least one active product
        $categories = Category::query()
            ->active()
            ->where('name', 'like', "%{$term}%")
            ->withCount('activeProducts')
            ->having('active_products_count', '>=', 1)
            ->limit(3)
            ->get(['id', 'name', 'slug']);

        $this->suggestions = [
            'products' => $products
                ->map(
                    fn($p) => [
                        'name' => $p->name,
                        'slug' => $p->slug,
                        'image' => $p->image_url,
                        'category' => $p->categories->first()?->name,
                        'category_slug' => $p->categories->first()?->slug,
                    ],
                )
                ->toArray(),

            'categories' => $categories
                ->map(
                    fn($c) => [
                        'name' => $c->name,
                        'slug' => $c->slug,
                        'products_count' => $c->active_products_count,
                    ],
                )
                ->toArray(),
        ];
    }

    public function openMobile(): void
    {
        $this->mobileOpen = true;
    }

    public function closeMobile(): void
    {
        $this->mobileOpen = false;
        $this->search = '';
        $this->suggestions = [];
        $this->showSuggestions = false;
    }
};
?>

<div class="w-full">

    {{-- ── DESKTOP ── --}}
    <div class="hidden lg:block w-full max-w-xl relative">
        <flux:input wire:model.live.debounce.300ms="search" wire:loading.attr.remove="disabled" icon="magnifying-glass"
            placeholder="Search products..." class="w-full" autocomplete="off" clearable
            @focus="$wire.showSuggestions = ($wire.suggestions?.products?.length > 0)"
            @keydown.escape="$wire.showSuggestions = false"
            @keydown.enter="window.location.href = '{{ route('shop.index') }}?search=' + encodeURIComponent($wire.search)" />

        <div wire:show="showSuggestions" @click.outside="$wire.showSuggestions = false"
            class="absolute z-50 w-full bg-white rounded-lg shadow-lg border border-zinc-200 top-full mt-1 max-h-[30rem] overflow-y-auto">
            @include('partials.search-suggestions')
        </div>
    </div>

    {{-- ── MOBILE TRIGGER ── --}}
    <button wire:click="openMobile" type="button"
        class="lg:hidden flex items-center justify-center w-9 h-9 rounded-md text-zinc-700 hover:bg-zinc-100 transition-colors"
        aria-label="Open search">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </button>

    {{-- ── MOBILE OVERLAY ── --}}
    {{-- x-teleport escapes the sticky header stacking context --}}
    <template x-teleport="body">
        <div x-show="$wire.mobileOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2" class="fixed inset-0 z-[200] bg-white flex flex-col"
            @keydown.escape.window="$wire.closeMobile()">

            {{-- Header --}}
            <div class="flex items-center gap-3 px-4 py-3 border-b border-zinc-200 shrink-0">
                <button wire:click="closeMobile" type="button"
                    class="shrink-0 text-zinc-600 hover:text-zinc-900 transition-colors" aria-label="Close search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                    placeholder="Search products..." class="w-full" autocomplete="off" clearable x-init="$nextTick(() => $el.querySelector('input')?.focus())"
                    @keydown.escape="$wire.closeMobile()"
                    @keydown.enter="
                        window.location.href = '{{ route('shop.index') }}?search=' + encodeURIComponent($wire.search);
                        $wire.closeMobile();
                    " />
            </div>

            {{-- Results --}}
            <div class="flex-1 overflow-y-auto">
                @if ($showSuggestions)
                    @include('partials.search-suggestions')
                @else
                    <div class="flex flex-col items-center justify-center h-full gap-3 text-zinc-400 px-8 text-center">
                        <svg class="w-12 h-12 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <p class="text-sm">Type at least 2 characters to search.</p>
                    </div>
                @endif
            </div>

        </div>
    </template>

</div>

@push('scripts')
    <script>
        // Reset any stuck Livewire loading states on error pages
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[wire\\:loading]').forEach(el => {
                el.style.display = 'none';
            });

            // Also stop any Alpine loading states
            document.querySelectorAll('[wire\\:loading\\.class]').forEach(el => {
                el.classList.remove('opacity-50', 'pointer-events-none', 'cursor-wait');
            });
        });
    </script>
@endpush
