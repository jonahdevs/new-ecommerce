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
            ->paginate(15);
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
            $this->dispatch('notify', variant: 'success', message: $isEditing ? 'Area updated.' : 'Area added.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save area.', [
                'exception' => $e->getMessage(),
                'area_id' => $this->form->area?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
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
            $this->dispatch('notify', variant: 'danger', message: 'Area deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete area.', [
                'exception' => $e->getMessage(),
                'area_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Could not delete this area. It may have dependent records.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Logistics</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Areas & Towns</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl" class="mb-2">Areas & Towns</flux:heading>
            <flux:subheading>Towns, suburbs, and estates within each county. An area can optionally override its
                county's shipping zone for more granular pricing.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate" class="cursor-pointer">
            Add Area
        </flux:button>
    </div>


    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50">
        {{-- Filters --}}
        <div class="flex flex-col md:flex-row gap-4 border-b px-5 py-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search area or county name..."
                icon="magnifying-glass" clearable class="max-w-md" />

            <div class="flex items-center gap-4 ms-auto">
                <flux:select wire:model.live="filterCounty" placeholder="All Counties" clearable class="md:w-56">
                    @foreach ($this->counties as $county)
                        <flux:select.option value="{{ $county->id }}">{{ $county->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterZone" placeholder="Zone Override" clearable class="md:w-48">
                    @foreach ($this->zones as $zone)
                        <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                    @endforeach
                </flux:select>
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
                        <flux:table.cell class="font-semibold ps-4!">
                            {{ $area->name }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $area->county->name }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($area->shippingZone)
                                <div class="flex items-center gap-1.5">
                                    <flux:badge color="orange" variant="flat" size="sm">
                                        {{ $area->shippingZone->name }}
                                    </flux:badge>
                                    <span class="text-xs text-zinc-400">override</span>
                                </div>
                            @else
                                <span class="text-xs text-zinc-400">
                                    Default ({{ $area->county->shippingZone->name ?? 'from county' }})
                                </span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer text-sheffield-blue!" wire:click="edit({{ $area->id }})" />
                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $area->id }})" />
                        </flux:table.cell>
                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.map-pin class="w-10 h-10 opacity-40" />
                                <div>
                                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">No areas found</p>
                                    <p class="text-xs mt-0.5">
                                        @if ($this->search || $this->filterCounty || $this->filterZone)
                                            No results match your current filters.
                                        @else
                                            Add towns and suburbs to support accurate address selection at checkout.
                                        @endif
                                    </p>
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
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
