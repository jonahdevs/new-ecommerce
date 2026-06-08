<?php

use App\Enums\CategorySection;
use App\Enums\CategoryStatus;
use App\Enums\StockStatus;
use App\Livewire\Concerns\InteractsWithStorefront;
use App\Models\Brand;
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

new #[Layout('layouts::storefront')] #[Title('Commercial Kitchen Equipment for East Africa')] class extends Component
{
    use InteractsWithStorefront;

    /** @var array<int, int> Locked so random order is fixed for the lifetime of the component. */
    public array $featuredProductIds = [];

    public function mount(): void
    {
        $description = 'Authorised distributor for Rational, Hobart, True, Electrolux Professional and more. Showrooms in Nairobi, Mombasa, Kampala and Kigali — install, service and spares across East Africa.';

        SEOMeta::setDescription($description);
        OpenGraph::setDescription($description)->setType('website');
        TwitterCard::setDescription($description);
        JsonLdMulti::setDescription($description)->setType('Organization');

        $this->featuredProductIds = Product::query()
            ->visibleInCatalog()
            ->published()
            ->where('stock_status', StockStatus::IN_STOCK)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->inRandomOrder()
            ->take(6)
            ->pluck('id')
            ->toArray();
    }

    // TODO: cache these once they become hot. View composer would be cleaner.
    #[Computed]
    public function featuredCategories(): Collection
    {
        return CategoryPlacement::query()
            ->with(['category' => fn ($q) => $q->withCount('products')])
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
        return Product::query()
            ->with(['brand', 'taxClass', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->whereIn('id', $this->featuredProductIds)
            ->get()
            ->sortBy(fn ($p) => array_search($p->id, $this->featuredProductIds))
            ->values();
    }

    #[Computed]
    public function newArrivals(): Collection
    {
        return Product::query()
            ->with(['brand', 'taxClass', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->visibleInCatalog()
            ->published()
            ->where('stock_status', StockStatus::IN_STOCK)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->latest('id')
            ->take(12)
            ->get();
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
        ],
        [
            'src' => '/images/banners/coffee-machines.webp',
            'alt' => 'Premium coffee machines',
            'cta' => 'Shop coffee machines',
            'align' => 'right',
        ],
        [
            'src' => '/images/banners/refrigeration.webp',
            'alt' => 'Smart cooling — refrigeration solutions',
            'cta' => 'Shop refrigeration',
            'align' => 'right',
        ],
        [
            'src' => '/images/banners/bakery-prep.webp',
            'alt' => 'Bakery preparation equipment',
            'cta' => 'Shop bakery prep',
            'align' => 'center',
        ],
        [
            'src' => '/images/banners/clearance-sale.webp',
            'alt' => 'Limited time clearance sale',
            'cta' => 'Shop clearance',
            'align' => 'left',
        ],
    ];

    $usps = [
        ['icon' => 'building-office-2', 'title' => 'Africa No. 1', 'sub' => 'In Kitchen Equipment'],
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
                    draggable="false" />
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
                    <button type="button" aria-label="{{ $slide['alt'] }}" :aria-hidden="idx !== {{ $i }}"
                        :tabindex="idx === {{ $i }} ? 0 : -1"
                        class="absolute inset-0 cursor-pointer border-0 p-0 transition-opacity duration-700 {{ $i === 0 ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none' }}"
                        :class="idx === {{ $i }} ? 'opacity-100 pointer-events-auto' :
                            'opacity-0 pointer-events-none'">
                        <img src="{{ $slide['src'] }}" alt="{{ $slide['alt'] }}" class="block size-full object-cover"
                            style="object-position: {{ $slide['align'] === 'left' ? 'left center' : ($slide['align'] === 'right' ? 'right center' : 'center') }}"
                            draggable="false" />
                        <span aria-hidden
                            class="pointer-events-none absolute bottom-6 inline-flex items-center gap-2 rounded-full bg-white/90 px-4 py-2.5 text-[13px] font-semibold text-ink shadow-lg backdrop-blur-md transition duration-500"
                            style="{{ $slide['align'] === 'left' ? 'right: 1.5rem;' : 'left: 1.5rem;' }}"
                            :class="idx === {{ $i }} ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">
                            {{ $slide['cta'] }}
                            <flux:icon.arrow-right variant="mini" class="size-3.5" />
                        </span>
                    </button>
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

    {{-- Categories — dense Workshop grid (12 chips, square aspect, ink underline) --}}
    <section class="shell pt-14">
        <div class="mb-4 flex items-baseline justify-between">
            <h2 class="text-[22px] font-semibold tracking-tight">Shop by category</h2>
            <a href="{{ route('catalog') }}" wire:navigate
                class="inline-flex items-center gap-1.5 text-[13px] text-ink-3 transition-colors hover:text-ink">
                View all <flux:icon.arrow-right variant="micro" class="size-3.5" />
            </a>
        </div>

        {{-- grid-rows-2 + auto-rows-[0] caps visible rows to 2 at any breakpoint, so the
             extra chips needed to fill a 7-col row at 2xl don't dangle below 2 rows of 6 at lg/xl. --}}
        <div
            class="grid grid-cols-2 grid-rows-2 gap-x-5 gap-y-7 overflow-hidden sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 2xl:grid-cols-7 auto-rows-[0]">
            @foreach ($this->featuredCategories as $category)
                <a href="{{ route('category.show', $category) }}" wire:navigate class="group block transition">
                    <div class="relative aspect-square overflow-hidden bg-surface-sunken">
                        @if ($category->image)
                            <img src="{{ $category->image_url }}" alt=""
                                loading="lazy"
                                class="block size-full object-cover transition duration-300 group-hover:scale-[1.04]" />
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
        <div class="relative overflow-hidden rounded-md border border-zinc-200 bg-white">
            <div class="grid grid-cols-[auto_1fr] items-stretch">
                <div
                    class="relative z-10 flex min-w-60 flex-col justify-center border-r border-zinc-200 bg-white px-8 py-8">
                    <h2 class="font-serif text-[22px] leading-tight font-semibold uppercase">The brands<br>professionals trust.</h2>
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
                            <a href="#" wire:navigate
                                class="flex w-45 shrink-0 flex-col items-center justify-center gap-2 self-stretch border-r border-zinc-200 px-5 py-7 text-center transition hover:bg-surface-sunken">
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
                <div class="flex flex-col justify-center border-b border-white/10 px-6 py-8 lg:col-span-1 lg:border-b-0 lg:border-r lg:border-white/10">
                    <div class="text-[11px] font-semibold uppercase tracking-widest text-white/60">Just In</div>
                    <div class="mt-2 font-serif text-4xl leading-none text-white">New</div>
                    <div class="mt-3 text-[13px] leading-relaxed text-white/75">Discover what's just dropped</div>
                    <a href="{{ route('catalog') }}?sort=newest" wire:navigate
                        class="group mt-5 inline-flex w-fit items-center gap-1.5 rounded-full border border-white/30 px-4 py-2 text-xs font-semibold text-white transition-all hover:border-white hover:bg-white/10">
                        View All
                        <flux:icon.arrow-right class="size-3 transition-transform duration-200 group-hover:translate-x-1" />
                    </a>
                </div>

                {{-- Products carousel --}}
                <div class="relative px-4 py-5 lg:col-span-5"
                    x-data="{
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
    <section class="shell pt-14">
        <div class="mb-4 flex items-baseline justify-between">
            <h2 class="text-[22px] font-semibold tracking-tight">Featured equipment</h2>
            <a href="{{ route('catalog') }}" wire:navigate
                class="inline-flex items-center gap-1.5 text-[13px] text-ink-3 transition-colors hover:text-ink">
                View all <flux:icon.arrow-right variant="micro" class="size-3.5" />
            </a>
        </div>
        <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4 2xl:grid-cols-6">
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

</div>
