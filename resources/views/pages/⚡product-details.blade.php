<?php

use App\Services\WishlistService;
use App\Services\CompareService;
use App\Services\CartService;
use App\Models\Product;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.guest')] class extends Component {
    public Product $product;
    public bool $wishlisted = false;
    public bool $inCompare = false;
    public bool $inCart = false;

    public string $selectedTab = 'description';

    // cart management
    public int $cartQuantity = 1;
    public ?int $cartItemId = null;

    public function mount(WishlistService $wishlist, CompareService $compareService, CartService $cartService)
    {
        $this->wishlisted = $wishlist->has($this->product?->id);
        $this->inCompare = $compareService->has($this->product->id);
        $this->inCart = $cartService->has($this->product->id);

        // If product is in cart, load the cart item quantity
        if ($this->inCart) {
            $cartItem = $cartService->getCartItem($this->product->id);

            if ($cartItem) {
                $this->cartQuantity = $cartItem->quantity;
                $this->cartItemId = $cartItem->id;
            }
        }
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

    public function toggleCompare(CompareService $compareService)
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

<div>
    <div class="container mx-auto px-4 py-4">
        {{-- Breadcrumbs --}}
        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('products') }}">Products</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="grid lg:grid-cols-4 gap-5">

            <div class="bg-white lg:col-span-3 rounded-sm grid grid-cols-1 lg:grid-cols-5 gap-6 p-6 border">

                <div class="lg:col-span-2">
                    {{-- Product Image Slider --}}
                    <div class="w-full" x-data="{
                        mainSwiper: null,
                        thumbSwiper: null,
                        activeIndex: 0,
                        isBeginning: true,
                        isEnd: false,

                        init() {
                            // Wait for next tick to ensure DOM is ready
                            this.$nextTick(() => {
                                // Initialize thumbnail slider first
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
                            });
                        },
                    }">
                        {{-- Main Slider --}}
                        <div class="mb-4" x-cloak x-show="mainSwiper">
                            <div class="swiper mainSwiper rounded-sm overflow-hidden bg-gray-50">
                                <div class="swiper-wrapper">
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
                        <div class="relative" x-show="thumbSwiper" x-cloak>
                            {{-- Previous Button --}}
                            <button @click="thumbSwiper.slidePrev()"
                                class="absolute left-0 top-1/2 -translate-y-1/2 z-10 p-2 cursor-pointer ">
                                <flux:icon.chevron-left class="size-6 stroke-2" variant="solid" />
                            </button>

                            <div class="swiper thumbSwiper px-12">
                                <div class="swiper-wrapper">
                                    @foreach ($product->images as $image)
                                        <div class="swiper-slide cursor-pointer">
                                            <div class="aspect-square rounded-sm overflow-hidden border-2 transition-all duration-300"
                                                :class="activeIndex === $loop->index ?
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                'border-sheffield-blue' :
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                'border-gray-200 hover:border-gray-300'">
                                                <img src="{{ $image->url }}"
                                                    alt="{{ $image->alt_text ?? $product->name }}"
                                                    class="w-full h-full object-cover" />
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Next Button --}}
                            <button @click="thumbSwiper.slideNext()"
                                class="absolute right-0 top-1/2 -translate-y-1/2 z-10 cursor-pointer p-2">
                                <flux:icon.chevron-right class="size-6 stroke-2" variant="solid" />
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Product Details Section --}}
                <div class="lg:col-span-3">
                    <h1 class="text-3xl font-bold">{{ $product->name }}</h1>

                    <div class="flex items-center justify-between flex-wrap gap-3 mt-2">
                        <div class="flex items-center gap-3">
                            <span class="text-zinc-500 text-sm">Brand:</span>
                            <span class="text-sheffield-blue font-semibold text-sm">{{ $product->brand?->name }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1">
                                @for ($i = 0; $i < 5; $i++)
                                    <flux:icon.star class="size-5 text-yellow-500" variant="solid" />
                                @endfor
                            </div>
                            <span class="text-sm font-medium text-zinc-600">(5.0)</span>
                            <a href="#" class="text-sm font-medium text-sheffield-blue hover:underline">
                                345 Reviews
                            </a>
                        </div>
                    </div>

                    <div class="my-4 text-zinc-500 text-sm">{!! $product->short_description !!}</div>

                    <div>
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
                    </div>

                    <flux:separator class="mt-3" />

                    @island
                        <div class="flex items-center gap-2 my-3">
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

                            <flux:button wire:click.stop="toggleWishlist" icon="heart"
                                icon-variant="{{ $wishlisted ? 'solid' : 'outline' }}" title="Wishlist"
                                @class(['cursor-pointer', 'text-red-500!' => $wishlisted])>
                            </flux:button>

                            <flux:button wire:click="toggleCompare" icon="{{ $inCompare ? 'x-mark' : 'scale' }}"
                                icon-variant="outline" title="Compare" @class(['cursor-pointer', 'text-sheffield-!' => $inCompare])></flux:button>
                            <flux:button icon="share" icon-variant="outline" title="Share"></flux:button>
                        </div>
                    @endisland
                    <flux:separator class="my-2" />
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white border rounded-sm">
                    <div class="border-b px-3 py-2">
                        <h3 class="font-medium uppercase text-sm">Delivery & Returns</h3>
                    </div>
                    <div class="p-3">
                        <h4 class="text-sm  font-medium text-slate-600">Choose your location</h4>
                        <flux:select class="w-full mt-2" wire:model="selectedLocation">

                            {{-- @foreach ($locations as $location)
                                <flux:select.option value="{{ $location->code }}">
                                    {{ $location->name }}
                                </flux:select.option>
                            @endforeach --}}
                        </flux:select>
                    </div>
                </div>

            </div>
        </div>

        {{--  --}}
        <div class="mt-10">
            <div class="border pb-6 relative pt-10 px-6 bg-white">
                <div class="flex items-center gap-2 absolute top-0 left-0 -translate-y-1/2 rounded-b-sm rounded-tr-sm">

                    <flux:button x-show="$wire.selectedTab == 'description'" @click="$wire.selectedTab = 'description'"
                        variant="primary" class="rounded-none cursor-pointer">
                        Description
                    </flux:button>

                    <flux:button x-show="$wire.selectedTab !== 'description'"
                        @click="$wire.selectedTab = 'description'" class="rounded-none cursor-pointer">
                        Description
                    </flux:button>

                    <flux:button x-show="$wire.selectedTab == 'specification'"
                        @click="$wire.selectedTab = 'specification'" variant="primary"
                        class="rounded-none cursor-pointer">
                        Specification
                    </flux:button>

                    <flux:button x-show="$wire.selectedTab !== 'specification'"
                        @click="$wire.selectedTab = 'specification'" class="rounded-none cursor-pointer">
                        Specification
                    </flux:button>

                    <flux:button x-show="$wire.selectedTab == 'reviews'" @click="$wire.selectedTab = 'reviews'"
                        variant="primary" class="rounded-none cursor-pointer">
                        Reviews</flux:button>

                    <flux:button x-show="$wire.selectedTab !== 'reviews'" @click="$wire.selectedTab = 'reviews'"
                        class="rounded-none cursor-pointer ">
                        Reviews
                    </flux:button>

                </div>

                <div wire:show="selectedTab == 'description'">
                    <div class="text-sm text-zinc-500 tracking-wider leading-6">{!! $product->description !!}</div>
                </div>

                <div wire:show="selectedTab == 'specification'">
                    <div class="text-sm text-zinc-500 tracking-wider leading-6">{!! $product->specification !!}</div>
                </div>

                <div wire:show="selectedTab == 'reviews'">

                    <h4 class="">Customer Ratings</h4>

                    <div class="flex items"></div>
                </div>
            </div>
        </div>
    </div>
</div>



<style>
    .swiper-button-next,
    .swiper-button-prev {
        color: #fff;
        background: rgba(0, 0, 0, 0.5);
        padding: 20px;
        border-radius: 50%;
        width: 40px;
        height: 40px;
    }

    .swiper-button-next:after,
    .swiper-button-prev:after {
        font-size: 20px;
    }

    .swiper-button-next:hover,
    .swiper-button-prev:hover {
        background: rgba(0, 0, 0, 0.7);
    }

    .thumbSwiper .swiper-slide {
        opacity: 0.6;
        transition: opacity 0.3s;
    }

    .thumbSwiper .swiper-slide-thumb-active {
        opacity: 1;
    }
</style>
