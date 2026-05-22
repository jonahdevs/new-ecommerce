<?php

use App\Models\Address;
use App\Models\SubCounty;
use App\Models\Town;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public SubCounty $subCounty;

    public function mount(SubCounty $subCounty): void
    {
        $this->subCounty = $subCounty->load(['county.shippingZone', 'shippingZone']);
    }

    public function rendering($view): void
    {
        $view->title($this->subCounty->name.' — Sub-county');
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'towns' => Town::where('sub_county_id', $this->subCounty->id)->count(),
            'town_overrides' => Town::where('sub_county_id', $this->subCounty->id)->whereNotNull('shipping_zone_id')->count(),
            'addresses' => Address::where('sub_county_id', $this->subCounty->id)->count(),
        ];
    }

    #[Computed]
    public function towns()
    {
        return Town::with('shippingZone')
            ->where('sub_county_id', $this->subCounty->id)
            ->orderBy('name')
            ->get();
    }
}; ?>

<div>
    <div class="mb-4">
        <flux:button :href="route('admin.logistics.configuration.locations.sub-counties.index')" wire:navigate
            variant="ghost" size="sm" icon="arrow-left">All Sub-counties</flux:button>
    </div>

    <flux:card class="p-6 mb-6">
        @php $effective = $subCounty->effectiveShippingZone(); @endphp

        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <flux:heading size="xl">{{ $subCounty->name }}</flux:heading>
                <flux:subheading class="mt-1">{{ $subCounty->county?->name }} County</flux:subheading>

                <div class="flex items-center gap-2 mt-3 flex-wrap">
                    @if ($subCounty->shipping_zone_id)
                        <flux:badge color="blue" size="sm">Override: {{ $subCounty->shippingZone->name }}</flux:badge>
                    @else
                        <flux:badge color="zinc" size="sm">Inherits: {{ $effective?->name ?? 'no zone' }}</flux:badge>
                    @endif
                </div>
            </div>

            @if ($effective)
                <flux:button variant="outline" size="sm" icon="arrow-top-right-on-square"
                    :href="route('admin.logistics.configuration.zones.show', $effective)" wire:navigate>
                    Open zone
                </flux:button>
            @endif
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Wards (ADM3)</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['towns'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Ward overrides</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['town_overrides'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Addresses</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['addresses'] }}</p>
            </div>
        </div>
    </flux:card>

    <flux:card class="p-0">
        <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm">Wards in this sub-county</flux:heading>
            <flux:subheading class="text-xs">Hover any ward to see its effective zone. Override at ward level only when the sub-county default doesn't fit.</flux:subheading>
        </div>

        @if ($this->towns->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-zinc-400">No wards seeded for this sub-county.</div>
        @else
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($this->towns as $town)
                    <div class="flex items-center justify-between px-5 py-3">
                        <a href="{{ route('admin.logistics.configuration.locations.towns.show', $town) }}"
                            wire:navigate class="font-medium text-zinc-800 dark:text-zinc-200 hover:underline">
                            {{ $town->name }}
                        </a>
                        @if ($town->shipping_zone_id)
                            <flux:badge color="violet" size="sm">{{ $town->shippingZone->name }}</flux:badge>
                        @else
                            <span class="text-xs text-zinc-400 italic">inherits</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>
</div>
