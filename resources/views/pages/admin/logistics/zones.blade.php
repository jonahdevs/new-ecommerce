<?php
use App\Models\ShippingZone;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Shipping Zones')] class extends Component {
    use WithPagination;

    // Form State
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public bool $is_active = true;

    #[Computed]
    public function zones()
    {
        return ShippingZone::orderBy('sort_order')->paginate(10);
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required|min:3',
            'code' => 'required|unique:shipping_zones,code,' . ($this->editingId ?? 'NULL'),
            'description' => 'nullable',
            'is_active' => 'boolean',
        ]);

        ShippingZone::updateOrCreate(['id' => $this->editingId], $data);

        Flux::toast($this->editingId ? 'Zone updated.' : 'Zone created.');

        $this->resetForm();
        Flux::modal('zone-modal')->close();
    }

    public function edit($id)
    {
        $zone = ShippingZone::findOrFail($id);
        $this->editingId = $zone->id;
        $this->name = $zone->name;
        $this->code = $zone->code;
        $this->description = $zone->description ?? '';
        $this->is_active = (bool) $zone->is_active;

        Flux::modal('zone-modal')->show();
    }

    public function toggleStatus($id)
    {
        $zone = ShippingZone::findOrFail($id);
        $zone->update(['is_active' => !$zone->is_active]);
        Flux::toast('Zone status updated.');
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        Flux::modal('delete-confirmation')->show();
    }

    public function delete()
    {
        if ($this->deletingId) {
            ShippingZone::destroy($this->deletingId);

            Flux::modal('delete-confirmation')->close();
            Flux::toast(variant: 'danger', text: 'Shipping zone permanently deleted.');

            $this->deletingId = null;
        }
    }

    public function resetForm()
    {
        $this->reset(['name', 'code', 'description', 'editingId', 'is_active']);
        $this->is_active = true;
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl" class="mb-2">Shipping Zones</flux:heading>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="#" icon="home" icon-variant="outline"></flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Shipping Zones</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="resetForm" @click="$flux.modal('zone-modal').show()"
            class="cursor-pointer">
            Add Zone
        </flux:button>
    </div>

    <flux:table :paginate="$this->zones">
        <flux:table.columns>
            <flux:table.column>Zone Name</flux:table.column>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->zones as $zone)
                <flux:table.row :key="$zone->id">
                    <flux:table.cell>
                        <div class="font-semibold">{{ $zone->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $zone->description }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm">{{ $zone->code }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:switch wire:click="toggleStatus({{ $zone->id }})" :checked="$zone->is_active" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button variant="ghost" size="sm" icon="pencil-square" class="cursor-pointer"
                            wire:click="edit({{ $zone->id }})" />

                        <flux:button variant="ghost" size="sm" icon="trash" color="red" class="cursor-pointer"
                            wire:click="confirmDelete({{ $zone->id }})" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>


    <flux:modal name="zone-modal" class="md:w-100 space-y-6">
        <div>
            <flux:heading size="lg">{{ $editingId ? 'Edit Zone' : 'Create Zone' }}</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="name" label="Name" />
            <flux:input wire:model="code" label="Short Code" />
            <flux:textarea wire:model="description" label="Description" />

            <flux:field variant="inline">
                <flux:label>Active Status</flux:label>
                <flux:description>Enable this zone for shipping calculations.</flux:description>
                <flux:switch wire:model="is_active" />
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

    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <div class="flex flex-col items-center text-center">
            <flux:heading size="lg">Delete Shipping Zone?</flux:heading>
            <flux:subheading>
                Are you sure? This action cannot be undone and may affect associated rates and locations.
            </flux:subheading>
        </div>

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
