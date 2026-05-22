<?php

use App\Models\Address;
use App\Models\ShippingZone;
use App\Models\Town;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Town $town;
    public string $editingZoneId = '';

    public function mount(Town $town): void
    {
        $this->town = $town->load(['subCounty', 'county', 'shippingZone', 'boundary']);
        $this->editingZoneId = (string) ($town->shipping_zone_id ?? '');
    }

    public function rendering($view): void
    {
        $view->title($this->town->name.' — Ward');
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function addresses()
    {
        return Address::with('user')
            ->where('town_id', $this->town->id)
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function addressCount(): int
    {
        return Address::where('town_id', $this->town->id)->count();
    }

    public function saveOverride(): void
    {
        $this->validate(['editingZoneId' => 'nullable|exists:shipping_zones,id']);

        $this->town->update(['shipping_zone_id' => $this->editingZoneId ?: null]);
        $this->town->refresh()->load(['shippingZone']);

        Flux::modal('zone-modal')->close();
        $this->dispatch('notify', title: 'Saved', variant: 'success', message: 'Override updated.');
    }
}; ?>

<div>
    <div class="mb-4">
        <flux:button :href="route('admin.logistics.configuration.locations.towns.index')" wire:navigate
            variant="ghost" size="sm" icon="arrow-left">All Wards</flux:button>
    </div>

    {{-- Header --}}
    <flux:card class="p-6 mb-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <flux:heading size="xl">{{ $town->name }}</flux:heading>
                <flux:subheading class="mt-1">
                    {{ $town->subCounty?->name ?? '—' }} sub-county · {{ $town->county?->name ?? '—' }} county
                </flux:subheading>

                @php $effective = $town->effectiveShippingZone(); @endphp
                @if ($effective)
                    <div class="flex items-center gap-2 mt-3">
                        <flux:badge :color="$town->shipping_zone_id ? 'violet' : 'zinc'" size="sm">
                            Effective: {{ $effective->name }}
                        </flux:badge>
                        @if ($town->shipping_zone_id)
                            <flux:badge color="violet" variant="flat" size="sm">Override active</flux:badge>
                        @else
                            <span class="text-xs text-zinc-400 italic">inherits</span>
                        @endif
                    </div>
                @endif
            </div>

            <flux:button variant="outline" size="sm" icon="pencil-square"
                x-on:click="$flux.modal('zone-modal').show()">
                {{ $town->shipping_zone_id ? 'Change Override' : 'Set Override' }}
            </flux:button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Customer addresses</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->addressCount }}</p>
            </div>
            @if ($town->lat_center && $town->lng_center)
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wide">Centroid</p>
                    <p class="text-sm font-mono mt-1">{{ number_format($town->lat_center, 4) }}, {{ number_format($town->lng_center, 4) }}</p>
                </div>
            @endif
            @if ($town->shape_id)
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wide">geoBoundaries ID</p>
                    <p class="text-xs font-mono mt-1 text-zinc-500">{{ $town->shape_id }}</p>
                </div>
            @endif
        </div>
    </flux:card>

    {{-- Addresses --}}
    <flux:card class="p-0">
        <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm">Recent customer addresses</flux:heading>
            <flux:subheading class="text-xs">Latest 10 addresses resolved to this ward by point-in-polygon at save time.</flux:subheading>
        </div>

        @if ($this->addresses->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-zinc-400">No customer addresses point to this ward yet.</div>
        @else
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($this->addresses as $addr)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div>
                            <p class="font-medium text-zinc-800 dark:text-zinc-200">{{ $addr->full_name }}</p>
                            <p class="text-xs text-zinc-500 mt-0.5">{{ $addr->address }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-zinc-500">{{ $addr->user?->email ?? 'guest' }}</p>
                            <p class="text-[11px] text-zinc-400">{{ $addr->created_at?->diffForHumans() }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>

    {{-- Edit modal --}}
    <flux:modal name="zone-modal" class="max-w-sm">
        <flux:heading size="lg">Override zone</flux:heading>
        <flux:text class="mt-1 mb-4 text-zinc-500">Leave blank to inherit from sub-county / county.</flux:text>

        <flux:field label="Zone">
            <flux:select wire:model="editingZoneId" placeholder="Inherit from parent">
                @foreach ($this->zones as $zone)
                    <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <div class="flex justify-end gap-3 mt-6">
            <flux:button variant="ghost" x-on:click="$flux.modal('zone-modal').close()">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveOverride">Save</flux:button>
        </div>
    </flux:modal>
</div>
