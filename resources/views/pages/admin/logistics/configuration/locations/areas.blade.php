<?php

use App\Models\Area;
use App\Models\County;
use App\Models\ShippingZone;
use App\Livewire\Forms\Admin\AreaForm;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Areas & Towns')] class extends Component {
    use WithPagination;

    public AreaForm $form;
    public ?int $deletingId = null;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterCounty = '';

    #[Url(history: true)]
    public string $filterZone = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedFilterCounty(): void
    {
        $this->resetPage();
    }
    public function updatedFilterZone(): void
    {
        $this->resetPage();
    }

    public function updatedFormCountyId(): void
    {
        $this->form->shipping_zone_id = '';
    }

    #[Computed]
    public function areas()
    {
        return Area::with(['county', 'county.shippingZone', 'shippingZone'])
            ->when($this->search, fn($q) => $q->where(fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhereHas('county', fn($c) => $c->where('name', 'like', "%{$this->search}%"))))
            ->when($this->filterCounty, fn($q) => $q->where('county_id', $this->filterCounty))
            ->when($this->filterZone, fn($q) => $q->where('shipping_zone_id', $this->filterZone))
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
        return ShippingZone::where('status', 'active')->orderBy('name')->get();
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
            $this->dispatch('notify', title: $isEditing ? 'Area Updated' : 'Area Added', variant: 'success', message: $isEditing ? 'Area updated.' : 'Area added.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save area.', [
                'exception' => $e->getMessage(),
                'area_id' => $this->form->area?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Save Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
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
            $this->dispatch('notify', title: 'Area Deleted', variant: 'danger', message: 'Area deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete area.', [
                'exception' => $e->getMessage(),
                'area_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Could not delete this area. It may have dependent records.');
        }
    }
}; ?>

<x-admin.logistics.layout heading="Areas & Towns"
    subheading="Towns, suburbs, and estates within each county. An area can optionally override its county's shipping zone for more granular pricing.">

    <x-slot:actions>
        <flux:button variant="primary" icon="plus" wire:click="openCreate" class="cursor-pointer">
            Add Area
        </flux:button>
    </x-slot:actions>

    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        {{-- Filters --}}
        <div class="flex flex-col md:flex-row gap-4 border-b dark:border-zinc-600 px-5 py-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search area or county name..."
                icon="magnifying-glass" clearable class="max-w-md" />

            <div class="flex items-center gap-4 ms-auto">
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" size="sm" icon="funnel" icon-variant="outline" icon-trailing="chevron-down">
                        Filters
                        @php $activeFilters = collect([$filterCounty, $filterZone])->filter()->count(); @endphp
                        @if ($activeFilters > 0)
                            <flux:badge size="sm" class="ms-1">{{ $activeFilters }}</flux:badge>
                        @endif
                    </flux:button>

                    <flux:menu class="min-w-64">
                        <div class="flex items-center justify-between px-3 py-2 border-b dark:border-zinc-700">
                            <flux:subheading>Filter Options</flux:subheading>
                            <flux:button variant="ghost" size="xs"
                                wire:click="$set('filterCounty', ''); $set('filterZone', '')"
                                class="cursor-pointer">Reset</flux:button>
                        </div>
                        <flux:separator />
                        <div class="p-3 space-y-3">
                            <flux:field>
                                <flux:label>County</flux:label>
                                <flux:select wire:model.live="filterCounty" placeholder="All Counties" clearable>
                                    @foreach ($this->counties as $county)
                                        <flux:select.option value="{{ $county->id }}">{{ $county->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                            <flux:field>
                                <flux:label>Zone Override</flux:label>
                                <flux:select wire:model.live="filterZone" placeholder="Zone Override" clearable>
                                    @foreach ($this->zones as $zone)
                                        <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                        </div>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
        <flux:table :paginate="$this->areas">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Area Name</flux:table.column>
                <flux:table.column>County</flux:table.column>
                <flux:table.column>Shipping Zone</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->areas as $area)
                    <flux:table.row :key="$area->id">
                        <flux:table.cell class="ps-4!">
                            <flux:heading size="sm">{{ $area->name }}</flux:heading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>{{ $area->county->name }}</flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($area->shippingZone)
                                <div class="flex items-center gap-1.5">
                                    <flux:badge color="orange" variant="flat" size="sm">
                                        {{ $area->shippingZone->name }}
                                    </flux:badge>
                                    <flux:subheading>override</flux:subheading>
                                </div>
                            @else
                                <flux:subheading>
                                    Default ({{ $area->county->shippingZone->name ?? 'from county' }})
                                </flux:subheading>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer" wire:click="edit({{ $area->id }})" tooltip="Edit area" />
                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $area->id }})" tooltip="Delete area" />
                        </flux:table.cell>
                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.map-pin class="w-10 h-10 opacity-40" />
                                <div>
                                    <flux:heading size="sm">No areas found</flux:heading>
                                    <flux:subheading class="mt-0.5">
                                        @if ($this->search || $this->filterCounty || $this->filterZone)
                                            No results match your current filters.
                                        @else
                                            Add towns and suburbs to support accurate address selection at checkout.
                                        @endif
                                    </flux:subheading>
                                </div>
                                @if ($this->search || $this->filterCounty || $this->filterZone)
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="$set('search', ''); $set('filterCounty', ''); $set('filterZone', '')"
                                        class="cursor-pointer">
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
    <flux:modal name="area-modal" class="md:w-md space-y-6">
        <flux:heading size="lg">{{ $form->area ? 'Edit Area' : 'Add New Area' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="form.name" label="Area Name" placeholder="e.g. Westlands" />

            <flux:select wire:model.live="form.county_id" label="Parent County" searchable
                placeholder="Select a county...">
                @foreach ($this->counties as $county)
                    <flux:select.option value="{{ $county->id }}">{{ $county->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="form.shipping_zone_id" label="Zone Override (Optional)" clearable
                placeholder="Use county's default zone"
                description="Only set this if this area ships at a different rate than its county.">
                @foreach ($this->zones as $zone)
                    <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">
                    Save Area
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">Delete Area?</flux:heading>
        <flux:subheading>This area will be removed. Any addresses linked to it will have their area cleared.
        </flux:subheading>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">
                Delete
            </flux:button>
        </div>
    </flux:modal>

</x-admin.logistics.layout>
