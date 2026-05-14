@php
    use App\Enums\ProductType;
    use Illuminate\Support\Facades\Storage;
@endphp

@placeholder
    <div class="min-h-screen">
        {{-- Breadcrumb skeleton --}}
        <div class="bg-white border-b border-zinc-200 py-3">
            <div class="container mx-auto px-4 flex items-center gap-3">
                <flux:skeleton animate="shimmer" class="w-4 h-4" />
                <flux:skeleton animate="shimmer" class="w-14 h-4" />
                <flux:skeleton animate="shimmer" class="w-3 h-3" />
                <flux:skeleton animate="shimmer" class="w-12 h-4" />
                <flux:skeleton animate="shimmer" class="w-3 h-3" />
                <flux:skeleton animate="shimmer" class="w-24 h-4" />
                <flux:skeleton animate="shimmer" class="w-3 h-3" />
                <flux:skeleton animate="shimmer" class="w-32 h-4" />
            </div>
        </div>

        <div class="container @container/page mx-auto px-4 py-4">
            <div class="grid @4xl/page:grid-cols-5 gap-5">
                {{-- Main content area --}}
                <div class="@4xl/page:col-span-4 space-y-5">
                    <div class="@container/details grid grid-cols-1 @3xl/details:grid-cols-7 gap-5">
                        {{-- Image gallery skeleton --}}
                        <div class="@3xl/details:col-span-3">
                            <div class="@3xl/details:sticky @3xl/details:top-24">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start">
                                    {{-- Main image --}}
                                    <div class="w-full md:flex-1 md:min-w-0">
                                        <div class="aspect-square w-full">
                                            <flux:skeleton animate="shimmer" class="w-full h-full rounded-lg" />
                                        </div>
                                    </div>
                                    {{-- Thumbnails — below on mobile, left on md+ --}}
                                    <div class="flex gap-2 md:flex-col md:shrink-0 md:w-20 md:order-first">
                                        @for ($i = 0; $i < 4; $i++)
                                            <flux:skeleton animate="shimmer" class="size-20 rounded-sm shrink-0" />
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Product details skeleton --}}
                        <div class="@3xl/details:col-span-4 space-y-4">
                            {{-- Title --}}
                            <flux:skeleton animate="shimmer" class="w-3/4 h-8" />

                            {{-- Brand + Rating --}}
                            <div class="flex items-center justify-between flex-wrap gap-3">
                                <flux:skeleton animate="shimmer" class="w-24 h-5" />
                                <div class="flex items-center gap-2">
                                    @for ($i = 0; $i < 5; $i++)
                                        <flux:skeleton animate="shimmer" class="w-4 h-4" />
                                    @endfor
                                    <flux:skeleton animate="shimmer" class="w-16 h-4" />
                                </div>
                            </div>

                            {{-- Short description --}}
                            <div class="space-y-2">
                                <flux:skeleton animate="shimmer" class="w-full h-4" />
                                <flux:skeleton animate="shimmer" class="w-5/6 h-4" />
                                <flux:skeleton animate="shimmer" class="w-4/6 h-4" />
                            </div>

                            {{-- SKU --}}
                            <flux:skeleton animate="shimmer" class="w-32 h-4" />

                            {{-- Variant selector skeleton --}}
                            <div class="space-y-3">
                                <flux:skeleton animate="shimmer" class="w-20 h-5" />
                                <div class="flex flex-wrap gap-2">
                                    @for ($i = 0; $i < 5; $i++)
                                        <flux:skeleton animate="shimmer" class="w-16 h-9 rounded-md" />
                                    @endfor
                                </div>
                            </div>

                            {{-- Price + Stock --}}
                            <flux:skeleton animate="shimmer" class="w-36 h-8" />
                            <flux:skeleton animate="shimmer" class="w-28 h-5" />
                        </div>
                    </div>

                    {{-- Tabs skeleton --}}
                    <div class="bg-white rounded-lg border">
                        <div class="border-b flex gap-4 px-6 py-3">
                            @for ($i = 0; $i < 4; $i++)
                                <flux:skeleton animate="shimmer" class="w-20 h-6" />
                            @endfor
                        </div>
                        <div class="p-6 space-y-3">
                            @for ($i = 0; $i < 6; $i++)
                                <flux:skeleton animate="shimmer" class="w-full h-4" />
                            @endfor
                        </div>
                    </div>
                </div>

                {{-- Sidebar skeleton --}}
                <div class="@4xl/page:col-span-1 border border-zinc-200 rounded p-4 h-fit">
                    {{-- Policy links --}}
                    @for ($i = 0; $i < 3; $i++)
                        <div class="flex items-center justify-between py-1.5">
                            <div class="flex items-center gap-2">
                                <flux:skeleton animate="shimmer" class="size-4 shrink-0" />
                                <flux:skeleton animate="shimmer" class="w-32 h-4" />
                            </div>
                            <flux:skeleton animate="shimmer" class="size-4" />
                        </div>
                    @endfor

                    <flux:skeleton animate="shimmer" class="w-full h-px my-2" />

                    {{-- Quantity stepper --}}
                    <div class="space-y-2 mb-3">
                        <flux:skeleton animate="shimmer" class="w-16 h-4" />
                        <div class="flex items-center gap-1">
                            <flux:skeleton animate="shimmer" class="w-9 h-9 rounded" />
                            <flux:skeleton animate="shimmer" class="w-10 h-5" />
                            <flux:skeleton animate="shimmer" class="w-9 h-9 rounded" />
                        </div>
                    </div>

                    {{-- Primary cart button --}}
                    <flux:skeleton animate="shimmer" class="w-full h-10 rounded-md" />

                    {{-- Secondary action buttons --}}
                    <div class="flex items-center gap-2 mt-2">
                        <flux:skeleton animate="shimmer" class="w-10 h-10 rounded-md" />
                        <flux:skeleton animate="shimmer" class="w-10 h-10 rounded-md" />
                        <flux:skeleton animate="shimmer" class="w-10 h-10 rounded-md" />
                    </div>
                </div>
            </div>
        </div>
    </div>
@endplaceholder

<div>
    <div class="bg-white border-b border-zinc-200 py-3">
        <div class="container mx-auto px-4 overflow-x-auto scrollbar-none">
            <flux:breadcrumbs class="flex-nowrap whitespace-nowrap min-w-max">
                <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                    Home
                </flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('shop.index') }}" wire:navigate>
                    Shop
                </flux:breadcrumbs.item>
                @if ($this->primaryCategory)
                    <flux:breadcrumbs.item
                        href="{{ route('shop.category', ['category' => $this->primaryCategory->slug]) }}"
                        wire:navigate>
                        {{ $this->primaryCategory->name }}
                    </flux:breadcrumbs.item>
                @endif
                <flux:breadcrumbs.item>{{ $product->name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="container @container/page mx-auto px-4 py-4">
        <div class="grid @4xl/page:grid-cols-5 gap-5">

            <div class="@4xl/page:col-span-4 space-y-5">

                <div class="@container/details grid grid-cols-1 @3xl/details:grid-cols-7 gap-5">

                    {{-- ═══════════════════════════════════════════════════ --}}
                    {{-- IMAGE SLIDER (SHARED BETWEEN BOTH TYPES)           --}}
                    {{-- ═══════════════════════════════════════════════════ --}}
                    <div class="@3xl/details:col-span-3">
                        @if (count($this->imageSlides) > 0)
                            <div wire:ignore x-data="{
                                mainSwiper: null,
                                thumbSwiper: null,
                                activeIndex: 0,
                                isMobile: window.innerWidth < 768,
                                init() {
                                    const sliderId = '{{ $product->type->value === 'grouped' ? 'grouped' : 'main' }}';
                            
                                    this.$nextTick(() => this.initSwipers(sliderId));
                            
                                    window.addEventListener('resize', () => {
                                        const nowMobile = window.innerWidth < 768;
                                        if (nowMobile !== this.isMobile) {
                                            this.isMobile = nowMobile;
                                            this.$nextTick(() => this.initSwipers(sliderId));
                                        }
                                    });
                            
                                    window.addEventListener('variant-image-selected', (e) => {
                                        if (this.mainSwiper) this.mainSwiper.slideTo(e.detail.index);
                                    });
                                },
                                initSwipers(sliderId) {
                                    const thumbEl = document.getElementById(sliderId + 'ThumbSwiper');
                                    const mainEl = document.getElementById(sliderId + 'MainSwiper');
                            
                                    if (this.mainSwiper) {
                                        this.mainSwiper.destroy(true, true);
                                        this.mainSwiper = null;
                                    }
                                    if (this.thumbSwiper) {
                                        this.thumbSwiper.destroy(true, true);
                                        this.thumbSwiper = null;
                                    }
                            
                                    if (thumbEl) {
                                        if (this.isMobile) {
                                            thumbEl.style.height = '';
                                        } else {
                                            const mainContainer = mainEl?.closest('.aspect-square');
                                            if (mainContainer) {
                                                thumbEl.style.height = mainContainer.offsetHeight + 'px';
                                            }
                                        }
                            
                                        this.thumbSwiper = new Swiper('#' + sliderId + 'ThumbSwiper', {
                                            direction: this.isMobile ? 'horizontal' : 'vertical',
                                            slidesPerView: 'auto',
                                            spaceBetween: 8,
                                            freeMode: true,
                                            watchSlidesProgress: true,
                                            mousewheel: true,
                                        });
                                    }
                            
                                    const mainSwiperOptions = {
                                        spaceBetween: 0,
                                        on: {
                                            slideChange: (swiper) => {
                                                this.activeIndex = swiper.activeIndex;
                                            },
                                        },
                                    };
                            
                                    if (this.thumbSwiper) {
                                        mainSwiperOptions.thumbs = { swiper: this.thumbSwiper };
                                    }
                            
                                    this.mainSwiper = new Swiper('#' + sliderId + 'MainSwiper', mainSwiperOptions);
                            
                                    if (thumbEl) thumbEl.classList.remove('opacity-0');
                                    if (mainEl) mainEl.classList.remove('opacity-0');
                                },
                            }" class="@3xl/details:sticky @3xl/details:top-24">

                                {{-- Flex col on mobile (thumbs below), flex row on md+ (thumbs left) --}}
                                <div class="flex flex-col gap-3 md:flex-row md:items-stretch">

                                    {{-- THUMBNAILS — horizontal strip on mobile (bottom), vertical on md+ (left) --}}
                                    @if (count($this->imageSlides) > 1)
                                        <div class="swiper order-last md:order-first opacity-0 transition-opacity duration-500 overflow-hidden h-20 md:h-auto md:shrink-0 md:w-20"
                                            id="{{ $product->type->value === 'grouped' ? 'grouped' : 'main' }}ThumbSwiper">
                                            <div class="swiper-wrapper">
                                                @foreach ($this->imageSlides as $index => $slide)
                                                    <div class="swiper-slide cursor-pointer overflow-hidden rounded-sm bg-white border-2 transition-all size-20"
                                                        :class="activeIndex === {{ $index }} ?
                                                            'border-primary' :
                                                            'border-transparent'">
                                                        <x-webp-image :src="$slide['url']" :webp="$slide['webp'] ?? null"
                                                            alt="{{ $slide['alt'] }}"
                                                            class="w-full h-full object-contain p-1" />
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- MAIN IMAGE —  fills remaining width, same 460px height --}}
                                    <div class="flex-1 min-w-0 bg-white aspect-square w-full overflow-hidden">
                                        <div class="swiper w-full h-full opacity-0 transition-opacity duration-500"
                                            id="{{ $product->type->value === 'grouped' ? 'grouped' : 'main' }}MainSwiper">
                                            <div class="swiper-wrapper">
                                                @foreach ($this->imageSlides as $slide)
                                                    <div class="swiper-slide flex items-start justify-center">
                                                        <x-webp-image :src="$slide['url']" :webp="$slide['webp'] ?? null"
                                                            alt="{{ $slide['alt'] }}"
                                                            class="w-full h-auto max-h-full object-contain" />
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        @else
                            {{-- No image placeholder --}}
                            <div class="@3xl/details:sticky @3xl/details:top-24">
                                <div
                                    class="bg-zinc-100 dark:bg-zinc-800 aspect-square w-full rounded-lg flex items-center justify-center">
                                    <div class="text-center text-zinc-400 dark:text-zinc-500">
                                        <flux:icon.photo class="w-16 h-16 mx-auto mb-2" />
                                        <p class="text-sm">No image available</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- ═══════════════════════════════════════════════════ --}}
                    {{-- PRODUCT DETAILS (DIFFERENT FOR EACH TYPE)          --}}
                    {{-- ═══════════════════════════════════════════════════ --}}
                    <div class="@3xl/details:col-span-4 space-y-4">

                        {{-- SHARED HEADER SECTION --}}
                        {{-- Name --}}
                        <flux:heading level="1"
                            class="text-xl! sm:text-2xl! lg:text-3xl! font-bold! text-zinc-900 dark:text-zinc-100 leading-tight">
                            {{ $product->name }}
                        </flux:heading>

                        {{-- Brand + Rating --}}
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            @if ($product->brand)
                                <div class="flex items-center gap-2">
                                    <span class="text-zinc-500 text-xs sm:text-sm">Brand:</span>
                                    <span
                                        class="text-secondary font-medium text-xs sm:text-sm">{{ $product->brand->name }}</span>
                                </div>
                            @endif

                            <div class="flex items-center gap-2">
                                @php $avgRating = $product->reviews_avg_rating ?? 0; @endphp
                                <div class="flex items-center gap-0.5">
                                    @for ($i = 0; $i < 5; $i++)
                                        @if ($avgRating >= $i + 1)
                                            <flux:icon.star variant="solid" class="w-4 h-4 text-yellow-400" />
                                        @elseif ($avgRating > $i)
                                            <div class="relative w-4 h-4">
                                                <flux:icon.star variant="solid" class="w-4 h-4 text-zinc-300" />
                                                <div class="absolute inset-0 overflow-hidden w-1/2">
                                                    <flux:icon.star variant="solid" class="w-4 h-4 text-yellow-400" />
                                                </div>
                                            </div>
                                        @else
                                            <flux:icon.star variant="solid" class="w-4 h-4 text-zinc-300" />
                                        @endif
                                    @endfor
                                </div>
                                <span
                                    class="text-xs sm:text-sm text-zinc-500">({{ number_format($avgRating, 1) }})</span>
                                <a href="{{ route('products.reviews', $product) }}" wire:navigate
                                    class="text-xs sm:text-sm text-secondary hover:underline">
                                    {{ $this->reviewStats['total'] }} reviews
                                </a>
                            </div>
                        </div>

                        {{-- Short description --}}
                        @if ($product->short_description)
                            <div class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                {!! $product->short_description !!}
                            </div>
                        @endif

                        {{-- TYPE-SPECIFIC CONTENT --}}
                        @if ($product->type->value === 'grouped')
                            {{-- ══════════════════════════════════════════════ --}}
                            {{-- GROUPED PRODUCT: BANNER CARD                   --}}
                            {{-- ══════════════════════════════════════════════ --}}
                            @php
                                $groupedPriceRange = $this->groupedPriceRange;
                                $hasGroupedRange = $groupedPriceRange['min'] > 0 && $groupedPriceRange['max'] > 0;
                                $sameGroupedPrice = $groupedPriceRange['min'] === $groupedPriceRange['max'];
                            @endphp
                            <div id="main-product-price"
                                x-intersect:enter="window.dispatchEvent(new CustomEvent('price-in-view'))"
                                x-intersect:leave="window.dispatchEvent(new CustomEvent('price-out-of-view'))"
                                class="relative overflow-hidden rounded-lg bg-linear-to-r from-secondary/10 to-secondary/5 border border-secondary/20">
                                {{-- Decorative icon --}}
                                <div class="absolute -right-2 -top-2 opacity-10">
                                    <flux:icon.squares-2x2 class="size-16 text-secondary" />
                                </div>

                                <div class="relative p-3">
                                    {{-- Title --}}
                                    <p class="text-sm font-semibold text-secondary mb-1">
                                        Kit Contains {{ $this->groupedProducts->count() }}
                                        {{ Str::plural('Item', $this->groupedProducts->count()) }}
                                    </p>

                                    {{-- Price Range --}}
                                    @if ($hasGroupedRange)
                                        <div class="flex items-baseline gap-2 mb-2">
                                            @if ($sameGroupedPrice)
                                                <span
                                                    class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ format_currency($groupedPriceRange['min']) }}</span>
                                                <span class="text-sm text-zinc-500">each</span>
                                            @else
                                                <span
                                                    class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ format_currency($groupedPriceRange['min']) }}</span>
                                                <span class="text-sm text-zinc-500">—</span>
                                                <span
                                                    class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ format_currency($groupedPriceRange['max']) }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- View Button --}}
                                    <button type="button" x-data @click="$flux.modal('kit-contents-modal').show()"
                                        class="inline-flex items-center gap-1.5 text-sm font-medium text-secondary hover:text-secondary-hover transition-colors cursor-pointer">
                                        View & select items
                                        <flux:icon.chevron-right class="size-4" />
                                    </button>
                                </div>
                            </div>
                        @elseif ($product->type->value === 'bundle')
                            {{-- ══════════════════════════════════════════════ --}}
                            {{-- BUNDLE PRODUCT: BANNER CARD WITH SAVINGS      --}}
                            {{-- ══════════════════════════════════════════════ --}}
                            @php
                                $bundlePrice = $product->sale_price ?? $product->price;
                                $bundleValue = $this->bundleValue;
                                $savings = $this->bundleSavingsPercent;
                                $bundlePriceRange = $this->bundlePriceRange;
                            @endphp
                            <div id="main-product-price"
                                x-intersect:enter="window.dispatchEvent(new CustomEvent('price-in-view'))"
                                x-intersect:leave="window.dispatchEvent(new CustomEvent('price-out-of-view'))"
                                class="relative overflow-hidden rounded-lg bg-linear-to-r from-green-500/10 to-green-500/5 border border-green-500/20">
                                {{-- Decorative icon --}}
                                <div class="absolute -right-2 -top-2 opacity-10">
                                    <flux:icon.gift class="size-16 text-green-600" />
                                </div>

                                <div class="relative p-3">
                                    {{-- Title with savings badge --}}
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="text-sm font-semibold text-green-700 dark:text-green-500">
                                            Bundle Deal
                                        </p>
                                        @if ($savings)
                                            <flux:badge color="green" size="sm">Save {{ $savings }}%
                                            </flux:badge>
                                        @endif
                                    </div>

                                    {{-- Bundle price --}}
                                    @if ($bundlePrice)
                                        <div class="flex items-baseline gap-2 mb-1">
                                            <span
                                                class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ format_currency($bundlePrice) }}</span>
                                            @if ($bundleValue > $bundlePrice)
                                                <span
                                                    class="text-sm text-zinc-400 line-through">{{ format_currency($bundleValue) }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Items count --}}
                                    <p class="text-xs text-zinc-500 mb-2">
                                        Includes {{ $this->bundleProducts->count() }}
                                        {{ Str::plural('item', $this->bundleProducts->count()) }}
                                    </p>

                                    {{-- View Button --}}
                                    <button type="button" x-data @click="$flux.modal('bundle-contents-modal').show()"
                                        class="inline-flex items-center gap-1.5 text-sm font-medium text-green-700 dark:text-green-500 hover:text-green-800 dark:hover:text-green-400 transition-colors cursor-pointer">
                                        View bundle contents
                                        <flux:icon.chevron-right class="size-4" />
                                    </button>
                                </div>
                            </div>
                        @else
                            {{-- ══════════════════════════════════════════════ --}}
                            {{-- REGULAR PRODUCT: VARIANTS, PRICE, CART        --}}
                            {{-- ══════════════════════════════════════════════ --}}

                            {{-- SKU --}}
                            @php $displaySku = $this->selectedVariant?->sku ?? $product->sku; @endphp
                            @if ($displaySku)
                                <p class="text-xs text-zinc-500">
                                    Item no: <span class="text-zinc-700 dark:text-zinc-300">{{ $displaySku }}</span>
                                </p>
                            @endif

                            {{-- VARIANT SELECTOR --}}
                            @if ($product->type->value === 'variable')
                                <div class="space-y-3">
                                    @foreach ($this->variationAttributes as $attribute)
                                        @php
                                            $watchType = $attribute['watch_type'] ?? 'label';
                                        @endphp
                                        <div class="space-y-1.5">
                                            <p class="text-xs sm:text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ $attribute['name'] }}
                                                @if (!empty($selectedAttributeValues[$attribute['name']]))
                                                    <span class="font-normal text-zinc-500">
                                                        : {{ $selectedAttributeValues[$attribute['name']] }}
                                                    </span>
                                                @endif
                                            </p>

                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($attribute['values'] as $value)
                                                    @php
                                                        $isSelected =
                                                            ($selectedAttributeValues[$attribute['name']] ?? null) ===
                                                            $value['value'];
                                                        $state = $value['state'];
                                                    @endphp

                                                    @if ($state === 'unavailable')
                                                        @continue
                                                    @endif

                                                    {{-- COLOR SWATCH --}}
                                                    @if ($watchType === 'color' && $value['color_code'])
                                                        <button type="button"
                                                            wire:click="selectAttributeValue('{{ $attribute['name'] }}', '{{ $value['value'] }}')"
                                                            class="relative w-9 h-9 rounded-full transition-all cursor-pointer {{ $isSelected ? 'ring-2 ring-offset-2 ring-secondary' : 'hover:ring-2 hover:ring-offset-2 hover:ring-zinc-300' }} {{ $state === 'backorder' ? 'ring-amber-400' : '' }}"
                                                            title="{{ $value['label'] }}{{ $state === 'out_of_stock' ? ' (out of stock)' : ($state === 'backorder' ? ' (backorder)' : '') }}">
                                                            <span
                                                                class="absolute inset-0.5 rounded-full border border-zinc-200 {{ $state === 'out_of_stock' ? 'opacity-50' : '' }}"
                                                                style="background-color: {{ $value['color_code'] }};"></span>
                                                            @if ($state === 'out_of_stock')
                                                                <span
                                                                    class="absolute inset-0 flex items-center justify-center">
                                                                    <span
                                                                        class="w-full h-0.5 bg-zinc-400 rotate-45 absolute"></span>
                                                                </span>
                                                            @elseif ($state === 'backorder')
                                                                <span
                                                                    class="absolute -top-1 -right-1 w-3 h-3 bg-amber-400 rounded-full border border-white"></span>
                                                            @endif
                                                        </button>

                                                        {{-- IMAGE SWATCH --}}
                                                    @elseif ($watchType === 'image' && $value['image_path'])
                                                        <button type="button"
                                                            wire:click="selectAttributeValue('{{ $attribute['name'] }}', '{{ $value['value'] }}')"
                                                            class="relative w-12 h-12 rounded-md border-2 transition-all cursor-pointer overflow-hidden {{ $isSelected ? 'border-secondary ring-1 ring-secondary' : 'border-zinc-200 hover:border-zinc-400' }} {{ $state === 'backorder' ? 'border-amber-400' : '' }}"
                                                            title="{{ $value['label'] }}{{ $state === 'out_of_stock' ? ' (out of stock)' : ($state === 'backorder' ? ' (backorder)' : '') }}">
                                                            <img src="{{ Storage::url($value['image_path']) }}"
                                                                alt="{{ $value['label'] }}"
                                                                class="w-full h-full object-cover {{ $state === 'out_of_stock' ? 'opacity-50' : '' }}" />
                                                            @if ($state === 'out_of_stock')
                                                                <span
                                                                    class="absolute inset-0 flex items-center justify-center bg-white/30">
                                                                    <span
                                                                        class="w-full h-0.5 bg-zinc-400 rotate-45 absolute"></span>
                                                                </span>
                                                            @elseif ($state === 'backorder')
                                                                <span
                                                                    class="absolute -top-1 -right-1 w-3 h-3 bg-amber-400 rounded-full border border-white"></span>
                                                            @endif
                                                        </button>

                                                        {{-- SELECT DROPDOWN --}}
                                                    @elseif ($watchType === 'select')
                                                        {{-- Handled outside the loop --}}
                                                        @continue

                                                        {{-- DEFAULT: LABEL/BUTTON --}}
                                                    @else
                                                        <button type="button"
                                                            wire:click="selectAttributeValue('{{ $attribute['name'] }}', '{{ $value['value'] }}')"
                                                            @class([
                                                                'px-3 py-1.5 text-sm border rounded-md transition-all cursor-pointer',
                                                                // Selected states
                                                                'border-secondary bg-secondary/5 text-secondary font-medium' =>
                                                                    $isSelected && $state === 'available',
                                                                'border-amber-500 bg-amber-50 text-amber-700 font-medium' =>
                                                                    $isSelected && $state === 'backorder',
                                                                'border-zinc-400 bg-zinc-100 text-zinc-500 font-medium' =>
                                                                    $isSelected && $state === 'out_of_stock',
                                                                // Unselected states
                                                                'border-zinc-300 text-zinc-700 hover:border-zinc-400 dark:border-zinc-600 dark:text-zinc-300' =>
                                                                    !$isSelected && $state === 'available',
                                                                'border-amber-300 text-amber-600 hover:border-amber-500 bg-amber-50/50' =>
                                                                    !$isSelected && $state === 'backorder',
                                                                'border-zinc-200 text-zinc-400 hover:border-zinc-300 bg-zinc-50' =>
                                                                    !$isSelected && $state === 'out_of_stock',
                                                            ])>
                                                            <span
                                                                class="{{ $state === 'out_of_stock' ? 'line-through' : '' }}">{{ $value['label'] }}</span>
                                                            @if ($state === 'backorder')
                                                                <span
                                                                    class="text-xs opacity-75 ml-1">(backorder)</span>
                                                            @endif
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>

                                            {{-- SELECT DROPDOWN (rendered separately for select watch_type) --}}
                                            @if ($watchType === 'select')
                                                <flux:select
                                                    wire:change="selectAttributeValue('{{ $attribute['name'] }}', $event.target.value)"
                                                    class="w-full max-w-xs">
                                                    <option value="">Select {{ $attribute['name'] }}</option>
                                                    @foreach ($attribute['values'] as $value)
                                                        @if ($value['state'] === 'unavailable')
                                                            @continue
                                                        @endif
                                                        <option value="{{ $value['value'] }}"
                                                            {{ ($selectedAttributeValues[$attribute['name']] ?? null) === $value['value'] ? 'selected' : '' }}>
                                                            {{ $value['label'] }}
                                                            @if ($value['state'] === 'backorder')
                                                                (backorder)
                                                            @elseif ($value['state'] === 'out_of_stock')
                                                                (out of stock)
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </flux:select>
                                            @endif
                                        </div>
                                    @endforeach

                                    {{-- No combination match warning --}}
                                    @if (!empty($selectedAttributeValues) && !$selectedVariantId)
                                        <div
                                            class="flex items-center gap-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                                            <flux:icon.exclamation-triangle class="size-4 shrink-0" />
                                            This combination is not available.
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- PRICE --}}
                            @if (!$product->requires_quotation)
                                <div id="main-product-price"
                                    x-intersect:enter="window.dispatchEvent(new CustomEvent('price-in-view'))"
                                    x-intersect:leave="window.dispatchEvent(new CustomEvent('price-out-of-view'))">
                                    @php
                                        $displaySource = $this->selectedVariant ?? $product;
                                        $regularPrice = $displaySource->price;
                                        $salePrice = $displaySource->sale_price;
                                        $finalPrice = $salePrice ?? $regularPrice;
                                        $hasDiscount = $salePrice && $regularPrice && $salePrice < $regularPrice;
                                    @endphp

                                    @if ($finalPrice)
                                        @if ($hasDiscount)
                                            <div class="flex items-center flex-wrap gap-2">
                                                <span class="text-xl sm:text-2xl font-bold text-secondary">
                                                    {{ format_currency($salePrice) }}
                                                </span>
                                                <span class="text-sm sm:text-base text-zinc-400 line-through">
                                                    {{ format_currency($regularPrice) }}
                                                </span>
                                                <flux:badge color="amber" size="sm">
                                                    -{{ number_format((($regularPrice - $salePrice) / $regularPrice) * 100) }}%
                                                </flux:badge>
                                            </div>
                                        @else
                                            <span class="text-xl sm:text-2xl font-bold text-secondary">
                                                {{ format_currency($finalPrice) }}
                                            </span>
                                        @endif
                                    @elseif ($product->type->value === 'variable' && !$selectedVariantId)
                                        <span class="text-sm sm:text-base text-zinc-400">Select options to see
                                            price</span>
                                    @endif

                                    {{-- STOCK STATUS --}}
                                    @php
                                        $state =
                                            $product->type->value === 'variable'
                                                ? $this->selectedVariantState
                                                : $this->simpleProductState;
                                        $variant = $this->selectedVariant;
                                        $source = $variant ?? $product;
                                    @endphp

                                    @if ($state === 'none')
                                        <p class="text-xs sm:text-sm text-zinc-400 mt-1">Select options to see
                                            availability
                                        </p>
                                    @elseif ($state === 'available')
                                        <p class="text-xs sm:text-sm text-green-600 mt-1 flex items-center gap-1">
                                            <flux:icon.check-circle class="size-4" />
                                            In Stock
                                            @if ($source->manage_stock && $source->stock_quantity > 0)
                                                ({{ $source->stock_quantity }} available)
                                            @endif
                                        </p>
                                    @elseif ($state === 'backorder')
                                        <p class="text-xs sm:text-sm text-amber-600 mt-1 flex items-center gap-1">
                                            <flux:icon.clock class="size-4" />
                                            Available on backorder
                                        </p>
                                        @php
                                            $backorderMsg = $source->backorder_message ?? null;
                                            $restockDate =
                                                $source instanceof \App\Models\ProductVariant
                                                    ? $source->expected_restock_date
                                                    : $product->expected_restock_date;
                                        @endphp
                                        @if ($backorderMsg || $restockDate)
                                            <div
                                                class="mt-2 bg-amber-50 border border-amber-200 rounded-md px-3 py-2.5 text-xs sm:text-sm text-amber-800">
                                                @if ($backorderMsg)
                                                    <p>{{ $backorderMsg }}</p>
                                                @endif
                                                @if ($restockDate)
                                                    <p class="text-xs text-amber-600 mt-1 flex items-center gap-1">
                                                        <flux:icon.calendar class="size-3.5" />
                                                        Expected back in stock: {{ $restockDate->format('d M Y') }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <p class="text-xs sm:text-sm text-red-500 mt-1 flex items-center gap-1">
                                            <flux:icon.x-circle class="size-4" />
                                            Out of Stock
                                        </p>
                                    @endif
                                </div>
                            @endif


                            @if ($this->accessories->count() > 0)
                                @php
                                    $priceRange = $this->accessoryPriceRange;
                                    $hasRange = $priceRange['min'] > 0 && $priceRange['max'] > 0;
                                    $samePrice = $priceRange['min'] === $priceRange['max'];
                                @endphp
                                <div
                                    class="relative overflow-hidden rounded-lg bg-linear-to-r from-secondary/10 to-secondary/5 border border-secondary/20">
                                    {{-- Decorative icon --}}
                                    <div class="absolute -right-2 -top-2 opacity-10">
                                        <flux:icon.wrench-screwdriver class="size-16 text-secondary" />
                                    </div>

                                    <div class="relative p-3">
                                        {{-- Title --}}
                                        <p class="text-sm font-semibold text-secondary mb-1">
                                            {{ $this->accessories->count() }}
                                            {{ Str::plural('Accessory', $this->accessories->count()) }} Available
                                        </p>

                                        {{-- Price Range --}}
                                        @if ($hasRange)
                                            <div class="flex items-baseline gap-2 mb-2">
                                                @if ($samePrice)
                                                    <span
                                                        class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ format_currency($priceRange['min']) }}</span>
                                                @else
                                                    <span
                                                        class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ format_currency($priceRange['min']) }}</span>
                                                    <span class="text-sm text-zinc-500">—</span>
                                                    <span
                                                        class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ format_currency($priceRange['max']) }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- View Button --}}
                                        <button type="button" x-data @click="$flux.modal('accessories-modal').show()"
                                            class="inline-flex items-center gap-1.5 text-sm font-medium text-secondary hover:text-secondary-hover transition-colors cursor-pointer">
                                            View accessories
                                            <flux:icon.chevron-right class="size-4" />
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- TABS SECTION --}}
                {{-- wire:ignore prevents Livewire morphing from breaking Alpine.js tab state --}}
                <div wire:ignore x-data="{ activeTab: 'description' }" class="@container/tabs relative mt-10">
                    <div
                        class="bg-white dark:bg-white/10 border border-zinc-200 dark:border-white/10 rounded-md pb-4 pt-8 px-4 @md/tabs:pb-6 @md/tabs:pt-10 @md/tabs:px-6">

                        {{-- Tab Buttons --}}
                        <div
                            class="flex items-center gap-2 absolute top-0 left-0 -translate-y-1/2 rounded-b-sm rounded-tr-sm">

                            {{-- Description Tab --}}
                            <flux:button x-show="activeTab === 'description'" type="button" size="customer-lg"
                                variant="customer-primary" class="cursor-pointer">
                                Description
                            </flux:button>
                            <flux:button x-show="activeTab !== 'description'" x-cloak type="button"
                                size="customer-lg" x-on:click="activeTab = 'description'" class="cursor-pointer">
                                Description
                            </flux:button>

                            {{-- Specification Tab --}}
                            <flux:button x-show="activeTab === 'specification'" type="button" size="customer-lg"
                                variant="customer-primary" class="cursor-pointer">
                                Specification
                            </flux:button>
                            <flux:button x-show="activeTab !== 'specification'" x-cloak type="button"
                                size="customer-lg" x-on:click="activeTab = 'specification'" class="cursor-pointer">
                                Specification
                            </flux:button>

                            @if ($this->product->reviews_enabled && app(\App\Settings\ReviewSettings::class)->reviews_enabled)
                                {{-- Reviews Tab --}}
                                <flux:button x-show="activeTab === 'reviews'" type="button" size="customer-lg"
                                    variant="customer-primary" class="cursor-pointer">
                                    Reviews
                                </flux:button>
                                <flux:button x-show="activeTab !== 'reviews'" x-cloak type="button"
                                    size="customer-lg" x-on:click="activeTab = 'reviews'" class="cursor-pointer">
                                    Reviews
                                </flux:button>
                            @endif

                        </div>

                        {{-- Tab Content: Description --}}
                        <template x-if="activeTab === 'description'">
                            <div>
                                <div class="text-xs sm:text-sm text-zinc-500 tracking-wider leading-6">
                                    {!! $product->description !!}
                                </div>
                            </div>
                        </template>

                        {{-- Tab Content: Specification --}}
                        <template x-if="activeTab === 'specification'">
                            <div>
                                @if (!empty($product->technical_specification))
                                    <div class="text-xs sm:text-sm text-zinc-500 tracking-wider leading-6">
                                        {!! $product->technical_specification !!}
                                    </div>
                                @else
                                    <p class="text-xs sm:text-sm text-zinc-500">No specifications available for this
                                        product.
                                    </p>
                                @endif
                            </div>
                        </template>

                        {{-- Tab Content: Reviews --}}
                        @if ($this->product->reviews_enabled && app(\App\Settings\ReviewSettings::class)->reviews_enabled)
                            <template x-if="activeTab === 'reviews'">
                                <div>
                                    <flux:heading level="4" class="font-bold! mb-6 text-base! sm:text-lg!">
                                        Customer
                                        Ratings</flux:heading>

                                    <div class="@container/reviews grid grid-cols-1 @2xl/reviews:grid-cols-4 gap-7">

                                        {{-- ── Rating Distribution ── --}}
                                        <div class="col-span-1">
                                            <div class="sticky top-44">
                                                <div class="text-center">
                                                    <div class="text-2xl sm:text-3xl font-bold text-secondary">
                                                        {{ $this->reviewStats['average'] }}
                                                    </div>

                                                    <div class="flex justify-center gap-1 mt-1">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            @if ($i <= floor($this->reviewStats['average']))
                                                                <flux:icon.star
                                                                    class="size-5 text-orange-400 fill-current" />
                                                            @elseif ($i - 0.5 <= $this->reviewStats['average'])
                                                                <svg class="w-5 h-5 text-orange-400"
                                                                    viewBox="0 0 20 20">
                                                                    <defs>
                                                                        <linearGradient id="half-star">
                                                                            <stop offset="50%"
                                                                                stop-color="currentColor" />
                                                                            <stop offset="50%"
                                                                                stop-color="#D1D5DB" />
                                                                        </linearGradient>
                                                                    </defs>
                                                                    <path fill="url(#half-star)"
                                                                        d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                                                </svg>
                                                            @else
                                                                <flux:icon.star
                                                                    class="size-5 text-zinc-300 fill-current" />
                                                            @endif
                                                        @endfor
                                                    </div>

                                                    <div class="text-xs sm:text-sm text-zinc-600 mt-1">
                                                        {{ $this->reviewStats['total'] }}
                                                        {{ Str::plural('review', $this->reviewStats['total']) }}
                                                    </div>
                                                </div>

                                                <flux:separator class="my-4" />

                                                <div class="space-y-2">
                                                    @foreach ($this->reviewStats['distribution'] as $rating => $data)
                                                        <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3">
                                                            <div class="flex gap-0.5">
                                                                @for ($star = 1; $star <= 5; $star++)
                                                                    @if ($star <= $rating)
                                                                        <flux:icon.star
                                                                            class="size-5 text-orange-400 fill-current" />
                                                                    @else
                                                                        <flux:icon.star
                                                                            class="size-5 text-zinc-300 fill-current" />
                                                                    @endif
                                                                @endfor
                                                            </div>
                                                            <div class="w-full bg-zinc-200 rounded-full h-2.5">
                                                                <div class="bg-secondary h-2.5 rounded-full"
                                                                    style="width: {{ $data['percentage'] }}%"></div>
                                                            </div>
                                                            <span
                                                                class="text-sm font-semibold text-secondary min-w-11.25">
                                                                {{ $data['percentage'] }}%
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ── Reviews List ── --}}
                                        <div class="col-span-1 @2xl/reviews:col-span-3">
                                            @if ($this->reviews->isEmpty())
                                                <div class="text-center py-8 text-zinc-500">
                                                    <p>No reviews yet. Be the first to review this product!</p>
                                                </div>
                                            @else
                                                <div class="space-y-6">
                                                    @foreach ($this->reviews as $review)
                                                        <livewire:review-item :review="$review" :key="'review-item-' . $review->id"
                                                            :user-vote="$this->userVotes->get($review->id)" />
                                                    @endforeach
                                                </div>

                                                @if ($this->hasMoreReviews)
                                                    <div class="mt-6 text-center">
                                                        <flux:button href="{{ route('products.reviews', $product) }}"
                                                            wire:navigate variant="customer-outline" size="customer">
                                                            View All {{ $this->reviewStats['total'] }} Reviews
                                                        </flux:button>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            </template>
                        @endif

                    </div>{{-- end tabs card --}}
                </div>{{-- end wire:ignore x-data activeTab --}}
            </div>

            {{-- DELIVERY SIDEBAR --}}
            <div class="@4xl/page:col-span-1 border border-zinc-200 dark:border-zinc-700 rounded h-fit sticky top-44 p-4">

                {{-- Price — slides in when main price scrolls out of view.
                     wire:ignore keeps Alpine state alive across Livewire re-renders. --}}
                <div wire:ignore x-data="{ priceVisible: false }" x-on:price-out-of-view.window="priceVisible = true"
                    x-on:price-in-view.window="priceVisible = false">
                    @if ($product->requires_quotation)
                        <div x-show="priceVisible" x-cloak x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="mb-3 pb-3 border-b border-zinc-200 dark:border-zinc-700">
                            <p class="text-xs text-zinc-400 uppercase tracking-wide mb-0.5">Price</p>
                            <span class="text-sm font-medium text-amber-600">Request a quote</span>
                        </div>
                    @elseif ($product->display_price)
                        <div x-show="priceVisible" x-cloak x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="mb-3 pb-3 border-b border-zinc-200 dark:border-zinc-700">
                            <p class="text-xs text-zinc-400 uppercase tracking-wide mb-0.5">Price</p>
                            <div class="flex items-baseline gap-1.5 flex-wrap">
                                @if ($product->has_price_prefix)
                                    <span class="text-xs text-zinc-400">{{ $product->display_price_prefix }}</span>
                                @endif
                                <span class="text-lg font-bold text-primary">{{ $product->display_price }}</span>
                                @if ($product->type === \App\Enums\ProductType::SIMPLE && $product->hasDiscount())
                                    <span
                                        class="text-xs text-zinc-400 line-through">{{ $product->formatted_price }}</span>
                                    <span
                                        class="text-xs font-medium text-green-600">-{{ $product->discountPercentage() }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Dynamic Policies --}}
                <div class="flex flex-col">
                    <a href="#" class="flex items-center justify-between py-1.5">
                        <div class="flex items-center gap-2">
                            <flux:icon.arrow-uturn-left class="size-4 text-primary" variant="outline" />
                            <span class="text-sm text-zinc-800 dark:text-zinc-100">Return & refund
                                policy</span>
                        </div>
                        <flux:icon.chevron-right class="size-4 text-zinc-400" />
                    </a>

                    <a href="#" class="flex items-center justify-between py-1.5">
                        <div class="flex items-center gap-2">
                            <flux:icon.shield-check class="size-4 text-primary" variant="outline" />
                            <span class="text-sm text-zinc-800 dark:text-zinc-100">Warranty
                                policy</span>
                        </div>
                        <flux:icon.chevron-right class="size-4 text-zinc-400" />
                    </a>

                    <a href="#" class="flex items-center justify-between py-1.5">
                        <div class="flex items-center gap-2">

                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                class="bi bi-shield-shaded size-4 text-primary" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M8 14.933a1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56" />
                            </svg>
                            <span class="text-sm text-zinc-800 dark:text-zinc-100">Secure
                                privacy</span>
                        </div>
                        <flux:icon.chevron-right class="size-4 text-zinc-400" />
                    </a>
                </div>

                <flux:separator class="my-2" />

                {{-- Actions --}}
                <div class="flex flex-col gap-3">
                    @if ($product->type->value === 'grouped')
                        {{-- Add to Cart for Grouped Products - Opens Modal --}}
                        <flux:button x-data @click="$flux.modal('kit-contents-modal').show()"
                            variant="customer-primary" size="customer-lg" class="w-full cursor-pointer"
                            icon="shopping-cart">
                            Add to Cart
                        </flux:button>
                    @elseif ($product->type->value === 'bundle')
                        {{-- Add to Cart for Bundle Products --}}
                        @if ($inCart)
                            {{-- Already in cart - show View Cart button --}}
                            <flux:button href="{{ route('cart') }}" wire:navigate variant="customer-primary"
                                size="customer-lg" class="w-full cursor-pointer" icon="shopping-cart">
                                View Cart
                            </flux:button>
                        @else
                            {{-- Not in cart - show Add to Cart which opens modal --}}
                            <flux:button x-data @click="$flux.modal('bundle-contents-modal').show()"
                                variant="customer-primary" size="customer-lg" class="w-full cursor-pointer"
                                icon="shopping-cart">
                                Add to Cart
                            </flux:button>
                        @endif
                    @else
                        {{-- Cart Actions for Regular Products --}}
                        @if ($product->requires_quotation)
                            <flux:button wire:click="addToQuoteBasket" variant="customer-primary" size="customer-lg"
                                class="w-full cursor-pointer" wire:loading.attr="disabled"
                                wire:target="addToQuoteBasket">
                                Add to Quote
                            </flux:button>

                            @if ($inQuoteBasket)
                                <flux:button href="{{ route('quote') }}" wire:navigate variant="customer-outline"
                                    size="customer-lg" class="w-full cursor-pointer">
                                    View Quote Basket
                                </flux:button>
                            @endif
                        @else
                            {{-- Quantity stepper — always visible when state is known --}}
                            @if ($state !== 'none')
                                <div class="mb-1">
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100 mb-2">Quantity</p>
                                    <div class="flex items-center gap-1">
                                        <button type="button" wire:click="decreaseCartQuantity"
                                            class="w-9 h-9 flex items-center justify-center rounded border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                            aria-label="Decrease quantity"
                                            @if ($state === 'out_of_stock') disabled @endif>
                                            <flux:icon.minus class="size-3.5" />
                                        </button>
                                        <span
                                            class="w-10 text-center text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                            {{ $cartQuantity }}
                                        </span>
                                        <button type="button" wire:click="increaseCartQuantity"
                                            class="w-9 h-9 flex items-center justify-center rounded border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                            aria-label="Increase quantity"
                                            @if ($state === 'out_of_stock') disabled @endif>
                                            <flux:icon.plus class="size-3.5" />
                                        </button>
                                    </div>

                                    @if ($inCart)
                                        <div class="mt-2 flex items-center gap-2 text-sm">
                                            <flux:icon.check-circle class="size-4 shrink-0 text-green-600" />
                                            <span class="text-green-600 font-medium">In Cart</span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Primary action button --}}
                            @if ($product->type === ProductType::VARIABLE && !$selectedVariantId)
                                <flux:button variant="customer-primary" size="customer-lg"
                                    class="w-full cursor-pointer" disabled>
                                    Select Options
                                </flux:button>
                            @elseif ($state === 'out_of_stock')
                                <flux:button variant="customer-outline" size="customer-lg"
                                    class="w-full cursor-not-allowed" disabled>
                                    Out of Stock
                                </flux:button>
                            @elseif ($inCart)
                                <flux:button href="{{ route('cart') }}" wire:navigate variant="customer-primary"
                                    size="customer-lg" class="w-full cursor-pointer" icon="shopping-cart">
                                    View Cart
                                </flux:button>
                            @elseif ($state === 'backorder')
                                <flux:button wire:click="addToCart" size="customer-lg"
                                    class="w-full cursor-pointer bg-amber-500! border-amber-500! hover:bg-amber-600! text-white!"
                                    wire:loading.attr="disabled" wire:target="addToCart">
                                    Pre-order
                                </flux:button>
                            @elseif ($state !== 'none')
                                <flux:button wire:click="addToCart" variant="customer-primary" size="customer-lg"
                                    class="w-full cursor-pointer" wire:loading.attr="disabled"
                                    wire:target="addToCart">
                                    Add to Cart
                                </flux:button>
                            @endif
                        @endif
                    @endif

                    {{-- Secondary Actions (Share / Wishlist) --}}
                    <div class="flex items-center gap-2 mt-2">
                        <div x-data="{
                            share() {
                                if (navigator.share) {
                                    navigator.share({
                                        title: '{{ addslashes($product->name) }}',
                                        text: '{{ addslashes(Str::limit($product->short_description, 100)) }}',
                                        url: '{{ url()->current() }}',
                                    })
                                } else {
                                    navigator.clipboard.writeText('{{ url()->current() }}').then(() => {
                                        $flux.toast({ text: 'Link copied!', variant: 'success' })
                                    })
                                }
                            }
                        }">
                            <flux:button icon="share" icon-variant="outline" title="Share"
                                variant="customer-outline" size="customer-lg" class="cursor-pointer"
                                @click="share()">
                            </flux:button>
                        </div>

                        <flux:button wire:click="toggleCompare" size="customer-lg" variant="customer-outline"
                            title="Compare" @class(['cursor-pointer', 'hover:text-white' => $inCompare])>
                            <x-slot name="icon">
                                @if ($inCompare)
                                    <flux:icon.x-mark class="size-5" />
                                @else
                                    <x-icon.compare class="size-5" />
                                @endif
                            </x-slot>
                        </flux:button>

                        <flux:button wire:click.stop="toggleWishlist" icon="heart" size="customer-lg"
                            variant="customer-outline" icon-variant="{{ $wishlisted ? 'solid' : 'outline' }}"
                            title="Wishlist" @class([
                                'cursor-pointer',
                                'text-red-500 hover:text-white' => $wishlisted,
                            ])>
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>

        <livewire:product-recommendations type="up_sells" :context="['product' => $product]" />
        <livewire:product-recommendations type="recently_viewed" />
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- ACCESSORIES MODAL                                                       --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($this->accessories->count() > 0)
        <flux:modal name="accessories-modal" class="w-full max-w-xl">
            <div class="space-y-4">
                {{-- Header --}}
                <flux:heading size="lg">Available Accessories</flux:heading>

                {{-- Items List --}}
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700 max-h-96 overflow-y-auto">
                    @foreach ($this->accessories as $accessory)
                        @php
                            $itemPrice = $accessory->final_price ?? ($accessory->price ?? 0);
                            $itemQty = $accessoryQuantities[$accessory->id] ?? 0;

                            // Stock status
                            $inStock = $accessory->manage_stock
                                ? $accessory->stock_quantity > 0 || $accessory->allow_backorder !== 'no'
                                : $accessory->stock_status !== 'out_of_stock';
                        @endphp

                        <div wire:key="accessory-item-{{ $accessory->id }}" class="flex items-center gap-3 py-3">
                            {{-- Product Image --}}
                            <div class="shrink-0 w-12 h-12 bg-white rounded border border-zinc-200 overflow-hidden">
                                @if ($accessory->image_path)
                                    <img src="{{ $accessory->image_url }}" alt="{{ $accessory->name }}"
                                        class="w-full h-full object-contain" />
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-zinc-100">
                                        <flux:icon.photo class="size-5 text-zinc-300" />
                                    </div>
                                @endif
                            </div>

                            {{-- Name, Price & Stock --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                    {{ $accessory->name }}
                                </p>
                                <p class="text-sm text-zinc-500">
                                    {{ $itemPrice > 0 ? format_currency($itemPrice) : '—' }}
                                </p>
                                @if ($inStock)
                                    <p class="text-xs text-green-600">In Stock</p>
                                @else
                                    <p class="text-xs text-red-500">Out of Stock</p>
                                @endif
                            </div>

                            {{-- Quantity Stepper --}}
                            <div class="shrink-0 flex items-center gap-1">
                                <button type="button" wire:click="decreaseAccessoryQuantity({{ $accessory->id }})"
                                    class="w-7 h-7 flex items-center justify-center rounded border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors cursor-pointer"
                                    aria-label="Decrease quantity">
                                    <flux:icon.minus class="size-3" />
                                </button>
                                <span
                                    class="w-8 text-center text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $itemQty }}</span>
                                <button type="button" wire:click="increaseAccessoryQuantity({{ $accessory->id }})"
                                    class="w-7 h-7 flex items-center justify-center rounded border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors cursor-pointer"
                                    aria-label="Increase quantity">
                                    <flux:icon.plus class="size-3" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-2 pt-2">
                    <flux:button type="button" variant="customer-outline"
                        x-on:click="$flux.modal('accessories-modal').close()" size="customer-lg"
                        class="cursor-pointer">
                        Continue Shopping
                    </flux:button>
                    <flux:button type="button" variant="customer-primary" wire:click="addAccessoriesToCart"
                        size="customer-lg" wire:loading.attr="disabled" wire:target="addAccessoriesToCart"
                        class="cursor-pointer">
                        Add to Cart
                        @if ($this->selectedAccessoriesCount > 0)
                            <flux:badge size="sm" class="ml-1">{{ $this->selectedAccessoriesCount }}
                            </flux:badge>
                        @endif
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- KIT CONTENTS MODAL (for Grouped Products)                              --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($product->type->value === 'grouped')
        <flux:modal name="kit-contents-modal" class="w-full max-w-xl">
            <div class="space-y-4">
                {{-- Header --}}
                <flux:heading size="lg">Select Items</flux:heading>

                {{-- Items List --}}
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->groupedProducts as $item)
                        @php
                            $itemPrice = $item->final_price ?? ($item->price ?? 0);
                            $itemQty = $groupedQuantities[$item->id] ?? 0;
                            $lineTotal = $itemPrice * $itemQty;
                        @endphp

                        <div wire:key="modal-grouped-{{ $item->id }}" class="flex items-center gap-3 py-3">
                            {{-- Product Image --}}
                            <div class="shrink-0 w-12 h-12 bg-white rounded border border-zinc-200 overflow-hidden">
                                @if ($item->image_path)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->name }}"
                                        class="w-full h-full object-contain" />
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-zinc-100">
                                        <flux:icon.photo class="size-5 text-zinc-300" />
                                    </div>
                                @endif
                            </div>

                            {{-- Name & Price --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                    {{ $item->name }}
                                </p>
                                <p class="text-sm text-zinc-500">
                                    {{ $itemPrice > 0 ? format_currency($itemPrice) : '—' }}
                                </p>
                                @if ($item->manage_stock)
                                    @if ($item->stock_quantity > 0 || $item->allow_backorder !== 'no')
                                        <p class="text-xs text-green-600">In Stock</p>
                                    @else
                                        <p class="text-xs text-red-500">Out of Stock</p>
                                    @endif
                                @else
                                    @if ($item->stock_status === 'out_of_stock')
                                        <p class="text-xs text-red-500">Out of Stock</p>
                                    @else
                                        <p class="text-xs text-green-600">In Stock</p>
                                    @endif
                                @endif
                            </div>

                            {{-- Quantity Stepper --}}
                            <div class="shrink-0 flex items-center gap-1">
                                <button type="button" wire:click="decreaseGroupedQuantity({{ $item->id }})"
                                    class="w-7 h-7 flex items-center justify-center rounded border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors cursor-pointer"
                                    aria-label="Decrease quantity">
                                    <flux:icon.minus class="size-3" />
                                </button>
                                <span class="w-8 text-center text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $itemQty }}
                                </span>
                                <button type="button" wire:click="increaseGroupedQuantity({{ $item->id }})"
                                    class="w-7 h-7 flex items-center justify-center rounded border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors cursor-pointer"
                                    aria-label="Increase quantity">
                                    <flux:icon.plus class="size-3" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-2 pt-2">
                    <flux:button type="button" variant="customer-outline"
                        x-on:click="$flux.modal('kit-contents-modal').close()" class="cursor-pointer"
                        size="customer-lg">
                        {{ $this->anyGroupedItemInCart ? 'Continue Shopping' : 'Close' }}
                    </flux:button>
                    @if ($this->anyGroupedItemInCart)
                        <flux:button href="{{ route('cart') }}" wire:navigate variant="customer-primary"
                            size="customer-lg" class="cursor-pointer" icon="shopping-cart">
                            View Cart
                        </flux:button>
                    @else
                        <flux:button type="button" variant="customer-primary" wire:click="addGroupedToCart"
                            size="customer-lg" wire:loading.attr="disabled" wire:target="addGroupedToCart"
                            class="cursor-pointer">
                            Add to Cart
                        </flux:button>
                    @endif
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- BUNDLE CONTENTS MODAL                                                  --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($product->type->value === 'bundle')
        <flux:modal name="bundle-contents-modal" class="w-full max-w-xl">
            <div class="space-y-4">
                {{-- Header --}}
                <div>
                    <flux:heading size="lg">What's in this bundle</flux:heading>
                    @php
                        $bundlePrice = $product->sale_price ?? $product->price;
                        $savings = $this->bundleSavingsPercent;
                    @endphp
                    @if ($savings)
                        <flux:text class="text-green-600 text-sm">You save {{ $savings }}% vs buying separately
                        </flux:text>
                    @endif
                </div>

                {{-- Items List --}}
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->bundleProducts as $item)
                        @php
                            $itemPrice = $item->final_price ?? ($item->price ?? 0);
                            $itemQty = $item->pivot->quantity ?? 1;
                        @endphp

                        <div wire:key="bundle-item-{{ $item->id }}" class="flex items-center gap-3 py-3">
                            {{-- Product Image --}}
                            <div class="shrink-0 w-12 h-12 bg-white rounded border border-zinc-200 overflow-hidden">
                                @if ($item->image_path)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->name }}"
                                        class="w-full h-full object-contain" />
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-zinc-100">
                                        <flux:icon.photo class="size-5 text-zinc-300" />
                                    </div>
                                @endif
                            </div>

                            {{-- Name, Price & Stock --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                    {{ $item->name }}
                                </p>
                                <p class="text-sm text-zinc-500">
                                    {{ $itemPrice > 0 ? format_currency($itemPrice) : '—' }}
                                </p>
                                @if ($item->manage_stock)
                                    @if ($item->stock_quantity > 0 || $item->allow_backorder !== 'no')
                                        <p class="text-xs text-green-600">In Stock</p>
                                    @else
                                        <p class="text-xs text-red-500">Out of Stock</p>
                                    @endif
                                @else
                                    @if ($item->stock_status === 'out_of_stock')
                                        <p class="text-xs text-red-500">Out of Stock</p>
                                    @else
                                        <p class="text-xs text-green-600">In Stock</p>
                                    @endif
                                @endif
                            </div>

                            {{-- Fixed qty (read-only for bundle) --}}
                            <div class="shrink-0 text-right">
                                <span class="text-sm text-zinc-500">Qty:</span>
                                <span
                                    class="text-sm font-medium text-zinc-800 dark:text-zinc-100 ml-1">{{ $itemQty }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Bundle Price --}}
                @if ($bundlePrice)
                    <div class="flex items-center justify-between pt-3 border-t border-zinc-200 dark:border-zinc-700">
                        <span class="text-sm text-zinc-500">Bundle Price</span>
                        <span class="text-lg font-bold text-secondary">{{ format_currency($bundlePrice) }}</span>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-2 pt-2">
                    <flux:button type="button" variant="customer-outline"
                        x-on:click="$flux.modal('bundle-contents-modal').close()" size="customer-lg"
                        class="cursor-pointer">
                        Continue Shopping
                    </flux:button>
                    @if ($inCart)
                        <flux:button href="{{ route('cart') }}" wire:navigate variant="customer-primary"
                            size="customer-lg" class="cursor-pointer" icon="shopping-cart">
                            View Cart
                        </flux:button>
                    @else
                        <flux:button type="button" variant="customer-primary" wire:click="addBundleToCart"
                            size="customer-lg" wire:loading.attr="disabled" wire:target="addBundleToCart"
                            class="cursor-pointer">
                            Add to Cart
                        </flux:button>
                    @endif
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- MOBILE STICKY BOTTOM ACTION BAR (hidden on lg+) --}}
    <div class="fixed bottom-0 inset-x-0 z-50 lg:hidden" x-data="{ visible: false }"
        x-on:price-out-of-view.window="visible = true" x-on:price-in-view.window="visible = false" x-show="visible"
        x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
        <div class="bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 px-4 py-3 shadow-lg">
            <div class="flex items-center gap-2">
                @php
                    $mobileState =
                        $product->type->value === 'variable' ? $this->selectedVariantState : $this->simpleProductState;
                @endphp

                @if ($product->type->value === 'grouped')
                    <flux:button x-data @click="$flux.modal('kit-contents-modal').show()" variant="customer-primary"
                        size="customer-lg" class="flex-1 cursor-pointer" icon="shopping-cart">
                        Add to Cart
                    </flux:button>
                @elseif ($product->type->value === 'bundle')
                    @if ($inCart)
                        <flux:button href="{{ route('cart') }}" wire:navigate variant="customer-primary"
                            size="customer-lg" class="flex-1 cursor-pointer" icon="shopping-cart">
                            View Cart
                        </flux:button>
                    @else
                        <flux:button x-data @click="$flux.modal('bundle-contents-modal').show()"
                            variant="customer-primary" size="customer-lg" class="flex-1 cursor-pointer"
                            icon="shopping-cart">
                            Add to Cart
                        </flux:button>
                    @endif
                @elseif ($product->requires_quotation)
                    <flux:button wire:click="addToQuoteBasket" variant="customer-primary" size="customer-lg"
                        class="flex-1 cursor-pointer" wire:loading.attr="disabled" wire:target="addToQuoteBasket">
                        Add to Quote
                    </flux:button>
                @elseif ($mobileState === 'out_of_stock')
                    <flux:button variant="customer-outline" size="customer-lg" class="flex-1 cursor-not-allowed"
                        disabled>
                        Out of Stock
                    </flux:button>
                @elseif ($inCart)
                    <flux:button href="{{ route('cart') }}" wire:navigate variant="customer-primary"
                        size="customer-lg" class="flex-1 cursor-pointer" icon="shopping-cart">
                        View Cart
                    </flux:button>
                @elseif ($mobileState === 'backorder')
                    <flux:button wire:click="addToCart" size="customer-lg"
                        class="flex-1 cursor-pointer bg-amber-500! border-amber-500! hover:bg-amber-600! text-white!"
                        wire:loading.attr="disabled" wire:target="addToCart">
                        Pre-order
                    </flux:button>
                @elseif ($mobileState !== 'none')
                    <flux:button wire:click="addToCart" variant="customer-primary" size="customer-lg"
                        class="flex-1 cursor-pointer" wire:loading.attr="disabled" wire:target="addToCart">
                        Add to Cart
                    </flux:button>
                @endif

                <flux:button wire:click.stop="toggleWishlist" icon="heart" size="customer-lg"
                    variant="customer-outline" icon-variant="{{ $wishlisted ? 'solid' : 'outline' }}"
                    title="Wishlist" @class(['cursor-pointer', 'text-red-500!' => $wishlisted])>
                </flux:button>
            </div>
        </div>
    </div>
</div>
