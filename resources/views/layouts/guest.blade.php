<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
</head>

<body class="bg-zinc-50 text-zinc-700 font-sans min-h-screen">

    <div class="bg-sheffield-red text-white">
        <section class="container mx-auto px-4">
            <div class="flex items-center justify-between py-2 text-sm gap-4">
                {{-- contact info - Hidden on mobile, visible on md+ --}}
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
                                effect: 'slide',
                                // Optional: Add fade effect for smoother transitions
                                // effect: 'fade',
                                // fadeEffect: {
                                //     crossFade: true
                                // },
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
                                    Free Shipping on Orders Over KES 10,000 <span class="underline font-medium">Learn
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

                <!-- Support Link - Hidden on mobile, visible on md+ -->
                <div class="hidden md:flex items-center gap-4">
                    <a href="" class="flex items-center gap-2 group hover:opacity-90 transition-opacity">
                        <flux:icon.question-mark-circle class="size-5 shrink-0" />
                        <span class="group-hover:underline text-xs lg:text-sm">Support</span>
                    </a>
                </div>
            </div>
        </section>
    </div>

    <livewire:app-bar />

    <main>
        {{ $slot }}
    </main>

    <x-toast-notification />
    <livewire:footer />

    @fluxScripts

    <!-- After your notification component -->
    <script>
        // Bridge Laravel flash messages to Alpine notifications
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
