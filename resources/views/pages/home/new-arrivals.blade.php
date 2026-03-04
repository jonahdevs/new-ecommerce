<section class="px-3 md:px-5 lg:col-span-5">
    {{-- products slider --}}
    <section class="relative pb-4" x-data="{
        swiper: null,
        init() {
            this.swiper = new Swiper('#newArrivalsSwiper', {
                spaceBetween: 12,
                loop: true,
                speed: 400,
                breakpoints: {
                    375: {
                        slidesPerView: 2,
                    },
                    480: {
                        slidesPerView: 2,
                    },
                    640: {
                        slidesPerView: 3,
                    },
                    768: {
                        slidesPerView: 4,
                    },
                    1024: {
                        slidesPerView: 5,
                    },
                },
            });
            this.$nextTick(() => {
                document.getElementById('newArrivalsSwiper').classList.remove('opacity-0');
            });
        }
    }">
        <div class="swiper opacity-0 transition-opacity duration-500" id="newArrivalsSwiper">
            <div class="swiper-wrapper pb-4">
                @foreach ($this->newArrivals as $product)
                    <div class="swiper-slide h-auto!" :key="'product-' . $product->id">
                        <livewire:product-card :product="$product" />
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Navigation buttons -->
        <button type="button" @click="swiper?.slidePrev()"
            class="absolute top-1/2 left-0  -translate-y-1/2 -translate-x-1/2 z-30 flex items-center justify-center cursor-pointer group focus:outline-none w-8 h-8 rounded-full bg-sheffield-blue/30 group-hover:bg-sheffield-blue/50 group-focus:ring-4 group-focus:ring-sheffield-blue/70 group-focus:outline-none">
            <flux:icon.arrow-long-left class="size-4 text-white" />
            <span class="sr-only">Previous</span>
        </button>

        <button type="button" @click="swiper?.slideNext()"
            class="absolute top-1/2 right-0 -translate-y-1/2 translate-x-1/2 z-30 flex items-center justify-center cursor-pointer group focus:outline-none w-8 h-8 rounded-full bg-sheffield-blue/30 group-hover:bg-sheffield-blue/50 group-focus:ring-4 group-focus:ring-sheffield-blue/70 group-focus:outline-none">
            <flux:icon.arrow-long-right class="size-4 text-white" />
            <span class="sr-only">Next</span>
        </button>
    </section>
</section>
