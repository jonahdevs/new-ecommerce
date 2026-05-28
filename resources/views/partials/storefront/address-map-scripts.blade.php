@assets
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLs=" crossorigin=""></script>
@endassets

@script
<script>
Alpine.data('addressMap', () => {
    // Non-reactive guard so x-effect doesn't re-init on every dependency change.
    let active = false;

    return {
        map: null,
        marker: null,
        locating: false,
        step: 1,

        open() {
            if (active) return;
            active = true;
            this.step = 1;
            this.$nextTick(() => {
                this.initMap();
                // Flux animates the modal in; recalc once it has settled so tiles render.
                setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 250);
            });
        },

        close() {
            if (! active) return;
            active = false;
            this.destroyMap();
        },

        showDetails() {
            this.step = 2;
        },

        showLocation() {
            this.step = 1;
            this.$nextTick(() => { if (this.map) this.map.invalidateSize(); });
        },

        initMap() {
            if (! this.$refs.mapContainer) return;
            if (this.map) this.destroyMap();

            const lat = this.$wire.latitude ?? -1.2921;
            const lng = this.$wire.longitude ?? 36.8219;
            const hasPin = this.$wire.latitude !== null;

            this.map = L.map(this.$refs.mapContainer, { zoomControl: true }).setView([lat, lng], hasPin ? 15 : 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);

            if (hasPin) {
                this.placeMarker(lat, lng);
            }

            this.map.on('click', (e) => {
                this.placeMarker(e.latlng.lat, e.latlng.lng);
            });

            this.$nextTick(() => { if (this.map) this.map.invalidateSize(); });
        },

        placeMarker(lat, lng) {
            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            } else {
                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                this.marker.on('dragend', (e) => {
                    const pos = e.target.getLatLng();
                    this.$wire.latitude  = parseFloat(pos.lat.toFixed(7));
                    this.$wire.longitude = parseFloat(pos.lng.toFixed(7));
                });
            }
            this.$wire.latitude  = parseFloat(lat.toFixed(7));
            this.$wire.longitude = parseFloat(lng.toFixed(7));
            this.map.panTo([lat, lng]);
        },

        clearPin() {
            if (this.marker) {
                this.map.removeLayer(this.marker);
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
                    this.map.setView([pos.coords.latitude, pos.coords.longitude], 16);
                },
                () => { this.locating = false; },
                { enableHighAccuracy: true, timeout: 8000 }
            );
        },

        destroyMap() {
            if (this.map) { this.map.remove(); this.map = null; this.marker = null; }
        },
    };
});
</script>
@endscript
