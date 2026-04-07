<?php

use App\Models\County;
use App\Models\ShippingZone;
use App\Livewire\Forms\Admin\CountyForm;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Counties')] class extends Component {
    use WithPagination;

    public CountyForm $form;
    public ?int $deletingId = null;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterZone = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedFilterZone(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function counties()
    {
        return County::with('shippingZone')->withCount('areas')->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))->when($this->filterZone, fn($q) => $q->where('shipping_zone_id', $this->filterZone))->orderBy('name')->paginate(10);
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::where('status', 'active')->orderBy('name')->get();
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
            $this->dispatch('notify', title: $isEditing ? 'County Updated' : 'County Added', variant: 'success', message: $isEditing ? 'County updated.' : 'County added.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save county.', [
                'exception' => $e->getMessage(),
                'county_id' => $this->form->county?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Save Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
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
            $county = County::findOrFail($this->deletingId);

            if ($county->areas()->exists()) {
                $this->dispatch('notify', title: 'Cannot Delete', variant: 'warning', message: 'Cannot delete — this county has areas attached. Remove them first.');
                Flux::modal('delete-confirmation')->close();
                return;
            }

            $county->delete();
            $this->deletingId = null;
            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', title: 'County Deleted', variant: 'danger', message: 'County deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete county.', [
                'exception' => $e->getMessage(),
                'county_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Could not delete this county. It may have dependent records.');
        }
    }
}; ?>

<x-admin.logistics.layout heading="Counties"
    subheading="Kenya's 47 counties, each assigned to a shipping zone. The zone determines which rate bracket applies for deliveries to that county.">

    <x-slot:actions>
        <flux:button variant="primary" icon="plus-circle" wire:click="openCreate" class="cursor-pointer">
            Add County
        </flux:button>
    </x-slot:actions>

    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        {{-- Filters --}}
        <div class="flex flex-col md:flex-row gap-4 px-5 py-3 border-b dark:border-zinc-600">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or code..."
                icon="magnifying-glass" clearable class="max-w-md" />

            <div class="flex items-center gap-5 ms-auto">
                <flux:select wire:model.live="filterZone" placeholder="All Zones" clearable class="md:w-56">
                    @foreach ($this->zones as $zone)
                        <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <flux:table :paginate="$this->counties">
            <flux:table.columns>
                <flux:table.column class="ps-4!">County</flux:table.column>
                <flux:table.column>Code</flux:table.column>
                <flux:table.column>Shipping Zone</flux:table.column>
                <flux:table.column>Areas</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->counties as $county)
                    <flux:table.row :key="$county->id">
                        <flux:table.cell class="ps-4!">
                            <flux:heading size="sm">{{ $county->name }}</flux:heading>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($county->code)
                                <code
                                    class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">{{ $county->code }}</code>
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge color="blue" variant="flat" size="sm">
                                {{ $county->shippingZone->name }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>{{ $county->areas_count }}</flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer" wire:click="edit({{ $county->id }})" tooltip="Edit county" />
                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $county->id }})" tooltip="Delete county" />
                        </flux:table.cell>
                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.building-office-2 class="w-10 h-10 opacity-40" />
                                <div>
                                    <flux:heading size="sm">No counties found</flux:heading>
                                    <flux:subheading class="mt-0.5">
                                        @if ($this->search || $this->filterZone)
                                            No results match your current filters.
                                        @else
                                            Add counties and assign them to a shipping zone.
                                        @endif
                                    </flux:subheading>
                                </div>
                                @if ($this->search || $this->filterZone)
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="$set('search', ''); $set('filterZone', '')">
                                        Clear filters
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Create / Edit Modal --}}
    <flux:modal name="county-modal" class="md:w-md space-y-6">
        <flux:heading size="lg">{{ $form->county ? 'Edit County' : 'Add New County' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="form.name" label="County Name" placeholder="e.g. Nairobi" />
            <flux:input wire:model="form.code" label="Code (Optional)" placeholder="e.g. NBI"
                description="Short unique identifier, typically 2-5 characters." />

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

    {{-- Delete Confirmation --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">Delete County?</flux:heading>
        <flux:subheading>All areas within this county must be removed before it can be deleted.</flux:subheading>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Delete</flux:button>
        </div>
    </flux:modal>

</x-admin.logistics.layout>
