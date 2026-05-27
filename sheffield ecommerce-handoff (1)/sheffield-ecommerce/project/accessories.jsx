// Sheffield — Accessories & Spare parts data + UI block for the product detail page.
// Surfaces as a new section between the main buy panel and the tabs.

// ───────── Data ─────────
window.SHEFFIELD_ACCESSORIES = {
  // Indexed by parent product slug. Each entry is an accessory or spare part
  // tied to that product. SKUs are unique; fits[] lists slugs this part fits.
  "rational-icombi-pro-6-1e": [
    { sku: "RAT-GN-1-1-65",   kind: "accessory",  name: "GN 1/1 65mm Stainless Pan",         price: 4800,  inStock: 124, image: null, kindIcon: "knife" },
    { sku: "RAT-GN-1-1-40",   kind: "accessory",  name: "GN 1/1 40mm Perforated Pan",        price: 5200,  inStock: 96,  image: null, kindIcon: "knife" },
    { sku: "RAT-CARE-TAB-100",kind: "consumable", name: "Active Green Cleaner Tabs — 100 ct",price: 18900, inStock: 60,  image: null, kindIcon: "knife" },
    { sku: "RAT-CARE-RINSE",  kind: "consumable", name: "Care Tabs — Rinse, 150 ct",         price: 14500, inStock: 40,  image: null, kindIcon: "knife" },
    { sku: "RAT-PROBE-MULTI", kind: "accessory",  name: "Multi-point Core Probe — 6 sensors",price: 86000, inStock: 8,   image: null, kindIcon: "chiller" },
    { sku: "RAT-GASKET-DOOR", kind: "spare",      name: "Door Gasket — 6 × 1/1 cabinet",     price: 12400, inStock: 22,  image: null, kindIcon: "knife" },
    { sku: "RAT-FAN-MOTOR",   kind: "spare",      name: "Fan Motor Assembly — Standard",     price: 64500, inStock: 5,   image: null, kindIcon: "blender" },
    { sku: "RAT-RACK-TROLLEY",kind: "accessory",  name: "Mobile Loading Trolley — 6 × 1/1",  price: 142000, inStock: 3,  image: null, kindIcon: "chiller" },
  ],
  "true-t-49-2-door-refrigerator": [
    { sku: "TRU-SHELF-PVC",   kind: "accessory",  name: "PVC-Coated Wire Shelf",             price: 6800,  inStock: 48, image: null, kindIcon: "knife" },
    { sku: "TRU-SHELF-CLIP",  kind: "spare",      name: "Adjustable Shelf Clip Set (12 pcs)",price: 1800,  inStock: 120,image: null, kindIcon: "knife" },
    { sku: "TRU-GASKET-T49",  kind: "spare",      name: "Door Gasket — Replacement",         price: 9400,  inStock: 18, image: null, kindIcon: "knife" },
    { sku: "TRU-CASTER-HD",   kind: "spare",      name: "Heavy-Duty Caster with Brake",      price: 4200,  inStock: 32, image: null, kindIcon: "knife" },
    { sku: "TRU-DEFROST-HTR", kind: "spare",      name: "Defrost Heater — 230V",             price: 14800, inStock: 6,  image: null, kindIcon: "blender" },
  ],
  "hobart-hl600-60qt-mixer": [
    { sku: "HOB-BEATER-60",   kind: "accessory",  name: "Flat Beater — 60 qt",               price: 24500, inStock: 8,  image: null, kindIcon: "mixer" },
    { sku: "HOB-WHIP-60",     kind: "accessory",  name: "Wire Whip — 60 qt",                 price: 22800, inStock: 6,  image: null, kindIcon: "mixer" },
    { sku: "HOB-DOUGH-60",    kind: "accessory",  name: "Dough Hook — 60 qt",                price: 28400, inStock: 4,  image: null, kindIcon: "mixer" },
    { sku: "HOB-BOWL-60",     kind: "accessory",  name: "Stainless Bowl — 60 qt spare",      price: 88000, inStock: 2,  image: null, kindIcon: "chiller" },
    { sku: "HOB-BELT-HL600",  kind: "spare",      name: "Drive Belt — HL600",                price: 14200, inStock: 11, image: null, kindIcon: "knife" },
    { sku: "HOB-AGITATOR",    kind: "spare",      name: "Agitator Shaft Bushing",            price: 7400,  inStock: 14, image: null, kindIcon: "knife" },
  ],
  "rancilio-classe-5-1gr": [
    { sku: "RAN-PORTA-58",    kind: "accessory",  name: "Single-spout Portafilter 58 mm",    price: 7200,  inStock: 22, image: null, kindIcon: "knife" },
    { sku: "RAN-BASKET-2",    kind: "accessory",  name: "Double Filter Basket — 18g",        price: 1800,  inStock: 80, image: null, kindIcon: "knife" },
    { sku: "RAN-TAMP-58",     kind: "accessory",  name: "Calibrated Tamper 58 mm",           price: 2800,  inStock: 36, image: null, kindIcon: "knife" },
    { sku: "RAN-WAND-STEAM",  kind: "spare",      name: "Steam Wand — Stainless",            price: 9400,  inStock: 7,  image: null, kindIcon: "knife" },
    { sku: "RAN-GASKET-GR",   kind: "spare",      name: "Group Gasket — 73 × 57 mm",         price: 1450,  inStock: 64, image: null, kindIcon: "knife" },
    { sku: "RAN-SHOWER-SCR",  kind: "spare",      name: "Shower Screen — Group",             price: 1800,  inStock: 40, image: null, kindIcon: "knife" },
  ],
};

// ───────── Component ─────────
const AccessoriesPanel = ({ product, navigate, addToCart }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const items = window.SHEFFIELD_ACCESSORIES[product.slug] || [];
  const [tab, setTab] = React.useState("all");
  const [selected, setSelected] = React.useState({}); // sku -> qty

  if (items.length === 0) return null;

  const tabs = [
    { id: "all",        label: "All",            filter: () => true },
    { id: "accessory",  label: "Accessories",    filter: (it) => it.kind === "accessory" },
    { id: "consumable", label: "Consumables",    filter: (it) => it.kind === "consumable" },
    { id: "spare",      label: "Spare parts",    filter: (it) => it.kind === "spare" },
  ];
  const counts = tabs.reduce((acc, t) => ({ ...acc, [t.id]: items.filter(t.filter).length }), {});
  const visible = items.filter(tabs.find(t => t.id === tab).filter);

  const setQ = (sku, q) => {
    const next = { ...selected };
    if (q <= 0) delete next[sku];
    else next[sku] = q;
    setSelected(next);
  };

  const selectedSkus = Object.keys(selected);
  const selectedTotal = selectedSkus.reduce((s, sku) => {
    const it = items.find(i => i.sku === sku);
    return s + (it?.price || 0) * selected[sku];
  }, 0);

  const addAll = () => {
    selectedSkus.forEach(sku => addToCart(product.slug, selected[sku]));
    setSelected({});
  };

  return (
    <section style={{ marginTop: 80 }}>
      <div style={{
        display: "flex", justifyContent: "space-between", alignItems: "end",
        marginBottom: 16, paddingBottom: 12, borderBottom: "1px solid var(--line)",
      }}>
        <div>
          <div className="kicker">For this product</div>
          <h2 style={{ fontSize: 28, marginTop: 6, fontWeight: 500 }}>
            Accessories & spare parts
          </h2>
          <p style={{ marginTop: 4, fontSize: 13, color: "var(--ink-3)" }}>
            All parts are genuine, kept in Nairobi for next-day dispatch across East Africa.
          </p>
        </div>
        <div style={{ fontSize: 12.5, color: "var(--ink-3)" }}>
          {items.length} compatible item{items.length === 1 ? "" : "s"}
        </div>
      </div>

      {/* Tabs */}
      <div style={{ display: "flex", gap: 0, borderBottom: "1px solid var(--line)", marginBottom: 16 }}>
        {tabs.map(t => (
          <button key={t.id} onClick={() => setTab(t.id)}
            disabled={counts[t.id] === 0 && t.id !== "all"}
            style={{
              background: "transparent", border: 0,
              padding: "10px 16px", fontSize: 13.5,
              color: tab === t.id ? "var(--ink)" : counts[t.id] === 0 ? "var(--ink-4)" : "var(--ink-3)",
              fontWeight: tab === t.id ? 600 : 500,
              borderBottom: tab === t.id ? "2px solid var(--accent)" : "2px solid transparent",
              marginBottom: -1, cursor: counts[t.id] === 0 ? "default" : "pointer",
            }}>
            {t.label} <span style={{ marginLeft: 4, color: "var(--ink-4)", fontVariantNumeric: "tabular-nums", fontWeight: 400 }}>{counts[t.id]}</span>
          </button>
        ))}
      </div>

      {/* Item table */}
      <div style={{
        background: "#fff", border: "1px solid var(--line)",
        borderRadius: "var(--radius-lg)", overflow: "hidden",
      }}>
        {visible.map((item, i) => (
          <AccessoryRow key={item.sku} item={item}
            qty={selected[item.sku] || 0}
            setQty={(q) => setQ(item.sku, q)}
            addOne={() => addToCart(product.slug, 1)}
            last={i === visible.length - 1}/>
        ))}
      </div>

      {/* Selected sticky-style row */}
      {selectedSkus.length > 0 && (
        <div style={{
          marginTop: 16,
          padding: 16,
          background: "var(--ink)", color: "#f3eadd",
          borderRadius: "var(--radius)",
          display: "grid", gridTemplateColumns: "1fr auto", gap: 16, alignItems: "center",
        }}>
          <div>
            <div style={{ fontSize: 13, color: "#c9bea4" }}>
              {selectedSkus.length} item{selectedSkus.length === 1 ? "" : "s"} selected
            </div>
            <div style={{ fontFamily: "var(--font-heading)", fontSize: 22, color: "#f6ecd9", fontVariantNumeric: "tabular-nums", whiteSpace: "nowrap" }}>
              {KES(selectedTotal)}
            </div>
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <button className="btn btn-outline btn-sm" style={{ background: "transparent", color: "#f3eadd", borderColor: "rgba(255,255,255,0.2)" }}
              onClick={() => setSelected({})}>Clear</button>
            <button className="btn btn-primary" onClick={addAll}>
              <IconCart size={14} sw={1.8}/> Add selected to cart
            </button>
          </div>
        </div>
      )}
    </section>
  );
};

const AccessoryRow = ({ item, qty, setQty, addOne, last }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const kindLabel = { accessory: "Accessory", consumable: "Consumable", spare: "Spare part" }[item.kind] || item.kind;
  const kindColor = { accessory: "var(--brand-blue-600)", consumable: "#2f7a4a", spare: "var(--accent)" }[item.kind];

  return (
    <div style={{
      display: "grid", gridTemplateColumns: "60px 120px 1fr auto auto auto", gap: 16, alignItems: "center",
      padding: 14, borderBottom: last ? "none" : "1px solid var(--line)",
    }}>
      <div style={{ width: 60, height: 60, background: "var(--bg-sunken)", borderRadius: 6, padding: 6 }}>
        {item.image
          ? <img src={item.image} alt="" style={{ width: "100%", height: "100%", objectFit: "contain" }}/>
          : <ProductIllustration kind={item.kindIcon || "knife"}/>}
      </div>
      <span style={{
        display: "inline-flex", alignItems: "center", justifyContent: "center",
        height: 22, padding: "0 8px", fontSize: 10.5, fontWeight: 700,
        letterSpacing: "0.06em", textTransform: "uppercase",
        background: "transparent", color: kindColor,
        border: "1px solid " + kindColor,
        borderRadius: 3, justifySelf: "start",
      }}>
        {kindLabel}
      </span>
      <div style={{ minWidth: 0 }}>
        <div style={{ fontSize: 14, fontWeight: 500, lineHeight: 1.3 }}>{item.name}</div>
        <div style={{ fontSize: 12, color: "var(--ink-3)", marginTop: 2, display: "flex", gap: 10 }}>
          <span style={{ fontFamily: "var(--font-mono)", fontVariantNumeric: "tabular-nums" }}>{item.sku}</span>
          <span style={{ color: item.inStock > 10 ? "#2f7a4a" : item.inStock > 0 ? "var(--warm-1)" : "var(--ink-3)" }}>
            {item.inStock > 10 ? "● In stock" : item.inStock > 0 ? `● ${item.inStock} left` : "● Made to order"}
          </span>
        </div>
      </div>
      <div style={{ fontWeight: 600, fontSize: 14, whiteSpace: "nowrap", fontVariantNumeric: "tabular-nums", textAlign: "right", minWidth: 100 }}>
        {KES(item.price)}
      </div>
      <div style={{ display: "inline-flex", alignItems: "center", border: "1px solid var(--line)", borderRadius: "var(--radius)", height: 36 }}>
        <button onClick={() => setQty(Math.max(0, qty - 1))}
          style={{ background: "transparent", border: 0, width: 30, height: "100%", color: "var(--ink-2)" }}>−</button>
        <span style={{ minWidth: 24, textAlign: "center", fontSize: 12.5, fontVariantNumeric: "tabular-nums", fontWeight: 500 }}>{qty}</span>
        <button onClick={() => setQty(qty + 1)}
          style={{ background: "transparent", border: 0, width: 30, height: "100%", color: "var(--ink-2)" }}>+</button>
      </div>
      <button className="btn btn-outline btn-sm"
        onClick={() => { addOne(); setQty(Math.max(1, qty)); }}
        style={{ minWidth: 100 }}>
        <IconCart size={13} sw={1.8}/> Add
      </button>
    </div>
  );
};

Object.assign(window, { AccessoriesPanel, AccessoryRow });
