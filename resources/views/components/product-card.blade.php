<?php

use App\Enums\ProductType;
use App\Services\WishlistService;
use App\Services\CompareService;
use App\Services\CartService;
use App\Models\Product;
use Livewire\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    public Product $product;

    public bool $wishlisted = false;
    public bool $inCompare = false;
    public bool $inCart = false;
    public int $cartQuantity = 1;
    public ?int $cartItemId = null;
    public bool $quickViewLoaded = false;

    public function mount(WishlistService $wishlist, CompareService $compareService, CartService $cartService): void
    {
        $this->wishlisted = $wishlist->has($this->product->id);
        $this->inCompare = $compareService->has($this->product->id);
        $this->inCart = $cartService->has($this->product->id);

        if ($this->inCart) {
            $cartItem = $cartService->getCartItem($this->product->id);
            if ($cartItem) {
                $this->cartItemId = $cartItem->id;
                $this->cartQuantity = $cartItem->quantity;
            }
        }
    }

    public function loadQuickView(): void
    {
        $this->quickViewLoaded = true;
    }

    public function goToProduct(): void
    {
        $this->redirect(route('products.show', $this->product), navigate: true);
    }

    public function toggleWishlist(WishlistService $wishlistService): void
    {
        try {
            $added = $wishlistService->toggle($this->product->id);
            $this->wishlisted = $added;
            $this->dispatch('wishlist-updated');
            $this->dispatch('notify', variant: 'success', title: $added ? 'Wishlist Updated' : 'Wishlist Updated', message: $added ? 'Product added to your wishlist' : 'Product removed from your wishlist');
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Action Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update wishlist');
        }
    }

    public function toggleCompare(CompareService $compareService): void
    {
        try {
            $added = $compareService->toggle($this->product->id);
            $this->inCompare = $added;
            $this->dispatch('compare-updated');
            $this->dispatch('notify', title: $added ? 'Comparison Updated' : 'Comparison Updated', variant: 'success', message: $added ? 'Product added to comparison list' : 'Product removed from comparison list');
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Action Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update comparison');
        }
    }

    /**
     * Quick cart action — only works for simple products.
     * Variable, grouped and quotation products redirect to the product page.
     */
    public function quickAddToCart(CartService $cartService): void
    {
        if (in_array($this->product->type, [ProductType::VARIABLE, ProductType::GROUPED]) || $this->product->requires_quotation) {
            $this->goToProduct();
            return;
        }

        $this->addToCart($cartService);
    }

    public function addToCart(CartService $cartService): void
    {
        try {
            $cartService->addItem($this->product->id, 1);

            $this->inCart = true;
            $cartItem = $cartService->getCartItem($this->product->id);
            if ($cartItem) {
                $this->cartItemId = $cartItem->id;
                $this->cartQuantity = $cartItem->quantity;
            }

            $this->dispatch('cart-updated');
            $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: 'Product added to your cart');
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Add to Cart Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add product to cart');
        }
    }

    public function increaseCartQuantity(CartService $cartService): void
    {
        try {
            $newQuantity = $this->cartQuantity + 1;

            if ($this->product->manage_stock && $newQuantity > $this->product->stock_quantity) {
                // $this->dispatch('notify', variant: 'warning', message: 'Maximum stock quantity reached');
                return;
            }

            if ($this->inCart && $this->cartItemId) {
                $cartService->updateItemQuantity($this->cartItemId, $newQuantity);
                $this->dispatch('cart-updated');
            }

            $this->cartQuantity = $newQuantity;
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update cart quantity');
        }
    }

    public function decreaseCartQuantity(CartService $cartService): void
    {
        try {
            $newQuantity = $this->cartQuantity - 1;

            if ($newQuantity < 1) {
                $this->removeFromCart($cartService);
                return;
            }

            if ($this->inCart && $this->cartItemId) {
                $cartService->updateItemQuantity($this->cartItemId, $newQuantity);
                $this->dispatch('cart-updated');
            }

            $this->cartQuantity = $newQuantity;
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update cart quantity');
        }
    }

    public function removeFromCart(CartService $cartService): void
    {
        try {
            if ($this->cartItemId) {
                $cartService->removeItem($this->cartItemId);
                $this->inCart = false;
                $this->cartItemId = null;
                $this->cartQuantity = 1;
                $this->dispatch('cart-updated');
                $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: 'Product removed from your cart');
            }
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Remove Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to remove product from cart');
        }
    }

    #[Computed]
    public function imageSlides(): array
    {
        $slides = [];
        $seenPaths = [];

        // 1. Main product image
        if ($this->product->image_path) {
            $seenPaths[] = $this->product->image_path;
            $slides[] = [
                'url' => $this->product->image_url,
                'alt' => $this->product->name,
            ];
        }

        // 2. Gallery images — skip anything already seen
        foreach ($this->product->images as $image) {
            if (!in_array($image->image_path, $seenPaths, true)) {
                $seenPaths[] = $image->image_path;
                $slides[] = [
                    'url' => Storage::url($image->image_path),
                    'alt' => $image->alt_text ?? $this->product->name,
                ];
            }
        }

        return $slides;
    }
};
?>

<flux:card
    {{ $attributes->class(['p-0 overflow-hidden h-full hover:shadow-[0px_0px_6px_2px_rgba(0,_0,_0,_0.1)] transition-all duration-300 ease-in-out group hover:border-zinc-200']) }}>
    <div class="h-full flex flex-col">

        {{-- ── IMAGE ── --}}
        <div class="relative">
            <a href="{{ route('products.show', $product) }}" wire:navigate wire:click.stop class="block">
                <figure
                    class="w-full aspect-square overflow-hidden mb-2 relative bg-zinc-50 flex items-center justify-center">
                    @if ($product->image_url)
                        <x-webp-image :src="$product->image_url" :webp="$product->webp_image_url" alt="{{ $product->name }}"
                            class="w-full h-full object-contain hover:scale-105 transition-transform duration-300"
                            loading="lazy" />
                    @else
                        <flux:icon.photo class="w-16 h-16 text-zinc-400 stroke-1" />
                    @endif
                </figure>
            </a>

            {{-- Left-side badges — top-left stack: discount, type, tags --}}
            @php
                $tagPriority = [
                    'sale' => 0,
                    'clearance' => 1,
                    'new arrival' => 2,
                    'best seller' => 3,
                    'trending' => 4,
                    'featured' => 5,
                    'limited edition' => 6,
                    'exclusive' => 7,
                    'premium' => 8,
                    'eco friendly' => 9,
                ];
                $tagColors = [
                    'sale' => 'bg-red-500',
                    'clearance' => 'bg-orange-500',
                    'new arrival' => 'bg-emerald-500',
                    'best seller' => 'bg-amber-500',
                    'trending' => 'bg-sky-500',
                    'featured' => 'bg-blue-500',
                    'limited edition' => 'bg-purple-500',
                    'exclusive' => 'bg-violet-500',
                    'premium' => 'bg-slate-600',
                    'eco friendly' => 'bg-green-600',
                ];
                $sortedTags = $product->tags
                    ->sortBy(fn($t) => $tagPriority[strtolower($t->name)] ?? 99)
                    ->take(1)
                    ->values();
            @endphp
            <div class="absolute top-2 left-0 flex flex-col gap-1">
                @if ($product->type === ProductType::SIMPLE && $product->hasDiscount())
                    <span class="rounded-e-full bg-red-400 px-2 py-1 text-xs font-medium text-white tracking-wide">
                        -{{ $product->discountPercentage() }}
                    </span>
                @endif

                @if ($product->type === ProductType::VARIABLE)
                    <span class="rounded-e-full bg-secondary px-2 py-1 text-xs font-medium text-white tracking-wide">
                        Options
                    </span>
                @elseif ($product->type === ProductType::GROUPED)
                    <span class="rounded-e-full bg-zinc-700 px-2 py-1 text-xs font-medium text-white tracking-wide">
                        Kit
                    </span>
                @endif

                @foreach ($sortedTags as $tag)
                    @php $color = $tagColors[strtolower($tag->name)] ?? 'bg-zinc-500'; @endphp
                    <span
                        class="rounded-e-full px-2.5 py-0.5 text-xs font-semibold text-white tracking-wide {{ $color }}">
                        {{ $tag->name }}
                    </span>
                @endforeach
            </div>

            {{-- Quick action buttons --}}
            <div
                class="absolute top-2 right-2 flex flex-col gap-2 translate-x-20 group-hover:translate-x-0 transition-transform duration-300">
                <flux:button wire:click.stop="toggleWishlist" size="sm" class="cursor-pointer"
                    title="{{ $wishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}">
                    <x-slot name="icon">
                        <flux:icon.heart variant="{{ $wishlisted ? 'solid' : 'outline' }}"
                            @class(['size-4', 'text-red-500' => $wishlisted]) />
                    </x-slot>
                </flux:button>

                <flux:modal.trigger name="quick-view-product-{{ $product->id }}">
                    <flux:button wire:click="loadQuickView" icon="eye" size="sm" icon-variant="outline"
                        title="Quick View" class="cursor-pointer" />
                </flux:modal.trigger>

                <flux:button wire:click.stop="toggleCompare" size="sm" icon-variant="outline"
                    icon="{{ $inCompare ? 'x-mark' : 'scale' }}" title="Compare" @class(['cursor-pointer', 'text-secondary!' => $inCompare]) />

                @if ($product->requires_quotation)
                    <flux:button wire:click="goToProduct" icon="document-text" size="sm" icon-variant="outline"
                        title="Request Quote" class="cursor-pointer" />
                @else
                    <flux:button wire:click.stop="quickAddToCart" icon="shopping-cart" size="sm"
                        icon-variant="outline"
                        title="{{ in_array($product->type, [ProductType::VARIABLE, ProductType::GROUPED]) ? 'View Options' : 'Add to Cart' }}"
                        class="cursor-pointer" />
                @endif
            </div>
        </div>

        {{-- ── DETAILS ── --}}
        <div class="p-4 flex flex-col gap-1 h-full">

            {{-- Brand --}}
            @if ($product->brand)
                <p class="text-zinc-400 text-xs uppercase tracking-wide">{{ $product->brand->name }}</p>
            @endif

            {{-- Name --}}
            <a href="{{ route('products.show', $product) }}" wire:click.prevent="goToProduct"
                class="text-sm text-zinc-700 line-clamp-2 font-medium tracking-wide">
                {{ $product->name }}
            </a>

            {{-- Star rating --}}
            <x-star-rating :rating="$product->average_rating ?? ($product->reviews_avg_rating ?? 0)" />

            {{-- Price --}}
            <div class="pt-2 mt-auto">
                @if ($product->requires_quotation)
                    <a href="{{ route('products.show', $product) }}" wire:navigate
                        class="text-sm font-medium text-amber-600 hover:underline">
                        Request a quote
                    </a>
                @elseif ($product->display_price)
                    <div class="flex items-baseline gap-1 flex-wrap">
                        @if ($product->has_price_prefix)
                            <span class="text-xs text-zinc-400">{{ $product->display_price_prefix }}</span>
                        @endif
                        <span class="font-extrabold text-primary tracking-wide">{{ $product->display_price }}</span>
                        @if ($product->type === ProductType::SIMPLE && $product->hasDiscount())
                            <span class="text-xs text-zinc-400 line-through">{{ $product->formatted_price }}</span>
                        @endif
                    </div>
                @else
                    <span class="text-sm text-zinc-400">Price unavailable</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ── QUICK VIEW MODAL ── --}}
    <flux:modal variant="floating" name="quick-view-product-{{ $product->id }}"
        class="w-[90%] md:w-full max-w-2xl rounded-xs!" overlay-class="bg-black backdrop-blur-lg">

        @if ($quickViewLoaded)
            <div class="grid grid-cols-1 md:grid-cols-3 pt-7">

                {{-- Images --}}
                <div class="col-span-1" x-data="{
                    mainSwiper: null,
                    thumbSwiper: null,
                    activeIndex: 0,
                    init() {
                        const thumbEl = this.$refs.thumbSwiper;
                
                        if (thumbEl && {{ count($this->imageSlides) }} > 1) {
                            this.thumbSwiper = new Swiper(thumbEl, {
                                spaceBetween: 10,
                                slidesPerView: 4,
                                freeMode: true,
                                watchSlidesProgress: true,
                            });
                        }
                
                        this.mainSwiper = new Swiper(this.$refs.mainSwiper, {
                            spaceBetween: 10,
                            thumbs: { swiper: this.thumbSwiper ?? null },
                            on: { slideChange: (s) => { this.activeIndex = s.realIndex; } },
                        });
                    },
                }">
                    {{-- Main --}}
                    <div class="swiper border-2 rounded-sm overflow-hidden px-2" x-ref="mainSwiper">
                        <div class="swiper-wrapper">
                            @foreach ($this->imageSlides as $slide)
                                <div class="swiper-slide">
                                    <div class="aspect-square flex items-center justify-center">
                                        <img src="{{ $slide['url'] }}" alt="{{ $slide['alt'] }}"
                                            class="w-full h-full object-contain" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Thumbnails --}}
                    @if (count($this->imageSlides) > 1)
                        <div class="swiper px-8 mt-4" x-ref="thumbSwiper">
                            <div class="swiper-wrapper">
                                @foreach ($this->imageSlides as $index => $slide)
                                    <div class="swiper-slide cursor-pointer">
                                        <div class="aspect-square rounded-sm overflow-hidden border-2 transition-all duration-300"
                                            :class="activeIndex === {{ $index }} ? 'border-secondary' :
                                                'border-zinc-200'">
                                            <img src="{{ $slide['url'] }}" alt="{{ $slide['alt'] }}"
                                                class="w-full h-full object-contain" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Details --}}
                <div class="col-span-1 md:col-span-2 pt-5 md:pt-0 md:pl-6">
                    <a href="{{ route('products.show', $product) }}" wire:navigate
                        class="text-xl font-bold mt-2 mb-1 text-zinc-800 hover:text-secondary hover:underline transition-colors">
                        {{ $product->name }}
                    </a>

                    <x-star-rating :rating="$product->average_rating ?? ($product->reviews_avg_rating ?? 0)" class="mb-2 mt-1" />

                    <div class="my-4 text-zinc-500 text-sm line-clamp-3">{!! $product->short_description !!}</div>

                    {{-- Price --}}
                    @if ($product->requires_quotation)
                        <a href="{{ route('products.show', $product) }}" wire:navigate
                            class="text-sm font-medium text-amber-600 hover:underline">
                            Request a quote →
                        </a>
                    @elseif ($product->display_price)
                        <div class="flex items-baseline gap-1 flex-wrap">
                            @if ($product->has_price_prefix)
                                <span class="text-sm text-zinc-400">{{ $product->display_price_prefix }}</span>
                            @endif
                            <span class="text-lg font-semibold text-secondary">{{ $product->display_price }}</span>
                            @if ($product->type === ProductType::SIMPLE && $product->hasDiscount())
                                <span class="text-sm text-zinc-400 line-through">{{ $product->formatted_price }}</span>
                                <flux:badge color="amber" size="sm">-{{ $product->discountPercentage() }}
                                </flux:badge>
                            @endif
                        </div>
                    @endif

                    {{-- Cart actions --}}
                    @if (!$product->requires_quotation && $product->type === ProductType::SIMPLE)
                        @island
                            <div class="mt-3 flex items-center gap-4 flex-wrap">
                                <flux:button.group>
                                    <flux:button icon="minus" class="cursor-pointer text-zinc-500!"
                                        wire:click="decreaseCartQuantity" title="Decrease" />
                                    <flux:input readonly value="{{ $cartQuantity }}"
                                        class="max-w-9! outline-none! border-none! ring-0! text-center!" />
                                    <flux:button icon="plus" class="cursor-pointer text-zinc-500!"
                                        wire:click="increaseCartQuantity" title="Increase" />
                                    @if ($inCart)
                                        <flux:button icon="trash" class="cursor-pointer text-red-500!"
                                            wire:click="removeFromCart" title="Remove" />
                                    @endif
                                </flux:button.group>

                                @if (!$inCart)
                                    <flux:button wire:click="addToCart" variant="primary"
                                        class="uppercase cursor-pointer">
                                        Add to Cart
                                    </flux:button>
                                @endif
                            </div>
                        @endisland
                    @elseif (in_array($product->type, [ProductType::VARIABLE, ProductType::GROUPED]))
                        <div class="mt-3">
                            <flux:button wire:click="goToProduct" variant="primary" class="uppercase cursor-pointer">
                                View Options
                            </flux:button>
                        </div>
                    @elseif ($product->requires_quotation)
                        <div class="mt-3">
                            <flux:button wire:click="goToProduct" variant="primary" class="uppercase cursor-pointer">
                                Request Quote
                            </flux:button>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="flex items-center justify-center h-64">
                <div class="animate-spin rounded-full h-8 w-8 border-2 border-zinc-200 border-t-zinc-500"></div>
            </div>
        @endif
    </flux:modal>
</flux:card>

<style>
    flux-modal::backdrop,
    [data-flux-modal]::backdrop {
        background-color: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(4px);
    }
</style>
