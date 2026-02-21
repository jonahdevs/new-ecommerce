<?php

use App\Models\Area;
use App\Models\County;
use App\Models\ShippingZone;
use App\Livewire\Forms\Admin\AreaForm;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Manage Areas')] class extends Component {
    use WithPagination;

    public AreaForm $form;
    public ?int $deletingId = null;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterCounty = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCounty(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function areas()
    {
        return Area::with(['county', 'shippingZone'])
            ->when($this->search, fn($q) => $q->where(fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhereHas('county', fn($c) => $c->where('name', 'like', "%{$this->search}%"))))
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
        return ShippingZone::active()->get();
    }

    public function openCreate(): void
    {
        $this->form->reset();
        Flux::modal('area-modal')->show();
    }

    public function save(): void
    {
        try {
            $isEditing = (bool) $this->form->area;

            $isEditing ? $this->form->update() : $this->form->store();

            $this->form->reset();
            Flux::modal('area-modal')->close();
            $this->dispatch('notify', variant: 'success', message: $isEditing ? 'Area updated.' : 'Area added.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save area.', [
                'exception' => $e->getMessage(),
                'area_id' => $this->form->area?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(Area $area): void
    {
        $this->form->setArea($area);
        Flux::modal('area-modal')->show();
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
            Area::destroy($this->deletingId);
            $this->deletingId = null;

            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', variant: 'danger', message: 'Area deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete area.', [
                'exception' => $e->getMessage(),
                'area_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Could not delete this area. It may have dependent records.');
        }
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl" class="mb-2">Areas & Towns</flux:heading>
            <flux:subheading>Organize towns and local areas to support accurate delivery coverage and address selection.
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" class="cursor-pointer">
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
    .
    <flux:card class="p-0">
        <flux:table :paginate="$this->areas">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Area Name</flux:table.column>
                <flux:table.column>County</flux:table.column>
                <flux:table.column>Zone</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->areas as $area)
                    <flux:table.row :key="$area->id">
                        <flux:table.cell class="font-semibold ps-4!">{{ $area->name }}</flux:table.cell>

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

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer text-sheffield-blue!" wire:click="edit({{ $area->id }})" />

                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $area->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Area Create / Edit Modal --}}
    <flux:modal name="area-modal" class="md:w-100 space-y-6">
        <flux:heading size="lg">{{ $form->area ? 'Edit Area' : 'Add New Area' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="form.name" label="Area Name" placeholder="e.g. Westlands" />

            <flux:select wire:model="form.county_id" label="Parent County" searchable placeholder="Select a county...">
                @foreach ($this->counties as $county)
                    <flux:select.option value="{{ $county->id }}">{{ $county->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="form.shipping_zone_id" label="Zone Override (Optional)" clearable
                placeholder="Use county zone">
                @foreach ($this->zones as $zone)
                    <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Area</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">Delete Area?</flux:heading>
        <flux:subheading>This area will be removed from its county.</flux:subheading>

        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Delete</flux:button>
        </div>
    </flux:modal>
</div>


<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
