<?php

use Livewire\Component;
use App\Models\Category;
use Livewire\Attributes\Computed;

new class extends Component {
    public int $cartCount = 0;
    public int $compareCount = 0;
    public int $wishlistCount = 0;

    #[Computed]
    public function categories()
    {
        return Category::active()->navbar()->orderBy('sort_order')->orderBy('name')->get();
    }
};
?>

<div>
    <div class="bg-sheffield-red text-white">
        <section class="container mx-auto px-4">
            <div class="flex items-center justify-between py-2 text-sm gap-4">
                {{-- contact info - Hidden on mobile, visible on md+ --}}
                <div class="hidden md:flex items-center gap-3 lg:gap-4">
                    <div class="flex items-center gap-2">
                        <flux:icon.phone class="w-4.5 h-4.5 shrink-0" />
                        <span class="text-xs lg:text-sm">(254) 713 777 111</span>
                    </div>
                </div>

                {{-- Vertical Promotion Carousel --}}
                <div class="flex-1 md:flex-none md:max-w-md lg:max-w-lg mx-auto overflow-hidden h-6"
                    x-data="{
                        swiper: null,
                        init() {
                            this.$nextTick(() => {
                                this.initializeSwiper();
                            });
                        },
                    
                        initializeSwiper() {
                            this.swiper = new Swiper('.promoSwiper', {
                                direction: 'vertical',
                                loop: true,
                                speed: 800,
                                autoplay: {
                                    delay: 3000,
                                    disableOnInteraction: false,
                                },
                                effect: 'slide',
                                // Optional: Add fade effect for smoother transitions
                                // effect: 'fade',
                                // fadeEffect: {
                                //     crossFade: true
                                // },
                            });
                        },
                    
                        destroy() {
                            if (this.swiper) {
                                this.swiper.destroy(true, true);
                            }
                        }
                    }">
                    <div class="swiper promoSwiper h-full">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide flex items-center justify-center">
                                <a href="#"
                                    class="text-center text-xs sm:text-sm hover:opacity-90 transition-opacity">
                                    Get 50% off on Member Exclusive Month <span class="underline font-medium">Shop
                                        Now</span>
                                </a>
                            </div>
                            <div class="swiper-slide flex items-center justify-center">
                                <a href="#"
                                    class="text-center text-xs sm:text-sm hover:opacity-90 transition-opacity">
                                    Free Shipping on Orders Over KES 10,000 <span class="underline font-medium">Learn
                                        More</span>
                                </a>
                            </div>
                            <div class="swiper-slide flex items-center justify-center">
                                <a href="#"
                                    class="text-center text-xs sm:text-sm hover:opacity-90 transition-opacity">
                                    New Arrivals: Latest Kitchen Equipment <span
                                        class="underline font-medium">Explore</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Link - Hidden on mobile, visible on md+ -->
                <div class="hidden md:flex items-center gap-4">
                    <a href="" class="flex items-center gap-2 group hover:opacity-90 transition-opacity">
                        <flux:icon.question-mark-circle class="size-5 shrink-0" />
                        <span class="group-hover:underline text-xs lg:text-sm">Support</span>
                    </a>
                </div>
            </div>
        </section>
    </div>

    {{-- Main Header --}}
    <nav class="w-full stick top-0 z-50 bg-cover bg-no-repeat"
        style="background-image: url('{{ asset('images/stainless_steel.jpg') }}')">
        <section class="container mx-auto px-4 py-3 lg:py-4">
            <div class="flex justify-between items-center gap-2 sm:gap-4 lg:gap-6">
                {{-- Logo --}}
                <a href="{{ route('home') }}" wire:navigate class="flex items-center shrink-0">
                    <img src="{{ asset('logo.png') }}" alt="{{ config('site.site.name') }} Logo"
                        class="h-8 sm:h-10 lg:h-12 w-auto transition-transform duration-300 hover:scale-105" />
                </a>

                {{-- Search Bar --}}
                <div class="flex-1">
                    <livewire:search-bar />
                </div>

                {{-- Cart & Account --}}
                <div class="flex items-center gap-3 sm:gap-4 lg:gap-6">

                    {{-- Wishlist --}}
                    <a href="" class="flex items-center gap-2">
                        <div class="relative">
                            <flux:icon.heart class="size-6 text-zinc-900 " />
                            @if ($wishlistCount > 0)
                                <span
                                    class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-medium">
                                    {{ $wishlistCount }}
                                </span>
                            @endif
                        </div>
                        <span class="hidden lg:inline text-sm font-medium text-zinc-900">Wishlist</span>
                    </a>

                    {{-- Compare --}}
                    <a href="" class="flex items-center gap-2">
                        <div class="relative">
                            <!-- Compare Icon -->
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                <g id="SVGRepo_iconCarrier">
                                    <g clip-path="url(#clip0_105_1836)">
                                        <path
                                            d="M13 3.99976H6C4.89543 3.99976 4 4.89519 4 5.99976V17.9998C4 19.1043 4.89543 19.9998 6 19.9998H13M17 3.99976H18C19.1046 3.99976 20 4.89519 20 5.99976V6.99976M20 16.9998V17.9998C20 19.1043 19.1046 19.9998 18 19.9998H17M20 10.9998V12.9998M12 1.99976V21.9998"
                                            stroke="#292929" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2"></path>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_105_1836">
                                            <rect fill="white" height="24" transform="translate(0 -0.000244141)"
                                                width="24"></rect>
                                        </clipPath>
                                    </defs>
                                </g>
                            </svg>

                            <!-- Badge with count -->
                            @if ($compareCount > 0)
                                <span
                                    class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
                                    {{ $compareCount }}
                                </span>
                            @endif
                        </div>
                        <span class="hidden lg:inline text-sm font-medium text-zinc-900">Compare</span>
                    </a>

                    {{-- Cart --}}
                    <a href="" class="flex items-center gap-2">
                        <div class="relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>

                            @if ($cartCount > 0)
                                <span
                                    class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-medium rounded-full w-5 h-5 flex items-center justify-center">
                                    {{ $cartCount }}
                                </span>
                            @endif
                        </div>
                        <span class="hidden lg:inline text-sm font-medium text-zinc-900">Cart</span>
                    </a>
                </div>
            </div>
        </section>
    </nav>
</div>
