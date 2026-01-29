<?php

use App\Services\WishlistService;
use App\Services\CompareService;
use App\Services\CartService;
use App\Services\ReviewService;
use App\Services\ProductService;
use App\Models\Product;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\ReviewHelpfulness;

new #[Layout('layouts.guest')] class extends Component {
    public Product $product;
    public bool $wishlisted = false;
    public bool $inCompare = false;
    public bool $inCart = false;

    public string $selectedTab = 'description';

    // cart management
    public int $cartQuantity = 1;
    public ?int $cartItemId = null;

    public int $reviewsToShow = 5;

    public function mount(Product $product, WishlistService $wishlist, CompareService $compareService, CartService $cartService)
    {
        $productService = app(ProductService::class);

        $productService->recordView($product);
        $productService->rememberRecentlyViewed($product);

        $product->loadAvg('reviews', 'rating');
        $this->product = $product;

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

    #[Computed]
    public function ratingDistribution()
    {
        $reviewService = app(ReviewService::class);
        $distribution = $reviewService->ratingDistribution($this->product);
        $totalReviews = $reviewService->totalReview($this->product);

        $result = [];
        foreach ($distribution as $rating => $count) {
            $result[$rating] = [
                'count' => $count,
                'percentage' => $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0,
            ];
        }

        return $result;
    }

    #[Computed]
    public function reviews()
    {
        $reviewService = app(ReviewService::class);
        return $reviewService->forProductPage($this->product, $this->reviewsToShow);
    }

    #[Computed]
    public function totalReviews()
    {
        $reviewService = app(ReviewService::class);
        return $reviewService->totalReview($this->product);
    }

    #[Computed]
    public function averageRating()
    {
        $reviewService = app(ReviewService::class);
        return $reviewService->averageRating($this->product);
    }

    #[Computed]
    public function hasMoreReviews()
    {
        return $this->totalReviews > $this->reviewsToShow;
    }

    /**
     * Get user votes for all reviews on current page (prevents N+1 queries)
     */
    #[Computed]
    public function userVotes()
    {
        if (!Auth::check()) {
            return collect();
        }

        $reviewIds = $this->reviews->pluck('id');

        if (empty($reviewIds)) {
            return collect();
        }

        return ReviewHelpfulness::whereIn('review_id', $reviewIds)->where('user_id', Auth::id())->get()->keyBy('review_id')->map(fn($vote) => $vote->is_helpful);
    }

    #[Computed]
    public function accessories()
    {
        return $this->product->accessories()->active()->get();
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
            <flux:breadcrumbs.item href="{{ route('products', ['category' => $product->primaryCategory()->slug]) }}">
                {{ $product->primaryCategory()->name }}
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $product->name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="grid lg:grid-cols-4 gap-5">

            <div class=" lg:col-span-3 rounded-sm grid grid-cols-1 lg:grid-cols-5 gap-6 p-6">

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
                        <div class="relative" x-show="thumbSwiper" x-cloak>
                            {{-- Previous Button --}}
                            {{-- <button @click="thumbSwiper.slidePrev()"
                                class="absolute left-0 top-1/2 -translate-y-1/2 z-10 p-2 cursor-pointer ">
                                <flux:icon.chevron-left class="size-6 stroke-2" variant="solid" />
                            </button> --}}

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

                            {{-- Next Button --}}
                            {{-- <button @click="thumbSwiper.slideNext()"
                                class="absolute right-0 top-1/2 -translate-y-1/2 z-10 cursor-pointer p-2">
                                <flux:icon.chevron-right class="size-6 stroke-2" variant="solid" />
                            </button> --}}
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
                                    @if ($product->reviews_avg_rating && $i <= floor($product->reviews_avg_rating))
                                        {{-- Full star --}}
                                        <flux:icon.star variant="solid"
                                            class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                                    @elseif ($product->reviews_avg_rating && $i - 0.5 <= $product->reviews_avg_rating)
                                        {{-- Half star --}}
                                        <div class="relative w-4 h-4">
                                            <flux:icon.star variant="solid" class="w-4 h-4 text-zinc-300" />
                                            <div class="absolute inset-0 overflow-hidden" style="width: 50%;">
                                                <flux:icon.star variant="solid"
                                                    class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                                            </div>
                                        </div>
                                    @else
                                        {{-- Empty star --}}
                                        <flux:icon.star variant="solid" class="w-4 h-4 text-zinc-300" />
                                    @endif
                                @endfor
                            </div>

                            <span
                                class="text-sm font-medium text-zinc-600">({{ number_format($product->reviews_avg_rating, 1) }})</span>
                            <a href="{{ route('product.reviews', $product) }}" wire:navigate
                                class="text-sm font-medium text-sheffield-blue hover:underline">
                                {{ $product->reviews()->count() }} Reviews
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

                    @island
                        <div class="flex items-center gap-2 my-3 mt-5">
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

                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="sticky top-44 border rounded-sm">
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

                        <flux:select placeholder="" class="mt-2"></flux:select>
                    </div>

                    <div class="border-t p-3 flex items-center">
                        <div class="border rounded-sm flex items-center justify-center p-1">
                            <svg class="size-7 shrink-0" fill="currentColor" version="1.1" viewBox="0 0 100 100">
                                <path
                                    d="m56.59 6.8594c-16.781-1.4375-33.602 7.0391-41.996 22.824-2.7969 5.2617-4.4023 10.852-4.9023 16.445l-0.17578 1.9922 3.9844 0.35547 0.17578-1.9922c0.45312-5.0742 1.9062-10.141 4.4531-14.926 10.18-19.145 33.863-26.434 53.047-16.328 19.184 10.105 26.566 33.766 16.535 52.988-10.027 19.215-33.621 26.699-53.18 16.602-9.7031-5.207-16.332-13.73-19.281-23.355l-0.58203-1.918-3.8242 1.1719 0.58203 1.9102c3.25 10.602 10.578 20.008 21.215 25.719 0.011719 0.007813 0.019531 0.011719 0.03125 0.015625 21.449 11.09 47.562 2.8398 58.59-18.293 11.027-21.133 2.8711-47.266-18.219-58.375-5.2734-2.7773-10.859-4.3555-16.453-4.8359z" />
                                <path
                                    d="m29.336 34.293c-0.62109-0.29688-1.3516-0.25781-1.9336 0.11328-0.58203 0.36719-0.93359 1.0078-0.93359 1.6953v29.004c0.003906 0.80078 0.48047 1.5234 1.2188 1.8359l24.367 10.438c0.50391 0.21875 1.0781 0.21875 1.582 0l24.367-10.438c0.73438-0.31641 1.2109-1.0391 1.2109-1.8359v-29.004c0-0.6875-0.35156-1.3242-0.92969-1.6914-0.58203-0.36719-1.3086-0.41406-1.9297-0.11719l-23.512 11.191zm1.1367 4.9688 21.52 10.246-0.003907-0.003906c0.54297 0.25781 1.1719 0.25781 1.7148 0l21.52-10.246v24.523l-22.375 9.582-22.375-9.582z" />
                                <path
                                    d="m52.848 45.695c-0.53125 0-1.043 0.21094-1.418 0.58594s-0.58594 0.88672-0.58594 1.4141v27.848c0 0.53125 0.21094 1.0391 0.58594 1.4141s0.88672 0.58594 1.418 0.58594c0.52734 0 1.0391-0.21094 1.4141-0.58594s0.58594-0.88281 0.58594-1.4141v-27.848c0-0.52734-0.21094-1.0391-0.58594-1.4141s-0.88672-0.58594-1.4141-0.58594z" />
                                <path
                                    d="m52.055 22.656-24.367 10.445c-1.0117 0.43359-1.4844 1.6055-1.0547 2.6211 0.20703 0.48828 0.60156 0.875 1.0938 1.0742 0.49219 0.19922 1.0469 0.19141 1.5352-0.015625l23.586-10.105 23.586 10.105h-0.003906c0.48828 0.20703 1.043 0.21484 1.5352 0.015625 0.49219-0.19922 0.88672-0.58594 1.0938-1.0742 0.42969-1.0156-0.042969-2.1875-1.0547-2.6211l-24.367-10.445c-0.50391-0.21484-1.0781-0.21484-1.582 0z" />
                                <path
                                    d="m19.488 38.859-1.4102 1.418-5.5234 5.582-4.5039-4.4531-1.418-1.4023-2.8125 2.8438 1.418 1.4023 5.9219 5.8594c0.78516 0.77734 2.0508 0.76953 2.8281-0.011719l6.9297-6.9961 1.4102-1.4258z" />

                            </svg>
                        </div>

                        <div class="ms-2">
                            <p class="font-medium  text-sm">Return Policy</p>
                            <p class="text-xs text-zinc-500">Easy Return, Quick Refund. <a href=""
                                    class="underline text-sheffield-blue">Details</a>
                            </p>
                        </div>
                    </div>

                    <div class="border-t p-3 flex items-center">
                        <div class="border rounded-sm flex items-center justify-center p-1">
                            <flux:icon.shield-check class="size-7 shrink-0" />
                        </div>

                        <div class="ms-2">
                            <p class="font-medium  text-sm">Warranty</p>
                            <p class="text-xs text-zinc-500 stroke-1!" stroke-width="1">Covered against manufacturing
                                defects. See <a href="" class="underline text-sheffield-blue">Details</a>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @if ($this->accessories->count() > 0)
            <h3 class="text-lg font-semibold mt-5 mb-4">
                Accessories
            </h3>

            <div class="grid grid-cols-6 gap-4 ">
                @foreach ($this->accessories as $accessory)
                    <livewire:accessory-item :product="$accessory" />
                @endforeach
            </div>
        @endif

        {{--  --}}

        <div class="border pb-6 relative pt-10 px-6 mt-10">
            <div class="flex items-center gap-2 absolute top-0 left-0 -translate-y-1/2 rounded-b-sm rounded-tr-sm">

                <flux:button x-show="$wire.selectedTab == 'description'" @click="$wire.selectedTab = 'description'"
                    variant="primary" class="rounded-none cursor-pointer">
                    Description
                </flux:button>

                <flux:button x-cloak x-show="$wire.selectedTab !== 'description'"
                    @click="$wire.selectedTab = 'description'" class="rounded-none cursor-pointer">
                    Description
                </flux:button>

                <flux:button x-cloak x-show="$wire.selectedTab == 'specification'"
                    @click="$wire.selectedTab = 'specification'" variant="primary"
                    class="rounded-none cursor-pointer">
                    Specification
                </flux:button>

                <flux:button x-show="$wire.selectedTab !== 'specification'"
                    @click="$wire.selectedTab = 'specification'" class="rounded-none cursor-pointer">
                    Specification
                </flux:button>

                <flux:button x-cloak x-show="$wire.selectedTab == 'reviews'" @click="$wire.selectedTab = 'reviews'"
                    variant="primary" class="rounded-none cursor-pointer">
                    Reviews</flux:button>

                <flux:button x-show="$wire.selectedTab !== 'reviews'" @click="$wire.selectedTab = 'reviews'"
                    class="rounded-none cursor-pointer ">
                    Reviews
                </flux:button>

            </div>

            <div wire:cloak wire:show="selectedTab == 'description'">
                <div class="text-sm text-zinc-500 tracking-wider leading-6">{!! $product->description !!}</div>
            </div>

            <div wire:cloak wire:show="selectedTab == 'specification'">
                <div class="text-sm text-zinc-500 tracking-wider leading-6">{!! $product->specification !!}</div>
            </div>

            <div wire:cloak wire:show="selectedTab == 'reviews'">
                <h4 class="font-bold mb-6">Customer Ratings</h4>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-7">
                    {{-- Rating Distribution --}}
                    <div class="col-span-1 ">
                        <div class="sticky top-44">
                            <div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-sheffield-blue">{{ $this->averageRating }}
                                    </div>
                                    <div class="flex justify-center gap-1 mt-1">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= floor($this->averageRating))
                                                <flux:icon.star class="size-5 text-orange-400 fill-current" />
                                            @elseif ($i - 0.5 <= $this->averageRating)
                                                <svg class="w-5 h-5 text-orange-400" viewBox="0 0 20 20">
                                                    <defs>
                                                        <linearGradient id="half-star">
                                                            <stop offset="50%" stop-color="currentColor" />
                                                            <stop offset="50%" stop-color="#D1D5DB" />
                                                        </linearGradient>
                                                    </defs>
                                                    <path fill="url(#half-star)"
                                                        d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                                </svg>
                                            @else
                                                <flux:icon.star class="size-5 text-zinc-300 fill-current" />
                                            @endif
                                        @endfor
                                    </div>
                                    <div class="text-sm text-zinc-600 mt-1">{{ $this->totalReviews }}
                                        {{ Str::plural('review', $this->totalReviews) }}</div>
                                </div>
                            </div>

                            <flux:separator class="my-4" />

                            <div class="space-y-2">
                                @foreach ($this->ratingDistribution as $rating => $data)
                                    <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3">
                                        {{-- Star Rating --}}
                                        <div class="flex gap-0.5">
                                            @for ($star = 1; $star <= 5; $star++)
                                                @if ($star <= $rating)
                                                    <flux:icon.star class="size-5 text-orange-400 fill-current" />
                                                @else
                                                    <flux:icon.star class="size-5 text-zinc-300 fill-current" />
                                                @endif
                                            @endfor
                                        </div>

                                        {{-- Progress Bar --}}
                                        <div class="w-full bg-zinc-200 rounded-full h-2.5">
                                            <div class="bg-sheffield-blue h-2.5 rounded-full"
                                                style="width: {{ $data['percentage'] }}%"></div>
                                        </div>

                                        {{-- Percentage --}}
                                        <span class="text-sm font-semibold text-sheffield-blue min-w-[45px]">
                                            {{ $data['percentage'] }}%
                                        </span>
                                    </div>
                                @endforeach
                            </div>


                        </div>
                    </div>

                    {{-- Reviews List --}}
                    <div class="col-span-1 lg:col-span-3">
                        @if ($this->reviews->isEmpty())
                            <div class="text-center py-8 text-zinc-500">
                                <p>No reviews yet. Be the first to review this product!</p>
                            </div>
                        @else
                            <div class="space-y-6">
                                @foreach ($this->reviews as $review)
                                    <livewire:review-item :review="$review" :key="'review-item-' . $review->id" :user-vote="$this->userVotes->get($review->id)" />
                                @endforeach
                            </div>

                            {{-- View All Reviews Link --}}
                            @if ($this->hasMoreReviews)
                                <div class="mt-6 text-center">
                                    <flux:button href="{{ route('product.reviews', $product) }}" wire:navigate>
                                        View All {{ $this->totalReviews }} Reviews
                                    </flux:button>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>


        <div class="mt-10">
            <livewire:product-recommendations type="similar" :context="['product' => $product]" />
        </div>

        <div class="mt-10">
            <livewire:product-recommendations type="recently_viewed" />
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
