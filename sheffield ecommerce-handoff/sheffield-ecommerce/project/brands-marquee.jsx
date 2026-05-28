// Sheffield — Brands marquee. Auto-scrolling logo strip with a fade on the left side
// and a static title card pinned at the start.

const BrandsMarquee = ({ navigate }) => {
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";
  const brands = window.SHEFFIELD_DATA.brands;
  // Duplicate the list once so the CSS animation can loop seamlessly.
  const loop = [...brands, ...brands];

  return (
    <section style={{ paddingTop: direction === "workshop" ? 56 : 96 }}>
      <div className="container">
        <div style={{
          position: "relative",
          background: "var(--bg-elev)",
          border: "1px solid var(--line)",
          borderRadius: "var(--radius-lg)",
          overflow: "hidden",
        }}>
          <div style={{ display: "grid", gridTemplateColumns: "auto 1fr", alignItems: "stretch" }}>
            {/* Static title card */}
            <div style={{
              padding: "0 32px",
              background: "var(--bg-sunken)", color: "var(--ink)",
              minWidth: 240,
              display: "flex", flexDirection: "column", justifyContent: "center",
              borderRight: "1px solid var(--line)",
              position: "relative", zIndex: 2,
            }}>
              <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.12em", textTransform: "uppercase", color: "var(--accent)" }}>
                Authorised distributor
              </div>
              <h2 style={{
                fontFamily: "var(--font-heading)",
                fontSize: direction === "workshop" ? 22 : 26, lineHeight: 1.15,
                fontWeight: 400, marginTop: 8,
              }}>
                Brands we carry.
              </h2>
              <a href="#" onClick={(e) => { e.preventDefault(); navigate("catalog", { brands: true }); }}
                style={{ marginTop: 12, fontSize: 12.5, color: "var(--ink-2)", display: "inline-flex", alignItems: "center", gap: 6 }}>
                All brands →
              </a>
            </div>

            {/* Marquee track */}
            <div style={{ position: "relative", overflow: "hidden", display: "flex", alignItems: "stretch" }}>
              {/* Fade gradients */}
              <div aria-hidden style={{
                position: "absolute", left: 0, top: 0, bottom: 0, width: 80, zIndex: 1,
                background: "linear-gradient(to right, var(--bg-elev) 0%, rgba(255,255,255,0) 100%)",
                pointerEvents: "none",
              }}/>
              <div aria-hidden style={{
                position: "absolute", right: 0, top: 0, bottom: 0, width: 80, zIndex: 1,
                background: "linear-gradient(to left, var(--bg-elev) 0%, rgba(255,255,255,0) 100%)",
                pointerEvents: "none",
              }}/>

              <div className="brand-marquee-track" style={{
                display: "flex", gap: 0,
                animation: "brandMarquee 40s linear infinite",
                width: "max-content",
                alignItems: "stretch",
              }}>
                {loop.map((b, i) => (
                  <a key={`${b.slug}-${i}`} href="#"
                    onClick={(e) => { e.preventDefault(); navigate("catalog", { brand: b.slug }); }}
                    style={{
                      flex: "0 0 auto",
                      width: 200,
                      alignSelf: "stretch",
                      borderRight: "1px solid var(--line)",
                      display: "flex", flexDirection: "column", justifyContent: "center", alignItems: "center", gap: 4,
                      padding: "36px 16px", textAlign: "center",
                      transition: "background 160ms ease",
                    }}
                    onMouseEnter={(e) => e.currentTarget.style.background = "var(--bg-sunken)"}
                    onMouseLeave={(e) => e.currentTarget.style.background = "transparent"}>
                    <div style={{ fontFamily: "var(--font-heading)", fontSize: 18, color: "var(--ink)" }}>{b.name}</div>
                    <div style={{ fontSize: 10.5, color: "var(--ink-4)", textTransform: "uppercase", letterSpacing: "0.08em" }}>{b.country} · {b.founded}</div>
                  </a>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

// inject keyframes once
if (typeof document !== "undefined" && !document.getElementById("__sheffield_brand_marquee_kf")) {
  const s = document.createElement("style"); s.id = "__sheffield_brand_marquee_kf";
  s.textContent = `
    @keyframes brandMarquee {
      from { transform: translateX(0); }
      to   { transform: translateX(-50%); }
    }
    .brand-marquee-track:hover { animation-play-state: paused; }
  `;
  document.head.appendChild(s);
}

Object.assign(window, { BrandsMarquee });
