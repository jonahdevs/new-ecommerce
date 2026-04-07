<?php

use App\Livewire\Forms\Admin\TaxClassForm;
use App\Models\TaxClass;
use Livewire\Attributes\{Title, Computed};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Tax Classes')] class extends Component {
    use WithPagination;

    public TaxClassForm $form;
    public ?int $deletingId = null;

    #[Computed]
    public function taxClasses()
    {
        return TaxClass::withCount('products')->orderBy('name')->paginate(15);
    }

    public function openCreate(): void
    {
        $this->form->reset();
        Flux::modal('tax-class-modal')->show();
    }

    public function save(): void
    {
        try {
            $isEditing = (bool) $this->form->taxClass;
            $isEditing ? $this->form->update() : $this->form->store();

            $this->form->reset();
            unset($this->taxClasses);
            Flux::modal('tax-class-modal')->close();
            $this->dispatch('notify', variant: 'success', title: $isEditing ? 'Class Updated' : 'Class Added', message: $isEditing ? 'Tax class updated.' : 'Tax class added.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save tax class.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: 'Save Failed', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(TaxClass $taxClass): void
    {
        $this->form->setTaxClass($taxClass);
        Flux::modal('tax-class-modal')->show();
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
            $taxClass = TaxClass::findOrFail($this->deletingId);

            if ($taxClass->products()->exists()) {
                $this->dispatch('notify', variant: 'warning', title: 'Cannot Delete', message: 'This tax class is assigned to products. Reassign them first.');
                Flux::modal('delete-confirmation')->close();
                return;
            }

            $taxClass->delete();
            $this->deletingId = null;
            unset($this->taxClasses);
            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', variant: 'danger', title: 'Class Deleted', message: 'Tax class deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete tax class.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: 'Delete Failed', message: 'Could not delete this tax class.');
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout>

        <div class="flex items-start justify-between mb-5">
            <div>
                <flux:heading size="lg">{{ __('Tax Classes') }}</flux:heading>
                <flux:subheading>{{ __('Define tax rates for different product types. Assign a class to a product to override the global default.') }}</flux:subheading>
            </div>
            <flux:button variant="primary" icon="plus-circle" wire:click="openCreate" class="cursor-pointer shrink-0">
                Add Class
            </flux:button>
        </div>

        <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
            <flux:table :paginate="$this->taxClasses">
                <flux:table.columns>
                    <flux:table.column class="ps-4!">Name</flux:table.column>
                    <flux:table.column>Rate</flux:table.column>
                    <flux:table.column>Products</flux:table.column>
                    <flux:table.column>Description</flux:table.column>
                    <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->taxClasses as $taxClass)
                        <flux:table.row :key="$taxClass->id">
                            <flux:table.cell class="ps-4!">
                                <flux:heading size="sm">{{ $taxClass->name }}</flux:heading>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge color="{{ (float) $taxClass->rate === 0.0 ? 'zinc' : 'blue' }}" variant="flat" size="sm">
                                    {{ $taxClass->rateLabel() }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:subheading>{{ $taxClass->products_count }}</flux:subheading>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:subheading class="max-w-xs truncate block">
                                    {{ $taxClass->description ?? '—' }}
                                </flux:subheading>
                            </flux:table.cell>

                            <flux:table.cell align="end" class="pe-4!">
                                <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                    class="cursor-pointer" wire:click="edit({{ $taxClass->id }})" tooltip="Edit class" />
                                <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                    color="red" class="cursor-pointer text-red-500!"
                                    wire:click="confirmDelete({{ $taxClass->id }})" tooltip="Delete class" />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="py-12 text-center">
                                <div class="flex flex-col items-center gap-3 text-zinc-400">
                                    <flux:icon.receipt-percent class="w-10 h-10 opacity-40" />
                                    <div>
                                        <flux:heading size="sm">No tax classes yet</flux:heading>
                                        <flux:subheading class="mt-0.5">Add a class to override the default rate for specific products.</flux:subheading>
                                    </div>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

    </x-pages::admin.settings.layout>

    {{-- Create / Edit Modal --}}
    <flux:modal name="tax-class-modal" class="md:w-md space-y-6">
        <flux:heading size="lg">{{ $form->taxClass ? 'Edit Tax Class' : 'Add Tax Class' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="form.name" label="Name" placeholder="e.g. Zero-Rated" />

            <flux:input wire:model="form.rate" label="Rate (%)" type="number" min="0" max="100"
                step="0.01" placeholder="0.00"
                description="Set to 0 for zero-rated or exempt products." />

            <flux:textarea wire:model="form.description" label="Description (Optional)"
                placeholder="Brief note about when this class applies..." rows="2" />

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Class</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">Delete Tax Class?</flux:heading>
        <flux:subheading>Tax classes assigned to products cannot be deleted. Reassign those products first.</flux:subheading>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Delete</flux:button>
        </div>
    </flux:modal>
</div>
