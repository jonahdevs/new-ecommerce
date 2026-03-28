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
                loop: true,
                speed: 600,
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
            class="absolute top-1/2 left-0 -translate-y-1/2 -translate-x-1/2 z-30 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
            <flux:icon.chevron-left class="size-3.5 text-white" />
            <span class="sr-only">Previous</span>
        </button>

        <button type="button" @click="swiper?.slideNext()"
            class="absolute top-1/2 right-0 -translate-y-1/2  translate-x-1/2  z-30 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
            <flux:icon.chevron-right class="size-3.5 text-white" />
            <span class="sr-only">Next</span>
        </button>
    </div>
</section>
