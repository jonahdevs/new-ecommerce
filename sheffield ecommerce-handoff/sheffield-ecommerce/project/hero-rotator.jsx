// Sheffield — hero banner rotator + thin promo banner.
// Uses real images supplied by the brand.

const HERO_BANNERS = [
  {
    src: "banners/topline.webp",
    alt: "Add to your topline — premium kitchen equipment",
    cta: { label: "Upgrade now", target: { name: "catalog" } },
    align: "right",
    accentDark: false,
  },
  {
    src: "banners/coffee-machines.webp",
    alt: "Premium coffee machines — best price guaranteed",
    cta: { label: "Shop coffee machines", target: { name: "category", params: { slug: "beverage" } } },
    align: "right",
    accentDark: true,
  },
  {
    src: "banners/refrigeration.webp",
    alt: "Smart cooling — refrigeration solutions",
    cta: { label: "Shop refrigeration", target: { name: "category", params: { slug: "refrigeration" } } },
    align: "right",
    accentDark: false,
  },
  {
    src: "banners/bakery-prep.webp",
    alt: "Bakery preparation equipment",
    cta: { label: "Shop bakery prep", target: { name: "category", params: { slug: "preparation" } } },
    align: "center",
    accentDark: false,
  },
  {
    src: "banners/clearance-sale.webp",
    alt: "Limited time clearance sale",
    cta: { label: "Shop clearance", target: { name: "catalog", params: { clearance: true } } },
    align: "left",
    accentDark: false,
  },
];

const ROTATE_MS = 6500;

const HeroRotator = ({ navigate }) => {
  const [idx, setIdx] = React.useState(0);
  const [paused, setPaused] = React.useState(false);
  const total = HERO_BANNERS.length;

  // auto-advance
  React.useEffect(() => {
    if (paused) return;
    const id = setInterval(() => setIdx(i => (i + 1) % total), ROTATE_MS);
    return () => clearInterval(id);
  }, [paused, total]);

  const go = (target) => {
    if (target.name === "category") navigate("category", target.params || {});
    else if (target.name === "product") navigate("product", target.params || {});
    else navigate(target.name, target.params || {});
  };

  return (
    <section
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      style={{
        position: "relative",
        background: "var(--bg-sunken)",
        borderBottom: "1px solid var(--line)",
      }}>
      <div className="container" style={{ paddingTop: 20, paddingBottom: 20 }}>
        <div style={{
          position: "relative",
          borderRadius: "var(--radius-lg)",
          overflow: "hidden",
          aspectRatio: "2181 / 624",
          background: "#1a1a1a",
        }}>
          {/* Slides */}
          {HERO_BANNERS.map((b, i) => (
            <button key={b.src} type="button"
              onClick={() => go(b.cta.target)}
              aria-label={b.alt}
              aria-hidden={i !== idx}
              tabIndex={i === idx ? 0 : -1}
              style={{
                position: "absolute", inset: 0, padding: 0,
                background: "transparent", border: 0,
                cursor: "pointer",
                opacity: i === idx ? 1 : 0,
                transition: "opacity 600ms ease",
                pointerEvents: i === idx ? "auto" : "none",
              }}>
              <img src={b.src} alt={b.alt}
                style={{
                  width: "100%", height: "100%",
                  objectFit: "cover", objectPosition: b.align === "left" ? "left center" : b.align === "right" ? "right center" : "center",
                  display: "block",
                }}
                draggable={false}
              />
              {/* CTA pill — bottom corner */}
              <span aria-hidden style={{
                position: "absolute",
                left: b.align === "left" ? "auto" : 24, right: b.align === "left" ? 24 : "auto",
                bottom: 24,
                background: "rgba(255,255,255,0.92)",
                color: "var(--ink)",
                fontSize: 13, fontWeight: 600,
                padding: "10px 16px", borderRadius: 999,
                display: "inline-flex", alignItems: "center", gap: 8,
                boxShadow: "0 4px 14px rgba(0,0,0,0.2)",
                backdropFilter: "blur(8px)",
                pointerEvents: "none",
                opacity: i === idx ? 1 : 0,
                transform: i === idx ? "translateY(0)" : "translateY(8px)",
                transition: "opacity 500ms ease 250ms, transform 500ms ease 250ms",
              }}>
                {b.cta.label} <IconArrow size={14} sw={2}/>
              </span>
            </button>
          ))}

          {/* Prev / Next */}
          <button onClick={() => setIdx(i => (i - 1 + total) % total)}
            aria-label="Previous slide"
            style={navArrowStyle("left")}>
            <IconArrowL size={16} sw={2.4}/>
          </button>
          <button onClick={() => setIdx(i => (i + 1) % total)}
            aria-label="Next slide"
            style={navArrowStyle("right")}>
            <IconArrow size={16} sw={2.4}/>
          </button>

          {/* Dots */}
          <div style={{
            position: "absolute", bottom: 16, left: "50%",
            transform: "translateX(-50%)",
            display: "flex", gap: 6, alignItems: "center",
            background: "rgba(0,0,0,0.35)",
            padding: "6px 10px", borderRadius: 999,
            backdropFilter: "blur(6px)",
          }}>
            {HERO_BANNERS.map((_, i) => (
              <button key={i} onClick={() => setIdx(i)} aria-label={`Go to slide ${i + 1}`}
                style={{
                  width: i === idx ? 22 : 6, height: 6,
                  background: i === idx ? "#fff" : "rgba(255,255,255,0.55)",
                  border: 0, borderRadius: 999, padding: 0, cursor: "pointer",
                  transition: "width 240ms ease, background 240ms ease",
                }}/>
            ))}
          </div>

          {/* slide counter top-right */}
          <div style={{
            position: "absolute", top: 14, right: 14,
            background: "rgba(0,0,0,0.35)", color: "#fff",
            padding: "4px 10px", borderRadius: 999, fontSize: 11,
            fontVariantNumeric: "tabular-nums", letterSpacing: "0.04em",
            backdropFilter: "blur(6px)",
            display: "flex", alignItems: "center", gap: 6,
          }}>
            <span style={{ fontWeight: 600 }}>{String(idx + 1).padStart(2, "0")}</span>
            <span style={{ opacity: 0.6 }}>/ {String(total).padStart(2, "0")}</span>
            {paused && <span style={{ opacity: 0.7, marginLeft: 4 }}>· paused</span>}
          </div>
        </div>
      </div>
    </section>
  );
};

const navArrowStyle = (side) => ({
  position: "absolute",
  top: "50%", transform: "translateY(-50%)",
  [side]: 12,
  width: 40, height: 40, padding: 0,
  background: "rgba(255,255,255,0.85)",
  color: "var(--ink)",
  border: 0, borderRadius: 999,
  display: "inline-flex", alignItems: "center", justifyContent: "center",
  cursor: "pointer",
  boxShadow: "0 4px 14px rgba(0,0,0,0.18)",
  backdropFilter: "blur(8px)",
  transition: "background 120ms ease",
});

// ───────── Thin promo banner ─────────
const ThinPromoBanner = ({ navigate }) => (
  <section style={{ background: "var(--bg-sunken)", padding: "12px 0 8px" }}>
    <div className="container">
      <button onClick={() => navigate("catalog")}
        aria-label="Your business, fully equipped — up to 20% off mega sale"
        style={{
          width: "100%", border: 0, padding: 0,
          borderRadius: 6, overflow: "hidden", cursor: "pointer",
          display: "block", background: "transparent",
          aspectRatio: "3117 / 400",
          boxShadow: "0 2px 8px rgba(0,0,0,0.06)",
        }}>
        <img src="banners/thin-banner.webp" alt=""
          style={{ width: "100%", height: "100%", objectFit: "cover", display: "block" }}
          draggable={false}/>
      </button>
    </div>
  </section>
);

Object.assign(window, { HeroRotator, ThinPromoBanner, HERO_BANNERS });
