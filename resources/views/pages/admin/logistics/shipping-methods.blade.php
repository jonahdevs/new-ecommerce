<?php

use App\Models\ShippingMethod;
use App\Livewire\Forms\Admin\ShippingMethodForm;
use Livewire\Attributes\{Title, Computed};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Shipping Methods')] class extends Component {
    use WithPagination;

    public ShippingMethodForm $form;
    public ?int $deletingId = null;

    #[Computed]
    public function methods()
    {
        return ShippingMethod::latest()->paginate(10);
    }

    public function openCreate(): void
    {
        $this->form->reset();
        Flux::modal('method-modal')->show();
    }

    public function save(): void
    {
        try {
            $isEditing = (bool) $this->form->method;

            $isEditing ? $this->form->update() : $this->form->store();

            $this->form->reset();
            Flux::modal('method-modal')->close();
            $this->dispatch('notify', variant: 'success', message: $isEditing ? 'Method updated.' : 'Method created.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save shipping method.', [
                'exception' => $e->getMessage(),
                'method_id' => $this->form->method?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(ShippingMethod $method): void
    {
        $this->form->setMethod($method);
        Flux::modal('method-modal')->show();
    }

    public function toggleStatus(ShippingMethod $method): void
    {
        $method->update(['is_active' => !$method->is_active]);
        $this->dispatch('notify', variant: 'success', message: 'Method status updated.');
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
            ShippingMethod::destroy($this->deletingId);
            $this->deletingId = null;

            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', variant: 'danger', message: 'Method deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete shipping method.', [
                'exception' => $e->getMessage(),
                'method_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Could not delete this method. It may have dependent records.');
        }
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl" class="mb-2">Shipping Methods</flux:heading>
            <flux:subheading>Manage the delivery options available to customers during checkout.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" class="cursor-pointer">
            New Method
        </flux:button>
    </div>

    <flux:card class="p-0">
        <flux:table :paginate="$this->methods">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Method</flux:table.column>
                <flux:table.column>Code</flux:table.column>
                <flux:table.column>Estimated Delivery</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->methods as $method)
                    <flux:table.row :key="$method->id">
                        <flux:table.cell class="ps-4!">
                            <flux:heading>{{ $method->name }}</flux:heading>
                            <flux:text class="text-xs!">{{ $method->description }}</flux:text>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" variant="outline" color="zinc">
                                {{ $method->code ?? 'N/A' }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm text-zinc-600">{{ $method->estimated_delivery }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:switch wire:click="toggleStatus({{ $method->id }})" :checked="$method->is_active" />
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer text-sheffield-blue!" wire:click="edit({{ $method->id }})" />

                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $method->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Method Create / Edit Modal --}}
    <flux:modal name="method-modal" class="md:w-md space-y-6">
        <flux:heading size="lg">{{ $form->method ? 'Edit Method' : 'Create Shipping Method' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <flux:input wire:model="form.name" label="Name" placeholder="e.g. Doorstep Delivery" />
                </div>
                <div class="col-span-1">
                    <flux:input wire:model="form.code" label="Code" placeholder="e.g. DSD" />
                </div>
            </div>

            <flux:input wire:model="form.estimated_delivery" label="Estimated Delivery"
                placeholder="e.g. 3-5 Business Days" />

            <flux:textarea wire:model="form.description" label="Internal Notes" />

            <flux:checkbox wire:model="form.is_active" label="Enable this method for customers" />

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Method</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
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


<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
