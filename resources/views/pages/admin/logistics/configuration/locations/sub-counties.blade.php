<?php

use App\Models\County;
use App\Models\ShippingZone;
use App\Models\SubCounty;
use Livewire\Attributes\{Computed, Title, Url};
use Livewire\Component;
use Flux\Flux;

new #[Title('Sub-Counties')] class extends Component {

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterCounty = '';

    #[Url(history: true)]
    public string $filterZone = '';

    // For bulk zone assignment on a county
    public ?int $bulkCountyId = null;
    public string $bulkZoneId = '';

    // For individual sub-county zone override
    public ?int $editingSubCountyId = null;
    public string $editingZoneId = '';

    public function updatedSearch(): void {}
    public function updatedFilterCounty(): void {}
    public function updatedFilterZone(): void {}

    #[Computed]
    public function counties()
    {
        return County::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function subCounties()
    {
        return SubCounty::with('county', 'shippingZone')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterCounty, fn ($q) => $q->where('county_id', $this->filterCounty))
            ->when($this->filterZone === 'overridden', fn ($q) => $q->whereNotNull('shipping_zone_id'))
            ->when($this->filterZone === 'inherited', fn ($q) => $q->whereNull('shipping_zone_id'))
            ->orderBy('county_id')
            ->orderBy('name')
            ->paginate(30);
    }

    // ─── Bulk zone assignment ─────────────────────────────────────

    public function openBulkAssign(int $countyId): void
    {
        $this->bulkCountyId = $countyId;
        $this->bulkZoneId   = '';
        Flux::modal('bulk-assign-modal')->show();
    }

    public function saveBulkAssign(): void
    {
        $this->validate([
            'bulkZoneId' => 'required|exists:shipping_zones,id',
        ], [
            'bulkZoneId.required' => 'Please select a zone.',
        ]);

        $count = SubCounty::where('county_id', $this->bulkCountyId)
            ->update(['shipping_zone_id' => $this->bulkZoneId]);

        Flux::modal('bulk-assign-modal')->close();
        $this->dispatch('notify',
            title: 'Zone Assigned',
            variant: 'success',
            message: "{$count} sub-counties assigned to zone."
        );
    }

    public function clearBulkZone(int $countyId): void
    {
        SubCounty::where('county_id', $countyId)->update(['shipping_zone_id' => null]);
        $this->dispatch('notify',
            title: 'Overrides Cleared',
            variant: 'success',
            message: 'All sub-counties in this county now inherit from the county zone.'
        );
    }

    // ─── Individual override ──────────────────────────────────────

    public function openEdit(int $subCountyId): void
    {
        $sc = SubCounty::findOrFail($subCountyId);
        $this->editingSubCountyId = $sc->id;
        $this->editingZoneId      = $sc->shipping_zone_id ?? '';
        Flux::modal('edit-modal')->show();
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editingZoneId' => 'nullable|exists:shipping_zones,id',
        ]);

        SubCounty::findOrFail($this->editingSubCountyId)
            ->update(['shipping_zone_id' => $this->editingZoneId ?: null]);

        Flux::modal('edit-modal')->close();
        $this->dispatch('notify', title: 'Sub-County Updated', variant: 'success', message: 'Zone override saved.');
    }

    public function clearOverride(int $subCountyId): void
    {
        SubCounty::findOrFail($subCountyId)->update(['shipping_zone_id' => null]);
        $this->dispatch('notify', title: 'Override Cleared', variant: 'success', message: 'Sub-county now inherits from county zone.');
    }
}; ?>

<x-admin.logistics.layout
    heading="Sub-Counties"
    subheading="Assign shipping zones to sub-counties. Sub-counties without an override inherit the parent county's zone. Zone is resolved automatically from the customer's map pin.">

    {{-- Filters --}}
    <flux:card class="p-0 mb-4 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        <div class="flex flex-col md:flex-row gap-3 px-5 py-3">
            <flux:input wire:model.live.debounce.300ms="search"
                placeholder="Search sub-county..."
                icon="magnifying-glass" clearable class="max-w-xs" />

            <flux:select wire:model.live="filterCounty" placeholder="All Counties" clearable class="md:w-48">
                @foreach ($this->counties as $county)
                    <flux:select.option value="{{ $county->id }}">{{ $county->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterZone" placeholder="All Sub-Counties" clearable class="md:w-48">
                <flux:select.option value="overridden">Zone Overridden</flux:select.option>
                <flux:select.option value="inherited">Inheriting from County</flux:select.option>
            </flux:select>
        </div>
    </flux:card>

    {{-- County bulk-assign buttons --}}
    @if (! $this->search && ! $this->filterZone)
        <flux:card class="p-4 mb-4">
            <flux:heading size="sm" class="mb-3">Bulk Zone Assignment by County</flux:heading>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->counties as $county)
                    <flux:button size="sm" variant="outline" wire:click="openBulkAssign({{ $county->id }})">
                        {{ $county->name }}
                    </flux:button>
                @endforeach
            </div>
        </flux:card>
    @endif

    {{-- Sub-county table --}}
    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        <flux:table :paginate="$this->subCounties">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Sub-County</flux:table.column>
                <flux:table.column>County</flux:table.column>
                <flux:table.column>Zone Override</flux:table.column>
                <flux:table.column>Effective Zone</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->subCounties as $sc)
                    <flux:table.row :key="$sc->id" wire:key="sc-{{ $sc->id }}">

                        <flux:table.cell class="ps-4! font-medium">
                            {{ $sc->name }}
                        </flux:table.cell>

                        <flux:table.cell class="text-zinc-500 dark:text-zinc-400">
                            {{ $sc->county->name }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($sc->shipping_zone_id)
                                <flux:badge color="blue" size="sm">{{ $sc->shippingZone->name }}</flux:badge>
                            @else
                                <span class="text-xs text-zinc-400 dark:text-zinc-500 italic">— inherits</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm">{{ $sc->effectiveShippingZone()?->name ?? '—' }}</span>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <div class="flex justify-end gap-2">
                                <flux:button size="xs" variant="ghost" wire:click="openEdit({{ $sc->id }})">
                                    Set Zone
                                </flux:button>
                                @if ($sc->shipping_zone_id)
                                    <flux:button size="xs" variant="ghost" wire:click="clearOverride({{ $sc->id }})"
                                        wire:confirm="Clear the zone override for {{ $sc->name }}?">
                                        Clear
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>

                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-400 py-10">
                            No sub-counties found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Bulk assign modal --}}
    <flux:modal name="bulk-assign-modal" class="max-w-sm">
        <flux:heading size="lg">Assign Zone to County</flux:heading>
        <flux:text class="mt-1 mb-4 text-zinc-500">
            All sub-counties in this county will be assigned the selected zone.
            You can override individual sub-counties afterwards.
        </flux:text>

        <flux:field label="Shipping Zone">
            <flux:select wire:model="bulkZoneId" placeholder="Select a zone...">
                @foreach ($this->zones as $zone)
                    <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <div class="flex justify-end gap-3 mt-6">
            <flux:button variant="ghost" x-on:click="$flux.modal('bulk-assign-modal').close()">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveBulkAssign">Assign Zone</flux:button>
        </div>
    </flux:modal>

    {{-- Individual edit modal --}}
    <flux:modal name="edit-modal" class="max-w-sm">
        <flux:heading size="lg">Set Zone Override</flux:heading>
        <flux:text class="mt-1 mb-4 text-zinc-500">
            Override the shipping zone for this sub-county. Leave blank to inherit from the county.
        </flux:text>

        <flux:field label="Zone Override">
            <flux:select wire:model="editingZoneId" placeholder="Inherit from county...">
                @foreach ($this->zones as $zone)
                    <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <div class="flex justify-end gap-3 mt-6">
            <flux:button variant="ghost" x-on:click="$flux.modal('edit-modal').close()">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveEdit">Save</flux:button>
        </div>
    </flux:modal>

</x-admin.logistics.layout>
