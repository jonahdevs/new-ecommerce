<?php

use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Component;

new #[Defer] class extends Component {
    #[Computed]
    public function products()
    {
        return Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path'])
            ->withAvg('reviews', 'rating')
            ->with('brand:id,name')
            ->active()
            ->inRandomOrder()
            ->limit(20)
            ->get();
    }

    public function mount()
    {
        $this->dispatch('products-loaded');
    }
};
?>

@placeholder
    <div class="">

        <section class="flex items-center justify-between py-4 ">
            <h2 class="font-semibold text-xl text-zinc-800">You May Also Like</h2>
        </section>

        <div class=" grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 pb-5">
            @for ($i = 0; $i < 12; $i++)
                <x-product-card-placeholder />
            @endfor
        </div>
    </div>
@endplaceholder

<div>
    <div class="">

        <section class="flex items-center justify-between py-4 ">
            <h2 class="font-semibold text-xl text-zinc-800">You May Also Like</h2>

            <a href="{{ route('products') }}" wire:navigate class="text-sheffield-red hover:underline text-sm">View
                All</a>
        </section>

        <section class="">
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
                            },
                            480: {
                                slidesPerView: 2,
                            },
                            640: {
                                slidesPerView: 3,
                                grid: {
                                    rows: 2,
                                    fill: 'row'
                                }
                            },
                            768: {
                                slidesPerView: 4,
                                grid: {
                                    rows: 2,
                                    fill: 'row'
                                }
                            },
                            1024: {
                                slidesPerView: 5,
                                grid: {
                                    rows: 2,
                                    fill: 'row'
                                }
                            },
                            1280: {
                                slidesPerView: 6,
                                grid: {
                                    rows: 2,
                                    fill: 'row'
                                }
                            },
                        },
                    });
                }
            }">
                <div class="swiper" id="youMayAlsoLike">
                    <div class="swiper-wrapper pb-5">
                        @foreach ($this->products as $product)
                            <div class="swiper-slide h-auto!">
                                <livewire:product-card :product="$product" :key="'product-' . $product->id" />
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Navigation buttons -->
                <button type="button" @click="swiper?.slidePrev()"
                    class="absolute top-1/2 left-0  -translate-y-1/2 -translate-x-1/2 z-30 flex items-center justify-center cursor-pointer group focus:outline-none w-8 h-8 rounded-full bg-sheffield-blue/30 group-hover:bg-sheffield-blue/50 group-focus:ring-4 group-focus:ring-sheffield-blue/70 group-focus:outline-none">
                    <svg class="w-3.5 h-3.5 text-white rtl:rotate-180" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 1 1 5l4 4" />
                    </svg>
                    <span class="sr-only">Previous</span>
                </button>

                <button type="button" @click="swiper?.slideNext()"
                    class="absolute top-1/2 right-0 -translate-y-1/2 translate-x-1/2 z-30 flex items-center justify-center cursor-pointer group focus:outline-none w-8 h-8 rounded-full bg-sheffield-blue/30 group-hover:bg-sheffield-blue/50 group-focus:ring-4 group-focus:ring-sheffield-blue/70 group-focus:outline-none">
                    <svg class="w-3.5 h-3.5 text-white rtl:rotate-180" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 9 4-4-4-4" />
                    </svg>

                    <span class="sr-only">Next</span>
                </button>
            </div>
        </section>
    </div>
</div>
