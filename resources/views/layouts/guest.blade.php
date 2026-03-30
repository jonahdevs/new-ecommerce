<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')

</head>

<body class="bg-zinc-50 text-zinc-700 font-sans min-h-screen">


    {{-- Announcement / Promo Bar --}}
    <div class="bg-brand-primary text-brand-primary-content">
        <section class="container mx-auto px-4">
            <div class="flex items-center justify-between py-2 text-sm gap-4">

                {{-- Contact info — hidden on mobile --}}
                <div class="hidden md:flex items-center gap-3 lg:gap-4">
                    <div class="flex items-center gap-2">
                        <flux:icon.phone class="w-4.5 h-4.5 shrink-0" />
                        <span class="text-xs lg:text-sm">(254) 713 777 111</span>
                    </div>
                </div>

                {{-- Vertical Promotion Carousel --}}
                <div class="flex-1 md:flex-none md:max-w-md lg:max-w-lg mx-auto overflow-hidden h-6"
                    x-data="{
                        swiper: null,
                        init() {
                            this.$nextTick(() => {
                                this.initializeSwiper();
                            });
                        },
                        initializeSwiper() {
                            this.swiper = new Swiper('.promoSwiper', {
                                direction: 'vertical',
                                loop: true,
                                speed: 800,
                                autoplay: {
                                    delay: 3000,
                                    disableOnInteraction: false,
                                },
                            });
                        },
                        destroy() {
                            if (this.swiper) {
                                this.swiper.destroy(true, true);
                            }
                        }
                    }">
                    <div class="swiper promoSwiper h-full">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide flex items-center justify-center">
                                <a href="#"
                                    class="text-center text-xs sm:text-sm hover:opacity-90 transition-opacity">
                                    Get 50% off on Member Exclusive Month <span class="underline font-medium">Shop
                                        Now</span>
                                </a>
                            </div>
                            <div class="swiper-slide flex items-center justify-center">
                                <a href="#"
                                    class="text-center text-xs sm:text-sm hover:opacity-90 transition-opacity">
                                    Free Shipping on Orders Over {{ get_currency_symbol() }} 10,000 <span class="underline font-medium">Learn
                                        More</span>
                                </a>
                            </div>
                            <div class="swiper-slide flex items-center justify-center">
                                <a href="#"
                                    class="text-center text-xs sm:text-sm hover:opacity-90 transition-opacity">
                                    New Arrivals: Latest Kitchen Equipment <span
                                        class="underline font-medium">Explore</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Support link — hidden on mobile --}}
                <div class="hidden md:flex items-center gap-4">
                    <a href="" class="flex items-center gap-2 group hover:opacity-90 transition-opacity">
                        <flux:icon.question-mark-circle class="size-5 shrink-0" />
                        <span class="group-hover:underline text-xs lg:text-sm">Support</span>
                    </a>
                </div>

            </div>
        </section>
    </div>

    <div class="sticky top-0 left-0 z-100 w-full">
        {{-- App Bar (logo + search + nav categories) --}}
        @persist('app-bar')
            <livewire:app-bar />
        @endpersist

    </div>
    {{-- END sticky header wrapper --}}

    <main>
        {{ $slot }}
    </main>

    <x-toast-notification />

    @persist('footer')
        <x-footer />
    @endpersist

    @fluxScripts

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if (session('success'))
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        variant: 'success',
                        message: @js(session('success'))
                    }
                }));
            @endif

            @if (session('error'))
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        variant: 'danger',
                        message: @js(session('error'))
                    }
                }));
            @endif

            @if (session('warning'))
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        variant: 'warning',
                        message: @js(session('warning'))
                    }
                }));
            @endif

            @if (session('info'))
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        variant: 'info',
                        message: @js(session('info'))
                    }
                }));
            @endif
        });
    </script>
</body>

</html>
