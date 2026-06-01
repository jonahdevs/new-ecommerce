@php
    // TODO: extract into a view composer + cache once this becomes hot
    $footerCategories = \App\Models\CategoryPlacement::query()
        ->with('category')
        ->where('location', \App\Enums\CategorySection::FOOTER)
        ->where('status', \App\Enums\CategoryStatus::ACTIVE)
        ->orderBy('sort_order')
        ->get()
        ->pluck('category')
        ->filter();

    // TODO: extract into a view composer + cache once this becomes hot
    $showrooms = \App\Models\Showroom::query()
        ->orderBy('sort_order')
        ->orderBy('city')
        ->get();

    $business = app(\App\Settings\BusinessSettings::class);
    $branding = app(\App\Settings\BrandingSettings::class);
    $social = app(\App\Settings\SocialSettings::class);

    // Fall back to the founding defaults until an admin fills the settings.
    $storeName = $branding->store_name ?: config('app.name', 'Sheffield');
    $tagline = $branding->tagline ?: 'Commercial kitchen equipment for restaurants, hotels and catering operations across East Africa. Since 2003.';
    $contactEmail = $business->contact_email ?: 'info@sheffieldafrica.com';
    $contactPhone = $business->contact_phone ?: '+254 713 777 111';
    $legalName = $business->legal_name ?: 'Sheffield Steel Systems Ltd';

    $socialLinks = array_filter([
        'Facebook' => $social->facebook_url,
        'Instagram' => $social->instagram_url,
        'X' => $social->x_url,
        'LinkedIn' => $social->linkedin_url,
        'YouTube' => $social->youtube_url,
    ]);
    $whatsapp = preg_replace('/\D+/', '', (string) $social->whatsapp_number);
@endphp

<footer class="mt-20 bg-brand-blue-500 pt-16 pb-8 text-[#e6ddc8]">
    <div class="shell">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
            <div class="md:col-span-4">
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
                    <a href="tel:{{ preg_replace('/\s+/', '', $contactPhone) }}" class="inline-flex items-center gap-2 hover:text-white">
                        <flux:icon.phone variant="micro" class="size-3.5" /> {{ $contactPhone }}
                    </a>
                </div>
                @if (count($socialLinks) || $whatsapp)
                    <div class="mt-5 flex flex-wrap gap-x-4 gap-y-2 text-[12px] font-medium tracking-wide text-[#d8c79d]">
                        @foreach ($socialLinks as $label => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="hover:text-white">{{ $label }}</a>
                        @endforeach
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="hover:text-white">WhatsApp</a>
                        @endif
                    </div>
                @endif
            </div>

            @foreach ($showrooms->chunk(2) as $group)
                <div class="md:col-span-2">
                    <h3 class="mb-4 text-xs font-bold tracking-[0.1em] text-[#d8c79d] uppercase">Showrooms</h3>
                    <div class="flex flex-col gap-5">
                        @foreach ($group as $loc)
                            <div>
                                <div class="inline-flex items-center gap-2 text-[13px] font-semibold text-[#f3eadd]">
                                    {{ $loc->city }}
                                    @if ($loc->is_hq)
                                        <span
                                            class="rounded-sm bg-brand-500 px-1.5 py-px text-[9px] tracking-wider text-white">HQ</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-[12px] leading-snug text-[#c9bea4]">
                                    {{ $loc->address }}, {{ $loc->country }}
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
            @endforeach

            <div class="md:col-span-2">
                <h3 class="mb-4 text-xs font-bold tracking-[0.1em] text-[#d8c79d] uppercase">Business</h3>
                <ul class="space-y-2.5 text-[13.5px] text-[#c9bea4]">
                    <li><a href="{{ route('quote.request') }}" class="hover:text-white" wire:navigate>Request a quote</a></li>
                    <li><a href="{{ route('page.show', 'trade-accounts') }}" class="hover:text-white" wire:navigate>Trade accounts</a></li>
                    <li><a href="{{ route('page.show', 'installation') }}" class="hover:text-white" wire:navigate>Installation</a></li>
                    <li><a href="{{ route('page.show', 'service-contracts') }}" class="hover:text-white" wire:navigate>Service contracts</a></li>
                    <li><a href="{{ route('page.show', 'spec-sheets') }}" class="hover:text-white" wire:navigate>Spec sheets</a></li>
                </ul>
            </div>

            <div class="md:col-span-2">
                <h3 class="mb-4 text-xs font-bold tracking-[0.1em] text-[#d8c79d] uppercase">Shop</h3>
                <ul class="space-y-2.5 text-[13.5px] text-[#c9bea4]">
                    @foreach ($footerCategories as $category)
                        <li><a href="{{ route('category.show', $category) }}" class="hover:text-white" wire:navigate>{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div
            class="mt-14 flex flex-wrap items-center justify-between gap-4 border-t border-[#e6ddc8]/15 pt-6 text-[12.5px] text-[#9c927c]">
            <div class="flex items-center gap-4">
                <span>&copy; {{ date('Y') }} {{ $legalName }}.</span>
                <a href="{{ route('page.show', 'terms-and-conditions') }}" class="hover:text-white" wire:navigate>Terms</a>
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
