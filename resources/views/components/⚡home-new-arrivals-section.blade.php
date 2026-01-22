<?php

use App\Models\Product;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Computed;
use Livewire\Component;

new #[Defer] class extends Component {
    #[Computed]
    public function products()
    {
        return Product::active()
            ->with(['brand', 'images'])
            ->newArrivals()
            ->inRandomOrder()
            ->limit(10)
            ->get();
    }
};
?>

@placeholder
    {{-- Loading state with matching structure --}}
    <div class=" pt-4 bg-sheffield-red border rounded-sm grid grid-cols-1 lg:grid-cols-6 gap-4">
        <div class="lg:col-span-1 flex justify-center flex-col text-white px-3 md:px-5 py-4 lg:py-0">
            <h4 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-3 lg:mb-5">New</h4>

            <p class="text-sm sm:text-base font-medium tracking-wide">Just In! Explore Our Latest Product Arrivals</p>

            <flux:button class="w-fit mt-4">Show All</flux:button>
        </div>

        <section class="px-3 md:px-5 lg:col-span-5 relative pb-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 pb-4">
                @for ($i = 0; $i < 5; $i++)
                    <div class="bg-white rounded-xs border animate-pulse">
                        <!-- Image skeleton -->
                        <div class="w-full aspect-square bg-zinc-200 mb-2"></div>

                        <div class="p-4">
                            <div class="h-4 bg-zinc-200 rounded w-1/4 mb-2"></div>

                            <!-- Product name skeleton (2 lines) -->
                            <div class="space-y-2 mb-2">
                                <div class="h-3.5 bg-zinc-200 rounded w-full"></div>
                                <div class="h-3.5 bg-zinc-200 rounded w-3/4"></div>
                            </div>

                            <!-- Price skeleton -->
                            <div class="h-4 bg-zinc-200 rounded w-1/2"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </section>
    </div>
@endplaceholder

<div>
    <div class=" pt-4 bg-sheffield-red border rounded-sm grid grid-cols-1 lg:grid-cols-6 gap-4">
        <div class="lg:col-span-1 flex justify-center flex-col text-white px-3 md:px-5 py-4 lg:py-0">
            <h4 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-3 lg:mb-5">New</h4>

            <p class="text-sm sm:text-base font-medium tracking-wide">Just In! Explore Our Latest Product Arrivals</p>

            <flux:button class="w-fit mt-4">Show All</flux:button>
        </div>

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
                }
            }">
                <div class="swiper" id="newArrivalsSwiper">
                    <div class="swiper-wrapper pb-4">
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
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-sheffield-blue/30 group-hover:bg-sheffield-blue/50 group-focus:ring-4 group-focus:ring-sheffield-blue/70 group-focus:outline-none">
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
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-sheffield-blue/30 group-hover:bg-sheffield-blue/50 group-focus:ring-4 group-focus:ring-sheffield-blue/70 group-focus:outline-none">
                        <svg class="w-3.5 h-3.5 text-white rtl:rotate-180" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m1 9 4-4-4-4" />
                        </svg>
                        <span class="sr-only">Next</span>
                    </span>
                </button>
            </section>
        </section>
    </div>
</div>
