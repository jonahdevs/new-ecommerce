<?php

use App\Models\Address;
use App\Models\ShippingZone;
use App\Services\Shipping\ShippingCalculator;
use App\Services\SubCountyResolver;
use App\Services\TownResolver;
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;

new #[Title('Address Tester')] class extends Component {
    public ?float $lat = null;
    public ?float $lng = null;
    public float $weight = 1.0;
    public float $orderAmount = 1500.0;
    public string $note = '';

    /**
     * Curated sample pins covering each tier. Lets staff click one and see the
     * resolver fire without having to find coordinates themselves.
     */
    public array $samples = [
        ['label' => 'Nairobi CBD',     'lat' => -1.2921, 'lng' => 36.8219, 'hint' => 'Expect: Nairobi Metro'],
        ['label' => 'Karen',           'lat' => -1.3185, 'lng' => 36.7017, 'hint' => 'Expect: Nairobi Metro'],
        ['label' => 'Thika Town',      'lat' => -1.0396, 'lng' => 37.0900, 'hint' => 'Expect: Nairobi Satellites'],
        ['label' => 'Ngong',           'lat' => -1.3667, 'lng' => 36.6333, 'hint' => 'Expect: Nairobi Satellites'],
        ['label' => 'Kitengela',       'lat' => -1.4762, 'lng' => 36.9608, 'hint' => 'Expect: Nairobi Satellites'],
        ['label' => 'Syokimau',        'lat' => -1.3645, 'lng' => 36.9358, 'hint' => 'Expect: Nairobi Satellites'],
        ['label' => 'Mombasa CBD',     'lat' => -4.0435, 'lng' => 39.6682, 'hint' => 'Expect: Out of Service Area'],
        ['label' => 'Eldoret',         'lat' =>  0.5200, 'lng' => 35.2697, 'hint' => 'Expect: Out of Service Area'],
    ];

    public function pickSample(int $index): void
    {
        $sample = $this->samples[$index] ?? null;

        if (! $sample) {
            return;
        }

        $this->lat = (float) $sample['lat'];
        $this->lng = (float) $sample['lng'];
        $this->note = $sample['label'];
    }

    public function clear(): void
    {
        $this->lat = null;
        $this->lng = null;
        $this->note = '';
    }

    /**
     * Run the precedence chain. Each tier is resolved independently so the UI
     * can show exactly which layer produced the zone.
     */
    #[Computed]
    public function resolution(): ?array
    {
        if ($this->lat === null || $this->lng === null) {
            return null;
        }

        $town = app(TownResolver::class)->resolve($this->lat, $this->lng);
        $subCounty = app(SubCountyResolver::class)->resolve($this->lat, $this->lng);
        $county = $subCounty?->county;

        if (! $county) {
            return [
                'town' => null,
                'sub_county' => null,
                'county' => null,
                'zone' => null,
                'source' => 'outside_kenya',
            ];
        }

        $zone = app(ShippingCalculator::class)->resolveZone($county->id, $subCounty?->id, $town?->id);

        // Determine which layer actually produced the zone — useful for debugging.
        $source = match (true) {
            $town?->shipping_zone_id !== null => 'town',
            $subCounty?->shipping_zone_id !== null => 'sub_county',
            $county?->shipping_zone_id !== null => 'county',
            default => 'fallback',
        };

        return [
            'town' => $town,
            'sub_county' => $subCounty,
            'county' => $county,
            'zone' => $zone,
            'source' => $source,
        ];
    }

    #[Computed]
    public function options(): \Illuminate\Support\Collection
    {
        $r = $this->resolution;

        if (! $r || ! $r['county']) {
            return collect();
        }

        return app(ShippingCalculator::class)->calculate(
            countyId: $r['county']->id,
            subCountyId: $r['sub_county']?->id,
            townId: $r['town']?->id,
            weightKg: $this->weight,
            orderAmount: $this->orderAmount,
        );
    }
}; ?>

<x-admin.logistics.layout
    heading="Address Tester"
    subheading="Drop coordinates in to see what zone resolves, how the precedence chain decided, and which shipping options the customer would be offered.">

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- ─── INPUTS (left, 2 cols) ─────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-4">

            <flux:card class="p-5 space-y-4">
                <flux:heading size="sm">Coordinates</flux:heading>

                <div class="grid grid-cols-2 gap-3">
                    <flux:input wire:model.live.debounce.300ms="lat" label="Latitude"
                        type="number" step="0.0000001" placeholder="-1.2921" />
                    <flux:input wire:model.live.debounce.300ms="lng" label="Longitude"
                        type="number" step="0.0000001" placeholder="36.8219" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <flux:input wire:model.live.debounce.300ms="weight" label="Cart weight (kg)"
                        type="number" step="0.1" min="0" />
                    <flux:input wire:model.live.debounce.300ms="orderAmount" label="Order total (KES)"
                        type="number" step="1" min="0" />
                </div>

                @if ($lat !== null && $lng !== null)
                    <flux:button size="sm" variant="ghost" wire:click="clear" icon="x-mark">Clear</flux:button>
                @endif
            </flux:card>

            <flux:card class="p-5">
                <flux:heading size="sm" class="mb-3">Sample pins</flux:heading>
                <flux:subheading class="text-xs mb-3">
                    One click drops a pin from a real location. Compare the actual outcome to the expected tier.
                </flux:subheading>
                <div class="grid grid-cols-1 gap-2">
                    @foreach ($samples as $i => $sample)
                        <button type="button" wire:click="pickSample({{ $i }})"
                            class="text-left px-3 py-2 rounded-md border border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 cursor-pointer transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-sm text-zinc-800 dark:text-zinc-200">{{ $sample['label'] }}</span>
                                <code class="text-[10px] text-zinc-400 tabular-nums">{{ number_format($sample['lat'], 4) }}, {{ number_format($sample['lng'], 4) }}</code>
                            </div>
                            <p class="text-[11px] text-zinc-400 mt-0.5">{{ $sample['hint'] }}</p>
                        </button>
                    @endforeach
                </div>
            </flux:card>
        </div>

        {{-- ─── OUTPUT (right, 3 cols) ────────────────────────────────── --}}
        <div class="lg:col-span-3 space-y-4">

            @php $r = $this->resolution; @endphp

            @if (! $r)
                <flux:card class="p-10 text-center">
                    <flux:icon.map class="size-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                    <flux:heading size="sm">Enter coordinates or pick a sample</flux:heading>
                    <flux:subheading class="mt-1">Resolution and shipping options will appear here.</flux:subheading>
                </flux:card>
            @elseif ($r['source'] === 'outside_kenya')
                <flux:card class="p-10 text-center">
                    <flux:icon.exclamation-triangle class="size-10 mx-auto mb-3 text-amber-500" />
                    <flux:heading size="sm">Outside Kenya</flux:heading>
                    <flux:subheading class="mt-1">No sub-county polygon contains this point. Check the coordinates.</flux:subheading>
                </flux:card>
            @else
                {{-- ─── Resolution chain ──────────────────────────────── --}}
                <flux:card class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="sm">Resolution chain</flux:heading>
                        @php
                            $source = $r['source'];
                            $sourceLabel = match ($source) {
                                'town' => 'Town override (ADM3)',
                                'sub_county' => 'Sub-county override (ADM2)',
                                'county' => 'County default (ADM1)',
                                default => 'No zone matched',
                            };
                            $sourceColor = match ($source) {
                                'town' => 'violet',
                                'sub_county' => 'blue',
                                'county' => 'zinc',
                                default => 'amber',
                            };
                        @endphp
                        <flux:badge :color="$sourceColor" variant="flat" size="sm">{{ $sourceLabel }} won</flux:badge>
                    </div>

                    <div class="space-y-3 text-sm">
                        {{-- Town --}}
                        <div class="flex items-start gap-3 p-3 rounded-md @if ($source === 'town') bg-violet-50 dark:bg-violet-950/30 border border-violet-200 dark:border-violet-800 @else bg-zinc-50 dark:bg-zinc-800/40 @endif">
                            <div class="size-6 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-700 dark:text-violet-300 text-xs font-bold shrink-0">1</div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">Town (ADM3)</span>
                                    <span class="text-xs text-zinc-500">
                                        {{ $r['town']?->name ?? 'no match' }}
                                    </span>
                                </div>
                                @if ($r['town'])
                                    <p class="text-xs text-zinc-500 mt-0.5">
                                        Override:
                                        @if ($r['town']->shipping_zone_id)
                                            <strong>{{ \App\Models\ShippingZone::find($r['town']->shipping_zone_id)?->name }}</strong>
                                        @else
                                            <em>inherits</em>
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Sub-county --}}
                        <div class="flex items-start gap-3 p-3 rounded-md @if ($source === 'sub_county') bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 @else bg-zinc-50 dark:bg-zinc-800/40 @endif">
                            <div class="size-6 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-700 dark:text-blue-300 text-xs font-bold shrink-0">2</div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">Sub-county (ADM2)</span>
                                    <span class="text-xs text-zinc-500">{{ $r['sub_county']?->name ?? 'no match' }}</span>
                                </div>
                                @if ($r['sub_county'])
                                    <p class="text-xs text-zinc-500 mt-0.5">
                                        Override:
                                        @if ($r['sub_county']->shipping_zone_id)
                                            <strong>{{ \App\Models\ShippingZone::find($r['sub_county']->shipping_zone_id)?->name }}</strong>
                                        @else
                                            <em>inherits</em>
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- County --}}
                        <div class="flex items-start gap-3 p-3 rounded-md @if ($source === 'county') bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 @else bg-zinc-50 dark:bg-zinc-800/40 @endif">
                            <div class="size-6 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-zinc-700 dark:text-zinc-300 text-xs font-bold shrink-0">3</div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">County (ADM1)</span>
                                    <span class="text-xs text-zinc-500">{{ $r['county']?->name ?? 'no match' }}</span>
                                </div>
                                @if ($r['county'])
                                    <p class="text-xs text-zinc-500 mt-0.5">
                                        Default zone:
                                        <strong>{{ \App\Models\ShippingZone::find($r['county']->shipping_zone_id)?->name ?? '—' }}</strong>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Resolved zone --}}
                    @if ($r['zone'])
                        @php
                            $zone = $r['zone'];
                            $status = $zone->status instanceof \App\Enums\ShippingZoneStatus ? $zone->status : \App\Enums\ShippingZoneStatus::from($zone->status);
                        @endphp
                        <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div>
                                    <p class="text-xs text-zinc-400 uppercase tracking-wide">Resolved zone</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <flux:heading size="sm">{{ $zone->name }}</flux:heading>
                                        @if ($zone->is_delivery_available)
                                            <flux:badge color="green" variant="flat" size="sm">Delivery Available</flux:badge>
                                        @else
                                            <flux:badge color="zinc" variant="flat" size="sm">Not Deliverable</flux:badge>
                                        @endif
                                    </div>
                                </div>
                                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square"
                                    :href="route('admin.logistics.configuration.zones.show', $zone)" wire:navigate>
                                    Open zone
                                </flux:button>
                            </div>
                        </div>
                    @endif
                </flux:card>

                {{-- ─── Shipping options ──────────────────────────────── --}}
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                        <flux:heading size="sm">Available shipping options</flux:heading>
                        <flux:subheading class="text-xs">What the customer would see at checkout for {{ $weight }} kg, KES {{ number_format($orderAmount, 0) }} order.</flux:subheading>
                    </div>

                    @if ($this->options->isEmpty())
                        <div class="px-5 py-10 text-center">
                            <flux:icon.no-symbol class="size-8 mx-auto mb-2 text-zinc-300 dark:text-zinc-600" />
                            <p class="text-sm text-zinc-500">No shipping options available.</p>
                            <p class="text-xs text-zinc-400 mt-1">
                                @if ($r['zone'] && ! $r['zone']->is_delivery_available)
                                    The zone is marked not deliverable.
                                @else
                                    No rate row matches this weight in this zone.
                                @endif
                            </p>
                        </div>
                    @else
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($this->options as $option)
                                <div class="flex items-center justify-between px-5 py-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $option->methodName }}</span>
                                            <flux:badge size="sm" variant="outline">{{ $option->methodType }}</flux:badge>
                                            @if ($option->isFree())
                                                <flux:badge color="green" variant="flat" size="sm">Free</flux:badge>
                                            @endif
                                        </div>
                                        <p class="text-xs text-zinc-500 mt-1">
                                            {{ $option->weightLabel }} · {{ $option->deliveryWindow() }}
                                        </p>
                                    </div>
                                    <div class="text-right shrink-0 ms-3">
                                        <span class="text-lg font-bold tabular-nums @if ($option->isFree()) text-emerald-600 dark:text-emerald-400 @else text-zinc-800 dark:text-zinc-200 @endif">
                                            {{ $option->formattedCost() }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:card>
            @endif

        </div>
    </div>

</x-admin.logistics.layout>
