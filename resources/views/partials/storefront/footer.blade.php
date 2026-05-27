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

    // TODO: move to a Settings model / table — phones, addresses come from config for now
    $showrooms = [
        ['city' => 'Nairobi',  'country' => 'Kenya',   'isHQ' => true,  'address' => 'Sheffield House, Mombasa Road', 'suburb' => 'Industrial Area', 'phone' => '+254 20 234 5600'],
        ['city' => 'Mombasa',  'country' => 'Kenya',   'isHQ' => false, 'address' => 'Nyerere Avenue, Plot 14',        'suburb' => 'Mombasa Island',  'phone' => '+254 41 230 0120'],
        ['city' => 'Kampala',  'country' => 'Uganda',  'isHQ' => false, 'address' => 'Plot 42, Yusuf Lule Road',       'suburb' => 'Nakasero',        'phone' => '+256 414 250 600'],
        ['city' => 'Kigali',   'country' => 'Rwanda',  'isHQ' => false, 'address' => 'KG 11 Avenue, Kacyiru',           'suburb' => 'Kacyiru',         'phone' => '+250 788 305 600'],
    ];
@endphp

<footer class="mt-20 bg-brand-blue-700 pt-16 pb-8 text-[#e6ddc8]">
    <div class="shell">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-12">
            <div class="md:col-span-4">
                <a href="{{ route('home') }}" class="inline-flex items-center" wire:navigate aria-label="{{ config('app.name', 'Sheffield') }} — Home">
                    <img src="/logo-inverse.png" alt="{{ config('app.name', 'Sheffield') }}" class="h-9 w-auto" />
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-[#c9bea4]">
                    Commercial kitchen equipment for restaurants, hotels and catering operations across East Africa. Since 2003.
                </p>
                <div class="mt-5 flex flex-col gap-2 text-[13.5px] text-[#c9bea4]">
                    <span class="inline-flex items-center gap-2">
                        <flux:icon.envelope variant="micro" class="size-3.5" /> sales@sheffield.co.ke
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <flux:icon.chat-bubble-left-right variant="micro" class="size-3.5" /> WhatsApp +254 711 234 567
                    </span>
                </div>
            </div>

            @foreach (array_chunk($showrooms, 2) as $group)
                <div class="md:col-span-2">
                    <h3 class="mb-4 text-xs font-bold tracking-[0.1em] text-[#d8c79d] uppercase">Showrooms</h3>
                    <div class="flex flex-col gap-5">
                        @foreach ($group as $loc)
                            <div>
                                <div class="inline-flex items-center gap-2 text-[13px] font-semibold text-[#f3eadd]">
                                    {{ $loc['city'] }}
                                    @if ($loc['isHQ'])
                                        <span class="rounded-sm bg-brand-500 px-1.5 py-px text-[9px] tracking-wider text-white">HQ</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-[12px] leading-snug text-[#c9bea4]">
                                    {{ $loc['address'] }}<br>
                                    {{ $loc['suburb'] }}, {{ $loc['country'] }}
                                </div>
                                <a href="#" class="mt-1 inline-block text-[12px] text-[#d8c79d] hover:text-white">{{ $loc['phone'] }}</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="md:col-span-2">
                <h3 class="mb-4 text-xs font-bold tracking-[0.1em] text-[#d8c79d] uppercase">Business</h3>
                <ul class="space-y-2.5 text-[13.5px] text-[#c9bea4]">
                    <li><a href="#" class="hover:text-white">Request a quote</a></li>
                    <li><a href="#" class="hover:text-white">Trade accounts</a></li>
                    <li><a href="#" class="hover:text-white">Installation</a></li>
                    <li><a href="#" class="hover:text-white">Service contracts</a></li>
                    <li><a href="#" class="hover:text-white">Spec sheets</a></li>
                </ul>
            </div>

            <div class="md:col-span-2">
                <h3 class="mb-4 text-xs font-bold tracking-[0.1em] text-[#d8c79d] uppercase">Shop</h3>
                <ul class="space-y-2.5 text-[13.5px] text-[#c9bea4]">
                    @foreach ($footerCategories as $category)
                        <li><a href="#" class="hover:text-white" wire:navigate>{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="mt-14 flex flex-wrap items-center justify-between gap-4 border-t border-[#e6ddc8]/15 pt-6 text-[12.5px] text-[#9c927c]">
            <div class="flex items-center gap-4">
                <span>&copy; {{ date('Y') }} Sheffield East Africa Ltd.</span>
                <a href="#" class="hover:text-white">Terms</a>
                <a href="#" class="hover:text-white">Privacy</a>
                <a href="#" class="hover:text-white">Cookies</a>
            </div>
            <div class="flex items-center gap-4">
                <span>Authorised distributor</span>
                <span class="h-4 w-px bg-[#e6ddc8]/20"></span>
                <span class="font-serif text-sm text-[#d8c79d]">NSF · CE · KEBS</span>
            </div>
        </div>
    </div>
</footer>
