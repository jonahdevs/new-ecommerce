<?php

use App\Models\ShippingZone;
use App\Models\SubCounty;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;

new #[Title('Zone Map')] #[Layout('layouts.app')] class extends Component {

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

    #[Computed]
    public function zones(): \Illuminate\Support\Collection
    {
        return ShippingZone::orderBy('id')->get();
    }

    #[Computed]
    public function zoneColors(): array
    {
        $colors = [];

        foreach ($this->zones as $i => $zone) {
            $colors[$zone->id] = $this->palette[$i % count($this->palette)];
        }

        return $colors;
    }

    /**
     * @return array<string, array{sub_county_id: int, sub_county: string, county: string, zone_id: int|null, zone: string, color: string, centroid: array{lat: float, lng: float}}>
     */
    #[Computed]
    public function mapData(): array
    {
        $zoneColors = $this->zoneColors;
        $zones      = $this->zones->keyBy('id');
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
     * @return array<int, array{id: int, name: string, color: string, available: bool}>
     */
    #[Computed]
    public function zonesForSelect(): array
    {
        $colors = $this->zoneColors;

        return $this->zones->map(fn ($z, $i) => [
            'id'        => $z->id,
            'name'      => $z->name,
            'color'     => $colors[$z->id],
            'available' => $z->is_delivery_available,
        ])->values()->toArray();
    }

    public function assignZoneToSubCounties(int $zoneId, array $subCountyIds): void
    {
        $zone = ShippingZone::findOrFail($zoneId);

        SubCounty::whereIn('id', array_map('intval', $subCountyIds))
            ->update(['shipping_zone_id' => $zone->id]);

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

        $this->dispatch('notify',
            title:   'Overrides Cleared',
            variant: 'success',
            message: count($subCountyIds).' sub-counties will now inherit from their county zone.',
        );
    }
}; ?>


<div class="p-6">
    <div class="mb-5">
        <flux:heading size="xl">Zone Map</flux:heading>
        <flux:subheading>Visualise and assign shipping zones. Draw a polygon on the map to bulk-assign sub-counties to a zone.</flux:subheading>
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-3 mb-4">

        {{-- Zone picker --}}
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400 shrink-0">Active zone:</span>
            @foreach ($this->zonesForSelect as $zone)
                <button type="button"
                    data-zone-id="{{ $zone['id'] }}"
                    data-zone-name="{{ $zone['name'] }}"
                    data-zone-color="{{ $zone['color'] }}"
                    onclick="selectZone(this)"
                    class="zone-btn flex items-center gap-1.5 px-3 py-1.5 rounded-full border-2 border-transparent text-sm font-medium transition-all cursor-pointer"
                    style="background: {{ $zone['color'] }}1a; color: {{ $zone['color'] }}; border-color: transparent">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $zone['color'] }}"></span>
                    {{ $zone['name'] }}
                </button>
            @endforeach
        </div>

        <div class="flex-1"></div>

        {{-- Mode buttons (plain HTML for reliable JS active-state toggling) --}}
        <div class="flex items-center rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden text-sm font-medium">
            <button type="button" id="btn-view" onclick="setMode('view')"
                class="mode-btn flex items-center gap-1.5 px-3 py-1.5 transition-colors cursor-pointer bg-zinc-800 text-white">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                View
            </button>
            <button type="button" id="btn-draw" onclick="setMode('draw')"
                class="mode-btn flex items-center gap-1.5 px-3 py-1.5 transition-colors cursor-pointer border-l border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                Draw Zone
            </button>
            <button type="button" id="btn-clear" onclick="setMode('clear')"
                class="mode-btn flex items-center gap-1.5 px-3 py-1.5 transition-colors cursor-pointer border-l border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
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

    {{-- Map + Legend --}}
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-4 items-start">

        <flux:card class="xl:col-span-3 p-0 overflow-hidden">
            <div class="relative">
                {{-- Mode indicator overlay (shown when not in view mode) --}}
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

        {{-- Legend --}}
        <div class="space-y-4">
            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-3">Zones</flux:heading>
                <div class="space-y-2.5">
                    @foreach ($this->zonesForSelect as $zone)
                        <div class="flex items-center gap-3">
                            <span class="w-3.5 h-3.5 rounded-sm shrink-0"
                                style="background:{{ $zone['color'] }}"></span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium leading-tight text-zinc-800 dark:text-zinc-200">
                                    {{ $zone['name'] }}</p>
                                <p class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-0.5">
                                    {{ $zone['available'] ? 'Delivery available' : 'Not yet available' }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                    <div class="flex items-center gap-3">
                        <span class="w-3.5 h-3.5 rounded-sm shrink-0" style="background:#9CA3AF"></span>
                        <p class="text-sm text-zinc-400 dark:text-zinc-500">No Zone Assigned</p>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 space-y-3">
                <flux:heading size="sm">How to use</flux:heading>
                <div class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed space-y-2">
                    <p><strong>View mode</strong> — hover any sub-county to see its zone.</p>
                    <p><strong>Draw Zone</strong> — pick a zone above, then draw a polygon. All sub-counties whose centres fall inside will be assigned to that zone.</p>
                    <p><strong>Clear Override</strong> — draw a polygon to reset sub-counties to inherit from their county zone.</p>
                </div>
            </flux:card>

            <div class="flex flex-col gap-2">
                <flux:button :href="route('admin.logistics.configuration.locations.sub-counties')"
                    wire:navigate variant="outline" size="sm" class="w-full">
                    Manage Sub-County Rows
                </flux:button>
                <flux:button :href="route('admin.logistics.configuration.locations.counties')"
                    wire:navigate variant="outline" size="sm" class="w-full">
                    Manage County Zones
                </flux:button>
            </div>
        </div>
    </div>

</div>

{{-- Data island: Blade processes this; Livewire ignores it (type != text/javascript) --}}
<script type="application/json" id="zone-map-payload">{!! json_encode([
    'wireId'     => $this->getId(),
    'mapData'    => $this->mapData,
    'zones'      => $this->zonesForSelect,
    'mapsKey'    => config('services.google.maps_key', ''),
    'geojsonUrl' => asset('maps/geoBoundaries-KEN-ADM2_simplified.geojson'),
]) !!}</script>

<script>
(function () {
    // ── Data from server ──────────────────────────────────────────────────────
    const _d          = JSON.parse(document.getElementById('zone-map-payload').textContent);
    const WIRE_ID     = _d.wireId;
    const ZONE_DATA   = _d.mapData;
    const ZONES       = _d.zones;
    const MAPS_KEY    = _d.mapsKey;
    const GEOJSON_URL = _d.geojsonUrl;

    // ── State ─────────────────────────────────────────────────────────────────
    let map            = null;
    let dataLayer      = null;
    let drawingManager = null;
    let drawnPolygon   = null;
    let mode           = 'view'; // 'view' | 'draw' | 'clear'
    let selectedZone   = ZONES[0] ?? null;
    let pendingIds     = [];     // sub_county_ids to assign

    // ── Zone selector ─────────────────────────────────────────────────────────
    window.selectZone = function (btn) {
        document.querySelectorAll('.zone-btn').forEach(b => {
            b.style.borderColor = 'transparent';
            b.style.fontWeight  = '500';
        });
        btn.style.borderColor = btn.dataset.zoneColor;
        btn.style.fontWeight  = '700';

        selectedZone = { id: +btn.dataset.zoneId, name: btn.dataset.zoneName, color: btn.dataset.zoneColor };

        // Refresh indicator if in draw mode
        if (mode === 'draw') {
            const indicator = document.getElementById('mode-indicator');
            if (indicator) {
                indicator.textContent = '✏ Draw a polygon to assign zone: ' + selectedZone.name;
                indicator.style.background = selectedZone.color;
            }
        }
    };

    // Activate first zone button — script is inline after DOM, so no DOMContentLoaded needed
    const firstZoneBtn = document.querySelector('.zone-btn');
    if (firstZoneBtn) { selectZone(firstZoneBtn); }

    // ── Mode management ───────────────────────────────────────────────────────
    const MODE_STYLES = {
        active:   'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-900',
        inactive: 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800',
    };

    window.setMode = function (newMode) {
        cancelSelection();
        mode = newMode;

        // Update button active state
        ['view', 'draw', 'clear'].forEach(m => {
            const btn = document.getElementById('btn-' + m);
            const isActive = mode === m;
            btn.className = btn.className
                .replace(MODE_STYLES.active, '')
                .replace(MODE_STYLES.inactive, '')
                .trim();
            btn.classList.add(...(isActive ? MODE_STYLES.active : MODE_STYLES.inactive).split(' '));
        });

        // Mode indicator overlay
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

        if (mode !== 'view') {
            dataLayer.revertStyle();
        }

        if (! drawingManager) { return; }

        if (mode === 'draw' || mode === 'clear') {
            drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
        } else {
            drawingManager.setDrawingMode(null);
        }
    };

    // ── Selection confirmation ────────────────────────────────────────────────
    window.confirmAssignment = function () {
        if (! pendingIds.length) { return; }

        const bar = document.getElementById('selection-bar');
        bar.classList.add('hidden');

        if (mode === 'clear') {
            Livewire.find(WIRE_ID)
                .call('clearZoneForSubCounties', pendingIds)
                .then(() => { applyLocalUpdate(pendingIds, null, '#9CA3AF', 'No Zone'); });
        } else {
            Livewire.find(WIRE_ID)
                .call('assignZoneToSubCounties', selectedZone.id, pendingIds)
                .then(() => { applyLocalUpdate(pendingIds, selectedZone.id, selectedZone.color, selectedZone.name); });
        }

        cancelSelection();
    };

    window.cancelSelection = function () {
        if (drawnPolygon) { drawnPolygon.setMap(null); drawnPolygon = null; }
        pendingIds = [];
        document.getElementById('selection-bar').classList.add('hidden');
        refreshDataLayer();
    };

    // Update ZONE_DATA in-memory and redraw styles
    function applyLocalUpdate(subCountyIds, zoneId, color, zoneName) {
        const idSet = new Set(subCountyIds.map(Number));

        for (const shapeId in ZONE_DATA) {
            const d = ZONE_DATA[shapeId];
            if (idSet.has(d.sub_county_id)) {
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

    // ── Map init ──────────────────────────────────────────────────────────────
    function initMap() {
        map = new google.maps.Map(document.getElementById('zone-map'), {
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

        // ── Data Layer (sub-county polygons) ──────────────────────────────────
        dataLayer = new google.maps.Data({ map });

        dataLayer.setStyle(featureStyle);

        dataLayer.addListener('mouseover', e => {
            if (mode !== 'view') { return; }
            dataLayer.overrideStyle(e.feature, { strokeWeight: 2.5, strokeColor: '#1e293b', fillOpacity: 0.55 });
        });

        dataLayer.addListener('mouseout', () => {
            if (mode !== 'view') { return; }
            dataLayer.revertStyle();
        });

        // ── Drawing Manager ───────────────────────────────────────────────────
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

            const matches = findSubCountiesInPolygon(drawnPolygon);
            pendingIds = matches.map(d => d.sub_county_id);

            if (! pendingIds.length) {
                drawnPolygon.setMap(null);
                drawnPolygon = null;
                return;
            }

            // Highlight matched sub-counties on the Data Layer
            const matchedIdSet = new Set(pendingIds);
            dataLayer.setStyle(feature => {
                const d = ZONE_DATA[feature.getProperty('shapeID')];
                if (d && matchedIdSet.has(d.sub_county_id)) {
                    return { fillColor: '#FBBF24', fillOpacity: 0.8, strokeColor: '#D97706', strokeWeight: 2 };
                }
                return featureStyle(feature);
            });

            // Show confirmation bar
            const label = mode === 'clear'
                ? `${pendingIds.length} sub-counties will have their zone override cleared (inherit from county).`
                : `${pendingIds.length} sub-counties will be assigned to <strong>${selectedZone?.name ?? '—'}</strong>.`;

            document.getElementById('selection-label').innerHTML = label;
            document.getElementById('btn-confirm').textContent = mode === 'clear' ? 'Clear Overrides' : 'Assign Zone';
            document.getElementById('selection-bar').classList.remove('hidden');

            // Stop drawing mode until confirmed/cancelled
            drawingManager.setDrawingMode(null);
        });

        // ── Load GeoJSON ──────────────────────────────────────────────────────
        fetch(GEOJSON_URL)
            .then(r => { if (! r.ok) { throw new Error(r.status); } return r.json(); })
            .then(data => {
                dataLayer.addGeoJson(data);
                fitMapToData();
            })
            .catch(() => {
                document.getElementById('zone-map').innerHTML = `
                    <div style="display:flex;align-items:center;justify-content:center;height:100%;text-align:center;color:#6b7280;font-size:14px;padding:2rem">
                        <div>
                            <p style="font-weight:600;margin-bottom:4px">Map data not available</p>
                            <p style="font-size:12px">Run <code>php artisan db:seed --class=CountyCoordinatesSeeder</code> to load sub-county boundaries.</p>
                        </div>
                    </div>`;
            });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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

    function findSubCountiesInPolygon(polygon) {
        const matches = [];

        for (const shapeId in ZONE_DATA) {
            const d = ZONE_DATA[shapeId];
            if (! d.centroid?.lat || ! d.centroid?.lng) { continue; }

            const point = new google.maps.LatLng(d.centroid.lat, d.centroid.lng);

            if (google.maps.geometry.poly.containsLocation(point, polygon)) {
                matches.push(d);
            }
        }

        return matches;
    }

    // ── Bootstrap ─────────────────────────────────────────────────────────────
    function loadGoogleMaps(key, cb) {
        if (window.google?.maps?.drawing) { return cb(); }
        const s  = document.createElement('script');
        s.src    = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=drawing,geometry`;
        s.onload = cb;
        s.onerror = () => {
            document.getElementById('zone-map').innerHTML =
                '<p style="padding:2rem;color:#6b7280;font-size:14px">Google Maps failed to load. Check your API key.</p>';
        };
        document.head.appendChild(s);
    }

    loadGoogleMaps(MAPS_KEY, initMap);
})();
</script>
