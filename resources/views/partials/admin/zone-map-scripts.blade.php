@script
<script>
// Lazily load Leaflet (CSS + JS) on demand and resolve once `L` is available.
window.ensureLeaflet = window.ensureLeaflet || function () {
    if (window.L) return Promise.resolve();
    if (window.__leafletReady) return window.__leafletReady;

    window.__leafletReady = new Promise((resolve, reject) => {
        // Official Leaflet 1.9.4 CDN URLs + SRI hashes (leafletjs.com/download.html).
        if (! document.querySelector('link[data-leaflet]')) {
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            css.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
            css.crossOrigin = '';
            css.setAttribute('data-leaflet', '');
            document.head.appendChild(css);
        }

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
        script.crossOrigin = '';
        script.setAttribute('data-leaflet', '');
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Leaflet'));
        document.head.appendChild(script);
    });

    return window.__leafletReady;
};

Alpine.data('zoneMap', () => {
    let active = false;

    return {
        map: null,
        marker: null,
        circle: null,

        open() {
            if (active) return;
            active = true;
            this.$nextTick(() => this.initMap());
        },

        close() {
            if (! active) return;
            active = false;
            this.destroyMap();
        },

        async initMap() {
            if (! this.$refs.zoneMapContainer) return;

            try {
                await window.ensureLeaflet();
            } catch (e) {
                console.error(e);
                return;
            }

            if (! active || ! this.$refs.zoneMapContainer) return;
            if (this.map) this.destroyMap();

            const lat = this.$wire.center_lat ?? -1.2921;
            const lng = this.$wire.center_lng ?? 36.8219;
            const hasCenter = this.$wire.center_lat !== null && this.$wire.center_lat !== '';

            this.map = L.map(this.$refs.zoneMapContainer, { zoomControl: true })
                .setView([lat, lng], hasCenter ? 12 : 11);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);

            if (hasCenter) {
                this.placeCenter(lat, lng);
            }

            this.map.on('click', (e) => this.placeCenter(e.latlng.lat, e.latlng.lng));

            // Keep the circle in sync when the radius input changes.
            this.$watch('$wire.radius_meters', () => this.syncCircle());

            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 300);
        },

        placeCenter(lat, lng) {
            this.$wire.center_lat = parseFloat(lat.toFixed(7));
            this.$wire.center_lng = parseFloat(lng.toFixed(7));

            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            } else {
                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                this.marker.on('drag', (e) => {
                    const pos = e.target.getLatLng();
                    this.$wire.center_lat = parseFloat(pos.lat.toFixed(7));
                    this.$wire.center_lng = parseFloat(pos.lng.toFixed(7));
                    if (this.circle) this.circle.setLatLng(pos);
                });
            }
            this.syncCircle();
            this.map.panTo([lat, lng]);
        },

        syncCircle() {
            const radius = parseFloat(this.$wire.radius_meters) || 0;
            if (! this.marker || radius <= 0) return;

            const center = this.marker.getLatLng();
            if (this.circle) {
                this.circle.setLatLng(center).setRadius(radius);
            } else {
                this.circle = L.circle(center, {
                    radius,
                    color: '#f97316',
                    fillColor: '#f97316',
                    fillOpacity: 0.12,
                }).addTo(this.map);
            }
        },

        destroyMap() {
            if (this.map) { this.map.remove(); this.map = null; this.marker = null; this.circle = null; }
        },
    };
});
</script>
@endscript
