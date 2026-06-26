<?php

use App\Enums\CategorySection;
use App\Enums\CategoryStatus;
use App\Enums\StockStatus;
use App\Livewire\Concerns\InteractsWithStorefront;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryPlacement;
use App\Models\Product;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Commercial Kitchen, Cold Room, Laundry & Healthcare Equipment')] class extends Component {
    use InteractsWithStorefront;

    /**
     * A product counts as a "new arrival" if it was published within this many
     * days. Products carrying the "New Arrival" tag are pinned regardless of age.
     */
    private const NEW_ARRIVAL_WINDOW_DAYS = 60;

    /** @var array<int, int> Locked so random order is fixed for the lifetime of the component. */
    public array $featuredProductIds = [];

    public function mount(): void
    {
        $description = 'Sheffield Africa — East Africa\'s leading supplier of commercial kitchen, cold room, laundry and healthcare equipment since 2003. Expert consultation, installation, service and spares across Kenya, Uganda and Rwanda.';

        SEOMeta::setDescription($description);
        OpenGraph::setDescription($description)->setType('website');
        TwitterCard::setDescription($description);
        JsonLdMulti::setDescription($description)->setType('Organization');

        $this->featuredProductIds = Product::query()->visibleInCatalog()->published()->where('stock_status', StockStatus::IN_STOCK)->whereNotNull('price')->where('price', '>', 0)->inRandomOrder()->take(6)->pluck('id')->toArray();
    }

    /**
     * The four fixed Sheffield divisions, in display order. Locked to these
     * slugs so the "Shop by department" band never picks up any other
     * top-level category.
     *
     * @var array<int, string>
     */
    private const DIVISION_SLUGS = ['commercial-kitchen', 'cold-room', 'laundry', 'healthcare'];

    /**
     * The four divisions (Commercial Kitchen, Cold Room, Laundry, Healthcare).
     * Children are eager-loaded so each card can render a 2×2 collage; staff
     * re-parent product categories under a division and the collage fills in
     * automatically.
     */
    #[Computed]
    public function divisions(): Collection
    {
        return Category::query()
            ->whereIn('slug', self::DIVISION_SLUGS)
            ->where('status', CategoryStatus::ACTIVE)
            ->with(['media', 'children' => fn($q) => $q->where('status', CategoryStatus::ACTIVE)->orderBy('sort_order')->with('media')])
            ->get()
            ->sortBy(fn(Category $c) => array_search($c->slug, self::DIVISION_SLUGS))
            ->values();
    }

    /**
     * Up to four image-backed products drawn from a division — its own products
     * plus everything in its subcategories — to fill the home card collage.
     */
    public function collageProducts(Category $division): Collection
    {
        $categoryIds = $division->children->pluck('id')->push($division->id)->all();

        return Product::query()->visibleInCatalog()->published()->whereHas('media')->where(fn($q) => $q->whereIn('primary_category_id', $categoryIds)->orWhereHas('categories', fn($c) => $c->whereIn('categories.id', $categoryIds)))->with('media')->take(4)->get();
    }

    // TODO: cache these once they become hot. View composer would be cleaner.
    #[Computed]
    public function featuredCategories(): Collection
    {
        return CategoryPlacement::query()
            ->with(['category' => fn($q) => $q->withCount('products')])
            ->where('location', CategorySection::HOME_PAGE_FEATURED)
            ->where('status', CategoryStatus::ACTIVE)
            ->orderBy('sort_order')
            ->take(14)
            ->get()
            ->pluck('category')
            ->filter();
    }

    #[Computed]
    public function featuredProducts(): Collection
    {
        // Curated: products staff have tagged "Featured", ordered by sort_order.
        $featured = Product::query()
            ->with(['brand:id,name', 'taxClass:id,rate,is_inclusive', 'media'])
            ->visibleInCatalog()
            ->published()
            ->where('stock_status', StockStatus::IN_STOCK)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->whereHas('tags', fn($t) => $t->where('name->' . config('app.locale', 'en'), 'Featured'))
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        if ($featured->isNotEmpty()) {
            return $featured;
        }

        // Fallback: nothing curated yet — show the locked random pool from mount().
        return Product::query()
            ->with(['brand:id,name', 'taxClass:id,rate,is_inclusive', 'media'])
            ->whereIn('id', $this->featuredProductIds)
            ->get()
            ->sortBy(fn($p) => array_search($p->id, $this->featuredProductIds))
            ->values();
    }

    #[Computed]
    public function newArrivals(): Collection
    {
        $base = Product::query()
            ->with(['brand:id,name', 'taxClass:id,rate,is_inclusive', 'media'])
            ->visibleInCatalog()
            ->published()
            ->where('stock_status', StockStatus::IN_STOCK)
            ->whereNotNull('price')
            ->where('price', '>', 0);

        // Engine: published within the window, OR manually pinned with the
        // "New Arrival" tag (overrides the age cut-off).
        $arrivals = (clone $base)->where(fn($q) => $q->where('published_at', '>=', now()->subDays(self::NEW_ARRIVAL_WINDOW_DAYS))->orWhereHas('tags', fn($t) => $t->where('name->' . config('app.locale', 'en'), 'New Arrival')))->latest('published_at')->take(12)->get();

        // Fallback: nothing qualifies (new or slow catalog) — show the latest anyway.
        return $arrivals->isNotEmpty() ? $arrivals : (clone $base)->latest('published_at')->take(12)->get();
    }

    #[Computed]
    public function brands(): Collection
    {
        return Brand::query()->where('is_active', true)->orderBy('sort_order')->take(16)->get();
    }
}; ?>

@php
    // Hero rotator slides — copy maps to a stable serialisable form for Alpine.
    //
    // Extra keys support the three hero designs below (only one is active at a time):
    //   'src'         desktop wide banner (text baked in)            — Options B & C
    //   'src_mobile'  taller mobile banner (text baked in)           — Option B (designer to supply)
    //   'headline'    live HTML headline over a plain background     — Option A
    //   'sub'         live HTML subtext                              — Option A
    //   'src_plain'   background photo WITHOUT baked-in text         — Option A (designer to supply)
    $heroSlides = [
        [
            'src' => '/images/banners/kitchen-equipment-banner.webp',
            'src_mobile' => '/images/banners/mobile/kitchen-equipment-banner.webp',
            'src_plain' => '/images/banners/plain/kitchen-equipment-banner.webp',
            'alt' => 'Fully equip your commercial kitchen',
            'headline' => 'Your business, fully equipped',
            'sub' => 'Outfit your entire kitchen from one trusted supplier — serving Africa since 2003.',
            'cta' => 'Shop all equipment',
            'align' => 'right',
            'url' => route('catalog'),
        ],
        [
            'src' => '/images/banners/ovens.webp',
            'src_mobile' => '/images/banners/mobile/ovens.webp',
            'src_plain' => '/images/banners/plain/ovens.webp',
            'alt' => 'Commercial ovens',
            'headline' => 'Bake. Roast. Repeat.',
            'sub' => 'Commercial ovens engineered to perform shift after shift.',
            'cta' => 'Shop ovens',
            'align' => 'right',
            'url' => route('catalog'),
        ],
        [
            'src' => '/images/banners/fryers.webp',
            'src_mobile' => '/images/banners/mobile/fryers.webp',
            'src_plain' => '/images/banners/plain/fryers.webp',
            'alt' => 'Commercial fryers',
            'headline' => 'Crispy, consistent, every time',
            'sub' => 'Heavy-duty fryers that keep pace with the lunch rush.',
            'cta' => 'Shop fryers',
            'align' => 'right',
            'url' => route('catalog'),
        ],
        [
            'src' => '/images/banners/prep-like-a-pro.webp',
            'src_mobile' => '/images/banners/mobile/prep-like-a-pro.webp',
            'src_plain' => '/images/banners/plain/prep-like-a-pro.webp',
            'alt' => 'Food preparation equipment',
            'headline' => 'Prep like a pro',
            'sub' => 'Slash prep time with pro-grade processors and slicers.',
            'cta' => 'Shop prep equipment',
            'align' => 'left',
            'url' => route('catalog'),
        ],
        [
            'src' => '/images/banners/meat-processors.webp',
            'src_mobile' => '/images/banners/mobile/meat-processors.webp',
            'src_plain' => '/images/banners/plain/meat-processors.webp',
            'alt' => 'Meat processing equipment',
            'headline' => 'Power through the butchery',
            'sub' => 'Mincers, slicers and bowl cutters built for serious volume.',
            'cta' => 'Shop meat processors',
            'align' => 'right',
            'url' => route('catalog'),
        ],
        [
            'src' => '/images/banners/clearance-sale.webp',
            'src_mobile' => '/images/banners/mobile/clearance-sale.webp',
            'src_plain' => '/images/banners/plain/clearance-sale.webp',
            'alt' => 'Limited time clearance sale',
            'headline' => 'Up to 20% off — while stocks last',
            'sub' => 'Limited-time prices on selected commercial equipment.',
            'cta' => 'Shop the sale',
            'align' => 'left',
            'url' => route('catalog', ['tag' => 'On Sale']),
        ],

        // ── Extra banners (uncomment any to add to the rotation) ──────────────
        /*
        [
            'src' => '/images/banners/fast-food.webp',
            'src_mobile' => '/images/banners/mobile/fast-food.webp',
            'src_plain' => '/images/banners/plain/fast-food.webp',
            'alt' => 'Fast food equipment',
            'headline' => 'Built for the rush',
            'sub' => 'Everything your quick-service kitchen needs to move fast.',
            'cta' => 'Shop fast food',
            'align' => 'right',
            'url' => route('catalog'),
        ],
        [
            'src' => '/images/banners/chafing-dishes.webp',
            'src_mobile' => '/images/banners/mobile/chafing-dishes.webp',
            'src_plain' => '/images/banners/plain/chafing-dishes.webp',
            'alt' => 'Chafing dishes and buffet servery',
            'headline' => 'Serve it hot, keep it elegant',
            'sub' => 'Premium chafing dishes and servery for flawless buffets.',
            'cta' => 'Shop servery',
            'align' => 'right',
            'url' => route('catalog'),
        ],
        [
            'src' => '/images/banners/coffee-machines.webp',
            'src_mobile' => '/images/banners/mobile/coffee-machines.webp',
            'src_plain' => '/images/banners/plain/coffee-machines.webp',
            'alt' => 'Premium coffee machines',
            'headline' => 'Pour the perfect cup',
            'sub' => 'Espresso, filter and bean-to-cup machines for every venue.',
            'cta' => 'Shop coffee machines',
            'align' => 'right',
            'url' => route('category.show', 'coffee-machines'),
        ],
        [
            'src' => '/images/banners/refrigeration.webp',
            'src_mobile' => '/images/banners/mobile/refrigeration.webp',
            'src_plain' => '/images/banners/plain/refrigeration.webp',
            'alt' => 'Smart cooling — refrigeration solutions',
            'headline' => 'Keep it cold, keep it fresh',
            'sub' => 'Reliable refrigeration that protects your stock around the clock.',
            'cta' => 'Shop refrigeration',
            'align' => 'right',
            'url' => route('category.show', 'refrigeration'),
        ],
        [
            'src' => '/images/banners/bakery-prep.webp',
            'src_mobile' => '/images/banners/mobile/bakery-prep.webp',
            'src_plain' => '/images/banners/plain/bakery-prep.webp',
            'alt' => 'Bakery preparation equipment',
            'headline' => 'Rise to the occasion',
            'sub' => 'Mixers, ovens and prep tools for serious baking.',
            'cta' => 'Shop bakery prep',
            'align' => 'center',
            'url' => route('category.show', 'bakery-preparation'),
        ],
        */
    ];

    $usps = [
        ['icon' => 'building-office-2', 'title' => 'Africa No. 1', 'sub' => 'In Commercial Equipment'],
        ['icon' => 'check-circle', 'title' => 'Guaranteed', 'sub' => 'Quality Assurance'],
        ['icon' => 'arrows-pointing-out', 'title' => 'Customized', 'sub' => 'Bespoke Solutions'],
        ['icon' => 'truck', 'title' => 'Fast Delivery', 'sub' => 'Countrywide Shipping'],
        ['icon' => 'code-bracket', 'title' => 'Installation', 'sub' => 'Professional Setup'],
    ];
@endphp

<div class="page-fade">
    {{-- Thin promo banner --}}
    <section class="bg-surface-sunken pt-3 pb-2">
        <div class="shell">
            <a href="#" wire:navigate aria-label="Up to 20% off mega sale" class="block overflow-hidden rounded-md"
                style="aspect-ratio: 3117 / 400">
                <img src="/images/banners/thin-banner.webp" alt="" class="size-full object-cover"
                    fetchpriority="high" decoding="async" draggable="false" />
            </a>
        </div>
    </section>

    {{-- Hero rotator --}}
    <section class="border-b border-zinc-200 bg-surface-sunken">
        <div class="shell py-3 md:py-5">
            {{-- Positioning wrapper — arrows sit just outside the image on desktop --}}
            {{-- wire:ignore keeps Livewire morphing from tearing down the
                 Swiper-initialised DOM when the component re-renders. --}}
            <div wire:ignore x-data="{
                swiper: null,
                current: 1,
                paused: false,
                init() {
                    this.swiper = new Swiper($refs.swiperEl, {
                        effect: 'fade',
                        fadeEffect: { crossFade: true },
                        loop: true,
                        speed: 700,
                        autoplay: {
                            delay: 6500,
                            pauseOnMouseEnter: true,
                            disableOnInteraction: false,
                        },
                        on: {
                            autoplayPause: () => { this.paused = true },
                            autoplayResume: () => { this.paused = false },
                            realIndexChange: (s) => { this.current = s.realIndex + 1 },
                        },
                    });
                },
            }">
                {{-- ── DESIGNER SPECS ──────────────────────────────────────────────
                     Desktop (≥768px):  2181 × 624 px  (~3.5:1) wide art
                     Mobile plain bg:   background photo WITHOUT baked-in text.
                         Desktop 2400 × 800 px (3:1), mobile 1080 × 1200 px (9:10).
                         Place the product on one side; leave opposite ~45% calm for
                         the overlaid headline. Export → /public/images/banners/plain/
                     ──────────────────────────────────────────────────────────────── --}}
                <div class="swiper group relative overflow-hidden rounded-md aspect-4/3 md:aspect-[2181/624]"
                    x-ref="swiperEl">
                    <div class="swiper-wrapper">
                        @foreach ($heroSlides as $i => $slide)
                            <div class="swiper-slide">
                                <a href="{{ $slide['url'] }}" wire:navigate aria-label="{{ $slide['alt'] }}"
                                    class="absolute inset-0 block cursor-pointer">

                                    {{-- Wide banner art with text baked in (src). Plain/mobile variants
                                         not yet supplied, so we use the existing baked-in banners. --}}
                                    <img src="{{ $slide['src'] }}" alt="{{ $slide['alt'] }}"
                                        class="block size-full object-cover"
                                        @if ($i === 0) fetchpriority="high" decoding="async" @else loading="lazy" decoding="async" @endif
                                        draggable="false" />

                                    {{-- Glassmorphism card (commented out — kept for easy switch-back)
                                    <div class="absolute inset-x-4 bottom-4 md:inset-x-auto md:bottom-auto md:top-1/2 md:left-10 md:max-w-sm md:-translate-y-1/2">
                                        <div class="rounded-xl border border-white/20 bg-white/10 p-5 shadow-xl backdrop-blur-md md:p-7">
                                            <h2 class="font-serif text-xl font-semibold leading-tight text-white md:text-[38px]">{{ $slide['headline'] }}</h2>
                                            <p class="mt-2 max-w-[30ch] text-xs text-white/80 md:mt-3 md:text-sm">{{ $slide['sub'] }}</p>
                                            <span aria-hidden class="mt-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-white px-4 py-2 text-xs font-semibold text-ink shadow-lg md:mt-4 md:text-sm">
                                                {{ $slide['cta'] }}
                                                <flux:icon.arrow-right variant="mini" class="size-3 md:size-3.5" />
                                            </span>
                                        </div>
                                    </div>
                                    --}}
                                </a>
                            </div>
                        @endforeach
                    </div>

                    {{-- Dots — Alpine-driven so positioning and pill styles are fully ours --}}
                    <div
                        class="absolute bottom-2 left-1/2 z-10 flex -translate-x-1/2 items-center gap-1.5 rounded-full bg-black/35 px-2 py-1 backdrop-blur-sm md:bottom-4 md:px-2.5 md:py-1.5">
                        @for ($i = 0; $i < count($heroSlides); $i++)
                            <button type="button" aria-label="Go to slide {{ $i + 1 }}"
                                @click="swiper?.slideToLoop({{ $i }})"
                                class="h-1.5 cursor-pointer rounded-full border-0 transition-all duration-200"
                                :class="current === {{ $i + 1 }} ? 'w-5 bg-white' : 'w-1.5 bg-white/55'">
                            </button>
                        @endfor
                    </div>

                    {{-- Slide counter --}}
                    <div
                        class="absolute top-2 right-2 z-10 flex items-center gap-1 rounded-full bg-black/35 px-2 py-0.5 text-[10px] tracking-wider text-white tabular-nums backdrop-blur-sm md:top-3.5 md:right-3.5 md:gap-1.5 md:px-2.5 md:py-1 md:text-[11px]">
                        <span class="font-semibold" x-text="String(current).padStart(2, '0')"></span>
                        <span class="opacity-60">/ {{ str_pad(count($heroSlides), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="opacity-70" x-show="paused" x-cloak>· paused</span>
                    </div>

                    {{-- Prev / next arrows — desktop only, revealed on hover --}}
                    <button type="button" @click="swiper?.slidePrev()" aria-label="Previous slide"
                        class="absolute top-1/2 left-3 z-10 hidden size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-black/35 text-white opacity-0 backdrop-blur-sm transition duration-200 hover:bg-black/55 group-hover:opacity-100 md:flex">
                        <flux:icon.chevron-left class="size-5" />
                    </button>
                    <button type="button" @click="swiper?.slideNext()" aria-label="Next slide"
                        class="absolute top-1/2 right-3 z-10 hidden size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-black/35 text-white opacity-0 backdrop-blur-sm transition duration-200 hover:bg-black/55 group-hover:opacity-100 md:flex">
                        <flux:icon.chevron-right class="size-5" />
                    </button>
                </div>

            </div>
        </div>
    </section>

    {{-- USPs strip --}}
    <section class="border-b border-zinc-200 bg-white">
        <div class="shell grid grid-cols-2 sm:grid-cols-5 sm:divide-x sm:divide-zinc-200">
            @foreach ($usps as $u)
                <div @class([
                    'flex items-center gap-2.5 px-3 py-4 sm:flex-col sm:items-center sm:gap-3 sm:px-5 sm:py-6 sm:text-center',
                    'hidden sm:flex' => $loop->last,
                ])>
                    <flux:icon name="{{ $u['icon'] }}" variant="outline"
                        class="size-6 shrink-0 text-brand-500 sm:size-9" />
                    <div>
                        <div class="text-[10px] font-bold tracking-widest text-ink uppercase sm:text-[11px]">
                            {{ $u['title'] }}</div>
                        <div class="mt-0.5 text-[10px] text-ink-3 sm:text-[11px]">{{ $u['sub'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Divisions / "Shop by department" — temporarily disabled. The section and its
         seeded Cold Room / Laundry / Healthcare products were removed; re-enable by
         removing the Blade comment wrapper below once the verticals are ready. --}}
    {{--
    @if ($this->divisions->isNotEmpty())
        <section class="shell pt-14">
            <h2 class="mb-4 text-[22px] font-semibold tracking-tight">Shop by department</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($this->divisions as $division)
                    @php
                        $tiles = $this->collageProducts($division);
                        // First and last cards use a 3-image mosaic (one full-width
                        // on top, two equal-width below); the middle two use a 2×2 grid.
                        $cells = $loop->first || $loop->last
                            ? [['span' => 'col-span-2', 'aspect' => 'aspect-[2/1]'], ['span' => '', 'aspect' => 'aspect-square'], ['span' => '', 'aspect' => 'aspect-square']]
                            : array_fill(0, 4, ['span' => '', 'aspect' => 'aspect-square']);
                    @endphp
                    <div class="flex flex-col rounded-md border border-zinc-200 bg-white p-5">
                        <h3 class="text-[15px] font-semibold tracking-tight text-ink">{{ $division->name }}</h3>

                        @if ($tiles->isNotEmpty())
                            <!-- Product-image collage (padded to keep the shape) -->
                            <div class="mt-4 grid grid-cols-2 gap-2.5">
                                @foreach ($cells as $i => $cell)
                                    @php $product = $tiles[$i] ?? null; @endphp
                                    <a @if ($product) href="{{ route('product.show', $product) }}" wire:navigate @endif
                                        @class(['group block', $cell['span'], 'pointer-events-none' => ! $product])>
                                        <div class="relative {{ $cell['aspect'] }} overflow-hidden rounded bg-surface-sunken">
                                            @if ($product?->cover_url)
                                                <img src="{{ $product->cover_url }}" alt="{{ $product->name }}"
                                                    loading="lazy"
                                                    class="size-full object-cover transition duration-500 group-hover:scale-105" />
                                            @else
                                                <div class="flex size-full items-center justify-center">
                                                    <flux:icon.photo variant="outline" class="size-6 text-zinc-300" />
                                                </div>
                                            @endif
                                        </div>
                                        @if ($product)
                                            <div class="mt-1.5 truncate text-[11px] text-ink-3">{{ $product->name }}</div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <!-- No product imagery yet — placeholder hero linking to the division -->
                            <a href="{{ route('category.show', $division) }}" wire:navigate
                                class="group mt-4 block flex-1">
                                <div class="relative h-full min-h-44 overflow-hidden rounded bg-surface-sunken">
                                    <div class="flex size-full items-center justify-center">
                                        <flux:icon.photo variant="outline" class="size-10 text-zinc-300" />
                                    </div>
                                </div>
                            </a>
                        @endif

                        <a href="{{ route('category.show', $division) }}" wire:navigate
                            class="group mt-4 inline-flex items-center gap-1.5 text-[13px] font-semibold text-brand-blue-500 transition-colors hover:text-brand-blue-600">
                            Shop {{ $division->name }}
                            <flux:icon.arrow-right variant="micro"
                                class="size-3.5 transition-transform duration-200 group-hover:translate-x-0.5" />
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
    --}}

    {{-- Categories — dense Workshop grid (12 chips, square aspect, ink underline) --}}
    <section class="shell pt-8 md:pt-14 @container">
        <div class="mb-4 flex items-baseline justify-between">
            <h2 class="text-[17px] font-semibold tracking-tight @md:text-[22px]">Shop by category</h2>
            <a href="{{ route('categories.index') }}" wire:navigate
                class="text-[13px] font-medium text-brand-500 underline transition-colors hover:text-brand-600">
                View all
            </a>
        </div>

        {{-- All featured categories stay visible at every breakpoint; only the column
             count changes, so the same chips simply reflow. --}}
        <div
            class="grid gap-x-3 gap-y-4 grid-cols-1 @3xs:grid-cols-2 @3xs:gap-x-4 @3xs:gap-y-5 @md:grid-cols-3 @md:gap-x-5 @md:gap-y-7 @xl:grid-cols-4 @3xl:grid-cols-5 @6xl:grid-cols-6 @7xl:grid-cols-7 @8xl:grid-cols-8">
            @foreach ($this->featuredCategories as $category)
                <a href="{{ route('category.show', $category) }}" wire:navigate class="group block transition">
                    <div class="relative aspect-[4/3] overflow-hidden rounded-lg bg-surface-sunken">
                        @if ($category->image_url)
                            @if ($placeholder = $category->image_placeholder)
                                <img src="{{ $placeholder }}" alt="" aria-hidden="true"
                                    class="absolute inset-0 size-full scale-110 object-cover blur-xl" />
                            @endif
                            <picture class="contents">
                                @if ($category->image_webp_url)
                                    <source srcset="{{ $category->image_webp_url }}" type="image/webp" />
                                @endif
                                <img src="{{ $category->image_url }}" alt="" loading="lazy"
                                    x-data="{ loaded: false }" x-init="loaded = $el.complete" x-on:load="loaded = true"
                                    x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
                                    class="relative block size-full object-cover transition duration-500 group-hover:scale-[1.04]" />
                            </picture>
                        @endif
                    </div>
                    <div class="flex items-baseline justify-between gap-2 pt-2.5">
                        <div
                            class="text-[11.5px] leading-tight font-semibold tracking-[0.06em] text-ink uppercase transition-colors group-hover:text-brand-500">
                            {{ $category->name }}
                        </div>
                        <div class="shrink-0 text-[11px] text-ink-3 tabular-nums">
                            {{ $category->products_count ?? $category->products()->count() }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Brands marquee --}}
    <section class="shell pt-8 md:pt-14">
        <div class="relative -mx-4 overflow-hidden border-y border-zinc-200 bg-white md:mx-0 md:rounded-md md:border">
            <div class="grid grid-cols-1 items-stretch md:grid-cols-[auto_1fr]">
                {{-- Title panel — hidden below md so the marquee runs edge to edge on phones --}}
                <div
                    class="relative z-10 hidden min-w-60 flex-col justify-center border-r border-zinc-200 bg-white px-8 py-8 md:flex">
                    <h2 class="font-serif text-[22px] leading-tight font-semibold uppercase">The
                        brands<br>professionals
                        trust.</h2>
                </div>

                <div class="brand-marquee relative flex items-stretch overflow-hidden">
                    <div
                        class="pointer-events-none absolute top-0 bottom-0 left-0 z-10 w-20 bg-linear-to-r from-white to-transparent">
                    </div>
                    <div
                        class="pointer-events-none absolute top-0 right-0 bottom-0 z-10 w-20 bg-linear-to-l from-white to-transparent">
                    </div>
                    <div class="brand-marquee-track flex w-max items-stretch">
                        @foreach ([...$this->brands->all(), ...$this->brands->all()] as $brand)
                            <a href="{{ $brand->website_url ?: '#' }}"
                                @if ($brand->website_url) target="_blank" rel="noopener noreferrer" @endif
                                class="flex w-36 shrink-0 flex-col items-center justify-center gap-2 self-stretch border-r border-zinc-200 px-4 py-4 text-center transition md:w-45 md:px-5 md:py-7">
                                @if ($brand->logo_url)
                                    <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}"
                                        class="h-14 w-full object-contain md:h-24" loading="lazy" />
                                @else
                                    <div class="font-serif text-lg text-ink">{{ $brand->name }}</div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- New Arrivals --}}
    <section class="shell pt-8 md:pt-14">
        <div class="overflow-hidden rounded-md bg-brand-500">
            <div class="grid grid-cols-1 lg:grid-cols-6">
                {{-- Left editorial panel --}}
                <div
                    class="flex flex-col justify-center border-b border-white/10 px-6 pt-8 pb-4 lg:col-span-1 lg:border-b-0 lg:border-r lg:border-white/10 lg:py-8">
                    <div class="font-serif text-4xl leading-none text-white">New arrivals</div>
                    <div class="mt-3 text-[13px] leading-relaxed text-white/75">Discover what's just dropped</div>
                    <flux:button href="{{ route('catalog') }}?arrivals=1" wire:navigate class="mt-5 w-fit">
                        View All
                    </flux:button>
                </div>

                {{-- Products carousel --}}
                <div class="relative px-4 py-5 lg:col-span-5" x-data="{
                    swiper: null,
                    init() {
                        this.swiper = new Swiper($refs.carousel, {
                            spaceBetween: 12,
                            loop: true,
                            speed: 400,
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
                    <div class="swiper" x-ref="carousel" wire:ignore>
                        <div class="swiper-wrapper pb-1">
                            @foreach ($this->newArrivals as $product)
                                <div class="swiper-slide h-auto!">
                                    <div class="h-full flex flex-col">
                                        <x-storefront.product-card :product="$product" class="h-full" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <button type="button" @click="swiper?.slidePrev()"
                        class="absolute top-1/2 left-1 z-10 -translate-y-1/2 hidden size-7 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-black/20 text-white backdrop-blur-sm transition hover:border-white/40 hover:bg-black/40 md:flex">
                        <flux:icon.chevron-left class="size-3.5" />
                    </button>
                    <button type="button" @click="swiper?.slideNext()"
                        class="absolute top-1/2 right-1 z-10 -translate-y-1/2 hidden size-7 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-black/20 text-white backdrop-blur-sm transition hover:border-white/40 hover:bg-black/40 md:flex">
                        <flux:icon.chevron-right class="size-3.5" />
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Featured products --}}
    <section class="shell pt-8 md:pt-14 @container">
        <div class="mb-4 flex items-baseline justify-between">
            <h2 class="text-[17px] font-semibold tracking-tight @md:text-[22px]">Featured equipment</h2>
            <a href="{{ route('catalog') }}?tag=Featured" wire:navigate
                class="text-[13px] font-medium text-brand-500 underline transition-colors hover:text-brand-600">
                View all
            </a>
        </div>
        <div
            class="grid grid-cols-1 gap-3.5 @xs:grid-cols-2 @md:grid-cols-3 @2xl:grid-cols-4 4xl:grid-cols-5 @6xl:grid-cols-6">
            @foreach ($this->featuredProducts as $product)
                <x-storefront.product-card :product="$product" />
            @endforeach
        </div>
    </section>


    {{-- RFQ banner --}}
    {{-- <section class="shell pt-14">
        <div class="grid grid-cols-1 items-center gap-6 rounded-md bg-ink p-9 text-white lg:grid-cols-[1fr_auto]"
            style="background: #0c1421">
            <div>
                <div class="text-[11.5px] font-bold tracking-[0.12em] text-brand-500 uppercase">For procurement</div>
                <div class="mt-2 font-serif text-[32px] leading-[1.1] font-normal">
                    Upload your tender or BOQ — formal quote in 24 hours.
                </div>
                <div class="mt-2 text-[14px] text-[#c9bea4]">
                    Upload PDF or Excel · We respond in business hours · No account required.
                </div>
            </div>
            <div class="flex gap-2.5">
                <flux:button variant="primary" class="h-12! px-6!">Start a quote</flux:button>
                <flux:button class="h-12! px-6! bg-transparent! border-white/20! text-white!">Book site visit
                </flux:button>
            </div>
        </div>
    </section> --}}

    @include('partials.storefront.accessory-modal')
</div>
