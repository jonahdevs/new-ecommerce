<?php

use Artesaos\SEOTools\Facades\SEOMeta;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Contact & Showrooms — Sheffield')] class extends Component
{
    public function mount(): void
    {
        SEOMeta::setDescription('Visit a Sheffield showroom in Nairobi, Mombasa, Kampala or Kigali. Equipment on the floor, spares in stock, and engineers on call.');
    }
}; ?>

@php
    $showrooms = [
        [
            'slug'     => 'nairobi',
            'city'     => 'Nairobi',
            'country'  => 'Kenya',
            'isHQ'     => true,
            'address'  => 'Off Old Mombasa Road, before the Nairobi SGR Terminus',
            'pobox'    => 'P.O. Box 29 – 00606, Nairobi Kenya',
            'phones'   => ['+254 713 777 111', '+254 713 444 000'],
            'email'    => 'info@sheffieldafrica.com',
            'hours'    => 'Mon–Fri · 8:00 – 17:30 · Sat · 9:00 – 14:00',
            'services' => ['Showroom', 'Warehouse', 'Service & Spares', 'Trade Counter'],
            'lat'      => -1.3194,
            'lng'      => 36.8842,
        ],
        [
            'slug'     => 'mombasa',
            'city'     => 'Mombasa',
            'country'  => 'Kenya',
            'isHQ'     => false,
            'address'  => 'Petrocity Complex 1st Floor, Off Links Road, Nyali',
            'phones'   => ['+254 713 777 111', '+254 713 317 214'],
            'email'    => 'mombasa@sheffieldafrica.com',
            'hours'    => 'Mon–Fri · 8:00 – 17:00 · Sat · 9:00 – 13:00',
            'services' => ['Showroom', 'Service & Spares', 'Coastal Logistics'],
            'lat'      => -4.0473,
            'lng'      => 39.6634,
        ],
        [
            'slug'     => 'kampala',
            'city'     => 'Kampala',
            'country'  => 'Uganda',
            'isHQ'     => false,
            'address'  => 'Bugolobi Hardware City, Block 3 Room 102, Mulwana Road',
            'phones'   => ['+256 741 177 711', '+256 741 177 712'],
            'email'    => 'uganda@sheffieldafrica.com',
            'hours'    => 'Mon–Fri · 8:30 – 17:30 · Sat · 9:00 – 13:00',
            'services' => ['Showroom', 'Service & Spares'],
            'lat'      => 0.3163,
            'lng'      => 32.5822,
        ],
        [
            'slug'     => 'kigali',
            'city'     => 'Kigali',
            'country'  => 'Rwanda',
            'isHQ'     => false,
            'address'  => 'Kicukiro Street, KK 500 ST',
            'phones'   => ['+250 794 007 302'],
            'email'    => 'rwanda@sheffieldafrica.com',
            'hours'    => 'Mon–Fri · 8:00 – 17:00 · Sat · 9:00 – 13:00',
            'services' => ['Showroom', 'Service'],
            'lat'      => -1.9499,
            'lng'      => 30.0588,
        ],
    ];
@endphp

<div class="page-fade">
    <div class="shell pt-4 pb-20">
        {{-- Breadcrumb --}}
        <nav class="mb-4 flex items-center gap-1.5 text-[12.5px] text-ink-3" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink" wire:navigate>Home</a>
            <flux:icon.chevron-right variant="micro" class="size-3" />
            <span class="text-ink">Contact & Showrooms</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight">Contact & Showrooms</h1>
        <p class="mt-2 text-[14.5px] text-ink-3">
            Equipment on the floor, spares in stock, and engineers on call across four cities.
        </p>

        {{-- Showroom band --}}
        <div class="mt-10 overflow-hidden rounded-md bg-brand-blue-700 text-[#f3eadd]"
            x-data="{ active: 'nairobi' }">
            <div class="grid min-h-120 grid-cols-1 lg:grid-cols-[1.1fr_1fr]">

                {{-- Map column --}}
                <div class="p-8">
                    <svg viewBox="0 0 360 420" class="block size-full">
                        <defs>
                            <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                                <path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.5" />
                            </pattern>
                        </defs>
                        <rect width="360" height="420" fill="url(#grid)" />
                        <g opacity="0.18" fill="#fff">
                            <path d="M180 90 L 280 100 L 295 160 L 285 220 L 240 245 L 195 260 L 175 240 L 165 180 Z" />
                            <path d="M120 130 L 175 130 L 180 200 L 130 215 L 105 195 L 100 165 Z" />
                            <path d="M95 220 L 135 215 L 140 250 L 110 260 L 90 248 Z" />
                            <path d="M135 250 L 200 248 L 260 270 L 285 320 L 240 365 L 180 360 L 130 320 L 120 290 Z" />
                        </g>
                        @foreach ($showrooms as $loc)
                            @php
                                $x = (($loc['lng'] - 28) / (42 - 28)) * 360;
                                $y = ((6 - $loc['lat']) / (6 - (-6))) * 420;
                                $anchor = in_array($loc['slug'], ['kigali', 'kampala']) ? 'end' : 'start';
                                $tx = $x + (in_array($loc['slug'], ['kigali', 'kampala']) ? -10 : 10);
                            @endphp
                            <g class="cursor-pointer" @click="active = '{{ $loc['slug'] }}'">
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="14" fill="hsl(354 68% 45% / 0.25)"
                                    x-show="active === '{{ $loc['slug'] }}'" />
                                <circle cx="{{ $x }}" cy="{{ $y }}" :r="active === '{{ $loc['slug'] }}' ? 6 : 4"
                                    fill="hsl(354 68% 45%)" stroke="#fff" stroke-width="1.5" />
                                <text x="{{ $tx }}" y="{{ $y + 4 }}" text-anchor="{{ $anchor }}"
                                    font-size="11" fill="rgba(255,255,255,0.85)"
                                    :font-weight="active === '{{ $loc['slug'] }}' ? 700 : 500">
                                    {{ $loc['city'] }}{{ $loc['isHQ'] ? ' ★' : '' }}
                                </text>
                            </g>
                        @endforeach
                    </svg>
                </div>

                {{-- Detail column --}}
                <div class="flex flex-col border-t border-white/10 p-10 lg:border-t-0 lg:border-l">
                    <div class="text-[11.5px] font-bold tracking-[0.12em] text-brand-500 uppercase">
                        Visit a Sheffield showroom
                    </div>
                    <h2 class="mt-2 font-serif text-3xl font-normal text-[#f6ecd9]">
                        Across four cities. <span class="text-brand-500">Always nearby.</span>
                    </h2>
                    <p class="mt-3 max-w-md text-[13.5px] leading-relaxed text-[#c9bea4]">
                        Equipment on the floor for hands-on demos, spares in stock, and engineers on call. Walk in or book a fitting consultation.
                    </p>

                    {{-- Tabs --}}
                    <div class="mt-5 flex border-b border-white/12">
                        @foreach ($showrooms as $loc)
                            <button type="button" @click="active = '{{ $loc['slug'] }}'"
                                class="-mb-px inline-flex cursor-pointer items-center gap-1.5 px-3.5 py-2.5 text-[13px] transition"
                                :class="active === '{{ $loc['slug'] }}'
                                    ? 'border-b-2 border-brand-500 font-semibold text-[#f6ecd9]'
                                    : 'border-b-2 border-transparent text-[#9c927c] hover:text-[#d8c79d]'">
                                {{ $loc['city'] }}
                                @if ($loc['isHQ'])
                                    <span class="rounded-sm bg-brand-500 px-1.5 py-px text-[9px] tracking-wider text-white">HQ</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    {{-- Detail panels --}}
                    @foreach ($showrooms as $loc)
                        <div class="mt-5 flex-1" x-show="active === '{{ $loc['slug'] }}'">
                            <div class="font-serif text-xl text-[#f6ecd9]">{{ $loc['address'] }}</div>
                            @if (!empty($loc['pobox']))
                                <div class="mt-0.5 text-[13px] text-[#c9bea4]">{{ $loc['pobox'] }}</div>
                            @endif
                            <div class="text-[13px] text-[#c9bea4]">{{ $loc['country'] }}</div>

                            <div class="mt-4 grid grid-cols-1 gap-2 text-[12.5px] text-[#d8c79d] sm:grid-cols-2">
                                @foreach ($loc['phones'] as $phone)
                                    <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}"
                                        class="inline-flex items-center gap-2 hover:text-white">
                                        <flux:icon.phone variant="micro" class="size-3.5 text-brand-500" /> {{ $phone }}
                                    </a>
                                @endforeach
                                <a href="mailto:{{ $loc['email'] }}"
                                    class="inline-flex items-center gap-2 hover:text-white">
                                    <flux:icon.envelope variant="micro" class="size-3.5 text-brand-500" /> {{ $loc['email'] }}
                                </a>
                                <span class="inline-flex items-center gap-2">
                                    <flux:icon.clock variant="micro" class="size-3.5 text-brand-500" /> Open today
                                </span>
                            </div>

                            <div class="mt-3 rounded bg-white/8 px-3 py-2.5 text-[12.5px] leading-relaxed text-[#d8c79d]">
                                {{ $loc['hours'] }}
                            </div>

                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($loc['services'] as $service)
                                    <span class="rounded-full bg-white/8 px-2.5 py-1 text-[11px] text-[#d8c79d]">{{ $service }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="mt-6 flex gap-2">
                        <flux:button variant="primary" icon-trailing="arrow-right">Get directions</flux:button>
                        <flux:button class="bg-white/8! border-white/16! text-[#f6ecd9]!">Book a showroom visit</flux:button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Get in touch cards --}}
        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div class="rounded-md border border-zinc-200 bg-white p-6">
                <flux:icon.envelope variant="outline" class="size-6 text-brand-500" />
                <div class="mt-3 font-semibold">General enquiries</div>
                <a href="mailto:info@sheffieldafrica.com" class="mt-1 block text-[13.5px] text-ink-3 hover:text-ink">info@sheffieldafrica.com</a>
            </div>
            <div class="rounded-md border border-zinc-200 bg-white p-6">
                <flux:icon.phone variant="outline" class="size-6 text-brand-500" />
                <div class="mt-3 font-semibold">Call us</div>
                <a href="tel:+254713777111" class="mt-1 block text-[13.5px] text-ink-3 hover:text-ink">+254 713 777 111</a>
                <a href="tel:+254713444000" class="block text-[13.5px] text-ink-3 hover:text-ink">+254 713 444 000</a>
            </div>
            <div class="rounded-md border border-zinc-200 bg-white p-6">
                <flux:icon.document-text variant="outline" class="size-6 text-brand-500" />
                <div class="mt-3 font-semibold">Request a quote</div>
                <p class="mt-1 text-[13.5px] text-ink-3">Upload your BOQ or tender — we respond within 24 hours.</p>
                <flux:button variant="primary" size="sm" class="mt-3">Start a quote</flux:button>
            </div>
        </div>
    </div>
</div>
