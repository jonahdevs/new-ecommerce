@props([])

<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
</head>

<body {{ $attributes->merge(['class' => 'bg-white text-on-surface font-sans min-h-screen flex flex-col']) }}>

    {{-- STICKY SECTION: Promo Bar + Main Header --}}
    <div class="sticky top-0 left-0 z-20 w-full">
        @inject('general', 'App\Settings\GeneralSettings')

        {{-- Announcement / Promo Bar --}}
        <div class="bg-primary text-on-primary">
            <section class="container mx-auto px-4">
                <div class="flex items-center justify-between py-2 text-sm gap-4">

                    {{-- Contact info — hidden on mobile --}}
                    @if ($general->store_phone)
                        <div class="hidden md:flex items-center gap-3 lg:gap-4">
                            <div class="flex items-center gap-2">
                                <flux:icon.phone class="w-3.5 h-3.5 sm:w-4 sm:h-4 lg:w-4.5 lg:h-4.5 shrink-0" />
                                <span class="text-xs sm:text-sm">{{ $general->store_phone }}</span>
                            </div>
                        </div>
                    @else
                        <div class="hidden md:block"></div>
                    @endif

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
                                        Free Shipping on Orders Over {{ get_currency_symbol() }} 10,000 <span
                                            class="underline font-medium">Learn
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
                            <flux:icon.question-mark-circle class="size-4 sm:size-5 shrink-0" />
                            <span class="group-hover:underline text-xs sm:text-sm">Support</span>
                        </a>
                    </div>

                </div>
            </section>
        </div>

        {{-- Main Header (Livewire component for cart/wishlist counts) --}}
        @persist('app-bar-header')
            <livewire:app-bar-header />
        @endpersist
    </div>
    {{-- END STICKY SECTION --}}

    {{-- Category Navigation (scrolls with page) --}}
    @persist('app-bar-categories')
        <livewire:app-bar-categories />
    @endpersist

    <main class="flex-1 bg-white">
        {{ $slot }}
    </main>

    <x-customer-notification />

    @persist('footer')
        <x-footer />
    @endpersist

    {{-- WhatsApp Chat Widget --}}
    @inject('social', 'App\Settings\SocialSettings')
    @if ($social->whatsapp_number)
        @php
            $waNumber = preg_replace('/[^0-9]/', '', $social->whatsapp_number);
        @endphp
        <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Hello Sheffield, I need assistance in') }}"
            target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp"
            class="fixed bottom-6 right-6 z-50 flex items-center justify-center w-14 h-14 rounded-full bg-[#25D366] text-white shadow-lg hover:bg-[#1ebe5d] transition-colors">
            <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
        </a>
    @endif

    @fluxScripts

    {{-- Override Livewire's NProgress bar color for the guest layout --}}
    <style>
        [x-cloak] {
            display: none !important;
        }

        :root {
            --livewire-progress-bar-color: var(--secondary);
        }
    </style>

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
