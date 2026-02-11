<?php
use App\Models\Area;
use App\Models\County;
use App\Models\ShippingZone;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Manage Areas')] class extends Component {
    use WithPagination;

    // Form State
    public string $name = '';
    public ?int $county_id = null;
    public ?int $shipping_zone_id = null; // Optional override
    public ?int $editingId = null;
    public ?int $deletingId = null;

    // Search and Filter State
    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public $filterCounty = '';

    /**
     * Hook to reset pagination when search or filters change.
     */
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterCounty()
    {
        $this->resetPage();
    }

    #[Computed]
    public function areas()
    {
        return Area::with(['county', 'shippingZone'])
            // Global Search Logic
            ->when($this->search, function ($query) {
                $query->where(fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhereHas('county', fn($c) => $c->where('name', 'like', "%{$this->search}%")));
            })
            // Dropdown Filter Logic
            ->when($this->filterCounty, fn($q) => $q->where('county_id', $this->filterCounty))
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function counties()
    {
        return County::orderBy('name')->get();
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::all();
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required',
            'county_id' => 'required|exists:counties,id',
            'shipping_zone_id' => 'nullable|exists:shipping_zones,id',
        ]);

        Area::updateOrCreate(['id' => $this->editingId], $data);

        Flux::toast($this->editingId ? 'Area updated.' : 'Area added.');
        $this->resetForm();
        Flux::modal('area-modal')->close();
    }

    public function edit($id)
    {
        $area = Area::findOrFail($id);
        $this->editingId = $area->id;
        $this->name = $area->name;
        $this->county_id = $area->county_id;
        $this->shipping_zone_id = $area->shipping_zone_id;

        Flux::modal('area-modal')->show();
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        Flux::modal('delete-confirmation')->show();
    }

    public function delete()
    {
        Area::destroy($this->deletingId);
        Flux::modal('delete-confirmation')->close();
        Flux::toast(variant: 'danger', text: 'Area deleted.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'county_id', 'shipping_zone_id', 'editingId']);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl" class="mb-2">Areas & Towns</flux:heading>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="#" icon="home" icon-variant="outline"></flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Areas & Towns</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="resetForm" x-on:click="$flux.modal('area-modal').show()">
            Add Area
        </flux:button>
    </div>

    <div class="flex flex-col md:flex-row justify-between gap-4 mb-4">

        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search area or county name..."
            icon="magnifying-glass" clearable class="max-w-md" />


        <div class="w-full md:w-64">
            <flux:select wire:model.live="filterCounty" placeholder="All Counties" clearable>
                @foreach ($this->counties as $county)
                    <option value="{{ $county->id }}">{{ $county->name }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->areas">
        <flux:table.columns>
            <flux:table.column>Area Name</flux:table.column>
            <flux:table.column>County</flux:table.column>
            <flux:table.column>Zone</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->areas as $area)
                <flux:table.row :key="$area->id">
                    <flux:table.cell class="font-semibold">{{ $area->name }}</flux:table.cell>

                    <flux:table.cell>{{ $area->county->name }}</flux:table.cell>

                    <flux:table.cell>
                        @if ($area->shippingZone)
                            <flux:badge color="orange" variant="flat" size="sm">
                                {{ $area->shippingZone->name }}
                            </flux:badge>
                        @else
                            <span class="text-xs text-zinc-400">Default (from County)</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button variant="ghost" size="sm" icon="pencil-square"
                            wire:click="edit({{ $area->id }})" class="cursor-pointer" />

                        <flux:button variant="ghost" size="sm" icon="trash" color="danger"
                            wire:click="confirmDelete({{ $area->id }})" class="cursor-pointer" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="area-modal" class="md:w-100 space-y-6">
        <flux:heading size="lg">{{ $editingId ? 'Edit Area' : 'Add New Area' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="name" label="Area Name" placeholder="e.g. Westlands" />

            <flux:select wire:model="county_id" label="Parent County" searchable>
                @foreach ($this->counties as $county)
                    <flux:select.option value="{{ $county->id }}">{{ $county->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="shipping_zone_id" label="Zone Override (Optional)" clearable
                placeholder="Use county zone">
                @foreach ($this->zones as $zone)
                    <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2">Save Area</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <div class="text-center">
            <flux:heading size="lg">Delete Area?</flux:heading>
            <flux:subheading>This area will be removed from its county.</flux:subheading>
        </div>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="primary" color="danger" class="flex-1">Delete</flux:button>
        </div>
    </flux:modal>
</div>
