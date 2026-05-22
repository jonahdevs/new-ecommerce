<?php

use App\Models\Address;
use App\Models\County;
use App\Models\DeliveryOrder;
use App\Models\ShippingZone;
use App\Models\SubCounty;
use App\Models\Town;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public County $county;

    public function mount(County $county): void
    {
        $this->county = $county->load(['shippingZone', 'boundary']);
    }

    public function rendering($view): void
    {
        $view->title($this->county->name.' — County');
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'sub_counties' => SubCounty::where('county_id', $this->county->id)->count(),
            'sub_county_overrides' => SubCounty::where('county_id', $this->county->id)->whereNotNull('shipping_zone_id')->count(),
            'town_overrides' => Town::where('county_id', $this->county->id)->whereNotNull('shipping_zone_id')->count(),
            'addresses' => Address::where('county_id', $this->county->id)->count(),
            'orders_mtd' => DeliveryOrder::where('shipping_zone_id', $this->county->shipping_zone_id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    #[Computed]
    public function subCounties()
    {
        return SubCounty::with('shippingZone')
            ->where('county_id', $this->county->id)
            ->orderBy('name')
            ->get();
    }
}; ?>

<div>
    <div class="mb-4">
        <flux:button :href="route('admin.logistics.configuration.locations.counties.index')" wire:navigate
            variant="ghost" size="sm" icon="arrow-left">All Counties</flux:button>
    </div>

    <flux:card class="p-6 mb-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <flux:heading size="xl">{{ $county->name }}</flux:heading>
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    @if ($county->code)
                        <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded">{{ $county->code }}</code>
                    @endif
                    @if ($county->shippingZone)
                        <flux:badge color="blue" size="sm">{{ $county->shippingZone->name }}</flux:badge>
                    @endif
                </div>
            </div>

            @if ($county->shippingZone)
                <flux:button variant="outline" size="sm" icon="arrow-top-right-on-square"
                    :href="route('admin.logistics.configuration.zones.show', $county->shippingZone)" wire:navigate>
                    Open zone
                </flux:button>
            @endif
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Sub-counties</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['sub_counties'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Sub-county overrides</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['sub_county_overrides'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Town overrides</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['town_overrides'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Addresses</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['addresses'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Orders (MTD)</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['orders_mtd'] }}</p>
            </div>
        </div>
    </flux:card>

    <flux:card class="p-0">
        <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <flux:heading size="sm">Sub-counties</flux:heading>
            <flux:button size="xs" variant="ghost" icon="arrow-top-right-on-square"
                :href="route('admin.logistics.configuration.locations.sub-counties.index', ['filterCounty' => $county->id])"
                wire:navigate>Manage</flux:button>
        </div>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($this->subCounties as $sc)
                <div class="flex items-center justify-between px-5 py-3">
                    <a href="{{ route('admin.logistics.configuration.locations.sub-counties.show', $sc) }}"
                        wire:navigate class="font-medium text-zinc-800 dark:text-zinc-200 hover:underline">
                        {{ $sc->name }}
                    </a>
                    @if ($sc->shipping_zone_id)
                        <flux:badge color="blue" size="sm">{{ $sc->shippingZone->name }}</flux:badge>
                    @else
                        <span class="text-xs text-zinc-400 italic">inherits county zone</span>
                    @endif
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-zinc-400">No sub-counties seeded for this county.</div>
            @endforelse
        </div>
    </flux:card>
</div>
