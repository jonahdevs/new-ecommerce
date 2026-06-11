// Sheffield — real interactive showroom map (Leaflet + CartoDB Positron tiles).
// Falls back to the stylized SVG RegionMap if Leaflet can't load.

// One-time style injection for branded pins + popups.
if (typeof document !== "undefined" && !document.getElementById("__shf_map_styles")) {
  const s = document.createElement("style");
  s.id = "__shf_map_styles";
  s.textContent = `
    .shf-map { position: absolute; inset: 0; background: var(--bg-sunken); z-index: 0; }
    .shf-map .leaflet-control-attribution { font-size: 10px; background: rgba(255,255,255,0.8); }
    .shf-map .leaflet-bar a { color: var(--ink-2); }
    .shf-pin-wrap { background: transparent !important; border: 0 !important; }
    .shf-pin {
      width: 24px; height: 24px; border-radius: 50% 50% 50% 0;
      background: var(--secondary); border: 2.5px solid #fff;
      transform: rotate(-45deg);
      box-shadow: 0 3px 8px rgba(12,20,33,0.35);
      display: flex; align-items: center; justify-content: center;
      transition: transform 180ms cubic-bezier(.2,.8,.2,1), background 180ms ease;
      cursor: pointer;
    }
    .shf-pin > span { width: 7px; height: 7px; border-radius: 50%; background: #fff; transform: rotate(45deg); }
    .shf-pin-wrap.active { z-index: 1000 !important; }
    .shf-pin-wrap.active .shf-pin { background: var(--accent); transform: rotate(-45deg) scale(1.32); }
    .leaflet-popup-content-wrapper { border-radius: var(--radius); box-shadow: var(--shadow); }
    .leaflet-popup-content { margin: 12px 14px; }
    .shf-pop { font-family: var(--font-body); font-size: 12.5px; color: var(--ink-2); line-height: 1.55; }
    .shf-pop strong { font-family: var(--font-heading); font-size: 15px; font-weight: 500; color: var(--ink); display: block; margin-bottom: 3px; }
    .shf-pop .shf-pop-hq { display: inline-block; font-size: 9px; font-weight: 700; letter-spacing: .06em; background: var(--accent); color: #fff; padding: 1px 6px; border-radius: 3px; margin-left: 6px; vertical-align: 1px; }
  `;
  document.head.appendChild(s);
}

const SHF_TILES = {
  url: "https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png",
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
  subdomains: "abcd",
  maxZoom: 19,
};

function ShowroomMap({ activeSlug, onPick }) {
  const elRef = React.useRef(null);
  const mapRef = React.useRef(null);
  const markersRef = React.useRef({});
  const locs = window.SHEFFIELD_LOCATIONS;

  // Initialise once
  React.useEffect(() => {
    if (!window.L || !elRef.current || mapRef.current) return;
    const L = window.L;
    const map = L.map(elRef.current, {
      zoomControl: true,
      scrollWheelZoom: false,
      attributionControl: true,
    });

    L.tileLayer(SHF_TILES.url, {
      attribution: SHF_TILES.attribution,
      subdomains: SHF_TILES.subdomains,
      maxZoom: SHF_TILES.maxZoom,
    }).addTo(map);

    locs.forEach(loc => {
      const icon = L.divIcon({
        className: "shf-pin-wrap",
        html: '<div class="shf-pin"><span></span></div>',
        iconSize: [28, 34],
        iconAnchor: [14, 30],
        popupAnchor: [0, -30],
      });
      const m = L.marker([loc.lat, loc.lng], { icon }).addTo(map);
      m.bindPopup(
        `<div class="shf-pop"><strong>${loc.city}${loc.isHQ ? '<span class="shf-pop-hq">HQ</span>' : ''}</strong>${loc.address}<br/>${loc.suburb}, ${loc.country}<br/>${loc.phone}</div>`
      );
      m.on("click", () => onPick && onPick(loc.slug));
      markersRef.current[loc.slug] = m;
    });

    // Fit all four cities in view to start
    const group = window.L.featureGroup(Object.values(markersRef.current));
    map.fitBounds(group.getBounds().pad(0.3));

    mapRef.current = map;
    // Leaflet sizes against the container; recalc after layout settles
    const t1 = setTimeout(() => map.invalidateSize(), 120);
    const t2 = setTimeout(() => map.invalidateSize(), 500);

    return () => {
      clearTimeout(t1); clearTimeout(t2);
      map.remove();
      mapRef.current = null;
      markersRef.current = {};
    };
  }, []);

  // React to active showroom change
  React.useEffect(() => {
    const map = mapRef.current;
    if (!map) return;
    const loc = locs.find(l => l.slug === activeSlug);
    if (!loc) return;
    map.invalidateSize();
    map.flyTo([loc.lat, loc.lng], 12, { duration: 0.8 });
    Object.entries(markersRef.current).forEach(([slug, m]) => {
      const el = m.getElement();
      if (el) el.classList.toggle("active", slug === activeSlug);
    });
    const m = markersRef.current[activeSlug];
    if (m) m.openPopup();
  }, [activeSlug]);

  // Fallback: no Leaflet → stylized SVG map on a brand-blue field
  if (typeof window === "undefined" || !window.L) {
    return (
      <div style={{ position: "absolute", inset: 0, background: "var(--brand-blue-700)" }}>
        <RegionMap activeSlug={activeSlug} onPick={onPick}/>
      </div>
    );
  }

  return <div ref={elRef} className="shf-map"/>;
}

Object.assign(window, { ShowroomMap });
