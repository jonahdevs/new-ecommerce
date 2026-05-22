<?php

use App\Enums\ShippingZoneStatus;
use App\Livewire\Forms\Admin\ShippingZoneForm;
use App\Models\ShippingZone;
use App\Models\SubCounty;
use App\Models\Town;
use Flux\Flux;
use Livewire\Attributes\{Computed, Title, Url};
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Shipping Zones')] class extends Component {
    use WithPagination;

    public ShippingZoneForm $form;
    public ?int $deletingId = null;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterStatus = '';

    private array $palette = [
        '#3B82F6', // blue
        '#10B981', // emerald
        '#F59E0B', // amber
        '#EF4444', // red
        '#8B5CF6', // violet
        '#EC4899', // pink
        '#06B6D4', // cyan
        '#F97316', // orange
    ];

    // ─── Pagination resets ────────────────────────────────────────────────

    public function updatedSearch(): void { $this->resetPage(); }

    public function updatedFilterStatus(): void { $this->resetPage(); }

    // ─── Computed data ────────────────────────────────────────────────────

    #[Computed]
    public function zones()
    {
        return ShippingZone::query()
            ->when($this->search, fn ($q) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%")
            ))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->withCount(['counties', 'subCounties as sub_county_overrides_count'])
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function statuses(): array
    {
        return ShippingZoneStatus::cases();
    }

    #[Computed]
    public function tierPresets(): array
    {
        return ShippingZoneForm::TIER_PRESETS;
    }

    /**
     * Stable colour per zone for the map and legend.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function zoneColors(): array
    {
        $colors = [];

        foreach (ShippingZone::orderBy('id')->pluck('id') as $i => $id) {
            $colors[$id] = $this->palette[$i % count($this->palette)];
        }

        return $colors;
    }

    /**
     * Sub-county → zone lookup keyed by geoBoundaries shape_id, used to colour the map.
     *
     * @return array<string, array{sub_county_id: int, sub_county: string, county: string, zone_id: int|null, zone: string, color: string, centroid: array{lat: float, lng: float}}>
     */
    #[Computed]
    public function mapData(): array
    {
        $zoneColors = $this->zoneColors;
        $zones      = ShippingZone::all()->keyBy('id');
        $data       = [];

        SubCounty::with('county')
            ->whereNotNull('shape_id')
            ->get()
            ->each(function ($sc) use (&$data, $zones, $zoneColors) {
                $effectiveZoneId = $sc->shipping_zone_id ?? $sc->county?->shipping_zone_id;
                $zone            = $effectiveZoneId ? $zones->get($effectiveZoneId) : null;

                $data[$sc->shape_id] = [
                    'sub_county_id' => $sc->id,
                    'sub_county'    => $sc->name,
                    'county'        => $sc->county?->name ?? '',
                    'zone_id'       => $zone?->id,
                    'zone'          => $zone?->name ?? 'No Zone',
                    'color'         => $zone ? ($zoneColors[$zone->id] ?? '#9CA3AF') : '#9CA3AF',
                    'centroid'      => [
                        'lat' => (float) ($sc->lat_center ?? 0),
                        'lng' => (float) ($sc->lng_center ?? 0),
                    ],
                ];
            });

        return $data;
    }

    /**
     * Town (ADM3) → zone lookup keyed by geoBoundaries shape_id.
     * Built the same shape as mapData() so the front-end can swap datasets seamlessly.
     *
     * @return array<string, array{town_id: int, town: string, sub_county: string, county: string, zone_id: int|null, zone: string, color: string, centroid: array{lat: float, lng: float}}>
     */
    #[Computed]
    public function townMapData(): array
    {
        $zoneColors = $this->zoneColors;
        $zones      = ShippingZone::all()->keyBy('id');
        $data       = [];

        Town::with(['subCounty', 'county'])
            ->whereNotNull('shape_id')
            ->get()
            ->each(function ($town) use (&$data, $zones, $zoneColors) {
                $effectiveZoneId = $town->shipping_zone_id
                    ?? $town->subCounty?->shipping_zone_id
                    ?? $town->county?->shipping_zone_id;

                $zone = $effectiveZoneId ? $zones->get($effectiveZoneId) : null;

                $data[$town->shape_id] = [
                    'town_id'    => $town->id,
                    'town'       => $town->name,
                    'sub_county' => $town->subCounty?->name ?? '',
                    'county'     => $town->county?->name ?? '',
                    'zone_id'    => $zone?->id,
                    'zone'       => $zone?->name ?? 'No Zone',
                    'color'      => $zone ? ($zoneColors[$zone->id] ?? '#9CA3AF') : '#9CA3AF',
                    'centroid'   => [
                        'lat' => (float) ($town->lat_center ?? 0),
                        'lng' => (float) ($town->lng_center ?? 0),
                    ],
                ];
            });

        return $data;
    }

    /**
     * Compact zone records used by both the map legend and the active-zone picker.
     *
     * @return array<int, array{id: int, name: string, color: string, available: bool}>
     */
    #[Computed]
    public function zonesForMap(): array
    {
        $colors = $this->zoneColors;

        return ShippingZone::orderBy('id')->get()->map(fn ($z) => [
            'id'        => $z->id,
            'name'      => $z->name,
            'color'     => $colors[$z->id] ?? '#9CA3AF',
            'available' => (bool) $z->is_delivery_available,
        ])->values()->toArray();
    }

    // ─── Actions ──────────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->form->reset();
        Flux::modal('zone-modal')->show();
    }

    public function applyTier(string $tier): void
    {
        $this->form->applyTier($tier);
    }

    public function save(): void
    {
        try {
            $isEditing = (bool) $this->form->zone;
            $isEditing ? $this->form->update() : $this->form->store();

            $this->form->reset();
            Flux::modal('zone-modal')->close();
            unset($this->zones, $this->mapData, $this->zonesForMap, $this->zoneColors);

            $this->dispatch('notify',
                title:   $isEditing ? 'Zone Updated' : 'Zone Added',
                variant: 'success',
                message: $isEditing ? 'Zone updated.' : 'Zone added.',
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save shipping zone.', [
                'exception' => $e->getMessage(),
                'zone_id'   => $this->form->zone?->id,
                'user_id'   => auth()->id(),
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
        if (! $this->deletingId) {
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
            unset($this->zones, $this->mapData, $this->zonesForMap, $this->zoneColors);
            $this->dispatch('notify', title: 'Zone Deleted', variant: 'danger', message: 'Zone deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete shipping zone.', [
                'exception' => $e->getMessage(),
                'zone_id'   => $this->deletingId,
                'user_id'   => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Could not delete this zone. It may have dependent records.');
        }
    }

    // ─── Map mutations (called from JS) ───────────────────────────────────

    public function assignZoneToSubCounties(int $zoneId, array $subCountyIds): void
    {
        $zone = ShippingZone::findOrFail($zoneId);

        SubCounty::whereIn('id', array_map('intval', $subCountyIds))
            ->update(['shipping_zone_id' => $zone->id]);

        unset($this->mapData);

        $this->dispatch('notify',
            title:   'Zone Assigned',
            variant: 'success',
            message: count($subCountyIds).' sub-counties assigned to '.$zone->name.'.',
        );
    }

    public function clearZoneForSubCounties(array $subCountyIds): void
    {
        SubCounty::whereIn('id', array_map('intval', $subCountyIds))
            ->update(['shipping_zone_id' => null]);

        unset($this->mapData);

        $this->dispatch('notify',
            title:   'Overrides Cleared',
            variant: 'success',
            message: count($subCountyIds).' sub-counties will now inherit from their county zone.',
        );
    }

    public function assignZoneToTowns(int $zoneId, array $townIds): void
    {
        $zone = ShippingZone::findOrFail($zoneId);

        Town::whereIn('id', array_map('intval', $townIds))
            ->update(['shipping_zone_id' => $zone->id]);

        unset($this->townMapData);

        $this->dispatch('notify',
            title:   'Wards Updated',
            variant: 'success',
            message: count($townIds).' wards assigned to '.$zone->name.'.',
        );
    }

    public function clearZoneForTowns(array $townIds): void
    {
        Town::whereIn('id', array_map('intval', $townIds))
            ->update(['shipping_zone_id' => null]);

        unset($this->townMapData);

        $this->dispatch('notify',
            title:   'Ward Overrides Cleared',
            variant: 'success',
            message: count($townIds).' wards will now inherit from sub-county / county.',
        );
    }
}; ?>

<x-admin.logistics.layout
    heading="Zones"
    subheading="Define service tiers and the geography they cover. Switch views to manage the same zones visually or as a list.">

    <x-slot:actions>
        {{-- View toggle (Alpine-driven, persisted in localStorage) --}}
        <div x-data="{ view: localStorage.getItem('zones_view') ?? 'map' }"
            x-init="$watch('view', v => localStorage.setItem('zones_view', v)); $dispatch('zones-view-init', view)"
            @zones-view-change.window="view = $event.detail"
            class="flex items-center rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden text-sm font-medium">
            <button type="button" @click="view = 'map'; $dispatch('zones-view-change', 'map')"
                ::class="view === 'map' ? 'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800'"
                class="flex items-center gap-1.5 px-3 py-1.5 transition-colors cursor-pointer">
                <flux:icon.map class="size-3.5" />
                Map
            </button>
            <button type="button" @click="view = 'table'; $dispatch('zones-view-change', 'table')"
                ::class="view === 'table' ? 'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800'"
                class="flex items-center gap-1.5 px-3 py-1.5 transition-colors cursor-pointer border-l border-zinc-200 dark:border-zinc-700">
                <flux:icon.list-bullet class="size-3.5" />
                Table
            </button>
        </div>

        <flux:button variant="primary" icon="plus-circle" wire:click="openCreate" class="cursor-pointer">
            Add Zone
        </flux:button>
    </x-slot:actions>

    {{-- Outer Alpine wrapper for view toggling --}}
    <div x-data="{ view: localStorage.getItem('zones_view') ?? 'map' }"
        @zones-view-change.window="view = $event.detail">

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- MAP VIEW                                                         --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <div x-show="view === 'map'" x-cloak>

            {{-- Toolbar: active-zone picker + mode buttons --}}
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400 shrink-0">Active zone:</span>
                    @foreach ($this->zonesForMap as $zone)
                        <button type="button"
                            data-zone-id="{{ $zone['id'] }}"
                            data-zone-name="{{ $zone['name'] }}"
                            data-zone-color="{{ $zone['color'] }}"
                            onclick="selectZone(this)"
                            class="zone-btn flex items-center gap-1.5 px-3 py-1.5 rounded-full border-2 border-transparent text-sm font-medium transition-all cursor-pointer"
                            style="background: {{ $zone['color'] }}1a; color: {{ $zone['color'] }};">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $zone['color'] }}"></span>
                            {{ $zone['name'] }}
                        </button>
                    @endforeach
                </div>

                <div class="flex-1"></div>

                {{-- Granularity toggle (ADM2 / ADM3) --}}
                <div class="flex items-center rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden text-sm font-medium">
                    <button type="button" id="btn-level-adm2" onclick="setLevel('adm2')"
                        class="level-btn flex items-center gap-1.5 px-3 py-1.5 transition-colors cursor-pointer bg-zinc-800 text-white">
                        Sub-counties
                    </button>
                    <button type="button" id="btn-level-adm3" onclick="setLevel('adm3')"
                        class="level-btn flex items-center gap-1.5 px-3 py-1.5 transition-colors cursor-pointer border-l border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        Wards
                    </button>
                </div>

                <div class="flex items-center rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden text-sm font-medium">
                    <button type="button" id="btn-view" onclick="setMode('view')"
                        class="mode-btn flex items-center gap-1.5 px-3 py-1.5 transition-colors cursor-pointer bg-zinc-800 text-white">
                        <flux:icon.eye class="size-3.5" />
                        View
                    </button>
                    <button type="button" id="btn-draw" onclick="setMode('draw')"
                        class="mode-btn flex items-center gap-1.5 px-3 py-1.5 transition-colors cursor-pointer border-l border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <flux:icon.pencil-square class="size-3.5" />
                        Draw Zone
                    </button>
                    <button type="button" id="btn-clear" onclick="setMode('clear')"
                        class="mode-btn flex items-center gap-1.5 px-3 py-1.5 transition-colors cursor-pointer border-l border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <flux:icon.x-circle class="size-3.5" />
                        Clear Override
                    </button>
                </div>
            </div>

            {{-- Selection confirmation banner --}}
            <div id="selection-bar"
                class="hidden mb-4 px-4 py-3 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 rounded-lg flex items-center gap-3">
                <flux:icon.map class="size-4 text-indigo-600 dark:text-indigo-400 shrink-0" />
                <span id="selection-label" class="text-sm text-indigo-700 dark:text-indigo-300 flex-1"></span>
                <flux:button size="sm" variant="primary" onclick="confirmAssignment()" id="btn-confirm">
                    Assign Zone
                </flux:button>
                <flux:button size="sm" variant="ghost" onclick="cancelSelection()">
                    Cancel
                </flux:button>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-4 gap-4 items-start">
                <flux:card class="xl:col-span-3 p-0 overflow-hidden">
                    <div class="relative">
                        <div id="mode-indicator"
                            class="hidden absolute top-3 left-1/2 -translate-x-1/2 z-10 px-3 py-1.5 rounded-full text-xs font-semibold shadow-lg pointer-events-none"
                            style="background:#6366F1;color:#fff">
                            Draw a polygon on the map
                        </div>
                        <div id="zone-map" class="w-full" style="height:640px;">
                            <div class="flex items-center justify-center h-full text-zinc-400 text-sm">
                                Loading map…
                            </div>
                        </div>
                    </div>
                </flux:card>

                <div class="space-y-4">
                    <flux:card class="p-4">
                        <flux:heading size="sm" class="mb-3">Zones</flux:heading>
                        <div class="space-y-2.5">
                            @foreach ($this->zonesForMap as $zone)
                                <a href="{{ route('admin.logistics.configuration.zones.show', $zone['id']) }}"
                                    wire:navigate
                                    class="flex items-center gap-3 -mx-2 px-2 py-1 rounded hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                    <span class="w-3.5 h-3.5 rounded-sm shrink-0" style="background:{{ $zone['color'] }}"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium leading-tight text-zinc-800 dark:text-zinc-200">{{ $zone['name'] }}</p>
                                        <p class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-0.5">
                                            {{ $zone['available'] ? 'Delivery available' : 'Not yet available' }}
                                        </p>
                                    </div>
                                </a>
                            @endforeach
                            <div class="flex items-center gap-3 -mx-2 px-2 py-1">
                                <span class="w-3.5 h-3.5 rounded-sm shrink-0" style="background:#9CA3AF"></span>
                                <p class="text-sm text-zinc-400 dark:text-zinc-500">No Zone Assigned</p>
                            </div>
                        </div>
                    </flux:card>

                    <flux:card class="p-4 space-y-3">
                        <flux:heading size="sm">How to use</flux:heading>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed space-y-2">
                            <p><strong>View</strong> — hover any sub-county to see its zone, click a legend item to drill in.</p>
                            <p><strong>Draw Zone</strong> — pick an active zone above, draw a polygon. All sub-counties whose centres fall inside are assigned.</p>
                            <p><strong>Clear Override</strong> — reset sub-counties to inherit from their county zone.</p>
                        </div>
                    </flux:card>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- TABLE VIEW                                                       --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <div x-show="view === 'table'" x-cloak>
            <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
                <div class="flex flex-col md:flex-row gap-4 px-5 py-3 border-b dark:border-zinc-600">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or code…"
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
                        <flux:table.column class="ps-4!">Zone</flux:table.column>
                        <flux:table.column>Code</flux:table.column>
                        <flux:table.column>Counties</flux:table.column>
                        <flux:table.column>Sub-county overrides</flux:table.column>
                        <flux:table.column>Delivery</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->zones as $zone)
                            <flux:table.row :key="$zone->id">
                                <flux:table.cell class="ps-4!">
                                    <a href="{{ route('admin.logistics.configuration.zones.show', $zone) }}" wire:navigate
                                        class="font-medium text-zinc-800 dark:text-zinc-200 hover:underline">
                                        {{ $zone->name }}
                                    </a>
                                    @if ($zone->description)
                                        <p class="text-xs text-zinc-400 mt-0.5 max-w-md truncate">{{ $zone->description }}</p>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($zone->code)
                                        <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">{{ $zone->code }}</code>
                                    @else
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell><flux:subheading>{{ $zone->counties_count }}</flux:subheading></flux:table.cell>
                                <flux:table.cell><flux:subheading>{{ $zone->sub_county_overrides_count }}</flux:subheading></flux:table.cell>

                                <flux:table.cell>
                                    @if ($zone->is_delivery_available)
                                        <flux:badge color="green" variant="flat" size="sm">Yes</flux:badge>
                                    @else
                                        <flux:badge color="zinc" variant="flat" size="sm">No</flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @php $status = $zone->status instanceof \App\Enums\ShippingZoneStatus ? $zone->status : \App\Enums\ShippingZoneStatus::from($zone->status); @endphp
                                    <flux:badge :color="$status->color()" variant="flat" size="sm">{{ $status->label() }}</flux:badge>
                                </flux:table.cell>

                                <flux:table.cell align="end" class="pe-4!">
                                    <flux:button variant="ghost" size="sm" icon="arrow-top-right-on-square"
                                        :href="route('admin.logistics.configuration.zones.show', $zone)" wire:navigate
                                        class="cursor-pointer" tooltip="View" />
                                    <flux:button variant="ghost" size="sm" icon="pencil-square"
                                        wire:click="edit({{ $zone->id }})" class="cursor-pointer" tooltip="Edit" />
                                    <flux:button variant="ghost" size="sm" icon="trash" color="red"
                                        wire:click="confirmDelete({{ $zone->id }})" class="cursor-pointer text-red-500!"
                                        tooltip="Delete" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="py-12 text-center">
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
        </div>
    </div>

    {{-- ─── Create / Edit Modal ─────────────────────────────────────────── --}}
    <flux:modal name="zone-modal" class="md:w-lg space-y-6">
        <flux:heading size="lg">{{ $form->zone ? 'Edit Zone' : 'Add New Zone' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            @unless ($form->zone)
                <div class="space-y-2">
                    <flux:label>Quick start (optional)</flux:label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($this->tierPresets as $key => $preset)
                            <button type="button" wire:click.prevent="applyTier('{{ $key }}')"
                                @class([
                                    'text-left px-3 py-2 rounded-md border text-sm transition-colors cursor-pointer',
                                    'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40' => $form->tier === $key,
                                    'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' => $form->tier !== $key,
                                ])>
                                <div class="font-medium text-zinc-800 dark:text-zinc-200">{{ $preset['name'] }}</div>
                                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    {{ $preset['is_delivery_available'] ? 'Delivery available' : 'Not deliverable' }}
                                </div>
                            </button>
                        @endforeach
                    </div>
                    <flux:description>Picks fill in the fields below. You can edit anything before saving.</flux:description>
                </div>
            @endunless

            <flux:input wire:model="form.name" label="Zone Name" placeholder="e.g. Nairobi Metro" />
            <flux:input wire:model="form.code" label="Code" placeholder="e.g. nairobi_metro"
                description="Short unique identifier. Lowercase, no spaces. Optional." />

            <flux:select wire:model="form.status" label="Status">
                @foreach ($this->statuses as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="form.description" label="Description"
                placeholder="What areas does this zone cover?" rows="2" />

            <flux:field variant="inline">
                <flux:checkbox wire:model="form.is_delivery_available" />
                <flux:label>Delivery Available</flux:label>
                <flux:description>
                    When off, checkout shows a no-delivery message for this zone. Used for quote-only or out-of-footprint tiers.
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

    {{-- ─── Delete Confirmation ─────────────────────────────────────────── --}}
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

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- MAP JS — only initializes when the map container is in the DOM           --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}

<script type="application/json" id="zone-map-payload">{!! json_encode([
    'wireId'        => $this->getId(),
    'adm2Data'      => $this->mapData,
    'adm3Data'      => $this->townMapData,
    'zones'         => $this->zonesForMap,
    'mapsKey'       => config('services.google.maps_key', ''),
    'adm2GeojsonUrl' => asset('maps/geoBoundaries-KEN-ADM2_simplified.geojson'),
    'adm3GeojsonUrl' => asset('maps/geoBoundaries-KEN-ADM3_simplified.geojson'),
]) !!}</script>

<script>
(function () {
    const _d           = JSON.parse(document.getElementById('zone-map-payload').textContent);
    const WIRE_ID      = _d.wireId;
    const ADM2_DATA    = _d.adm2Data;
    const ADM3_DATA    = _d.adm3Data;
    const ZONES        = _d.zones;
    const MAPS_KEY     = _d.mapsKey;
    const GEOJSON_URLS = { adm2: _d.adm2GeojsonUrl, adm3: _d.adm3GeojsonUrl };

    // Currently active level. ZONE_DATA always points at the active dataset.
    let currentLevel = 'adm2';
    let ZONE_DATA    = ADM2_DATA;
    const loadedLevels = new Set();

    let map = null, dataLayer = null, drawingManager = null, drawnPolygon = null;
    let infoWindow = null;
    let mode = 'view';
    let selectedZone = ZONES[0] ?? null;
    let pendingIds = [];

    window.selectZone = function (btn) {
        document.querySelectorAll('.zone-btn').forEach(b => {
            b.style.borderColor = 'transparent';
            b.style.fontWeight  = '500';
        });
        btn.style.borderColor = btn.dataset.zoneColor;
        btn.style.fontWeight  = '700';
        selectedZone = { id: +btn.dataset.zoneId, name: btn.dataset.zoneName, color: btn.dataset.zoneColor };

        if (mode === 'draw') {
            const indicator = document.getElementById('mode-indicator');
            if (indicator) {
                indicator.textContent = '✏ Draw a polygon to assign zone: ' + selectedZone.name;
                indicator.style.background = selectedZone.color;
            }
        }
    };

    const firstZoneBtn = document.querySelector('.zone-btn');
    if (firstZoneBtn) { selectZone(firstZoneBtn); }

    const MODE_STYLES = {
        active:   'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-900',
        inactive: 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800',
    };

    window.setMode = function (newMode) {
        cancelSelection();
        infoWindow?.close();
        mode = newMode;

        ['view', 'draw', 'clear'].forEach(m => {
            const btn = document.getElementById('btn-' + m);
            if (!btn) return;
            const isActive = mode === m;
            btn.className = btn.className
                .replace(MODE_STYLES.active, '')
                .replace(MODE_STYLES.inactive, '')
                .trim();
            btn.classList.add(...(isActive ? MODE_STYLES.active : MODE_STYLES.inactive).split(' '));
        });

        const indicator = document.getElementById('mode-indicator');
        if (mode === 'draw') {
            indicator.textContent = '✏ Draw a polygon to assign zone: ' + (selectedZone?.name ?? '');
            indicator.style.background = selectedZone?.color ?? '#6366F1';
            indicator.classList.remove('hidden');
        } else if (mode === 'clear') {
            indicator.textContent = '✕ Draw a polygon to clear zone overrides';
            indicator.style.background = '#6B7280';
            indicator.classList.remove('hidden');
        } else {
            indicator.classList.add('hidden');
        }

        if (mode !== 'view' && dataLayer) { dataLayer.revertStyle(); }
        if (! drawingManager) { return; }

        drawingManager.setDrawingMode(
            (mode === 'draw' || mode === 'clear') ? google.maps.drawing.OverlayType.POLYGON : null
        );
    };

    window.confirmAssignment = function () {
        if (! pendingIds.length) { return; }

        document.getElementById('selection-bar').classList.add('hidden');

        const wire = Livewire.find(WIRE_ID);
        const assignMethod = currentLevel === 'adm3' ? 'assignZoneToTowns' : 'assignZoneToSubCounties';
        const clearMethod  = currentLevel === 'adm3' ? 'clearZoneForTowns' : 'clearZoneForSubCounties';

        if (mode === 'clear') {
            wire.call(clearMethod, pendingIds)
                .then(() => applyLocalUpdate(pendingIds, null, '#9CA3AF', 'No Zone'));
        } else {
            wire.call(assignMethod, selectedZone.id, pendingIds)
                .then(() => applyLocalUpdate(pendingIds, selectedZone.id, selectedZone.color, selectedZone.name));
        }

        cancelSelection();
    };

    // Switch between ADM2 (sub-counties) and ADM3 (wards). Re-loads the data
    // layer with the appropriate GeoJSON; cache fetched files in-memory.
    window.setLevel = function (level) {
        if (level === currentLevel) return;
        currentLevel = level;
        ZONE_DATA = level === 'adm3' ? ADM3_DATA : ADM2_DATA;

        // Update toggle button styling
        ['adm2', 'adm3'].forEach(l => {
            const btn = document.getElementById('btn-level-' + l);
            if (!btn) return;
            const isActive = level === l;
            btn.className = btn.className
                .replace(MODE_STYLES.active, '')
                .replace(MODE_STYLES.inactive, '')
                .trim();
            btn.classList.add(...(isActive ? MODE_STYLES.active : MODE_STYLES.inactive).split(' '));
        });

        cancelSelection();
        loadGeoJsonForLevel(level);
    };

    function loadGeoJsonForLevel(level) {
        if (!dataLayer) return;

        // Drop currently-loaded features
        dataLayer.forEach(f => dataLayer.remove(f));

        fetch(GEOJSON_URLS[level])
            .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(data => {
                dataLayer.addGeoJson(data);
                refreshDataLayer();
                loadedLevels.add(level);
                if (level === 'adm3') fitMapToData();
            })
            .catch(() => {
                const c = document.getElementById('zone-map');
                if (c) c.innerHTML = '<p style="padding:2rem;color:#6b7280;font-size:14px">Failed to load '+level.toUpperCase()+' boundaries.</p>';
            });
    }

    window.cancelSelection = function () {
        if (drawnPolygon) { drawnPolygon.setMap(null); drawnPolygon = null; }
        pendingIds = [];
        const bar = document.getElementById('selection-bar');
        if (bar) bar.classList.add('hidden');
        refreshDataLayer();
    };

    // Extract the right ID field for the current level (sub_county_id or town_id).
    function recordIdOf(d) {
        return currentLevel === 'adm3' ? d.town_id : d.sub_county_id;
    }

    function applyLocalUpdate(recordIds, zoneId, color, zoneName) {
        const idSet = new Set(recordIds.map(Number));
        for (const shapeId in ZONE_DATA) {
            const d = ZONE_DATA[shapeId];
            if (idSet.has(recordIdOf(d))) {
                d.zone_id = zoneId;
                d.zone    = zoneName;
                d.color   = color;
            }
        }
        refreshDataLayer();
    }

    function refreshDataLayer() {
        if (dataLayer) { dataLayer.setStyle(featureStyle); }
    }

    function initMap() {
        const container = document.getElementById('zone-map');
        if (!container) return;

        map = new google.maps.Map(container, {
            center: { lat: -0.023, lng: 37.9 },
            zoom: 6,
            mapTypeId: 'roadmap',
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
                mapTypeIds: ['roadmap', 'satellite', 'terrain'],
            },
            streetViewControl: false,
            fullscreenControl: true,
        });

        dataLayer = new google.maps.Data({ map });
        dataLayer.setStyle(featureStyle);

        infoWindow = new google.maps.InfoWindow({ disableAutoPan: true, pixelOffset: new google.maps.Size(0, -8) });

        dataLayer.addListener('mouseover', e => {
            if (mode !== 'view') return;
            dataLayer.overrideStyle(e.feature, { strokeWeight: 2.5, strokeColor: '#1e293b', fillOpacity: 0.55 });

            const d = ZONE_DATA[e.feature.getProperty('shapeID')];
            if (!d) return;

            const name   = currentLevel === 'adm3' ? d.town : d.sub_county;
            const parent = currentLevel === 'adm3' ? `${d.sub_county}, ${d.county}` : d.county;
            const hasOverride = currentLevel === 'adm3' ? !!d.town_id : !!d.sub_county_id;

            infoWindow.setContent(`<div style="font-family:system-ui,sans-serif;font-size:13px;line-height:1.6;padding:2px 4px">
                <strong style="display:block;font-size:14px">${name}</strong>
                <span style="color:#6b7280;font-size:11px">${parent}</span>
                <div style="margin-top:6px;display:flex;align-items:center;gap:6px;font-size:12px">
                    <span style="width:10px;height:10px;border-radius:2px;background:${d.color};display:inline-block;flex-shrink:0"></span>
                    <span>${d.zone}</span>
                </div>
            </div>`);
            infoWindow.setPosition(e.latLng);
            infoWindow.open(map);
        });

        dataLayer.addListener('mousemove', e => {
            if (mode !== 'view' || !infoWindow) return;
            infoWindow.setPosition(e.latLng);
        });

        dataLayer.addListener('mouseout', () => {
            if (mode !== 'view') return;
            dataLayer.revertStyle();
            infoWindow?.close();
        });

        drawingManager = new google.maps.drawing.DrawingManager({
            drawingMode: null,
            drawingControl: false,
            polygonOptions: {
                fillColor:   '#6366F1',
                fillOpacity: 0.15,
                strokeColor: '#6366F1',
                strokeWeight: 2,
                strokeDashArray: [6, 3],
                editable:    true,
                zIndex:      10,
            },
        });
        drawingManager.setMap(map);

        drawingManager.addListener('overlaycomplete', e => {
            if (drawnPolygon) { drawnPolygon.setMap(null); }
            drawnPolygon = e.overlay;

            const matches = findRecordsInPolygon(drawnPolygon);
            pendingIds = matches.map(recordIdOf);

            if (! pendingIds.length) {
                drawnPolygon.setMap(null);
                drawnPolygon = null;
                return;
            }

            const matchedIdSet = new Set(pendingIds);
            dataLayer.setStyle(feature => {
                const d = ZONE_DATA[feature.getProperty('shapeID')];
                if (d && matchedIdSet.has(recordIdOf(d))) {
                    return { fillColor: '#FBBF24', fillOpacity: 0.8, strokeColor: '#D97706', strokeWeight: 2 };
                }
                return featureStyle(feature);
            });

            const noun = currentLevel === 'adm3' ? 'wards' : 'sub-counties';
            const label = mode === 'clear'
                ? `${pendingIds.length} ${noun} will have their zone override cleared.`
                : `${pendingIds.length} ${noun} will be assigned to <strong>${selectedZone?.name ?? '—'}</strong>.`;

            document.getElementById('selection-label').innerHTML = label;
            document.getElementById('btn-confirm').textContent = mode === 'clear' ? 'Clear Overrides' : 'Assign Zone';
            document.getElementById('selection-bar').classList.remove('hidden');

            drawingManager.setDrawingMode(null);
        });

        fetch(GEOJSON_URLS[currentLevel])
            .then(r => { if (! r.ok) { throw new Error(r.status); } return r.json(); })
            .then(data => {
                dataLayer.addGeoJson(data);
                loadedLevels.add(currentLevel);
                fitMapToData();
            })
            .catch(() => {
                container.innerHTML = `
                    <div style="display:flex;align-items:center;justify-content:center;height:100%;text-align:center;color:#6b7280;font-size:14px;padding:2rem">
                        <div>
                            <p style="font-weight:600;margin-bottom:4px">Map data not available</p>
                            <p style="font-size:12px">Run <code>php artisan db:seed --class=CountyCoordinatesSeeder</code> to load boundaries.</p>
                        </div>
                    </div>`;
            });
    }

    function featureStyle(feature) {
        const d = ZONE_DATA[feature.getProperty('shapeID')];
        return {
            fillColor:   d?.color ?? '#9CA3AF',
            fillOpacity: 0.35,
            strokeColor: d?.color ?? '#9CA3AF',
            strokeWeight: 1.2,
            strokeOpacity: 0.8,
        };
    }

    function fitMapToData() {
        const bounds = new google.maps.LatLngBounds();
        dataLayer.forEach(feature => {
            feature.getGeometry()?.forEachLatLng(latlng => bounds.extend(latlng));
        });
        if (! bounds.isEmpty()) { map.fitBounds(bounds, { top: 10, right: 10, bottom: 10, left: 10 }); }
    }

    function findRecordsInPolygon(polygon) {
        const matches = [];
        for (const shapeId in ZONE_DATA) {
            const d = ZONE_DATA[shapeId];
            if (! d.centroid?.lat || ! d.centroid?.lng) continue;
            const point = new google.maps.LatLng(d.centroid.lat, d.centroid.lng);
            if (google.maps.geometry.poly.containsLocation(point, polygon)) {
                matches.push(d);
            }
        }
        return matches;
    }

    function loadGoogleMaps(key, cb) {
        if (window.google?.maps?.drawing) { return cb(); }
        const s  = document.createElement('script');
        s.src    = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=drawing,geometry`;
        s.onload = cb;
        s.onerror = () => {
            const c = document.getElementById('zone-map');
            if (c) c.innerHTML = '<p style="padding:2rem;color:#6b7280;font-size:14px">Google Maps failed to load. Check your API key.</p>';
        };
        document.head.appendChild(s);
    }

    loadGoogleMaps(MAPS_KEY, initMap);
})();
</script>
