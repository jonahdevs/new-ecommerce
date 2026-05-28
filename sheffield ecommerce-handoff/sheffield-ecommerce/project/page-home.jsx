// Sheffield — Home page. Renders both editorial and workshop variants.

const HomePage = ({ navigate, addToCart, compare, setCompare, wishlist, toggleWish }) => {
  const D = window.SHEFFIELD_DATA;
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";
  const featuredProducts = D.products.filter(p => p.featured).slice(0, 5);

  return (
    <div className="page-fade">
      <ThinPromoBanner navigate={navigate}/>
      <HeroRotator navigate={navigate}/>
      {direction === "workshop"
        ? <HomeWorkshop navigate={navigate} addToCart={addToCart} compare={compare} setCompare={setCompare} wishlist={wishlist} toggleWish={toggleWish} featuredProducts={featuredProducts}/>
        : <HomeEditorial navigate={navigate} addToCart={addToCart} compare={compare} setCompare={setCompare} wishlist={wishlist} toggleWish={toggleWish} featuredProducts={featuredProducts}/>
      }
    </div>
  );
};

// ════════════════════════════════════════════════════════════════
// EDITORIAL — warm, magazine-led
// ════════════════════════════════════════════════════════════════
const HomeEditorial = ({ navigate, addToCart, compare, setCompare, wishlist, toggleWish, featuredProducts }) => {
  const D = window.SHEFFIELD_DATA;
  return (
    <>
      {/* TRUST STRIP — extends the hero's sunken zone */}
      <section style={{ background: "var(--bg-sunken)", borderBottom: "1px solid var(--line)" }}>
        <div className="container" style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", padding: "36px 0 40px" }}>
          {[
            { icon: <IconTruck size={20}/>, t: "Regional delivery", s: "Nairobi · Mombasa · Kampala · Kigali" },
            { icon: <IconWrench size={20}/>, t: "Install & commission", s: "Factory-trained engineers, on-site" },
            { icon: <IconShield size={20}/>, t: "Parts in stock", s: "98% of consumables next-day" },
            { icon: <IconCertified size={20}/>, t: "Trade pricing", s: "Net 30 for verified business accounts" },
          ].map((u, i) => (
            <div key={i} style={{ display: "flex", alignItems: "center", gap: 14, paddingLeft: i === 0 ? 0 : 24, borderLeft: i === 0 ? 0 : "1px solid var(--line-strong)" }}>
              <div style={{ color: "var(--accent)" }}>{u.icon}</div>
              <div>
                <div style={{ fontSize: 14, fontWeight: 500 }}>{u.t}</div>
                <div style={{ fontSize: 12.5, color: "var(--ink-3)" }}>{u.s}</div>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* CATEGORIES */}
      <section style={{ paddingTop: 88, paddingBottom: 32 }}>
        <div className="container">
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "end", marginBottom: 40 }}>
            <div>
              <div className="kicker">Shop by category</div>
              <h2 style={{ fontSize: 44, marginTop: 10, fontWeight: 400 }}>From service line to back of house.</h2>
            </div>
            <a href="#" onClick={(e) => { e.preventDefault(); navigate("catalog"); }} style={{ fontSize: 14, color: "var(--ink-2)" }}>All categories →</a>
          </div>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 16 }}>
            {D.categories.slice(0, 8).map(cat => <CategoryTile key={cat.slug} cat={cat} navigate={navigate}/>)}
          </div>
        </div>
      </section>

      {/* FEATURED PRODUCTS — editorial */}
      <section style={{ paddingTop: 72 }}>
        <div className="container">
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "end", marginBottom: 40 }}>
            <div>
              <div className="kicker">In rotation</div>
              <h2 style={{ fontSize: 44, marginTop: 10, fontWeight: 400 }}>What chefs are specifying this season.</h2>
            </div>
            <a href="#" onClick={(e) => { e.preventDefault(); navigate("catalog", { filter: "featured" }); }} style={{ fontSize: 14, color: "var(--ink-2)", display: "inline-flex", alignItems: "center", gap: 6 }}>See all featured →</a>
          </div>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 24 }}>
            {featuredProducts.slice(0, 4).map(p => (
              <ProductCard key={p.slug} product={p} navigate={navigate} compare={compare} setCompare={setCompare} addToCart={addToCart} wishlist={wishlist} toggleWish={toggleWish}/>
            ))}
          </div>
        </div>
      </section>

      {/* NEW ARRIVALS — editorial */}
      <section style={{ paddingTop: 72 }}>
        <div className="container">
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "end", marginBottom: 40 }}>
            <div>
              <div className="kicker">Just landed</div>
              <h2 style={{ fontSize: 44, marginTop: 10, fontWeight: 400 }}>New arrivals on the floor.</h2>
            </div>
            <a href="#" onClick={(e) => { e.preventDefault(); navigate("catalog", { filter: "new" }); }} style={{ fontSize: 14, color: "var(--ink-2)", display: "inline-flex", alignItems: "center", gap: 6 }}>See all new →</a>
          </div>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 24 }}>
            {D.products.filter(p => p.isNew).slice(0, 4).map(p => (
              <ProductCard key={p.slug} product={p} navigate={navigate} compare={compare} setCompare={setCompare} addToCart={addToCart} wishlist={wishlist} toggleWish={toggleWish}/>
            ))}
          </div>
        </div>
      </section>

      {/* RFQ feature */}
      <section style={{ paddingTop: 96 }}>
        <div className="container" style={{ display: "grid", gridTemplateColumns: "1fr", gap: 24 }}>
          {/* RFQ */}
          <div style={{
            background: "var(--ink)", color: "#f3eadd",
            borderRadius: "var(--radius-lg)", padding: 56,
            position: "relative", overflow: "hidden",
            minHeight: 380,
          }}>
            <div style={{
              position: "absolute", right: -80, bottom: -60, width: 360, height: 360,
              borderRadius: "50%", background: "radial-gradient(circle, var(--brand-700), transparent 70%)",
              opacity: 0.6,
            }}/>
            <div style={{ position: "relative", maxWidth: 480 }}>
              <div style={{ fontSize: 11.5, fontWeight: 600, letterSpacing: "0.12em", textTransform: "uppercase", color: "var(--warm-1)" }}>
                For procurement teams
              </div>
              <h3 style={{
                fontFamily: "var(--font-heading)", fontWeight: 400,
                fontSize: 42, lineHeight: 1.05, marginTop: 14, color: "#f6ecd9",
              }}>
                A formal quote, on letterhead, in <span style={{ fontStyle: "italic", color: "var(--accent)" }}>24 hours</span>.
              </h3>
              <p style={{ marginTop: 18, fontSize: 14.5, lineHeight: 1.55, color: "#c9bea4" }}>
                Upload a kitchen layout, a tender document or a list of SKUs. We'll come back with a costed bill of equipment, lead times and installation timeline. No account required.
              </p>
              <div style={{ marginTop: 24, display: "flex", gap: 12 }}>
                <button className="btn btn-primary btn-lg" onClick={() => navigate("catalog", { quote: true })}>
                  Start a quote <IconArrow size={16} sw={2}/>
                </button>
                <button className="btn btn-ghost btn-lg" style={{ color: "#f3eadd", border: "1px solid rgba(243,234,221,0.2)" }}>
                  Schedule a site visit
                </button>
              </div>
            </div>
          </div>


        </div>
      </section>

      {/* BRANDS marquee */}
      <BrandsMarquee navigate={navigate}/>

            {/* SHOWROOM LOCATIONS — shared between directions */}
      <ShowroomBand navigate={navigate}/>

      {/* EDITORIAL — case study cards */}
      <section style={{ paddingTop: 96, paddingBottom: 64 }}>
        <div className="container">
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "end", marginBottom: 40 }}>
            <div>
              <div className="kicker">From the field</div>
              <h2 style={{ fontSize: 40, marginTop: 10, fontWeight: 400 }}>Specs that earned their place on the line.</h2>
            </div>
          </div>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 24 }}>
            {[
              { tag: "Hotel · Diani Beach", title: "How a 220-cover hotel kitchen halved gas use", read: "5 min", kind: "tilting-pan", bg: "#e8dec3" },
              { tag: "Restaurant · Westlands", title: "Building a pastry section around one display case", read: "4 min", kind: "display", bg: "#d8c6a7" },
              { tag: "Catering · Kampala", title: "Banquet output × 3 with no extra footprint", read: "6 min", kind: "combi-oven", bg: "#c4a87a" },
            ].map((s, i) => (
              <article key={i} style={{ cursor: "pointer" }}>
                <div style={{
                  aspectRatio: "4 / 3", background: s.bg, borderRadius: "var(--radius-lg)",
                  overflow: "hidden", padding: 24, position: "relative",
                }}>
                  <ProductIllustration kind={s.kind}/>
                  <span style={{
                    position: "absolute", top: 14, left: 14,
                    fontSize: 11, fontWeight: 600, letterSpacing: "0.06em",
                    textTransform: "uppercase", color: "var(--warm-3)",
                  }}>{s.tag}</span>
                </div>
                <h4 style={{ fontFamily: "var(--font-heading)", fontWeight: 400, fontSize: 22, marginTop: 16, lineHeight: 1.2 }}>{s.title}</h4>
                <div style={{ marginTop: 8, fontSize: 13, color: "var(--ink-3)", display: "flex", alignItems: "center", gap: 6 }}>
                  <IconClock size={12} sw={1.6}/> {s.read} read
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>
    </>
  );
};

const CategoryTile = ({ cat, navigate }) => {
  return (
    <a href="#" onClick={(e) => { e.preventDefault(); navigate("category", { slug: cat.slug }); }} style={{
      borderRadius: "var(--radius-lg)",
      aspectRatio: "5 / 4", display: "flex", flexDirection: "column",
      position: "relative", overflow: "hidden",
      transition: "transform 200ms ease, box-shadow 200ms ease",
      boxShadow: "0 1px 0 rgba(35,28,14,0.04)",
    }}
    onMouseEnter={(e) => { e.currentTarget.style.transform = "translateY(-2px)"; e.currentTarget.style.boxShadow = "0 12px 30px -12px rgba(35,28,14,0.18)"; }}
    onMouseLeave={(e) => { e.currentTarget.style.transform = "translateY(0)"; e.currentTarget.style.boxShadow = "0 1px 0 rgba(35,28,14,0.04)"; }}>
      {cat.image && (
        <img src={cat.image} alt="" loading="lazy"
          style={{ position: "absolute", inset: 0, width: "100%", height: "100%", objectFit: "cover" }}/>
      )}
      {/* Bottom gradient + label */}
      <div aria-hidden style={{
        position: "absolute", inset: 0,
        background: "linear-gradient(to top, rgba(20,16,8,0.78) 0%, rgba(20,16,8,0.18) 45%, rgba(20,16,8,0) 70%)",
      }}/>
      <div style={{ position: "absolute", left: 20, bottom: 18, right: 20, color: "#fff" }}>
        <div style={{ fontFamily: "var(--font-heading)", fontSize: 22, lineHeight: 1.1 }}>{cat.name}</div>
        <div style={{ fontSize: 12, color: "rgba(255,255,255,0.78)", marginTop: 4 }}>{cat.count} products →</div>
      </div>
      {cat.icon && (
        <div style={{
          position: "absolute", top: 14, left: 14,
          width: 36, height: 36, borderRadius: 999,
          background: "rgba(255,255,255,0.94)",
          display: "flex", alignItems: "center", justifyContent: "center",
          padding: 7,
          boxShadow: "0 2px 8px rgba(0,0,0,0.18)",
        }}>
          <img src={cat.icon} alt="" style={{ width: "100%", height: "100%", objectFit: "contain" }}/>
        </div>
      )}
    </a>
  );
};

// ════════════════════════════════════════════════════════════════
// WORKSHOP — denser, catalog-led
// ════════════════════════════════════════════════════════════════
const HomeWorkshop = ({ navigate, addToCart, compare, setCompare, wishlist, toggleWish, featuredProducts }) => {
  const D = window.SHEFFIELD_DATA;
  return (
    <>
      {/* USPs strip */}
      <section style={{ background: "var(--bg-sunken)", borderTop: "1px solid var(--line)", borderBottom: "1px solid var(--line)" }}>
        <div className="container" style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", padding: "22px 0" }}>
          {[
            { icon: <IconTruck size={18}/>, t: "Regional delivery", s: "KE · UG · TZ · RW" },
            { icon: <IconWrench size={18}/>, t: "Install & commission", s: "Factory-trained" },
            { icon: <IconShield size={18}/>, t: "Spares in stock", s: "Next-day for 98%" },
            { icon: <IconCertified size={18}/>, t: "Net 30 terms", s: "Verified businesses" },
          ].map((u, i) => (
            <div key={i} style={{ display: "flex", alignItems: "center", gap: 12, paddingLeft: i === 0 ? 0 : 20, borderLeft: i === 0 ? 0 : "1px solid var(--line)" }}>
              <div style={{ color: "var(--accent)" }}>{u.icon}</div>
              <div>
                <div style={{ fontSize: 13.5, fontWeight: 600 }}>{u.t}</div>
                <div style={{ fontSize: 12, color: "var(--ink-3)" }}>{u.s}</div>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* CATEGORIES — dense */}
      <section style={{ paddingTop: 56 }}>
        <div className="container">
          <SectionHeader title="Shop by category" link="All →" onLink={() => navigate("catalog")}/>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(6, 1fr)", gap: 20, rowGap: 28 }}>
            {D.categories.slice(0, 12).map(cat => <CategoryChipWS key={cat.slug} cat={cat} navigate={navigate}/>)}
          </div>
        </div>
      </section>

      {/* BRANDS marquee */}
      <BrandsMarquee navigate={navigate}/>

      {/* FEATURED */}
      <section style={{ paddingTop: 56 }}>
        <div className="container">
          <SectionHeader title="Featured equipment" link="See all featured →" onLink={() => navigate("catalog", { filter: "featured" })}/>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 14 }}>
            {featuredProducts.slice(0, 4).map(p => (
              <ProductCard key={p.slug} product={p} navigate={navigate} compare={compare} setCompare={setCompare} addToCart={addToCart} wishlist={wishlist} toggleWish={toggleWish}/>
            ))}
          </div>
        </div>
      </section>

      {/* NEW ARRIVALS — workshop */}
      <section style={{ paddingTop: 56 }}>
        <div className="container">
          <SectionHeader title="New arrivals" link="See all new →" onLink={() => navigate("catalog", { filter: "new" })}/>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 14 }}>
            {D.products.filter(p => p.isNew).slice(0, 4).map(p => (
              <ProductCard key={p.slug} product={p} navigate={navigate} compare={compare} setCompare={setCompare} addToCart={addToCart} wishlist={wishlist} toggleWish={toggleWish}/>
            ))}
          </div>
        </div>
      </section>

      {/* RFQ banner workshop style */}
      <ShowroomBand navigate={navigate}/>
      <section style={{ paddingTop: 56 }}>
        <div className="container">
          <div style={{
            background: "var(--ink)", color: "#fff",
            borderRadius: "var(--radius-lg)", padding: 36,
            display: "grid", gridTemplateColumns: "1fr auto", gap: 24, alignItems: "center",
          }}>
            <div>
              <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.12em", textTransform: "uppercase", color: "var(--warm-1)" }}>For procurement</div>
              <div style={{ fontFamily: "var(--font-heading)", fontSize: 32, lineHeight: 1.1, marginTop: 8 }}>
                Upload your tender or BOQ — formal quote in 24 hours.
              </div>
              <div style={{ marginTop: 10, fontSize: 14, color: "#c9bea4" }}>
                Upload PDF or Excel · We respond in business hours · No account required.
              </div>
            </div>
            <div style={{ display: "flex", gap: 10 }}>
              <button className="btn btn-primary btn-lg" onClick={() => navigate("catalog", { quote: true })}>Start a quote</button>
              <button className="btn btn-lg" style={{ background: "transparent", color: "#fff", border: "1px solid rgba(255,255,255,0.2)" }}>Book site visit</button>
            </div>
          </div>
        </div>
      </section>
    </>
  );
};

const SectionHeader = ({ title, link, onLink }) => (
  <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline", marginBottom: 16, paddingBottom: 12, borderBottom: "1px solid var(--line)" }}>
    <h2 style={{ fontSize: 22, fontWeight: 600, letterSpacing: "-0.01em" }}>{title}</h2>
    {link && <a href="#" onClick={(e) => { e.preventDefault(); onLink && onLink(); }} style={{ fontSize: 13, color: "var(--ink-2)" }}>{link}</a>}
  </div>
);

const CategoryChipWS = ({ cat, navigate }) => {
  return (
    <a href="#" onClick={(e) => { e.preventDefault(); navigate("category", { slug: cat.slug }); }}
      style={{
        display: "block",
        textDecoration: "none",
        transition: "opacity 120ms ease",
      }}
      onMouseEnter={(e) => {
        const img = e.currentTarget.querySelector("img.ws-cat-img");
        if (img) img.style.transform = "scale(1.04)";
        const label = e.currentTarget.querySelector(".ws-cat-label");
        if (label) label.style.color = "var(--accent)";
      }}
      onMouseLeave={(e) => {
        const img = e.currentTarget.querySelector("img.ws-cat-img");
        if (img) img.style.transform = "scale(1)";
        const label = e.currentTarget.querySelector(".ws-cat-label");
        if (label) label.style.color = "var(--ink)";
      }}>
      <div style={{
        position: "relative",
        aspectRatio: "1 / 1",
        background: "var(--bg-sunken)",
        overflow: "hidden",
        borderBottom: "2px solid var(--ink)",
      }}>
        {cat.image && (
          <img className="ws-cat-img" src={cat.image} alt="" loading="lazy"
            style={{
              width: "100%", height: "100%", objectFit: "cover", display: "block",
              transition: "transform 240ms ease",
            }}/>
        )}
      </div>
      <div style={{ paddingTop: 10, display: "flex", alignItems: "baseline", justifyContent: "space-between", gap: 8 }}>
        <div className="ws-cat-label" style={{
          fontSize: 11.5,
          fontWeight: 600,
          letterSpacing: "0.06em",
          textTransform: "uppercase",
          color: "var(--ink)",
          lineHeight: 1.25,
          transition: "color 120ms ease",
        }}>{cat.name}</div>
        <div style={{
          fontSize: 11,
          color: "var(--ink-3)",
          fontVariantNumeric: "tabular-nums",
          flexShrink: 0,
        }}>{cat.count}</div>
      </div>
    </a>
  );
};

const ProductIllustrationFloating = ({ kind }) => (
  <div style={{
    position: "absolute", right: -40, bottom: -50, width: 360, height: 360,
    opacity: 0.95, pointerEvents: "none",
  }}>
    <ProductIllustration kind={kind}/>
  </div>
);

Object.assign(window, { HomePage });

