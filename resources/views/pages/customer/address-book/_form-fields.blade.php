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

    $hasPinnedInit = !empty($form->latitude) ? 'true' : 'false';
    $pinnedInit = !empty($form->latitude) ? "'Location saved'" : "''";
@endphp

<div x-data="{ step: 'map', hasPinned: false, pinnedText: '' }" x-init="hasPinned = {{ $hasPinnedInit }};
pinnedText = {{ $pinnedInit }}"
    @map-pin-placed.window="hasPinned = true; pinnedText = $event.detail.text">

    {{-- ══════════════════════════════════════════════════════
         STEP 1 — PIN YOUR LOCATION
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'map'">
        <div class="p-6 space-y-5">

            {{-- County / Area --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="{{ $labelClass }}">Region / County *</label>
                    <select id="addr-county-select" wire:model.live="form.county_id"
                        class="{{ $inputClass }} {{ $selectArrow }}">
                        <option value="">Select County...</option>
                        @foreach ($this->counties as $county)
                            <option value="{{ $county->id }}">{{ $county->name }}</option>
                        @endforeach
                    </select>
                    @error('form.county_id')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">City / Area</label>
                    <select wire:model="form.area_id" class="{{ $inputClass }} {{ $selectArrow }}">
                        <option value="">{{ $form->county_id ? 'Select Area' : 'Select a county first' }}
                        </option>
                        @foreach ($this->areas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </select>
                    @error('form.area_id')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Map --}}
            <div>
                <label class="{{ $labelClass }}">📍 Pin your exact delivery location</label>
                <p class="text-[12px] text-zinc-500 mb-3 leading-relaxed">
                    Select your county and area first, then click or drag the pin to your exact delivery location.
                </p>

                <div id="map-mismatch-warning"
                    class="hidden mb-3 text-[12px] text-amber-600 bg-amber-50 border-l-[3px] border-amber-500 p-3">
                    ⚠️ The pin appears to be outside the selected county. Please reposition it for accurate shipping
                    rates.
                </div>

                <div id="address-map" wire:ignore class="w-full border-[1.5px] border-zinc-200 z-0 bg-zinc-100"
                    style="height:320px;"></div>

                <div
                    class="bg-zinc-50 border-x-[1.5px] border-b-[1.5px] border-zinc-200 p-2.5 flex items-center gap-2 text-[11px] text-zinc-500">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    Click anywhere on the map to drop a delivery pin. Drag the pin to adjust.
                </div>
            </div>

            {{-- Pinned location bar (visible once a pin is placed) --}}
            <div x-show="hasPinned" x-cloak
                class="bg-zinc-50 border-l-[3px] border-brand-primary px-4 py-3 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-brand-primary shrink-0" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                    <circle cx="12" cy="10" r="3" />
                </svg>
                <span x-text="pinnedText || 'Location pinned'" class="text-[12px] font-semibold text-zinc-700"></span>
            </div>

        </div>

        {{-- Step 1 footer --}}
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-zinc-100">
            @if ($cancelHref)
                <a href="{{ $cancelHref }}" wire:navigate>
                    <flux:button tag="span">Cancel</flux:button>
                </a>
            @else
                <flux:button type="button" wire:click="closeModal">Cancel</flux:button>
            @endif

            <flux:button variant="primary" type="button" x-bind:disabled="!hasPinned"
                class="inline-flex items-center gap-2" x-bind:class="!hasPinned ? 'opacity-40 cursor-not-allowed!' : ''"
                @click="step = 'form'">
                Continue <flux:icon.move-right class="size-4" />
            </flux:button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 2 — DELIVERY DETAILS
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'form'">
        <div class="p-6 space-y-5">

            {{-- Pinned summary bar --}}
            <div
                class="bg-zinc-50 border-l-[3px] border-brand-primary px-4 py-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <svg class="w-4 h-4 text-brand-primary shrink-0" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg>
                    <span x-text="pinnedText || 'Location pinned'"
                        class="text-[12px] font-semibold text-zinc-700 truncate"></span>
                </div>
                <button type="button"
                    class="text-[11px] font-bold font-barlow-condensed tracking-widest uppercase text-brand-primary cursor-pointer transition-opacity hover:opacity-70 shrink-0"
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

        function loadLeaflet(callback) {
            if (window.L) {
                return callback();
            }
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = callback;
            document.head.appendChild(script);
        }

        loadLeaflet(() => {
            const KENYA_CENTER = [-1.2921, 36.8219];
            let map, pin;

            const container = document.getElementById('address-map');
            if (!container) {
                return;
            }

            map = L.map(container, {
                zoomControl: true
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap',
                maxZoom: 19,
            }).addTo(map);

            map.setView(KENYA_CENTER, 13);
            setTimeout(() => map.invalidateSize(), 300);

            window.deliveryMap = map;

            const pinIcon = L.divIcon({
                className: '',
                html: `<div style="width:32px;height:40px;filter:drop-shadow(0 3px 6px rgba(0,0,0,.35));"><svg viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg"><path d="M16 0C7.163 0 0 7.163 0 16c0 10 16 24 16 24S32 26 32 16C32 7.163 24.837 0 16 0z" fill="#FF4500" /><circle cx="16" cy="16" r="7" fill="white" /><circle cx="16" cy="16" r="4" fill="#FF4500" /></svg></div>`,
                iconSize: [32, 40],
                iconAnchor: [16, 40],
            });

            function getPinnedText() {
                const el = document.getElementById('addr-county-select');
                const text = el?.options[el.selectedIndex]?.text || '';
                return text && text !== 'Select County...' ? `${text} — location pinned` : 'Location pinned';
            }

            function placePin(lat, lng) {
                if (pin) {
                    pin.setLatLng([lat, lng]);
                } else {
                    pin = L.marker([lat, lng], {
                        icon: pinIcon,
                        draggable: true
                    }).addTo(map);
                    pin.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        $wire.set('form.latitude', pos.lat);
                        $wire.set('form.longitude', pos.lng);
                        window.dispatchEvent(new CustomEvent('map-pin-placed', {
                            detail: {
                                text: getPinnedText()
                            }
                        }));
                    });
                }
            }

            map.on('click', (e) => {
                placePin(e.latlng.lat, e.latlng.lng);
                $wire.set('form.latitude', e.latlng.lat);
                $wire.set('form.longitude', e.latlng.lng);
                window.dispatchEvent(new CustomEvent('map-pin-placed', {
                    detail: {
                        text: getPinnedText()
                    }
                }));
            });

            $wire.call('getMapState').then(state => {
                if (state?.pin?.lat) {
                    placePin(state.pin.lat, state.pin.lng);
                    map.setView([state.pin.lat, state.pin.lng], 15);
                }
            });

            $wire.$watch('form.county_id', () => {
                $wire.call('getMapState').then(state => {
                    if (state?.center) {
                        placePin(state.center.lat, state.center.lng);
                        map.setView([state.center.lat, state.center.lng], 12);
                    }
                });
            });

            $wire.$watch('form.area_id', () => {
                $wire.call('getMapState').then(state => {
                    if (state?.center) {
                        placePin(state.center.lat, state.center.lng);
                        map.setView([state.center.lat, state.center.lng], 14);
                    }
                });
            });
        });
    </script>
@endscript
