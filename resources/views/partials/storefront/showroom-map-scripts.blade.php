@php
    $integrations = app(\App\Settings\IntegrationSettings::class);
    $mapProvider = $integrations->map_provider ?: 'leaflet';
    $googleMapsKey = $integrations->google_maps_api_key ?: config('services.google.maps_api_key');
    // Fall back to leaflet if Google is selected but no key is configured.
    if ($mapProvider === 'google' && ! $googleMapsKey) {
        $mapProvider = 'leaflet';
    }
@endphp

{{-- Branded Leaflet pin + popup styles (injected once). --}}
<style>
    .shf-map { position: absolute; inset: 0; z-index: 0; }
    .shf-map .leaflet-control-attribution { font-size: 10px; background: rgba(255,255,255,0.82); }
    .shf-pin-wrap { background: transparent !important; border: 0 !important; }
    .shf-pin {
        width: 22px; height: 22px; border-radius: 50% 50% 50% 0;
        background: var(--color-brand-blue-600); border: 2.5px solid #fff;
        transform: rotate(-45deg); box-shadow: 0 3px 8px rgba(12,20,33,0.4);
        display: flex; align-items: center; justify-content: center;
        transition: transform 180ms cubic-bezier(.2,.8,.2,1), background 180ms ease; cursor: pointer;
    }
    .shf-pin > span { width: 6px; height: 6px; border-radius: 50%; background: #fff; transform: rotate(45deg); }
    .shf-pin-wrap.active { z-index: 1000 !important; }
    .shf-pin-wrap.active .shf-pin { background: var(--color-brand-500); transform: rotate(-45deg) scale(1.3); }
</style>

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
        s.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}`;
        s.onload = resolve;
        s.onerror = () => reject(new Error('Failed to load Google Maps.'));
        document.head.appendChild(s);
    });
};

Alpine.data('showroomMap', (config) => {
    const provider = @js($mapProvider);
    const googleKey = @js($googleMapsKey);
    const isGoogle = provider === 'google';
    const locations = config.locations || [];

    return {
        active: config.initial ?? (locations[0]?.id ?? null),
        map: null,
        markers: {},
        ready: false,

        init() {
            this.$watch('active', (id) => this.focus(id));
            this.$nextTick(() => this.build());
        },

        async build() {
            if (! locations.length) return;
            try {
                isGoogle ? await this.buildGoogle() : await this.buildLeaflet();
                this.ready = true;
                this.focus(this.active);
            } catch (e) {
                // Leave the SVG fallback in place.
                console.error(e);
            }
        },

        // ── Leaflet ──────────────────────────────────────────────
        async buildLeaflet() {
            await window.ensureLeaflet();
            const el = this.$refs.map;
            if (! el) return;

            this.map = L.map(el, { zoomControl: true, scrollWheelZoom: true, attributionControl: true });

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd', maxZoom: 19,
            }).addTo(this.map);

            const pts = [];
            locations.forEach((loc) => {
                const icon = L.divIcon({
                    className: 'shf-pin-wrap', html: '<div class="shf-pin"><span></span></div>',
                    iconSize: [22, 22], iconAnchor: [11, 22],
                });
                const marker = L.marker([loc.lat, loc.lng], { icon, title: loc.city }).addTo(this.map);
                marker.on('click', () => { this.active = loc.id; });
                this.markers[loc.id] = marker;
                pts.push([loc.lat, loc.lng]);
            });

            this.map.fitBounds(pts, { padding: [44, 44], maxZoom: 7 });
            setTimeout(() => this.map && this.map.invalidateSize(), 250);
        },

        // ── Google ───────────────────────────────────────────────
        async buildGoogle() {
            await window.ensureGoogleMaps(googleKey);
            const el = this.$refs.map;
            if (! el) return;

            this.map = new google.maps.Map(el, {
                mapTypeControl: false, streetViewControl: false, fullscreenControl: false, zoomControl: true,
            });

            const bounds = new google.maps.LatLngBounds();
            locations.forEach((loc) => {
                const marker = new google.maps.Marker({
                    position: { lat: loc.lat, lng: loc.lng }, map: this.map, title: loc.city,
                });
                marker.addListener('click', () => { this.active = loc.id; });
                this.markers[loc.id] = marker;
                bounds.extend(marker.getPosition());
            });
            this.map.fitBounds(bounds, 44);
            google.maps.event.addListenerOnce(this.map, 'idle', () => {
                if (this.map.getZoom() > 7) this.map.setZoom(7);
            });
        },

        // ── Sync active marker ───────────────────────────────────
        focus(id) {
            if (! this.map || id == null) return;
            const loc = locations.find((l) => l.id === id);
            if (! loc) return;

            if (isGoogle) {
                this.map.panTo({ lat: loc.lat, lng: loc.lng });
            } else {
                Object.entries(this.markers).forEach(([key, m]) => {
                    const elPin = m.getElement();
                    if (elPin) elPin.classList.toggle('active', Number(key) === id);
                });
                this.map.panTo([loc.lat, loc.lng]);
            }
        },
    };
});
</script>
@endscript
