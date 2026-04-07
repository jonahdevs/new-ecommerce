<?php

use App\Enums\ShippingZoneStatus;
use App\Models\ShippingZone;
use App\Livewire\Forms\Admin\ShippingZoneForm;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Shipping Zones')] class extends Component {
    use WithPagination;

    public ShippingZoneForm $form;
    public ?int $deletingId = null;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterStatus = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::query()->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))->withCount('counties')->orderBy('name')->paginate(10);
    }

    #[Computed]
    public function statuses(): array
    {
        return ShippingZoneStatus::cases();
    }

    public function openCreate(): void
    {
        $this->form->reset();
        Flux::modal('zone-modal')->show();
    }

    public function save(): void
    {
        try {
            $isEditing = (bool) $this->form->zone;
            $isEditing ? $this->form->update() : $this->form->store();

            $this->form->reset();
            Flux::modal('zone-modal')->close();
            $this->dispatch('notify', title: $isEditing ? 'Zone Updated' : 'Zone Added', variant: 'success', message: $isEditing ? 'Zone updated.' : 'Zone added.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save shipping zone.', [
                'exception' => $e->getMessage(),
                'zone_id' => $this->form->zone?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Save Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(ShippingZone $zone): void
    {
        $this->form->setZone($zone);
        Flux::modal('zone-modal')->show();
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
            $zone = ShippingZone::findOrFail($this->deletingId);

            if ($zone->counties()->exists()) {
                $this->dispatch('notify', title: 'Cannot Delete', variant: 'warning', message: 'Cannot delete — this zone has counties assigned to it.');
                Flux::modal('delete-confirmation')->close();
                return;
            }

            $zone->delete();
            $this->deletingId = null;
            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', title: 'Zone Deleted', variant: 'danger', message: 'Zone deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete shipping zone.', [
                'exception' => $e->getMessage(),
                'zone_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Could not delete this zone. It may have dependent records.');
        }
    }
}; ?>

<x-admin.logistics.layout heading="Zones"
    subheading="Define geographic pricing regions. Counties are assigned to zones to determine which rate bracket applies at checkout.">

    <x-slot:actions>
        <flux:button variant="primary" icon="plus-circle" wire:click="openCreate" class="cursor-pointer">
            Add Zone
        </flux:button>
    </x-slot:actions>

    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        {{-- Filters --}}
        <div class="flex flex-col md:flex-row gap-4 px-5 py-3 border-b dark:border-zinc-600">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or code..."
                icon="magnifying-glass" clearable class="max-w-md" />

            <div class="ms-auto flex items-center gap-5">
                <flux:select wire:model.live="filterStatus" placeholder="All Statuses" clearable class="md:w-44">
                    @foreach ($this->statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
        <flux:table :paginate="$this->zones">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Zone Name</flux:table.column>
                <flux:table.column>Code</flux:table.column>
                <flux:table.column>Counties</flux:table.column>
                <flux:table.column>Description</flux:table.column>
                <flux:table.column>Delivery Available</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->zones as $zone)
                    <flux:table.row :key="$zone->id">
                        <flux:table.cell class="ps-4!">
                            <flux:heading size="sm">{{ $zone->name }}</flux:heading>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($zone->code)
                                <code
                                    class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">{{ $zone->code }}</code>
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>{{ $zone->counties_count }}</flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading class="max-w-xs truncate block">
                                {{ $zone->description ?? '—' }}
                            </flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($zone->is_delivery_available)
                                <flux:badge color="green" variant="flat" size="sm">Yes</flux:badge>
                            @else
                                <flux:badge color="zinc" variant="flat" size="sm">No</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @php $status = $zone->status instanceof \App\Enums\ShippingZoneStatus ? $zone->status : \App\Enums\ShippingZoneStatus::from($zone->status); @endphp
                            <flux:badge :color="$status->color()" variant="flat" size="sm">
                                {{ $status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer" wire:click="edit({{ $zone->id }})" tooltip="Edit zone" />
                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $zone->id }})" tooltip="Delete zone" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.map class="w-10 h-10 opacity-40" />
                                <div>
                                    <flux:heading size="sm">No zones found</flux:heading>
                                    <flux:subheading class="mt-0.5">
                                        @if ($this->search || $this->filterStatus)
                                            No results match your current filters.
                                        @else
                                            Get started by adding your first shipping zone.
                                        @endif
                                    </flux:subheading>
                                </div>
                                @if ($this->search || $this->filterStatus)
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="$set('search', ''); $set('filterStatus', '')">
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
    <flux:modal name="zone-modal" class="md:w-md space-y-6">
        <flux:heading size="lg">{{ $form->zone ? 'Edit Zone' : 'Add New Zone' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="form.name" label="Zone Name" placeholder="e.g. Within Nairobi" />
            <flux:input wire:model="form.code" label="Code (Optional)" placeholder="e.g. nairobi_cbd"
                description="Short unique identifier. Lowercase, no spaces." />

            <flux:select wire:model="form.status" label="Status">
                @foreach ($this->statuses as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="form.description" label="Description (Optional)"
                placeholder="What areas does this zone cover?" rows="2" />

            <flux:field variant="inline">
                <flux:checkbox wire:model="form.is_delivery_available" />
                <flux:label>Delivery Available</flux:label>
                <flux:description>
                    Enable for zones where customers can place a direct order.
                    Disable for zones that require a delivery quote.
                </flux:description>
            </flux:field>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Zone</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">Delete Zone?</flux:heading>
        <flux:subheading>Counties assigned to this zone must be reassigned before it can be deleted.</flux:subheading>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Delete</flux:button>
        </div>
    </flux:modal>

</x-admin.logistics.layout>
