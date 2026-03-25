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
        return Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description', 'type', 'requires_quotation', 'reviews_enabled'])
            ->with([
                'brand:id,name,slug',
                'images' => fn($q) => $q->limit(1),
                'variants' => fn($q) => $q
                    ->where('is_active', true)
                    ->whereNotNull('price')
                    ->select(['id', 'product_id', 'price', 'sale_price', 'is_active']),
            ])
            ->withAvg('reviews', 'rating')
            ->active()
            ->newArrivals()
            ->inRandomOrder()
            ->limit(20)
            ->get();
    }

    #[Computed(persist: true)]
    public function products()
    {
        return Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description', 'type', 'requires_quotation', 'reviews_enabled'])
            ->with([
                'brand:id,name,slug',
                'images' => fn($q) => $q->limit(1),
                'variants' => fn($q) => $q
                    ->where('is_active', true)
                    ->whereNotNull('price')
                    ->select(['id', 'product_id', 'price', 'sale_price', 'is_active']),
            ])
            ->withAvg('reviews', 'rating')
            ->active()
            ->inRandomOrder()
            ->limit(24)
            ->get();
    }
};
?>

<div>

    {{-- ============================================================ --}}
    {{-- Hero Background Wrapper — top of page → feature strips       --}}
    {{-- ============================================================ --}}
    <div class="relative overflow-hidden bg-cover bg-center bg-fixed"
        style="background-image: url('{{ asset('images/home/hero-bg.jpg') }}');">

        {{-- Hero section --}}
        <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8 relative" x-data="{
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

            {{-- Carousel — slightly inset with a shadow ring so it floats --}}
            <div class="swiper opacity-0 transition-opacity duration-500 rounded-md overflow-hidden shadow-md"
                id="heroSwiper">
                <div class="swiper-wrapper">
                    @foreach ($this->heroBanners as $banner)
                        <div class="swiper-slide">
                            <img src="{{ $banner['image'] }}" alt="{{ $banner['alt'] }}" class="w-full h-auto">
                        </div>
                    @endforeach
                </div>

                <div class="swiper-pagination"></div>

                {{-- Circular Progress Indicator with Pause/Play --}}
                <div class="absolute -bottom-2 right-3 sm:bottom-4 sm:right-4 z-50">
                    <button type="button" @click="toggleAutoplay()"
                        class="relative w-7 h-7 sm:w-10 sm:h-10 group cursor-pointer"
                        :aria-label="isPaused ? 'Play slideshow' : 'Pause slideshow'">
                        <svg class="w-full h-full transform -rotate-90 drop-shadow-lg" viewBox="0 0 48 48">
                            <circle cx="24" cy="24" r="22" fill="rgba(0, 0, 0, 0.3)" />
                            <circle cx="24" cy="24" r="20" fill="rgba(255, 255, 255, 0.95)" />
                            <circle cx="24" cy="24" r="18" fill="none" stroke="rgba(0, 0, 0, 0.1)"
                                stroke-width="2.5" />
                            <circle cx="24" cy="24" r="18" fill="none"
                                style="stroke: var(--brand-primary)" stroke-width="2.5" stroke-linecap="round"
                                :stroke-dasharray="progressCircumference" :stroke-dashoffset="progressOffset"
                                class="transition-all duration-100 ease-linear" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg x-show="isPaused"
                                class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-brand-primary ml-0.5 transition-transform group-hover:scale-110"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z" />
                            </svg>
                            <svg x-show="!isPaused"
                                class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-brand-primary transition-transform group-hover:scale-110"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                            </svg>
                        </div>
                    </button>
                </div>

                {{-- Prev / Next controls --}}
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
    </div>


    {{-- Feature strips — sits directly below, no  needed --}}
    <section class="border-y border-zinc-200 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 divide-x divide-zinc-100">

                <div class="flex flex-col items-center text-center p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-brand-primary">
                        <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-900">Africa No. 1</h3>
                    <p class="mt-1 text-xs text-zinc-500 leading-tight">In Kitchen Equipment</p>
                </div>

                <div class="flex flex-col items-center text-center p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-brand-primary">
                        <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-900">Guaranteed</h3>
                    <p class="mt-1 text-xs text-zinc-500 leading-tight">Quality Assurance</p>
                </div>

                <div class="flex flex-col items-center text-center p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-brand-primary">
                        <flux:icon.arrows-pointing-out class="size-8" />
                    </div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-900">Customized</h3>
                    <p class="mt-1 text-xs text-zinc-500 leading-tight">Bespoke Solutions</p>
                </div>

                <div class="flex flex-col items-center text-center p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-brand-primary">
                        <svg class="size-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-900">Fast Delivery</h3>
                    <p class="mt-1 text-xs text-zinc-500 leading-tight">Countrywide Shipping</p>
                </div>

                <div class="hidden lg:flex flex-col items-center text-center p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-brand-primary">
                        <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
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
        <div class="container mx-auto px-4 mt-6">
            <div class="pb-6">
                <h2 class="font-bold text-lg md:text-2xl text-zinc-900 leading-tight">Top Categories</h2>
                <p class="text-zinc-500 text-xs md:text-sm mt-2">Discover our most popular shopping categories</p>
            </div>
            @placeholder
                <div
                    class="py-3 pb-5 grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 gap-3">
                    @for ($i = 0; $i < 14; $i++)
                        <div class="animate-pulse">
                            <div class="w-full aspect-4/3 bg-zinc-200 rounded-md"></div>
                            <div class="w-3/4 h-3 sm:h-4 mt-2 bg-zinc-200 mx-auto rounded"></div>
                        </div>
                    @endfor
                </div>
            @endplaceholder
            @include('pages.home.top-categories')
        </div>
    @endisland

    <section class="container mx-auto px-4 mt-6">
        <img src="{{ asset('images/home/THIN BANNER.png') }}" alt="banner" class="w-full h-auto">
    </section>

    {{-- New Arrivals --}}
    <div class="container mx-auto px-4 mt-8">
        <div class="bg-brand-primary rounded-md overflow-hidden grid grid-cols-1 lg:grid-cols-6">

            {{-- Left Panel --}}
            <div
                class="lg:col-span-1 flex flex-col justify-center px-5 md:px-6 py-6 lg:py-8
                border-b border-white/10 lg:border-b-0 lg:border-r lg:border-white/10">
                <span class="text-white/60 text-xs font-semibold uppercase tracking-widest mb-2">
                    Just In
                </span>
                <h4 class="text-3xl lg:text-4xl font-bold text-white leading-none mb-3">New</h4>
                <p class="text-white/75 text-sm leading-relaxed mb-5">
                    Discover what's just dropped
                </p>
                <a href="#"
                    class="inline-flex items-center gap-1.5 w-fit text-xs font-semibold
                        text-white border border-white/30 hover:border-white hover:bg-white/10
                        px-4 py-2 rounded-full transition-all duration-200 group focus:outline-none">
                    View All
                    <flux:icon.arrow-right
                        class="size-3.5 transition-transform duration-200 group-hover:translate-x-1" />
                </a>
            </div>

            {{-- Products --}}
            @island('new-arrivals', defer: true)
                @placeholder
                    <section class="lg:col-span-5 px-4 md:px-5 py-5">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
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

    @island(name: 'products', defer: true)
        @placeholder
            <div class="container mx-auto px-4 mt-8">
                <div class="flex items-center gap-3 py-4">
                    <h2 class="font-semibold text-xl text-zinc-900">You May Also Like</h2>
                    <div class="h-px flex-1 bg-zinc-100"></div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 pb-6">
                    @for ($i = 0; $i < 12; $i++)
                        <x-product-card-placeholder />
                    @endfor
                </div>
            </div>
        @endplaceholder
        @include('pages.home.products')
    @endisland

    {{-- Locations Section --}}
    <section class="container @container/locations mx-auto px-4 mt-6 mb-12">

        <div class="pb-6">
            <h2 class="text-2xl font-bold text-zinc-900 leading-tight">Our Locations</h2>
            <p class="text-sm text-zinc-500 mt-2">From local hubs to a continental presence.</p>
        </div>

        <div
            class="grid grid-cols-1 @sm/locations:grid-cols-2 @3xl/locations:grid-cols-3 @5xl/locations:grid-cols-4 gap-4">

            @php
                $locations = [
                    [
                        'city' => 'Nairobi',
                        'flag' => 'images/kenya-flag.png',
                        'flag_alt' => 'Kenya flag',
                        'image' => 'images/showrooms/SHEFFIELD SHOWROOM.png',
                        'address' => 'Off Old Mombasa Road before the Nairobi SGR Terminus',
                        'phone' => '+254 713 444 000',
                        'tel' => '+254713444000',
                        'email' => 'info@sheffieldafrica.com',
                    ],
                    [
                        'city' => 'Mombasa',
                        'flag' => 'images/kenya-flag.png',
                        'flag_alt' => 'Kenya flag',
                        'image' => 'images/showrooms/MOMBASA SHOWROOM-01.jpg',
                        'address' => 'Petrocity Complex 1st Floor — Off Links Road, Nyali, Mombasa',
                        'phone' => '+254 713 317 214',
                        'tel' => '+254713317214',
                        'email' => 'mombasa@sheffieldafrica.com',
                    ],
                    [
                        'city' => 'Kampala',
                        'flag' => 'images/uganda.png',
                        'flag_alt' => 'Uganda flag',
                        'image' => 'images/showrooms/IMAGES SHOWROOMS-01.jpg',
                        'address' => 'Bugolobi Hardware City, Block 3 Room 102, Mulwana Road',
                        'phone' => '+256 741 177 712',
                        'tel' => '+256741177712',
                        'email' => 'uganda@sheffieldafrica.com',
                    ],
                    [
                        'city' => 'Kigali',
                        'flag' => 'images/rwanda.png',
                        'flag_alt' => 'Rwanda flag',
                        'image' => 'images/showrooms/IMAGES SHOWROOMS-02.jpg',
                        'address' => 'Kicukiro Street, KK 500 ST Kigali, Rwanda',
                        'phone' => '+250 794 007 302',
                        'tel' => '+250794007302',
                        'email' => 'rwanda@sheffieldafrica.com',
                    ],
                ];
            @endphp

            @foreach ($locations as $location)
                <div
                    class="group/card bg-white rounded-md overflow-hidden
                    ring-1 ring-zinc-200/80
                    hover:ring-zinc-300 hover:shadow-md hover:shadow-zinc-200/60
                    transition-all duration-200">

                    {{-- Image --}}
                    <div class="w-full h-60 overflow-hidden">
                        <img src="{{ asset($location['image']) }}" alt="{{ $location['city'] }} showroom"
                            loading="lazy"
                            class="w-full h-full object-cover object-center transition-transform duration-300 group-hover/card:scale-105">
                    </div>

                    {{-- Content --}}
                    <div class="p-5">

                        {{-- City + Flag --}}
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <h3 class="text-lg font-bold text-zinc-900">{{ $location['city'] }}</h3>
                            <img src="{{ asset($location['flag']) }}" alt="{{ $location['flag_alt'] }}"
                                class="size-6 rounded-full object-cover ring-1 ring-zinc-200">
                        </div>

                        {{-- Contact Details --}}
                        <div class="space-y-2.5">

                            {{-- Address --}}
                            <div class="flex items-start gap-3">
                                <div
                                    class="shrink-0 mt-0.5 w-7 h-7 rounded-md bg-brand-secondary/10 text-brand-secondary ring-1 ring-brand-secondary/20 flex items-center justify-center">
                                    <flux:icon.map-pin class="w-3.5 h-3.5" />
                                </div>
                                <p class="text-sm text-zinc-500 leading-relaxed pt-1">{{ $location['address'] }}</p>
                            </div>

                            {{-- Phone --}}
                            <div class="flex items-center gap-3">
                                <div
                                    class="shrink-0 w-7 h-7 rounded-md bg-brand-secondary/10 text-brand-secondary ring-1 ring-brand-secondary/20 flex items-center justify-center">
                                    <flux:icon.phone class="w-3.5 h-3.5" />
                                </div>
                                <a href="tel:{{ $location['tel'] }}"
                                    class="text-sm text-zinc-600 hover:text-brand-primary transition-colors duration-150 font-medium">
                                    {{ $location['phone'] }}
                                </a>
                            </div>

                            {{-- Email --}}
                            <div class="flex items-center gap-3">
                                <div
                                    class="shrink-0 w-7 h-7 rounded-md bg-brand-secondary/10 text-brand-secondary ring-1 ring-brand-secondary/20 flex items-center justify-center">
                                    <flux:icon.envelope class="w-3.5 h-3.5" />
                                </div>
                                <a href="mailto:{{ $location['email'] }}"
                                    class="text-sm text-zinc-600 hover:text-brand-primary transition-colors duration-150 font-medium break-all">
                                    {{ $location['email'] }}
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
