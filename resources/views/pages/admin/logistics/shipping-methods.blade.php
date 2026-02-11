<?php
use App\Models\ShippingMethod;
use Livewire\Attributes\{Title, Computed};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Shipping Methods')] class extends Component {
    use WithPagination;

    public string $name = '';
    public string $description = '';
    public ?string $code = '';
    public bool $is_active = true;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    #[Computed]
    public function methods()
    {
        return ShippingMethod::latest()->paginate(10);
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required|min:3',
            'description' => 'nullable',
            'estimated_delivery' => 'required', // e.g., "3-5 Business Days"
            'is_active' => 'boolean',
        ]);

        ShippingMethod::updateOrCreate(['id' => $this->editingId], $data);

        Flux::toast($this->editingId ? 'Method updated.' : 'Method created.');
        $this->resetForm();
        Flux::modal('method-modal')->close();
    }

    public function edit($id)
    {
        $method = ShippingMethod::findOrFail($id);
        $this->editingId = $method->id;
        $this->name = $method->name;
        $this->description = $method->description ?? '';
        $this->estimated_delivery = $method->estimated_delivery;
        $this->is_active = $method->is_active;

        Flux::modal('method-modal')->show();
    }

    public function toggleStatus($id)
    {
        $method = ShippingMethod::find($id);
        $method->update(['is_active' => !$method->is_active]);
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        Flux::modal('delete-confirmation')->show();
    }

    public function delete()
    {
        ShippingMethod::destroy($this->deletingId);
        Flux::modal('delete-confirmation')->close();
        Flux::toast(variant: 'danger', text: 'Method deleted.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'description', 'estimated_delivery', 'is_active', 'editingId']);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl" class="mb-2">Shipping Methods</flux:heading>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="#" icon="home" icon-variant="outline"></flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Shipping Methods</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="resetForm"
            x-on:click="$flux.modal('method-modal').show()">
            New Method
        </flux:button>
    </div>

    <flux:table :paginate="$this->methods">
        <flux:table.columns>
            <flux:table.column>Service Name</flux:table.column>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->methods as $method)
                <flux:table.row :key="$method->id">
                    <flux:table.cell>
                        <div class="font-semibold">{{ $method->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $method->description }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $method->code ?? 'N/A' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:switch wire:click="toggleStatus({{ $method->id }})" :checked="$method->is_active" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button variant="ghost" size="sm" icon="pencil-square" class="cursor-pointer"
                            wire:click="edit({{ $method->id }})" />

                        <flux:button variant="ghost" size="sm" icon="trash" color="danger" class="cursor-pointer"
                            wire:click="confirmDelete({{ $method->id }})" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="method-modal" class="md:w-md space-y-6">
        <flux:heading size="lg">{{ $editingId ? 'Edit Method' : 'Create Shipping Method' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="name" label="Name" placeholder="e.g. Doorstep Delivery" />
            <flux:input wire:model="code" label="Code" placeholder="e.g. DSD" />
            <flux:textarea wire:model="description" label="Internal Notes" />

            <flux:checkbox wire:model="is_active" label="Enable this method for customers" />

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Method</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <div class="text-center">
            <flux:heading size="lg">Delete Method?</flux:heading>
            <flux:subheading>This will remove it from all assigned shipping rates.</flux:subheading>
        </div>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>

            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Confirm</flux:button>
        </div>
    </flux:modal>
</div>
