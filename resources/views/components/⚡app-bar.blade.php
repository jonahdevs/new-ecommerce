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
    <nav class="w-full sticky! top-0! left-0! z-50! bg-cover bg-center bg-no-repeat"
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

                {{-- User Profile Dropdown --}}
                <flux:dropdown position="bottom" align="end" hover>
                    @auth
                        @if (auth()->user()->avatar)
                            <flux:profile circle avatar="{{ auth()->user()->avatar }}" name="{{ auth()->user()->name }}" />
                        @else
                            <flux:profile circle name="{{ auth()->user()->name }}" />
                        @endif
                    @else
                        <button type="button" class="flex items-center gap-2 hover:text-sheffield-sheffield-blue-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <div class="hidden lg:block">
                                <div class="text-sm font-medium text-zinc-900">Account</div>
                            </div>
                            <svg class="w-4 h-4 hidden lg:block" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    @endauth

                    <flux:navmenu class="mt-5.5! rounded-sm!">
                        <flux:navmenu.item href="#" wire:navigate icon="user" icon-variant="outline">Account
                        </flux:navmenu.item>

                        <flux:navmenu.item href="#" wire:navigate icon="package" icon-variant="outline">
                            Orders
                        </flux:navmenu.item>

                        <flux:navmenu.item href="#" wire:navigate icon="heart" icon-variant="outline">
                            Favorite Items
                        </flux:navmenu.item>

                        @auth
                            <flux:navmenu.item href="#" wire:navigate icon="envelope">
                                Messages
                            </flux:navmenu.item>
                        @endauth
                        <flux:menu.separator />
                        @auth
                            <form action="{{ route('logout') }}" method="post">
                                @csrf
                                <flux:navmenu.item type="submit" icon="arrow-right-start-on-rectangle" variant="danger"
                                    class="cursor-pointer">
                                    Logout
                                </flux:navmenu.item>
                            </form>
                        @else
                            <flux:navmenu.item href="{{ route('login') }}" icon="arrow-left-start-on-rectangle"
                                class="cursor-pointer">
                                Log in
                            </flux:navmenu.item>
                        @endauth
                    </flux:navmenu>
                </flux:dropdown>
            </div>
        </section>
    </nav>

    {{-- Category navigation --}}
    <nav class="bg-sheffield-red text-white sticky! left-0! top-20! z-40 shadow-md">
        <section class="container mx-auto px-4 hidden lg:block">
            <ul class="m-0 flex flex-wrap border-r border-white/20  p-0" data-language="en" role="menubar"
                aria-label="Main navigation menu">
                @foreach ($this->categories->take(12) as $category)
                    <li class="w-[16.66666666666667%] cursor-pointer hover:bg-sheffield-red-dark" tabindex="0"
                        role="menuitem" aria-expanded="false">
                        <div class="relative h-9.25">
                            <a class="flex min-h-full items-center overflow-hidden text-ellipsis whitespace-nowrap border-l border-white/20 px-1.25 xl:px-2.5 border-b"
                                wire:navigate href="#">
                                <img alt="" loading="eager" width="26" height="26" decoding="async"
                                    data-nimg="1" class="duration-300 max-h-6.5 max-w-6.5 max-md:hidden invert"
                                    style="color:transparent" src="{{ $category->icon_url }}">
                                <span class="ml-2 truncate text-sm text-zinc-50">{{ $category->name }}</span>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section
            class="container mx-auto px-4 lg:hidden grid grid-flow-col auto-cols-max gap-1 overflow-x-auto scrollbar-hide">
            @foreach ($this->categories as $category)
                <a href="#" wire:navigate
                    class="inline-block px-4 py-3 text-sm hover:opacity-80 transition-opacity duration-500">
                    {{ $category->name }}
                </a>
            @endforeach
        </section>
    </nav>

</div>
