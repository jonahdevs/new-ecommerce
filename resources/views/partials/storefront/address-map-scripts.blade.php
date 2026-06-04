@php
    $integrations    = app(\App\Settings\IntegrationSettings::class);
    $mapProvider     = $integrations->map_provider ?: 'leaflet';
    $googleMapsKey   = $integrations->google_maps_api_key ?: config('services.google.maps_api_key');
    // Fall back to leaflet if Google selected but no key configured.
    if ($mapProvider === 'google' && ! $googleMapsKey) {
        $mapProvider = 'leaflet';
    }
@endphp

@script
<script>
window.ensureLeaflet = window.ensureLeaflet || function () {
    return window.L
        ? Promise.resolve()
        : Promise.reject(new Error('Leaflet is not loaded. Run npm run build.'));
};

window.ensureGoogleMaps = window.ensureGoogleMaps || function (apiKey) {
    if (window.google?.maps) return Promise.resolve();
    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`;
        s.onload = resolve;
        s.onerror = () => reject(new Error('Failed to load Google Maps.'));
        document.head.appendChild(s);
    });
};

Alpine.data('addressMap', () => {
    let active = false;
    const provider   = @js($mapProvider);
    const googleKey  = @js($googleMapsKey);
    const isGoogle   = provider === 'google';

    return {
        map: null,
        marker: null,
        locating: false,
        step: 1,

        open() {
            if (active) return;
            active = true;
            this.step = 1;
            this.$nextTick(() => this.initMap());
        },

        close() {
            if (! active) return;
            active = false;
            this.destroyMap();
        },

        showDetails() { this.step = 2; },

        showLocation() {
            this.step = 1;
            this.$nextTick(() => {
                if (this.map && ! isGoogle) this.map.invalidateSize();
            });
        },

        async initMap() {
            isGoogle ? await this.initGoogleMap() : await this.initLeafletMap();
        },

        // ==================================================
        // LEAFLET
        // ==================================================

        async initLeafletMap() {
            const container = document.getElementById('address-map-container');
            if (! container) return;

            try { await window.ensureLeaflet(); } catch (e) { console.error(e); return; }
            if (! active) return;
            if (this.map) this.destroyMap();

            const lat    = this.$wire.latitude  ?? -1.2921;
            const lng    = this.$wire.longitude ?? 36.8219;
            const hasPin = this.$wire.latitude !== null;

            this.map = L.map(container, { zoomControl: true }).setView([lat, lng], hasPin ? 15 : 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);

            if (hasPin) this.placeMarker(lat, lng);

            this.map.on('click', (e) => this.placeMarker(e.latlng.lat, e.latlng.lng));

            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 300);
        },

        placeMarkerLeaflet(lat, lng) {
            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            } else {
                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                this.marker.on('dragend', (e) => {
                    const p = e.target.getLatLng();
                    this.$wire.latitude  = parseFloat(p.lat.toFixed(7));
                    this.$wire.longitude = parseFloat(p.lng.toFixed(7));
                });
            }
            this.$wire.latitude  = parseFloat(lat.toFixed(7));
            this.$wire.longitude = parseFloat(lng.toFixed(7));
            this.map.panTo([lat, lng]);
        },

        destroyLeafletMap() {
            if (this.map) { this.map.remove(); this.map = null; this.marker = null; }
        },

        // ==================================================
        // GOOGLE MAPS
        // ==================================================

        async initGoogleMap() {
            const container = document.getElementById('address-map-container');
            if (! container) return;

            try { await window.ensureGoogleMaps(googleKey); } catch (e) { console.error(e); return; }
            if (! active) return;
            if (this.map) this.destroyMap();

            const lat    = this.$wire.latitude  ?? -1.2921;
            const lng    = this.$wire.longitude ?? 36.8219;
            const hasPin = this.$wire.latitude !== null;

            this.map = new google.maps.Map(container, {
                center: { lat, lng },
                zoom: hasPin ? 15 : 13,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
            });

            if (hasPin) this.placeMarker(lat, lng);

            this.map.addListener('click', (e) => {
                this.placeMarker(e.latLng.lat(), e.latLng.lng());
            });
        },

        placeMarkerGoogle(lat, lng) {
            if (this.marker) {
                this.marker.setPosition({ lat, lng });
            } else {
                this.marker = new google.maps.Marker({
                    position: { lat, lng },
                    map: this.map,
                    draggable: true,
                });
                this.marker.addListener('dragend', () => {
                    const p = this.marker.getPosition();
                    this.$wire.latitude  = parseFloat(p.lat().toFixed(7));
                    this.$wire.longitude = parseFloat(p.lng().toFixed(7));
                });
            }
            this.$wire.latitude  = parseFloat(lat.toFixed(7));
            this.$wire.longitude = parseFloat(lng.toFixed(7));
            this.map.panTo({ lat, lng });
        },

        destroyGoogleMap() {
            if (this.marker) { this.marker.setMap(null); this.marker = null; }
            this.map = null;
            const container = document.getElementById('address-map-container');
            if (container) container.innerHTML = '';
        },

        // ==================================================
        // SHARED
        // ==================================================

        placeMarker(lat, lng) {
            isGoogle ? this.placeMarkerGoogle(lat, lng) : this.placeMarkerLeaflet(lat, lng);
        },

        destroyMap() {
            isGoogle ? this.destroyGoogleMap() : this.destroyLeafletMap();
        },

        clearPin() {
            if (this.marker) {
                isGoogle ? this.marker.setMap(null) : this.map.removeLayer(this.marker);
                this.marker = null;
            }
            this.$wire.latitude  = null;
            this.$wire.longitude = null;
        },

        locateMe() {
            if (! navigator.geolocation) return;
            this.locating = true;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.locating = false;
                    this.placeMarker(pos.coords.latitude, pos.coords.longitude);
                    if (isGoogle) {
                        this.map.setZoom(16);
                    } else {
                        this.map.setView([pos.coords.latitude, pos.coords.longitude], 16);
                    }
                },
                () => { this.locating = false; },
                { enableHighAccuracy: true, timeout: 8000 }
            );
        },
    };
});
</script>
@endscript
