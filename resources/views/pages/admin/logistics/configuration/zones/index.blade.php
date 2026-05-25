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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    // ─── Computed data ────────────────────────────────────────────────────

    #[Computed]
    public function zones()
    {
        return ShippingZone::query()
            ->when($this->search, fn($q) => $q->where(fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->withCount(['counties', 'subCounties as sub_county_overrides_count'])
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function statuses(): array
    {
        return ShippingZoneStatus::cases();
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
        $zones = ShippingZone::all()->keyBy('id');
        $data = [];

        SubCounty::with('county')
            ->whereNotNull('shape_id')
            ->get()
            ->each(function ($sc) use (&$data, $zones, $zoneColors) {
                $effectiveZoneId = $sc->shipping_zone_id ?? $sc->county?->shipping_zone_id;
                $zone = $effectiveZoneId ? $zones->get($effectiveZoneId) : null;

                $data[$sc->shape_id] = [
                    'sub_county_id' => $sc->id,
                    'sub_county' => $sc->name,
                    'county' => $sc->county?->name ?? '',
                    'zone_id' => $zone?->id,
                    'zone' => $zone?->name ?? 'No Zone',
                    'color' => $zone ? $zoneColors[$zone->id] ?? '#9CA3AF' : '#9CA3AF',
                    'centroid' => [
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
        $zones = ShippingZone::all()->keyBy('id');
        $data = [];

        Town::with(['subCounty', 'county'])
            ->whereNotNull('shape_id')
            ->get()
            ->each(function ($town) use (&$data, $zones, $zoneColors) {
                $effectiveZoneId = $town->shipping_zone_id ?? ($town->subCounty?->shipping_zone_id ?? $town->county?->shipping_zone_id);

                $zone = $effectiveZoneId ? $zones->get($effectiveZoneId) : null;

                $data[$town->shape_id] = [
                    'town_id' => $town->id,
                    'town' => $town->name,
                    'sub_county' => $town->subCounty?->name ?? '',
                    'county' => $town->county?->name ?? '',
                    'zone_id' => $zone?->id,
                    'zone' => $zone?->name ?? 'No Zone',
                    'color' => $zone ? $zoneColors[$zone->id] ?? '#9CA3AF' : '#9CA3AF',
                    'centroid' => [
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
     * @return array<int, array{id: int, name: string, color: string, available: bool, has_geometry: bool, geometry: array|null}>
     */
    #[Computed]
    public function zonesForMap(): array
    {
        $colors = $this->zoneColors;

        return ShippingZone::orderBy('id')
            ->get()
            ->map(
                fn($z) => [
                    'id' => $z->id,
                    'name' => $z->name,
                    'color' => $colors[$z->id] ?? '#9CA3AF',
                    'available' => (bool) $z->is_delivery_available,
                    'has_geometry' => $z->geometry !== null,
                    'geometry' => $z->geometry,
                ],
            )
            ->values()
            ->toArray();
    }

    // ─── Actions ──────────────────────────────────────────────────────────

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
            unset($this->zones, $this->mapData, $this->zonesForMap, $this->zoneColors);

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
            unset($this->zones, $this->mapData, $this->zonesForMap, $this->zoneColors);
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

    // ─── Map mutations (called from JS) ───────────────────────────────────

    // ─── Admin-boundary assignment (county / sub-county / ward) ─────────────
    // These methods assign admin units to a zone. They do NOT touch the
    // zone's custom polygon — those are managed independently below.

    public function assignZoneToSubCounties(int $zoneId, array $subCountyIds): void
    {
        $zone = ShippingZone::findOrFail($zoneId);

        SubCounty::whereIn('id', array_map('intval', $subCountyIds))->update(['shipping_zone_id' => $zone->id]);

        unset($this->mapData);

        $this->dispatch('notify', title: 'Zone Assigned', variant: 'success', message: count($subCountyIds) . ' sub-counties assigned to ' . $zone->name . '.');
    }

    public function clearZoneForSubCounties(array $subCountyIds): void
    {
        SubCounty::whereIn('id', array_map('intval', $subCountyIds))->update(['shipping_zone_id' => null]);

        unset($this->mapData);

        $this->dispatch('notify', title: 'Overrides Cleared', variant: 'success', message: count($subCountyIds) . ' sub-counties will now inherit from their county zone.');
    }

    public function assignZoneToTowns(int $zoneId, array $townIds): void
    {
        $zone = ShippingZone::findOrFail($zoneId);

        Town::whereIn('id', array_map('intval', $townIds))->update(['shipping_zone_id' => $zone->id]);

        unset($this->townMapData);

        $this->dispatch('notify', title: 'Wards Updated', variant: 'success', message: count($townIds) . ' wards assigned to ' . $zone->name . '.');
    }

    public function clearZoneForTowns(array $townIds): void
    {
        Town::whereIn('id', array_map('intval', $townIds))->update(['shipping_zone_id' => null]);

        unset($this->townMapData);

        $this->dispatch('notify', title: 'Ward Overrides Cleared', variant: 'success', message: count($townIds) . ' wards will now inherit from sub-county / county.');
    }

    // ─── Custom polygon boundary ──────────────────────────────────────────────
    // These methods manage the zone's precise drawn polygon, which is used at
    // checkout as the highest-priority resolution step — completely independent
    // of the admin-boundary hierarchy above.

    public function saveZonePolygon(int $zoneId, array $coordinates): void
    {
        ShippingZone::findOrFail($zoneId)->update(['geometry' => $coordinates]);

        unset($this->zonesForMap);

        $this->dispatch('notify', title: 'Boundary Saved', variant: 'success', message: 'Custom polygon boundary saved. It will be used first at checkout for this zone.');
    }

    public function clearZonePolygon(int $zoneId): void
    {
        ShippingZone::findOrFail($zoneId)->update(['geometry' => null]);

        unset($this->zonesForMap);

        $this->dispatch('notify', title: 'Boundary Removed', variant: 'info', message: 'Custom polygon removed. Zone will resolve via county/sub-county/ward assignments.');
    }
}; ?>

<x-admin.logistics.layout heading="Zones"
    subheading="Define service tiers and the geography they cover. Switch views to manage the same zones visually or as a list.">

    <x-slot:actions>
        {{-- View toggle (Alpine-driven, persisted in localStorage) --}}
        <div x-data="{ view: localStorage.getItem('zones_view') ?? 'map' }" x-init="$watch('view', v => localStorage.setItem('zones_view', v))" x-on:zones-view-change.window="view = $event.detail">
            <flux:button.group>
                <flux:button icon="map" x-on:click="view = 'map'; $dispatch('zones-view-change', 'map')"
                    x-bind:class="view === 'map' ? 'bg-primary! text-white! dark:bg-zinc-200! dark:text-zinc-900!' : ''">
                    Map
                </flux:button>
                <flux:button icon="list-bullet" x-on:click="view = 'table'; $dispatch('zones-view-change', 'table')"
                    x-bind:class="view === 'table' ? 'bg-primary! text-white! dark:bg-zinc-200! dark:text-zinc-900!' : ''">
                    Table
                </flux:button>
            </flux:button.group>
        </div>

        <flux:button variant="primary" icon="plus-circle" wire:click="openCreate" class="cursor-pointer">
            Add Zone
        </flux:button>
    </x-slot:actions>

    {{-- Outer Alpine wrapper for view toggling --}}
    <div x-data="{ view: localStorage.getItem('zones_view') ?? 'map' }" x-on:zones-view-change.window="view = $event.detail">

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- MAP VIEW                                                         --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <div x-show="view === 'map'" x-cloak>

            {{-- Toolbar: granularity left, mode right --}}
            <div class="flex items-center justify-between gap-3 mb-4 flex-wrap" x-data="{ mapLevel: 'adm2', mapMode: 'view' }"
                x-on:map-level-change.window="mapLevel = $event.detail"
                x-on:map-mode-change.window="mapMode = $event.detail">

                {{-- Granularity --}}
                <div class="flex items-center gap-2">
                    <flux:button.group>
                        <flux:button size="sm" icon="squares-2x2"
                            x-on:click="mapLevel = 'adm2'; $dispatch('map-level-change', 'adm2')"
                            onclick="setLevel('adm2')"
                            x-bind:class="mapLevel === 'adm2' ? 'bg-primary! text-white! dark:bg-zinc-200! dark:text-zinc-900!' :
                                ''">
                            Sub-counties
                        </flux:button>
                        <flux:button size="sm" icon="view-columns"
                            x-on:click="mapLevel = 'adm3'; $dispatch('map-level-change', 'adm3')"
                            onclick="setLevel('adm3')"
                            x-bind:class="mapLevel === 'adm3' ? 'bg-primary! text-white! dark:bg-zinc-200! dark:text-zinc-900!' :
                                ''">
                            Wards
                        </flux:button>
                    </flux:button.group>
                </div>

                {{-- Mode --}}
                <div class="flex items-center gap-2">
                    <flux:button.group>
                        <flux:button size="sm" icon="eye"
                            x-on:click="mapMode = 'view'; $dispatch('map-mode-change', 'view')"
                            onclick="setMode('view')"
                            x-bind:class="mapMode === 'view' ? 'bg-primary! text-white! dark:bg-zinc-200! dark:text-zinc-900!' :
                                ''">
                            View
                        </flux:button>
                        <flux:button size="sm" icon="pencil-square"
                            x-on:click="mapMode = 'draw'; $dispatch('map-mode-change', 'draw')"
                            onclick="setMode('draw')"
                            x-bind:class="mapMode === 'draw' ? 'bg-primary! text-white! dark:bg-zinc-200! dark:text-zinc-900!' :
                                ''">
                            Assign Areas
                        </flux:button>
                        <flux:button size="sm" icon="map-pin"
                            x-on:click="mapMode = 'boundary'; $dispatch('map-mode-change', 'boundary')"
                            onclick="setMode('boundary')"
                            x-bind:class="mapMode === 'boundary' ? 'bg-primary! text-white! dark:bg-zinc-200! dark:text-zinc-900!' :
                                ''">
                            Custom Boundary
                        </flux:button>
                        <flux:button size="sm" icon="x-circle"
                            x-on:click="mapMode = 'clear'; $dispatch('map-mode-change', 'clear')"
                            onclick="setMode('clear')"
                            x-bind:class="mapMode === 'clear' ? 'bg-primary! text-white! dark:bg-zinc-200! dark:text-zinc-900!' :
                                ''">
                            Clear
                        </flux:button>
                    </flux:button.group>
                </div>
            </div>

            {{-- Map + Sidebar --}}
            <div class="grid grid-cols-1 xl:grid-cols-4 gap-4 items-start">

                {{-- Map card --}}
                <flux:card class="xl:col-span-3 p-0 overflow-hidden">

                    {{-- Mode status bar --}}
                    <div id="mode-status-bar"
                        class="flex items-center gap-2 px-4 py-2 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900">
                        <flux:icon.eye class="size-3.5 text-zinc-400 shrink-0" id="mode-status-icon" />
                        <span class="text-xs text-zinc-500 dark:text-zinc-400" id="mode-status-text">
                            Hover any area to see its zone. Click <strong>Draw Zone</strong> to start assigning.
                        </span>
                    </div>

                    {{-- Selection confirmation bar --}}
                    <div id="selection-bar"
                        class="hidden items-center gap-3 px-4 py-2.5 bg-indigo-50 dark:bg-indigo-950/50 border-b border-indigo-200 dark:border-indigo-800">
                        <flux:icon.cursor-arrow-rays class="size-4 text-indigo-500 dark:text-indigo-400 shrink-0" />
                        <span id="selection-label"
                            class="text-sm text-indigo-700 dark:text-indigo-300 flex-1 min-w-0"></span>
                        <flux:button size="sm" variant="primary" onclick="confirmAssignment()" id="btn-confirm"
                            class="shrink-0">
                            Confirm
                        </flux:button>
                        <flux:button size="sm" variant="ghost" onclick="cancelSelection()" class="shrink-0">
                            Cancel
                        </flux:button>
                    </div>

                    <div id="zone-map" class="w-full" style="height:600px;">
                        <div class="flex items-center justify-center h-full text-zinc-400 text-sm">
                            Loading map…
                        </div>
                    </div>
                </flux:card>

                {{-- Sidebar --}}
                <div class="space-y-3">

                    {{-- Zone picker --}}
                    <flux:card class="p-0 overflow-hidden">
                        <div class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-800">
                            <flux:heading size="sm">Zones</flux:heading>
                            <p class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-0.5">
                                Click a zone to select it for drawing
                            </p>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($this->zonesForMap as $zone)
                                <div class="zone-btn group flex items-center gap-3 px-4 py-3 cursor-pointer transition-colors border-l-[3px] border-transparent hover:bg-zinc-50 dark:hover:bg-zinc-800/60 select-none"
                                    data-zone-id="{{ $zone['id'] }}" data-zone-name="{{ $zone['name'] }}"
                                    data-zone-color="{{ $zone['color'] }}" onclick="selectZone(this)">

                                    {{-- Colour swatch --}}
                                    <span class="w-4 h-10 rounded shrink-0"
                                        style="background:{{ $zone['color'] }}"></span>

                                    {{-- Info --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 leading-snug">
                                            {{ $zone['name'] }}
                                        </p>
                                        <p class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-0.5">
                                            {{ $zone['available'] ? 'Delivery available' : 'Not deliverable' }}
                                        </p>
                                        @if ($zone['has_geometry'])
                                            <p
                                                class="text-[11px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1 mt-0.5">
                                                <flux:icon.map-pin class="size-2.5 shrink-0" />
                                                Custom boundary set
                                            </p>
                                        @endif
                                    </div>

                                    {{-- Actions --}}
                                    <div
                                        class="flex flex-col items-center gap-1.5 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="{{ route('admin.logistics.configuration.zones.show', $zone['id']) }}"
                                            wire:navigate onclick="event.stopPropagation()" title="Zone details"
                                            class="p-1 rounded-md text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 dark:hover:text-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                                            <flux:icon.arrow-top-right-on-square class="size-3.5" />
                                        </a>
                                        @if ($zone['has_geometry'])
                                            <button type="button"
                                                onclick="event.stopPropagation(); clearZoneBoundary({{ $zone['id'] }})"
                                                title="Remove custom boundary"
                                                class="p-1 rounded-md text-zinc-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors cursor-pointer">
                                                <flux:icon.map-pin class="size-3.5" />
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            {{-- No zone row --}}
                            <div class="flex items-center gap-3 px-4 py-3">
                                <span class="w-4 h-10 rounded shrink-0 bg-zinc-300 dark:bg-zinc-600"></span>
                                <div>
                                    <p class="text-sm text-zinc-400 dark:text-zinc-500">No Zone</p>
                                    <p class="text-[11px] text-zinc-300 dark:text-zinc-600 mt-0.5">Unassigned areas</p>
                                </div>
                            </div>
                        </div>
                    </flux:card>

                    {{-- Quick help --}}
                    <flux:card class="p-4">
                        <div class="space-y-3 text-xs text-zinc-500 dark:text-zinc-400">
                            <div class="flex gap-2.5">
                                <flux:icon.eye class="size-3.5 shrink-0 mt-0.5 text-zinc-400" />
                                <span><strong class="text-zinc-700 dark:text-zinc-300">View</strong> — hover to inspect
                                    any area's zone.</span>
                            </div>
                            <div class="flex gap-2.5">
                                <flux:icon.pencil-square class="size-3.5 shrink-0 mt-0.5 text-indigo-400" />
                                <span><strong class="text-zinc-700 dark:text-zinc-300">Assign Areas</strong> — select a
                                    zone, draw a polygon, and confirm. Sub-counties or wards whose centroid falls inside
                                    are assigned to that zone.</span>
                            </div>
                            <div class="flex gap-2.5">
                                <flux:icon.map-pin class="size-3.5 shrink-0 mt-0.5 text-violet-400" />
                                <span><strong class="text-zinc-700 dark:text-zinc-300">Custom Boundary</strong> — draw
                                    a precise polygon that takes priority at checkout. Customers whose GPS pin falls
                                    inside are placed directly in this zone — overriding county/sub-county
                                    assignments.</span>
                            </div>
                            <div class="flex gap-2.5">
                                <flux:icon.x-circle class="size-3.5 shrink-0 mt-0.5 text-zinc-400" />
                                <span><strong class="text-zinc-700 dark:text-zinc-300">Clear</strong> — draw to reset
                                    areas back to their county default.</span>
                            </div>
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
                        <flux:select wire:model.live="filterStatus" placeholder="All Statuses" clearable
                            class="md:w-44">
                            @foreach ($this->statuses as $status)
                                <flux:select.option value="{{ $status->value }}">{{ $status->label() }}
                                </flux:select.option>
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
                                    <a href="{{ route('admin.logistics.configuration.zones.show', $zone) }}"
                                        wire:navigate
                                        class="font-medium text-zinc-800 dark:text-zinc-200 hover:underline">
                                        {{ $zone->name }}
                                    </a>
                                    @if ($zone->description)
                                        <p class="text-xs text-zinc-400 mt-0.5 max-w-md truncate">
                                            {{ $zone->description }}</p>
                                    @endif
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
                                    <flux:subheading>{{ $zone->sub_county_overrides_count }}</flux:subheading>
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
                                        {{ $status->label() }}</flux:badge>
                                </flux:table.cell>

                                <flux:table.cell align="end" class="pe-4!">
                                    <flux:button variant="ghost" size="sm" icon="arrow-top-right-on-square"
                                        :href="route('admin.logistics.configuration.zones.show', $zone)" wire:navigate
                                        class="cursor-pointer" tooltip="View" />
                                    <flux:button variant="ghost" size="sm" icon="pencil-square"
                                        wire:click="edit({{ $zone->id }})" class="cursor-pointer"
                                        tooltip="Edit" />
                                    <flux:button variant="ghost" size="sm" icon="trash" color="red"
                                        wire:click="confirmDelete({{ $zone->id }})"
                                        class="cursor-pointer text-red-500!" tooltip="Delete" />
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
                    When off, checkout shows a no-delivery message for this zone. Used for quote-only or
                    out-of-footprint tiers.
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
    'adm2Data'       => $this->mapData,
    'adm3Data'       => $this->townMapData,
    'zones'          => $this->zonesForMap,
    'mapsKey'        => config('services.google.maps_key', ''),
    'adm2GeojsonUrl' => asset('maps/geoBoundaries-KEN-ADM2_simplified.geojson'),
    'adm3GeojsonUrl' => asset('maps/geoBoundaries-KEN-ADM3_simplified.geojson'),
]) !!}</script>

<script>
    (function() {
        const _d = JSON.parse(document.getElementById('zone-map-payload').textContent);
        const ADM2_DATA = _d.adm2Data;
        const ADM3_DATA = _d.adm3Data;
        const ZONES = _d.zones;
        const MAPS_KEY = _d.mapsKey;
        const GEOJSON_URLS = {
            adm2: _d.adm2GeojsonUrl,
            adm3: _d.adm3GeojsonUrl
        };

        // Currently active level. ZONE_DATA always points at the active dataset.
        let currentLevel = 'adm2';
        let ZONE_DATA = ADM2_DATA;
        const loadedLevels = new Set();

        let map = null,
            dataLayer = null,
            drawingManager = null,
            drawnPolygon = null;
        let mode = 'view';
        let selectedZone = ZONES[0] ?? null;
        let pendingIds = [];
        let storedPolygons = {}; // zone id → google.maps.Polygon for saved boundaries

        window.selectZone = function(el) {
            // Clear all zone item selection styles
            document.querySelectorAll('.zone-btn').forEach(b => {
                b.style.borderLeftColor = 'transparent';
                b.style.backgroundColor = '';
            });

            // Highlight the selected zone item
            el.style.borderLeftColor = el.dataset.zoneColor;
            el.style.backgroundColor = el.dataset.zoneColor + '18';

            selectedZone = {
                id: +el.dataset.zoneId,
                name: el.dataset.zoneName,
                color: el.dataset.zoneColor
            };

            // In boundary mode, the drawing polygon should use the zone's colour so
            // the admin sees a live preview of the boundary being drawn.
            if (mode === 'boundary' && drawingManager) {
                drawingManager.setOptions({
                    polygonOptions: {
                        fillColor: selectedZone.color,
                        fillOpacity: 0.15,
                        strokeColor: selectedZone.color,
                        strokeWeight: 2.5,
                        editable: true,
                        zIndex: 10,
                    },
                });
            }

            updateModeStatus();
        };

        // Update the status bar text and icon to reflect the current mode + selected zone.
        function updateModeStatus() {
            const bar = document.getElementById('mode-status-bar');
            const text = document.getElementById('mode-status-text');
            if (!bar || !text) {
                return;
            }

            if (mode === 'draw') {
                const zoneName = selectedZone?.name ?? '—';
                const color = selectedZone?.color ?? '#6366F1';
                bar.style.background = color + '18';
                bar.style.borderColor = color + '60';
                text.innerHTML =
                    `✏ <strong>Assign areas</strong> — draw a polygon around the sub-counties/wards to assign to <strong style="color:${color}">${zoneName}</strong>.`;
            } else if (mode === 'boundary') {
                const zoneName = selectedZone?.name ?? '—';
                const color = selectedZone?.color ?? '#8B5CF6';
                bar.style.background = '#f5f3ff';
                bar.style.borderColor = '#c4b5fd';
                text.innerHTML =
                    `📍 <strong>Custom boundary</strong> — draw the precise delivery boundary for <strong style="color:${color}">${zoneName}</strong>. This polygon takes priority over county/sub-county assignments at checkout.`;
            } else if (mode === 'clear') {
                bar.style.background = '#fef2f2';
                bar.style.borderColor = '#fecaca';
                text.innerHTML =
                    '✕ <strong>Clear mode</strong> — draw a polygon to reset areas inside back to their county default.';
            } else {
                bar.style.background = '';
                bar.style.borderColor = '';
                text.innerHTML =
                    'Hover any area to see its zone. Switch to <strong>Assign Areas</strong> mode to assign areas to a zone, or <strong>Custom Boundary</strong> to draw a precise checkout boundary.';
            }
        }

        const firstZoneBtn = document.querySelector('.zone-btn');
        if (firstZoneBtn) {
            selectZone(firstZoneBtn);
        }

        window.setMode = function(newMode) {
            cancelSelection();
            mode = newMode;

            updateModeStatus();

            if (mode !== 'view' && dataLayer) {
                dataLayer.revertStyle();
            }
            if (!drawingManager) {
                return;
            }

            // In boundary mode the drawn polygon uses the zone's own colour so the
            // admin gets a live preview of exactly what will be stored.
            if (mode === 'boundary' && selectedZone) {
                drawingManager.setOptions({
                    polygonOptions: {
                        fillColor: selectedZone.color,
                        fillOpacity: 0.15,
                        strokeColor: selectedZone.color,
                        strokeWeight: 2.5,
                        editable: true,
                        zIndex: 10,
                    },
                });
            } else {
                drawingManager.setOptions({
                    polygonOptions: {
                        fillColor: '#6366F1',
                        fillOpacity: 0.15,
                        strokeColor: '#6366F1',
                        strokeWeight: 2,
                        editable: true,
                        zIndex: 10,
                    },
                });
            }

            drawingManager.setDrawingMode(
                (mode === 'draw' || mode === 'clear' || mode === 'boundary') ?
                google.maps.drawing.OverlayType.POLYGON :
                null
            );
        };

        window.confirmAssignment = function() {
            const selBar = document.getElementById('selection-bar');
            selBar.classList.add('hidden');
            selBar.classList.remove('flex');

            // ── Custom boundary: save drawn polygon as zone.geometry only ──────────
            if (mode === 'boundary') {
                if (!drawnPolygon || !selectedZone) {
                    cancelSelection();
                    return;
                }

                const polygonPath = [];
                drawnPolygon.getPath().forEach(latlng => {
                    polygonPath.push([latlng.lat(), latlng.lng()]);
                });

                $wire.saveZonePolygon(selectedZone.id, polygonPath)
                    .then(() => {
                        // Keep local ZONES in sync so renderStoredPolygons reflects the new boundary.
                        const zone = ZONES.find(z => z.id === selectedZone.id);
                        if (zone) {
                            zone.geometry = polygonPath;
                            zone.has_geometry = true;
                        }
                        renderStoredPolygons();
                    });

                cancelSelection();
                return;
            }

            // ── Admin-boundary assignment or clear ────────────────────────────────
            if (!pendingIds.length) {
                cancelSelection();
                return;
            }

            const assignMethod = currentLevel === 'adm3' ? 'assignZoneToTowns' : 'assignZoneToSubCounties';
            const clearMethod = currentLevel === 'adm3' ? 'clearZoneForTowns' : 'clearZoneForSubCounties';

            if (mode === 'clear') {
                $wire[clearMethod](pendingIds)
                    .then(() => applyLocalUpdate(pendingIds, null, '#9CA3AF', 'No Zone'));
            } else {
                // Assign sub-counties/wards to the selected zone.
                // No polygon is saved here — use "Custom Boundary" mode for that.
                $wire[assignMethod](selectedZone.id, pendingIds)
                    .then(() => applyLocalUpdate(pendingIds, selectedZone.id, selectedZone.color, selectedZone
                        .name));
            }

            cancelSelection();
        };

        // Remove a zone's custom polygon boundary (called from the legend clear button).
        window.clearZoneBoundary = function(zoneId) {
            $wire.clearZonePolygon(zoneId).then(() => {
                const zone = ZONES.find(z => z.id === zoneId);
                if (zone) {
                    zone.geometry = null;
                    zone.has_geometry = false;
                }
                renderStoredPolygons();
            });
        };

        // Switch between ADM2 (sub-counties) and ADM3 (wards). Re-loads the data
        // layer with the appropriate GeoJSON; cache fetched files in-memory.
        window.setLevel = function(level) {
            if (level === currentLevel) {
                return;
            }
            currentLevel = level;
            ZONE_DATA = level === 'adm3' ? ADM3_DATA : ADM2_DATA;
            cancelSelection();
            loadGeoJsonForLevel(level);
        };

        function loadGeoJsonForLevel(level) {
            if (!dataLayer) return;

            // Drop currently-loaded features
            dataLayer.forEach(f => dataLayer.remove(f));

            fetch(GEOJSON_URLS[level])
                .then(r => {
                    if (!r.ok) throw new Error(r.status);
                    return r.json();
                })
                .then(data => {
                    dataLayer.addGeoJson(data);
                    refreshDataLayer();
                    loadedLevels.add(level);
                    if (level === 'adm3') fitMapToData();
                })
                .catch(() => {
                    const c = document.getElementById('zone-map');
                    if (c) c.innerHTML =
                        '<p style="padding:2rem;color:#6b7280;font-size:14px">Failed to load ' + level
                        .toUpperCase() + ' boundaries.</p>';
                });
        }

        window.cancelSelection = function() {
            if (drawnPolygon) {
                drawnPolygon.setMap(null);
                drawnPolygon = null;
            }
            pendingIds = [];
            const bar = document.getElementById('selection-bar');
            if (bar) {
                bar.classList.add('hidden');
                bar.classList.remove('flex');
            }
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
                    d.zone = zoneName;
                    d.color = color;
                }
            }
            refreshDataLayer();
        }

        function refreshDataLayer() {
            if (dataLayer) {
                dataLayer.setStyle(featureStyle);
            }
        }

        function initMap() {
            const container = document.getElementById('zone-map');
            if (!container) return;

            // Kenya bounding box — keeps the map centred on Kenya.
            // strictBounds: false lets the user zoom out freely while
            // preventing the map centre from drifting outside the country.
            const KENYA_BOUNDS = {
                north: 5.0,
                south: -5.0,
                east: 42.0,
                west: 33.5,
            };

            map = new google.maps.Map(container, {
                center: {
                    lat: -0.023,
                    lng: 37.9
                },
                zoom: 6,
                mapTypeId: 'roadmap',
                restriction: {
                    latLngBounds: KENYA_BOUNDS,
                    strictBounds: false,
                },
                mapTypeControl: true,
                mapTypeControlOptions: {
                    style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
                    mapTypeIds: ['roadmap', 'terrain', 'satellite'],
                    position: google.maps.ControlPosition.TOP_RIGHT,
                },
                streetViewControl: false,
                fullscreenControl: true,
                zoomControlOptions: {
                    position: google.maps.ControlPosition.RIGHT_CENTER,
                },
            });

            dataLayer = new google.maps.Data({
                map
            });
            dataLayer.setStyle(featureStyle);

            // Custom cursor-following tooltip — replaces InfoWindow to avoid
            // Google Maps' default chrome (close button, arrows, mismatched border).
            const tooltip = document.createElement('div');
            tooltip.style.cssText = [
                'position:absolute',
                'pointer-events:none',
                'z-index:9999',
                'display:none',
                'background:#fff',
                'border:1px solid #e4e4e7',
                'border-radius:8px',
                'box-shadow:0 4px 12px rgba(0,0,0,.12)',
                'padding:9px 12px 10px',
                'min-width:150px',
                'font-family:system-ui,sans-serif',
            ].join(';');
            document.getElementById('zone-map').style.position = 'relative';
            document.getElementById('zone-map').appendChild(tooltip);

            const mapDiv = document.getElementById('zone-map');

            function showTooltip(x, y, d, name, parent) {
                tooltip.innerHTML = `
                <p style="margin:0 0 1px;font-size:13px;font-weight:600;color:#111827;line-height:1.3">${name}</p>
                <p style="margin:0 0 7px;font-size:11px;color:#6b7280">${parent}</p>
                <div style="display:inline-flex;align-items:center;gap:6px">
                    <span style="width:9px;height:9px;border-radius:2px;background:${d.color};flex-shrink:0"></span>
                    <span style="font-size:11px;font-weight:500;color:#374151">${d.zone}</span>
                </div>`;
                positionTooltip(x, y);
                tooltip.style.display = 'block';
            }

            function positionTooltip(x, y) {
                const mapRect = mapDiv.getBoundingClientRect();
                const tipW = tooltip.offsetWidth || 170;
                const tipH = tooltip.offsetHeight || 70;
                const offset = 14;
                let left = x + offset;
                let top = y - tipH - offset;
                // Flip right→left if overflowing right edge
                if (left + tipW > mapRect.width) {
                    left = x - tipW - offset;
                }
                // Flip above→below if overflowing top
                if (top < 0) {
                    top = y + offset;
                }
                tooltip.style.left = left + 'px';
                tooltip.style.top = top + 'px';
            }

            function hideTooltip() {
                tooltip.style.display = 'none';
            }

            dataLayer.addListener('mouseover', e => {
                // Highlight only in view mode — avoids clashing with drawing highlights.
                if (mode === 'view') {
                    dataLayer.overrideStyle(e.feature, {
                        strokeWeight: 2.5,
                        strokeColor: '#1e293b',
                        fillOpacity: 0.55,
                    });
                }

                const d = ZONE_DATA[e.feature.getProperty('shapeID')];
                if (!d) { return; }

                const name   = currentLevel === 'adm3' ? d.town : d.sub_county;
                const parent = currentLevel === 'adm3' ? `${d.sub_county}, ${d.county}` : d.county;
                const rect   = mapDiv.getBoundingClientRect();
                showTooltip(e.domEvent.clientX - rect.left, e.domEvent.clientY - rect.top, d, name, parent);
            });

            dataLayer.addListener('mousemove', e => {
                const rect = mapDiv.getBoundingClientRect();
                positionTooltip(e.domEvent.clientX - rect.left, e.domEvent.clientY - rect.top);
            });

            dataLayer.addListener('mouseout', () => {
                if (mode === 'view') { dataLayer.revertStyle(); }
                hideTooltip();
            });

            drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: null,
                drawingControl: false,
                polygonOptions: {
                    fillColor: '#6366F1',
                    fillOpacity: 0.15,
                    strokeColor: '#6366F1',
                    strokeWeight: 2,
                    strokeDashArray: [6, 3],
                    editable: true,
                    zIndex: 10,
                },
            });
            drawingManager.setMap(map);

            drawingManager.addListener('overlaycomplete', e => {
                if (drawnPolygon) {
                    drawnPolygon.setMap(null);
                }
                drawnPolygon = e.overlay;
                drawingManager.setDrawingMode(null);

                // ── Custom boundary mode: just show save confirmation ─────────────
                if (mode === 'boundary') {
                    if (!selectedZone) {
                        drawnPolygon.setMap(null);
                        drawnPolygon = null;
                        return;
                    }
                    const color = selectedZone.color;
                    document.getElementById('selection-label').innerHTML =
                        `Save as the delivery boundary for <strong style="color:${color}">${selectedZone.name}</strong>? Customers whose GPS falls inside this polygon will be placed in this zone at checkout.`;
                    document.getElementById('btn-confirm').textContent = 'Save Boundary';
                    const selBar = document.getElementById('selection-bar');
                    selBar.classList.remove('hidden');
                    selBar.classList.add('flex');
                    return;
                }

                // ── Assign areas / clear mode: find areas inside polygon ──────────
                const matches = findRecordsInPolygon(drawnPolygon);
                pendingIds = matches.map(recordIdOf);

                if (!pendingIds.length) {
                    drawnPolygon.setMap(null);
                    drawnPolygon = null;
                    return;
                }

                const matchedIdSet = new Set(pendingIds);
                dataLayer.setStyle(feature => {
                    const d = ZONE_DATA[feature.getProperty('shapeID')];
                    if (d && matchedIdSet.has(recordIdOf(d))) {
                        return {
                            fillColor: '#FBBF24',
                            fillOpacity: 0.8,
                            strokeColor: '#D97706',
                            strokeWeight: 2
                        };
                    }
                    return featureStyle(feature);
                });

                const noun = currentLevel === 'adm3' ? 'wards' : 'sub-counties';
                const label = mode === 'clear' ?
                    `${pendingIds.length} ${noun} will have their zone override cleared.` :
                    `${pendingIds.length} ${noun} will be assigned to <strong>${selectedZone?.name ?? '—'}</strong>.`;

                document.getElementById('selection-label').innerHTML = label;
                document.getElementById('btn-confirm').textContent = mode === 'clear' ? 'Clear Overrides' :
                    'Confirm';
                const selBar = document.getElementById('selection-bar');
                selBar.classList.remove('hidden');
                selBar.classList.add('flex');
            });

            fetch(GEOJSON_URLS[currentLevel])
                .then(r => {
                    if (!r.ok) {
                        throw new Error(r.status);
                    }
                    return r.json();
                })
                .then(data => {
                    dataLayer.addGeoJson(data);
                    loadedLevels.add(currentLevel);
                    fitMapToData();
                    renderStoredPolygons();
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
            const d    = ZONE_DATA[feature.getProperty('shapeID')];
            const hasZone = d?.zone_id != null;

            return {
                // Assigned areas get a light tinted fill; unassigned areas are
                // fully transparent so the base map shows through clearly.
                fillColor:    hasZone ? d.color : '#000000',
                fillOpacity:  hasZone ? 0.2 : 0,
                strokeColor:  hasZone ? d.color : '#9CA3AF',
                strokeWeight: hasZone ? 1.5 : 0.6,
                strokeOpacity: hasZone ? 0.9 : 0.4,
            };
        }

        function fitMapToData() {
            const bounds = new google.maps.LatLngBounds();
            dataLayer.forEach(feature => {
                feature.getGeometry()?.forEachLatLng(latlng => bounds.extend(latlng));
            });
            if (!bounds.isEmpty()) {
                map.fitBounds(bounds, {
                    top: 10,
                    right: 10,
                    bottom: 10,
                    left: 10
                });
            }
        }

        /**
         * Render admin-drawn polygon boundaries as permanent overlays on the map.
         * Each zone with a stored geometry gets a coloured outline.
         * Called on init and after any polygon is saved or cleared.
         */
        function renderStoredPolygons() {
            // Remove existing boundary overlays.
            Object.values(storedPolygons).forEach(p => p.setMap(null));
            storedPolygons = {};

            ZONES.forEach(zone => {
                if (!zone.geometry || zone.geometry.length < 3) {
                    return;
                }

                const path = zone.geometry.map(([lat, lng]) => ({
                    lat,
                    lng
                }));

                const polygon = new google.maps.Polygon({
                    paths: path,
                    fillColor: zone.color,
                    fillOpacity: 0.06,
                    strokeColor: zone.color,
                    strokeWeight: 2.5,
                    strokeOpacity: 1,
                    zIndex: 5,
                    clickable: false,
                });

                polygon.setMap(map);
                storedPolygons[zone.id] = polygon;
            });
        }

        function findRecordsInPolygon(polygon) {
            const matches = [];
            for (const shapeId in ZONE_DATA) {
                const d = ZONE_DATA[shapeId];
                if (!d.centroid?.lat || !d.centroid?.lng) continue;
                const point = new google.maps.LatLng(d.centroid.lat, d.centroid.lng);
                if (google.maps.geometry.poly.containsLocation(point, polygon)) {
                    matches.push(d);
                }
            }
            return matches;
        }

        function loadGoogleMaps(key, cb) {
            if (window.google?.maps?.drawing) {
                return cb();
            }
            const s = document.createElement('script');
            s.src = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=drawing,geometry`;
            s.onload = cb;
            s.onerror = () => {
                const c = document.getElementById('zone-map');
                if (c) c.innerHTML =
                    '<p style="padding:2rem;color:#6b7280;font-size:14px">Google Maps failed to load. Check your API key.</p>';
            };
            document.head.appendChild(s);
        }

        loadGoogleMaps(MAPS_KEY, initMap);
    })();
</script>
