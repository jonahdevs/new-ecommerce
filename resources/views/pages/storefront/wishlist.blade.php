<?php

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

new #[Layout('layouts::storefront')] #[Title('Wishlist')] class extends Component {
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
        Flux::toast(heading: 'Added to cart', text: "{$count} " . str('item')->plural($count) . ' added to your cart.', variant: 'success');
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
            ->with(['brand:id,name', 'taxClass:id,rate,is_inclusive', 'media'])
            ->visibleInCatalog()
            ->published()
            ->honorStockVisibility()
            ->when($wishlistSlugs, fn($q) => $q->whereNotIn('slug', $wishlistSlugs))
            ->inRandomOrder()
            ->take(6)
            ->get();
    }
}; ?>

@php
    $totalCents = $this->products->sum(fn($p) => $p->sale_price ?? ($p->price ?? 0));
@endphp

<div class="page-fade">
    {{-- Breadcrumb --}}
    <div class="bg-surface-sunken">
        <div class="shell py-3">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Wishlist</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="shell pt-3 pb-20">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Wishlist</h1>
            </div>

            @if ($this->products->isNotEmpty())
                <div class="flex gap-2.5">
                    <flux:button wire:click="clear" wire:confirm="Clear your entire wishlist?">Clear wishlist
                    </flux:button>
                    <flux:button variant="primary" wire:click="addAllToCart" icon="shopping-cart">Add all to cart
                    </flux:button>
                </div>
            @endif
        </div>

        @if ($this->products->isEmpty())
            <div class="mt-10 flex flex-col items-center justify-center px-6 py-16 text-center">
                <img src="{{ asset('images/empty-states/wishlist.svg') }}" alt="Your wishlist is empty"
                    class="mx-auto h-72 w-72" />
                <h2 class="mt-6 text-xl font-semibold sm:text-2xl">Your wishlist is empty.</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-ink-3">
                    Save your favourite products here to keep track of items you love.
                </p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <flux:button variant="customer-primary" size="customer" :href="route('catalog')" wire:navigate>
                        <flux:icon.magnifying-glass variant="micro" class="size-3.5" />
                        Browse products
                    </flux:button>
                    <flux:button variant="customer-outline" size="customer" :href="route('home')" wire:navigate>Back to
                        Home</flux:button>
                </div>
            </div>
        @else
            <div class="mt-8 @container">
                <div
                    class="grid grid-cols-1 gap-3.5 @xs:grid-cols-2 @md:grid-cols-3 @2xl:grid-cols-4 4xl:grid-cols-5 @6xl:grid-cols-6">
                    @foreach ($this->products as $product)
                        <x-storefront.product-card :product="$product" wire:key="wish-{{ $product->slug }}" />
                    @endforeach
                </div>
            </div>

            {{-- Convert-to-quote band --}}
            <div class="mt-8 grid grid-cols-1 items-center gap-6 rounded-md p-6 text-[#f3eadd] sm:grid-cols-[1fr_auto]"
                style="background:#0c1421">
                <div>
                    <div class="font-serif text-xl">Need a formal quote for this list?</div>
                    <div class="mt-1 text-[13px] text-[#c9bea4]">
                        Convert your wishlist to a costed quotation with delivery, installation and lead times. Response
                        in 24 business hours.
                    </div>
                </div>
                <flux:button variant="primary" icon-trailing="arrow-right"
                    :href="route('quote.request', ['products' => $this->products->pluck('slug')->implode(',')])"
                    wire:navigate>
                    Convert to quote
                </flux:button>
            </div>
        @endif

        {{-- Recommendations --}}
        @if ($this->recommendations->isNotEmpty())
            <section class="pt-20">
                <div class="mb-4 flex items-baseline justify-between">
                    <h2 class="text-[22px] font-semibold tracking-tight">You might also want</h2>
                    <a href="{{ route('catalog') }}" wire:navigate
                        class="text-[13px] font-medium text-brand-500 underline transition-colors hover:text-brand-600">
                        View all
                    </a>
                </div>

                <div class="relative" x-data="{
                    swiper: null,
                    init() {
                        this.swiper = new Swiper($refs.carousel, {
                            spaceBetween: 12,
                            speed: 400,
                            preventClicks: false,
                            breakpoints: {
                                0: { slidesPerView: 1.2 },
                                480: { slidesPerView: 2.2 },
                                768: { slidesPerView: 3.2 },
                                1024: { slidesPerView: 4.2 },
                                1280: { slidesPerView: 5.2 },
                                1536: { slidesPerView: 6.5 },
                            },
                        });
                    }
                }">
                    <div class="swiper overflow-hidden" x-ref="carousel">
                        <div class="swiper-wrapper pb-1">
                            @foreach ($this->recommendations as $product)
                                <div class="swiper-slide h-auto!">
                                    <div class="h-full flex flex-col">
                                        <x-storefront.product-card :product="$product" wire:key="reco-{{ $product->id }}"
                                            class="h-full" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <button type="button" @click="swiper?.slidePrev()"
                        class="absolute top-1/2 -left-3 z-10 -translate-y-1/2 flex size-8 cursor-pointer items-center justify-center rounded-full border border-zinc-200 bg-white text-ink shadow-sm transition hover:bg-zinc-50">
                        <flux:icon.chevron-left class="size-4" />
                    </button>
                    <button type="button" @click="swiper?.slideNext()"
                        class="absolute top-1/2 -right-3 z-10 -translate-y-1/2 flex size-8 cursor-pointer items-center justify-center rounded-full border border-zinc-200 bg-white text-ink shadow-sm transition hover:bg-zinc-50">
                        <flux:icon.chevron-right class="size-4" />
                    </button>
                </div>
            </section>
        @endif
    </div>

    @include('partials.storefront.accessory-modal')
</div>
