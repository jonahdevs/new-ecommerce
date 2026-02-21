<?php

use App\Models\PickupStation;
use App\Models\County;
use App\Models\Area;
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // Reset area when county changes so stale area_id isn't submitted
    public function updatedFormCountyId(): void
    {
        $this->form->area_id = null;
    }

    #[Computed]
    public function stations()
    {
        return PickupStation::with(['county', 'area'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function counties()
    {
        return County::orderBy('name')->get();
    }

    #[Computed]
    public function availableAreas()
    {
        if (!$this->form->county_id) {
            return collect();
        }
        return Area::where('county_id', $this->form->county_id)->orderBy('name')->get();
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
            $this->dispatch('notify', variant: 'success', message: $isEditing ? 'Station updated.' : 'Station created.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save pickup station.', [
                'exception' => $e->getMessage(),
                'station_id' => $this->form->station?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(PickupStation $station): void
    {
        $this->form->setStation($station);
        Flux::modal('station-modal')->show();
    }

    public function toggleActive(PickupStation $station): void
    {
        $station->update(['is_active' => !$station->is_active]);
        $this->dispatch('notify', variant: 'success', message: 'Station status updated.');
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
            PickupStation::destroy($this->deletingId);
            $this->deletingId = null;

            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', variant: 'danger', message: 'Pickup station removed.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete pickup station.', [
                'exception' => $e->getMessage(),
                'station_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Could not delete this station. It may have dependent records.');
        }
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">Pickup Stations</flux:heading>
            <flux:subheading>Manage pickup stations where customers can collect their orders.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" class="cursor-pointer">
            New Station
        </flux:button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or code..." icon="magnifying-glass"
            class="max-w-md" />
    </div>


    <flux:card class="p-0">
        <flux:table :paginate="$this->stations">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Station</flux:table.column>
                <flux:table.column>Location</flux:table.column>
                <flux:table.column>Contact / Hours</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->stations as $station)
                    <flux:table.row :key="$station->id">
                        <flux:table.cell class="ps-4!">
                            <div class="font-semibold">{{ $station->name }}</div>
                            <div class="text-xs text-zinc-500 font-mono">{{ $station->code }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-sm">{{ $station->county->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $station->area?->name ?? 'General Area' }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-xs italic">{{ $station->phone ?: 'No phone' }}</div>
                            <div class="text-[10px] text-zinc-500 truncate max-w-[150px]">
                                {{ $station->operating_hours }}
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:switch wire:click="toggleActive({{ $station->id }})"
                                :checked="$station->is_active" />
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer text-sheffield-blue!" wire:click="edit({{ $station->id }})" />

                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $station->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Station Create / Edit Modal --}}
    <flux:modal name="station-modal" class="md:w-140 space-y-6">
        <flux:heading size="lg">{{ $form->station ? 'Edit' : 'Create' }} Pickup Station</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="form.name" label="Station Name" placeholder="e.g. Nairobi CBD Hub" />
                <flux:input wire:model="form.code" label="Unique Code" placeholder="e.g. cbd-hub-01" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model.live="form.county_id" label="County" placeholder="Select County...">
                    @foreach ($this->counties as $county)
                        <flux:select.option value="{{ $county->id }}">{{ $county->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form.area_id" label="Area (Optional)" placeholder="Select Area..."
                    :disabled="! $form->county_id">
                    @foreach ($this->availableAreas as $area)
                        <flux:select.option value="{{ $area->id }}">{{ $area->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea wire:model="form.address" label="Detailed Address" rows="2" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="form.phone" label="Contact Phone" />
                <flux:input wire:model="form.operating_hours" label="Operating Hours"
                    placeholder="e.g. Mon-Fri 8am-6pm" />
            </div>

            <div class="grid grid-cols-2 gap-4 p-3 bg-zinc-50 rounded-lg border border-zinc-200">
                <flux:input type="number" step="0.0000001" wire:model="form.latitude" label="Latitude"
                    placeholder="-1.2863" />
                <flux:input type="number" step="0.0000001" wire:model="form.longitude" label="Longitude"
                    placeholder="36.8219" />
            </div>

            <flux:checkbox wire:model="form.is_active" label="Station is open for business" />

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Station</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <div class="text-center">
            <flux:heading size="lg">Delete Station?</flux:heading>
            <flux:subheading>Customers will no longer be able to select this pickup point.</flux:subheading>
        </div>

        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Confirm Delete</flux:button>
        </div>
    </flux:modal>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
