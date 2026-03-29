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
        <flux:select.option value="" selected hidden>
            Select County...
        </flux:select.option>
        @foreach ($this->counties as $county)
            <flux:select.option :value="$county->id">
                {{ $county->name }}
            </flux:select.option>
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
        Select your county and area first, then click or drag the pin to your exact delivery location.
    </p>

    {{-- Mismatch warning --}}
    <div id="map-mismatch-warning"
        class="hidden mb-2 text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded px-3 py-2">
        ⚠️ The pin appears to be outside the selected county. Please reposition it or double-check your county
        selection. You can still save.
    </div>


    <div id="address-map" wire:ignore class="w-full rounded-lg border border-zinc-200 z-0" style="height:350px;">
    </div>
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
            script.onerror = () => console.error('Failed to load Leaflet');
            document.head.appendChild(script);
        }

        loadLeaflet(() => {
            console.log('Leaflet loaded:', typeof L !== 'undefined');

            const KENYA_CENTER = [-0.0236, 37.9062];
            const KENYA_ZOOM = 6;
            let map, pin, boundaryLayer, nominatimTimer;

            // ── Find container ─────────────────────────────────────────────────────
            const container = document.getElementById('address-map');
            if (!container) {
                console.error('Map container #address-map not found');
                return;
            }
            console.log('Map container found:', container.offsetHeight + 'px tall');

            // ── Init map ───────────────────────────────────────────────────────────
            map = L.map(container, {
                zoomControl: true
            });

            // ── Click to reposition pin ────────────────────────────────────────────
            map.on('click', (e) => {
                const {
                    lat,
                    lng
                } = e.latlng;
                placePin(lat, lng);
                syncToWire(lat, lng);

                // Run the same county validation as drag
                clearTimeout(nominatimTimer);
                nominatimTimer = setTimeout(async () => {
                    const returnedValues = await reverseGeocode(lat, lng);
                    const state = await $wire.call('getMapState');
                    const selectedCounty = normalise(state?.countyName ?? '');

                    if (returnedValues.length > 0) {
                        const matched = returnedValues.some(val => {
                            const n = normalise(val);
                            return n === selectedCounty || n.includes(selectedCounty) ||
                                selectedCounty.includes(n);
                        });
                        showWarning(!matched);
                    }
                }, 800);
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(map);

            // Set initial view immediately so tiles start loading
            map.setView(KENYA_CENTER, KENYA_ZOOM);

            // Fix size after paint
            setTimeout(() => map.invalidateSize(), 300);

            const observer = new ResizeObserver(() => map.invalidateSize());
            observer.observe(container);

            // ── Pin icon ───────────────────────────────────────────────────────────
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
                            fillOpacity: 0.08
                        },
                    }).addTo(map);
                } catch (e) {
                    console.warn('Could not draw county boundary:', e);
                }
            }

            function applyMapState(state) {
                console.log('applyMapState:', state);
                if (!state) return;

                drawBoundary(state.boundaryGeojson);

                const lat = state.pin?.lat ?? state.center?.lat ?? KENYA_CENTER[0];
                const lng = state.pin?.lng ?? state.center?.lng ?? KENYA_CENTER[1];

                if (state.countyName) {
                    placePin(lat, lng);
                    map.setView([lat, lng], 12);
                    syncToWire(lat, lng);
                    showWarning(false);
                } else {
                    if (pin) {
                        map.removeLayer(pin);
                        pin = null;
                    }
                    map.setView(KENYA_CENTER, KENYA_ZOOM);
                    showWarning(false);
                }

                setTimeout(() => map.invalidateSize(), 100);
            }

            // ── Nominatim ─────────────────────────────────────────────────────────
            async function reverseGeocode(lat, lng) {
                try {
                    const res = await fetch(
                        `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`, {
                            headers: {
                                'Accept-Language': 'en'
                            }
                        });
                    const data = await res.json();
                    console.log('Nominatim response:', data?.address);

                    // Nominatim can return the county in different fields depending on the area
                    return [
                        data?.address?.county,
                        data?.address?.state_district,
                        data?.address?.state,
                        data?.address?.region,
                    ].filter(Boolean);
                } catch {
                    return [];
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
                clearTimeout(nominatimTimer);
                nominatimTimer = setTimeout(async () => {
                    const returnedValues = await reverseGeocode(lat, lng);
                    const state = await $wire.call('getMapState');
                    const selectedCounty = normalise(state?.countyName ?? '');

                    if (returnedValues.length > 0) {
                        // Check if ANY of the returned values contain or match the selected county
                        const matched = returnedValues.some(val => {
                            const n = normalise(val);
                            return n === selectedCounty ||
                                n.includes(selectedCounty) ||
                                selectedCounty.includes(n);
                        });
                        showWarning(!matched);
                    }
                }, 800);
            }

            // ── Boot ──────────────────────────────────────────────────────────────
            $wire.call('getMapState').then(state => {
                if (state?.countyName) {
                    // County already selected (edit mode) — use that
                    applyMapState(state);
                } else {
                    // No county selected — center + pin on GPS
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                const lat = position.coords.latitude;
                                const lng = position.coords.longitude;
                                map.setView([lat, lng], 15);
                                placePin(lat, lng);
                                syncToWire(lat, lng);

                                clearTimeout(nominatimTimer);
                                nominatimTimer = setTimeout(async () => {
                                    const returnedValues = await reverseGeocode(lat, lng);
                                    const state = await $wire.call('getMapState');
                                    const selectedCounty = normalise(state?.countyName ??
                                        '');

                                    if (returnedValues.length > 0 && selectedCounty) {
                                        const matched = returnedValues.some(val => {
                                            const n = normalise(val);
                                            return n === selectedCounty || n
                                                .includes(selectedCounty) ||
                                                selectedCounty.includes(n);
                                        });
                                        showWarning(!matched);
                                    }
                                }, 800);
                            },
                            () => {
                                // GPS denied — fall back to Kenya center, no pin
                                map.setView(KENYA_CENTER, KENYA_ZOOM);
                            }, {
                                enableHighAccuracy: true,
                                timeout: 5000,
                                maximumAge: 0
                            }
                        );
                    } else {
                        map.setView(KENYA_CENTER, KENYA_ZOOM);
                    }
                }
            });

            // ── Watchers ──────────────────────────────────────────────────────────
            $wire.$watch('form.county_id', () => {
                showWarning(false);
                $wire.call('getMapState').then(applyMapState);
            });

            $wire.$watch('form.area_id', () => {
                $wire.call('getMapState').then(state => {
                    if (!state?.countyName) return;
                    placePin(state.center.lat, state.center.lng);
                    map.setView([state.center.lat, state.center.lng], 14);
                    syncToWire(state.center.lat, state.center.lng);
                    showWarning(false);
                });
            });
        });
    </script>
@endscript
