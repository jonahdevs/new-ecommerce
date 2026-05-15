<?php
// Extract directive's "with" parameter (overrides component properties)
$__islandScope = (function($name = null, $token = null, $lazy = false, $defer = false, $always = false, $skip = false, $with = []) {
    return $with;
})('new-arrivals', defer: true);
if (!empty($__islandScope)) {
    extract($__islandScope, EXTR_OVERWRITE);
}

// Extract runtime "with" parameter if provided (overrides everything)
if (isset($__runtimeWith) && is_array($__runtimeWith) && !empty($__runtimeWith)) {
    extract($__runtimeWith, EXTR_OVERWRITE);
}
?>

                <?php if (isset($__placeholder)) { ob_start(); } if (isset($__placeholder)): ?>
                    <section class="@4xl/newarrivals:col-span-5 px-4 md:px-5 py-5">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            @for ($i = 0; $i < 5; $i++)
                                <x-product-card-placeholder />
                            @endfor
                        </div>
                    </section>
                <?php endif; if (isset($__placeholder)) { echo ob_get_clean(); return; } ?>


                <section class="@4xl/newarrivals:col-span-5 px-4 md:px-5 py-5">
                    <div class="relative" x-data="{
                        swiper: null,
                        init() {
                            this.swiper = new Swiper('#newArrivalsSwiper', {
                                spaceBetween: 12,
                                loop: true,
                                speed: 400,
                                // Allow clicks on links inside slides
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
                            class="absolute top-1/2 left-1 -translate-y-1/2 z-1 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                            <flux:icon.chevron-left class="size-3.5 text-white" />
                            <span class="sr-only">Previous</span>
                        </button>

                        <button type="button" @click="swiper?.slideNext()"
                            class="absolute top-1/2 right-1 -translate-y-1/2 z-1 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                            <flux:icon.chevron-right class="size-3.5 text-white" />
                            <span class="sr-only">Next</span>
                        </button>
                    </div>
                </section>
            