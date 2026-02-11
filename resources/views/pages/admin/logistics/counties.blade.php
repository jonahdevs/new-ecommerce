<?php
use App\Models\County;
use App\Models\ShippingZone;
use Livewire\Attributes\{Title, Computed};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Manage Counties')] class extends Component {
    use WithPagination;

    // Form State
    public string $name = '';
    public string $code = '';
    public ?int $shipping_zone_id = null;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    // Search/Filter State
    public string $search = '';

    #[Computed]
    public function counties()
    {
        return County::with('shippingZone')->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))->orderBy('name')->paginate(10);
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::active()->get();
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required|unique:counties,name,' . ($this->editingId ?? 'NULL'),
            'code' => 'nullable|unique:counties,code,' . ($this->editingId ?? 'NULL'),
            'shipping_zone_id' => 'required|exists:shipping_zones,id',
        ]);

        County::updateOrCreate(['id' => $this->editingId], $data);

        Flux::toast($this->editingId ? 'County updated.' : 'County created.');
        $this->resetForm();
        Flux::modal('county-modal')->close();
    }

    public function edit($id)
    {
        $county = County::findOrFail($id);
        $this->editingId = $county->id;
        $this->name = $county->name;
        $this->code = $county->code ?? '';
        $this->shipping_zone_id = $county->shipping_zone_id;

        Flux::modal('county-modal')->show();
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        Flux::modal('delete-confirmation')->show();
    }

    public function delete()
    {
        County::destroy($this->deletingId);
        Flux::modal('delete-confirmation')->close();
        Flux::toast(variant: 'danger', text: 'County removed.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'code', 'shipping_zone_id', 'editingId']);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl" class="mb-2">Counties</flux:heading>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="#" icon="home" icon-variant="outline"></flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Counties</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="resetForm" @click="$flux.modal('county-modal').show()"
            class="cursor-pointer">
            Add County
        </flux:button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search counties..." icon="magnifying-glass"
            class="max-w-md" />
    </div>

    <flux:table :paginate="$this->counties">
        <flux:table.columns>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>County Name</flux:table.column>
            <flux:table.column>Zone</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->counties as $county)
                <flux:table.row :key="$county->id">
                    <flux:table.cell>
                        <flux:badge variant="outline" color="zinc" class="font-mono">
                            {{ str_pad($county->code, 3, '0', STR_PAD_LEFT) }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="font-semibold">{{ $county->name }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:badge color="zinc" variant="outline">{{ $county->shippingZone->name }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button variant="ghost" size="sm" icon="pencil-square" class="cursor-pointer"
                            wire:click="edit({{ $county->id }})" />
                        <flux:button variant="ghost" size="sm" icon="trash" color="danger" class="cursor-pointer"
                            wire:click="confirmDelete({{ $county->id }})" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="county-modal" class="md:w-100 space-y-6">
        <flux:heading size="lg" class="text-center">{{ $editingId ? 'Edit County' : 'Add County' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-4 gap-4">
                <div class="col-span-1">
                    <flux:input wire:model="code" label="Code" placeholder="047" maxlength="3" />
                </div>
                <div class="col-span-3">
                    <flux:input wire:model="name" label="County Name" />
                </div>
            </div>

            <flux:select wire:model="shipping_zone_id" label="Shipping Zone" searchable placeholder="Select a zone...">
                @foreach ($this->zones as $zone)
                    <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2">Save County</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <div class="text-center">
            <flux:heading size="lg">Remove County?</flux:heading>
            <flux:subheading>This will unbind it from its shipping zone.</flux:subheading>
        </div>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>

            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Delete</flux:button>
        </div>
    </flux:modal>
</div>
