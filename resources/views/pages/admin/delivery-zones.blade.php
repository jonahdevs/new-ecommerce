<?php

use App\Models\DeliveryZone;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Delivery zones — Admin')] class extends Component
{
    // ==================================================
    // SEARCH & FILTER
    // ==================================================
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    // ==================================================
    // BULK SELECTION
    // ==================================================
    /** @var array<int, string> */
    public array $selected = [];

    public bool $selectAll = false;

    // ==================================================
    // ZONE FORM
    // ==================================================
    public bool $showZoneModal = false;

    public ?int $editingZoneId = null;

    public string $name = '';

    public string $county = 'Nairobi';

    public bool $is_active = true;

    public int $sort_order = 0;

    public int $priority = 0;

    /** @var array<int, array{lat: float, lng: float}> */
    public array $polygon = [];

    #[Computed]
    public function zones(): Collection
    {
        return DeliveryZone::query()
            ->withCount(['promotions', 'carrierZones'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('county', 'like', '%'.$this->search.'%');
            }))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->clearSelection();
    }

    public function updatedFilterStatus(): void
    {
        $this->clearSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value
            ? $this->zones->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    // ==================================================
    // ZONE CRUD
    // ==================================================
    public function zoneRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'county' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'priority' => ['integer', 'min:0'],
            'polygon' => ['required', 'array', 'min:3'],
            'polygon.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'polygon.*.lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function openCreateZone(): void
    {
        $this->resetValidation();
        $this->reset(['editingZoneId', 'name', 'county', 'is_active', 'sort_order', 'priority', 'polygon']);
        $this->county = 'Nairobi';
        $this->is_active = true;
        $this->showZoneModal = true;
    }

    public function openEditZone(int $id): void
    {
        $this->resetValidation();
        $zone = DeliveryZone::findOrFail($id);
        $this->editingZoneId = $zone->id;
        $this->name = $zone->name;
        $this->county = $zone->county;
        $this->is_active = $zone->is_active;
        $this->sort_order = $zone->sort_order;
        $this->priority = $zone->priority;
        $this->polygon = $zone->polygon ?? [];
        $this->showZoneModal = true;
    }

    public function saveZone(): void
    {
        $data = $this->validate($this->zoneRules());

        $payload = [
            'name' => $data['name'],
            'county' => $data['county'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
            'priority' => $data['priority'],
            'polygon' => $data['polygon'],
        ];

        if ($this->editingZoneId) {
            DeliveryZone::findOrFail($this->editingZoneId)->update($payload);
            Flux::toast(heading: 'Zone updated', text: $payload['name'].' has been saved.', variant: 'success');
        } else {
            DeliveryZone::create($payload);
            Flux::toast(heading: 'Zone created', text: $payload['name'].' is now a delivery area.', variant: 'success');
        }

        $this->showZoneModal = false;
        unset($this->zones);
    }

    public function toggleZoneActive(int $id): void
    {
        $zone = DeliveryZone::findOrFail($id);
        $zone->update(['is_active' => ! $zone->is_active]);
        unset($this->zones);
    }

    public function deleteZone(int $id): void
    {
        DeliveryZone::findOrFail($id)->delete();
        unset($this->zones);
        Flux::toast(heading: 'Zone removed', text: 'The delivery area has been deleted.', variant: 'warning');
    }

    // ==================================================
    // BULK ACTIONS
    // ==================================================
    public function bulkActivate(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = DeliveryZone::whereIn('id', $this->selected)->update(['is_active' => true]);
        $this->afterBulk();
        Flux::toast(heading: 'Zones activated', text: $count.' zone(s) set to active.', variant: 'success');
    }

    public function bulkDeactivate(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = DeliveryZone::whereIn('id', $this->selected)->update(['is_active' => false]);
        $this->afterBulk();
        Flux::toast(heading: 'Zones deactivated', text: $count.' zone(s) turned off.', variant: 'success');
    }

    public function bulkDelete(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = DeliveryZone::whereIn('id', $this->selected)->delete();
        $this->afterBulk();
        Flux::toast(heading: 'Zones deleted', text: $count.' zone(s) have been removed.', variant: 'warning');
    }

    private function afterBulk(): void
    {
        $this->clearSelection();
        unset($this->zones);
    }
}; ?>

@include('partials.admin.zone-map-scripts')

<div x-data="zoneMap()" x-effect="$wire.showZoneModal ? open() : close()">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Delivery zones</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Delivery zones</flux:heading>
            <flux:text class="mt-1">Circular areas you deliver to. A customer's map pin must fall inside an active zone.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreateZone">Add zone</flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name or county…"
                icon="magnifying-glass" clearable class="max-w-xs" />
            <flux:select wire:model.live="filterStatus" class="w-36">
                <flux:select.option value="">All statuses</flux:select.option>
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="inactive">Inactive</flux:select.option>
            </flux:select>
        </div>

        {{-- Bulk action bar --}}
        @if (count($selected) > 0)
            <div class="flex flex-wrap items-center gap-3 border-b border-zinc-200 bg-brand-50 px-6 py-2.5 dark:border-zinc-700 dark:bg-brand-500/10">
                <flux:text class="font-medium">{{ count($selected) }} selected</flux:text>
                <flux:button size="sm" variant="ghost" wire:click="bulkActivate">Activate</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="bulkDeactivate">Deactivate</flux:button>
                <flux:button size="sm" variant="ghost" icon="trash-2"
                    wire:click="bulkDelete"
                    wire:confirm="Delete {{ count($selected) }} zone(s)? This cannot be undone."
                    class="text-red-500! hover:text-red-600!">Delete</flux:button>
                <flux:spacer />
                <flux:button size="sm" variant="ghost" wire:click="clearSelection">Clear</flux:button>
            </div>
        @endif

        <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column class="w-10">
                    <flux:checkbox wire:model.live="selectAll" />
                </flux:table.column>
                <flux:table.column>Zone</flux:table.column>
                <flux:table.column>County</flux:table.column>
                <flux:table.column>Points</flux:table.column>
                <flux:table.column>Carriers</flux:table.column>
                <flux:table.column>Priority</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->zones as $zone)
                    <flux:table.row :key="$zone->id" wire:key="zone-{{ $zone->id }}">
                        <flux:table.cell>
                            <flux:checkbox wire:model.live="selected" value="{{ $zone->id }}" />
                        </flux:table.cell>
                        <flux:table.cell variant="strong">{{ $zone->name }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $zone->county }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums text-zinc-500">{{ count($zone->polygon ?? []) }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums text-zinc-500">{{ $zone->carrier_zones_count }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums text-zinc-500">{{ $zone->priority }}</flux:table.cell>
                        <flux:table.cell>
                            <button type="button" wire:click="toggleZoneActive({{ $zone->id }})">
                                <flux:badge :color="$zone->is_active ? 'green' : 'zinc'" size="sm" inset="top bottom">
                                    {{ $zone->is_active ? 'Active' : 'Off' }}
                                </flux:badge>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:tooltip content="Activity log">
                                    <flux:button size="xs" variant="ghost" icon="clock"
                                        :href="route('admin.activity.item', ['delivery_zone', $zone->id])"
                                        wire:navigate />
                                </flux:tooltip>
                                <flux:button size="xs" variant="ghost" icon="pencil-square" tooltip="Edit"
                                    wire:click="openEditZone({{ $zone->id }})" />
                                <flux:button size="xs" variant="ghost" icon="trash-2" tooltip="Delete"
                                    wire:click="deleteZone({{ $zone->id }})"
                                    wire:confirm="Delete {{ $zone->name }}? Addresses in this zone will lose their assignment."
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="py-12 text-center text-zinc-400">
                            @if ($search || $filterStatus)
                                No zones match your filters.
                            @else
                                No delivery zones yet. Add your first area.
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- ================================================== --}}
    {{-- ZONE MODAL --}}
    {{-- ================================================== --}}
    <flux:modal wire:model.self="showZoneModal" class="md:w-[900px]" :dismissible="false">
        <div>
            <flux:heading>{{ $editingZoneId ? 'Edit zone' : 'New delivery zone' }}</flux:heading>
            <flux:subheading x-show="currentStep === 1">Click the map to draw the zone boundary. Drag a point to adjust it, double-click to remove it.</flux:subheading>
            <flux:subheading x-show="currentStep === 2" x-cloak>Fill in the details for this delivery zone.</flux:subheading>
        </div>

        <form wire:submit="saveZone" class="mt-6">

            {{-- Step 1: Map --}}
            <div x-show="currentStep === 1" class="space-y-3">
                <div id="zone-map-container" class="h-72 w-full overflow-hidden rounded-md border border-zinc-200 bg-zinc-100 sm:h-96 md:h-[480px] dark:border-zinc-700"></div>

                <div class="flex items-center justify-between">
                    <flux:text size="sm" class="text-zinc-500">
                        <span x-text="$wire.polygon.length"></span> point(s) — click map to add, double-click a point to remove
                    </flux:text>
                    <div class="flex items-center gap-2">
                        <flux:button size="xs" variant="ghost" x-on:click="undoLast" x-bind:disabled="!$wire.polygon.length">Undo</flux:button>
                        <flux:button size="xs" variant="ghost" x-on:click="clearAll" x-bind:disabled="!$wire.polygon.length">Clear</flux:button>
                    </div>
                </div>

                <div x-show="polygonError">
                    <flux:error>Draw at least 3 points on the map to define the zone boundary.</flux:error>
                </div>
                @error('polygon') <flux:error>Draw at least 3 points on the map to define the zone boundary.</flux:error> @enderror

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button type="button" variant="ghost" x-on:click="$flux.modals().close()">Cancel</flux:button>
                    <flux:button type="button" variant="primary" icon-trailing="chevron-right" x-on:click="goToStep2">Next</flux:button>
                </div>
            </div>

            {{-- Step 2: Details --}}
            <div x-show="currentStep === 2" x-cloak class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input wire:model="name" placeholder="Nairobi & Surroundings" />
                        <flux:error name="name" />
                    </flux:field>
                    <flux:field>
                        <flux:label>County</flux:label>
                        <flux:input wire:model="county" placeholder="Nairobi" />
                        <flux:error name="county" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Priority</flux:label>
                        <flux:input type="number" wire:model="priority" min="0" />
                        <flux:description>Higher wins when zones overlap.</flux:description>
                        <flux:error name="priority" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Sort order</flux:label>
                        <flux:input type="number" wire:model="sort_order" min="0" />
                        <flux:error name="sort_order" />
                    </flux:field>
                </div>

                <flux:checkbox wire:model="is_active" label="Active (available at checkout)" />

                <div class="flex justify-between gap-3 pt-2">
                    <flux:button type="button" variant="ghost" icon="chevron-left" x-on:click="goToStep1">Back</flux:button>
                    <div class="flex gap-3">
                        <flux:button type="button" variant="ghost" x-on:click="$flux.modals().close()">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">{{ $editingZoneId ? 'Save zone' : 'Create zone' }}</flux:button>
                    </div>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
