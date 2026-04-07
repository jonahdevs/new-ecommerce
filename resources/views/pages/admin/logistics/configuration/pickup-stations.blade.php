<?php

use App\Enums\PickupStationStatus;
use App\Models\Area;
use App\Models\County;
use App\Models\LogisticsProvider;
use App\Models\PickupStation;
use App\Livewire\Forms\Admin\PickupStationForm;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Pickup Stations')] class extends Component {
    use WithPagination;

    public PickupStationForm $form;
    public ?int $deletingId = null;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterCounty = '';

    #[Url(history: true)]
    public string $filterProvider = '';

    #[Url(history: true)]
    public string $filterStatus = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedFilterCounty(): void
    {
        $this->resetPage();
    }
    public function updatedFilterProvider(): void
    {
        $this->resetPage();
    }
    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    // When county changes in the form, clear the area selection
    public function updatedFormCountyId(): void
    {
        $this->form->area_id = '';
    }

    #[Computed]
    public function stations()
    {
        return PickupStation::with(['county', 'area', 'logisticsProvider'])
            ->when(
                $this->search,
                fn($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%")
                    ->orWhere('address', 'like', "%{$this->search}%"),
            )
            ->when($this->filterCounty, fn($q) => $q->where('county_id', $this->filterCounty))
            ->when($this->filterProvider, fn($q) => $q->where('logistics_provider_id', $this->filterProvider))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function counties()
    {
        return County::orderBy('name')->get();
    }

    #[Computed]
    public function areasForForm()
    {
        if (!$this->form->county_id) {
            return collect();
        }
        return Area::where('county_id', $this->form->county_id)->orderBy('name')->get();
    }

    #[Computed]
    public function providers()
    {
        return LogisticsProvider::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function statuses(): array
    {
        return PickupStationStatus::cases();
    }

    public function openCreate(): void
    {
        $this->form->reset();
        Flux::modal('station-modal')->show();
    }

    public function save(): void
    {
        try {
            $isEditing = (bool) $this->form->station;
            $isEditing ? $this->form->update() : $this->form->store();

            $this->form->reset();
            Flux::modal('station-modal')->close();
            $this->dispatch('notify', title: $isEditing ? 'Station Updated' : 'Station Added', variant: 'success', message: $isEditing ? 'Station updated.' : 'Station added.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save pickup station.', [
                'exception' => $e->getMessage(),
                'station_id' => $this->form->station?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Save Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(PickupStation $station): void
    {
        $this->form->setStation($station);
        Flux::modal('station-modal')->show();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('delete-confirmation')->show();
    }

    public function delete(): void
    {
        if (!$this->deletingId) {
            return;
        }

        try {
            $station = PickupStation::findOrFail($this->deletingId);

            if ($station->deliveryOrders()->exists()) {
                $this->dispatch('notify', title: 'Cannot Delete', variant: 'warning', message: 'Cannot delete — this station has delivery orders attached. Deactivate it instead.');
                Flux::modal('delete-confirmation')->close();
                return;
            }

            $station->delete();
            $this->deletingId = null;
            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', title: 'Station Deleted', variant: 'danger', message: 'Station deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete pickup station.', [
                'exception' => $e->getMessage(),
                'station_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Could not delete this station. It may have dependent records.');
        }
    }
}; ?>

<x-admin.logistics.layout heading="Pickup Stations"
    subheading="Physical collection points for the PUS model. Customers collect parcels here within the holding period.">

    <x-slot:actions>
        <flux:button variant="primary" icon="plus-circle" wire:click="openCreate" class="cursor-pointer">
            Add Station
        </flux:button>
    </x-slot:actions>

    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        {{-- Filters --}}
        <div class="flex flex-col md:flex-row gap-4 px-5 py-3 border-b dark:border-zinc-600">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name, code or address..."
                icon="magnifying-glass" clearable class="max-w-md" />

            <div class="ms-auto flex items-center gap-5">
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" size="sm" icon="funnel" icon-variant="outline" icon-trailing="chevron-down">
                        Filters
                        @php $activeFilters = collect([$filterCounty, $filterProvider, $filterStatus])->filter()->count(); @endphp
                        @if ($activeFilters > 0)
                            <flux:badge size="sm" class="ms-1">{{ $activeFilters }}</flux:badge>
                        @endif
                    </flux:button>

                    <flux:menu class="min-w-64">
                        <div class="flex items-center justify-between px-3 py-2 border-b dark:border-zinc-700">
                            <flux:subheading>Filter Options</flux:subheading>
                            <flux:button variant="ghost" size="xs"
                                wire:click="$set('filterCounty', ''); $set('filterProvider', ''); $set('filterStatus', '')"
                                class="cursor-pointer">Reset</flux:button>
                        </div>
                        <flux:separator />
                        <div class="p-3 space-y-3">
                            <flux:field>
                                <flux:label>County</flux:label>
                                <flux:select wire:model.live="filterCounty" placeholder="All Counties" clearable>
                                    @foreach ($this->counties as $county)
                                        <flux:select.option value="{{ $county->id }}">{{ $county->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                            <flux:field>
                                <flux:label>Provider</flux:label>
                                <flux:select wire:model.live="filterProvider" placeholder="All Providers" clearable>
                                    @foreach ($this->providers as $provider)
                                        <flux:select.option value="{{ $provider->id }}">{{ $provider->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                            <flux:field>
                                <flux:label>Status</flux:label>
                                <flux:select wire:model.live="filterStatus" placeholder="All Statuses" clearable>
                                    @foreach ($this->statuses as $status)
                                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                        </div>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>

        <flux:table :paginate="$this->stations">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Station</flux:table.column>
                <flux:table.column>Location</flux:table.column>
                <flux:table.column>Provider</flux:table.column>
                <flux:table.column>Contact</flux:table.column>
                <flux:table.column>Holding Days</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->stations as $station)
                    <flux:table.row :key="$station->id">
                        <flux:table.cell class="ps-4!">
                            <flux:heading size="sm">{{ $station->name }}</flux:heading>
                            <flux:subheading>{{ $station->code }}</flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>{{ $station->county->name }}</flux:subheading>
                            @if ($station->area)
                                <flux:subheading>{{ $station->area->name }}</flux:subheading>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>{{ $station->logisticsProvider->name }}</flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>{{ $station->phone ?? '—' }}</flux:subheading>
                            @if ($station->operating_hours)
                                <flux:subheading class="max-w-45 truncate">
                                    {{ $station->operating_hours }}</flux:subheading>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>{{ $station->holding_days }} days</flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $status =
                                    $station->status instanceof \App\Enums\PickupStationStatus
                                        ? $station->status
                                        : \App\Enums\PickupStationStatus::from($station->status);
                            @endphp
                            <flux:badge :color="$status->color()" variant="flat" size="sm">
                                {{ $status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer" wire:click="edit({{ $station->id }})" tooltip="Edit station" />
                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $station->id }})" tooltip="Delete station" />
                        </flux:table.cell>
                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <flux:icon.building-storefront class="w-10 h-10 opacity-40 text-zinc-400" />
                                <div>
                                    <flux:heading size="sm" class="font-medium!">No stations found</flux:heading>
                                    <flux:subheading class="text-xs! mt-0.5">
                                        @if ($this->search || $this->filterCounty || $this->filterProvider || $this->filterStatus)
                                            No results match your current filters.
                                        @else
                                            Add your first pickup station to enable the PUS delivery model.
                                        @endif
                                    </flux:subheading>
                                </div>
                                @if ($this->search || $this->filterCounty || $this->filterProvider || $this->filterStatus)
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="$set('search', ''); $set('filterCounty', ''); $set('filterProvider', ''); $set('filterStatus', '')">
                                        Clear filters
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Create / Edit Modal --}}
    <flux:modal name="station-modal" class="md:w-xl space-y-6">
        <flux:heading size="lg">{{ $form->station ? 'Edit Station' : 'Add Pickup Station' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="form.name" label="Station Name" placeholder="e.g. Westlands Station"
                    class="col-span-2" />

                <flux:input wire:model="form.code" label="Code" placeholder="e.g. westlands-station"
                    description="Unique slug for this station." />

                <flux:input wire:model="form.holding_days" label="Holding Days" type="number" min="1"
                    max="30" description="Days before parcel is returned." />
            </div>

            <flux:select wire:model="form.logistics_provider_id" label="Logistics Provider" placeholder="Select...">
                @foreach ($this->providers as $provider)
                    <flux:select.option value="{{ $provider->id }}">{{ $provider->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model.live="form.county_id" label="County" searchable placeholder="Select county...">
                    @foreach ($this->counties as $county)
                        <flux:select.option value="{{ $county->id }}">{{ $county->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form.area_id" label="Area (Optional)" clearable placeholder="Select area...">
                    @foreach ($this->areasForForm as $area)
                        <flux:select.option value="{{ $area->id }}">{{ $area->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea wire:model="form.address" label="Full Address" rows="2"
                placeholder="Street address..." />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="form.phone" label="Phone (Optional)" placeholder="+254 700 000 000" />
                <flux:select wire:model="form.status" label="Status">
                    @foreach ($this->statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input wire:model="form.operating_hours" label="Operating Hours (Optional)"
                placeholder="e.g. Mon–Fri 8am–6pm, Sat 9am–2pm" />

            {{-- Coordinates --}}
            <flux:field>
                <flux:label>Coordinates (Optional)</flux:label>
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="form.latitude" placeholder="-1.2921" type="number"
                        step="any" />
                    <flux:input wire:model="form.longitude" placeholder="36.8219" type="number"
                        step="any" />
                </div>
                <flux:description>Latitude and Longitude for mapping</flux:description>
            </flux:field>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Station</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">Delete Station?</flux:heading>
        <flux:subheading>Stations with delivery orders cannot be deleted. Set the status to <strong>Inactive</strong> to
            stop routing parcels here.</flux:subheading>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Delete</flux:button>
        </div>
    </flux:modal>

</x-admin.logistics.layout>
