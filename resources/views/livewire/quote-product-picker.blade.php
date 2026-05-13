<?php

use App\Models\Product;
use App\Services\QuoteBasketService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

new class extends Component {
    public string $search = '';
    public int $perPage = 18;
    public int $page = 1;
    public bool $hasMore = true;
    public array $loadedProducts = [];
    public array $addedProductIds = [];

    public function mount(): void
    {
        $this->syncBasketState();
        $this->loadProducts();
    }

    public function syncBasketState(): void
    {
        $this->addedProductIds = app(QuoteBasketService::class)->items()->pluck('product_id')->toArray();
    }

    public function updatedSearch(): void
    {
        $this->reset(['page', 'loadedProducts', 'hasMore']);
        $this->loadProducts();
    }

    public function loadMore(): void
    {
        if (!$this->hasMore) {
            return;
        }
        $this->page++;
        $this->loadProducts();
    }

    public function loadProducts(): void
    {
        $query = Product::query()
            ->select(['id', 'name', 'slug', 'image_path', 'price', 'sale_price', 'type', 'requires_quotation', 'status', 'visibility'])
            ->with([
                'variants' => fn($q) => $q
                    ->where('is_active', true)
                    ->whereNotNull('price')
                    ->select(['id', 'product_id', 'price', 'sale_price']),
            ])
            ->active()
            ->visibleInSearch()
            ->when($this->search, fn(Builder $q) => $q->where(fn(Builder $q2) => $q2->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->limit($this->perPage + 1)
            ->offset(($this->page - 1) * $this->perPage)
            ->get();

        $this->hasMore = $query->count() > $this->perPage;

        $newItems = $query
            ->take($this->perPage)
            ->map(
                fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'image_url' => $p->image_url,
                    'requires_quotation' => $p->requires_quotation,
                    'display_price' => $p->display_price,
                    'has_price_prefix' => $p->has_price_prefix,
                    'display_price_prefix' => $p->display_price_prefix,
                ],
            )
            ->toArray();

        $this->loadedProducts = [...$this->loadedProducts, ...$newItems];
    }

    public function addToQuote(int $productId): void
    {
        app(QuoteBasketService::class)->add($productId, 1);
        $this->addedProductIds[] = $productId;
        $this->dispatch('quote-basket-updated');
        $this->dispatch('quote-item-added');
    }

    public function removeFromQuote(int $productId): void
    {
        app(QuoteBasketService::class)->remove($productId);
        $this->addedProductIds = array_values(array_filter($this->addedProductIds, fn($id) => $id !== $productId));
        $this->dispatch('quote-basket-updated');
        $this->dispatch('quote-item-removed');
    }
};
?>

<div class="flex flex-col h-full">

    {{-- Search --}}
    <div class="px-4 pt-4 pb-3 border-b border-zinc-200 dark:border-zinc-700 shrink-0">
        <div class="relative">
            <flux:icon.magnifying-glass
                class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search products by name or SKU..."
                class="customer-input pl-9 bg-white w-full" autocomplete="off" />
        </div>
    </div>

    {{-- Product grid with container queries --}}
    <div class="flex-1 overflow-y-auto p-4 @container" x-data="{ loading: false }"
        x-on:scroll.passive="
            if (!loading && ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 100)) {
                loading = true;
                $wire.loadMore().then(() => loading = false);
            }
        ">

        @if (count($loadedProducts) > 0)
            <div class="grid grid-cols-2 @sm:grid-cols-3 @lg:grid-cols-4 @xl:grid-cols-5 gap-3">
                @foreach ($loadedProducts as $product)
                    @php $isAdded = in_array($product['id'], $addedProductIds); @endphp

                    <div wire:key="picker-{{ $product['id'] }}"
                        class="flex flex-col overflow-hidden group bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 hover:shadow-[0px_0px_6px_2px_rgba(0,0,0,0.08)] transition-all duration-200">

                        {{-- Image section --}}
                        <div class="relative aspect-square bg-zinc-50 dark:bg-zinc-800 overflow-hidden">
                            @if ($product['image_url'])
                                <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}"
                                    class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy" />
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <flux:icon.photo class="size-10 text-zinc-300 stroke-1" />
                                </div>
                            @endif

                            {{-- +/- button — absolute bottom-right of image --}}
                            <div class="absolute bottom-2 right-2">
                                @if ($isAdded)
                                    <button type="button" wire:click="removeFromQuote({{ $product['id'] }})"
                                        class="w-7 h-7 flex items-center justify-center rounded-full bg-primary text-on-primary shadow-md hover:bg-primary/80 transition-colors cursor-pointer"
                                        title="Remove from quote">
                                        <flux:icon.minus class="size-3.5" />
                                    </button>
                                @else
                                    <button type="button" wire:click="addToQuote({{ $product['id'] }})"
                                        class="w-7 h-7 flex items-center justify-center rounded-full bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 shadow-md border border-zinc-200 dark:border-zinc-600 hover:bg-primary hover:text-on-primary hover:border-primary transition-colors cursor-pointer"
                                        title="Add to quote">
                                        <flux:icon.plus class="size-3.5" />
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Name + price --}}
                        <div class="p-2.5 space-y-0.5">
                            <p class="text-xs font-medium text-zinc-800 dark:text-zinc-100 line-clamp-2 leading-snug">
                                {{ $product['name'] }}
                            </p>
                            <div>
                                @if ($product['requires_quotation'])
                                    <span class="text-xs text-amber-600 font-medium">Quote only</span>
                                @elseif ($product['display_price'])
                                    <div class="flex items-baseline gap-1 flex-wrap">
                                        @if ($product['has_price_prefix'])
                                            <span
                                                class="text-[10px] text-zinc-400">{{ $product['display_price_prefix'] }}</span>
                                        @endif
                                        <span
                                            class="text-xs font-bold text-primary">{{ $product['display_price'] }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400">Price on request</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <flux:icon.magnifying-glass class="size-10 text-zinc-300 mb-3" />
                <p class="text-sm text-zinc-500">
                    @if ($search)
                        No products found for "{{ $search }}"
                    @else
                        No products available
                    @endif
                </p>
            </div>
        @endif

        {{-- Load more / end indicator --}}
        @if ($hasMore)
            <div class="flex items-center justify-center py-6">
                <div class="w-5 h-5 border-2 border-zinc-200 border-t-zinc-500 rounded-full animate-spin"></div>
            </div>
        @elseif (count($loadedProducts) > 0)
            <p class="text-center text-xs text-zinc-400 py-4">All products loaded</p>
        @endif
    </div>

</div>
