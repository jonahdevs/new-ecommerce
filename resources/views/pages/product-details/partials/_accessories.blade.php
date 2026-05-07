@if ($this->accessories->count() > 0)
    <div id="accessories" class="scroll-mt-44">

        <div wire:ignore x-data="{
            swiper: null,
            init() {
                this.swiper = new Swiper('#accessoriesSwiper', {
                    slidesPerView: 1,
                    spaceBetween: 16,
                    navigation: {
                        nextEl: '#accessories-next',
                        prevEl: '#accessories-prev',
                    },
                    pagination: {
                        el: '#accessories-pagination',
                        clickable: true,
                    },
                    breakpoints: {
                        640: { slidesPerView: 2 },
                        1024: { slidesPerView: 3 },
                        1280: { slidesPerView: 4 },
                    },
                });
            },
        }">


            {{-- Swiper --}}
            <div class="swiper" id="accessoriesSwiper">
                <div class="swiper-wrapper items-stretch pb-1">
                    @foreach ($this->accessories as $accessory)
                        <div class="swiper-slide h-auto!">
                            <livewire:accessory-item :product="$accessory" :recommended-quantity="$accessory->pivot->quantity ?? 1" :key="'accessory-' . $accessory->id" />
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between mt-4">
                {{-- Pagination dots — only shown when more than 4 accessories --}}
                @if ($this->accessories->count() > 4)
                    <div id="accessories-pagination" class=" space-x-1.5 "></div>
                @endif

                <div class="flex items-center justify-end gap-2 ms-auto">
                    {{-- Prev --}}
                    <button id="accessories-prev"
                        class="w-8 h-8 flex items-center justify-center border border-zinc-300 rounded-md transition-colors hover:bg-zinc-100 cursor-pointer"
                        aria-label="Previous">
                        <flux:icon.chevron-left class="w-4 h-4 text-zinc-600" />
                    </button>

                    {{-- Next --}}
                    <button id="accessories-next"
                        class="w-8 h-8 flex items-center justify-center border border-zinc-300 rounded-md transition-colors hover:bg-zinc-100 cursor-pointer"
                        aria-label="Next">
                        <flux:icon.chevron-right class="w-4 h-4 text-zinc-600" />
                    </button>

                    <div class="w-px h-5 bg-zinc-200 mx-1"></div>

                    {{-- Add all --}}
                    <flux:button wire:click="addAllAccessoriesToCart" wire:loading.attr="disabled"
                        wire:target="addAllAccessoriesToCart" size="sm" variant="filled" icon="shopping-bag"
                        icon-variant="outline" class="cursor-pointer">
                        Add all
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
@endif

<style>
    #accessoriesSwiper .swiper-slide {
        height: auto;
    }

    #accessories-pagination .swiper-pagination-bullet {
        width: 6px;
        height: 6px;
        background: #d4d4d8;
        opacity: 1;
        border-radius: 9999px;
        transition: width 0.2s, background 0.2s;
        display: inline-block;
        cursor: pointer;
    }

    #accessories-pagination .swiper-pagination-bullet-active {
        width: 16px;
        background: var(--color-secondary, #1d4ed8);
        border-radius: 9999px;
    }

    #accessories-prev.swiper-button-disabled,
    #accessories-next.swiper-button-disabled {
        opacity: 0.3;
        cursor: not-allowed;
        pointer-events: none;
    }
</style>
