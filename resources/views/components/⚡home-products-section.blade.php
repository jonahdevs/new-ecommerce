<?php

use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Component;

new #[Defer] class extends Component {
    #[Computed]
    public function products()
    {
        return Product::active()->inRandomOrder()->limit(12)->get();
    }

    public function mount()
    {
        $this->dispatch('products-loaded');
    }
};
?>

@placeholder
    <div class="bg-white border rounded-sm">

        <section class="flex items-center justify-between py-4 px-3 md:px-5">
            <h2 class="font-semibold text-xl text-zinc-800">You May Also Like</h2>

        </section>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 pb-5">
            @for ($i = 0; $i < 12; $i++)
                <div class="bg-white rounded border animate-pulse">
                    <div class="w-full aspect-square bg-zinc-200 mb-2"></div>
                    <div class="p-4">
                        <div class="h-4 bg-zinc-200 rounded w-1/4 mb-2"></div>
                        <div class="space-y-2 mb-2">
                            <div class="h-3.5 bg-zinc-200 rounded w-full"></div>
                            <div class="h-3.5 bg-zinc-200 rounded w-3/4"></div>
                        </div>
                        <div class="h-4 bg-zinc-200 rounded w-1/2"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
@endplaceholder

<div>
    <div class="bg-white border rounded-sm">

        <section class="flex items-center justify-between py-4 px-3 md:px-5">
            <h2 class="font-semibold text-xl text-zinc-800">You May Also Like</h2>

            <a href="#" wire:navigate class="text-sheffield-red hover:underline text-sm">View
                All</a>
        </section>

        <section class="px-3 md:px-5">
            <!-- Products slider -->
            <div class="relative" x-data="{
                swiper: null,
                initSwiper() {
                    if (this.swiper) {
                        this.swiper.destroy(true, true);
                    }
                    this.$nextTick(() => {
                        this.swiper = new Swiper('#youMayAlsoLike', {
                            slidesPerView: 2,
                            slidesPerGroup: 1,
                            spaceBetween: 12,
                            loop: false,
                            speed: 400,
                            observer: true,
                            observeParents: true,
                            watchOverflow: true,
                            grid: {
                                rows: 2,
                                fill: 'row'
                            },
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
                                1280: {
                                    slidesPerView: 6,
                                },
                            },
                        });
                    });
                }
            }" x-init="initSwiper()"
                @products-loaded.window="initSwiper()">
                <div class="swiper" id="youMayAlsoLike">
                    <div class="swiper-wrapper pb-5">
                        @foreach ($this->products as $product)
                            <div class="swiper-slide h-auto!">
                                <livewire:product-card :product="$product" />
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Navigation buttons -->
                <button type="button" @click="swiper?.slidePrev()"
                    class="absolute top-0 left-0 -translate-x-1/2 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none">
                    <span
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-sheffield-blue/30 hover:bg-sheffield-blue/50 focus:ring-4 focus:ring-sheffield-blue/70 focus:outline-none">
                        <svg class="w-3.5 h-3.5 text-white rtl:rotate-180" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 1 1 5l4 4" />
                        </svg>
                        <span class="sr-only">Previous</span>
                    </span>
                </button>

                <button type="button" @click="swiper?.slideNext()"
                    class="absolute top-0 right-0 translate-x-1/2 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none">
                    <span
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-sheffield-blue/30 hover:bg-sheffield-blue/50 focus:ring-4 focus:ring-sheffield-blue/70 focus:outline-none">
                        <svg class="w-3.5 h-3.5 text-white rtl:rotate-180" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m1 9 4-4-4-4" />
                        </svg>
                        <span class="sr-only">Next</span>
                    </span>
                </button>
            </div>
        </section>
    </div>
</div>
