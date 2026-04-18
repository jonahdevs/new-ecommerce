@php
    use App\Enums\ProductType;
@endphp

<flux:card class="lg:col-span-3 rounded-sm grid grid-cols-1 lg:grid-cols-5 gap-6 lg:gap-10">

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- IMAGE SLIDER                                        --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    <div class="lg:col-span-2">
        <div wire:ignore class="w-full" x-data="{
            mainSwiper: null,
            thumbSwiper: null,
            activeIndex: 0,
            init() {
                const thumbEl = document.getElementById('thumbSwiper');
        
                if (thumbEl) {
                    this.thumbSwiper = new Swiper('#thumbSwiper', {
                        spaceBetween: 10,
                        slidesPerView: 4,
                        freeMode: true,
                        watchSlidesProgress: true,
                        loop: false,
                        breakpoints: {
                            640: { slidesPerView: 5 },
                            768: { slidesPerView: 6 },
                        },
                    });
                }
        
                this.mainSwiper = new Swiper('#mainSwiper', {
                    spaceBetween: 10,
                    loop: false,
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                    thumbs: { swiper: this.thumbSwiper ?? null },
                    on: {
                        slideChange: (swiper) => {
                            this.activeIndex = swiper.realIndex;
                        },
                    },
                });
        
                this.$nextTick(() => {
                    if (thumbEl) thumbEl.classList.remove('opacity-0');
                    document.getElementById('mainSwiper').classList.remove('opacity-0');
                });
            },
        }">
            {{-- Main slider --}}
            <div class="mb-4">
                <div class="swiper border-2 rounded-sm overflow-hidden opacity-0 transition-opacity duration-500"
                    id="mainSwiper">
                    <div class="swiper-wrapper">
                        @foreach ($this->imageSlides as $slide)
                            <div class="swiper-slide">
                                <div class="aspect-square flex items-center justify-center p-2">
                                    <x-webp-image :src="$slide['url']" :webp="$slide['webp'] ?? null"
                                        alt="{{ $slide['alt'] }}" class="w-full h-full object-contain" />
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if (count($this->imageSlides) > 1)
                        <button type="button" @click="mainSwiper?.slidePrev()"
                            class="absolute top-1/2 left-1 -translate-y-1/2 z-30
                   w-7 h-7 rounded-full flex items-center justify-center
                   bg-black/20 hover:bg-black/40 backdrop-blur-sm
                   border border-white/20 hover:border-white/40
                   transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                            <flux:icon.chevron-left class="size-3.5 text-white" />
                            <span class="sr-only">Previous</span>
                        </button>

                        <button type="button" @click="mainSwiper?.slideNext()"
                            class="absolute top-1/2 right-1 -translate-y-1/2 z-30
                   w-7 h-7 rounded-full flex items-center justify-center
                   bg-black/20 hover:bg-black/40 backdrop-blur-sm
                   border border-white/20 hover:border-white/40
                   transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                            <flux:icon.chevron-right class="size-3.5 text-white" />
                            <span class="sr-only">Next</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Thumbnail slider --}}
            @if (count($this->imageSlides) > 1)
                <div class="swiper px-8 opacity-0 transition-opacity duration-500" id="thumbSwiper">
                    <div class="swiper-wrapper">
                        @foreach ($this->imageSlides as $index => $slide)
                            <div class="swiper-slide cursor-pointer">
                                <div class="aspect-square rounded-sm overflow-hidden border-2 transition-all duration-300"
                                    :class="activeIndex === {{ $index }} ?
                                        'border-brand-secondary' :
                                        'border-zinc-200 hover:border-zinc-300'">
                                    <x-webp-image :src="$slide['url']" :webp="$slide['webp'] ?? null"
                                        alt="{{ $slide['alt'] }}" class="w-full h-full object-contain" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- PRODUCT DETAILS                                     --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    <div class="lg:col-span-3 space-y-4">

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
                    <span class="text-brand-secondary font-medium text-xs sm:text-sm">{{ $product->brand->name }}</span>
                </div>
            @endif
            @island('reviews')
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
                    <span class="text-xs sm:text-sm text-zinc-500">({{ number_format($avgRating, 1) }})</span>
                    <a href="{{ route('products.reviews', $product) }}" wire:navigate
                        class="text-xs sm:text-sm text-brand-secondary hover:underline">
                        {{ $this->reviewStats['total'] }} reviews
                    </a>
                </div>
            @endisland
        </div>

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
                                        ($selectedAttributeValues[$attribute['name']] ?? null) === $value['value'];
                                    $state = $value['state'];
                                @endphp

                                @if ($state === 'unavailable')
                                    {{-- Never show unpriced variants --}}
                                    @continue
                                @elseif ($state === 'available')
                                    <button type="button"
                                        wire:click="selectAttributeValue('{{ $attribute['name'] }}', '{{ $value['value'] }}')"
                                        @class([
                                            'px-3 py-1.5 text-sm border rounded-md transition-all cursor-pointer',
                                            'border-brand-secondary bg-brand-secondary/5 text-brand-secondary font-medium' => $isSelected,
                                            'border-zinc-300 text-zinc-700 hover:border-zinc-400 dark:border-zinc-600 dark:text-zinc-300' => !$isSelected,
                                        ])>
                                        {{ $value['label'] }}
                                    </button>
                                @elseif ($state === 'backorder')
                                    <button type="button"
                                        wire:click="selectAttributeValue('{{ $attribute['name'] }}', '{{ $value['value'] }}')"
                                        @class([
                                            'px-3 py-1.5 text-sm border rounded-md transition-all cursor-pointer',
                                            'border-amber-500 bg-amber-50 text-amber-700 font-medium' => $isSelected,
                                            'border-amber-300 text-amber-600 hover:border-amber-500 bg-amber-50/50' => !$isSelected,
                                        ])>
                                        {{ $value['label'] }}
                                        <span class="text-xs opacity-75 ml-1">(backorder)</span>
                                    </button>
                                @else
                                    {{-- out_of_stock — greyed, not clickable but visible --}}
                                    <button type="button" disabled
                                        class="px-3 py-1.5 text-sm border rounded-md border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 cursor-not-allowed relative"
                                        title="Out of stock">
                                        <span class="line-through text-zinc-400">{{ $value['label'] }}</span>
                                    </button>
                                @endif
                            @endforeach
                        </div>
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

        {{-- SHORT DESCRIPTION --}}
        @if ($product->short_description)
            <div class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                {!! $product->short_description !!}
            </div>
        @endif

        {{-- PRICE --}}
        @if (!$product->requires_quotation)
            <div>
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
                            <span class="text-xl sm:text-2xl font-bold text-brand-secondary">
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
                        <span class="text-xl sm:text-2xl font-bold text-brand-secondary">
                            {{ format_currency($finalPrice) }}
                        </span>
                    @endif
                @elseif ($product->type->value === 'variable' && !$selectedVariantId)
                    <span class="text-sm sm:text-base text-zinc-400">Select options to see price</span>
                @endif

                {{-- STOCK STATUS --}}
                @php
                    $state =
                        $product->type->value === 'variable' ? $this->selectedVariantState : $this->simpleProductState;
                    $variant = $this->selectedVariant;
                    $source = $variant ?? $product;
                @endphp

                @if ($state === 'none')
                    <p class="text-xs sm:text-sm text-zinc-400 mt-1">Select options to see availability</p>
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

        <flux:separator />

        @if ($this->accessories->count() > 0)
            <a href="#accessories"
                onclick="document.getElementById('accessories').scrollIntoView({ behavior: 'smooth' }); return false;"
                class="flex items-center gap-2 px-3 py-2 bg-blue-50 dark:bg-blue-950/30 border-l-2 border-brand-secondary rounded-r-md no-underline group">
                <flux:icon.wrench-screwdriver class="size-3.5 text-brand-secondary shrink-0" variant="outline" />
                <span class="text-xs sm:text-sm text-blue-900 dark:text-blue-200">
                    {{ $this->accessories->count() }}
                    {{ Str::plural('accessory', $this->accessories->count()) }} available for this product
                </span>
                <span
                    class="text-xs text-brand-secondary ml-auto group-hover:translate-y-0.5 transition-transform flex items-center gap-2">
                    View
                    <flux:icon.arrow-long-down class="size-4" />
                </span>
            </a>
        @endif

        {{--  CART ACTIONS  --}}
        <div class="flex items-center gap-2 flex-wrap">

            @if ($product->requires_quotation)
                {{-- Quotation products — no cart, quote only --}}
                <flux:button wire:click="addToQuoteBasket" variant="primary" class="uppercase cursor-pointer"
                    wire:loading.attr="disabled" wire:target="addToQuoteBasket">
                    <x-slot name="icon">
                        <flux:icon.document-text class="size-4" />
                    </x-slot>
                    Add to Quote
                </flux:button>

                @if ($inQuoteBasket)
                    <a href="{{ route('quote') }}" wire:navigate>
                        <flux:button icon="arrow-right" icon-variant="outline" class="cursor-pointer">
                            View Quote Basket
                        </flux:button>
                    </a>
                @endif
            @else
                {{-- Quantity stepper — hidden when out of stock --}}
                @if ($state !== 'out_of_stock' && $state !== 'none')
                    <flux:button.group>
                        <flux:button icon="minus" wire:click="decreaseCartQuantity"
                            class="cursor-pointer text-zinc-500!" title="Decrease" />
                        <flux:input readonly value="{{ $cartQuantity }}"
                            class="max-w-9! text-center! outline-none! border-none! ring-0!" />
                        <flux:button icon="plus" wire:click="increaseCartQuantity"
                            class="cursor-pointer text-zinc-500!" title="Increase" />
                        @if ($inCart)
                            <flux:button icon="trash" icon-variant="outline" wire:click="removeFromCart"
                                class="cursor-pointer text-red-500!" title="Remove" />
                        @endif
                    </flux:button.group>
                @endif


                {{-- Primary action --}}
                @if ($product->type === ProductType::VARIABLE && !$selectedVariantId)
                    <flux:button variant="primary" class="uppercase cursor-pointer" disabled>
                        Select Options
                    </flux:button>
                @elseif ($state === 'out_of_stock')
                    <flux:button class="uppercase cursor-not-allowed" disabled>
                        Out of Stock
                    </flux:button>
                @elseif ($state === 'backorder' && !$inCart)
                    <flux:button wire:click="addToCart"
                        class="uppercase cursor-pointer bg-amber-500! border-amber-500! hover:bg-amber-600! text-white!"
                        wire:loading.attr="disabled" wire:target="addToCart">
                        Pre-order
                    </flux:button>
                @elseif (!$inCart)
                    <flux:button wire:click="addToCart" variant="primary" class="uppercase cursor-pointer"
                        wire:loading.attr="disabled" wire:target="addToCart">
                        Add to Cart
                    </flux:button>
                @endif

            @endif

            {{-- Wishlist --}}
            <flux:button wire:click.stop="toggleWishlist" icon="heart"
                icon-variant="{{ $wishlisted ? 'solid' : 'outline' }}" title="Wishlist"
                @class(['cursor-pointer', 'text-red-500!' => $wishlisted]) />

            {{-- Compare --}}
            <flux:button wire:click="toggleCompare" icon="{{ $inCompare ? 'x-mark' : 'scale' }}"
                icon-variant="outline" title="Compare" @class(['cursor-pointer', 'text-brand-secondary!' => $inCompare]) />

            {{-- Share --}}
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
                <flux:button icon="share" icon-variant="outline" title="Share" class="cursor-pointer"
                    @click="share()" />
            </div>

        </div>


    </div>

</flux:card>
