<!-- ====== Footer Section Start -->
<footer class="relative z-10 bg-inverse-surface text-inverse-on-surface pb-10 pt-20">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-8">

            <!-- Company Info & Store Locations -->
            <div class="lg:col-span-1">
                <a href="/" class="inline-block mb-4">
                    @if ($general->store_logo)
                        <img src="{{ asset('storage/' . $general->store_logo) }}" alt="{{ $general->store_name }}"
                            class="h-12 w-auto" />
                    @else
                        <img src="{{ asset('logo-inverse.png') }}" alt="{{ $general->store_name }}"
                            class="h-12 w-auto" />
                    @endif
                </a>

                @if ($general->store_address)
                    <div class="mb-6">
                        <div class="flex items-start gap-3 mb-2">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 mt-1 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <div>
                                <flux:heading size="base" level="4"
                                    class="text-white! text-base! sm:text-lg! md:text-xl! mb-3">Our Store Location
                                </flux:heading>
                                <div class="space-y-1 text-xs sm:text-sm text-inverse-on-surface">
                                    <flux:text class="text-xs! sm:text-sm!">{{ $general->store_address }}</flux:text>
                                    @if ($general->store_address_line_2)
                                        <flux:text class="text-xs! sm:text-sm!">{{ $general->store_address_line_2 }}
                                        </flux:text>
                                    @endif
                                    @if ($general->store_city || $general->store_country)
                                        <flux:text class="text-xs! sm:text-sm!">
                                            {{ implode(', ', array_filter([$general->store_city, $general->store_country])) }}
                                        </flux:text>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Top Categories -->
            <div>
                <flux:heading size="base" level="4"
                    class="text-white! text-base! md:text-lg! lg:text-xl! mb-6 relative inline-block">
                    Top Categories
                    <span class="absolute top-full mt-1 left-0 w-12 h-0.5 bg-primary"></span>
                </flux:heading>
                <ul class="space-y-3">
                    @foreach ($footerCategories as $category)
                        <li>
                            <flux:link href="{{ route('shop.category', ['category' => $category->slug]) }}"
                                wire:navigate
                                class="text-inverse-on-surface hover:text-white transition-colors text-xs sm:text-sm!">
                                {{ $category->name }}
                            </flux:link>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Important Links -->
            <div>
                <flux:heading size="base" level="4"
                    class="text-white! text-base! md:text-lg! lg:text-xl! mb-6 relative inline-block">
                    Important Links
                    <span class="absolute top-full mt-1 left-0 w-12 h-0.5 bg-primary"></span>
                </flux:heading>
                <ul class="space-y-3">
                    <li>
                        <flux:link href="www.sheffieldafrica.com/about-us"
                            class="text-inverse-on-surface hover:text-white transition-colors text-xs sm:text-sm!">
                            About us
                        </flux:link>
                    </li>
                    <li>
                        <flux:link href="#"
                            class="text-inverse-on-surface hover:text-white transition-colors text-xs sm:text-sm!">
                            Contact Us
                        </flux:link>
                    </li>
                    <li>
                        <flux:link href="#"
                            class="text-inverse-on-surface hover:text-white transition-colors text-xs sm:text-sm!">
                            Faq
                        </flux:link>
                    </li>
                    <li>
                        <flux:link href="#"
                            class="text-inverse-on-surface hover:text-white transition-colors text-xs sm:text-sm!">
                            Latest Posts
                        </flux:link>
                    </li>
                    <li>
                        <flux:link href="#"
                            class="text-inverse-on-surface hover:text-white transition-colors text-xs sm:text-sm!">
                            Order Track
                        </flux:link>
                    </li>
                </ul>
            </div>

            <!-- Legal Documents -->
            <div>
                <flux:heading size="base" level="4"
                    class="text-white! text-base! md:text-lg! lg:text-xl! mb-6 relative inline-block">
                    Legal Documents
                    <span class="absolute top-full mt-1 left-0 w-12 h-0.5 bg-primary"></span>
                </flux:heading>
                <ul class="space-y-3">
                    <li>
                        <flux:link href="{{ route('privacy') }}"
                            class="text-inverse-on-surface hover:text-white transition-colors text-xs sm:text-sm!">
                            Privacy Policy
                        </flux:link>
                    </li>
                    <li>
                        <flux:link href="{{ route('terms') }}"
                            class="text-inverse-on-surface hover:text-white transition-colors text-xs sm:text-sm!">
                            Terms & Conditions
                        </flux:link>
                    </li>
                    <li>
                        <flux:link href="#"
                            class="text-inverse-on-surface hover:text-white transition-colors text-xs sm:text-sm!">
                            Warranty Policy
                        </flux:link>
                    </li>
                    <li>
                        <flux:link href="#"
                            class="text-inverse-on-surface hover:text-white transition-colors text-xs sm:text-sm!">
                            Return Policy
                        </flux:link>
                    </li>
                    <li>
                        <flux:link href="#"
                            class="text-inverse-on-surface hover:text-white transition-colors text-xs sm:text-sm!">
                            Shipping Policy
                        </flux:link>
                    </li>
                    <li>
                        <flux:link href="#"
                            class="text-inverse-on-surface hover:text-white transition-colors text-xs sm:text-sm!">
                            Refund Policy
                        </flux:link>
                    </li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div>
                <flux:heading size="base" level="4"
                    class="text-white! text-base! md:text-lg! lg:text-xl! mb-6 relative inline-block">
                    Newsletter
                    <span class="absolute top-full mt-1 left-0 w-12 h-0.5 bg-primary"></span>
                </flux:heading>
                <flux:text class="text-xs! sm:text-sm! md:text-base! text-inverse-on-surface mb-6">
                    Enter your email to receive our latest updates about our products.
                </flux:text>
                <div class="flex gap-2 flex-wrap">
                    <input type="email" placeholder="Email address"
                        class="flex-1 px-3 sm:px-4 py-2 sm:py-3 bg-white/10 border border-inverse-outline rounded text-xs sm:text-sm text-white placeholder-inverse-on-surface-muted focus:outline-none focus:ring-2 focus:ring-primary" />
                    <button type="button"
                        class="px-4 sm:px-6 py-2 sm:py-3 bg-primary hover:bg-primary-hover text-on-primary rounded font-medium text-xs sm:text-sm transition-colors">
                        Subscribe
                    </button>
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="mt-12 pt-8 border-t border-inverse-outline">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">

                <!-- Copyright -->
                <flux:text class="text-xs! sm:text-sm! text-inverse-on-surface-muted">
                    © {{ date('Y') }} {{ $general->store_name }}. All Rights Reserved.
                </flux:text>

                <!-- Payment Methods -->
                <div class="flex items-center gap-4">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal"
                        class="h-6 opacity-70 hover:opacity-100 transition-opacity" />
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa"
                        class="h-6 opacity-70 hover:opacity-100 transition-opacity" />
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard"
                        class="h-6 opacity-70 hover:opacity-100 transition-opacity" />
                    <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/American_Express_logo_%282018%29.svg"
                        alt="American Express" class="h-6 opacity-70 hover:opacity-100 transition-opacity" />
                </div>

                <!-- Social Media -->
                @php
                    $socialLinks = array_filter([
                        'facebook' => $social->facebook_url,
                        'twitter' => $social->twitter_url,
                        'instagram' => $social->instagram_url,
                        'linkedin' => $social->linkedin_url,
                        'tiktok' => $social->tiktok_url,
                        'youtube' => $social->youtube_url,
                    ]);
                @endphp
                @if (!empty($socialLinks))
                    <div class="flex items-center gap-4">
                        <flux:text class="text-xs! sm:text-sm! text-inverse-on-surface-muted mr-2">Follow Us:</flux:text>

                        @if ($social->facebook_url)
                            <a href="{{ $social->facebook_url }}" target="_blank" rel="noopener noreferrer"
                                class="text-inverse-on-surface-muted hover:text-white transition-colors"
                                aria-label="Facebook">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                            </a>
                        @endif

                        @if ($social->twitter_url)
                            <a href="{{ $social->twitter_url }}" target="_blank" rel="noopener noreferrer"
                                class="text-inverse-on-surface-muted hover:text-white transition-colors"
                                aria-label="X / Twitter">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                </svg>
                            </a>
                        @endif

                        @if ($social->instagram_url)
                            <a href="{{ $social->instagram_url }}" target="_blank" rel="noopener noreferrer"
                                class="text-inverse-on-surface-muted hover:text-white transition-colors"
                                aria-label="Instagram">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z" />
                                </svg>
                            </a>
                        @endif

                        @if ($social->linkedin_url)
                            <a href="{{ $social->linkedin_url }}" target="_blank" rel="noopener noreferrer"
                                class="text-inverse-on-surface-muted hover:text-white transition-colors"
                                aria-label="LinkedIn">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                </svg>
                            </a>
                        @endif

                        @if ($social->tiktok_url)
                            <a href="{{ $social->tiktok_url }}" target="_blank" rel="noopener noreferrer"
                                class="text-inverse-on-surface-muted hover:text-white transition-colors"
                                aria-label="TikTok">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.78 1.52V6.76a4.85 4.85 0 01-1.01-.07z" />
                                </svg>
                            </a>
                        @endif

                        @if ($social->youtube_url)
                            <a href="{{ $social->youtube_url }}" target="_blank" rel="noopener noreferrer"
                                class="text-inverse-on-surface-muted hover:text-white transition-colors"
                                aria-label="YouTube">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M23.495 6.205a3.007 3.007 0 00-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 00.527 6.205a31.247 31.247 0 00-.522 5.805 31.247 31.247 0 00.522 5.783 3.007 3.007 0 002.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 002.088-2.088 31.247 31.247 0 00.5-5.783 31.247 31.247 0 00-.5-5.805zM9.609 15.601V8.408l6.264 3.602z" />
                                </svg>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</footer>
<!-- ====== Footer Section End -->
