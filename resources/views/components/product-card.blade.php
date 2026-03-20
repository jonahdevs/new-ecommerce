<?php

use App\Enums\ProductType;
use App\Services\WishlistService;
use App\Services\CompareService;
use App\Services\CartService;
use App\Models\Product;
use Livewire\Component;

new class extends Component {
    public Product $product;

    public bool $wishlisted = false;
    public bool $inCompare = false;
    public bool $inCart = false;
    public int $cartQuantity = 1;
    public ?int $cartItemId = null;

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
            $this->dispatch('notify', variant: 'success', message: $added ? 'Added to wishlist' : 'Removed from wishlist');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update wishlist');
        }
    }

    public function toggleCompare(CompareService $compareService): void
    {
        try {
            $added = $compareService->toggle($this->product->id);
            $this->inCompare = $added;
            $this->dispatch('compare-updated');
            $this->dispatch('notify', variant: 'success', message: $added ? 'Added to comparison' : 'Removed from comparison');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update comparison');
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
            $cartService->addItem($this->product->id, $this->cartQuantity);

            $this->inCart = true;
            $cartItem = $cartService->getCartItem($this->product->id);
            if ($cartItem) {
                $this->cartItemId = $cartItem->id;
                $this->cartQuantity = $cartItem->quantity;
            }

            $this->dispatch('cart-updated');
            $this->dispatch('notify', variant: 'success', message: 'Added to cart successfully');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to add to cart');
        }
    }

    public function increaseCartQuantity(CartService $cartService): void
    {
        try {
            $newQuantity = $this->cartQuantity + 1;

            if ($this->product->manage_stock && $newQuantity > $this->product->stock_quantity) {
                $this->dispatch('notify', variant: 'warning', message: 'Maximum stock quantity reached');
                return;
            }

            if ($this->inCart && $this->cartItemId) {
                $cartService->updateItemQuantity($this->cartItemId, $newQuantity);
                $this->dispatch('cart-updated');
            }

            $this->cartQuantity = $newQuantity;
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update quantity');
        }
    }

    public function decreaseCartQuantity(CartService $cartService): void
    {
        try {
            $newQuantity = $this->cartQuantity - 1;

            if ($newQuantity < 1) {
                $this->dispatch('notify', variant: 'warning', message: 'Minimum quantity is 1');
                return;
            }

            if ($this->inCart && $this->cartItemId) {
                $cartService->updateItemQuantity($this->cartItemId, $newQuantity);
                $this->dispatch('cart-updated');
            }

            $this->cartQuantity = $newQuantity;
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update quantity');
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
                $this->dispatch('notify', variant: 'success', message: 'Removed from cart');
            }
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to remove from cart');
        }
    }
};
?>

<flux:card
    {{ $attributes->class(['p-0 overflow-hidden h-full hover:shadow-[0px_0px_6px_2px_rgba(0,_0,_0,_0.1)] transition-all duration-300 ease-in-out group']) }}>
    <div class="h-full flex flex-col">

        {{-- ── IMAGE ── --}}
        <div class="relative">
            <a href="{{ route('products.show', $product) }}" wire:navigate wire:click.stop class="block">
                <figure
                    class="w-full aspect-square overflow-hidden mb-2 relative bg-zinc-50 flex items-center justify-center">
                    @if ($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                            class="w-full h-full object-contain hover:scale-105 transition-transform duration-300"
                            loading="lazy">
                    @else
                        <flux:icon.photo class="w-16 h-16 text-zinc-400 stroke-1" />
                    @endif
                </figure>
            </a>

            {{-- Discount badge — simple products only --}}
            @if ($product->type === ProductType::SIMPLE && $product->hasDiscount())
                <span
                    class="absolute left-0 top-2 rounded-e-full bg-red-400 px-2 py-1 text-xs font-medium text-white tracking-wide">
                    -{{ $product->discountPercentage() }}
                </span>
            @endif

            {{-- Type badge — variable / grouped / quotation --}}
            @if ($product->type === ProductType::VARIABLE)
                <span
                    class="absolute left-0 top-2 rounded-e-full bg-sheffield-blue px-2 py-1 text-xs font-medium text-white tracking-wide">
                    Options
                </span>
            @elseif ($product->type === ProductType::GROUPED)
                <span
                    class="absolute left-0 top-2 rounded-e-full bg-zinc-700 px-2 py-1 text-xs font-medium text-white tracking-wide">
                    Kit
                </span>
            @elseif ($product->requires_quotation)
                <span
                    class="absolute left-0 top-2 rounded-e-full bg-amber-500 px-2 py-1 text-xs font-medium text-white tracking-wide">
                    Quote
                </span>
            @endif

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
                    <flux:button icon="eye" size="sm" icon-variant="outline" title="Quick View"
                        class="cursor-pointer" />
                </flux:modal.trigger>

                <flux:button wire:click.stop="toggleCompare" size="sm" icon-variant="outline"
                    icon="{{ $inCompare ? 'x-mark' : 'scale' }}" title="Compare" @class(['cursor-pointer', 'text-sheffield-blue!' => $inCompare]) />

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
                class="text-sm text-zinc-700 line-clamp-2 group-hover:underline group-hover:text-sheffield-blue">
                {{ $product->name }}
            </a>

            {{-- Star rating --}}
            <x-star-rating :rating="$product->reviews_avg_rating ?? 0" />

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
                        <span class="font-semibold text-sheffield-blue">{{ $product->display_price }}</span>
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
    <flux:modal variant="floating" name="quick-view-product-{{ $product->id }}" class="w-full max-w-2xl rounded-xs!"
        overlay-class="bg-black backdrop-blur-lg">
        <div class="grid grid-cols-3">

            {{-- Images --}}
            <div class="col-span-1" x-data="{
                mainSwiper: null,
                thumbSwiper: null,
                activeIndex: 0,
                init() {
                    const thumbEl = this.$refs.thumbSwiper;

                    if (thumbEl && thumbEl.querySelectorAll('.swiper-slide').length > 1) {
                        this.thumbSwiper = new Swiper(thumbEl, {
                            spaceBetween: 10,
                            slidesPerView: 4,
                            freeMode: true,
                            watchSlidesProgress: true,
                            loop: false,
                        });
                    }

                    this.mainSwiper = new Swiper(this.$refs.mainSwiper, {
                        spaceBetween: 10,
                        loop: false,
                        thumbs: { swiper: this.thumbSwiper ?? null },
                        on: {
                            slideChange: (swiper) => {
                                this.activeIndex = swiper.realIndex;
                            },
                        },
                    });
                },
            }">
                {{-- Main --}}
                <div class="mb-4">
                    <div class="swiper border-2 rounded-sm overflow-hidden px-2" x-ref="mainSwiper">
                        <div class="swiper-wrapper">
                            @if ($product->image_url)
                                <div class="swiper-slide">
                                    <div class="aspect-square flex items-center justify-center">
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                            class="w-full h-full object-contain" />
                                    </div>
                                </div>
                            @endif
                            @foreach ($product->images as $image)
                                <div class="swiper-slide">
                                    <div class="aspect-square flex items-center justify-center">
                                        <img src="{{ Storage::url($image->image_path) }}"
                                            alt="{{ $image->alt_text ?? $product->name }}"
                                            class="w-full h-full object-contain" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Thumbnails --}}
                @if ($product->images->count() > 0)
                    <div class="swiper px-8" x-ref="thumbSwiper">
                        <div class="swiper-wrapper">
                            @if ($product->image_url)
                                <div class="swiper-slide cursor-pointer">
                                    <div class="aspect-square rounded-sm overflow-hidden border-2 transition-all duration-300"
                                        :class="activeIndex === 0 ? 'border-sheffield-blue' : 'border-zinc-200'">
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                            class="w-full h-full object-contain" />
                                    </div>
                                </div>
                            @endif
                            @foreach ($product->images as $index => $image)
                                <div class="swiper-slide cursor-pointer">
                                    <div class="aspect-square rounded-sm overflow-hidden border-2 transition-all duration-300"
                                        :class="activeIndex === {{ $index + ($product->image_url ? 1 : 0) }} ?
                                            'border-sheffield-blue' : 'border-zinc-200'">
                                        <img src="{{ Storage::url($image->image_path) }}"
                                            alt="{{ $image->alt_text ?? $product->name }}"
                                            class="w-full h-full object-contain" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Details --}}
            <div class="col-span-2 pl-6">
                <a href="{{ route('products.show', $product) }}" wire:navigate
                    class="text-xl font-bold mt-2 mb-1 hover:text-sheffield-blue hover:underline transition-colors">
                    {{ $product->name }}
                </a>

                <x-star-rating :rating="$product->reviews_avg_rating ?? 0" class="mb-2 mt-1" />

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
                        <span class="text-lg font-semibold text-sheffield-blue">{{ $product->display_price }}</span>
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
                        <div class="mt-3 flex items-center gap-4">
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
                                <flux:button wire:click="addToCart" variant="primary" class="uppercase cursor-pointer">
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
    </flux:modal>
</flux:card>

<style>
    flux-modal::backdrop,
    [data-flux-modal]::backdrop {
        background-color: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(4px);
    }
</style>
