<?php

use App\Models\Showroom;
use App\Notifications\ContactEnquiryReceived;
use App\Settings\BusinessSettings;
use Artesaos\SEOTools\Facades\SEOMeta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Contact & Showrooms')] class extends Component {
    /** @var list<string> */
    public array $inquiryTypes = ['Sales enquiry', 'Request a quote', 'Service & spares', 'Installation', 'Trade account', 'Project consultation'];

    public string $inquiry = 'Sales enquiry';

    public string $name = '';

    public string $business = '';

    public string $email = '';

    public string $phone = '';

    public ?int $location = null;

    public string $message = '';

    public bool $consent = false;

    public bool $sent = false;

    public string $reference = '';

    public function mount(): void
    {
        SEOMeta::setDescription('Talk to a Sheffield equipment specialist — commercial kitchens, cold rooms, laundry and healthcare. Sales, service, trade accounts and project consultation by phone, WhatsApp, the form, or any of our four showrooms.');

        $requested = (string) request('inquiry');
        if (in_array($requested, $this->inquiryTypes, true)) {
            $this->inquiry = $requested;
        }

        $this->location = $this->showrooms->firstWhere('is_hq', true)?->id ?? $this->showrooms->first()?->id;
    }

    /**
     * @return Collection<int, Showroom>
     */
    #[Computed]
    public function showrooms(): Collection
    {
        return Showroom::query()->orderByDesc('is_hq')->orderBy('sort_order')->get();
    }

    public function submit(): void
    {
        $validated = $this->validate(
            [
                'inquiry' => ['required', 'string', 'in:' . implode(',', $this->inquiryTypes)],
                'name' => ['required', 'string', 'max:120'],
                'business' => ['nullable', 'string', 'max:150'],
                'email' => ['required', 'email', 'max:150'],
                'phone' => ['nullable', 'string', 'max:40'],
                'location' => ['nullable', 'integer', 'exists:showrooms,id'],
                'message' => ['required', 'string', 'max:5000'],
                'consent' => ['accepted'],
            ],
            [
                'consent.accepted' => 'Please agree to be contacted about your enquiry.',
            ],
        );

        $this->reference = 'SHF-' . random_int(100000, 999999);

        $showroom = $validated['location'] ? Showroom::find($validated['location']) : null;

        $recipient = app(BusinessSettings::class)->contact_email ?: config('mail.from.address');

        Notification::route('mail', $recipient)->notify(
            new ContactEnquiryReceived([
                'reference' => $this->reference,
                'inquiry' => $validated['inquiry'],
                'name' => $validated['name'],
                'business' => $validated['business'] ?: null,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?: null,
                'location' => $showroom ? $showroom->city . ', ' . $showroom->country : null,
                'message' => $validated['message'],
            ]),
        );

        $this->sent = true;
    }

    public function sendAnother(): void
    {
        $this->reset(['message', 'consent', 'sent', 'reference']);
    }
}; ?>

@php
    $channels = [
        [
            'icon' => 'shopping-cart',
            'title' => 'Sales & quotes',
            'desc' => 'Spec a new kitchen, price a fit-out, or convert a basket into a formal quote.',
            'lines' => [
                ['icon' => 'phone', 'label' => '+254 20 234 5600'],
                ['icon' => 'envelope', 'label' => 'sales@sheffieldafrica.com'],
            ],
            'sla' => 'Same-day response',
        ],
        [
            'icon' => 'wrench-screwdriver',
            'title' => 'Service & spares',
            'desc' => 'Breakdowns, preventive maintenance and genuine parts for equipment in the field.',
            'lines' => [
                ['icon' => 'phone', 'label' => '+254 20 234 5612'],
                ['icon' => 'envelope', 'label' => 'service@sheffieldafrica.com'],
            ],
            'sla' => '48-hr response SLA',
        ],
        [
            'icon' => 'check-badge',
            'title' => 'Trade accounts',
            'desc' => 'Business pricing, Net 30 terms, multi-site ordering and a dedicated specialist.',
            'lines' => [
                ['icon' => 'phone', 'label' => '+254 20 234 5620'],
                ['icon' => 'envelope', 'label' => 'trade@sheffieldafrica.com'],
            ],
            'sla' => 'Approval in 2 business days',
        ],
        [
            'icon' => 'document-text',
            'title' => 'Project consultation',
            'desc' => 'Full-kitchen design, ventilation, electrical load and installation planning.',
            'lines' => [
                ['icon' => 'phone', 'label' => '+254 711 234 590'],
                ['icon' => 'envelope', 'label' => 'projects@sheffieldafrica.com'],
            ],
            'sla' => 'Book a site visit',
        ],
    ];

    $stats = [
        ['icon' => 'chat-bubble-left-right', 'k' => 'Avg. first reply', 'v' => 'Under 2 hours'],
        ['icon' => 'map-pin', 'k' => 'Showrooms', 'v' => '4 across East Africa'],
        ['icon' => 'shield-check', 'k' => 'Service SLA', 'v' => '48-hour response'],
    ];

    $steps = [
        ['t' => 'We read & route it', 'd' => 'Your enquiry reaches the right specialist — sales, service or projects.'],
        ['t' => 'A specialist replies', 'd' => 'Usually within 2 working hours, by your preferred channel.'],
        ['t' => 'We scope it together', 'd' => 'Sizing, quotes, a showroom visit or a site survey as needed.'],
    ];
@endphp

<div class="page-fade">

    {{-- ───────── Masthead ───────── --}}
    <section class="border-b border-line bg-surface-sunken">
        <div class="shell pt-5 pb-12 lg:pt-7 lg:pb-16">
            <div class="max-w-3xl">
                <flux:breadcrumbs class="mb-4">
                    <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Contact</flux:breadcrumbs.item>
                </flux:breadcrumbs>

                <span class="text-[11.5px] font-bold uppercase tracking-[0.12em] text-brand-500">We're here to
                    help</span>
                <h1 class="mt-3 font-serif text-4xl font-normal leading-[1.04] tracking-tight text-ink lg:text-5xl">
                    Talk to a
                    <span class="italic text-brand-500">specialist</span>.
                </h1>
                <p class="mt-4 text-[16px] leading-relaxed text-ink-2">
                    From commercial kitchens and cold rooms to laundry and healthcare — sizing, power load,
                    ventilation or installation, get it right before you commit. Reach our team by phone, WhatsApp
                    or the form below, or walk into any of our four showrooms.
                </p>

                <div class="mt-6 flex flex-wrap gap-7">
                    @foreach ($stats as $stat)
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-9.5 shrink-0 items-center justify-center rounded-[10px] border border-line bg-surface text-brand-blue-600">
                                <flux:icon :icon="$stat['icon']" variant="outline" class="size-4.5" />
                            </span>
                            <div>
                                <div class="text-[11.5px] text-ink-3">{{ $stat['k'] }}</div>
                                <div class="text-[14px] font-semibold text-ink">{{ $stat['v'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ───────── Channel cards (temporarily hidden) ───────── --}}
    {{--
    <section class="shell pt-14 lg:pt-18">
        <div class="grid grid-cols-1 gap-4.5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($channels as $channel)
                <flux:card class="flex flex-col rounded-lg p-5.5">
                    <span class="flex size-11 items-center justify-center rounded-[11px] bg-surface-sunken text-brand-blue-600">
                        <flux:icon :icon="$channel['icon']" variant="outline" class="size-5.5" />
                    </span>
                    <div class="mt-4 font-serif text-[19px] text-ink">{{ $channel['title'] }}</div>
                    <p class="mt-1.5 mb-4 flex-1 text-[13px] leading-relaxed text-ink-3">{{ $channel['desc'] }}</p>
                    <div class="flex flex-col gap-1.5">
                        @foreach ($channel['lines'] as $line)
                            <span class="inline-flex items-center gap-2 text-[13px] text-ink-2">
                                <flux:icon :icon="$line['icon']" variant="micro" class="size-3.5 shrink-0 text-ink-4" /> {{ $line['label'] }}
                            </span>
                        @endforeach
                    </div>
                    <div class="mt-3.5 inline-flex items-center gap-1.5 border-t border-line pt-3 text-[11.5px] font-semibold uppercase tracking-[0.04em] text-brand-blue-600">
                        <flux:icon.clock variant="micro" class="size-3.5" /> {{ $channel['sla'] }}
                    </div>
                </flux:card>
            @endforeach
        </div>
    </section>
    --}}

    {{-- ───────── Form + sidebar ───────── --}}
    <section class="shell pt-14 lg:pt-18">
        <div class="grid grid-cols-1 items-start gap-10 lg:grid-cols-[1.5fr_0.85fr]">

            {{-- Form card --}}
            <flux:card class="rounded-lg p-8">
                @if ($sent)
                    <div class="py-5 text-center">
                        <div
                            class="mx-auto mb-4.5 flex size-16 items-center justify-center rounded-full bg-green-100 text-green-700">
                            <flux:icon.check variant="outline" class="size-7.5" />
                        </div>
                        <h2 class="font-serif text-[26px] text-ink">Message received</h2>
                        <p class="mx-auto mt-3 max-w-md text-[15px] leading-relaxed text-ink-2">
                            Thanks, {{ \Illuminate\Support\Str::of($name)->trim()->explode(' ')->first() ?: 'there' }}.
                            A Sheffield specialist will be in touch within
                            <strong class="text-ink">2 working hours</strong>. We've sent a copy to {{ $email }}.
                        </p>
                        <div
                            class="mt-5 inline-flex items-center gap-2 rounded-full bg-surface-sunken px-3.5 py-2 text-[13px] text-ink-2">
                            <span class="text-ink-3">Reference</span>
                            <strong class="font-mono tracking-wide">{{ $reference }}</strong>
                        </div>
                        <div class="mt-6 flex justify-center gap-2.5">
                            <flux:button variant="primary" icon-trailing="arrow-right" :href="route('catalog')"
                                wire:navigate>Browse the catalog</flux:button>
                            <flux:button wire:click="sendAnother">Send another</flux:button>
                        </div>
                    </div>
                @else
                    <form wire:submit="submit">
                        <h2 class="font-serif text-[28px] text-ink">Send us a message</h2>
                        <p class="mt-1.5 mb-6 text-[14px] text-ink-3">
                            Tell us what you're working on. The more detail, the faster we can help.
                        </p>

                        {{-- Inquiry type chips --}}
                        <div class="mb-5.5">
                            <span class="mb-2.5 block text-[13px] font-semibold text-ink-2">What's this about?</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($inquiryTypes as $type)
                                    <button type="button" wire:click="$set('inquiry', '{{ $type }}')"
                                        @class([
                                            'h-9 rounded-full border px-3.5 text-[13px] font-medium transition',
                                            'border-brand-500 bg-brand-500 text-white' => $inquiry === $type,
                                            'border-line-strong bg-surface text-ink-2 hover:border-ink-4' =>
                                                $inquiry !== $type,
                                        ])>{{ $type }}</button>
                                @endforeach
                            </div>
                            <flux:error name="inquiry" />
                        </div>

                        <div class="grid grid-cols-1 gap-4.5 sm:grid-cols-2">
                            <flux:field>
                                <flux:label>Full name <span class="ms-1 text-brand-500">*</span></flux:label>
                                <flux:input wire:model="name" placeholder="Jane Mwangi" required />
                                <flux:error name="name" />
                            </flux:field>
                            <flux:input wire:model="business" label="Business name" placeholder="e.g. Artcaffé Group" />
                            <flux:field>
                                <flux:label>Email <span class="ms-1 text-brand-500">*</span></flux:label>
                                <flux:input wire:model="email" type="email" placeholder="jane@business.co.ke"
                                    required />
                                <flux:error name="email" />
                            </flux:field>
                            <flux:input wire:model="phone" label="Phone" placeholder="+254 7…" />
                        </div>

                        <div class="mt-4.5">
                            <flux:select wire:model="location" label="Nearest showroom">
                                @foreach ($this->showrooms as $showroom)
                                    <flux:select.option :value="$showroom->id">
                                        {{ $showroom->city }},
                                        {{ $showroom->country }}{{ $showroom->is_hq ? ' (HQ)' : '' }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div class="mt-4.5">
                            <flux:field>
                                <flux:label>How can we help? <span class="ms-1 text-brand-500">*</span></flux:label>
                                <flux:textarea wire:model="message" rows="5" required
                                    placeholder="Tell us about the equipment or project — quantities, timelines and any constraints." />
                                <flux:error name="message" />
                            </flux:field>
                        </div>

                        <label class="mt-4.5 flex cursor-pointer items-start gap-3">
                            <flux:checkbox wire:model="consent" class="mt-0.5" />
                            <span @class([
                                'text-[13px] leading-relaxed',
                                'text-brand-500' => $errors->has('consent'),
                                'text-ink-3' => !$errors->has('consent'),
                            ])>
                                I agree to Sheffield contacting me about this enquiry and accept the
                                <a href="{{ route('page.show', 'privacy-policy') }}" wire:navigate
                                    class="text-brand-blue-600 underline">privacy policy</a>.
                            </span>
                        </label>
                        <flux:error name="consent" />

                        <div class="mt-6 flex flex-wrap items-center gap-3.5">
                            <flux:button type="submit" variant="primary" icon-trailing="arrow-right" class="px-6!">
                                <span wire:loading.remove wire:target="submit">Send message</span>
                                <span wire:loading wire:target="submit">Sending…</span>
                            </flux:button>
                            <span class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-4">
                                <flux:icon.shield-check variant="micro" class="size-3.5" /> We reply within 2 working
                                hours
                            </span>
                        </div>
                    </form>
                @endif
            </flux:card>

            {{-- Sidebar --}}
            <div class="flex flex-col gap-4.5">
                <flux:card class="rounded-lg p-6">
                    <div class="text-[11.5px] font-bold uppercase tracking-[0.1em] text-ink-3">What happens next</div>
                    <div class="mt-4 flex flex-col gap-4">
                        @foreach ($steps as $i => $step)
                            <div class="flex gap-3.5">
                                <span
                                    class="flex size-6.5 shrink-0 items-center justify-center rounded-full bg-surface-sunken font-serif text-[13px] font-bold text-brand-blue-600">{{ $i + 1 }}</span>
                                <div>
                                    <div class="text-[14px] font-semibold text-ink">{{ $step['t'] }}</div>
                                    <div class="mt-0.5 text-[12.5px] leading-relaxed text-ink-3">{{ $step['d'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </flux:card>

                <div class="rounded-lg bg-brand-blue-700 p-6 text-[#e6ddc8]">
                    <div class="text-[11.5px] font-bold uppercase tracking-[0.1em] text-[#d8c79d]">Head office hours
                    </div>
                    <div class="mt-3.5 flex flex-col gap-2.5 text-[13.5px]">
                        @foreach ([['Mon – Fri', '8:00 – 17:00'], ['Saturday', '8:00 – 13:00'], ['Sunday', 'Closed']] as [$day, $time])
                            <div class="flex justify-between text-[#c9bea4]">
                                <span>{{ $day }}</span><span
                                    class="font-medium text-[#f3eadd]">{{ $time }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="my-4 h-px bg-[#e6ddc8]/15"></div>
                    <div class="text-[12.5px] leading-relaxed text-[#c9bea4]">
                        All times East Africa (EAT, GMT+3). Emergency service line operates 24/7 for active contract
                        holders.
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ───────── Showrooms ───────── --}}
    @include('partials.storefront.showroom-map-scripts')
    @php
        $mapLocations = $this->showrooms
            ->map(
                fn($s) => [
                    'id' => $s->id,
                    'lat' => $s->latitude,
                    'lng' => $s->longitude,
                    'city' => $s->city,
                    'isHq' => $s->is_hq,
                ],
            )
            ->values();
        $initialLocation = $this->showrooms->firstWhere('is_hq', true)?->id ?? $this->showrooms->first()?->id;
    @endphp
    <section class="shell pt-16 pb-20 lg:pt-22" x-data="showroomMap({ initial: {{ $initialLocation ?? 'null' }}, locations: {{ \Illuminate\Support\Js::from($mapLocations) }} })">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-5">
            <div>
                <span class="text-[11.5px] font-bold uppercase tracking-[0.12em] text-brand-500">Walk in &amp; see it
                    working</span>
                <h2 class="mt-2.5 font-serif text-3xl font-normal text-ink lg:text-4xl">Visit a Sheffield showroom</h2>
            </div>
        </div>

        <div
            class="grid grid-cols-1 overflow-hidden rounded-lg border border-line bg-surface lg:min-h-110 lg:grid-cols-[1fr_1.15fr]">

            {{-- Map: real interactive map (Leaflet / Google per admin map_provider),
                 with the stylised SVG region map as a graceful fallback until it loads. --}}
            <div class="relative min-h-72 overflow-hidden bg-brand-blue-700 lg:min-h-110">
                <div x-ref="map" class="shf-map"></div>

                <div class="absolute inset-0 z-10 p-7" x-show="! ready" x-transition.opacity.duration.400ms>
                    <svg viewBox="0 0 360 420" class="block size-full">
                        <defs>
                            <pattern id="contact-grid" width="20" height="20" patternUnits="userSpaceOnUse">
                                <path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.05)"
                                    stroke-width="0.5" />
                            </pattern>
                        </defs>
                        <rect width="360" height="420" fill="url(#contact-grid)" />
                        <g opacity="0.18" fill="#fff">
                            <path d="M180 90 L 280 100 L 295 160 L 285 220 L 240 245 L 195 260 L 175 240 L 165 180 Z" />
                            <path d="M120 130 L 175 130 L 180 200 L 130 215 L 105 195 L 100 165 Z" />
                            <path d="M95 220 L 135 215 L 140 250 L 110 260 L 90 248 Z" />
                            <path
                                d="M135 250 L 200 248 L 260 270 L 285 320 L 240 365 L 180 360 L 130 320 L 120 290 Z" />
                        </g>
                        @foreach ($this->showrooms as $loc)
                            @php
                                $x = (($loc->longitude - 28) / (42 - 28)) * 360;
                                $y = ((6 - $loc->latitude) / 12) * 420;
                                $leftSide = in_array($loc->city, ['Kigali', 'Kampala'], true);
                                $anchor = $leftSide ? 'end' : 'start';
                                $tx = $x + ($leftSide ? -10 : 10);
                            @endphp
                            <g class="cursor-pointer" @click="active = {{ $loc->id }}">
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="14"
                                    fill="hsl(354 68% 45% / 0.25)" x-show="active === {{ $loc->id }}" />
                                <circle cx="{{ $x }}" cy="{{ $y }}"
                                    :r="active === {{ $loc->id }} ? 6 : 4" fill="hsl(354 68% 45%)"
                                    stroke="#fff" stroke-width="1.5" />
                                <text x="{{ $tx }}" y="{{ $y + 4 }}"
                                    text-anchor="{{ $anchor }}" font-size="11" fill="rgba(255,255,255,0.85)"
                                    :font-weight="active === {{ $loc->id }} ? 700 : 500">{{ $loc->city }}{{ $loc->is_hq ? ' ★' : '' }}</text>
                            </g>
                        @endforeach
                    </svg>
                </div>
            </div>

            {{-- Detail --}}
            <div class="flex flex-col p-8">
                <div class="flex flex-wrap gap-1.5 border-b border-line pb-3.5">
                    @foreach ($this->showrooms as $loc)
                        <button type="button" @click="active = {{ $loc->id }}"
                            class="inline-flex h-8.5 items-center gap-1.5 rounded-full border px-3.5 text-[13px] transition"
                            :class="active === {{ $loc->id }} ? 'border-ink bg-ink text-white font-semibold' :
                                'border-line bg-surface text-ink-2 font-medium hover:border-line-strong'">
                            {{ $loc->city }}
                            @if ($loc->is_hq)
                                <span class="rounded-sm px-1.5 py-px text-[9px] tracking-[0.06em]"
                                    :class="active === {{ $loc->id }} ? 'bg-brand-500 text-white' :
                                        'bg-surface-sunken text-ink-3'">HQ</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                @foreach ($this->showrooms as $loc)
                    <div class="mt-5 flex-1" x-show="active === {{ $loc->id }}" x-cloak>
                        <div class="font-serif text-2xl text-ink">{{ $loc->address }}</div>
                        <div class="mt-1.5 text-[13.5px] text-ink-3">
                            {{ $loc->city }}, {{ $loc->country }}@if ($loc->pobox)
                                · {{ $loc->pobox }}
                            @endif
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-x-5 gap-y-3.5 sm:grid-cols-2">
                            @php
                                $rows = [
                                    ['icon' => 'phone', 'label' => 'Phone', 'value' => $loc->phones[0] ?? null],
                                    [
                                        'icon' => 'chat-bubble-left-right',
                                        'label' => 'WhatsApp',
                                        'value' => $loc->whatsapp,
                                    ],
                                    ['icon' => 'envelope', 'label' => 'Email', 'value' => $loc->email],
                                    ['icon' => 'clock', 'label' => 'Hours', 'value' => $loc->hours],
                                ];
                            @endphp
                            @foreach ($rows as $row)
                                @if ($row['value'])
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 text-brand-blue-600">
                                            <flux:icon :icon="$row['icon']" variant="outline" class="size-4" />
                                        </span>
                                        <div>
                                            <div class="text-[11.5px] text-ink-4">{{ $row['label'] }}</div>
                                            <div class="text-[13.5px] font-medium text-ink">{{ $row['value'] }}</div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        @if ($loc->services)
                            <div class="mt-5 flex flex-wrap gap-1.5">
                                @foreach ($loc->services as $service)
                                    <span
                                        class="rounded-full bg-surface-sunken px-2.5 py-1 text-[11.5px] text-ink-2">{{ $service }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

                <div class="mt-5.5 flex gap-2.5">
                    @foreach ($this->showrooms as $loc)
                        <template x-if="active === {{ $loc->id }}">
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($loc->address . ', ' . $loc->city . ', ' . $loc->country) }}"
                                target="_blank" rel="noopener">
                                <flux:button variant="primary" icon-trailing="arrow-right">Get directions
                                </flux:button>
                            </a>
                        </template>
                    @endforeach
                    <flux:button :href="route('quote.request')" wire:navigate>Book a showroom visit</flux:button>
                </div>
            </div>
        </div>
    </section>
</div>
