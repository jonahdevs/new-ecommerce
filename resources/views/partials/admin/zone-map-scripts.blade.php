@script
<script>
window.ensureLeaflet = window.ensureLeaflet || function () {
    return window.L
        ? Promise.resolve()
        : Promise.reject(new Error('Leaflet is not loaded. Run npm run build.'));
};

Alpine.data('zoneMap', () => {
    let active = false;

    return {
        map: null,
        markers: [],
        poly: null,
        currentStep: 1,
        polygonError: false,

        open() {
            if (active) return;
            active = true;
            this.currentStep = 1;
            this.polygonError = false;
            this.$nextTick(() => this.initMap());
        },

        close() {
            if (! active) return;
            active = false;
            this.destroyMap();
        },

        goToStep2() {
            if (this.$wire.polygon.length < 3) {
                this.polygonError = true;
                return;
            }
            this.polygonError = false;
            this.currentStep = 2;
        },

        goToStep1() {
            this.currentStep = 1;
            this.$nextTick(() => { if (this.map) this.map.invalidateSize(); });
        },

        async initMap() {
            const container = document.getElementById('zone-map-container');
            if (! container) return;

            try {
                await window.ensureLeaflet();
            } catch (e) {
                console.error(e);
                return;
            }

            if (! active) return;
            if (this.map) this.destroyMap();

            this.map = L.map(container, { zoomControl: true })
                .setView([-1.2921, 36.8219], 10);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);

            // Load existing polygon when editing.
            const existing = this.$wire.polygon;
            if (Array.isArray(existing) && existing.length >= 3) {
                existing.forEach(pt => this.addVertex(pt.lat, pt.lng, false));
                this.redraw();
                const bounds = L.latLngBounds(this.markers.map(m => m.getLatLng()));
                this.map.fitBounds(bounds.pad(0.15));
            }

            this.map.on('click', e => this.addVertex(e.latlng.lat, e.latlng.lng));
            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 300);
        },

        vertexIcon() {
            return L.divIcon({
                className: '',
                html: '<div style="width:12px;height:12px;border-radius:50%;background:#f97316;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4);cursor:grab"></div>',
                iconSize: [12, 12],
                iconAnchor: [6, 6],
            });
        },

        addVertex(lat, lng, sync = true) {
            const marker = L.marker([lat, lng], {
                draggable: true,
                icon: this.vertexIcon(),
            }).addTo(this.map);

            marker.on('drag', () => { this.redraw(); this.syncWire(); });

            // Double-click a vertex to remove it.
            marker.on('dblclick', e => {
                L.DomEvent.stop(e);
                this.map.removeLayer(marker);
                this.markers.splice(this.markers.indexOf(marker), 1);
                this.redraw();
                this.syncWire();
            });

            this.markers.push(marker);
            this.redraw();
            if (sync) this.syncWire();
        },

        undoLast() {
            if (! this.markers.length) return;
            this.map.removeLayer(this.markers.pop());
            this.redraw();
            this.syncWire();
        },

        clearAll() {
            this.markers.forEach(m => this.map.removeLayer(m));
            this.markers = [];
            this.redraw();
            this.syncWire();
        },

        redraw() {
            if (this.poly) { this.map.removeLayer(this.poly); this.poly = null; }

            const lls = this.markers.map(m => m.getLatLng());
            if (lls.length >= 2) {
                this.poly = L.polygon(lls, {
                    color: '#f97316',
                    fillColor: '#f97316',
                    fillOpacity: 0.12,
                    weight: 2,
                }).addTo(this.map);
            }
        },

        syncWire() {
            this.$wire.polygon = this.markers.map(m => {
                const p = m.getLatLng();
                return { lat: parseFloat(p.lat.toFixed(7)), lng: parseFloat(p.lng.toFixed(7)) };
            });
        },

        destroyMap() {
            this.markers = [];
            this.poly = null;
            if (this.map) { this.map.remove(); this.map = null; }
        },
    };
});
</script>
@endscript
