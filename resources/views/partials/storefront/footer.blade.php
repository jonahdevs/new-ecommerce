@php
    // TODO: extract into a view composer + cache once this becomes hot
    $footerCategories = \App\Models\CategoryPlacement::query()
        ->with('category')
        ->where('location', \App\Enums\CategorySection::FOOTER)
        ->where('status', \App\Enums\CategoryStatus::ACTIVE)
        ->orderBy('sort_order')
        ->get()
        ->pluck('category')
        ->filter()
        ->take(7);

    // TODO: extract into a view composer + cache once this becomes hot
    $showrooms = \App\Models\Showroom::query()->orderBy('sort_order')->orderBy('city')->get();

    $business = app(\App\Settings\BusinessSettings::class);
    $branding = app(\App\Settings\BrandingSettings::class);
    $social = app(\App\Settings\SocialSettings::class);

    // Fall back to the founding defaults until an admin fills the settings.
    $storeName = $branding->store_name ?: config('app.name', 'Sheffield');
    $tagline =
        $branding->tagline ?:
        'Commercial kitchen equipment for restaurants, hotels and catering operations across East Africa - Since 2003.';
    $contactEmail = $business->contact_email ?: 'info@sheffieldafrica.com';
    $contactPhone = $business->contact_phone ?: '+254 713 777 111';
    $legalName = $business->legal_name ?: 'Sheffield Steel Systems Ltd';

    $whatsapp = preg_replace('/\D+/', '', (string) $social->whatsapp_number);

    $socialLinks = array_filter([
        'Facebook' => $social->facebook_url,
        'Instagram' => $social->instagram_url,
        'X' => $social->x_url,
        'LinkedIn' => $social->linkedin_url,
        'YouTube' => $social->youtube_url,
        'WhatsApp' => $whatsapp ? 'https://wa.me/' . $whatsapp : '',
    ]);

    // Brand glyphs (simple-icons paths) — Heroicons has no brand logos.
    $socialIcons = [
        'Facebook' =>
            'M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.849-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z',
        'Instagram' =>
            'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z',
        'X' =>
            'M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z',
        'LinkedIn' =>
            'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
        'YouTube' =>
            'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
        'WhatsApp' =>
            'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12.05 21.785h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884zm8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z',
    ];
@endphp

<footer class="mt-5 bg-brand-blue-500 pt-10 pb-8 text-[#e6ddc8] md:mt-10 md:pt-16">
    <div class="shell">
        <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 md:grid-cols-12">
            <div class="col-span-2 sm:col-span-3 md:col-span-4">
                <a href="{{ route('home') }}" class="inline-flex items-center" wire:navigate
                    aria-label="{{ $storeName }} — Home">
                    <img src="/logo-inverse.png" alt="{{ $storeName }}" class="h-9 w-auto" />
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-[#c9bea4]">
                    {{ $tagline }}
                </p>
                <div class="mt-5 flex flex-col gap-2 text-[13.5px] text-[#c9bea4]">
                    <a href="mailto:{{ $contactEmail }}" class="inline-flex items-center gap-2 hover:text-white">
                        <flux:icon.envelope variant="micro" class="size-3.5" /> {{ $contactEmail }}
                    </a>
                    <a href="tel:{{ preg_replace('/\s+/', '', $contactPhone) }}"
                        class="inline-flex items-center gap-2 hover:text-white">
                        <flux:icon.phone variant="micro" class="size-3.5" /> {{ $contactPhone }}
                    </a>
                </div>
                @if (count($socialLinks))
                    <div class="mt-5 flex flex-wrap gap-2.5">
                        @foreach ($socialLinks as $label => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                                aria-label="{{ $label }}" title="{{ $label }}"
                                class="flex size-9 items-center justify-center rounded-full border border-white/15 bg-white/5 text-[#e6ddc8] transition hover:bg-white hover:text-brand-blue-600">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true">
                                    <path d="{{ $socialIcons[$label] }}" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="col-span-2 sm:col-span-3 md:col-span-4">
                <h3 class="mb-4 text-xs font-bold tracking-widest text-[#d8c79d] uppercase">Showrooms</h3>
                <div class="grid grid-cols-1 gap-x-6 gap-y-5 min-[420px]:grid-cols-2">
                    @foreach ($showrooms as $loc)
                        <div>
                            <div class="inline-flex items-center gap-2 text-[13px] font-semibold text-[#f3eadd]">
                                {{ $loc->city }}
                                @if ($loc->is_hq)
                                    <span
                                        class="rounded-sm bg-brand-500 px-1.5 py-px text-[9px] tracking-wider text-white">HQ</span>
                                @endif
                            </div>
                            <div class="mt-1 text-[12px] leading-snug text-[#c9bea4]">
                                {{ $loc->address }}@empty($loc->pobox), {{ $loc->country }}@endempty
                                @if (!empty($loc->pobox))
                                    <br>{{ $loc->pobox }}
                                @endif
                            </div>
                            <div class="mt-1.5 flex flex-col gap-0.5">
                                <div class="text-[12px] text-[#d8c79d]">
                                    @foreach ($loc->phones as $i => $phone)
                                        @if ($i > 0)
                                            <span class="opacity-50">/</span>
                                        @endif
                                        <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}"
                                            class="hover:text-white">{{ $phone }}</a>
                                    @endforeach
                                </div>
                                @if ($loc->email)
                                    <a href="mailto:{{ $loc->email }}"
                                        class="text-[12px] text-[#d8c79d] hover:text-white">{{ $loc->email }}</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-span-2 min-[420px]:col-span-1 md:col-span-2">
                <h3 class="mb-4 text-xs font-bold tracking-widest text-[#d8c79d] uppercase">Business</h3>
                <ul class="space-y-2.5 text-[13.5px] text-[#c9bea4]">
                    <li><a href="{{ route('categories.index') }}" class="hover:text-white" wire:navigate>All
                            categories</a></li>
                    <li><a href="{{ route('quote.request') }}" class="hover:text-white" wire:navigate>Request a
                            quote</a></li>
                </ul>
            </div>

            <div class="col-span-2 min-[420px]:col-span-1 md:col-span-2">
                <h3 class="mb-4 text-xs font-bold tracking-widest text-[#d8c79d] uppercase">Shop</h3>
                <ul class="space-y-2.5 text-[13.5px] text-[#c9bea4]">
                    @foreach ($footerCategories as $category)
                        <li><a href="{{ route('category.show', $category) }}" class="hover:text-white"
                                wire:navigate>{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div
            class="mt-14 flex flex-wrap items-center justify-between gap-4 border-t border-[#e6ddc8]/15 pt-6 text-[12.5px] text-[#9c927c]">
            <div class="flex items-center gap-4">
                <span>&copy; {{ date('Y') }} {{ $legalName }}.</span>
                <a href="{{ route('page.show', 'terms-and-conditions') }}" class="hover:text-white"
                    wire:navigate>Terms</a>
                <a href="{{ route('page.show', 'privacy-policy') }}" class="hover:text-white" wire:navigate>Privacy</a>
                <a href="{{ route('page.show', 'cookie-policy') }}" class="hover:text-white" wire:navigate>Cookies</a>
            </div>
            <div class="flex items-center gap-4">
                <span>Authorised distributor</span>
                <span class="h-4 w-px bg-[#e6ddc8]/20"></span>
                <span class="font-serif text-sm text-[#d8c79d]">NSF · CE · KEBS</span>
            </div>
        </div>
    </div>
</footer>
