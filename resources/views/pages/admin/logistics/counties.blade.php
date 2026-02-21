<?php

use App\Models\County;
use App\Models\ShippingZone;
use App\Livewire\Forms\Admin\CountyForm;
use Livewire\Attributes\{Title, Computed};
use Livewire\WithPagination;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Flux\Flux;

new #[Title('Manage Counties')] class extends Component {
    use WithPagination;

    public CountyForm $form;
    public ?int $deletingId = null;
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

    public function openCreate(): void
    {
        $this->form->reset();
        Flux::modal('county-modal')->show();
    }

    public function save(): void
    {
        try {
            $isEditing = (bool) $this->form->county;

            $isEditing ? $this->form->update() : $this->form->store();

            $this->form->reset();

            Flux::modal('county-modal')->close();

            $this->dispatch('notify', variant: 'success', message: $isEditing ? 'County updated' : 'County created.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save county.', [
                'exception' => $e->getMessage(),
                'county_id' => $this->form->county?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(County $county): void
    {
        $this->form->setCounty($county);
        Flux::modal('county-modal')->show();
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
            County::destroy($this->deletingId);
            $this->deletingId = null;

            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', variant: 'success', message: 'County removed.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete county.', [
                'exception' => $e->getMessage(),
                'county_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);

            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl" class="mb-2">Counties</flux:heading>
            <flux:subheading>Manage counties used for customer addresses and delivery coverage.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" class="cursor-pointer">
            Add County
        </flux:button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search counties..." icon="magnifying-glass"
            class="max-w-md" />
    </div>


    <flux:card class="p-0">
        <flux:table :paginate="$this->counties">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Code</flux:table.column>
                <flux:table.column>County Name</flux:table.column>
                <flux:table.column>Zone</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->counties as $county)
                    <flux:table.row :key="$county->id">
                        <flux:table.cell class="ps-4!">
                            <flux:badge variant="outline" color="zinc" class="font-mono">
                                {{ str_pad($county->code, 3, '0', STR_PAD_LEFT) }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>{{ $county->name }}</flux:table.cell>

                        <flux:table.cell>
                            <flux:badge color="zinc" variant="outline" size="sm">
                                {{ $county->shippingZone->name }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer text-sheffield-blue!" wire:click="edit({{ $county->id }})" />

                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $county->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- County Create / Edit Modal --}}
    <flux:modal name="county-modal" class="md:w-100 space-y-6">
        <flux:heading size="lg" class="text-center">
            {{ $form->county ? 'Edit County' : 'Add County' }}
        </flux:heading>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-4 gap-4">
                <div class="col-span-1">
                    <flux:input wire:model="form.code" label="Code" placeholder="047" maxlength="3" />
                </div>
                <div class="col-span-3">
                    <flux:input wire:model="form.name" label="County Name" />
                </div>
            </div>

            <flux:select wire:model="form.shipping_zone_id" label="Shipping Zone" searchable
                placeholder="Select a zone...">
                @foreach ($this->zones as $zone)
                    <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save County</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">Remove County?</flux:heading>
        <flux:subheading>This will unbind it from its shipping zone.</flux:subheading>

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
