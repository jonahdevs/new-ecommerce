<div class="grid grid-cols-2 gap-5">

    {{-- First Name --}}
    <flux:input wire:model="form.first_name" :label="__('First Name')" placeholder="John" />

    {{-- Last Name --}}
    <flux:input wire:model="form.last_name" :label="__('Last Name')" placeholder="Doe" />

    {{-- Phone Number --}}
    <flux:field>
        <flux:label>{{ __('Phone Number') }}</flux:label>
        <flux:input.group>
            <flux:input.group.prefix>+254</flux:input.group.prefix>
            <flux:input wire:model="form.phone_number" placeholder="Enter Your Phone Number" mask="999 999 999" />
        </flux:input.group>
        <flux:error name="form.phone_number" />
    </flux:field>

    {{-- Alternative Phone Number --}}
    <flux:field>
        <flux:label>{{ __('Alternative Phone Number') }}</flux:label>
        <flux:input.group>
            <flux:input.group.prefix>+254</flux:input.group.prefix>
            <flux:input wire:model="form.alternative_phone_number" placeholder="Enter Your Alternative Phone Number"
                mask="999 999 999" />
        </flux:input.group>
        <flux:error name="form.alternative_phone_number" />
    </flux:field>

    {{-- County --}}
    <flux:select wire:model.live="form.county_id" placeholder="Select County..." :label="__('Region / County')">
        {{-- Explicit null placeholder option --}}
        <flux:select.option value="" selected hidden>
            Select County...
        </flux:select.option>
        @foreach ($this->counties as $zoneName => $zoneCounties)
            <flux:select.option disabled value="">
                -- {{ $zoneName }} --
            </flux:select.option>

            @foreach ($zoneCounties as $county)
                <flux:select.option :value="$county->id">
                    {{ $county->name }}
                </flux:select.option>
            @endforeach
        @endforeach

    </flux:select>

    {{-- Area --}}
    <flux:select wire:model="form.area_id" :label="__('City/Area')">
        <flux:select.option value="" selected hidden>
            {{ $form->county_id ? 'Select Area' : 'Select a county first' }}
        </flux:select.option>
        @foreach ($this->areas as $area)
            <flux:select.option :value="$area->id">
                {{ $area->name }}
            </flux:select.option>
        @endforeach
    </flux:select>
</div>

{{-- Hidden lat/lng — synced to Livewire form --}}
<input type="hidden" wire:model="form.latitude" id="map-lat" />
<input type="hidden" wire:model="form.longitude" id="map-lng" />

{{-- Map --}}
<flux:field>
    <flux:label>{{ __('Pin Your Exact Location') }}</flux:label>
    <p class="text-sm text-zinc-500 mb-2">
        Select your county and area first, then drag the pin to your exact delivery location.
    </p>

    {{-- Mismatch warning --}}
    <div id="map-mismatch-warning"
        class="hidden mb-2 text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded px-3 py-2">
        ⚠️ The pin appears to be outside the selected county. Please reposition it or double-check your county
        selection. You can still save.
    </div>

    <div id="address-map" class="w-full rounded-lg border border-zinc-200 z-0" style="height:350px;"></div>
</flux:field>

{{-- Address --}}
<flux:input wire:model="form.address_text" :label="__('Address')" placeholder="Enter your Address" />

{{-- Additional Info --}}
<flux:textarea wire:model="form.additional_information" :label="__('Additional Information')"
    placeholder="Enter Additional Information" />

@if ($this->hasDefaultAddress)
    <flux:field variant="inline">
        <flux:checkbox wire:model="form.is_default" />
        <flux:label>Set as default Address</flux:label>
    </flux:field>
@endif

@script
    <script>
        // ─── Leaflet CSS ───────────────────────────────────────────────────────────
        if (!document.getElementById('leaflet-css')) {
            const link = document.createElement('link');
            link.id = 'leaflet-css';
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(link);
        }

        // ─── Leaflet JS ────────────────────────────────────────────────────────────
        function loadLeaflet(callback) {
            if (window.L) return callback();
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = callback;
            document.head.appendChild(script);
        }

        loadLeaflet(() => {
            // ── State ──────────────────────────────────────────────────────────────
            const KENYA_CENTER = [-0.0236, 37.9062];
            const KENYA_ZOOM = 6;
            let map, pin, boundaryLayer, nominatimTimer;

            // ── Init map ───────────────────────────────────────────────────────────
            const container = document.getElementById('address-map');
            map = L.map(container, {
                zoomControl: true
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(map);

            // ── Draggable pin ──────────────────────────────────────────────────────
            const pinIcon = L.divIcon({
                className: '',
                html: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ef4444" width="32" height="32">
                     <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                     <circle cx="12" cy="9" r="2.5" fill="white"/>
                   </svg>`,
                iconSize: [32, 32],
                iconAnchor: [16, 32],
            });

            // ── Helpers ────────────────────────────────────────────────────────────
            function placePin(lat, lng) {
                if (pin) {
                    pin.setLatLng([lat, lng]);
                } else {
                    pin = L.marker([lat, lng], {
                        icon: pinIcon,
                        draggable: true
                    }).addTo(map);
                    pin.on('dragend', onPinDragEnd);
                }
            }

            function syncToWire(lat, lng) {
                $wire.set('form.latitude', lat);
                $wire.set('form.longitude', lng);
            }

            function showWarning(show) {
                const el = document.getElementById('map-mismatch-warning');
                if (el) el.classList.toggle('hidden', !show);
            }

            function drawBoundary(geojson) {
                if (boundaryLayer) {
                    map.removeLayer(boundaryLayer);
                    boundaryLayer = null;
                }
                if (!geojson) return;

                try {
                    const data = typeof geojson === 'string' ? JSON.parse(geojson) : geojson;
                    boundaryLayer = L.geoJSON(data, {
                        style: {
                            color: '#3b82f6',
                            weight: 2,
                            opacity: 0.8,
                            fillColor: '#3b82f6',
                            fillOpacity: 0.08,
                        },
                    }).addTo(map);
                } catch (e) {
                    console.warn('Could not draw county boundary:', e);
                }
            }

            function applyMapState(state) {
                if (!state) return;

                // Draw boundary
                drawBoundary(state.boundaryGeojson);

                // If we have a saved pin (edit mode) use that, otherwise use center
                const lat = state.pin?.lat ?? state.center?.lat ?? KENYA_CENTER[0];
                const lng = state.pin?.lng ?? state.center?.lng ?? KENYA_CENTER[1];

                if (state.countyName) {
                    placePin(lat, lng);
                    map.setView([lat, lng], 12);
                    syncToWire(lat, lng);
                    showWarning(false);
                } else {
                    // No county selected — show all of Kenya, remove pin
                    if (pin) {
                        map.removeLayer(pin);
                        pin = null;
                    }
                    map.setView(KENYA_CENTER, KENYA_ZOOM);
                    showWarning(false);
                }
            }

            // ── Nominatim reverse geocode ──────────────────────────────────────────
            async function reverseGeocode(lat, lng) {
                try {
                    const url = `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`;
                    const res = await fetch(url, {
                        headers: {
                            'Accept-Language': 'en'
                        }
                    });
                    const data = await res.json();
                    return data?.address?.county ??
                        data?.address?.state_district ??
                        data?.address?.state ??
                        null;
                } catch {
                    return null;
                }
            }

            function normalise(str) {
                return (str ?? '').toLowerCase().replace(/\s+county$/i, '').trim();
            }

            async function onPinDragEnd(e) {
                const {
                    lat,
                    lng
                } = e.target.getLatLng();
                syncToWire(lat, lng);

                // Debounce Nominatim call
                clearTimeout(nominatimTimer);
                nominatimTimer = setTimeout(async () => {
                    const returnedCounty = await reverseGeocode(lat, lng);
                    const selectedCounty = (await $wire.get('mapState'))?.countyName ?? '';

                    if (returnedCounty) {
                        const mismatch = normalise(returnedCounty) !== normalise(selectedCounty);
                        showWarning(mismatch);
                    }
                }, 800);
            }

            // ── Boot from initial Livewire state ───────────────────────────────────
            $wire.get('mapState').then(state => {
                applyMapState(state);
                // Fix tile rendering after Livewire has finished painting
                setTimeout(() => map.invalidateSize(), 200);
            });

            // ── React to county / area changes ─────────────────────────────────────
            $wire.$watch('form.county_id', () => {
                showWarning(false);
                $wire.get('mapState').then(applyMapState);
            });

            $wire.$watch('form.area_id', () => {
                $wire.get('mapState').then(state => {
                    if (!state?.countyName) return;
                    // Only recenter to area — don't remove the pin
                    const lat = state.center.lat;
                    const lng = state.center.lng;
                    placePin(lat, lng);
                    map.setView([lat, lng], 14);
                    syncToWire(lat, lng);
                    showWarning(false);
                });
            });
        });
    </script>
@endscript
