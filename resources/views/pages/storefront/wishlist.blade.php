<?php

use App\Enums\StockStatus;
use App\Livewire\Concerns\InteractsWithStorefront;
use App\Models\Product;
use App\Support\StorefrontSession;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Wishlist')] class extends Component
{
    use InteractsWithStorefront;

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,follow');
    }

    public function remove(string $slug): void
    {
        StorefrontSession::removeFromWishlist($slug);
        unset($this->products);
        $this->dispatch('wishlist-updated');
        Flux::toast(heading: 'Removed from wishlist', text: 'Item has been removed from your wishlist.', variant: 'warning');
    }

    public function clear(): void
    {
        StorefrontSession::clearWishlist();
        unset($this->products);
        $this->dispatch('wishlist-updated');
        Flux::toast(heading: 'Wishlist cleared', text: 'All saved items have been removed from your wishlist.', variant: 'danger');
    }

    public function addAllToCart(): void
    {
        $count = $this->products->count();
        foreach ($this->products as $product) {
            StorefrontSession::addToCart($product->slug);
        }
        $this->dispatch('cart-updated');
        Flux::toast(
            heading: 'Added to cart',
            text: "{$count} " . str('item')->plural($count) . ' added to your cart.',
            variant: 'success',
        );
    }

    #[Computed]
    public function products(): Collection
    {
        return StorefrontSession::wishlistProducts();
    }

    #[Computed]
    public function recommendations(): Collection
    {
        $wishlistSlugs = StorefrontSession::wishlist();

        return Product::query()
            ->with(['brand', 'taxClass', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->where('visibility', 'visible')
            ->where('stock_status', StockStatus::IN_STOCK->value)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->when($wishlistSlugs, fn ($q) => $q->whereNotIn('slug', $wishlistSlugs))
            ->inRandomOrder()
            ->take(6)
            ->get();
    }
}; ?>

@php
    $totalCents = $this->products->sum(fn ($p) => $p->sale_price ?? $p->price ?? 0);
@endphp

<div class="page-fade">
    <div class="shell pt-4 pb-20">
        {{-- Breadcrumb --}}
        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Wishlist</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Wishlist</h1>
                <p class="mt-2 text-[14.5px] text-ink-3">
                    @if ($this->products->isEmpty())
                        Nothing saved yet — tap the heart on any product.
                    @else
                        {{ $this->products->count() }} {{ \Illuminate\Support\Str::plural('item', $this->products->count()) }} ·
                        Estimated total {!! money($totalCents) !!}
                    @endif
                </p>
            </div>

            @if ($this->products->isNotEmpty())
                <div class="flex gap-2.5">
                    <flux:button wire:click="clear" wire:confirm="Clear your entire wishlist?">Clear wishlist</flux:button>
                    <flux:button variant="primary" wire:click="addAllToCart" icon="shopping-cart">Add all to cart</flux:button>
                </div>
            @endif
        </div>

        @if ($this->products->isEmpty())
            {{-- Empty state --}}
            <div class="mt-10 rounded-md bg-surface-sunken p-16 text-center">
                <flux:icon.heart variant="outline" class="mx-auto size-12 text-ink-4" />
                <h2 class="mt-5 font-serif text-2xl">No saved items yet.</h2>
                <p class="mx-auto mt-2 max-w-md text-ink-3">
                    Tap the heart on any product to save it here. Wishlists keep across devices once you're signed in,
                    and can be converted into a formal quote with one click.
                </p>
                <div class="mt-6 flex justify-center gap-2.5">
                    <flux:button variant="primary" :href="route('catalog')" wire:navigate>Browse the catalog</flux:button>
                    @guest
                        <flux:button :href="route('login')" wire:navigate>Sign in to sync</flux:button>
                    @endguest
                </div>
            </div>
        @else
            <div class="mt-8 flex flex-col gap-3">
                @foreach ($this->products as $product)
                    @php
                        $price = $product->sale_price ?? $product->price ?? 0;
                        $compareAt = $product->sale_price ? $product->price : null;
                        $inStock = $product->stock_status === StockStatus::IN_STOCK;
                    @endphp
                    <article wire:key="wish-{{ $product->slug }}"
                        class="grid grid-cols-[120px_1fr_auto_auto] items-center gap-5 rounded-md border border-zinc-200 bg-white p-4">
                        <a href="#" wire:navigate
                            class="block size-30 overflow-hidden rounded bg-surface-sunken p-2"
                            style="width: 120px; height: 120px">
                            @if ($product->cover_url)
                                <img src="{{ $product->cover_url }}" alt="" class="size-full object-contain" loading="lazy" />
                            @endif
                        </a>
                        <div>
                            @if ($product->brand)
                                <div class="text-[11.5px] font-bold tracking-[0.06em] text-brand-blue-600 uppercase">{{ $product->brand->name }}</div>
                            @endif
                            <a href="#" wire:navigate class="mt-1 block text-base leading-snug font-medium hover:text-brand-500">{{ $product->name }}</a>
                            @if ($product->short_description)
                                <div class="mt-1 line-clamp-2 max-w-xl text-[13px] text-ink-3">{{ $product->short_description }}</div>
                            @endif
                            <div class="mt-2 flex items-center gap-2 text-[12px] text-ink-2">
                                <span>SKU: {{ $product->sku }}</span>
                                <span class="text-ink-4">·</span>
                                <span class="{{ $inStock ? 'text-emerald-700' : 'text-ink-3' }}">
                                    {{ $inStock ? '● In stock' : '● Made to order' }}
                                </span>
                            </div>
                        </div>
                        <div class="min-w-32 text-right">
                            @if ($compareAt)
                                <div class="text-[12px] text-ink-4 line-through whitespace-nowrap">{!! money($compareAt) !!}</div>
                            @endif
                            <div class="font-serif text-xl tabular-nums whitespace-nowrap">{!! $price ? money($price) : 'Request quote' !!}</div>
                        </div>
                        <div class="flex min-w-36 flex-col gap-1.5">
                            <flux:button variant="primary" size="sm" wire:click="addToCart('{{ $product->slug }}')" icon="shopping-cart">Add to cart</flux:button>
                            <flux:button size="sm">Compare</flux:button>
                            <button type="button" wire:click="remove('{{ $product->slug }}')"
                                class="cursor-pointer text-[12px] text-ink-3 underline underline-offset-2 hover:text-brand-500">
                                Remove
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Convert-to-quote band --}}
            <div class="mt-8 grid grid-cols-1 items-center gap-6 rounded-md p-6 text-[#f3eadd] sm:grid-cols-[1fr_auto]" style="background:#0c1421">
                <div>
                    <div class="font-serif text-xl">Need a formal quote for this list?</div>
                    <div class="mt-1 text-[13px] text-[#c9bea4]">
                        Convert your wishlist to a costed quotation with delivery, installation and lead times. Response in 24 business hours.
                    </div>
                </div>
                <flux:button variant="primary" icon-trailing="arrow-right">Convert to quote</flux:button>
            </div>
        @endif

        {{-- Recommendations --}}
        @if ($this->recommendations->isNotEmpty())
            <section class="pt-20">
                <div class="mb-4 flex items-baseline justify-between border-b border-zinc-200 pb-3">
                    <h2 class="text-[22px] font-semibold tracking-tight">You might also want</h2>
                    <a href="{{ route('catalog') }}" wire:navigate class="inline-flex items-center gap-1 text-[13px] text-zinc-600 hover:text-zinc-900">Browse all <flux:icon.arrow-right variant="micro" class="size-3.5" /></a>
                </div>
                <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4 2xl:grid-cols-6">
                    @foreach ($this->recommendations as $product)
                        <x-storefront.product-card :product="$product" wire:key="reco-{{ $product->id }}" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
