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

new #[Layout('layouts::storefront')] #[Title('Sheffield — Commercial Kitchen Equipment for East Africa')] class extends Component
{
    use InteractsWithStorefront;

    public function mount(): void
    {
        $description = 'Authorised distributor for Rational, Hobart, True, Electrolux Professional and more. Showrooms in Nairobi, Mombasa, Kampala and Kigali — install, service and spares across East Africa.';

        SEOMeta::setDescription($description);
        OpenGraph::setDescription($description)->setType('website');
        TwitterCard::setDescription($description);
        JsonLdMulti::setDescription($description)->setType('Organization');
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
            ->with(['brand', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->where('visibility', 'visible')
            ->where('stock_status', StockStatus::IN_STOCK)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->inRandomOrder()
            ->take(6)
            ->get();
    }

    #[Computed]
    public function newArrivals(): Collection
    {
        return Product::query()
            ->with(['brand', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->where('visibility', 'visible')
            ->where('stock_status', StockStatus::IN_STOCK)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->latest('id')
            ->take(6)
            ->get();
    }

    #[Computed]
    public function brands(): Collection
    {
        return Brand::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->take(16)
            ->get();
    }
}; ?>

@php
    // Hero rotator slides — copy maps to a stable serialisable form for Alpine
    $heroSlides = [
        ['src' => '/images/banners/topline.webp',         'alt' => 'Add to your topline — premium kitchen equipment', 'cta' => 'Upgrade now',           'align' => 'right'],
        ['src' => '/images/banners/coffee-machines.webp', 'alt' => 'Premium coffee machines',                          'cta' => 'Shop coffee machines',  'align' => 'right'],
        ['src' => '/images/banners/refrigeration.webp',   'alt' => 'Smart cooling — refrigeration solutions',          'cta' => 'Shop refrigeration',    'align' => 'right'],
        ['src' => '/images/banners/bakery-prep.webp',     'alt' => 'Bakery preparation equipment',                     'cta' => 'Shop bakery prep',      'align' => 'center'],
        ['src' => '/images/banners/clearance-sale.webp',  'alt' => 'Limited time clearance sale',                      'cta' => 'Shop clearance',        'align' => 'left'],
    ];

    $usps = [
        ['icon' => 'truck',         'title' => 'Regional delivery',     'sub' => 'KE · UG · TZ · RW'],
        ['icon' => 'wrench-screwdriver', 'title' => 'Install & commission', 'sub' => 'Factory-trained'],
        ['icon' => 'shield-check',  'title' => 'Spares in stock',       'sub' => 'Next-day for 98%'],
        ['icon' => 'check-badge',   'title' => 'Net 30 terms',          'sub' => 'Verified businesses'],
    ];
@endphp

<div class="page-fade">
    {{-- Thin promo banner --}}
    <section class="bg-surface-sunken pt-3 pb-2">
        <div class="shell">
            <a href="#" wire:navigate aria-label="Up to 20% off mega sale"
                class="block overflow-hidden rounded-md shadow-sm"
                style="aspect-ratio: 3117 / 400">
                <img src="/images/banners/thin-banner.webp" alt="" class="size-full object-cover" draggable="false" />
            </a>
        </div>
    </section>

    {{-- Hero rotator --}}
    <section class="border-b border-zinc-200 bg-surface-sunken"
        x-data="{
            idx: 0,
            paused: false,
            total: {{ count($heroSlides) }},
            timer: null,
            start() { this.timer = setInterval(() => { if (!this.paused) this.idx = (this.idx + 1) % this.total }, 6500) },
            stop() { clearInterval(this.timer) },
        }"
        x-init="start()"
        @mouseenter="paused = true" @mouseleave="paused = false">
        <div class="shell py-5">
            <div class="relative overflow-hidden rounded-lg bg-zinc-900" style="aspect-ratio: 2181 / 624">
                @foreach ($heroSlides as $i => $slide)
                    <button type="button"
                        aria-label="{{ $slide['alt'] }}"
                        :aria-hidden="idx !== {{ $i }}"
                        :tabindex="idx === {{ $i }} ? 0 : -1"
                        class="absolute inset-0 cursor-pointer border-0 p-0 transition-opacity duration-700"
                        :class="idx === {{ $i }} ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'">
                        <img src="{{ $slide['src'] }}" alt="{{ $slide['alt'] }}"
                            class="block size-full object-cover"
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
                <button type="button" aria-label="Previous slide"
                    @click="idx = (idx - 1 + total) % total"
                    class="absolute top-1/2 left-3 inline-flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-ink shadow-md backdrop-blur-md hover:bg-white">
                    <flux:icon.arrow-left variant="mini" class="size-4" />
                </button>
                <button type="button" aria-label="Next slide"
                    @click="idx = (idx + 1) % total"
                    class="absolute top-1/2 right-3 inline-flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-ink shadow-md backdrop-blur-md hover:bg-white">
                    <flux:icon.arrow-right variant="mini" class="size-4" />
                </button>

                {{-- Dots --}}
                <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-1.5 rounded-full bg-black/35 px-2.5 py-1.5 backdrop-blur-sm">
                    @for ($i = 0; $i < count($heroSlides); $i++)
                        <button type="button" aria-label="Go to slide {{ $i + 1 }}"
                            @click="idx = {{ $i }}"
                            class="h-1.5 rounded-full border-0 transition-all duration-200"
                            :class="idx === {{ $i }} ? 'w-5 bg-white' : 'w-1.5 bg-white/55'"></button>
                    @endfor
                </div>

                {{-- Slide counter --}}
                <div class="absolute top-3.5 right-3.5 flex items-center gap-1.5 rounded-full bg-black/35 px-2.5 py-1 text-[11px] tracking-wider text-white tabular-nums backdrop-blur-sm">
                    <span class="font-semibold" x-text="String(idx + 1).padStart(2, '0')"></span>
                    <span class="opacity-60">/ {{ str_pad(count($heroSlides), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="opacity-70" x-show="paused">· paused</span>
                </div>
            </div>
        </div>
    </section>

    {{-- USPs strip --}}
    <section class="border-b border-zinc-200 bg-surface-sunken">
        <div class="shell grid grid-cols-2 py-5 sm:grid-cols-4">
            @foreach ($usps as $i => $u)
                <div class="flex items-center gap-3 {{ $i > 0 ? 'border-l border-zinc-200 pl-5' : '' }}">
                    <flux:icon name="{{ $u['icon'] }}" variant="outline" class="size-5 shrink-0 text-brand-500" />
                    <div>
                        <div class="text-[13.5px] font-semibold">{{ $u['title'] }}</div>
                        <div class="text-xs text-ink-3">{{ $u['sub'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Categories — dense Workshop grid (12 chips, square aspect, ink underline) --}}
    <section class="shell pt-14">
        <div class="mb-4 flex items-baseline justify-between border-b border-zinc-200 pb-3">
            <h2 class="text-[22px] font-semibold tracking-tight">Shop by category</h2>
            <a href="{{ route('catalog') }}" class="text-[13px] text-zinc-600 hover:text-zinc-900" wire:navigate>All →</a>
        </div>

        {{-- grid-rows-2 + [grid-auto-rows:0] caps visible rows to 2 at any breakpoint, so the
             extra chips needed to fill a 7-col row at 2xl don't dangle below 2 rows of 6 at lg/xl. --}}
        <div class="grid grid-cols-2 grid-rows-2 gap-x-5 gap-y-7 overflow-hidden sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 2xl:grid-cols-7 [grid-auto-rows:0]">
            @foreach ($this->featuredCategories as $category)
                <a href="{{ route('category.show', $category) }}" wire:navigate
                    class="group block transition">
                    <div class="relative aspect-square overflow-hidden border-b-2 border-ink bg-surface-sunken">
                        @if ($category->image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($category->image) }}"
                                alt="" loading="lazy"
                                class="block size-full object-cover transition duration-300 group-hover:scale-[1.04]" />
                        @endif
                    </div>
                    <div class="flex items-baseline justify-between gap-2 pt-2.5">
                        <div class="text-[11.5px] leading-tight font-semibold tracking-[0.06em] text-ink uppercase transition-colors group-hover:text-brand-500">
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
        <div class="relative overflow-hidden rounded-lg border border-zinc-200 bg-white">
            <div class="grid grid-cols-[auto_1fr] items-stretch">
                <div class="relative z-10 flex min-w-[240px] flex-col justify-center border-r border-zinc-200 bg-surface-sunken px-8 py-8">
                    <div class="text-[11.5px] font-bold tracking-[0.12em] text-brand-500 uppercase">
                        Authorised distributor
                    </div>
                    <h2 class="mt-2 font-serif text-[22px] leading-tight font-normal">Brands we carry.</h2>
                    <a href="#" class="mt-3 inline-flex items-center gap-1.5 text-[12.5px] text-zinc-600 hover:text-zinc-900" wire:navigate>
                        All brands →
                    </a>
                </div>

                <div class="brand-marquee relative flex items-stretch overflow-hidden">
                    <div class="pointer-events-none absolute top-0 bottom-0 left-0 z-10 w-20 bg-gradient-to-r from-white to-transparent"></div>
                    <div class="pointer-events-none absolute top-0 right-0 bottom-0 z-10 w-20 bg-gradient-to-l from-white to-transparent"></div>
                    <div class="brand-marquee-track flex w-max items-stretch">
                        @foreach ([...$this->brands->all(), ...$this->brands->all()] as $i => $brand)
                            <a href="#" wire:navigate
                                class="flex w-[200px] shrink-0 flex-col items-center justify-center gap-1 self-stretch border-r border-zinc-200 px-4 py-9 text-center transition hover:bg-surface-sunken">
                                <div class="font-serif text-lg text-ink">{{ $brand->name }}</div>
                                <div class="text-[10.5px] tracking-[0.08em] text-ink-4 uppercase">Authorised dealer</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Featured products --}}
    <section class="shell pt-14">
        <div class="mb-4 flex items-baseline justify-between border-b border-zinc-200 pb-3">
            <h2 class="text-[22px] font-semibold tracking-tight">Featured equipment</h2>
            <a href="{{ route('catalog') }}" class="text-[13px] text-zinc-600 hover:text-zinc-900" wire:navigate>See all featured →</a>
        </div>
        <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4 2xl:grid-cols-6">
            @foreach ($this->featuredProducts as $product)
                <x-storefront.product-card :product="$product" />
            @endforeach
        </div>
    </section>

    {{-- New arrivals --}}
    <section class="shell pt-14">
        <div class="mb-4 flex items-baseline justify-between border-b border-zinc-200 pb-3">
            <h2 class="text-[22px] font-semibold tracking-tight">New arrivals</h2>
            <a href="{{ route('catalog') }}?sort=newest" class="text-[13px] text-zinc-600 hover:text-zinc-900" wire:navigate>See all new →</a>
        </div>
        <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4 2xl:grid-cols-6">
            @foreach ($this->newArrivals as $product)
                <x-storefront.product-card :product="$product" />
            @endforeach
        </div>
    </section>

    {{-- Showroom band --}}
    <section class="shell pt-14">
        <div class="overflow-hidden rounded-lg bg-brand-blue-700 text-[#f3eadd]"
            x-data="{ active: 'nairobi' }">
            <div class="grid min-h-[420px] grid-cols-1 lg:grid-cols-[1.1fr_1fr]">
                {{-- Map column --}}
                <div class="relative bg-brand-blue-700 p-6">
                    <svg viewBox="0 0 360 420" class="block size-full">
                        <defs>
                            <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                                <path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.5" />
                            </pattern>
                        </defs>
                        <rect width="360" height="420" fill="url(#grid)" />
                        <g opacity="0.18" fill="#fff">
                            <path d="M180 90 L 280 100 L 295 160 L 285 220 L 240 245 L 195 260 L 175 240 L 165 180 Z" />
                            <path d="M120 130 L 175 130 L 180 200 L 130 215 L 105 195 L 100 165 Z" />
                            <path d="M95 220 L 135 215 L 140 250 L 110 260 L 90 248 Z" />
                            <path d="M135 250 L 200 248 L 260 270 L 285 320 L 240 365 L 180 360 L 130 320 L 120 290 Z" />
                        </g>
                        @php
                            // Project (lat, lng) → SVG coords (28E–42E, 5N–6S)
                            $pins = [
                                ['slug' => 'nairobi',  'city' => 'Nairobi',  'lat' => -1.3194, 'lng' => 36.8842, 'hq' => true],
                                ['slug' => 'mombasa',  'city' => 'Mombasa',  'lat' => -4.0473, 'lng' => 39.6634, 'hq' => false],
                                ['slug' => 'kampala',  'city' => 'Kampala',  'lat' => 0.3163,  'lng' => 32.5822, 'hq' => false],
                                ['slug' => 'kigali',   'city' => 'Kigali',   'lat' => -1.9499, 'lng' => 30.0588, 'hq' => false],
                            ];
                        @endphp
                        @foreach ($pins as $p)
                            @php
                                $x = (($p['lng'] - 28) / (42 - 28)) * 360;
                                $y = ((6 - $p['lat']) / (6 - (-6))) * 420;
                                $anchor = in_array($p['slug'], ['kigali', 'kampala']) ? 'end' : 'start';
                                $tx = $x + (in_array($p['slug'], ['kigali', 'kampala']) ? -10 : 10);
                            @endphp
                            <g class="cursor-pointer" @click="active = '{{ $p['slug'] }}'">
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="14" fill="hsl(354 68% 45% / 0.25)"
                                    x-show="active === '{{ $p['slug'] }}'" />
                                <circle cx="{{ $x }}" cy="{{ $y }}" :r="active === '{{ $p['slug'] }}' ? 6 : 4"
                                    fill="hsl(354 68% 45%)" stroke="#fff" stroke-width="1.5" />
                                <text x="{{ $tx }}" y="{{ $y + 4 }}" text-anchor="{{ $anchor }}"
                                    font-size="11" fill="rgba(255,255,255,0.85)"
                                    :font-weight="active === '{{ $p['slug'] }}' ? 700 : 500">{{ $p['city'] }}{{ $p['hq'] ? ' ★' : '' }}</text>
                            </g>
                        @endforeach
                    </svg>
                </div>

                {{-- Detail column --}}
                <div class="flex flex-col p-10">
                    <div class="text-[11.5px] font-bold tracking-[0.12em] text-brand-500 uppercase">
                        Visit a Sheffield showroom
                    </div>
                    <h2 class="mt-2 font-serif text-3xl font-normal text-[#f6ecd9]">
                        Across four cities. <span class="text-brand-500">Always nearby.</span>
                    </h2>
                    <p class="mt-3 max-w-md text-[13.5px] leading-relaxed text-[#c9bea4]">
                        Equipment on the floor for hands-on demos, spares in stock, and engineers on call. Walk in or book a fitting consultation.
                    </p>

                    {{-- Tabs --}}
                    <div class="mt-5 flex border-b border-white/12">
                        @foreach ([
                            ['slug' => 'nairobi', 'city' => 'Nairobi', 'hq' => true],
                            ['slug' => 'mombasa', 'city' => 'Mombasa', 'hq' => false],
                            ['slug' => 'kampala', 'city' => 'Kampala', 'hq' => false],
                            ['slug' => 'kigali',  'city' => 'Kigali',  'hq' => false],
                        ] as $tab)
                            <button type="button" @click="active = '{{ $tab['slug'] }}'"
                                class="-mb-px inline-flex items-center gap-1.5 px-3.5 py-2.5 text-[13px] transition"
                                :class="active === '{{ $tab['slug'] }}'
                                    ? 'border-b-2 border-brand-500 font-semibold text-[#f6ecd9]'
                                    : 'border-b-2 border-transparent text-[#9c927c] hover:text-[#d8c79d]'">
                                {{ $tab['city'] }}
                                @if ($tab['hq'])
                                    <span class="rounded-sm bg-brand-500 px-1.5 py-px text-[9px] tracking-wider text-white">HQ</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    {{-- Active detail --}}
                    @foreach ([
                        ['slug' => 'nairobi', 'address' => 'Sheffield House, Mombasa Road', 'suburb' => 'Industrial Area · Nairobi, Kenya · 00100', 'phone' => '+254 20 234 5600', 'email' => 'nairobi@sheffield.co.ke', 'hours' => 'Mon–Fri · 8:00 – 17:30 · Sat · 9:00 – 14:00', 'services' => ['Showroom', 'Warehouse', 'Service & Spares', 'Trade Counter']],
                        ['slug' => 'mombasa', 'address' => 'Nyerere Avenue, Plot 14',        'suburb' => 'Mombasa Island · Mombasa, Kenya · 80100',  'phone' => '+254 41 230 0120', 'email' => 'mombasa@sheffield.co.ke', 'hours' => 'Mon–Fri · 8:00 – 17:00 · Sat · 9:00 – 13:00', 'services' => ['Showroom', 'Service & Spares', 'Coastal Logistics']],
                        ['slug' => 'kampala', 'address' => 'Plot 42, Yusuf Lule Road',       'suburb' => 'Nakasero · Kampala, Uganda · P.O. Box 12044', 'phone' => '+256 414 250 600', 'email' => 'kampala@sheffield.co.ug', 'hours' => 'Mon–Fri · 8:30 – 17:30 · Sat · 9:00 – 13:00', 'services' => ['Showroom', 'Service & Spares']],
                        ['slug' => 'kigali',  'address' => 'KG 11 Avenue, Kacyiru',           'suburb' => 'Kacyiru · Kigali, Rwanda · P.O. Box 2640', 'phone' => '+250 788 305 600', 'email' => 'kigali@sheffield.rw',     'hours' => 'Mon–Fri · 8:00 – 17:00 · Sat · 9:00 – 13:00', 'services' => ['Showroom', 'Service']],
                    ] as $loc)
                        <div class="mt-5 flex-1" x-show="active === '{{ $loc['slug'] }}'">
                            <div class="font-serif text-xl text-[#f6ecd9]">{{ $loc['address'] }}</div>
                            <div class="mt-1 text-[13px] text-[#c9bea4]">{{ $loc['suburb'] }}</div>

                            <div class="mt-4 grid grid-cols-2 gap-2 text-[12.5px] text-[#d8c79d]">
                                <span class="inline-flex items-center gap-2"><flux:icon.phone variant="micro" class="size-3.5 text-brand-500" /> {{ $loc['phone'] }}</span>
                                <span class="inline-flex items-center gap-2"><flux:icon.chat-bubble-left-right variant="micro" class="size-3.5 text-brand-500" /> WhatsApp</span>
                                <span class="inline-flex items-center gap-2"><flux:icon.envelope variant="micro" class="size-3.5 text-brand-500" /> {{ $loc['email'] }}</span>
                                <span class="inline-flex items-center gap-2"><flux:icon.clock variant="micro" class="size-3.5 text-brand-500" /> Open today</span>
                            </div>

                            <div class="mt-3 rounded bg-white/8 px-3 py-2.5 text-[12.5px] leading-relaxed text-[#d8c79d]">
                                {{ $loc['hours'] }}
                            </div>

                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($loc['services'] as $s)
                                    <span class="rounded-full bg-white/8 px-2.5 py-1 text-[11px] text-[#d8c79d]">{{ $s }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="mt-6 flex gap-2">
                        <flux:button variant="primary" icon-trailing="arrow-right">Get directions</flux:button>
                        <flux:button class="!bg-white/8 !border-white/16 !text-[#f6ecd9]">Book a showroom visit</flux:button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- RFQ banner --}}
    <section class="shell pt-14">
        <div class="grid grid-cols-1 items-center gap-6 rounded-lg bg-ink p-9 text-white lg:grid-cols-[1fr_auto]" style="background: #0c1421">
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
                <flux:button variant="primary" class="!h-12 !px-6">Start a quote</flux:button>
                <flux:button class="!h-12 !px-6 !bg-transparent !border-white/20 !text-white">Book site visit</flux:button>
            </div>
        </div>
    </section>

    {{-- Newsletter band --}}
    <section class="shell pt-14"
        x-data="{ email: '', submitted: false, error: '', interests: ['new-products'],
                  toggle(i) { this.interests.includes(i) ? this.interests = this.interests.filter(x => x !== i) : this.interests.push(i) },
                  submit() { if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) { this.error = 'Enter a valid work email.'; return } this.error = ''; this.submitted = true } }">
        <div class="grid grid-cols-1 items-center gap-12 rounded-lg bg-brand-blue-700 px-14 py-12 text-[#f3eadd] lg:grid-cols-[1fr_1.2fr]">
            <div>
                <div class="text-[11.5px] font-bold tracking-[0.12em] text-brand-500 uppercase">The Sheffield Quarterly</div>
                <h2 class="mt-2 font-serif text-[clamp(28px,3vw,38px)] leading-tight font-normal text-[#f6ecd9]">
                    Catalog drops, project stories, trade-only offers — four times a year.
                </h2>
                <ul class="mt-4 flex gap-5 text-[12.5px] text-[#c9bea4]">
                    <li class="inline-flex items-center gap-1.5"><flux:icon.check variant="mini" class="size-3.5 text-brand-500" /> No spam</li>
                    <li class="inline-flex items-center gap-1.5"><flux:icon.check variant="mini" class="size-3.5 text-brand-500" /> 1-click unsubscribe</li>
                    <li class="inline-flex items-center gap-1.5"><flux:icon.check variant="mini" class="size-3.5 text-brand-500" /> 4,800+ trade subscribers</li>
                </ul>
            </div>
            <div>
                <template x-if="!submitted">
                    <form @submit.prevent="submit()">
                        <div class="flex gap-2">
                            <input type="email" x-model="email" placeholder="you@kitchen.co.ke"
                                class="h-13 flex-1 rounded border border-white/16 bg-white/6 px-3.5 text-[14.5px] text-[#f6ecd9] placeholder:text-[#9c927c] focus:border-brand-500 focus:ring-0 focus:outline-none" />
                            <flux:button type="submit" variant="primary" class="!h-12 !px-6">Subscribe</flux:button>
                        </div>
                        <div class="mt-2 text-[12.5px] text-brand-500" x-show="error" x-text="error"></div>
                        <div class="mt-3.5 flex flex-wrap gap-1.5">
                            @foreach ([['new-products', 'New products'], ['seasonal-catalogs', 'Catalogs'], ['trade-pricing', 'Trade offers'], ['projects', 'Projects']] as [$id, $label])
                                <button type="button" @click="toggle('{{ $id }}')"
                                    class="inline-flex h-6.5 items-center gap-1.5 rounded-full border px-3 text-[11.5px] font-medium transition"
                                    :class="interests.includes('{{ $id }}')
                                        ? 'bg-brand-500 border-brand-500 text-white'
                                        : 'bg-white/8 border-white/14 text-[#d8c79d] hover:bg-white/12'">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <p class="mt-3 text-[11px] text-[#9c927c]">
                            By subscribing you agree to our <a href="#" class="text-[#c9bea4] hover:text-white">Privacy Policy</a>.
                        </p>
                    </form>
                </template>
                <template x-if="submitted">
                    <div class="flex items-center gap-3.5 rounded bg-white/6 p-6">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-500 text-white">
                            <flux:icon.check variant="mini" class="size-5" />
                        </div>
                        <div>
                            <div class="font-serif text-xl text-[#f6ecd9]">You're on the list.</div>
                            <div class="mt-1 text-[13px] text-[#c9bea4]">
                                Confirmation sent to <strong class="text-[#f6ecd9]" x-text="email"></strong>.
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </section>
</div>
