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
            ->with(['brand', 'taxClass', 'media'])
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
            ->with(['brand', 'taxClass', 'media'])
            ->whereIn('id', $this->featuredProductIds)
            ->get()
            ->sortBy(fn($p) => array_search($p->id, $this->featuredProductIds))
            ->values();
    }

    #[Computed]
    public function newArrivals(): Collection
    {
        $base = Product::query()
            ->with(['brand', 'taxClass', 'media'])
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
    // Hero rotator slides — copy maps to a stable serialisable form for Alpine
    $heroSlides = [
        [
            'src' => '/images/banners/topline.webp',
            'alt' => 'Add to your topline — premium kitchen equipment',
            'cta' => 'Upgrade now',
            'align' => 'right',
            'url' => route('catalog'),
        ],
        [
            'src' => '/images/banners/coffee-machines.webp',
            'alt' => 'Premium coffee machines',
            'cta' => 'Shop coffee machines',
            'align' => 'right',
            'url' => route('category.show', 'coffee-machines'),
        ],
        [
            'src' => '/images/banners/refrigeration.webp',
            'alt' => 'Smart cooling — refrigeration solutions',
            'cta' => 'Shop refrigeration',
            'align' => 'right',
            'url' => route('category.show', 'refrigeration'),
        ],
        [
            'src' => '/images/banners/bakery-prep.webp',
            'alt' => 'Bakery preparation equipment',
            'cta' => 'Shop bakery prep',
            'align' => 'center',
            'url' => route('category.show', 'bakery-preparation'),
        ],
        [
            'src' => '/images/banners/clearance-sale.webp',
            'alt' => 'Limited time clearance sale',
            'cta' => 'Shop clearance',
            'align' => 'left',
            'url' => route('catalog', ['tag' => 'On Sale']),
        ],
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
            <a href="#" wire:navigate aria-label="Up to 20% off mega sale"
                class="block overflow-hidden rounded-md shadow-sm" style="aspect-ratio: 3117 / 400">
                <img src="/images/banners/thin-banner.webp" alt="" class="size-full object-cover"
                    fetchpriority="high" decoding="async" draggable="false" />
            </a>
        </div>
    </section>

    {{-- Hero rotator --}}
    <section class="border-b border-zinc-200 bg-surface-sunken" x-data="{
        idx: 0,
        paused: false,
        total: {{ count($heroSlides) }},
        timer: null,
        start() { this.timer = setInterval(() => { if (!this.paused) this.idx = (this.idx + 1) % this.total }, 6500) },
        stop() { clearInterval(this.timer) },
    }" x-init="start()"
        @mouseenter="paused = true" @mouseleave="paused = false">
        <div class="shell py-5">
            <div class="relative overflow-hidden rounded-md bg-zinc-900" style="aspect-ratio: 2181 / 624">
                @foreach ($heroSlides as $i => $slide)
                    <a href="{{ $slide['url'] }}" wire:navigate aria-label="{{ $slide['alt'] }}"
                        :aria-hidden="idx !== {{ $i }}" :tabindex="idx === {{ $i }} ? 0 : -1"
                        class="absolute inset-0 block cursor-pointer transition-opacity duration-700 {{ $i === 0 ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none' }}"
                        :class="idx === {{ $i }} ? 'opacity-100 pointer-events-auto' :
                            'opacity-0 pointer-events-none'">
                        <img src="{{ $slide['src'] }}" alt="{{ $slide['alt'] }}" class="block size-full object-cover"
                            style="object-position: {{ $slide['align'] === 'left' ? 'left center' : ($slide['align'] === 'right' ? 'right center' : 'center') }}"
                            @if ($i === 0) fetchpriority="high" decoding="async" @else loading="lazy" decoding="async" @endif
                            draggable="false" />
                        <span aria-hidden
                            class="pointer-events-none absolute bottom-6 inline-flex items-center gap-2 rounded-full bg-white/90 px-4 py-2.5 text-[13px] font-semibold text-ink shadow-lg backdrop-blur-md transition duration-500"
                            style="{{ $slide['align'] === 'left' ? 'right: 1.5rem;' : 'left: 1.5rem;' }}"
                            :class="idx === {{ $i }} ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">
                            {{ $slide['cta'] }}
                            <flux:icon.arrow-right variant="mini" class="size-3.5" />
                        </span>
                    </a>
                @endforeach

                {{-- Prev / Next --}}
                <button type="button" aria-label="Previous slide" @click="idx = (idx - 1 + total) % total"
                    class="absolute top-1/2 left-3 inline-flex size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-white/85 text-ink shadow-md backdrop-blur-md hover:bg-white">
                    <flux:icon.arrow-left variant="mini" class="size-4" />
                </button>
                <button type="button" aria-label="Next slide" @click="idx = (idx + 1) % total"
                    class="absolute top-1/2 right-3 inline-flex size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-white/85 text-ink shadow-md backdrop-blur-md hover:bg-white">
                    <flux:icon.arrow-right variant="mini" class="size-4" />
                </button>

                {{-- Dots --}}
                <div
                    class="absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-1.5 rounded-full bg-black/35 px-2.5 py-1.5 backdrop-blur-sm">
                    @for ($i = 0; $i < count($heroSlides); $i++)
                        <button type="button" aria-label="Go to slide {{ $i + 1 }}"
                            @click="idx = {{ $i }}"
                            class="h-1.5 cursor-pointer rounded-full border-0 transition-all duration-200"
                            :class="idx === {{ $i }} ? 'w-5 bg-white' : 'w-1.5 bg-white/55'"></button>
                    @endfor
                </div>

                {{-- Slide counter --}}
                <div
                    class="absolute top-3.5 right-3.5 flex items-center gap-1.5 rounded-full bg-black/35 px-2.5 py-1 text-[11px] tracking-wider text-white tabular-nums backdrop-blur-sm">
                    <span class="font-semibold" x-text="String(idx + 1).padStart(2, '0')"></span>
                    <span class="opacity-60">/ {{ str_pad(count($heroSlides), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="opacity-70" x-show="paused" x-cloak>· paused</span>
                </div>
            </div>
        </div>
    </section>

    {{-- USPs strip --}}
    <section class="border-b border-zinc-200 bg-white">
        <div class="shell grid grid-cols-2 sm:grid-cols-5 sm:divide-x sm:divide-zinc-200">
            @foreach ($usps as $u)
                <div class="flex flex-col items-center gap-3 px-5 py-6 text-center">
                    <flux:icon name="{{ $u['icon'] }}" variant="outline" class="size-9 text-brand-500" />
                    <div>
                        <div class="text-[11px] font-bold tracking-widest text-ink uppercase">{{ $u['title'] }}</div>
                        <div class="mt-0.5 text-[11px] text-ink-3">{{ $u['sub'] }}</div>
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
    <section class="shell pt-14 @container">
        <div class="mb-4 flex items-baseline justify-between">
            <h2 class="text-[22px] font-semibold tracking-tight">Shop by category</h2>
            <a href="{{ route('categories.index') }}" wire:navigate
                class="inline-flex items-center gap-1.5 text-[13px] text-ink-3 transition-colors hover:text-ink">
                View all <flux:icon.arrow-right variant="micro" class="size-3.5" />
            </a>
        </div>

        {{-- All featured categories stay visible at every breakpoint; only the column
             count changes, so the same chips simply reflow. --}}
        <div
            class="grid gap-x-5 gap-y-7 grid-cols-1  @3xs:grid-cols-2 @md:grid-cols-3 @xl:grid-cols-4 @3xl:grid-cols-5 @6xl:grid-cols-6 @7xl:grid-cols-7 @8xl:grid-cols-8">
            @foreach ($this->featuredCategories as $category)
                <a href="{{ route('category.show', $category) }}" wire:navigate class="group block transition">
                    <div class="relative aspect-square overflow-hidden bg-surface-sunken">
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
    <section class="shell pt-14">
        <div class="relative -mx-4 overflow-hidden border-y border-zinc-200 bg-white md:mx-0 md:rounded-md md:border">
            <div class="grid grid-cols-1 items-stretch md:grid-cols-[auto_1fr]">
                {{-- Title panel — hidden below md so the marquee runs edge to edge on phones --}}
                <div
                    class="relative z-10 hidden min-w-60 flex-col justify-center border-r border-zinc-200 bg-white px-8 py-8 md:flex">
                    <h2 class="font-serif text-[22px] leading-tight font-semibold uppercase">The brands<br>professionals
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
                                class="flex w-45 shrink-0 flex-col items-center justify-center gap-2 self-stretch border-r border-zinc-200 px-5 py-7 text-center transition">
                                @if ($brand->logo_url)
                                    <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}"
                                        class="h-24 w-full object-contain" loading="lazy" />
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
    <section class="shell pt-14">
        <div class="overflow-hidden rounded-md bg-brand-500">
            <div class="grid grid-cols-1 lg:grid-cols-6">
                {{-- Left editorial panel --}}
                <div
                    class="flex flex-col justify-center border-b border-white/10 px-6 py-8 lg:col-span-1 lg:border-b-0 lg:border-r lg:border-white/10">
                    <div class="text-[11px] font-semibold uppercase tracking-widest text-white/60">Just In</div>
                    <div class="mt-2 font-serif text-4xl leading-none text-white">New</div>
                    <div class="mt-3 text-[13px] leading-relaxed text-white/75">Discover what's just dropped</div>
                    <a href="{{ route('catalog') }}?arrivals=1" wire:navigate
                        class="group mt-5 inline-flex w-fit items-center gap-1.5 rounded-full border border-white/30 px-4 py-2 text-xs font-semibold text-white transition-all hover:border-white hover:bg-white/10">
                        View All
                        <flux:icon.arrow-right
                            class="size-3 transition-transform duration-200 group-hover:translate-x-1" />
                    </a>
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
                    <div class="swiper" x-ref="carousel">
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
                        class="absolute top-1/2 left-1 z-10 -translate-y-1/2 flex size-7 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-black/20 text-white backdrop-blur-sm transition hover:border-white/40 hover:bg-black/40">
                        <flux:icon.chevron-left class="size-3.5" />
                    </button>
                    <button type="button" @click="swiper?.slideNext()"
                        class="absolute top-1/2 right-1 z-10 -translate-y-1/2 flex size-7 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-black/20 text-white backdrop-blur-sm transition hover:border-white/40 hover:bg-black/40">
                        <flux:icon.chevron-right class="size-3.5" />
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Featured products --}}
    <section class="shell pt-14 @container">
        <div class="mb-4 flex items-baseline justify-between">
            <h2 class="text-[22px] font-semibold tracking-tight">Featured equipment</h2>
            <a href="{{ route('catalog') }}?tag=Featured" wire:navigate
                class="inline-flex items-center gap-1.5 text-[13px] text-ink-3 transition-colors hover:text-ink">
                View all <flux:icon.arrow-right variant="micro" class="size-3.5" />
            </a>
        </div>
        <div class="grid grid-cols-1 gap-3.5 @sm:grid-cols-2 @xl:grid-cols-3 @3xl:grid-cols-4 @6xl:grid-cols-6">
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
