<?php

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

    public function mount(WishlistService $wishlist, CompareService $compareService, CartService $cartService)
    {
        $this->wishlisted = $wishlist->has($this->product?->id);
        $this->inCompare = $compareService->has($this->product->id);
        $this->inCart = $cartService->has($this->product->id);
    }

    public function goToProduct()
    {
        return $this->redirect(route('products.show', $this->product), navigate: true);
    }

    public function toggleWishlist(WishlistService $wishlistService)
    {
        try {
            $added = $wishlistService->toggle($this->product?->id);
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

            // Dispatch events
            $this->dispatch('compare-updated');

            $this->dispatch('notify', variant: 'success', message: $added ? 'Added to comparison' : 'Removed from comparison');
        } catch (\Exception $e) {
            $this->dispatch('notify', variant: 'danger', message: $e->getMessage() ?: 'Unable to update comparison');
        } finally {
            $this->loading = false;
        }
    }

    public function addToCart(CartService $cartService)
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

    public function increaseCartQuantity(CartService $cartService)
    {
        try {
            if ($this->inCart && $this->cartItemId) {
                // Product is in cart - update cart quantity
                $newQuantity = $this->cartQuantity + 1;

                if ($newQuantity > $this->product->stock_quantity) {
                    $this->dispatch('notify', variant: 'warning', message: 'Maximum stock quantity reached');
                    return;
                }

                $cartService->updateItemQuantity($this->cartItemId, $newQuantity);
                $this->cartQuantity = $newQuantity;

                $this->dispatch('cart-updated');
            } else {
                // Product not in cart - just increase local quantity
                if ($this->cartQuantity < $this->product->stock_quantity) {
                    $this->cartQuantity++;
                } else {
                    $this->dispatch('notify', variant: 'warning', message: 'Maximum stock quantity reached');
                }
            }
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update quantity');
        }
    }

    public function decreaseCartQuantity(CartService $cartService)
    {
        try {
            if ($this->inCart && $this->cartItemId) {
                // Product is in cart - update cart quantity
                $newQuantity = $this->cartQuantity - 1;

                if ($newQuantity < 1) {
                    $this->dispatch('notify', variant: 'warning', message: 'Minimum quantity is 1');
                    return;
                }

                $cartService->updateItemQuantity($this->cartItemId, $newQuantity);
                $this->cartQuantity = $newQuantity;

                $this->dispatch('cart-updated');
            } else {
                // Product not in cart - just decrease local quantity
                if ($this->cartQuantity > 1) {
                    $this->cartQuantity--;
                } else {
                    $this->dispatch('notify', variant: 'warning', message: 'Minimum quantity is 1');
                }
            }
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update quantity');
        }
    }

    public function removeFromCart(CartService $cartService)
    {
        try {
            if ($this->cartItemId) {
                $cartService->removeItem($this->cartItemId);

                // Reset state
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
<div
    {{ $attributes->class(['bg-white overflow-hidden h-full border hover:shadow-[0px_0px_6px_2px_rgba(0,_0,_0,_0.1)] transition-all duration-300 ease-in-out group rounded-sm']) }}>
    <div class="h-full flex flex-col">
        <div class="relative">
            <a href="{{ route('products.show', $product) }}" wire:navigate wire:click.stop class="block">
                <figure
                    class="w-full aspect-square overflow-hidden mb-2 relative bg-zinc-50 flex items-center justify-center">
                    @if ($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                            class="w-full h-full object-cover hover:scale-105 transition-transform duration-300 "
                            loading="lazy">
                    @else
                        <flux:icon.photo class="w-16 h-16 text-zinc-400 stroke-1" />
                    @endif
                </figure>
            </a>
            {{-- Quick action buttons --}}
            <div
                class="absolute top-2 right-2 flex flex-col gap-2 translate-x-20 group-hover:translate-x-0 transition-transform duration-300">
                <flux:button wire:click.stop="toggleWishlist" icon="heart" title="Wishlist"
                    icon-variant="{{ $wishlisted ? 'solid' : 'outline' }}" @class([
                        'cursor-pointer',
                        'text-red-500! border-red-500!' => $wishlisted,
                    ]) size="sm">
                </flux:button>

                <flux:modal.trigger name="quick-view-product-{{ $product->id }}">
                    <flux:button icon="eye" size="sm" icon-variant="outline" title="Quick View"
                        class="cursor-pointer">
                    </flux:button>
                </flux:modal.trigger>

                <flux:button wire:click.stop="toggleCompare" icon="{{ $inCompare ? 'x-mark' : 'scale' }}" size="sm"
                    icon-variant="outline" title="Compare" @class(['cursor-pointer', 'text-red-500!' => $inCompare])>
                </flux:button>

                <flux:button wire:click="addToCart" icon="shopping-cart" size="sm" icon-variant="outline"
                    title="Add to Cart" class="cursor-pointer">

                </flux:button>
            </div>
        </div>

        <div class="p-4 flex flex-col gap-1 h-full">
            {{-- Brand --}}
            @if ($product->brand)
                <p class="text-zinc-400 text-xs uppercase tracking-wide">{{ $product->brand?->name }}</p>
            @endif

            {{-- Product Name --}}
            <a href="{{ route('products.show', $product) }}" wire:click.prevent="goToProduct"
                class="text-sm text-zinc-700 line-clamp-2 group-hover:underline group-hover:text-sheffield-blue">
                {{ $product->name }}
            </a>

            {{-- Star Rating - Always show 5 stars --}}
            <div class="flex items-center gap-1">
                <div class="flex gap-0.5">
                    @for ($i = 1; $i <= 5; $i++)
                        @if ($product->reviews_avg_rating && $i <= floor($product->reviews_avg_rating))
                            {{-- Full star --}}
                            <flux:icon.star variant="solid" class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                        @elseif ($product->reviews_avg_rating && $i - 0.5 <= $product->reviews_avg_rating)
                            {{-- Half star --}}
                            <div class="relative w-4 h-4">
                                <flux:icon.star variant="solid" class="w-4 h-4 text-zinc-300" />
                                <div class="absolute inset-0 overflow-hidden" style="width: 50%;">
                                    <flux:icon.star variant="solid" class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                                </div>
                            </div>
                        @else
                            {{-- Empty star --}}
                            <flux:icon.star variant="solid" class="w-4 h-4 text-zinc-300" />
                        @endif
                    @endfor
                </div>
                @if ($product->reviews_avg_rating)
                    <span class="text-xs text-zinc-500">{{ number_format($product->reviews_avg_rating, 1) }}</span>
                @endif
            </div>

            <div class="pt-2 mt-auto">
                @if ($product->hasDiscount())
                    <div class="flex items-center flex-wrap gap-x-2">
                        <p class="font-semibold text-sheffield-blue">{{ $product->formatted_final_price }}</p>
                        <p class="text-sm text-zinc-500 line-through">{{ $product->formatted_price }}</p>
                    </div>
                @else
                    <p class="font-semibold text-sheffield-blue">{{ $product->formatted_final_price }}</p>
                @endif
            </div>
        </div>
    </div>

    <flux:modal name="quick-view-product-{{ $product->id }}" class="w-full max-w-2xl rounded-xs!"
        overlay-class="bg-black backdrop-blur-lg">

        <div class="grid grid-cols-3 ">
            <div class="col-span-1">
                <div class="w-full" x-data="{
                    mainSwiper: null,
                    thumbSwiper: null,
                    activeIndex: 0,
                    isBeginning: true,
                    isEnd: false,

                    init() {
                        this.thumbSwiper = new Swiper('.thumbSwiper', {
                            spaceBetween: 10,
                            slidesPerView: 4,
                            freeMode: true,
                            watchSlidesProgress: true,
                            loop: true,
                            breakpoints: {
                                640: {
                                    slidesPerView: 5,
                                },
                                768: {
                                    slidesPerView: 6,
                                },
                            },
                            on: {
                                slideChange: (swiper) => {
                                    this.isBeginning = swiper.isBeginning;
                                    this.isEnd = swiper.isEnd;
                                },
                            },
                        });

                        // Initialize main slider
                        this.mainSwiper = new Swiper('.mainSwiper', {
                            spaceBetween: 10,
                            loop: true,
                            navigation: {
                                nextEl: '.swiper-button-next',
                                prevEl: '.swiper-button-prev',
                            },
                            thumbs: {
                                swiper: this.thumbSwiper,
                            },
                            on: {
                                slideChange: (swiper) => {
                                    this.activeIndex = swiper.realIndex;

                                    // Ensure the active thumbnail is visible
                                    this.thumbSwiper.slideTo(swiper.realIndex);
                                },
                            },
                        });

                        // Set initial state
                        this.isBeginning = this.thumbSwiper.isBeginning;
                        this.isEnd = this.thumbSwiper.isEnd;
                    },
                }">
                    {{-- Main Slider --}}
                    <div class="mb-4">
                        <div class="swiper mainSwiper border border-2 rounded-sm  overflow-hidden px-2">
                            <div class="swiper-wrapper ">
                                @foreach ($product->images as $image)
                                    <div class="swiper-slide">
                                        <div class="aspect-square flex items-center justify-center">
                                            <img src="{{ $image->url }}"
                                                alt="{{ $image->alt_text ?? $product->name }}"
                                                class="w-full h-full object-contain" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Thumbnail Slider --}}
                    <div class="relative">
                        <div class="swiper thumbSwiper px-12">
                            <div class="swiper-wrapper">
                                @foreach ($product->images as $image)
                                    <div class="swiper-slide cursor-pointer">
                                        <div class="aspect-square rounded-sm overflow-hidden border-2 transition-all duration-300"
                                            :class="activeIndex === {{ $loop->index }} ? 'border-sheffield-blue' :
                                                'border-zinc-200 hover:border-zinc-300'">
                                            <img src="{{ $image->url }}"
                                                alt="{{ $image->alt_text ?? $product->name }}"
                                                class="w-full h-full object-cover" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-2 pl-6">
                <a href="{{ route('products.show', $product) }}" wire:navigate
                    class="text-xl font-bold mt-1 hover:text-sheffield-blue hover:underline transition-colors duration-300">{{ $product->name }}</a>
                <div class="flex items-center gap-1 mb-2">
                    @for ($i = 0; $i < 5; $i++)
                        @if ($product->reviews_avg_rating && $i <= floor($product->reviews_avg_rating))
                            {{-- Full star --}}
                            <flux:icon.star variant="solid" class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                        @elseif ($product->reviews_avg_rating && $i - 0.5 <= $product->reviews_avg_rating)
                            {{-- Half star --}}
                            <div class="relative w-4 h-4">
                                <flux:icon.star variant="solid" class="w-4 h-4 text-zinc-300" />
                                <div class="absolute inset-0 overflow-hidden" style="width: 50%;">
                                    <flux:icon.star variant="solid" class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                                </div>
                            </div>
                        @else
                            {{-- Empty star --}}
                            <flux:icon.star variant="solid" class="w-4 h-4 text-zinc-300" />
                        @endif
                    @endfor
                </div>

                <div class="my-4 text-zinc-500 text-sm line-clamp-3">{!! $product->short_description !!}</div>

                @if ($product->hasDiscount())
                    <div class="flex items-center flex-wrap gap-x-2">
                        <p class="text-lg font-semibold text-sheffield-blue">
                            {{ $product->formatted_final_price }}
                        </p>
                        <p class=" text-zinc-500 line-through">{{ $product->formatted_sale_price }}</p>

                        <flux:badge color="amber" size="sm">-{{ $product->discountPercentage() }}
                        </flux:badge>
                    </div>
                @else
                    <p class="font-semibold text-lg text-sheffield-blue">{{ $product->formatted_final_price }}
                    </p>
                @endif

                @island
                    <div class="mt-1 flex items-center gap-4">
                        <flux:button.group>
                            <flux:button icon="minus" class="cursor-pointer text-zinc-500!" title="Decrease Quantity"
                                wire:click="decreaseCartQuantity"></flux:button>

                            <flux:input readonly value="{{ $cartQuantity }}"
                                class="max-w-9! outline-none! border-none! ring-0 focus:outline-none! focus:border-none!"
                                style="outline: none; padding-left: 0 !important; padding-right: 0 !important; text-align: center !important;" />

                            <flux:button icon="plus" class="cursor-pointer text-zinc-500!" title="Increase Quantity"
                                wire:click="increaseCartQuantity"></flux:button>

                            @if ($inCart)
                                <flux:button icon="trash" class="cursor-pointer text-red-500!"
                                    wire:click="removeFromCart" title="Remove Item from Cart">
                                </flux:button>
                            @endif
                        </flux:button.group>

                        @if (!$inCart)
                            <flux:button wire:click="addToCart" class="uppercase" variant="primary">
                                Add to Cart
                            </flux:button>
                        @endif
                    </div>
                @endisland

            </div>
        </div>
    </flux:modal>
</div>


<style>
    flux-modal::backdrop,
    [data-flux-modal]::backdrop {
        background-color: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(4px);
    }
</style>
