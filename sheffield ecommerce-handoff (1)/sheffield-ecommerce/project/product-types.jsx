// Sheffield — product purchase panels for each type.
// Replaces the right-side "Info" column on the product detail page.

// ════════════════════════════════════════════════════════════════
// Common helpers
// ════════════════════════════════════════════════════════════════
const TypeBadge = ({ type }) => {
  const labels = {
    simple:       { label: "Simple",        bg: "var(--ink-2)" },
    variant:      { label: "Configurable",  bg: "var(--brand-blue-600)" },
    bundled:      { label: "Bundle",        bg: "var(--accent)" },
    grouped:      { label: "Kit",           bg: "var(--brand-blue-700)" },
    virtual:      { label: "Service",       bg: "#2f7a4a" },
    downloadable: { label: "Digital",       bg: "#7a4ac9" },
  }[type] || { label: type, bg: "var(--ink)" };
  return (
    <span style={{
      display: "inline-flex", alignItems: "center", gap: 6,
      fontSize: 10.5, fontWeight: 700, letterSpacing: "0.08em",
      textTransform: "uppercase", padding: "4px 8px", borderRadius: 4,
      background: labels.bg, color: "#fff",
    }}>● {labels.label}</span>
  );
};

const Money = ({ value, big = false }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  return <span style={{
    fontFamily: big ? "var(--font-heading)" : "var(--font-body)",
    fontSize: big ? 40 : 16, fontWeight: big ? 400 : 600,
    letterSpacing: big ? "-0.02em" : "normal",
    fontVariantNumeric: "tabular-nums", whiteSpace: "nowrap",
  }}>{KES(value)}</span>;
};

// ════════════════════════════════════════════════════════════════
// VARIANT (Configurable)
// ════════════════════════════════════════════════════════════════
const VariantPanel = ({ product, qty, setQty, addToCart, onWish, isWished, onCompare, isCompared, onRFQ }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const [selected, setSelected] = React.useState(() => {
    const init = {};
    product.variantAxes.forEach(a => init[a.id] = a.options[0].id);
    return init;
  });

  const priceDelta = product.variantAxes.reduce((sum, axis) => {
    const opt = axis.options.find(o => o.id === selected[axis.id]);
    return sum + (opt?.diff || 0);
  }, 0);
  const unitPrice = product.price + priceDelta;
  const computedSku = product.sku + "-" + Object.values(selected).join("-").toUpperCase();

  return (
    <div>
      <PanelHeader product={product}/>

      {/* Live SKU + price */}
      <div style={{ marginTop: 24, padding: "20px 0", borderTop: "1px solid var(--line)", borderBottom: "1px solid var(--line)" }}>
        <div style={{ fontSize: 11.5, color: "var(--ink-3)", marginBottom: 8 }}>
          Configured SKU: <span style={{ fontFamily: "var(--font-mono)", color: "var(--ink-2)" }}>{computedSku}</span>
        </div>
        <Money value={unitPrice} big/>
        <span style={{ fontSize: 12.5, color: "var(--ink-3)", marginLeft: 12 }}>excl. 16% VAT</span>
        {priceDelta > 0 && (
          <div style={{ marginTop: 6, fontSize: 12.5, color: "var(--ink-3)" }}>
            Base {KES(product.price)} + options {KES(priceDelta)}
          </div>
        )}
      </div>

      {/* Variant axes */}
      <div style={{ marginTop: 24, display: "flex", flexDirection: "column", gap: 22 }}>
        {product.variantAxes.map(axis => (
          <VariantAxis key={axis.id} axis={axis}
            value={selected[axis.id]}
            onChange={(v) => setSelected({ ...selected, [axis.id]: v })}/>
        ))}
      </div>

      {/* CTA */}
      <QtyAndCTA product={product} qty={qty} setQty={setQty} unitPrice={unitPrice}
        addLabel="Add to cart"
        addClick={() => addToCart(product.slug, qty)}
        onRFQ={onRFQ}/>

      <SecondaryActions product={product} onWish={onWish} isWished={isWished} onCompare={onCompare} isCompared={isCompared}/>
      <TrustGrid product={product}/>
    </div>
  );
};

const VariantAxis = ({ axis, value, onChange }) => {
  const selectedOpt = axis.options.find(o => o.id === value);
  return (
    <div>
      <div style={{ display: "flex", alignItems: "baseline", justifyContent: "space-between", marginBottom: 8 }}>
        <label style={{ fontSize: 12, fontWeight: 700, letterSpacing: "0.06em", textTransform: "uppercase", color: "var(--ink-2)" }}>
          {axis.label}
        </label>
        <span style={{ fontSize: 12.5, color: "var(--ink-3)" }}>
          {selectedOpt?.label}
          {selectedOpt?.sub && <span style={{ marginLeft: 6, color: "var(--ink-4)" }}>· {selectedOpt.sub}</span>}
        </span>
      </div>
      {axis.kind === "swatch" ? (
        <div style={{ display: "grid", gridTemplateColumns: `repeat(${axis.options.length}, 1fr)`, gap: 8 }}>
          {axis.options.map(o => (
            <button key={o.id} onClick={() => onChange(o.id)} style={{
              padding: "12px 14px", textAlign: "left", cursor: "pointer",
              background: value === o.id ? "var(--bg-sunken)" : "#fff",
              border: "1.5px solid " + (value === o.id ? "var(--accent)" : "var(--line)"),
              borderRadius: "var(--radius)",
            }}>
              <div style={{ fontSize: 14, fontWeight: 600 }}>{o.label}</div>
              <div style={{ fontSize: 11.5, color: "var(--ink-3)", marginTop: 2 }}>{o.sub}</div>
              <div style={{ fontSize: 11, color: "var(--ink-2)", marginTop: 6, fontVariantNumeric: "tabular-nums" }}>
                {o.diff === 0 ? "Included" : `+ ${window.SHEFFIELD_DATA.KES(o.diff)}`}
              </div>
            </button>
          ))}
        </div>
      ) : (
        <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
          {axis.options.map(o => (
            <button key={o.id} onClick={() => onChange(o.id)} style={{
              padding: "8px 12px", borderRadius: 999,
              background: value === o.id ? "var(--ink)" : "transparent",
              color: value === o.id ? "#fff" : "var(--ink-2)",
              border: "1px solid " + (value === o.id ? "var(--ink)" : "var(--line-strong)"),
              fontSize: 13, fontWeight: 500, cursor: "pointer",
            }}>
              {o.label}{o.diff > 0 && <span style={{ marginLeft: 6, opacity: 0.7, fontWeight: 400 }}>+ {window.SHEFFIELD_DATA.KES(o.diff)}</span>}
            </button>
          ))}
        </div>
      )}
    </div>
  );
};

// ════════════════════════════════════════════════════════════════
// BUNDLED — pick one from each slot
// ════════════════════════════════════════════════════════════════
const BundlePanel = ({ product, addToCart, navigate, onWish, isWished, onCompare, isCompared, onRFQ }) => {
  const D = window.SHEFFIELD_DATA;
  const KES = D.KES;
  const [picks, setPicks] = React.useState(() => {
    const init = {};
    product.bundleSlots.forEach(s => { if (s.required) init[s.id] = s.options[0]; });
    return init;
  });

  const itemPrices = Object.values(picks).map(slug => D.products.find(p => p.slug === slug)?.price || 0);
  const grossTotal = itemPrices.reduce((s, p) => s + p, 0);
  const discount = Math.round(grossTotal * product.bundleDiscount);
  const total = grossTotal - discount;

  const pickFor = (slotId, slug) => setPicks({ ...picks, [slotId]: slug });

  return (
    <div>
      <PanelHeader product={product}/>

      <div style={{ marginTop: 24, padding: "20px 0", borderTop: "1px solid var(--line)", borderBottom: "1px solid var(--line)" }}>
        <div style={{ display: "flex", alignItems: "baseline", gap: 12 }}>
          <Money value={total} big/>
          {discount > 0 && <span style={{ fontSize: 13, color: "var(--ink-4)", textDecoration: "line-through", whiteSpace: "nowrap" }}>{KES(grossTotal)}</span>}
        </div>
        <div style={{ marginTop: 6, fontSize: 12.5, color: "var(--ink-3)" }}>
          {Object.values(picks).filter(Boolean).length} of {product.bundleSlots.filter(s => s.required).length} required slots filled
          {discount > 0 && <> · Bundle saves <strong style={{ color: "var(--accent)" }}>{KES(discount)}</strong></>}
        </div>
      </div>

      {/* Slots */}
      <div style={{ marginTop: 24, display: "flex", flexDirection: "column", gap: 22 }}>
        {product.bundleSlots.map((slot, i) => (
          <BundleSlot key={slot.id} slot={slot} stepIndex={i + 1}
            value={picks[slot.id]}
            onPick={(slug) => pickFor(slot.id, slug)}
            onClear={() => { const next = { ...picks }; delete next[slot.id]; setPicks(next); }}/>
        ))}
      </div>

      {/* CTA */}
      <div style={{ marginTop: 28, display: "flex", gap: 10 }}>
        <button className="btn btn-primary btn-lg" style={{ flex: 1 }}
          onClick={() => {
            Object.values(picks).filter(Boolean).forEach(slug => addToCart(slug, 1));
          }}>
          <IconCart size={16} sw={1.8}/> Add bundle to cart — {KES(total)}
        </button>
        <button className="btn btn-outline btn-lg" onClick={onRFQ}>
          <IconDocument size={16} sw={1.6}/> Quote
        </button>
      </div>
      <SecondaryActions product={product} onWish={onWish} isWished={isWished} onCompare={onCompare} isCompared={isCompared}/>
      <TrustGrid product={product}/>
    </div>
  );
};

const BundleSlot = ({ slot, stepIndex, value, onPick, onClear }) => {
  const D = window.SHEFFIELD_DATA;
  const KES = D.KES;
  const selectedProduct = value ? D.products.find(p => p.slug === value) : null;

  return (
    <div>
      <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 10 }}>
        <span style={{
          width: 22, height: 22, borderRadius: 999,
          background: selectedProduct ? "var(--accent)" : (slot.required ? "var(--ink-4)" : "var(--bg-sunken)"),
          color: selectedProduct || slot.required ? "#fff" : "var(--ink-3)",
          fontSize: 11, fontWeight: 700, display: "inline-flex", alignItems: "center", justifyContent: "center",
        }}>{selectedProduct ? <IconCheck size={12} sw={2.6}/> : stepIndex}</span>
        <div style={{ fontSize: 14, fontWeight: 600 }}>{slot.label}</div>
        {!slot.required && <span style={{ fontSize: 11, color: "var(--ink-4)" }}>Optional</span>}
      </div>
      <div style={{ fontSize: 12.5, color: "var(--ink-3)", marginBottom: 10, marginLeft: 32 }}>{slot.helper}</div>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(140px, 1fr))", gap: 8, marginLeft: 32 }}>
        {slot.options.map(slug => {
          const p = D.products.find(x => x.slug === slug);
          if (!p) return null;
          const selected = value === slug;
          return (
            <button key={slug} onClick={() => selected ? onClear() : onPick(slug)} style={{
              padding: 10, textAlign: "left", cursor: "pointer",
              background: selected ? "var(--bg-sunken)" : "#fff",
              border: "1.5px solid " + (selected ? "var(--accent)" : "var(--line)"),
              borderRadius: "var(--radius)",
              display: "flex", flexDirection: "column", gap: 6,
            }}>
              <div style={{ width: "100%", aspectRatio: "1 / 1", background: "var(--bg-sunken)", borderRadius: 4 }}>
                <ProductIllustration kind={p.kind} photo={p.photos?.[0]}/>
              </div>
              <div style={{ fontSize: 11.5, fontWeight: 500, lineHeight: 1.25, overflow: "hidden", textOverflow: "ellipsis", display: "-webkit-box", WebkitLineClamp: 2, WebkitBoxOrient: "vertical", minHeight: 28 }}>{p.name}</div>
              <div style={{ fontSize: 11, fontWeight: 600, fontVariantNumeric: "tabular-nums" }}>{KES(p.price)}</div>
            </button>
          );
        })}
      </div>
    </div>
  );
};

// ════════════════════════════════════════════════════════════════
// GROUPED — line-item table with per-item qty
// ════════════════════════════════════════════════════════════════
const GroupedPanel = ({ product, addToCart, onWish, isWished, onCompare, isCompared, onRFQ }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const [qtys, setQtys] = React.useState(() => {
    const init = {};
    product.groupedItems.forEach(i => init[i.sku] = i.defaultQty);
    return init;
  });

  const total = product.groupedItems.reduce((s, i) => s + (qtys[i.sku] || 0) * i.price, 0);
  const selectedCount = Object.values(qtys).filter(q => q > 0).length;

  return (
    <div>
      <PanelHeader product={product}/>

      <div style={{ marginTop: 24, padding: "16px 0", borderTop: "1px solid var(--line)", borderBottom: "1px solid var(--line)" }}>
        <Money value={total} big/>
        <span style={{ fontSize: 12.5, color: "var(--ink-3)", marginLeft: 12 }}>excl. 16% VAT</span>
        <div style={{ marginTop: 6, fontSize: 12.5, color: "var(--ink-3)" }}>
          {selectedCount} of {product.groupedItems.length} items selected · adjust quantities below
        </div>
      </div>

      <div style={{ marginTop: 16, border: "1px solid var(--line)", borderRadius: "var(--radius)", overflow: "hidden" }}>
        {product.groupedItems.map((item, i) => {
          const qty = qtys[item.sku] || 0;
          return (
            <div key={item.sku} style={{
              display: "grid", gridTemplateColumns: "44px 1fr auto auto", gap: 12, alignItems: "center",
              padding: 12, borderBottom: i === product.groupedItems.length - 1 ? "none" : "1px solid var(--line)",
              background: qty === 0 ? "var(--bg-sunken)" : "#fff",
              opacity: qty === 0 ? 0.6 : 1,
            }}>
              <div style={{ width: 44, height: 44, background: "var(--bg-sunken)", borderRadius: 4, padding: 4 }}>
                <ProductIllustration kind={item.kind} photo={item.photo}/>
              </div>
              <div style={{ minWidth: 0 }}>
                <div style={{ fontSize: 13, fontWeight: 500, lineHeight: 1.3 }}>{item.name}</div>
                <div style={{ fontSize: 11, color: "var(--ink-3)", marginTop: 2 }}>{item.sku} · {item.unit}</div>
              </div>
              <div style={{ minWidth: 90, textAlign: "right", fontSize: 13, fontWeight: 600, fontVariantNumeric: "tabular-nums", whiteSpace: "nowrap" }}>{KES(item.price)}</div>
              <div style={{ display: "inline-flex", alignItems: "center", border: "1px solid var(--line)", borderRadius: 4, height: 30 }}>
                <button onClick={() => setQtys({ ...qtys, [item.sku]: Math.max(0, qty - 1) })}
                  style={{ background: "transparent", border: 0, width: 26, height: "100%", color: "var(--ink-2)" }}>−</button>
                <span style={{ minWidth: 22, textAlign: "center", fontSize: 12, fontVariantNumeric: "tabular-nums", fontWeight: 500 }}>{qty}</span>
                <button onClick={() => setQtys({ ...qtys, [item.sku]: qty + 1 })}
                  style={{ background: "transparent", border: 0, width: 26, height: "100%", color: "var(--ink-2)" }}>+</button>
              </div>
            </div>
          );
        })}
      </div>

      <div style={{ marginTop: 24, display: "flex", gap: 10 }}>
        <button className="btn btn-primary btn-lg" style={{ flex: 1 }}
          onClick={() => {
            product.groupedItems.forEach(item => {
              if (qtys[item.sku] > 0) {
                // For grouped items we just add the parent product to cart with full data — in real impl we'd add each sub-item
                addToCart(product.slug, 1);
              }
            });
          }}>
          <IconCart size={16} sw={1.8}/> Add selected items — {KES(total)}
        </button>
        <button className="btn btn-outline btn-lg" onClick={onRFQ}>
          <IconDocument size={16} sw={1.6}/> Quote
        </button>
      </div>
      <SecondaryActions product={product} onWish={onWish} isWished={isWished} onCompare={onCompare} isCompared={isCompared}/>
      <TrustGrid product={product}/>
    </div>
  );
};

// ════════════════════════════════════════════════════════════════
// VIRTUAL — service tiers
// ════════════════════════════════════════════════════════════════
const VirtualPanel = ({ product, qty, setQty, addToCart, onWish, isWished, onCompare, isCompared, onRFQ }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const [tier, setTier] = React.useState(product.serviceTiers.find(t => t.recommended)?.id || product.serviceTiers[0].id);
  const [term, setTerm] = React.useState(1);

  const selected = product.serviceTiers.find(t => t.id === tier);
  const totalAnnual = selected.annualPrice * qty;
  const totalForTerm = totalAnnual * term * (term === 3 ? 0.92 : 1); // 8% off for 3 year

  return (
    <div>
      <PanelHeader product={product}/>

      {/* Tier cards */}
      <div style={{ marginTop: 24 }}>
        <div style={{ fontSize: 12, fontWeight: 700, letterSpacing: "0.06em", textTransform: "uppercase", color: "var(--ink-2)", marginBottom: 10 }}>
          Choose a service tier
        </div>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 8 }}>
          {product.serviceTiers.map(t => {
            const active = tier === t.id;
            return (
              <button key={t.id} onClick={() => setTier(t.id)} style={{
                padding: 16, textAlign: "left", cursor: "pointer",
                background: active ? "var(--bg-sunken)" : "#fff",
                border: "1.5px solid " + (active ? "var(--accent)" : "var(--line)"),
                borderRadius: "var(--radius)",
                position: "relative",
              }}>
                {t.recommended && <span style={{
                  position: "absolute", top: -8, right: 10,
                  background: "var(--accent)", color: "#fff",
                  fontSize: 9.5, fontWeight: 700, padding: "2px 8px", borderRadius: 3, letterSpacing: "0.06em",
                }}>RECOMMENDED</span>}
                <div style={{ fontFamily: "var(--font-heading)", fontSize: 18 }}>{t.name}</div>
                <div style={{ fontSize: 12, color: "var(--ink-3)", marginTop: 4 }}>{t.sla} · {t.visits} visits/yr</div>
                <div style={{ marginTop: 10, fontFamily: "var(--font-heading)", fontSize: 22, fontVariantNumeric: "tabular-nums", whiteSpace: "nowrap" }}>
                  {KES(t.annualPrice)}
                </div>
                <div style={{ fontSize: 11, color: "var(--ink-3)" }}>{t.unit}</div>
              </button>
            );
          })}
        </div>

        {/* What's included */}
        <div style={{ marginTop: 14, padding: 14, background: "var(--bg-sunken)", borderRadius: "var(--radius)" }}>
          <div style={{ fontSize: 12, fontWeight: 600, color: "var(--ink-2)", marginBottom: 8 }}>{selected.name} tier includes:</div>
          <ul style={{ margin: 0, padding: 0, listStyle: "none", display: "grid", gridTemplateColumns: "1fr 1fr", gap: "6px 14px" }}>
            {selected.features.map((f, i) => (
              <li key={i} style={{ fontSize: 12.5, color: "var(--ink-2)", display: "flex", gap: 8, alignItems: "start" }}>
                <IconCheck size={14} sw={2.4} stroke="#2f7a4a" style={{ flexShrink: 0, marginTop: 2 }}/> {f}
              </li>
            ))}
          </ul>
        </div>
      </div>

      {/* Units + term */}
      <div style={{ marginTop: 22, display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
        <div>
          <label style={{ fontSize: 12, fontWeight: 700, letterSpacing: "0.06em", textTransform: "uppercase", color: "var(--ink-2)", display: "block", marginBottom: 6 }}>Units to cover</label>
          <div style={{ display: "inline-flex", alignItems: "center", border: "1px solid var(--line)", borderRadius: "var(--radius)", height: 44, width: "100%" }}>
            <button onClick={() => setQty(Math.max(1, qty - 1))} style={{ background: "transparent", border: 0, width: 40, height: "100%", color: "var(--ink-2)", fontSize: 16 }}>−</button>
            <span style={{ flex: 1, textAlign: "center", fontWeight: 600 }}>{qty} unit{qty === 1 ? "" : "s"}</span>
            <button onClick={() => setQty(qty + 1)} style={{ background: "transparent", border: 0, width: 40, height: "100%", color: "var(--ink-2)", fontSize: 16 }}>+</button>
          </div>
        </div>
        <div>
          <label style={{ fontSize: 12, fontWeight: 700, letterSpacing: "0.06em", textTransform: "uppercase", color: "var(--ink-2)", display: "block", marginBottom: 6 }}>Contract term</label>
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 4 }}>
            {[1, 3].map(t => (
              <button key={t} onClick={() => setTerm(t)} style={{
                height: 44, background: term === t ? "var(--ink)" : "#fff",
                color: term === t ? "#fff" : "var(--ink-2)",
                border: "1px solid " + (term === t ? "var(--ink)" : "var(--line)"),
                borderRadius: "var(--radius)", fontSize: 13, fontWeight: 600, cursor: "pointer",
              }}>
                {t === 3 ? "3 years (-8%)" : "1 year"}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Total */}
      <div style={{ marginTop: 18, padding: 14, background: "var(--brand-blue-700)", color: "#f3eadd", borderRadius: "var(--radius)" }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline" }}>
          <span style={{ fontSize: 13, color: "#c9bea4" }}>Annual contract value</span>
          <span style={{ fontFamily: "var(--font-heading)", fontSize: 24, color: "#f6ecd9", fontVariantNumeric: "tabular-nums" }}>{KES(totalAnnual)}</span>
        </div>
        {term > 1 && (
          <div style={{ display: "flex", justifyContent: "space-between", marginTop: 4, fontSize: 12.5, color: "#9c927c" }}>
            <span>Total for {term}-year term</span>
            <span style={{ color: "#d8c79d", fontVariantNumeric: "tabular-nums" }}>{KES(Math.round(totalForTerm))}</span>
          </div>
        )}
      </div>

      {/* CTA */}
      <div style={{ marginTop: 18, display: "flex", gap: 10 }}>
        <button className="btn btn-primary btn-lg" style={{ flex: 1 }}
          onClick={() => addToCart(product.slug, 1)}>
          <IconShield size={16} sw={1.8}/> Start contract — {KES(Math.round(totalForTerm))}
        </button>
        <button className="btn btn-outline btn-lg" onClick={onRFQ}>Talk to engineer</button>
      </div>

      <div style={{ marginTop: 14, fontSize: 12, color: "var(--ink-3)", display: "flex", gap: 8, alignItems: "start" }}>
        <IconClock size={14} sw={1.6} style={{ color: "var(--secondary)", flexShrink: 0, marginTop: 2 }}/>
        <span>Contract is activated within 48 hours of payment. First preventive visit scheduled within the first month.</span>
      </div>

      <SecondaryActions product={product} onWish={onWish} isWished={isWished} onCompare={onCompare} isCompared={isCompared}/>
    </div>
  );
};

// ════════════════════════════════════════════════════════════════
// DOWNLOADABLE — digital file pack
// ════════════════════════════════════════════════════════════════
const DownloadablePanel = ({ product, addToCart, onWish, isWished, onCompare, isCompared }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const totalPages = product.downloadFiles.reduce((s, f) => s + f.pages, 0);

  return (
    <div>
      <PanelHeader product={product}/>

      {/* Price */}
      <div style={{ marginTop: 24, padding: "20px 0", borderTop: "1px solid var(--line)", borderBottom: "1px solid var(--line)" }}>
        <div style={{ display: "flex", alignItems: "baseline", gap: 14 }}>
          {product.compareAt && <span style={{ fontSize: 16, color: "var(--ink-4)", textDecoration: "line-through", whiteSpace: "nowrap" }}>{KES(product.compareAt)}</span>}
          <Money value={product.price} big/>
        </div>
        <div style={{ marginTop: 8, padding: "8px 12px", background: "#7a4ac9", color: "#fff", borderRadius: 4, display: "inline-flex", alignItems: "center", gap: 8, fontSize: 12, fontWeight: 500 }}>
          <IconDownload size={14} sw={2}/> Instant download after purchase
        </div>
      </div>

      {/* What's in the pack */}
      <div style={{ marginTop: 22 }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline", marginBottom: 12 }}>
          <div style={{ fontSize: 12, fontWeight: 700, letterSpacing: "0.06em", textTransform: "uppercase", color: "var(--ink-2)" }}>
            What's in the pack
          </div>
          <span style={{ fontSize: 12, color: "var(--ink-3)" }}>{product.downloadFiles.length} files · {totalPages} pages</span>
        </div>
        <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
          {product.downloadFiles.map((f, i) => (
            <div key={i} style={{
              display: "grid", gridTemplateColumns: "auto 1fr auto auto", gap: 10, alignItems: "center",
              padding: "10px 12px", border: "1px solid var(--line)", borderRadius: "var(--radius)",
              background: "#fff",
            }}>
              <span style={{
                width: 28, padding: "2px 0", textAlign: "center",
                fontSize: 9.5, fontWeight: 700, color: "#fff", borderRadius: 3,
                background: f.format === "PDF" ? "#c64646" : f.format === "XLSX" ? "#1d8740" : "#1d5d99",
              }}>{f.format}</span>
              <div>
                <div style={{ fontSize: 13, fontWeight: 500 }}>{f.name}</div>
                <div style={{ fontSize: 11, color: "var(--ink-3)" }}>{f.pages} pages · {f.size}</div>
              </div>
              <button style={{ background: "transparent", border: 0, color: "var(--ink-3)", fontSize: 11, cursor: "pointer", textDecoration: "underline" }}>Preview</button>
              <IconDownload size={14} sw={1.6} style={{ color: "var(--ink-3)" }}/>
            </div>
          ))}
        </div>
      </div>

      {/* License */}
      <div style={{ marginTop: 16, padding: 14, background: "var(--bg-sunken)", borderRadius: "var(--radius)", fontSize: 12.5, color: "var(--ink-2)", lineHeight: 1.55, display: "flex", gap: 10, alignItems: "start" }}>
        <IconCertified size={16} style={{ color: "var(--secondary)", flexShrink: 0, marginTop: 2 }}/>
        <span><strong>Licence.</strong> {product.licenseTerms}</span>
      </div>

      {/* CTA */}
      <div style={{ marginTop: 22 }}>
        <button className="btn btn-primary btn-lg" style={{ width: "100%" }}
          onClick={() => addToCart(product.slug, 1)}>
          <IconDownload size={16} sw={1.8}/> Buy and download — {KES(product.price)}
        </button>
      </div>

      <SecondaryActions product={product} onWish={onWish} isWished={isWished} onCompare={onCompare} isCompared={isCompared}/>
    </div>
  );
};

// ════════════════════════════════════════════════════════════════
// Shared bits — header + secondary actions + trust
// ════════════════════════════════════════════════════════════════
const PanelHeader = ({ product }) => {
  const D = window.SHEFFIELD_DATA;
  const brand = D.brands.find(b => b.slug === product.brand);
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";
  return (
    <div>
      <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 12 }}>
        <span style={{ fontSize: 11.5, fontWeight: 600, letterSpacing: "0.1em", textTransform: "uppercase", color: "var(--brand-blue-600)" }}>
          {brand?.name} · {brand?.country}
        </span>
        <TypeBadge type={product.type || "simple"}/>
      </div>
      <h1 style={{
        fontFamily: "var(--font-heading)",
        fontSize: direction === "workshop" ? 32 : 44, fontWeight: 400,
        lineHeight: 1.05,
        letterSpacing: direction === "workshop" ? "-0.02em" : "-0.012em",
      }}>{product.name}</h1>
      <p style={{ fontSize: 16, lineHeight: 1.5, color: "var(--ink-2)", marginTop: 14, maxWidth: 540 }}>{product.tagline}</p>
    </div>
  );
};

const QtyAndCTA = ({ product, qty, setQty, unitPrice, addLabel, addClick, onRFQ }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  return (
    <div style={{ marginTop: 28, display: "flex", gap: 10, alignItems: "stretch" }}>
      <div style={{ display: "inline-flex", alignItems: "center", border: "1px solid var(--line)", borderRadius: "var(--radius)", height: 52 }}>
        <button onClick={() => setQty(Math.max(1, qty - 1))} style={{ background: "transparent", border: 0, width: 44, height: "100%", color: "var(--ink-2)", fontSize: 18 }}>−</button>
        <span style={{ minWidth: 30, textAlign: "center", fontVariantNumeric: "tabular-nums", fontSize: 16, fontWeight: 500 }}>{qty}</span>
        <button onClick={() => setQty(qty + 1)} style={{ background: "transparent", border: 0, width: 44, height: "100%", color: "var(--ink-2)", fontSize: 18 }}>+</button>
      </div>
      <button className="btn btn-primary btn-lg" style={{ flex: 1 }} onClick={addClick}>
        <IconCart size={16} sw={1.8}/> {addLabel} — {KES(unitPrice * qty)}
      </button>
      <button className="btn btn-outline btn-lg" onClick={onRFQ}>
        <IconDocument size={16} sw={1.6}/> Quote
      </button>
    </div>
  );
};

const SecondaryActions = ({ product, onWish, isWished, onCompare, isCompared }) => (
  <div style={{ marginTop: 12, display: "flex", gap: 16, fontSize: 12.5, color: "var(--ink-3)" }}>
    <button onClick={onCompare} style={{ background: "transparent", border: 0, color: isCompared ? "var(--accent)" : "var(--ink-2)", display: "inline-flex", alignItems: "center", gap: 6, padding: 0, cursor: "pointer" }}>
      <IconCompare size={14} sw={1.6}/> {isCompared ? "Comparing" : "Add to comparison"}
    </button>
    <button onClick={onWish} style={{ background: "transparent", border: 0, color: isWished ? "var(--accent)" : "var(--ink-2)", display: "inline-flex", alignItems: "center", gap: 6, padding: 0, cursor: "pointer" }}>
      <IconHeart size={14} sw={1.6} fill={isWished ? "currentColor" : "none"}/> {isWished ? "Saved" : "Save"}
    </button>
    <a href="#" onClick={(e) => e.preventDefault()} style={{ display: "inline-flex", alignItems: "center", gap: 6, color: "var(--ink-2)" }}>
      <IconDownload size={14} sw={1.6}/> Spec sheet
    </a>
  </div>
);

const TrustGrid = ({ product }) => (
  <div style={{
    marginTop: 24, display: "grid", gridTemplateColumns: "repeat(2, 1fr)", gap: 12,
    padding: 18, background: "var(--bg-sunken)", borderRadius: "var(--radius)",
  }}>
    <TrustItem icon={<IconShield size={16}/>} t="Warranty" s={product.warranty}/>
    <TrustItem icon={<IconTruck size={16}/>} t="Delivery" s={product.type === "downloadable" || product.type === "virtual" ? "No physical delivery" : "3–5 days, regional"}/>
    <TrustItem icon={<IconWrench size={16}/>} t="Support" s="Engineers in 4 cities"/>
    <TrustItem icon={<IconCertified size={16}/>} t="Trade pricing" s="Net 30 available"/>
  </div>
);

Object.assign(window, {
  VariantPanel, BundlePanel, GroupedPanel, VirtualPanel, DownloadablePanel,
  PanelHeader, QtyAndCTA, SecondaryActions, TrustGrid, TypeBadge,
});
