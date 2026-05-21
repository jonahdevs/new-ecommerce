<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use App\Models\{Category, Product};
use App\Enums\CategorySection;
use Illuminate\Support\Facades\Cache;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;

new #[Layout('layouts.guest')] class extends Component {
    // Cache TTLs (centralised so they're easy to tune)
    const TTL_CATEGORIES = 60 * 60 * 6; // 6 hours - categories don't change often
    const TTL_NEW_ARRIVALS = 60 * 60 * 2; // 2 hours - new arrivals change more frequently
    const TTL_PRODUCTS = 60 * 60 * 3; // 3 hours - sales counts shift slowly

    #[Computed]
    public function heroBanners()
    {
        return config('site.hero_slides');
    }

    public function mount(): void
    {
        // SEO Meta Tags
        SEOMeta::setTitle('Commercial Kitchen Equipment Supplier in East Africa');
        SEOMeta::setDescription('Leading supplier of commercial kitchen equipment in Kenya, Uganda & Rwanda. Restaurant equipment, bakery machines, refrigeration solutions, and professional kitchen supplies.');
        SEOMeta::addKeyword(['commercial kitchen equipment', 'restaurant equipment Kenya', 'bakery equipment East Africa', 'refrigeration solutions', 'kitchen supplies', 'Sheffield Africa', 'commercial kitchen Uganda', 'restaurant equipment Rwanda']);
        SEOMeta::setCanonical(route('home'));

        // OpenGraph
        OpenGraph::setTitle('Sheffield Africa - Commercial Kitchen Equipment Supplier');
        OpenGraph::setDescription('Leading supplier of commercial kitchen equipment in East Africa. Quality restaurant equipment, bakery machines, and refrigeration solutions.');
        OpenGraph::setUrl(route('home'));
        OpenGraph::setType('website');
        OpenGraph::addImage(asset('images/og-home.jpg'));

        // Twitter Card
        TwitterCard::setType('summary_large_image');
        TwitterCard::setTitle('Sheffield Africa - Commercial Kitchen Equipment');
        TwitterCard::setDescription('Leading supplier of commercial kitchen equipment in East Africa');
        TwitterCard::setImage(asset('images/og-home.jpg'));

        // JSON-LD Organization Schema
        JsonLd::setType('Organization');
        JsonLd::setTitle(config('app.name'));
        JsonLd::setDescription('Leading supplier of commercial kitchen equipment in East Africa');
        JsonLd::setUrl(config('app.url'));
        JsonLd::addValue('logo', asset('images/logo.png'));
        JsonLd::addValue('contactPoint', [
            [
                '@type' => 'ContactPoint',
                'telephone' => '+254-713-444-000',
                'contactType' => 'customer service',
                'areaServed' => ['KE', 'UG', 'RW'],
                'availableLanguage' => ['English', 'Swahili'],
            ],
        ]);
        JsonLd::addValue('address', [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Off Old Mombasa Road before the Nairobi SGR Terminus',
            'addressLocality' => 'Nairobi',
            'addressCountry' => 'KE',
        ]);
    }

    // Top categories
    // Tagged so flushing 'categories' busts this automatically via the observer.

    #[Computed(persist: true)]
    public function topCategories()
    {
        return Cache::tags(['homepage', 'categories'])->remember('homepage:top-categories', self::TTL_CATEGORIES, function () {
            return Category::inSection(CategorySection::HOME_PAGE_FEATURED)
                ->active()
                ->get(['id', 'name', 'slug', 'image_path']);
        });
    }

    #[Computed]
    public function newArrivals()
    {
        return Cache::tags(['homepage', 'products'])->remember('homepage:new-arrivals', self::TTL_NEW_ARRIVALS, function () {
            return Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description', 'type', 'requires_quotation', 'reviews_enabled', 'stock_status', 'manage_stock', 'stock_quantity', 'average_rating', 'reviews_count', 'created_at'])
                ->with([
                    'brand:id,name,slug',
                    'images' => fn($q) => $q->select(['id', 'product_id', 'image_path', 'alt_text', 'sort_order'])->limit(1),
                    'variants' => fn($q) => $q
                        ->where('is_active', true)
                        ->whereNotNull('price')
                        ->select(['id', 'product_id', 'price', 'sale_price', 'is_active']),
                    'tags' => fn($q) => $q->select(['id', 'name', 'order_column', 'color']),
                ])
                ->active()
                ->visibleInCatalog()
                ->newArrivals()
                ->latest()
                ->limit(20)
                ->get();
        });
    }

    #[Computed]
    public function products()
    {
        return Cache::tags(['homepage', 'products'])->remember('homepage:featured-products', self::TTL_PRODUCTS, function () {
            return Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description', 'type', 'requires_quotation', 'reviews_enabled', 'stock_status', 'manage_stock', 'stock_quantity', 'average_rating', 'reviews_count', 'sales_count', 'created_at'])
                ->with([
                    'brand:id,name,slug',
                    'images' => fn($q) => $q->select(['id', 'product_id', 'image_path', 'alt_text', 'sort_order'])->limit(1),
                    'variants' => fn($q) => $q
                        ->where('is_active', true)
                        ->whereNotNull('price')
                        ->select(['id', 'product_id', 'price', 'sale_price', 'is_active']),
                    'tags' => fn($q) => $q->select(['id', 'name', 'order_column', 'color']),
                ])
                ->active()
                ->visibleInCatalog()
                ->orderBy('sales_count', 'desc')
                ->limit(24)
                ->get();
        });
    }
};
?>

@push('head-scripts')
    <link rel="preload" as="image" href="/images/home/COFFEE-MACHINES.jpg">
@endpush

<div>
    {{-- Hero Background Wrapper --}}
    <div class="relative overflow-hidden">

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
            <div class="swiper opacity-0 transition-opacity duration-500 rounded-md overflow-hidden" id="heroSwiper">
                <div class="swiper-wrapper">
                    @foreach ($this->heroBanners as $i => $banner)
                        <div class="swiper-slide">
                            <img src="{{ $banner['image'] }}" alt="{{ $banner['alt'] }}" class="w-full h-auto"
                                @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
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
                            <circle cx="24" cy="24" r="18" fill="none" style="stroke: var(--primary)"
                                stroke-width="2.5" stroke-linecap="round" :stroke-dasharray="progressCircumference"
                                :stroke-dashoffset="progressOffset" class="transition-all duration-100 ease-linear" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg x-show="isPaused"
                                class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-primary ml-0.5 transition-transform group-hover:scale-110"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z" />
                            </svg>
                            <svg x-show="!isPaused"
                                class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-primary transition-transform group-hover:scale-110"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                            </svg>
                        </div>
                    </button>
                </div>

                {{-- Prev / Next controls --}}
                <button type="button" @click="swiper.slidePrev()"
                    class="absolute top-0 start-0 z-1 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none">
                    <span
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/30 dark:bg-zinc-800/30 hover:bg-white/50 dark:hover:bg-zinc-800/60 focus:ring-4 focus:ring-white dark:focus:ring-zinc-800/70 focus:outline-none">
                        <flux:icon.arrow-long-left class="size-4 text-white" />
                        <span class="sr-only">Previous</span>
                    </span>
                </button>
                <button type="button" @click="swiper.slideNext()"
                    class="absolute top-0 end-0 z-1 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none">
                    <span
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/30 dark:bg-zinc-800/30 hover:bg-white/50 dark:hover:bg-zinc-800/60 focus:ring-4 focus:ring-white dark:focus:ring-zinc-800/70 focus:outline-none">
                        <flux:icon.arrow-long-right class="size-4 text-white" />
                        <span class="sr-only">Next</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Feature strips  --}}
    <section class="border-y border-zinc-200 bg-white">
        <div class="container @container/features mx-auto px-4">
            <div class="grid grid-cols-2 @2xl/features:grid-cols-3 @5xl/features:grid-cols-5 divide-x divide-zinc-100">

                <div
                    class="flex flex-col items-center text-center p-4 @md/features:p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-primary">
                        <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <flux:heading size="sm" level="3" class="text-xs! font-semibold uppercase tracking-wider">
                        Africa No. 1</flux:heading>
                    <flux:text class="mt-1 text-xs! text-on-surface-variant leading-tight">In Kitchen Equipment
                    </flux:text>
                </div>

                <div
                    class="flex flex-col items-center text-center p-4 @md/features:p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-primary">
                        <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                        </svg>
                    </div>
                    <flux:heading size="sm" level="3" class="text-xs! font-semibold uppercase tracking-wider">
                        Guaranteed</flux:heading>
                    <flux:text class="mt-1 text-xs! text-on-surface-variant leading-tight">Quality Assurance</flux:text>
                </div>

                <div
                    class="flex flex-col items-center text-center p-4 @md/features:p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-primary">
                        <flux:icon.arrows-pointing-out class="size-8" />
                    </div>
                    <flux:heading size="sm" level="3" class="text-xs! font-semibold uppercase tracking-wider">
                        Customized</flux:heading>
                    <flux:text class="mt-1 text-xs! text-on-surface-variant leading-tight">Bespoke Solutions</flux:text>
                </div>

                <div
                    class="flex flex-col items-center text-center p-4 @md/features:p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-primary">
                        <svg class="size-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                        </svg>
                    </div>
                    <flux:heading size="sm" level="3"
                        class="text-xs! font-semibold uppercase tracking-wider">Fast Delivery</flux:heading>
                    <flux:text class="mt-1 text-xs! text-on-surface-variant leading-tight">Countrywide Shipping
                    </flux:text>
                </div>

                <div
                    class="hidden @5xl/features:flex flex-col items-center text-center p-4 @md/features:p-6 transition-colors hover:bg-zinc-50">
                    <div class="mb-3 text-primary">
                        <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M14.25 9.75L16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                        </svg>
                    </div>
                    <flux:heading size="sm" level="3"
                        class="text-xs! font-semibold uppercase tracking-wider">Installation</flux:heading>
                    <flux:text class="mt-1 text-xs! text-on-surface-variant leading-tight">Professional Setup
                    </flux:text>
                </div>

            </div>
        </div>
    </section>

    <div class="container @container/categories mx-auto px-4 mt-5 md:mt-7">
        <div class="pb-4 @md/categories:pb-6">
            <flux:heading size="lg" level="2"
                class="text-lg! @sm/categories:text-xl! @md/categories:text-2xl! @2xl/categories:text-3xl! font-serif font-semibold">
                Top Categories
            </flux:heading>
        </div>
        @island('top-categories')
            @php
                // Container-driven category grid:
                //  base (<20rem)  1 col  — minimum-width phones
                //  @xs  (20rem+)  2 cols — phone portrait
                //  @md  (28rem+)  3 cols — large phones
                //  @xl  (36rem+)  4 cols — tablets
                //  @3xl (48rem+)  5 cols — small laptops
                //  @5xl (64rem+)  6 cols — desktops
                //  @7xl (80rem+)  7 cols — wide desktops
                $catGrid =
                    'grid-cols-1 @xs/categories:grid-cols-2 @md/categories:grid-cols-3 @xl/categories:grid-cols-4 @3xl/categories:grid-cols-5 @5xl/categories:grid-cols-6 @7xl/categories:grid-cols-7';
            @endphp

            @placeholder
                <div class="py-3 pb-5 grid {{ $catGrid }} gap-3">
                    @for ($i = 0; $i < 14; $i++)
                        <div class="animate-pulse">
                            <div class="w-full aspect-4/3 bg-zinc-200 rounded-md"></div>
                            <div class="w-3/4 h-3 sm:h-4 mt-2 bg-zinc-200 mx-auto rounded"></div>
                        </div>
                    @endfor
                </div>
            @endplaceholder

            <div class="grid {{ $catGrid }} gap-x-3 gap-y-6 @md/categories:gap-x-5 @md/categories:gap-y-10">
                @foreach ($this->topCategories as $category)
                    <div class="group relative" :key="'category-' . $category->id">
                        <a href="{{ route('shop.category', ['category' => $category->slug]) }}" wire:navigate
                            class="block">
                            <div @class([
                                'relative aspect-4/3 overflow-hidden rounded-md bg-zinc-50',
                                'border border-zinc-200' => !$category->image_url,
                            ])>
                                @if ($category->image_url)
                                    <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy"
                                        class="object-cover w-full h-full">
                                @else
                                    <div class="flex items-center justify-center h-full">
                                        <flux:icon.photo class="text-zinc-300 h-10 w-10 stroke-1" />
                                    </div>
                                @endif
                            </div>

                            <p class="mt-5 text-center text-sm font-semibold">
                                {{ $category->name }}
                            </p>
                        </a>
                    </div>
                @endforeach
            </div>
        @endisland
    </div>

    <section class="container mx-auto px-4 mt-5 md:mt-7">
        <img src="{{ asset('images/home/THIN BANNER.webp') }}" alt="banner" class="w-full h-auto" loading="lazy">
    </section>

    {{-- New Arrivals --}}
    <div class="container @container/newarrivals mx-auto px-0 sm:px-4 mt-5 md:mt-7">
        <div
            class="bg-primary max-sm:rounded-none rounded-md overflow-hidden grid grid-cols-1 @4xl/newarrivals:grid-cols-6">
            {{-- Left Panel --}}
            <div
                class="@4xl/newarrivals:col-span-1 flex flex-col justify-center px-5 md:px-6 py-6 @4xl/newarrivals:py-8
                border-b border-white/10 @4xl/newarrivals:border-b-0 @4xl/newarrivals:border-r @4xl/newarrivals:border-white/10">
                <flux:text class="text-white/60 text-xs! font-semibold uppercase tracking-widest mb-2">
                    Just In
                </flux:text>
                <flux:heading size="xl" level="4"
                    class="text-2xl! sm:text-3xl! lg:text-4xl! text-white! leading-none mb-3 font-serif">New
                </flux:heading>
                <flux:text class="text-white/75 text-xs! sm:text-sm! md:text-base! leading-relaxed mb-5">
                    Discover what's just dropped
                </flux:text>
                <a href="{{ route('shop.index', ['sort' => 'newest']) }}" wire:navigate
                    class="inline-flex items-center gap-1.5 w-fit text-[10px] sm:text-xs md:text-sm font-semibold
                        text-white border border-white/30 hover:border-white hover:bg-white/10
                        px-3 sm:px-4 py-1.5 sm:py-2 rounded-full transition-all duration-200 group focus:outline-none">
                    View All
                    <flux:icon.arrow-right
                        class="size-3 sm:size-3.5 transition-transform duration-200 group-hover:translate-x-1" />
                </a>
            </div>

            {{-- Products --}}
            @island('new-arrivals', defer: true)
                @placeholder
                    <section class="@4xl/newarrivals:col-span-5 px-4 md:px-5 py-5">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            @for ($i = 0; $i < 5; $i++)
                                <x-product-card-placeholder />
                            @endfor
                        </div>
                    </section>
                @endplaceholder


                <section class="@4xl/newarrivals:col-span-5 px-4 md:px-5 py-5">
                    <div class="relative" x-data="{
                        swiper: null,
                        init() {
                            this.swiper = new Swiper('#newArrivalsSwiper', {
                                spaceBetween: 12,
                                loop: true,
                                speed: 400,
                                // Allow clicks on links inside slides
                                preventClicks: false,
                                preventClicksPropagation: false,
                                touchStartPreventDefault: false,
                                breakpoints: {
                                    375: { slidesPerView: 2 },
                                    640: { slidesPerView: 3 },
                                    768: { slidesPerView: 4 },
                                    1024: { slidesPerView: 5 },
                                },
                            });
                        }
                    }">
                        <div class="swiper" id="newArrivalsSwiper">
                            <div class="swiper-wrapper pb-1">
                                @foreach ($this->newArrivals as $product)
                                    <div class="swiper-slide h-auto!" :key="'product-' . $product->id">
                                        <livewire:product-card :product="$product" />
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Nav Buttons — inside container, no overflow bleed --}}
                        <button type="button" @click="swiper?.slidePrev()"
                            class="absolute top-1/2 left-1 -translate-y-1/2 z-1 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                            <flux:icon.chevron-left class="size-3.5 text-white" />
                            <span class="sr-only">Previous</span>
                        </button>

                        <button type="button" @click="swiper?.slideNext()"
                            class="absolute top-1/2 right-1 -translate-y-1/2 z-1 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                            <flux:icon.chevron-right class="size-3.5 text-white" />
                            <span class="sr-only">Next</span>
                        </button>
                    </div>
                </section>
            @endisland
        </div>
    </div>

    <div class="container @container/related mx-auto px-4 mt-5 md:mt-7">
        <div class="">
            <section class="flex items-center justify-between pb-4 ">
                <flux:heading size="lg" level="2"
                    class="text-base! @sm/related:text-lg! @md/related:text-xl! @2xl/related:text-2xl! @4xl/related:text-3xl! font-serif! font-semibold!">
                    You May Also Like
                </flux:heading>

                <flux:link :href="route('shop.index')" wire:navigate
                    class="text-xs @sm/related:text-sm no-underline! hover:underline">
                    View all
                </flux:link>
            </section>

            @island(name: 'products', defer: true)
                @placeholder
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 pb-6">
                        @for ($i = 0; $i < 12; $i++)
                            <x-product-card-placeholder />
                        @endfor
                    </div>
                @endplaceholder

                <section>
                    <!-- Products slider -->
                    <div class="relative" x-data="{
                        swiper: null,
                        init() {
                            if (this.swiper) {
                                this.swiper.destroy(true, true);
                            }
                    
                            this.swiper = new Swiper('#youMayAlsoLike', {
                                slidesPerView: 2,
                                slidesPerGroup: 1,
                                spaceBetween: 12,
                                speed: 600,
                                // Allow clicks on links inside slides
                                preventClicks: false,
                                preventClicksPropagation: false,
                                touchStartPreventDefault: false,
                                breakpoints: {
                                    375: {
                                        slidesPerView: 2,
                                        slidesPerGroup: 2,
                                        grid: { rows: 2, fill: 'row' }
                                    },
                                    480: {
                                        slidesPerView: 2,
                                        slidesPerGroup: 2,
                                        grid: { rows: 2, fill: 'row' }
                                    },
                                    640: {
                                        slidesPerView: 3,
                                        slidesPerGroup: 3,
                                        grid: { rows: 2, fill: 'row' }
                                    },
                                    768: {
                                        slidesPerView: 4,
                                        slidesPerGroup: 4,
                                        grid: { rows: 2, fill: 'row' }
                                    },
                                    1024: {
                                        slidesPerView: 5,
                                        slidesPerGroup: 5,
                                        grid: { rows: 2, fill: 'row' }
                                    },
                                    1280: {
                                        slidesPerView: 6,
                                        slidesPerGroup: 6,
                                        grid: { rows: 2, fill: 'row' }
                                    },
                                },
                            });
                            this.$nextTick(() => {
                                document.getElementById('youMayAlsoLike').classList.remove('opacity-0')
                            })
                        }
                    }">
                        <div class="swiper opacity-0 transition-opacity duration-500" id="youMayAlsoLike">
                            <div class="swiper-wrapper pb-5">
                                @foreach ($this->products as $product)
                                    <div class="swiper-slide h-auto!" :key="'product-' . $product->id">
                                        <livewire:product-card :product="$product" />
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Nav Buttons — inside container, no overflow bleed --}}
                        <button type="button" @click="swiper?.slidePrev()"
                            class="absolute top-1/2 left-0 -translate-y-1/2 -translate-x-1/2 z-1 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                            <flux:icon.chevron-left class="size-3.5 text-white" />
                            <span class="sr-only">Previous</span>
                        </button>

                        <button type="button" @click="swiper?.slideNext()"
                            class="absolute top-1/2 right-0 -translate-y-1/2  translate-x-1/2  z-1 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                            <flux:icon.chevron-right class="size-3.5 text-white" />
                            <span class="sr-only">Next</span>
                        </button>
                    </div>
                </section>
            @endisland
        </div>
    </div>

    {{-- Locations Section --}}
    <section class="container @container/locations mx-auto px-4 mt-5 md:mt-7 mb-12">

        <div class="pb-4 @md/locations:pb-6">
            <flux:heading size="xl" level="2"
                class="text-lg! @sm/locations:text-xl! @md/locations:text-2xl! @3xl/locations:text-3xl! font-serif! font-semibold!">
                Our
                Locations
            </flux:heading>
            <flux:subheading class="text-xs! @sm/locations:text-sm! @md/locations:text-base! mt-2">From local hubs to a
                continental
                presence.
            </flux:subheading>
        </div>

        <div
            class="grid grid-cols-1 @sm/locations:grid-cols-2 @3xl/locations:grid-cols-3 @5xl/locations:grid-cols-4 gap-4">

            @php
                $locations = [
                    [
                        'city' => 'Nairobi',
                        'flag' => 'images/kenya-flag.png',
                        'flag_alt' => 'Kenya flag',
                        'image' => 'images/showrooms/SHEFFIELD SHOWROOM.webp',
                        'address' => 'Off Old Mombasa Road before the Nairobi SGR Terminus',
                        'phone' => '+254 713 444 000',
                        'tel' => '+254713444000',
                        'email' => 'info@sheffieldafrica.com',
                    ],
                    [
                        'city' => 'Mombasa',
                        'flag' => 'images/kenya-flag.png',
                        'flag_alt' => 'Kenya flag',
                        'image' => 'images/showrooms/MOMBASA SHOWROOM.webp',
                        'address' => 'Petrocity Complex 1st Floor — Off Links Road, Nyali, Mombasa',
                        'phone' => '+254 713 317 214',
                        'tel' => '+254713317214',
                        'email' => 'mombasa@sheffieldafrica.com',
                    ],
                    [
                        'city' => 'Kampala',
                        'flag' => 'images/uganda.png',
                        'flag_alt' => 'Uganda flag',
                        'image' => 'images/showrooms/KAMPALA SHOWROOM.webp',
                        'address' => 'Bugolobi Hardware City, Block 3 Room 102, Mulwana Road',
                        'phone' => '+256 741 177 712',
                        'tel' => '+256741177712',
                        'email' => 'uganda@sheffieldafrica.com',
                    ],
                    [
                        'city' => 'Kigali',
                        'flag' => 'images/rwanda.png',
                        'flag_alt' => 'Rwanda flag',
                        'image' => 'images/showrooms/KIGALI SHOWROOM.webp',
                        'address' => 'Kicukiro Street, KK 500 ST Kigali, Rwanda',
                        'phone' => '+250 794 007 302',
                        'tel' => '+250794007302',
                        'email' => 'rwanda@sheffieldafrica.com',
                    ],
                ];
            @endphp

            @foreach ($locations as $location)
                <div class="group/card bg-white rounded-md overflow-hidden ring-1 ring-zinc-200/80">

                    {{-- Image --}}
                    <div class="w-full h-48 @md/locations:h-60 overflow-hidden">
                        <img src="{{ asset($location['image']) }}" alt="{{ $location['city'] }} showroom"
                            loading="lazy" class="w-full h-full object-cover object-center">
                    </div>

                    {{-- Content --}}
                    <div class="p-4 @md/locations:p-5">

                        {{-- City + Flag --}}
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <flux:heading size="base" level="3"
                                class="text-base! @sm/locations:text-lg! @md/locations:text-xl! font-serif!">
                                {{ $location['city'] }}
                            </flux:heading>
                            <img src="{{ asset($location['flag']) }}" alt="{{ $location['flag_alt'] }}"
                                class="size-5 @sm/locations:size-6 rounded-full object-cover">
                        </div>

                        {{-- Contact Details --}}
                        <div class="space-y-2.5">

                            {{-- Address --}}
                            <div class="flex items-start gap-3">
                                <div
                                    class="shrink-0 mt-0.5 w-6 h-6 @sm/locations:w-7 @sm/locations:h-7 rounded-md bg-secondary/10 text-secondary ring-1 ring-secondary/20 flex items-center justify-center">
                                    <flux:icon.map-pin class="w-3 h-3 @sm/locations:w-3.5 @sm/locations:h-3.5" />
                                </div>
                                <flux:text
                                    class="text-xs! @sm/locations:text-sm! text-on-surface-variant leading-relaxed pt-1">
                                    {{ $location['address'] }}</flux:text>
                            </div>

                            {{-- Phone --}}
                            <div class="flex items-center gap-3">
                                <div
                                    class="shrink-0 w-6 h-6 @sm/locations:w-7 @sm/locations:h-7 rounded-md bg-secondary/10 text-secondary ring-1 ring-secondary/20 flex items-center justify-center">
                                    <flux:icon.phone class="w-3 h-3 @sm/locations:w-3.5 @sm/locations:h-3.5" />
                                </div>
                                <flux:link href="tel:{{ $location['tel'] }}"
                                    class="text-xs! @sm/locations:text-sm! text-on-surface-variant hover:text-primary transition-colors duration-150 font-normal! no-underline!">
                                    {{ $location['phone'] }}
                                </flux:link>
                            </div>

                            {{-- Email --}}
                            <div class="flex items-center gap-3">
                                <div
                                    class="shrink-0 w-6 h-6 @sm/locations:w-7 @sm/locations:h-7 rounded-md bg-secondary/10 text-secondary ring-1 ring-secondary/20 flex items-center justify-center">
                                    <flux:icon.envelope class="w-3 h-3 @sm/locations:w-3.5 @sm/locations:h-3.5" />
                                </div>
                                <flux:link href="mailto:{{ $location['email'] }}"
                                    class="text-xs! @sm/locations:text-sm! text-on-surface-variant hover:text-primary transition-colors duration-150 break-all font-normal! no-underline!">
                                    {{ $location['email'] }}
                                </flux:link>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
