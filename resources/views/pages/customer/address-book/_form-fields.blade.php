@php
    $cancelHref = $cancelHref ?? null;
    $submitLabel = $submitLabel ?? 'Save Address';
    $mapsKey = config('services.google.maps_key', '');

    $inputClass = 'customer-input font-barlow text-on-surface bg-white placeholder:text-zinc-300';
    $selectArrow =
        "appearance-none bg-[url('data:image/svg+xml,%3Csvg_xmlns=%22http://www.w3.org/2000/svg%22_width=%2210%22_height=%226%22%3E%3Cpath_d=%22M0_0l5_6_5-6z%22_fill=%22%23888%22/%3E%3C/svg%3E')] bg-no-repeat bg-[right_12px_center]";

    $tagBase =
        'px-4 py-1.5 border-[1.5px] border-zinc-200 bg-white text-[11px] font-bold font-barlow tracking-[0.04em] uppercase cursor-pointer transition-all hover:border-zinc-950';
    $tagSelected = 'bg-zinc-950 border-zinc-950 text-white';

    $hasPinnedInit = !empty($form->latitude) ? 'true' : 'false';
    $initCounty = !empty($form->county_id) ? \App\Models\County::find($form->county_id) : null;
    $countyResolvedInit = $initCounty ? 'true' : 'false';
    $countyNameInit = $initCounty ? "'" . addslashes($initCounty->name) . "'" : "''";
@endphp

<div x-data="{
    step: 'map',
    hasPinned: false,
    pinnedText: '',
    countyResolved: false,
    countyResolving: false,
    countyName: '',
}" x-init="hasPinned = {{ $hasPinnedInit }};
countyResolved = {{ $countyResolvedInit }};
countyName = {{ $countyNameInit }};"
    @map-pin-placed.window="hasPinned = true; pinnedText = $event.detail.text; countyResolving = true"
    @county-resolved.window="countyResolved = $event.detail.resolved; countyName = $event.detail.name; countyResolving = false">

    {{-- ══════════════════════════════════════════════════════
         STEP 1 — PIN YOUR LOCATION
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'map'">
        <div class="p-6 space-y-5">

            {{-- Search input (Google Places Autocomplete) --}}
            <x-customer.form-field label="Search location">
                <input type="text" id="map-search-input" placeholder="e.g. Westlands, Nairobi…"
                    class="{{ $inputClass }} flex-1" autocomplete="off">
            </x-customer.form-field>

            {{-- Map --}}
            <div>
                <label class="block text-[10px] font-bold tracking-widest uppercase text-on-surface-variant mb-1.5">📍 Pin your
                    exact delivery location</label>

                <div id="address-map" wire:ignore class="w-full border-[1.5px] border-zinc-200 z-0"
                    style="height:320px;"></div>

                <div
                    class="bg-zinc-50 border-x-[1.5px] border-b-[1.5px] border-zinc-200 p-2.5 flex items-center gap-2 text-[11px] text-on-surface-variant">
                    <flux:icon.information-circle class="size-3 shrink-0" />
                    Click anywhere on the map to drop a delivery pin. Drag the pin to adjust.
                </div>
            </div>


{{-- County NOT resolved — amber warning + fallback select --}}
            <div x-show="hasPinned && !countyResolved && !countyResolving" x-cloak class="space-y-3">
                <div class="bg-amber-50 border-l-[3px] border-amber-500 px-4 py-3 flex items-start gap-2.5">
                    <flux:icon.exclamation-triangle class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" />
                    <div class="min-w-0">
                        <p x-text="pinnedText" class="text-[12px] font-semibold text-on-surface truncate mb-0.5"></p>
                        <p class="text-[11px] text-amber-700">County not detected — please select it below.</p>
                    </div>
                </div>
                <x-customer.form-field label="County" name="form.county_id" :required="true">
                    <select id="addr-county-select" wire:model.live="form.county_id"
                        class="{{ $inputClass }} {{ $selectArrow }}"
                        @change="countyResolved = !!$el.value; countyName = $el.options[$el.selectedIndex]?.text || ''">
                        <option value="">Select County…</option>
                        @foreach ($this->counties as $county)
                            <option value="{{ $county->id }}">{{ $county->name }}</option>
                        @endforeach
                    </select>
                </x-customer.form-field>
            </div>

        </div>

        {{-- Step 1 footer --}}
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-zinc-100">
            @if ($cancelHref)
                <a href="{{ $cancelHref }}" wire:navigate>
                    <flux:button tag="span" variant="customer-outline" size="customer">Cancel</flux:button>
                </a>
            @else
                <flux:button type="button" wire:click="closeModal" variant="customer-outline" size="customer">Cancel
                </flux:button>
            @endif

            <flux:button variant="customer-primary" size="customer-lg" type="button"
                class="inline-flex items-center gap-2"
                x-bind:disabled="!hasPinned || !countyResolved || countyResolving"
                x-bind:class="(!hasPinned || !countyResolved || countyResolving) ? 'opacity-40 cursor-not-allowed!' : ''"
                @click="step = 'form'">
                Continue
                <flux:icon.move-right class="size-4" />
            </flux:button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 2 — DELIVERY DETAILS
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'form'">
        <div class="p-6 space-y-5">

            {{-- Pinned summary bar --}}
            <div class="bg-zinc-100 border-l-[3px] border-primary px-3.5 py-2.5 flex items-start justify-between gap-3">
                <div class="flex items-start gap-2 min-w-0">
                    <flux:icon.map-pin class="w-3.5 h-3.5 text-primary shrink-0 mt-0.5" />
                    <span x-text="pinnedText || 'Location pinned'"
                        class="text-[12px] font-semibold text-on-surface leading-snug"></span>
                </div>
                <button type="button"
                    class="text-[11px] font-bold tracking-[0.06em] uppercase text-primary cursor-pointer hover:opacity-70 transition-opacity shrink-0 whitespace-nowrap bg-none border-none p-0 mt-0.5"
                    @click="step = 'map'; $nextTick(() => { setTimeout(() => window.resizeDeliveryMap?.(), 80); })">
                    Change Pin
                </button>
            </div>

            {{-- Name --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-customer.form-field label="First Name" name="form.first_name" :required="true">
                    <input type="text" wire:model="form.first_name" placeholder="John"
                        class="{{ $inputClass }}{{ $errors->has('form.first_name') ? ' border-red-500' : '' }}">
                </x-customer.form-field>

                <x-customer.form-field label="Last Name" name="form.last_name">
                    <input type="text" wire:model="form.last_name" placeholder="Doe"
                        class="{{ $inputClass }}{{ $errors->has('form.last_name') ? ' border-red-500' : '' }}">
                </x-customer.form-field>
            </div>

            {{-- Phone --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-customer.form-field label="Phone Number" name="form.phone_number" :required="true">
                    <x-slot:prefix>+254</x-slot:prefix>
                    <input type="text" wire:model="form.phone_number" placeholder="712 345 678"
                        class="{{ $inputClass }} border-l-0{{ $errors->has('form.phone_number') ? ' border-red-500' : '' }}">
                </x-customer.form-field>

                <x-customer.form-field label="Alternative Phone (Optional)" name="form.alternative_phone_number">
                    <x-slot:prefix>+254</x-slot:prefix>
                    <input type="text" wire:model="form.alternative_phone_number" placeholder="722 000 000"
                        class="{{ $inputClass }} border-l-0{{ $errors->has('form.alternative_phone_number') ? ' border-red-500' : '' }}">
                </x-customer.form-field>
            </div>

            {{-- Address --}}
            <x-customer.form-field label="Street / Apartment / Office" name="form.address_text" :required="true">
                <input type="text" wire:model="form.address_text" placeholder="e.g. Westlands Road, Apartment 3B"
                    class="{{ $inputClass }}{{ $errors->has('form.address_text') ? ' border-red-500' : '' }}">
            </x-customer.form-field>

            {{-- Delivery instructions --}}
            <x-customer.form-field label="Delivery Instructions (Optional)" name="form.additional_information">
                <textarea wire:model="form.additional_information" rows="3"
                    placeholder="e.g. Green gate, 2nd floor, call on arrival"
                    class="{{ $inputClass }} h-24{{ $errors->has('form.additional_information') ? ' border-red-500' : '' }}"></textarea>
            </x-customer.form-field>

            {{-- Label + default --}}
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <label class="block text-[10px] font-bold tracking-widest uppercase text-on-surface-variant">Label:</label>
                    <div class="flex gap-2">
                        @foreach (['Home', 'Work', 'Other'] as $addrLabel)
                            <button type="button"
                                class="{{ $tagBase }} {{ ($form->label ?? 'Home') === $addrLabel ? $tagSelected : '' }}"
                                wire:click="$set('form.label', '{{ $addrLabel }}')">{{ $addrLabel }}</button>
                        @endforeach
                    </div>
                </div>

                @if ($this->hasDefaultAddress)
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" wire:model="form.is_default" class="w-4 h-4 accent-brand-primary">
                        <span
                            class="text-[12px] font-bold uppercase tracking-widest text-on-surface-variant group-hover:text-on-surface">Set
                            as default</span>
                    </label>
                @endif
            </div>

            {{-- Hidden coordinates --}}
            <input type="hidden" wire:model="form.latitude" />
            <input type="hidden" wire:model="form.longitude" />

        </div>

        {{-- Step 2 footer --}}
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-zinc-100">
            <flux:button type="button" variant="customer-outline" size="customer"
                class="inline-flex items-center gap-2"
                @click="step = 'map'; $nextTick(() => { setTimeout(() => window.resizeDeliveryMap?.(), 80); })">
                <flux:icon.move-left class="size-4" />
                Back to Map
            </flux:button>

            <flux:button variant="customer-primary" size="customer-lg" type="submit">
                {{ $submitLabel }}
            </flux:button>
        </div>
    </div>

</div>

@script
    <script>
        // ── Shared state ──────────────────────────────────────────────────────────
        let map = null, pin = null, infoWindow = null, geocoder = null;
        const KENYA_CENTER = { lat: -1.2921, lng: 36.8219 };
        const MAPS_KEY = @js($mapsKey);
        const PIN_SVG = `<svg viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg"><path d="M16 0C7.163 0 0 7.163 0 16c0 10 16 24 16 24S32 26 32 16C32 7.163 24.837 0 16 0z" fill="#FF4500"/><circle cx="16" cy="16" r="7" fill="white"/><circle cx="16" cy="16" r="4" fill="#FF4500"/></svg>`;

        // ── CSS injections ────────────────────────────────────────────────────────

        if (!document.getElementById('address-map-pac-css')) {
            const style = document.createElement('style');
            style.id = 'address-map-pac-css';
            style.textContent = `
                .pac-container {
                    z-index: 9999 !important;
                    border-radius: 0 !important;
                    border: 1.5px solid #e4e4e7 !important;
                    border-top: none !important;
                    box-shadow: 4px 4px 0 rgba(0,0,0,.08) !important;
                    font-family: var(--font-barlow, sans-serif) !important;
                }
                .pac-item {
                    font-size: 12px !important;
                    padding: 8px 12px !important;
                    cursor: pointer !important;
                    border-top: 1px solid #f4f4f5 !important;
                }
                .pac-item:hover, .pac-item-selected { background: #f4f4f5 !important; }
                .pac-item-query { font-weight: 600 !important; color: #18181b !important; }
                .pac-matched { font-weight: 700 !important; }
                .pac-icon { display: none !important; }
                .gm-style-iw-c { border-radius: 0 !important; padding: 0 !important; }
                .gm-style-iw-d { padding: 8px 12px !important; font-size: 12px !important; font-weight: 600 !important; line-height: 1.5 !important; overflow: auto !important; }
                .gm-style-iw-chr { display: none !important; }
            `;
            document.head.appendChild(style);
        }

        // ── Loader ────────────────────────────────────────────────────────────────

        function loadGoogleMaps(key, cb) {
            if (window.google?.maps?.places) { return cb(); }
            const s = document.createElement('script');
            s.src = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=places`;
            s.onload = cb;
            document.head.appendChild(s);
        }

        // ── Helpers ───────────────────────────────────────────────────────────────

        window.resizeDeliveryMap = () => {
            if (window.deliveryMap && window.google?.maps) {
                google.maps.event.trigger(window.deliveryMap, 'resize');
            }
        };

        function placePin(lat, lng) {
            if (!window.google?.maps || !map) { return; }

            if (pin) {
                pin.setPosition({ lat, lng });
            } else {
                pin = new google.maps.Marker({
                    position: { lat, lng },
                    map,
                    draggable: true,
                    icon: {
                        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(PIN_SVG),
                        scaledSize: new google.maps.Size(32, 40),
                        anchor: new google.maps.Point(16, 40),
                    },
                });
                pin.addListener('dragend', (e) => {
                    const lat = e.latLng.lat();
                    const lng = e.latLng.lng();
                    reverseGeocode(lat, lng, (countyRaw) => dropPin(lat, lng, countyRaw));
                });
            }

            if (!infoWindow) { infoWindow = new google.maps.InfoWindow(); }
        }

        function showInfoWindow(text) {
            if (!infoWindow || !pin || !map) { return; }
            infoWindow.setContent(`<div>📍 <b>Delivery here</b><br>${text}</div>`);
            infoWindow.open({ map, anchor: pin });
        }

        function fallbackCountyFromName(countyRaw) {
            if (!countyRaw) {
                window.dispatchEvent(new CustomEvent('county-resolved', { detail: { resolved: false, name: '' } }));
                return;
            }
            $wire.call('resolveCountyFromName', countyRaw).then(result => {
                window.dispatchEvent(new CustomEvent('county-resolved', {
                    detail: { resolved: !!result, name: result?.name || '' }
                }));
            });
        }

        function dropPin(lat, lng, geocodedCountyRaw) {
            $wire.call('pinDropped', lat, lng).then(result => {
                if (result.countyResolved) {
                    window.dispatchEvent(new CustomEvent('county-resolved', {
                        detail: { resolved: true, name: result.countyName }
                    }));
                } else {
                    fallbackCountyFromName(geocodedCountyRaw || '');
                }
            });
        }

        // ── Reverse geocoding (Google Geocoder) ─── display text only ─────────────

        function reverseGeocode(lat, lng, onCountyRaw) {
            if (!geocoder) { geocoder = new google.maps.Geocoder(); }

            geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                if (status !== 'OK' || !results?.length) {
                    const fallback = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                    showInfoWindow(fallback);
                    window.dispatchEvent(new CustomEvent('map-pin-placed', { detail: { text: fallback } }));
                    if (onCountyRaw) { onCountyRaw(''); }
                    return;
                }

                const comps = results[0].address_components;
                const get = (type) => comps.find(c => c.types.includes(type))?.long_name || '';

                const countyRaw = get('administrative_area_level_1');
                const road = get('route') || get('establishment') || get('point_of_interest');
                const suburb = get('sublocality_level_1') || get('neighborhood');
                const city = get('locality');
                const parts = [road, suburb, city].filter(Boolean);
                const shortDisp = parts.length
                    ? parts.join(', ')
                    : (results[0].formatted_address || `${lat.toFixed(5)}, ${lng.toFixed(5)}`);

                showInfoWindow(shortDisp);
                window.dispatchEvent(new CustomEvent('map-pin-placed', { detail: { text: shortDisp } }));
                if (onCountyRaw) { onCountyRaw(countyRaw); }
            });
        }

        // ── Map setup ─────────────────────────────────────────────────────────────

        function setupMap() {
            const container = document.getElementById('address-map');
            if (!container) { return; }

            if (map && (!document.body.contains(map.getDiv()) || map.getDiv() !== container)) {
                map = null;
                pin = null;
                infoWindow = null;
            }

            if (!map) {
                map = new google.maps.Map(container, {
                    center: KENYA_CENTER,
                    zoom: 13,
                    mapTypeId: 'roadmap',
                    zoomControl: true,
                    streetViewControl: false,
                    mapTypeControl: false,
                    fullscreenControl: false,
                    clickableIcons: false,
                });
                window.deliveryMap = map;

                map.addListener('click', (e) => {
                    const lat = e.latLng.lat();
                    const lng = e.latLng.lng();
                    placePin(lat, lng);
                    reverseGeocode(lat, lng, (countyRaw) => dropPin(lat, lng, countyRaw));
                });
            }

            setTimeout(() => {
                google.maps.event.trigger(map, 'resize');
                $wire.call('getMapState').then(state => {
                    if (state?.pin?.lat) {
                        map.setCenter({ lat: state.pin.lat, lng: state.pin.lng });
                        map.setZoom(15);
                        placePin(state.pin.lat, state.pin.lng);
                        reverseGeocode(state.pin.lat, state.pin.lng, null);
                    } else {
                        if (pin) { pin.setMap(null); pin = null; }
                        map.setCenter(KENYA_CENTER);
                        map.setZoom(13);
                    }
                });
            }, 150);
        }

        // ── Autocomplete setup ────────────────────────────────────────────────────

        function setupAutocomplete() {
            const input = document.getElementById('map-search-input');
            if (!input || input._googleAutocomplete) { return; }
            input._googleAutocomplete = true;

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); }
            });

            const ac = new google.maps.places.Autocomplete(input, {
                componentRestrictions: { country: 'ke' },
                fields: ['geometry', 'address_components', 'formatted_address', 'name'],
            });

            ac.addListener('place_changed', () => {
                const place = ac.getPlace();
                if (!place.geometry?.location) { return; }

                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();

                const comps = place.address_components || [];
                const get = (type) => comps.find(c => c.types.includes(type))?.long_name || '';

                const countyRaw = get('administrative_area_level_1');
                const city = get('locality') || get('administrative_area_level_2') || '';
                const displayText = place.name
                    ? [place.name, city].filter(Boolean).join(', ')
                    : (place.formatted_address || `${lat.toFixed(5)}, ${lng.toFixed(5)}`);

                if (map) { map.setCenter({ lat, lng }); map.setZoom(16); }
                placePin(lat, lng);
                showInfoWindow(displayText);
                window.dispatchEvent(new CustomEvent('map-pin-placed', { detail: { text: displayText } }));
                dropPin(lat, lng, countyRaw);
            });
        }

        // ── Bootstrap ─────────────────────────────────────────────────────────────

        loadGoogleMaps(MAPS_KEY, () => {
            $wire.on('address-modal-opened', () => {
                setupMap();
                setupAutocomplete();
            });

            if (document.getElementById('address-map')) { setupMap(); }
            setupAutocomplete();
        });
    </script>
@endscript
