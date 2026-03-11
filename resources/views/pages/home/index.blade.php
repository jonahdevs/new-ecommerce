<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use App\Models\{Category, Product};
use App\Enums\CategorySection;

new #[Layout('layouts.guest')] class extends Component {
    #[Computed(persist: true)]
    public function heroBanners()
    {
        return config('site.hero_slides');
    }

    #[Computed(persist: true)]
    public function topCategories()
    {
        return Category::inSection(CategorySection::HOME_PAGE_FEATURED)->active()->get();
    }

    #[Computed(persist: true)]
    public function newArrivals()
    {
        return Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description'])
            ->withAvg('reviews', 'rating')
            ->with('brand:id,name')
            ->active()
            ->newArrivals()
            ->inRandomOrder()
            ->limit(20)
            ->get();
    }

    #[Computed(persist: true)]
    public function products()
    {
        $products = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description'])
            ->withAvg('reviews', 'rating')
            ->with('brand:id,name')
            ->active()
            ->inRandomOrder()
            ->limit(24)
            ->get();

        return $products;
    }
};
?>

<div>
    {{-- Hero section --}}
    <div class="container mx-auto mt-5 px-4" x-data="{
        swiper: null,
        isPaused: false,
        autoplayDelay: 5000,
        progressCircumference: 2 * Math.PI * 18,
        progressOffset: 0,
    
        init() {
            this.swiper = new Swiper('#heroSwiper', {
                loop: true,
                autoplay: {
                    delay: this.autoplayDelay,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: '#heroSwiper .swiper-pagination',
                    clickable: true,
                },
                on: {
                    slideChange: () => {
                        this.resetProgress();
                    },
                },
            });
            this.$nextTick(() => {
                document.getElementById('heroSwiper').classList.remove('opacity-0');
            });
            this.startProgress();
        },
        toggleAutoplay() {
            this.isPaused = !this.isPaused;
            if (this.isPaused) {
                this.swiper.autoplay.stop();
            } else {
                this.swiper.autoplay.start();
                this.startProgress();
            }
        },
        startProgress() {
            this.progressOffset = 0;
            const interval = setInterval(() => {
                if (this.isPaused) {
                    clearInterval(interval);
                    return;
                }
                this.progressOffset += (this.progressCircumference / (this.autoplayDelay / 100));
                if (this.progressOffset >= this.progressCircumference) {
                    this.progressOffset = 0;
                }
            }, 100);
        },
        resetProgress() {
            this.progressOffset = 0;
        }
    }">
        <div class="swiper opacity-0 transition-opacity duration-500" id="heroSwiper">
            <div class="swiper-wrapper">
                @foreach ($this->heroBanners as $banner)
                    <div class="swiper-slide">
                        <img src="{{ $banner['image'] }}" alt="{{ $banner['alt'] }}" class="w-full h-auto rounded-md">
                    </div>
                @endforeach
            </div>

            <div class="swiper-pagination"></div>

            <!-- Compact Circular Progress Indicator with Pause/Play - Bottom Right -->
            <div class="absolute -bottom-2 right-3 sm:bottom-4 sm:right-4 z-50">
                <button type="button" @click="toggleAutoplay()"
                    class="relative w-7 h-7 sm:w-10 sm:h-10 group cursor-pointer"
                    :aria-label="isPaused ? 'Play slideshow' : 'Pause slideshow'">
                    <!-- Background Circle with Shadow -->
                    <svg class="w-full h-full transform -rotate-90 drop-shadow-lg" viewBox="0 0 48 48">
                        <!-- Outer glow/shadow circle -->
                        <circle cx="24" cy="24" r="22" fill="rgba(0, 0, 0, 0.3)" />

                        <!-- Background Circle -->
                        <circle cx="24" cy="24" r="20" fill="rgba(255, 255, 255, 0.95)" />

                        <!-- Background track (light gray) -->
                        <circle cx="24" cy="24" r="18" fill="none" stroke="rgba(0, 0, 0, 0.1)"
                            stroke-width="2.5" />

                        <!-- Progress Circle (Sheffield Red) -->
                        <circle cx="24" cy="24" r="18" fill="none" stroke="#E31E24" stroke-width="2.5"
                            stroke-linecap="round" :stroke-dasharray="progressCircumference"
                            :stroke-dashoffset="progressOffset" class="transition-all duration-100 ease-linear" />
                    </svg>

                    <!-- Pause/Play Icon (Centered) -->
                    <div class="absolute inset-0 flex items-center justify-center">
                        <!-- Play Icon -->
                        <svg x-show="isPaused"
                            class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-sheffield-red ml-0.5 transition-transform group-hover:scale-110"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                        <!-- Pause Icon -->
                        <svg x-show="!isPaused"
                            class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-sheffield-red transition-transform group-hover:scale-110"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                        </svg>
                    </div>
                </button>
            </div>

            <!-- Slider controls -->

            <button type="button" @click="swiper.slidePrev()"
                class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none">
                <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/30 dark:bg-zinc-800/30 hover:bg-white/50 dark:hover:bg-zinc-800/60 focus:ring-4 focus:ring-white dark:focus:ring-zinc-800/70 focus:outline-none">
                    <flux:icon.arrow-long-left class="size-4 text-white" />
                    <span class="sr-only">Previous</span>
                </span>
            </button>

            <button type="button" @click="swiper.slideNext()"
                class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none">
                <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/30 dark:bg-zinc-800/30 hover:bg-white/50 dark:hover:bg-zinc-800/60 focus:ring-4 focus:ring-white dark:focus:ring-zinc-800/70 focus:outline-none">
                    <flux:icon.arrow-long-right class="size-4 text-white" />
                    <span class="sr-only">Next</span>
                </span>
            </button>
        </div>
    </div>

    <section class="border-y border-zinc-200 bg-white my-6">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 divide-x divide-zinc-100">

                <div class="flex flex-col items-center text-center p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-sheffield-red">
                        <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-900">Africa No. 1</h3>
                    <p class="mt-1 text-xs text-zinc-500 leading-tight">In Kitchen Equipment</p>
                </div>

                <div class="flex flex-col items-center text-center p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-sheffield-red">
                        <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-900">Guaranteed</h3>
                    <p class="mt-1 text-xs text-zinc-500 leading-tight">Quality Assurance</p>
                </div>

                <div class="flex flex-col items-center text-center p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-sheffield-red">
                        <flux:icon.arrows-pointing-out class="size-8" />
                    </div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-900">Customized</h3>
                    <p class="mt-1 text-xs text-zinc-500 leading-tight">Bespoke Solutions</p>
                </div>

                <div class="flex flex-col items-center text-center p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-sheffield-red">
                        <flux:icon.truck class="size-8 stroke-1.5" />
                    </div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-900">Fast Delivery</h3>
                    <p class="mt-1 text-xs text-zinc-500 leading-tight">Countrywide Shipping</p>
                </div>

                <div class="hidden lg:flex flex-col items-center text-center p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-sheffield-red">
                        <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M14.25 9.75L16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-900">Installation</h3>
                    <p class="mt-1 text-xs text-zinc-500 leading-tight">Professional Setup</p>
                </div>
            </div>
        </div>
    </section>



    @island('top-categories')
        @placeholder
            <div class="">
                <div class="py-4">
                    <!-- Responsive Heading -->
                    <h2 class="font-semibold text-xl text-zinc-800 ">
                        Top Categories
                    </h2>
                </div>
                <div
                    class="py-3 pb-5 grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 gap-3">
                    @for ($i = 0; $i < 14; $i++)
                        <div class="animate-pulse">
                            <div class="w-full aspect-4/3 bg-zinc-200 rounded-md"></div>
                            <div class="w-3/4 h-3 sm:h-4 mt-2 bg-zinc-200 mx-auto rounded"></div>
                        </div>
                    @endfor
                </div>
            </div>
        @endplaceholder

        @include('pages.home.top-categories')
    @endisland

    <section class="container mx-auto px-4 my-6">
        <img src="{{ asset('images/home/THIN BANNER.png') }}" alt="banner" class="w-full h-auto">
    </section>

    <div class="container mx-auto px-4">
        <div class=" pt-4 bg-sheffield-red border rounded-sm grid grid-cols-1 lg:grid-cols-6 gap-4">
            <div class="lg:col-span-1 flex justify-center flex-col text-white px-3 md:px-5 py-4 lg:py-0">
                <h4 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-3 lg:mb-5">New</h4>

                <p class="text-sm sm:text-base font-medium tracking-wide">Just In! Explore Our Latest Product Arrivals
                </p>

                <flux:button class="w-fit mt-4">Show All</flux:button>
            </div>
            @island('new-arrivals', defer: true)
                @placeholder
                    <section class="px-3 md:px-5 lg:col-span-5 relative pb-4">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 pb-4">
                            @for ($i = 0; $i < 5; $i++)
                                <x-product-card-placeholder />
                            @endfor
                        </div>
                    </section>
                @endplaceholder

                @include('pages.home.new-arrivals')
            @endisland
        </div>
    </div>

    <section class="container mx-auto px-4 mt-6">
        <a href="#" class=" block overflow-hidden rounded-sm">
            <img src="{{ asset('images/home/CLEARANCE-SALE.jpg') }}" alt="banner"
                class="w-full h-auto object-cover object-center rounded-sm">
        </a>
    </section>

    @island(name: 'products', defer: true)
        @placeholder
            <div class="container mx-auto px-4">

                <section class="flex items-center justify-between py-4 ">
                    <h2 class="font-semibold text-xl text-zinc-800">You May Also Like</h2>
                </section>

                <div class=" grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 pb-5">
                    @for ($i = 0; $i < 12; $i++)
                        <x-product-card-placeholder />
                    @endfor
                </div>
            </div>
        @endplaceholder

        @include('pages.home.products')
    @endisland

    <!-- Locations Section -->
    <section class="container @container/locations mx-auto px-4 my-12">
        <!-- Header -->
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Our Locations</h2>
            <p class="text-lg text-sheffield-red">From local hubs to a continental presence.</p>
        </div>

        <!-- Locations Grid -->
        <div
            class="grid grid-cols-1 @sm/locations:grid-cols-2 @3xl/locations:grid-cols-3 @5xl/locations:grid-cols-4 gap-4">

            <!-- Nairobi -->
            <div class="bg-white rounded overflow-hidden hover:shadow-xs transition-shadow duration-300">
                <img src="{{ asset('images/showrooms/SHEFFIELD SHOWROOM.png') }}" alt="Location 1"
                    class="w-full h-56 object-cover" />
                <div class="p-5">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        Nairobi
                        <img src="{{ asset('images/kenya-flag.png') }}" alt="kenya flag" class="size-5 rounded-full">
                    </h3>

                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-600 mt-0.5 shrink-0" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div class="text-sm text-gray-700">
                                <p>Off Old Mombasa Road before the Nairobi SGR Terminus</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                            <a href="tel:+11234567890"
                                class="text-sm text-gray-700 hover:text-red-600 transition-colors">
                                +254 713 444 000
                            </a>
                        </div>

                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            <a href="mailto:contact@location1.com"
                                class="text-sm text-gray-700 hover:text-sheffield-red transition-colors break-all">
                                info@sheffieldafrica.com
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mombasa -->
            <div class="bg-white rounded overflow-hidden hover:shadow-sm transition-shadow duration-300">
                <img src="{{ asset('images/showrooms/MOMBASA SHOWROOM-01.jpg') }}" alt="Location 2"
                    class="w-full h-56 object-cover" />
                <div class="p-5">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        Mombasa
                        <img src="{{ asset('images/kenya-flag.png') }}" alt="kenya flag" class="size-5 rounded-full">
                    </h3>

                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-600 mt-0.5 shrink-0" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div class="text-sm text-gray-700">
                                <p>Petrocity Complex 1st Floor-Off Links Road, Nyali, Mombasa</p>

                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                            <a href="tel:+11234567890"
                                class="text-sm text-gray-700 hover:text-red-600 transition-colors">
                                +254 713 317 214
                            </a>
                        </div>

                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            <a href="mailto:contact@location2.com"
                                class="text-sm text-gray-700 hover:text-sheffield-red transition-colors  break-all">
                                mombasa@sheffieldafrica.com
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kampala -->
            <div class="bg-white rounded overflow-hidden hover:shadow-sm transition-shadow duration-300">
                <img src="{{ asset('images/showrooms/IMAGES SHOWROOMS-01.jpg') }}" alt="Location 2"
                    class="w-full h-56 object-cover" />
                <div class="p-5">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        Kampala
                        <img src="{{ asset('images/uganda.png') }}" alt="uganda flag" class="size-5 rounded-full">
                    </h3>

                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-600 mt-0.5 shrink-0" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div class="text-sm text-gray-700">
                                <p>Bugolobi Hardware City Opposite Uganda Baati, Block 3 Room 102, Mulwana Road.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                            <a href="tel:+11234567890"
                                class="text-sm text-gray-700 hover:text-red-600 transition-colors">
                                +256 741 177 712
                            </a>
                        </div>

                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            <a href="mailto:contact@location2.com"
                                class="text-sm text-gray-700 hover:text-sheffield-red transition-colors  break-all">
                                uganda@sheffieldafrica.com
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kigali -->
            <div class="bg-white rounded overflow-hidden hover:shadow-sm transition-shadow duration-300">
                <img src="{{ asset('images/showrooms/IMAGES SHOWROOMS-02.jpg') }}" alt="Location 3"
                    class="w-full h-56 object-cover" />
                <div class="p-5">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        Kigali
                        <img src="{{ asset('images/rwanda.png') }}" alt="rwanda flag" class="size-5 rounded-full">
                    </h3>

                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-600 mt-0.5 shrink-0" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div class="text-sm text-gray-700">
                                <p>Kicukiro Street, KK 500 ST Kigali, Rwanda</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                            <a href="tel:+11234567890"
                                class="text-sm text-gray-700 hover:text-red-600 transition-colors">
                                +250 794 007 302
                            </a>
                        </div>

                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            <a href="mailto:contact@location3.com"
                                class="text-sm text-gray-700 hover:text-sheffield-red transition-colors break-all">
                                rwanda@sheffieldafrica.com
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
