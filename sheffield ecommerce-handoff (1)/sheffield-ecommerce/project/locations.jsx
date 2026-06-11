// Sheffield — Locations: data + showroom band component.

window.SHEFFIELD_LOCATIONS = [
  {
    slug: "nairobi",
    city: "Nairobi",
    country: "Kenya",
    isHQ: true,
    address: "Sheffield House, Mombasa Road",
    suburb: "Industrial Area",
    postcode: "00100",
    phone: "+254 20 234 5600",
    whatsapp: "+254 711 234 567",
    email: "nairobi@sheffield.co.ke",
    hours: { weekday: "Mon–Fri · 8:00 – 17:30", saturday: "Sat · 9:00 – 14:00", sunday: "Closed Sundays" },
    services: ["Showroom", "Warehouse", "Service & Spares", "Trade Counter"],
    lat: -1.3194, lng: 36.8842,
  },
  {
    slug: "mombasa",
    city: "Mombasa",
    country: "Kenya",
    isHQ: false,
    address: "Nyerere Avenue, Plot 14",
    suburb: "Mombasa Island",
    postcode: "80100",
    phone: "+254 41 230 0120",
    whatsapp: "+254 711 230 012",
    email: "mombasa@sheffield.co.ke",
    hours: { weekday: "Mon–Fri · 8:00 – 17:00", saturday: "Sat · 9:00 – 13:00", sunday: "Closed Sundays" },
    services: ["Showroom", "Service & Spares", "Coastal Logistics"],
    lat: -4.0473, lng: 39.6634,
  },
  {
    slug: "kampala",
    city: "Kampala",
    country: "Uganda",
    isHQ: false,
    address: "Plot 42, Yusuf Lule Road",
    suburb: "Nakasero",
    postcode: "P.O. Box 12044",
    phone: "+256 414 250 600",
    whatsapp: "+256 776 250 600",
    email: "kampala@sheffield.co.ug",
    hours: { weekday: "Mon–Fri · 8:30 – 17:30", saturday: "Sat · 9:00 – 13:00", sunday: "Closed Sundays" },
    services: ["Showroom", "Service & Spares"],
    lat: 0.3163, lng: 32.5822,
  },
  {
    slug: "kigali",
    city: "Kigali",
    country: "Rwanda",
    isHQ: false,
    address: "KG 11 Avenue, Kacyiru",
    suburb: "Kacyiru",
    postcode: "P.O. Box 2640",
    phone: "+250 788 305 600",
    whatsapp: "+250 788 305 601",
    email: "kigali@sheffield.rw",
    hours: { weekday: "Mon–Fri · 8:00 – 17:00", saturday: "Sat · 9:00 – 13:00", sunday: "Closed Sundays" },
    services: ["Showroom", "Service"],
    lat: -1.9499, lng: 30.0588,
  },
];

// ───────── Inline East-Africa map with dots ─────────
const RegionMap = ({ activeSlug, onPick }) => {
  // Mercator-ish projection within a 360x420 viewBox covering ~28E–42E, 5N–6S
  const project = (lat, lng) => {
    const x = ((lng - 28) / (42 - 28)) * 360;
    const y = ((6 - lat) / (6 - (-6))) * 420;
    return { x, y };
  };
  return (
    <svg viewBox="0 0 360 420" style={{ width: "100%", height: "100%", display: "block" }}>
      <defs>
        <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
          <path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.04)" strokeWidth="0.5"/>
        </pattern>
      </defs>
      <rect width="360" height="420" fill="var(--brand-blue-700)"/>
      <rect width="360" height="420" fill="url(#grid)"/>
      {/* Country outline approximations as soft blobs */}
      <g opacity="0.18">
        {/* Kenya */}
        <path d="M180 90 L 280 100 L 295 160 L 285 220 L 240 245 L 195 260 L 175 240 L 165 180 Z" fill="#fff"/>
        {/* Uganda */}
        <path d="M120 130 L 175 130 L 180 200 L 130 215 L 105 195 L 100 165 Z" fill="#fff"/>
        {/* Rwanda */}
        <path d="M95 220 L 135 215 L 140 250 L 110 260 L 90 248 Z" fill="#fff"/>
        {/* Tanzania (decorative) */}
        <path d="M135 250 L 200 248 L 260 270 L 285 320 L 240 365 L 180 360 L 130 320 L 120 290 Z" fill="#fff"/>
      </g>
      {/* City labels under map for context */}
      <g style={{ fontFamily: "var(--font-body)", fontSize: 9, fill: "rgba(255,255,255,0.4)" }}>
        <text x="180" y="380" textAnchor="middle">East Africa · 4 showrooms</text>
      </g>
      {/* Pins */}
      {window.SHEFFIELD_LOCATIONS.map(loc => {
        const { x, y } = project(loc.lat, loc.lng);
        const active = loc.slug === activeSlug;
        return (
          <g key={loc.slug} onClick={() => onPick(loc.slug)} style={{ cursor: "pointer" }}>
            {active && <circle cx={x} cy={y} r="14" fill="hsl(354 68% 45% / 0.25)"/>}
            <circle cx={x} cy={y} r={active ? 6 : 4} fill="hsl(354 68% 45%)" stroke="#fff" strokeWidth="1.5"/>
            <text x={x + (loc.slug === "kigali" || loc.slug === "kampala" ? -10 : 10)}
              y={y + 4} textAnchor={(loc.slug === "kigali" || loc.slug === "kampala") ? "end" : "start"}
              style={{ fontFamily: "var(--font-body)", fontSize: 11, fontWeight: active ? 700 : 500,
                fill: active ? "#fff" : "rgba(255,255,255,0.78)" }}>
              {loc.city}{loc.isHQ ? " ★" : ""}
            </text>
          </g>
        );
      })}
    </svg>
  );
};

// ───────── Showroom band (home page section) ─────────
const ShowroomBand = ({ navigate }) => {
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";
  const [active, setActive] = React.useState("nairobi");
  const loc = window.SHEFFIELD_LOCATIONS.find(l => l.slug === active);

  return (
    <section style={{ paddingTop: direction === "workshop" ? 56 : 96, paddingBottom: 16 }}>
      <div className="container">
        <div style={{
          background: "var(--brand-blue-700)", color: "#f3eadd",
          borderRadius: "var(--radius-lg)", overflow: "hidden",
          display: "grid", gridTemplateColumns: "1.1fr 1fr", minHeight: 420,
        }}>
          {/* Map side */}
          <div style={{ position: "relative", background: "var(--brand-blue-700)" }}>
            <RegionMap activeSlug={active} onPick={setActive}/>
          </div>

          {/* Detail side */}
          <div style={{ padding: 40, display: "flex", flexDirection: "column" }}>
            <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.12em", textTransform: "uppercase", color: "var(--warm-1)" }}>
              Visit a Sheffield showroom
            </div>
            <h2 style={{
              fontFamily: "var(--font-heading)", color: "#f6ecd9", fontWeight: 400,
              fontSize: direction === "workshop" ? 28 : 36, lineHeight: 1.1, marginTop: 10,
            }}>
              Across four cities. <span style={{ color: "var(--accent)", fontStyle: direction === "workshop" ? "normal" : "italic" }}>Always nearby.</span>
            </h2>
            <p style={{ marginTop: 12, fontSize: 13.5, color: "#c9bea4", maxWidth: 420, lineHeight: 1.55 }}>
              Equipment on the floor for hands-on demos, spares in stock, and engineers on call. Walk in or book a fitting consultation.
            </p>

            {/* Tabs */}
            <div style={{ marginTop: 22, display: "flex", gap: 0, borderBottom: "1px solid rgba(255,255,255,0.12)" }}>
              {window.SHEFFIELD_LOCATIONS.map(l => (
                <button key={l.slug} onClick={() => setActive(l.slug)} style={{
                  background: "transparent", border: 0, color: l.slug === active ? "#f6ecd9" : "#9c927c",
                  padding: "10px 14px", fontSize: 13, fontWeight: l.slug === active ? 600 : 500,
                  borderBottom: l.slug === active ? "2px solid var(--accent)" : "2px solid transparent",
                  marginBottom: -1, cursor: "pointer",
                  display: "inline-flex", gap: 6, alignItems: "center",
                }}>
                  {l.city}
                  {l.isHQ && <span style={{ fontSize: 9, padding: "1px 6px", background: "var(--accent)", color: "#fff", borderRadius: 3, letterSpacing: "0.06em" }}>HQ</span>}
                </button>
              ))}
            </div>

            {/* Active detail */}
            <div style={{ marginTop: 20, flex: 1 }}>
              <div style={{ fontFamily: "var(--font-heading)", fontSize: 22, color: "#f6ecd9" }}>
                {loc.address}
              </div>
              <div style={{ fontSize: 13, color: "#c9bea4", marginTop: 4 }}>
                {loc.suburb} · {loc.city}, {loc.country} · {loc.postcode}
              </div>

              <div style={{ marginTop: 16, display: "grid", gridTemplateColumns: "1fr 1fr", gap: "8px 24px", fontSize: 12.5, color: "#d8c79d" }}>
                <span style={{ display: "inline-flex", gap: 8, alignItems: "center" }}>
                  <IconPhone size={14} sw={1.6} style={{ color: "var(--warm-1)" }}/> {loc.phone}
                </span>
                <span style={{ display: "inline-flex", gap: 8, alignItems: "center" }}>
                  <IconChat size={14} sw={1.6} style={{ color: "var(--warm-1)" }}/> WhatsApp
                </span>
                <span style={{ display: "inline-flex", gap: 8, alignItems: "center" }}>
                  <IconMail size={14} sw={1.6} style={{ color: "var(--warm-1)" }}/> {loc.email}
                </span>
                <span style={{ display: "inline-flex", gap: 8, alignItems: "center" }}>
                  <IconClock size={14} sw={1.6} style={{ color: "var(--warm-1)" }}/> Open today
                </span>
              </div>

              <div style={{ marginTop: 14, padding: "10px 12px", background: "rgba(255,255,255,0.06)", borderRadius: 6, fontSize: 12.5, color: "#d8c79d", lineHeight: 1.55 }}>
                {loc.hours.weekday}<br/>{loc.hours.saturday}<br/>{loc.hours.sunday}
              </div>

              <div style={{ marginTop: 14, display: "flex", flexWrap: "wrap", gap: 6 }}>
                {loc.services.map(s => (
                  <span key={s} style={{
                    fontSize: 11, padding: "4px 9px", borderRadius: 999,
                    background: "rgba(255,255,255,0.08)", color: "#d8c79d",
                  }}>{s}</span>
                ))}
              </div>
            </div>

            <div style={{ marginTop: 22, display: "flex", gap: 8 }}>
              <button className="btn btn-primary">Get directions <IconArrow size={14} sw={2}/></button>
              <button className="btn" style={{ background: "rgba(255,255,255,0.08)", color: "#f6ecd9", border: "1px solid rgba(255,255,255,0.16)" }}>
                Book a showroom visit
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

Object.assign(window, { ShowroomBand, RegionMap });
