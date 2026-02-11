<?php
use App\Models\PickupStation;
use App\Models\County;
use App\Models\Area;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Pickup Stations')] class extends Component {
    use WithPagination;

    // Form State
    public string $name = '';
    public string $code = '';
    public ?int $county_id = null;
    public ?int $area_id = null;
    public string $address = '';
    public string $phone = '';
    public string $operating_hours = '';
    public ?float $latitude = null;
    public ?float $longitude = null;
    public bool $is_active = true;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    // Search
    #[Url(history: true)]
    public string $search = '';

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
        if ($this->county_id) {
            return Area::where('county_id', $this->county_id)->get();
        }

        return collect();
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required|min:3',
            'code' => 'required|unique:pickup_stations,code,' . ($this->editingId ?? 'NULL'),
            'county_id' => 'required|exists:counties,id',
            'area_id' => 'nullable|exists:areas,id',
            'address' => 'required',
            'phone' => 'nullable',
            'operating_hours' => 'nullable',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);

        PickupStation::updateOrCreate(['id' => $this->editingId], $data);

        Flux::toast($this->editingId ? 'Station updated.' : 'Station created.');
        $this->resetForm();
        Flux::modal('station-modal')->close();
    }

    public function edit($id)
    {
        $station = PickupStation::findOrFail($id);
        $this->editingId = $station->id;
        $this->name = $station->name;
        $this->code = $station->code;
        $this->county_id = $station->county_id;
        $this->area_id = $station->area_id;
        $this->address = $station->address;
        $this->phone = $station->phone ?? '';
        $this->operating_hours = $station->operating_hours ?? '';
        $this->latitude = $station->latitude;
        $this->longitude = $station->longitude;
        $this->is_active = $station->is_active;

        Flux::modal('station-modal')->show();
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        Flux::modal('delete-confirmation')->show();
    }

    public function delete()
    {
        PickupStation::destroy($this->deletingId);
        Flux::modal('delete-confirmation')->close();
        Flux::toast(variant: 'danger', text: 'Pickup station removed.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'code', 'county_id', 'area_id', 'address', 'phone', 'operating_hours', 'latitude', 'longitude', 'editingId', 'is_active']);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">Pickup Stations</flux:heading>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="#" icon="home" icon-variant="outline"></flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Pickup Stations</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="resetForm" @click="$flux.modal('station-modal').show()"
            class="cursor-pointer">
            New Station
        </flux:button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or code..."
            icon="magnifying-glass" class="max-w-md" />
    </div>

    <flux:table :paginate="$this->stations">
        <flux:table.columns>
            <flux:table.column>Station</flux:table.column>
            <flux:table.column>Location</flux:table.column>
            <flux:table.column>Contact/Hours</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->stations as $station)
                <flux:table.row :key="$station->id">
                    <flux:table.cell>
                        <div class="font-semibold">{{ $station->name }}</div>
                        <div class="text-xs text-zinc-500 font-mono">{{ $station->code }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="text-sm">{{ $station->county->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $station->area?->name ?? 'General Area' }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="text-xs italic">{{ $station->phone ?: 'No phone' }}</div>
                        <div class="text-[10px] text-zinc-500 truncate max-w-37.5">
                            {{ $station->operating_hours }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:switch wire:click="toggleActive({{ $station->id }})" :checked="$station->is_active" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button variant="ghost" size="sm" icon="pencil-square"
                            wire:click="edit({{ $station->id }})" class="cursor-pointer" />
                        <flux:button variant="ghost" size="sm" icon="trash" color="danger" class="cursor-pointer"
                            wire:click="confirmDelete({{ $station->id }})" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="station-modal" class="md:w-140 space-y-6">
        <flux:heading size="lg">{{ $editingId ? 'Edit' : 'Create' }} Pickup Station</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="name" label="Station Name" placeholder="e.g. Nairobi CBD Hub" />
                <flux:input wire:model="code" label="Unique Code" placeholder="cbd-hub-01" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model.live="county_id" label="County">
                    <option value="">Select County...</option>
                    @foreach ($this->counties as $county)
                        <option value="{{ $county->id }}">{{ $county->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="area_id" label="Area (Optional)" :disabled="!$county_id">
                    <option value="">Select Area...</option>
                    @foreach ($this->availableAreas as $area)
                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea wire:model="address" label="Detailed Address" rows="2" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="phone" label="Contact Phone" />
                <flux:input wire:model="operating_hours" label="Operating Hours" placeholder="e.g. Mon-Fri 8am-6pm" />
            </div>

            <div class="grid grid-cols-2 gap-4 p-3 bg-zinc-50 rounded-lg border border-zinc-200">
                <flux:input type="number" step="0.0000001" wire:model="latitude" label="Latitude"
                    placeholder="-1.2863" />
                <flux:input type="number" step="0.0000001" wire:model="longitude" label="Longitude"
                    placeholder="36.8219" />
            </div>

            <flux:checkbox wire:model="is_active" label="Station is open for business" />

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Station</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <div class="text-center">
            <flux:heading size="lg">Delete Station?</flux:heading>
            <flux:subheading>Customers will no longer be able to select this point.</flux:subheading>
        </div>

        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>

            <flux:button wire:click="delete" variant="primary" color="danger" class="flex-1 cursor-pointer">Confirm
                Delete
            </flux:button>
        </div>
    </flux:modal>
</div>
