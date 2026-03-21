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
                const thumbEl = document.getElementById('groupedThumbSwiper');
        
                if (thumbEl) {
                    this.thumbSwiper = new Swiper('#groupedThumbSwiper', {
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
        
                this.mainSwiper = new Swiper('#groupedMainSwiper', {
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
                    document.getElementById('groupedMainSwiper').classList.remove('opacity-0');
                });
            },
        }">
            {{-- Main slider --}}
            <div class="mb-4">
                <div class="swiper border-2 rounded-sm overflow-hidden opacity-0 transition-opacity duration-500"
                    id="groupedMainSwiper">
                    <div class="swiper-wrapper">
                        @foreach ($this->imageSlides as $slide)
                            <div class="swiper-slide">
                                <div class="aspect-square flex items-center justify-center p-2">
                                    <img src="{{ $slide['url'] }}" alt="{{ $slide['alt'] }}"
                                        class="w-full h-full object-contain" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div>

            {{-- Thumbnail slider --}}
            @if (count($this->imageSlides) > 1)
                <div class="swiper px-8 opacity-0 transition-opacity duration-500" id="groupedThumbSwiper">
                    <div class="swiper-wrapper">
                        @foreach ($this->imageSlides as $index => $slide)
                            <div class="swiper-slide cursor-pointer">
                                <div class="aspect-square rounded-sm overflow-hidden border-2 transition-all duration-300"
                                    :class="activeIndex === {{ $index }} ?
                                        'border-sheffield-blue' :
                                        'border-zinc-200 hover:border-zinc-300'">
                                    <img src="{{ $slide['url'] }}" alt="{{ $slide['alt'] }}"
                                        class="w-full h-full object-contain" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- GROUPED PRODUCT DETAILS                             --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    <div class="lg:col-span-3 space-y-4">

        {{-- Name --}}
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 leading-tight">
            {{ $product->name }}
        </h1>

        {{-- Brand + Rating --}}
        <div class="flex items-center justify-between flex-wrap gap-3">
            @if ($product->brand)
                <div class="flex items-center gap-2">
                    <span class="text-zinc-500 text-sm">Brand:</span>
                    <span class="text-sheffield-blue font-medium text-sm">{{ $product->brand->name }}</span>
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
                <span class="text-sm text-zinc-500">({{ number_format($avgRating, 1) }})</span>
                <a href="{{ route('products.reviews', $product) }}" wire:navigate
                    class="text-sm text-sheffield-blue hover:underline">
                    {{ $this->reviewStats['total'] }} reviews
                </a>
            </div>
        </div>

        {{-- Short description --}}
        @if ($product->short_description)
            <div class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                {!! $product->short_description !!}
            </div>
        @endif

        {{-- ── KIT CONTENTS TABLE ── --}}
        <div class="space-y-2">
            <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                Kit contents
            </p>

            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">

                {{-- Header --}}
                <div
                    class="hidden sm:grid grid-cols-12 gap-2 px-3 py-2 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 text-[10px] font-medium text-zinc-500 uppercase tracking-wide">
                    <div class="col-span-5">Product</div>
                    <div class="col-span-3 text-center">Quantity</div>
                    <div class="col-span-4 text-right">Price</div>
                </div>
                <div class="max-h-64 overflow-y-auto">


                    {{-- Rows --}}
                    @foreach ($this->groupedProducts as $item)
                        @php
                            $itemPrice = $item->final_price ?? ($item->price ?? 0);
                            $itemQty = $groupedQuantities[$item->id] ?? ($item->pivot->quantity ?? 1);
                            $isSelected = in_array($item->id, $selectedGroupedItems);
                        @endphp

                        <div wire:key="grouped-{{ $item->id }}"
                            wire:click="$toggle('selectedGroupedItems', {{ $item->id }})"
                            class="grid grid-cols-12 gap-2 px-3 py-2.5 items-center cursor-pointer transition-colors duration-150
                            {{ !$loop->last ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }}
                            {{ $isSelected ? 'bg-blue-50 dark:bg-blue-950/20' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }}">

                            {{-- Checkbox + Name --}}
                            <div class="col-span-5 flex items-center gap-2 min-w-0">
                                <flux:checkbox wire:model.live="selectedGroupedItems" value="{{ $item->id }}"
                                    wire:click.stop />
                                <a href="{{ route('products.show', $item) }}" wire:navigate wire:click.stop
                                    class="text-sm font-medium text-sheffield-blue hover:underline truncate">
                                    {{ $item->name }}
                                </a>
                            </div>

                            {{-- Quantity stepper --}}
                            <div class="col-span-3 flex justify-center" wire:click.stop>
                                <div
                                    class="flex items-center border border-zinc-200 dark:border-zinc-700 rounded-md overflow-hidden">
                                    <button type="button"
                                        wire:click.stop="decreaseGroupedQuantity({{ $item->id }})"
                                        class="w-6 h-6 flex items-center justify-center text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors cursor-pointer text-base leading-none">
                                        −
                                    </button>
                                    <span
                                        class="w-7 h-6 flex items-center justify-center text-xs font-medium text-zinc-800 dark:text-zinc-100
                                    border-l border-r border-zinc-200 dark:border-zinc-700">
                                        {{ $itemQty }}
                                    </span>
                                    <button type="button"
                                        wire:click.stop="increaseGroupedQuantity({{ $item->id }})"
                                        class="w-6 h-6 flex items-center justify-center text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors cursor-pointer text-base leading-none">
                                        +
                                    </button>
                                </div>
                            </div>

                            {{-- Price --}}
                            <div class="col-span-4 text-right">
                                @if ($isSelected)
                                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                        {{ $itemPrice > 0 ? format_currency($itemPrice * $itemQty) : '—' }}
                                    </span>
                                @else
                                    <span class="text-sm text-zinc-400 dark:text-zinc-600">—</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Kit total --}}
                <div
                    class="flex items-center justify-between px-3 py-2.5 bg-zinc-50 dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700">
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ count($selectedGroupedItems) }} of {{ $this->groupedProducts->count() }} items selected
                    </span>
                    <div class="text-right">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400 mr-1">Total</span>
                        <span class="text-base font-semibold text-sheffield-blue">
                            {{ format_currency($this->groupedTotal) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── CART ACTIONS ── --}}
        <div class="flex flex-col gap-2">

            {{-- Add full kit --}}
            <flux:button wire:click="addFullKitToCart" variant="primary" class="w-full uppercase cursor-pointer"
                wire:loading.attr="disabled" wire:target="addFullKitToCart">
                Add Full Kit to Cart
            </flux:button>

            {{-- Add selected — only shown when some but not all items are selected --}}
            @if (!empty($selectedGroupedItems) && count($selectedGroupedItems) < $this->groupedProducts->count())
                <flux:button wire:click="addSelectedGroupedToCart" class="w-full uppercase cursor-pointer"
                    wire:loading.attr="disabled" wire:target="addSelectedGroupedToCart">
                    Add Selected Items ({{ count($selectedGroupedItems) }})
                </flux:button>
            @endif

            {{-- Wishlist + Compare + Share --}}
            <div class="flex items-center gap-2">
                <flux:button wire:click.stop="toggleWishlist" icon="heart"
                    icon-variant="{{ $wishlisted ? 'solid' : 'outline' }}" title="Wishlist"
                    @class(['cursor-pointer', 'text-red-500!' => $wishlisted]) />

                <flux:button wire:click="toggleCompare" icon="{{ $inCompare ? 'x-mark' : 'scale' }}"
                    icon-variant="outline" title="Compare" @class(['cursor-pointer', 'text-sheffield-blue!' => $inCompare]) />

                <flux:button icon="share" icon-variant="outline" title="Share" class="cursor-pointer" />
            </div>
        </div>

        {{-- Purchase note --}}
        @if ($product->purchase_note)
            <div
                class="text-xs text-zinc-500 bg-zinc-50 dark:bg-zinc-800 rounded-md px-3 py-2 border border-zinc-200 dark:border-zinc-700">
                {{ $product->purchase_note }}
            </div>
        @endif

    </div>

</flux:card>
