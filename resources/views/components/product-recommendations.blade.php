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

    // Swiper configuration properties
    public bool $autoplay = true;
    public int $autoplayDelay = 3000;
    public int $speed = 400;
    public bool $loop = true;

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
        <flux:heading size="lg" level="2"
            class="text-base! sm:text-lg! md:text-xl! lg:text-2xl! xl:text-3xl! font-serif! font-semibold! mb-4!">
            {{ match ($type) {
                'similar' => 'Similar Products',
                'up_sells' => 'You May Also Like',
                'bought_together' => 'Frequently Bought Together',
                'recently_viewed' => 'Recently Viewed Items',
                default => 'You May Also Like',
            } }}
        </flux:heading>

        @if ($slider)
            <div x-data="{
                swiper: null,
                init() {
                    if (this.swiper) {
                        this.swiper.destroy(true, true);
                    }
            
                    this.swiper = new Swiper('#{{ $type }}', {
                        slidesPerView: 2,
                        spaceBetween: 12,
                        loop: {{ $loop ? 'true' : 'false' }},
                        speed: {{ $speed }},
                        // Allow clicks on links inside slides
                        preventClicks: false,
                        preventClicksPropagation: false,
                        touchStartPreventDefault: false,
                        @if ($autoplay) autoplay: {
                                delay: {{ $autoplayDelay }},
                                disableOnInteraction: false,
                                pauseOnMouseEnter: true,
                            }, @endif
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
            
                    this.$nextTick(() => {
                        document.getElementById('{{ $type }}').classList.remove('opacity-0');
                    });
            
                }
            }" class="relative">
                <div class="swiper px-5" id="{{ $type }}">
                    <div class="swiper-wrapper  pb-5">
                        @foreach ($this->products as $product)
                            <div class="swiper-slide h-auto!" wire:key="rec-{{ $type }}-{{ $product->id }}">
                                <livewire:product-card :product="$product" :key="'rec-card-' . $type . '-' . $product->id" />
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Nav Buttons — inside container, no overflow bleed --}}
                <button type="button" @click="swiper?.slidePrev()"
                    class="absolute top-1/2 left-0 -translate-x-1/2 -translate-y-1/2 z-1 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                    <flux:icon.chevron-left class="size-3.5 text-white" />
                    <span class="sr-only">Previous</span>
                </button>

                <button type="button" @click="swiper?.slideNext()"
                    class="absolute top-1/2 right-0 translate-x-1/2 -translate-y-1/2 z-1 w-7 h-7 rounded-full flex items-center justify-center bg-black/20 hover:bg-black/40 backdrop-blur-sm border border-white/20 hover:border-white/40 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                    <flux:icon.chevron-right class="size-3.5 text-white" />
                    <span class="sr-only">Next</span>
                </button>
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach ($this->products as $product)
                    <livewire:product-card :product="$product" :key="'rec-card-' . $type . '-' . $product->id" />
                @endforeach
            </div>
        @endif
    @endif
</div>
