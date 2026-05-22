<?php

use App\Models\County;
use App\Models\ShippingZone;
use App\Models\SubCounty;
use App\Models\Town;
use Flux\Flux;
use Livewire\Attributes\{Computed, Title, Url};
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Towns')] class extends Component {
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterCounty = '';

    #[Url(history: true)]
    public string $filterMode = '';

    public ?int $editingTownId = null;
    public string $editingZoneId = '';

    public int $perPage = 10;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterCounty(): void { $this->resetPage(); }
    public function updatedFilterMode(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

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
    public function towns()
    {
        return Town::with(['subCounty', 'county', 'shippingZone'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterCounty, fn ($q) => $q->where('county_id', $this->filterCounty))
            ->when($this->filterMode === 'overridden', fn ($q) => $q->whereNotNull('shipping_zone_id'))
            ->when($this->filterMode === 'inherited', fn ($q) => $q->whereNull('shipping_zone_id'))
            ->orderBy('county_id')
            ->orderBy('sub_county_id')
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function openEdit(int $townId): void
    {
        $town = Town::findOrFail($townId);
        $this->editingTownId = $town->id;
        $this->editingZoneId = (string) ($town->shipping_zone_id ?? '');
        Flux::modal('town-edit')->show();
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editingZoneId' => 'nullable|exists:shipping_zones,id',
        ]);

        Town::findOrFail($this->editingTownId)
            ->update(['shipping_zone_id' => $this->editingZoneId ?: null]);

        Flux::modal('town-edit')->close();
        unset($this->towns, $this->totals);

        $this->dispatch('notify', title: 'Town Updated', variant: 'success', message: 'Zone override saved.');
    }

    public function clearOverride(int $townId): void
    {
        Town::findOrFail($townId)->update(['shipping_zone_id' => null]);
        unset($this->towns, $this->totals);

        $this->dispatch('notify', title: 'Override Cleared', variant: 'success', message: 'Town now inherits from its sub-county / county.');
    }
}; ?>

<x-admin.logistics.layout
    heading="Towns (Wards)"
    subheading="ADM3-level overrides — the most-specific tier in the precedence chain. Use sparingly: only override wards where the sub-county default is wrong.">

    {{-- Table --}}
    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        <div class="flex flex-wrap items-center gap-3 px-5 py-3 border-b dark:border-zinc-600">
            <flux:input wire:model.live.debounce.300ms="search"
                placeholder="Search ward..."
                icon="magnifying-glass" clearable class="max-w-sm" />

            <div class="flex items-center gap-2 ms-auto flex-wrap">
                <flux:select wire:model.live="filterCounty" placeholder="All Counties" clearable class="w-44">
                    @foreach ($this->counties as $county)
                        <flux:select.option value="{{ $county->id }}">{{ $county->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterMode" placeholder="All Wards" clearable class="w-44">
                    <flux:select.option value="overridden">With override</flux:select.option>
                    <flux:select.option value="inherited">Inheriting</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-20">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>
            </div>
        </div>
        <flux:table :paginate="$this->towns">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Ward</flux:table.column>
                <flux:table.column>Sub-county</flux:table.column>
                <flux:table.column>County</flux:table.column>
                <flux:table.column>Override</flux:table.column>
                <flux:table.column>Effective</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->towns as $town)
                    <flux:table.row :key="$town->id">
                        <flux:table.cell class="ps-4!">
                            <a href="{{ route('admin.logistics.configuration.locations.towns.show', $town) }}"
                                wire:navigate
                                class="font-medium text-zinc-800 dark:text-zinc-200 hover:underline">
                                {{ $town->name }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">{{ $town->subCounty?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">{{ $town->county?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($town->shipping_zone_id)
                                <flux:badge color="violet" size="sm">{{ $town->shippingZone->name }}</flux:badge>
                            @else
                                <span class="text-xs text-zinc-400 italic">— inherits</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-sm">
                            {{ $town->effectiveShippingZone()?->name ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button size="xs" variant="ghost" wire:click="openEdit({{ $town->id }})">Set</flux:button>
                            @if ($town->shipping_zone_id)
                                <flux:button size="xs" variant="ghost" wire:click="clearOverride({{ $town->id }})"
                                    wire:confirm="Clear the override for {{ $town->name }}?">Clear</flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.map class="w-10 h-10 opacity-40" />
                                <div>
                                    <flux:heading size="sm">No wards match these filters</flux:heading>
                                    <flux:subheading class="mt-0.5">
                                        @if ($this->filterMode === 'overridden')
                                            Nothing overridden yet — most wards inherit from their sub-county. Switch the filter to see all.
                                        @else
                                            Try clearing the filters.
                                        @endif
                                    </flux:subheading>
                                </div>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Edit modal --}}
    <flux:modal name="town-edit" class="max-w-sm">
        <flux:heading size="lg">Set zone override</flux:heading>
        <flux:text class="mt-1 mb-4 text-zinc-500">
            Override the shipping zone for this ward. Leave blank to inherit from the parent sub-county / county.
        </flux:text>

        <flux:field label="Zone">
            <flux:select wire:model="editingZoneId" placeholder="Inherit from parent">
                @foreach ($this->zones as $zone)
                    <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <div class="flex justify-end gap-3 mt-6">
            <flux:button variant="ghost" x-on:click="$flux.modal('town-edit').close()">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveEdit">Save</flux:button>
        </div>
    </flux:modal>

</x-admin.logistics.layout>
