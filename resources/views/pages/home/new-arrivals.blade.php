<section class="lg:col-span-5 px-4 md:px-5 py-5">
    <div class="relative" x-data="{
        swiper: null,
        init() {
            this.swiper = new Swiper('#newArrivalsSwiper', {
                spaceBetween: 12,
                loop: true,
                speed: 400,
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
            class="absolute top-1/2 left-1 -translate-y-1/2 z-30 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
            <flux:icon.chevron-left class="size-3.5 text-white" />
            <span class="sr-only">Previous</span>
        </button>

        <button type="button" @click="swiper?.slideNext()"
            class="absolute top-1/2 right-1 -translate-y-1/2 z-30 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
            <flux:icon.chevron-right class="size-3.5 text-white" />
            <span class="sr-only">Next</span>
        </button>
    </div>
</section>
