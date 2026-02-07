<?php

use Livewire\Component;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Computed;
use App\Services\ProductService;

new #[Defer] class extends Component {
    public string $type;
    public array $context = [];
    public bool $slider = true;
    public int $limit = 8;

    #[Computed]
    public function products()
    {
        return app(ProductService::class)->recommend($this->type, $this->context, $this->limit);
    }
};
?>

@placeholder
    <div class="pt-10">
        <flux:skeleton animate="shimmer" class="w-44 h-5 mb-4" />
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @for ($i = 1; $i <= 6; $i++)
                <x-product-card-placeholder />
            @endfor
        </div>
    </div>
@endplaceholder

<div @class(['pt-10' => $this->products->isNotEmpty()])>
    @if ($this->products->isNotEmpty())
        <h3 class="text-lg font-semibold mb-4">
            {{ match ($type) {
                'similar' => 'Similar Products',
                'bought_together' => 'Frequently Bought Together',
                'recently_viewed' => 'Recently Viewed Items',
                default => 'You may also like',
            } }}
        </h3>

        @if ($slider)
            <div x-data="{
                swiper: null,
                init() {
                    if (this.swiper) {
                        this.swiper.destroy(true, true);
                    }

                    this.$nextTick(() => {
                        this.swiper = new Swiper('#{{ $type }}', {
                            slidesPerView: 2,
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
                                1280: {
                                    slidesPerView: 6,
                                },
                            },
                        });
                    });

                }
            }" class="relative">
                <div class="swiper px-5" id="{{ $type }}">
                    <div class="swiper-wrapper  pb-5">
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
        @else
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach ($this->products as $product)
                    <livewire:product-card :product="$product" />
                @endforeach
            </div>
        @endif
    @endif
</div>
