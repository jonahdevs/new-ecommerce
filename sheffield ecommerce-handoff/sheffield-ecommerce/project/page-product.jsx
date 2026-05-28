// Sheffield — Product detail page.

const ProductPage = ({ navigate, addToCart, compare, setCompare, wishlist = [], toggleWish, params }) => {
  const D = window.SHEFFIELD_DATA;
  const KES = D.KES;
  const product = D.products.find(p => p.slug === params.slug) || D.products[0];
  const brand = D.brands.find(b => b.slug === product.brand);
  const category = D.categories.find(c => c.slug === product.category);
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";

  const [galleryIdx, setGalleryIdx] = React.useState(0);
  const [qty, setQty] = React.useState(1);
  const [tab, setTab] = React.useState("specs");
  const [installation, setInstallation] = React.useState(false);
  const [extWarranty, setExtWarranty] = React.useState(false);
  const [showRFQ, setShowRFQ] = React.useState(false);

  const isCompared = compare.includes(product.slug);
  const isWished = wishlist.includes(product.slug);

  const installPrice = Math.round(product.price * 0.06);
  const warrantyPrice = Math.round(product.price * 0.04);
  const totalAddons = (installation ? installPrice : 0) + (extWarranty ? warrantyPrice : 0);
  const unitPrice = product.price * qty + totalAddons * qty;

  const related = D.products.filter(p => p.category === product.category && p.slug !== product.slug).slice(0, 4);

  // Reset when product changes
  React.useEffect(() => { setGalleryIdx(0); setQty(1); }, [product.slug]);

  return (
    <div className="page-fade">
      <div className="container" style={{ paddingTop: 24, paddingBottom: 80 }}>
        {/* Breadcrumb */}
        <div style={{ fontSize: 12.5, color: "var(--ink-3)", marginBottom: 24, display: "flex", gap: 6, alignItems: "center" }}>
          <a href="#" onClick={(e) => { e.preventDefault(); navigate("home"); }}>Home</a>
          <IconChevronR size={12} sw={1.6}/>
          <a href="#" onClick={(e) => { e.preventDefault(); navigate("catalog"); }}>Catalog</a>
          <IconChevronR size={12} sw={1.6}/>
          <a href="#" onClick={(e) => { e.preventDefault(); navigate("category", { slug: category.slug }); }}>{category.name}</a>
          <IconChevronR size={12} sw={1.6}/>
          <span style={{ color: "var(--ink)" }}>{product.name}</span>
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "1.05fr 1fr", gap: 56 }}>
          {/* GALLERY */}
          <div>
            <div style={{
              background: "var(--surface)", border: "1px solid var(--line)",
              borderRadius: "var(--radius-lg)", aspectRatio: "1/1",
              position: "relative", overflow: "hidden", padding: 48,
            }}>
              {product.badge && (
                <span style={{
                  position: "absolute", top: 20, left: 20,
                  fontSize: 11, fontWeight: 700, letterSpacing: "0.08em", textTransform: "uppercase",
                  color: "var(--accent)",
                }}>● {product.badge}</span>
              )}
              <div style={{ position: "absolute", top: 20, right: 20, display: "flex", gap: 6 }}>
                <button onClick={() => toggleWish && toggleWish(product.slug)}
                  className="btn btn-sm btn-outline"
                  style={{ width: 38, height: 38, padding: 0, background: isWished ? "var(--accent)" : "#fff", borderColor: isWished ? "var(--accent)" : "var(--line-strong)", color: isWished ? "#fff" : "var(--ink)" }}>
                  <IconHeart size={16} sw={1.6} fill={isWished ? "currentColor" : "none"}/>
                </button>
                <button className="btn btn-sm btn-outline" style={{ width: 38, height: 38, padding: 0, background: "#fff" }}><IconShare size={16} sw={1.6}/></button>
              </div>
              {product.photos && product.photos[galleryIdx] ? (
                <img src={product.photos[galleryIdx]} alt={product.name}
                  style={{ width: "100%", height: "100%", objectFit: "contain", display: "block", padding: 12 }}/>
              ) : (
                <ProductIllustration kind={product.kind}/>
              )}
              <div style={{ position: "absolute", bottom: 20, left: 20, right: 20, display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                <div style={{ fontSize: 12, color: "var(--ink-3)" }}>{galleryIdx + 1} / {product.photos?.length || product.images}</div>
                <div style={{ fontSize: 12, color: "var(--ink-3)", display: "flex", alignItems: "center", gap: 6 }}>
                  <IconLeaf size={12} sw={1.6}/> {product.origin}
                </div>
              </div>
            </div>
            {/* Thumbnails */}
            <div style={{ display: "grid", gridTemplateColumns: `repeat(${product.photos?.length || product.images}, 1fr)`, gap: 10, marginTop: 14 }}>
              {Array.from({ length: product.photos?.length || product.images }).map((_, i) => (
                <button key={i} onClick={() => setGalleryIdx(i)} style={{
                  aspectRatio: "1/1", background: "var(--surface)",
                  border: `1px solid ${i === galleryIdx ? "var(--accent)" : "var(--line)"}`,
                  borderRadius: "var(--radius)", padding: 8, cursor: "pointer",
                  transition: "border-color 120ms ease",
                  overflow: "hidden",
                }}>
                  {product.photos?.[i] ? (
                    <img src={product.photos[i]} alt=""
                      style={{ width: "100%", height: "100%", objectFit: "contain", display: "block" }}/>
                  ) : (
                    <ProductIllustration kind={product.kind}/>
                  )}
                </button>
              ))}
            </div>
          </div>

          {/* INFO — dispatched by product type */}
          <div>
            {product.type === "variant" ? (
              <VariantPanel product={product} qty={qty} setQty={setQty} addToCart={addToCart}
                onWish={() => toggleWish && toggleWish(product.slug)} isWished={isWished}
                onCompare={() => setCompare(isCompared ? compare.filter(s => s !== product.slug) : [...compare, product.slug].slice(0, 4))} isCompared={isCompared}
                onRFQ={() => setShowRFQ(true)}/>
            ) : product.type === "bundled" ? (
              <BundlePanel product={product} addToCart={addToCart} navigate={navigate}
                onWish={() => toggleWish && toggleWish(product.slug)} isWished={isWished}
                onCompare={() => setCompare(isCompared ? compare.filter(s => s !== product.slug) : [...compare, product.slug].slice(0, 4))} isCompared={isCompared}
                onRFQ={() => setShowRFQ(true)}/>
            ) : product.type === "grouped" ? (
              <GroupedPanel product={product} addToCart={addToCart}
                onWish={() => toggleWish && toggleWish(product.slug)} isWished={isWished}
                onCompare={() => setCompare(isCompared ? compare.filter(s => s !== product.slug) : [...compare, product.slug].slice(0, 4))} isCompared={isCompared}
                onRFQ={() => setShowRFQ(true)}/>
            ) : product.type === "virtual" ? (
              <VirtualPanel product={product} qty={qty} setQty={setQty} addToCart={addToCart}
                onWish={() => toggleWish && toggleWish(product.slug)} isWished={isWished}
                onCompare={() => setCompare(isCompared ? compare.filter(s => s !== product.slug) : [...compare, product.slug].slice(0, 4))} isCompared={isCompared}
                onRFQ={() => setShowRFQ(true)}/>
            ) : product.type === "downloadable" ? (
              <DownloadablePanel product={product} addToCart={addToCart}
                onWish={() => toggleWish && toggleWish(product.slug)} isWished={isWished}
                onCompare={() => setCompare(isCompared ? compare.filter(s => s !== product.slug) : [...compare, product.slug].slice(0, 4))} isCompared={isCompared}/>
            ) : (
              <SimplePanel product={product} qty={qty} setQty={setQty} addToCart={addToCart}
                installation={installation} setInstallation={setInstallation}
                extWarranty={extWarranty} setExtWarranty={setExtWarranty}
                onWish={() => toggleWish && toggleWish(product.slug)} isWished={isWished}
                onCompare={() => setCompare(isCompared ? compare.filter(s => s !== product.slug) : [...compare, product.slug].slice(0, 4))} isCompared={isCompared}
                onRFQ={() => setShowRFQ(true)}/>
            )}
          </div>
        </div>

        {/* ACCESSORIES & SPARE PARTS */}
        <AccessoriesPanel product={product} navigate={navigate} addToCart={addToCart}/>

        {/* TABS — specs, description, install, reviews */}
        <div style={{ marginTop: 96 }}>
          <div style={{ display: "flex", gap: 0, borderBottom: "1px solid var(--line)" }}>
            {[
              { id: "specs", label: "Specifications" },
              { id: "overview", label: "Overview" },
              { id: "install", label: "Installation & service" },
              { id: "downloads", label: "Documents" },
              { id: "reviews", label: `Reviews (${product.reviews})` },
            ].map(t => (
              <button key={t.id} onClick={() => setTab(t.id)} style={{
                background: "transparent", border: 0,
                padding: "14px 20px", fontSize: 14, color: tab === t.id ? "var(--ink)" : "var(--ink-3)",
                fontWeight: tab === t.id ? 600 : 500,
                borderBottom: tab === t.id ? "2px solid var(--accent)" : "2px solid transparent",
                marginBottom: -1,
              }}>{t.label}</button>
            ))}
          </div>

          <div style={{ paddingTop: 32 }}>
            {tab === "specs" && <SpecsTab product={product}/>}
            {tab === "overview" && <OverviewTab product={product} brand={brand}/>}
            {tab === "install" && <InstallTab product={product}/>}
            {tab === "downloads" && <DownloadsTab product={product}/>}
            {tab === "reviews" && <ReviewsTab product={product}/>}
          </div>
        </div>

        {/* RELATED */}
        <div style={{ marginTop: 96 }}>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "end", marginBottom: 24 }}>
            <h2 style={{ fontSize: direction === "workshop" ? 24 : 36, fontWeight: 400 }}>
              {direction === "workshop" ? "Related equipment" : "Often specified with this."}
            </h2>
            <a href="#" onClick={(e) => { e.preventDefault(); navigate("category", { slug: category.slug }); }} style={{ fontSize: 13, color: "var(--ink-2)" }}>
              More in {category.name} →
            </a>
          </div>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: direction === "workshop" ? 14 : 24 }}>
            {related.map(p => (
              <ProductCard key={p.slug} product={p} navigate={navigate} compare={compare} setCompare={setCompare} addToCart={addToCart} wishlist={wishlist} toggleWish={toggleWish}/>
            ))}
          </div>
        </div>
      </div>

      {showRFQ && <RFQModal product={product} onClose={() => setShowRFQ(false)}/>}
    </div>
  );
};

// ───────── Small bits ─────────
const AddonRow = ({ checked, onChange, title, description, price }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  return (
    <label style={{
      display: "grid", gridTemplateColumns: "auto 1fr auto", gap: 14, alignItems: "start",
      padding: "14px 14px", border: "1px solid var(--line)", borderRadius: "var(--radius)",
      cursor: "pointer", marginBottom: 8,
      background: checked ? "var(--bg-sunken)" : "transparent",
      borderColor: checked ? "var(--ink-2)" : "var(--line)",
    }}>
      <span style={{
        width: 18, height: 18, marginTop: 2,
        border: "1.5px solid " + (checked ? "var(--accent)" : "var(--line-strong)"),
        borderRadius: 4, display: "inline-flex", alignItems: "center", justifyContent: "center",
        background: checked ? "var(--accent)" : "transparent",
      }}>{checked && <IconCheck size={13} sw={2.4} stroke="#fff"/>}</span>
      <input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} style={{ display: "none" }}/>
      <div>
        <div style={{ fontSize: 14, fontWeight: 500 }}>{title}</div>
        <div style={{ fontSize: 12.5, color: "var(--ink-3)", marginTop: 2 }}>{description}</div>
      </div>
      <div style={{ fontWeight: 600, fontVariantNumeric: "tabular-nums" }}>+ {KES(price)}</div>
    </label>
  );
};

// ─── Simple panel (the original layout, kept for fallback) ───
const SimplePanel = ({ product, qty, setQty, addToCart, installation, setInstallation, extWarranty, setExtWarranty, onWish, isWished, onCompare, isCompared, onRFQ }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const installPrice = Math.round(product.price * 0.06);
  const warrantyPrice = Math.round(product.price * 0.04);
  const totalAddons = (installation ? installPrice : 0) + (extWarranty ? warrantyPrice : 0);
  const unitPrice = product.price * qty + totalAddons * qty;
  return (
    <>
      <PanelHeader product={product}/>

      <div style={{ display: "flex", alignItems: "center", gap: 18, marginTop: 18, fontSize: 13 }}>
        <span style={{ display: "inline-flex", alignItems: "center", gap: 4 }}>
          <span style={{ color: "var(--warm-1)", display: "inline-flex", gap: 1 }}>
            {[1,2,3,4,5].map(n => (
              <IconStarFill key={n} size={13} stroke="none" style={{ color: n <= Math.round(product.rating) ? "var(--warm-1)" : "var(--line)" }}/>
            ))}
          </span>
          <span style={{ marginLeft: 6 }}>{product.rating}</span>
          <span style={{ color: "var(--ink-3)" }}>({product.reviews} reviews)</span>
        </span>
        <span style={{ color: "var(--ink-3)" }}>·</span>
        <span style={{ color: "var(--ink-3)" }}>SKU: <span style={{ color: "var(--ink-2)", fontVariantNumeric: "tabular-nums" }}>{product.sku}</span></span>
      </div>

      <div style={{ marginTop: 28, padding: "20px 0", borderTop: "1px solid var(--line)", borderBottom: "1px solid var(--line)" }}>
        <div style={{ display: "flex", alignItems: "baseline", gap: 14 }}>
          {product.compareAt && <span style={{ fontSize: 18, color: "var(--ink-4)", textDecoration: "line-through", whiteSpace: "nowrap" }}>{KES(product.compareAt)}</span>}
          <Money value={product.price} big/>
          <span style={{ fontSize: 12.5, color: "var(--ink-3)" }}>excl. 16% VAT</span>
        </div>
        {product.bulkPrice && (
          <div style={{ marginTop: 10, padding: "10px 12px", background: "var(--bg-sunken)", borderRadius: 6, fontSize: 13, display: "flex", justifyContent: "space-between" }}>
            <span><strong>Bulk pricing.</strong> Order {product.bulkPrice.qty}+ to drop to {KES(product.bulkPrice.price)} each.</span>
            <a href="#" onClick={(e) => { e.preventDefault(); onRFQ(); }} style={{ color: "var(--accent)" }}>Larger volume?</a>
          </div>
        )}
        <div style={{ marginTop: 10, display: "flex", flexWrap: "wrap", gap: 14, fontSize: 13, color: "var(--ink-2)" }}>
          <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
            <span style={{ width: 8, height: 8, borderRadius: 999, background: product.inStock > 0 ? "#2f7a4a" : "var(--warm-1)" }}/>
            {product.inStock > 0 ? `${product.inStock} in stock — Nairobi warehouse` : "Made to order"}
          </span>
          <span style={{ color: "var(--ink-3)" }}>· {product.leadTime}</span>
        </div>
      </div>

      {/* Add-ons */}
      <div style={{ marginTop: 24 }}>
        <div style={{ fontSize: 12, fontWeight: 700, letterSpacing: "0.08em", textTransform: "uppercase", color: "var(--ink-3)", marginBottom: 10 }}>
          Bundle add-ons
        </div>
        <AddonRow checked={installation} onChange={setInstallation}
          title="Professional installation & commissioning"
          description="Factory-trained engineer, on-site, parts & connections included."
          price={installPrice}/>
        <AddonRow checked={extWarranty} onChange={setExtWarranty}
          title="Extended warranty +24 months"
          description="Adds 2 years to factory cover. Annual service visits included."
          price={warrantyPrice}/>
      </div>

      <QtyAndCTA product={product} qty={qty} setQty={setQty}
        unitPrice={(product.price + totalAddons)}
        addLabel="Add to cart"
        addClick={() => addToCart(product.slug, qty)}
        onRFQ={onRFQ}/>
      <SecondaryActions product={product} onWish={onWish} isWished={isWished} onCompare={onCompare} isCompared={isCompared}/>
      <TrustGrid product={product}/>
    </>
  );
};

const TrustItem = ({ icon, t, s }) => (
  <div style={{ display: "flex", gap: 10, alignItems: "start" }}>
    <div style={{ color: "var(--accent)" }}>{icon}</div>
    <div>
      <div style={{ fontSize: 12.5, fontWeight: 600 }}>{t}</div>
      <div style={{ fontSize: 12, color: "var(--ink-3)" }}>{s}</div>
    </div>
  </div>
);

const SpecsTab = ({ product }) => (
  <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "0 56px", maxWidth: 920 }}>
    {product.specs.map((s, i) => (
      <div key={i} style={{
        display: "grid", gridTemplateColumns: "1fr 1.3fr", gap: 16,
        padding: "14px 0", borderBottom: "1px solid var(--line)",
        fontSize: 14,
      }}>
        <span style={{ color: "var(--ink-3)" }}>{s.label}</span>
        <span style={{ color: "var(--ink)" }}>{s.value}</span>
      </div>
    ))}
  </div>
);

const OverviewTab = ({ product, brand }) => (
  <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 56, maxWidth: 920 }}>
    <div>
      <h3 style={{ fontSize: 22, fontWeight: 500, marginBottom: 14 }}>About this product</h3>
      <p style={{ fontSize: 14.5, lineHeight: 1.65, color: "var(--ink-2)" }}>
        {product.tagline} The {product.name} is engineered for high-output commercial kitchens, with construction and components designed to handle the demands of restaurant, hotel and catering operations across the region.
      </p>
      <p style={{ fontSize: 14.5, lineHeight: 1.65, color: "var(--ink-2)", marginTop: 14 }}>
        Sheffield supplies this unit with full factory commissioning, on-site training for kitchen staff, and access to our regional service network. Parts inventory for this product line is maintained in Nairobi, with most components available next-day across East Africa.
      </p>
    </div>
    <div>
      <h3 style={{ fontSize: 22, fontWeight: 500, marginBottom: 14 }}>About {brand.name}</h3>
      <p style={{ fontSize: 14.5, lineHeight: 1.65, color: "var(--ink-2)" }}>
        {brand.blurb} Founded in {brand.founded}, {brand.country}.
      </p>
      <a href="#" onClick={(e) => e.preventDefault()} className="btn btn-outline btn-sm" style={{ marginTop: 18 }}>
        Visit {brand.name} storefront →
      </a>
    </div>
  </div>
);

const InstallTab = ({ product }) => (
  <div style={{ maxWidth: 920 }}>
    <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 24 }}>
      {[
        { t: "1. Site survey", s: "Engineer visits to confirm utilities, ventilation and access. Free within 50 km of Nairobi.", icon: <IconLocation size={20}/> },
        { t: "2. Delivery & commissioning", s: "White-glove unboxing, levelling and first-run calibration with kitchen staff present.", icon: <IconTruck size={20}/> },
        { t: "3. Service & spares", s: "Quarterly preventive visits available. 98% of spares stocked locally for next-day dispatch.", icon: <IconWrench size={20}/> },
      ].map((step, i) => (
        <div key={i} style={{ padding: 24, background: "var(--bg-sunken)", borderRadius: "var(--radius-lg)" }}>
          <div style={{ color: "var(--accent)", marginBottom: 12 }}>{step.icon}</div>
          <div style={{ fontFamily: "var(--font-heading)", fontSize: 18 }}>{step.t}</div>
          <p style={{ fontSize: 13.5, color: "var(--ink-2)", marginTop: 6, lineHeight: 1.55 }}>{step.s}</p>
        </div>
      ))}
    </div>
    <div style={{ marginTop: 28, padding: 24, background: "var(--ink)", color: "#f3eadd", borderRadius: "var(--radius-lg)", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
      <div>
        <div style={{ fontFamily: "var(--font-heading)", fontSize: 22, color: "#f3eadd" }}>Need a service contract?</div>
        <div style={{ fontSize: 13, color: "#c9bea4", marginTop: 4 }}>From KES 24,000/year for one unit. Annual preventive + 48-hr response.</div>
      </div>
      <button className="btn btn-primary">Get a service quote</button>
    </div>
  </div>
);

const DownloadsTab = ({ product }) => (
  <div style={{ maxWidth: 720, display: "flex", flexDirection: "column", gap: 8 }}>
    {[
      { name: "Spec sheet", size: "2 pp · 480 KB", icon: <IconDocument size={18}/> },
      { name: "Installation manual", size: "32 pp · 4.2 MB", icon: <IconWrench size={18}/> },
      { name: "Service & maintenance guide", size: "24 pp · 3.1 MB", icon: <IconShield size={18}/> },
      { name: "Certification — NSF / CE", size: "1 pp · 220 KB", icon: <IconCertified size={18}/> },
      { name: "Cleaning & HACCP procedures", size: "8 pp · 1.4 MB", icon: <IconLeaf size={18}/> },
    ].map((f, i) => (
      <a key={i} href="#" onClick={(e) => e.preventDefault()} style={{
        display: "grid", gridTemplateColumns: "auto 1fr auto auto", gap: 14, alignItems: "center",
        padding: "16px 20px", border: "1px solid var(--line)", borderRadius: "var(--radius)",
        background: "#fff",
      }}>
        <span style={{ color: "var(--accent)" }}>{f.icon}</span>
        <div>
          <div style={{ fontSize: 14, fontWeight: 500 }}>{f.name}</div>
          <div style={{ fontSize: 12, color: "var(--ink-3)" }}>{f.size}</div>
        </div>
        <span style={{ fontSize: 12, color: "var(--ink-3)" }}>PDF</span>
        <IconDownload size={16} sw={1.6} style={{ color: "var(--ink-2)" }}/>
      </a>
    ))}
  </div>
);

const ReviewsTab = ({ product }) => {
  const reviews = [
    { name: "Anita W.", role: "Executive Chef · Tribe Hotel, Nairobi", rating: 5, time: "2 months ago", body: "Replaced our 8-year-old combi. The new unit is paying for itself in gas savings and we've cut overcooked roasts to zero. Sheffield's commissioning team trained the whole brigade in an afternoon." },
    { name: "Daniel M.", role: "Owner · Kilimani Cafe & Bakery", rating: 5, time: "5 months ago", body: "Quote-to-install in 11 days, including site rework. Three months in, no service callouts. The HACCP logging is a game-changer for the audit." },
    { name: "Procurement, JW Marriott", role: "Hotel group", rating: 4, time: "8 months ago", body: "Sound investment, well-built. Wish the touchscreen was less reflective in daylight installs but operationally faultless." },
  ];
  return (
    <div style={{ display: "grid", gridTemplateColumns: "260px 1fr", gap: 48, maxWidth: 1080 }}>
      <div>
        <div style={{ fontFamily: "var(--font-heading)", fontSize: 56, lineHeight: 1, color: "var(--ink)" }}>{product.rating}</div>
        <div style={{ display: "flex", gap: 2, marginTop: 6, color: "var(--warm-1)" }}>
          {[1,2,3,4,5].map(n => <IconStarFill key={n} size={16} stroke="none" style={{ color: n <= Math.round(product.rating) ? "var(--warm-1)" : "var(--line)" }}/>)}
        </div>
        <div style={{ fontSize: 13, color: "var(--ink-3)", marginTop: 8 }}>Based on {product.reviews} verified reviews</div>
        <button className="btn btn-outline btn-sm" style={{ marginTop: 16 }}>Write a review</button>
      </div>
      <div>
        {reviews.map((r, i) => (
          <article key={i} style={{ padding: "20px 0", borderBottom: "1px solid var(--line)" }}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline" }}>
              <div>
                <div style={{ fontWeight: 600, fontSize: 14 }}>{r.name}</div>
                <div style={{ fontSize: 12, color: "var(--ink-3)" }}>{r.role}</div>
              </div>
              <div style={{ display: "flex", gap: 1, color: "var(--warm-1)" }}>
                {[1,2,3,4,5].map(n => <IconStarFill key={n} size={12} stroke="none" style={{ color: n <= r.rating ? "var(--warm-1)" : "var(--line)" }}/>)}
              </div>
            </div>
            <p style={{ fontSize: 14.5, color: "var(--ink-2)", marginTop: 8, lineHeight: 1.6 }}>{r.body}</p>
            <div style={{ fontSize: 11.5, color: "var(--ink-4)", marginTop: 6 }}>{r.time}</div>
          </article>
        ))}
      </div>
    </div>
  );
};

// ───────── RFQ Modal ─────────
const RFQModal = ({ product, onClose }) => {
  const [submitted, setSubmitted] = React.useState(false);
  return (
    <div style={{ position: "fixed", inset: 0, zIndex: 60, display: "flex", alignItems: "center", justifyContent: "center" }}>
      <div onClick={onClose} style={{ position: "absolute", inset: 0, background: "rgba(20,16,8,0.5)" }}/>
      <div style={{
        position: "relative", background: "var(--bg-elev)",
        width: 560, maxWidth: "calc(100vw - 32px)",
        maxHeight: "calc(100vh - 64px)", overflowY: "auto",
        borderRadius: "var(--radius-lg)", padding: 32,
        boxShadow: "0 30px 80px -20px rgba(20,16,8,0.4)",
      }}>
        <button onClick={onClose} aria-label="Close" className="btn btn-ghost btn-sm" style={{ position: "absolute", top: 16, right: 16, width: 36, padding: 0 }}>
          <IconClose size={18}/>
        </button>
        {submitted ? (
          <div style={{ textAlign: "center", padding: "16px 0 8px" }}>
            <div style={{ width: 52, height: 52, margin: "0 auto", borderRadius: 999, background: "var(--accent)", display: "flex", alignItems: "center", justifyContent: "center", color: "#fff" }}>
              <IconCheck size={28} sw={2.4}/>
            </div>
            <h2 style={{ fontFamily: "var(--font-heading)", fontSize: 28, marginTop: 18 }}>Quote request received.</h2>
            <p style={{ color: "var(--ink-2)", marginTop: 8 }}>
              We'll respond by email within one business day. Reference number <strong>RFQ-2026-04183</strong>.
            </p>
            <button className="btn btn-primary" style={{ marginTop: 22 }} onClick={onClose}>Done</button>
          </div>
        ) : (
          <>
            <div className="kicker">Request a formal quote</div>
            <h2 style={{ fontFamily: "var(--font-heading)", fontSize: 26, marginTop: 8, fontWeight: 400 }}>{product.name}</h2>
            <p style={{ fontSize: 13.5, color: "var(--ink-3)", marginTop: 6 }}>
              We'll send a costed quotation on letterhead with lead times, installation and any required ancillaries. Typical response: 24 business hours.
            </p>

            <div style={{ marginTop: 20, display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
              <FieldGroup label="Quantity"><input className="input" defaultValue="1" type="number" min="1"/></FieldGroup>
              <FieldGroup label="Required by"><input className="input" type="date"/></FieldGroup>
              <FieldGroup label="Company"><input className="input" placeholder="e.g. Tribe Hotels Ltd"/></FieldGroup>
              <FieldGroup label="Your name"><input className="input" placeholder="Full name"/></FieldGroup>
              <FieldGroup label="Email"><input className="input" placeholder="you@company.co.ke" type="email"/></FieldGroup>
              <FieldGroup label="Phone"><input className="input" placeholder="+254 ..." type="tel"/></FieldGroup>
              <FieldGroup label="City / install address" full>
                <input className="input" placeholder="e.g. Nairobi · Westlands"/>
              </FieldGroup>
              <FieldGroup label="Notes (specs, related items, tender refs)" full>
                <textarea className="input" rows="4" style={{ height: "auto", paddingTop: 10, paddingBottom: 10 }}
                  placeholder="Any constraints, bundled items, or attach a BOQ on the next screen."/>
              </FieldGroup>
            </div>

            <label style={{ display: "flex", alignItems: "center", gap: 10, marginTop: 14, fontSize: 13, color: "var(--ink-2)" }}>
              <input type="checkbox" defaultChecked style={{ accentColor: "var(--accent)" }}/>
              Include installation & commissioning estimate
            </label>

            <div style={{ marginTop: 22, display: "flex", gap: 10 }}>
              <button className="btn btn-primary btn-lg" style={{ flex: 1 }} onClick={() => setSubmitted(true)}>Send quote request</button>
              <button className="btn btn-outline btn-lg" onClick={onClose}>Cancel</button>
            </div>
          </>
        )}
      </div>
    </div>
  );
};

const FieldGroup = ({ label, children, full }) => (
  <div style={{ gridColumn: full ? "1 / -1" : "auto", display: "flex", flexDirection: "column", gap: 6 }}>
    <label style={{ fontSize: 12, fontWeight: 600, color: "var(--ink-2)" }}>{label}</label>
    {children}
  </div>
);

Object.assign(window, { ProductPage });
