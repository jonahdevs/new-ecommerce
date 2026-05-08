@php
    $cancelHref = $cancelHref ?? null;
    $submitLabel = $submitLabel ?? 'Save Address';

    $inputClass =
        'w-full border-[1.5px] border-zinc-200 px-3 py-2.5 text-[13px] font-medium font-barlow transition-colors outline-none text-zinc-950 bg-white placeholder:text-zinc-300 focus:border-brand-primary';
    $labelClass = 'block text-[11px] font-bold tracking-[0.08em] uppercase text-zinc-500 mb-1.5';
    $errorClass = 'text-red-500 text-[11px] font-medium mt-1';
    $selectArrow =
        "appearance-none bg-[url('data:image/svg+xml,%3Csvg_xmlns=%22http://www.w3.org/2000/svg%22_width=%2210%22_height=%226%22%3E%3Cpath_d=%22M0_0l5_6_5-6z%22_fill=%22%23888%22/%3E%3C/svg%3E')] bg-no-repeat bg-[right_12px_center]";

    $tagBase =
        'px-4 py-1.5 border-[1.5px] border-zinc-200 bg-white text-[11px] font-bold font-barlow tracking-[0.04em] uppercase cursor-pointer transition-all hover:border-zinc-950';
    $tagSelected = 'bg-zinc-950 border-zinc-950 text-white';

    $hasPinnedInit      = ! empty($form->latitude) ? 'true' : 'false';
    $initCounty         = ! empty($form->county_id) ? \App\Models\County::find($form->county_id) : null;
    $countyResolvedInit = $initCounty ? 'true' : 'false';
    $countyNameInit     = $initCounty ? "'" . addslashes($initCounty->name) . "'" : "''";
@endphp

<div
    x-data="{
        step: 'map',
        hasPinned: false,
        pinnedText: '',
        countyResolved: false,
        countyResolving: false,
        countyName: '',
        searchNotFound: false,
    }"
    x-init="
        hasPinned      = {{ $hasPinnedInit }};
        countyResolved = {{ $countyResolvedInit }};
        countyName     = {{ $countyNameInit }};
    "
    @map-pin-placed.window="hasPinned = true; pinnedText = $event.detail.text; searchNotFound = false; countyResolving = true"
    @county-resolved.window="countyResolved = $event.detail.resolved; countyName = $event.detail.name; countyResolving = false"
    @map-search-not-found.window="searchNotFound = true"
>

    {{-- ══════════════════════════════════════════════════════
         STEP 1 — PIN YOUR LOCATION
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'map'">
        <div class="p-6 space-y-5">

            {{-- Search input --}}
            <div>
                <label class="{{ $labelClass }}">Search location</label>
                <div class="flex">
                    <input
                        type="text"
                        id="map-search-input"
                        placeholder="e.g. Westlands, Nairobi…"
                        class="{{ $inputClass }} flex-1"
                        @keydown.enter.prevent="$dispatch('do-map-search')"
                    >
                    <button
                        type="button"
                        class="px-4 bg-zinc-950 text-white hover:bg-primary transition-colors shrink-0 border-[1.5px] border-l-0 border-zinc-950"
                        @click="$dispatch('do-map-search')"
                        title="Search"
                    >
                        <flux:icon.magnifying-glass class="size-4" />
                    </button>
                </div>
                <p x-show="searchNotFound" x-cloak class="{{ $errorClass }} mt-1.5">
                    Location not found. Try a different search.
                </p>
            </div>

            {{-- Map --}}
            <div>
                <label class="{{ $labelClass }}">📍 Pin your exact delivery location</label>
                <p class="text-[12px] text-zinc-500 mb-3 leading-relaxed">
                    Search or click anywhere on the map. Your county is detected automatically from the pin.
                </p>

                <div id="address-map" wire:ignore class="w-full border-[1.5px] border-zinc-200 z-0 bg-zinc-100"
                    style="height:320px;"></div>

                <div class="bg-zinc-50 border-x-[1.5px] border-b-[1.5px] border-zinc-200 p-2.5 flex items-center gap-2 text-[11px] text-zinc-500">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    Click anywhere on the map to drop a delivery pin. Drag the pin to adjust.
                </div>
            </div>

            {{-- Detecting in-flight --}}
            <div x-show="countyResolving" x-cloak
                class="flex items-center gap-2.5 px-4 py-3 bg-zinc-50 border-l-[3px] border-zinc-300">
                <svg class="w-4 h-4 text-zinc-400 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" />
                </svg>
                <span class="text-[12px] font-medium text-zinc-500">Detecting location…</span>
            </div>

            {{-- County resolved — success bar --}}
            <div x-show="hasPinned && countyResolved && !countyResolving" x-cloak
                class="bg-green-50 border-l-[3px] border-green-500 px-4 py-3 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-green-500 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                <div class="min-w-0">
                    <p x-text="pinnedText" class="text-[12px] font-semibold text-zinc-700 truncate"></p>
                    <p class="text-[11px] text-green-700 font-bold mt-0.5">
                        County detected: <span x-text="countyName"></span>
                    </p>
                </div>
            </div>

            {{-- County NOT resolved — amber warning + fallback select --}}
            <div x-show="hasPinned && !countyResolved && !countyResolving" x-cloak class="space-y-3">
                <div class="bg-amber-50 border-l-[3px] border-amber-500 px-4 py-3 flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path
                            d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                        <line x1="12" y1="9" x2="12" y2="13" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                    <div class="min-w-0">
                        <p x-text="pinnedText" class="text-[12px] font-semibold text-zinc-700 truncate mb-0.5"></p>
                        <p class="text-[11px] text-amber-700">County not detected — please select it below.</p>
                    </div>
                </div>
                <div>
                    <label class="{{ $labelClass }}">County *</label>
                    <select
                        id="addr-county-select"
                        wire:model.live="form.county_id"
                        class="{{ $inputClass }} {{ $selectArrow }}"
                        @change="countyResolved = !!$el.value; countyName = $el.options[$el.selectedIndex]?.text || ''"
                    >
                        <option value="">Select County…</option>
                        @foreach ($this->counties as $county)
                            <option value="{{ $county->id }}">{{ $county->name }}</option>
                        @endforeach
                    </select>
                    @error('form.county_id')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>
            </div>

        </div>

        {{-- Step 1 footer --}}
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-zinc-100">
            @if ($cancelHref)
                <a href="{{ $cancelHref }}" wire:navigate>
                    <flux:button tag="span" size="sm">Cancel</flux:button>
                </a>
            @else
                <flux:button size="sm" type="button" wire:click="closeModal">Cancel</flux:button>
            @endif

            <flux:button
                variant="primary" size="sm" type="button"
                x-bind:disabled="!hasPinned || !countyResolved || countyResolving"
                x-bind:class="(!hasPinned || !countyResolved || countyResolving) ? 'opacity-40 !cursor-not-allowed' : ''"
                @click="step = 'form'"
            >
                Continue →
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
                    <svg class="w-3.5 h-3.5 text-primary shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg>
                    <span x-text="pinnedText || 'Location pinned'"
                        class="text-[12px] font-semibold text-zinc-700 leading-snug"></span>
                </div>
                <button type="button"
                    class="text-[11px] font-bold tracking-[0.06em] uppercase text-primary cursor-pointer hover:opacity-70 transition-opacity shrink-0 whitespace-nowrap bg-none border-none p-0 mt-0.5"
                    @click="step = 'map'; $nextTick(() => { setTimeout(() => window.deliveryMap?.invalidateSize(), 80); })">
                    Change Pin
                </button>
            </div>

            {{-- Name --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="{{ $labelClass }}">First Name *</label>
                    <input type="text" wire:model="form.first_name" placeholder="John"
                        class="{{ $inputClass }}{{ $errors->has('form.first_name') ? ' border-red-500' : '' }}">
                    @error('form.first_name')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Last Name</label>
                    <input type="text" wire:model="form.last_name" placeholder="Doe"
                        class="{{ $inputClass }}{{ $errors->has('form.last_name') ? ' border-red-500' : '' }}">
                    @error('form.last_name')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Phone --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="{{ $labelClass }}">Phone Number *</label>
                    <div class="flex">
                        <span
                            class="flex items-center px-3 border-y-[1.5px] border-l-[1.5px] border-zinc-200 bg-zinc-50 text-[13px] font-bold text-zinc-500">+254</span>
                        <input type="text" wire:model="form.phone_number" placeholder="712 345 678"
                            class="{{ $inputClass }} border-l-0{{ $errors->has('form.phone_number') ? ' border-red-500' : '' }}">
                    </div>
                    @error('form.phone_number')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Alternative Phone (Optional)</label>
                    <div class="flex">
                        <span
                            class="flex items-center px-3 border-y-[1.5px] border-l-[1.5px] border-zinc-200 bg-zinc-50 text-[13px] font-bold text-zinc-500">+254</span>
                        <input type="text" wire:model="form.alternative_phone_number" placeholder="722 000 000"
                            class="{{ $inputClass }} border-l-0{{ $errors->has('form.alternative_phone_number') ? ' border-red-500' : '' }}">
                    </div>
                    @error('form.alternative_phone_number')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Address --}}
            <div>
                <label class="{{ $labelClass }}">Street / Apartment / Office *</label>
                <input type="text" wire:model="form.address_text" placeholder="e.g. Westlands Road, Apartment 3B"
                    class="{{ $inputClass }}{{ $errors->has('form.address_text') ? ' border-red-500' : '' }}">
                @error('form.address_text')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>

            {{-- Delivery instructions --}}
            <div>
                <label class="{{ $labelClass }}">Delivery Instructions (Optional)</label>
                <textarea wire:model="form.additional_information" rows="3"
                    placeholder="e.g. Green gate, 2nd floor, call on arrival"
                    class="{{ $inputClass }} h-24{{ $errors->has('form.additional_information') ? ' border-red-500' : '' }}"></textarea>
                @error('form.additional_information')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>

            {{-- Label + default --}}
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <label class="{{ $labelClass }} !mb-0">Label:</label>
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
                            class="text-[12px] font-bold uppercase tracking-widest text-zinc-500 group-hover:text-zinc-950">Set
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
            <flux:button size="sm" type="button"
                @click="step = 'map'; $nextTick(() => { setTimeout(() => window.deliveryMap?.invalidateSize(), 80); })">
                ← Back to Map
            </flux:button>

            <flux:button variant="primary" size="sm" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $submitLabel }}</span>
                <span wire:loading>Saving…</span>
            </flux:button>
        </div>
    </div>

</div>

@script
    <script>
        if (!document.getElementById('leaflet-css')) {
            const link = document.createElement('link');
            link.id = 'leaflet-css';
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(link);
        }

        // Brand-matched popup styles
        if (!document.getElementById('address-map-popup-css')) {
            const style = document.createElement('style');
            style.id = 'address-map-popup-css';
            style.textContent = `
                .leaflet-popup-content-wrapper {
                    border-radius: 0 !important;
                    border: 2px solid var(--color-primary) !important;
                    box-shadow: 4px 4px 0 rgba(0,0,0,.12) !important;
                }
                .leaflet-popup-tip { background: var(--color-primary) !important; }
                .leaflet-popup-content { font-size: 12px !important; font-weight: 600 !important; margin: 8px 12px !important; line-height: 1.5 !important; }
            `;
            document.head.appendChild(style);
        }

        function loadLeaflet(callback) {
            if (window.L) { return callback(); }
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = callback;
            document.head.appendChild(script);
        }

        loadLeaflet(() => {
            const KENYA_CENTER = [-1.2921, 36.8219];
            let map, pin;

            const container = document.getElementById('address-map');
            if (!container) { return; }

            map = L.map(container, { zoomControl: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap',
                maxZoom: 19,
            }).addTo(map);
            map.setView(KENYA_CENTER, 13);
            window.deliveryMap = map;

            // Livewire's DOM morph can replace the <dialog> element between renders,
            // leaving a MutationObserver watching a detached node. Watch the Livewire
            // property instead so invalidateSize() fires reliably on every open.
            function onModalOpen() {
                setTimeout(() => {
                    map.invalidateSize();
                    $wire.call('getMapState').then(state => {
                        if (state?.pin?.lat) {
                            placePin(state.pin.lat, state.pin.lng);
                            map.setView([state.pin.lat, state.pin.lng], 15);
                            reverseGeocode(state.pin.lat, state.pin.lng);
                        } else {
                            if (pin) { map.removeLayer(pin); pin = null; }
                            map.setView(KENYA_CENTER, 13);
                        }
                    });
                }, 150);
            }

            // $wire.on() is the reliable Livewire 4 way to react to server-dispatched
            // events inside @script blocks — more dependable than $wire.$watch here.
            $wire.on('address-modal-opened', onModalOpen);

            if (!container.closest('dialog')) {
                onModalOpen();
            }

            const pinIcon = L.divIcon({
                className: '',
                html: `<div style="width:32px;height:40px;filter:drop-shadow(0 3px 6px rgba(0,0,0,.35));"><svg viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg"><path d="M16 0C7.163 0 0 7.163 0 16c0 10 16 24 16 24S32 26 32 16C32 7.163 24.837 0 16 0z" fill="#FF4500" /><circle cx="16" cy="16" r="7" fill="white" /><circle cx="16" cy="16" r="4" fill="#FF4500" /></svg></div>`,
                iconSize: [32, 40],
                iconAnchor: [16, 40],
                popupAnchor: [0, -44],
            });

            function placePin(lat, lng) {
                if (pin) {
                    pin.setLatLng([lat, lng]);
                } else {
                    pin = L.marker([lat, lng], { icon: pinIcon, draggable: true }).addTo(map);
                    pin.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        $wire.set('form.latitude', pos.lat);
                        $wire.set('form.longitude', pos.lng);
                        reverseGeocode(pos.lat, pos.lng);
                    });
                }
            }

            map.on('click', (e) => {
                placePin(e.latlng.lat, e.latlng.lng);
                $wire.set('form.latitude', e.latlng.lat);
                $wire.set('form.longitude', e.latlng.lng);
                reverseGeocode(e.latlng.lat, e.latlng.lng);
            });


            function reverseGeocode(lat, lng) {
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`, {
                    headers: { 'Accept-Language': 'en' }
                })
                .then(r => r.json())
                .then(data => {
                    const a = data.address || {};
                    const road     = a.road || a.pedestrian || a.footway || '';
                    const suburb   = a.suburb || a.neighbourhood || a.quarter || '';
                    const district = a.city_district || a.district || '';
                    const locality = suburb || district;
                    const city     = a.city || a.town || a.village || '';

                    // In Kenya, `state` reliably holds the county name.
                    // `county` frequently returns ward/sub-county names — prefer it last.
                    const wardPattern = /\b(ward|sub.?county|division|location)\b/i;
                    const countyCandidates = [a.state, a.state_district, a.county].filter(Boolean);
                    const countyRaw = countyCandidates.find(c => !wardPattern.test(c)) ?? countyCandidates[0] ?? '';
                    const areaRaw   = suburb || district || city || '';

                    const parts = [road, locality, city].filter(Boolean);
                    const shortDisp = parts.length ? parts.join(', ') : `${lat.toFixed(5)}, ${lng.toFixed(5)}`;

                    if (pin) {
                        pin.bindPopup(
                            `<b>📍 Delivery here</b><br>${shortDisp}`,
                            { maxWidth: 240 }
                        ).openPopup();
                    }

                    window.dispatchEvent(new CustomEvent('map-pin-placed', { detail: { text: shortDisp } }));

                    if (countyRaw) {
                        $wire.call('resolveCountyFromName', countyRaw).then(result => {
                            window.dispatchEvent(new CustomEvent('county-resolved', {
                                detail: { resolved: !!result, name: result?.name || '' }
                            }));
                            if (result && areaRaw) {
                                $wire.call('resolveAreaFromName', areaRaw);
                            }
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('county-resolved', { detail: { resolved: false, name: '' } }));
                    }
                })
                .catch(() => {
                    const fallback = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                    window.dispatchEvent(new CustomEvent('map-pin-placed', { detail: { text: fallback } }));
                    window.dispatchEvent(new CustomEvent('county-resolved', { detail: { resolved: false, name: '' } }));
                });
            }

            // Map search
            window.addEventListener('do-map-search', () => {
                const input = document.getElementById('map-search-input');
                const q = input?.value?.trim();
                if (!q) { return; }

                fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&countrycodes=ke&format=json&limit=1`, {
                    headers: { 'Accept-Language': 'en' }
                })
                .then(r => r.json())
                .then(results => {
                    if (!results.length) {
                        window.dispatchEvent(new CustomEvent('map-search-not-found'));
                        return;
                    }
                    const r = results[0];
                    const lat = parseFloat(r.lat);
                    const lng = parseFloat(r.lon);
                    map.setView([lat, lng], 16);
                    placePin(lat, lng);
                    $wire.set('form.latitude', lat);
                    $wire.set('form.longitude', lng);
                    reverseGeocode(lat, lng);
                })
                .catch(() => window.dispatchEvent(new CustomEvent('map-search-not-found')));
            });
        });
    </script>
@endscript
