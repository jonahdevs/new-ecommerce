<?php

use App\Models\Showroom;
use App\Notifications\ContactEnquiryReceived;
use App\Rules\Recaptcha;
use App\Settings\BusinessSettings;
use App\Support\CountryCodes;
use Artesaos\SEOTools\Facades\SEOMeta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Contact & Showrooms')] class extends Component {
    /** @var list<string> */
    public array $inquiryTypes = ['Sales enquiry', 'Service & spares', 'Installation', 'Project consultation'];

    public string $inquiry = 'Sales enquiry';

    public string $name = '';

    public string $business = '';

    public string $email = '';

    public string $phone_country_code = '+254';

    public string $phone_local = '';

    public string $message = '';

    public bool $consent = false;

    public bool $sent = false;

    public string $reference = '';

    public string $recaptchaToken = '';

    public function mount(): void
    {
        SEOMeta::setDescription('Talk to a Sheffield equipment specialist — commercial kitchens, cold rooms, laundry and healthcare. Sales, service, trade accounts and project consultation by phone, WhatsApp, the form, or any of our four showrooms.');

        $requested = (string) request('inquiry');
        if (in_array($requested, $this->inquiryTypes, true)) {
            $this->inquiry = $requested;
        }
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
                'phone_country_code' => ['required', 'string', 'max:10'],
                'phone_local' => ['nullable', 'string', 'max:20'],
                'message' => ['required', 'string', 'max:5000'],
                'consent' => ['accepted'],
                'recaptchaToken' => [new Recaptcha('contact')],
            ],
            [
                'consent.accepted' => 'Please agree to be contacted about your enquiry.',
            ],
        );

        $phone = filled($this->phone_local)
            ? $this->phone_country_code . ltrim($this->phone_local, '0')
            : null;

        $this->reference = 'SHF-' . random_int(100000, 999999);

        $recipient = app(BusinessSettings::class)->contact_email ?: config('mail.from.address');

        Notification::route('mail', $recipient)->notify(
            new ContactEnquiryReceived([
                'reference' => $this->reference,
                'inquiry' => $validated['inquiry'],
                'name' => $validated['name'],
                'business' => $validated['business'] ?: null,
                'email' => $validated['email'],
                'phone' => $phone,
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

    $features = [
        ['icon' => 'truck', 'title' => 'Free Delivery', 'sub' => 'On qualifying orders'],
        ['icon' => 'chat-bubble-left-right', 'title' => 'Expert Support', 'sub' => 'Talk to a specialist'],
        ['icon' => 'wrench-screwdriver', 'title' => 'Installation', 'sub' => 'Professional setup'],
        ['icon' => 'shield-check', 'title' => 'Warranty & Service', 'sub' => 'Genuine parts'],
        ['icon' => 'lock-closed', 'title' => 'Secure Payment', 'sub' => '100% protected'],
    ];

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

<div class="page-fade">

    {{-- Leaflet/Google map styles + Alpine showroomMap component (used beside the
         form and in the showrooms section below). --}}
    @include('partials.storefront.showroom-map-scripts')

    {{-- Breadcrumb --}}
    <div class="bg-surface-sunken">
        <div class="shell py-3">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Contact</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

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
    <section class="shell pt-3 pb-12 lg:pb-14" x-data="showroomMap({ initial: {{ $initialLocation ?? 'null' }}, locations: {{ \Illuminate\Support\Js::from($mapLocations) }} })">
        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Contact us</h1>
        <p class="mt-2 text-[14.5px] text-ink-3">Our team would love to hear from you!</p>

        <div class="mt-6 grid grid-cols-1 items-start gap-10 lg:grid-cols-[1.1fr_1fr]">

            {{-- Form --}}
            <div>
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
                    <x-recaptcha-livewire />
                    <form x-data @submit.prevent="__rcSubmit('contact', $wire)">
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
                            <flux:field>
                                <flux:label>Phone</flux:label>
                                <flux:input.group>
                                    <x-country-code-combobox wire:model="phone_country_code" />
                                    <flux:input wire:model="phone_local" type="tel" placeholder="712 345 678"
                                        autocomplete="tel" />
                                </flux:input.group>
                                <flux:error name="phone_local" />
                            </flux:field>
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
                            <flux:button type="submit" variant="customer-primary" size="customer-lg"
                                icon-trailing="send" class="px-6!">
                                <span wire:loading.remove wire:target="submit">Send message</span>
                                <span wire:loading wire:target="submit">Sending…</span>
                            </flux:button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- Sidebar — showroom locations map, side by side with the form --}}
            <flux:card class="overflow-hidden rounded-md p-0">
                <div class="relative h-80 lg:h-full lg:min-h-110">
                    <div x-ref="map" class="shf-map"></div>
                </div>
            </flux:card>
        </div>
    </section>

    {{-- ───────── Showrooms directory ───────── --}}
    <section class="shell pb-12 lg:pb-14">
        <div class="grid grid-cols-1 gap-x-10 gap-y-10 lg:grid-cols-[0.85fr_2fr]">

            {{-- Intro --}}
            <div>
                <h2 class="font-serif text-3xl font-normal text-ink lg:text-4xl">Visit Our Showrooms</h2>
                <p class="mt-3 text-[15px] text-ink-3">Find us at these locations.</p>
            </div>

            {{-- Locations — two columns for the four showrooms --}}
            <div class="grid grid-cols-1 gap-x-10 gap-y-9 sm:grid-cols-2">
                @foreach ($this->showrooms as $loc)
                    <div>
                        <div class="flex items-center gap-2 text-[17px] font-bold text-ink">
                            {{ $loc->city }}
                            @if ($loc->is_hq)
                                <span
                                    class="rounded-sm bg-surface-sunken px-1.5 py-px text-[9px] font-bold tracking-[0.06em] text-brand-blue-600">HQ</span>
                            @endif
                        </div>
                        <div class="mt-1.5 text-[13.5px] leading-relaxed text-ink-3">
                            {{ $loc->address }}<br>
                            {{ $loc->city }}, {{ $loc->country }}@if ($loc->pobox)
                                · {{ $loc->pobox }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───────── Service highlights ───────── --}}
    <section class="shell pb-12 lg:pb-14">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($features as $feature)
                <div class="flex items-center gap-3.5 rounded-lg border border-line bg-surface px-5 py-5">
                    <flux:icon :icon="$feature['icon']" variant="outline" class="size-8 shrink-0 text-brand-blue-600" />
                    <div>
                        <div class="text-[15px] font-bold text-ink">{{ $feature['title'] }}</div>
                        <div class="mt-0.5 text-[12.5px] text-ink-3">{{ $feature['sub'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
