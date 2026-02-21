<?php

use App\Models\ShippingZone;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;
use App\Livewire\Forms\Admin\ShippingZoneForm;

new #[Title('Shipping Zones')] class extends Component {
    use WithPagination;

    public ShippingZoneForm $form;
    public ?int $deletingId = null;

    #[Computed]
    public function zones()
    {
        return ShippingZone::paginate(10);
    }

    public function save()
    {
        try {
            $this->form->zone ? $this->form->update() : $this->form->store();
            $this->form->reset();
            Flux::modal('zone-modal')->close();
            $this->dispatch('notify', variant: 'success', message: 'Shipping zone saved.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save shipping zone: ' . $e->getMessage());
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(ShippingZone $zone)
    {
        $this->form->setZone($zone);
        Flux::modal('zone-modal')->show();
    }

    public function openCreate()
    {
        $this->form->reset();
        Flux::modal('zone-modal')->show();
    }

    public function toggleStatus(ShippingZone $zone)
    {
        $zone->update(['is_active' => !$zone->is_active]);
        Flux::toast('Zone status updated.');
    }

    public function confirmDelete(int $id)
    {
        $this->deletingId = $id;
        Flux::modal('delete-confirmation')->show();
    }

    public function delete()
    {
        if ($this->deletingId) {
            ShippingZone::destroy($this->deletingId);
            $this->deletingId = null;

            Flux::modal('delete-confirmation')->close();
            Flux::toast(variant: 'danger', text: 'Shipping zone permanently deleted.');
        }
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl" class="mb-2">Shipping Zones</flux:heading>
            <flux:subheading>Group counties and towns into shipping zones to control delivery costs and availability.
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" class="cursor-pointer">
            Add Zone
        </flux:button>
    </div>

    <flux:card class="p-0">
        <flux:table :paginate="$this->zones">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Zone Name</flux:table.column>
                <flux:table.column>Code</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->zones as $zone)
                    <flux:table.row :key="$zone->id">
                        <flux:table.cell class="ps-4!">
                            <div class="font-semibold">{{ $zone->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $zone->description }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm">{{ $zone->code }}</flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:switch wire:click="toggleStatus({{ $zone->id }})" :checked="$zone->is_active" />
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer text-sheffield-blue!" wire:click="edit({{ $zone->id }})" />

                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $zone->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Zone Create / Edit Modal --}}
    <flux:modal name="zone-modal" class="md:w-100 space-y-6">
        <flux:heading size="lg">
            {{ $form->zone ? 'Edit Zone' : 'Create Zone' }}
        </flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="form.name" label="Name" />
            <flux:input wire:model="form.code" label="Short Code" />
            <flux:textarea wire:model="form.description" label="Description" />

            <flux:field variant="inline">
                <flux:label>Active Status</flux:label>
                <flux:description>Enable this zone for shipping calculations.</flux:description>
                <flux:switch wire:model="form.is_active" />
            </flux:field>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg">Delete Shipping Zone?</flux:heading>

        <flux:subheading>
            Are you sure? This action cannot be undone and may affect associated rates and locations.
        </flux:subheading>

        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>

            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">
                Yes, Delete
            </flux:button>
        </div>
    </flux:modal>
</div>


<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
