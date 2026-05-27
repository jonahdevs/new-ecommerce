// Sheffield — header search with live autocomplete.

const RECENT_KEY = "sheffield-recent-searches";
const TRENDING = ["combi oven", "blast chiller", "True T-49", "Hobart mixer", "espresso", "Victorinox knife"];

const loadRecent = () => { try { return JSON.parse(localStorage.getItem(RECENT_KEY) || "[]"); } catch { return []; } };
const saveRecent = (arr) => { try { localStorage.setItem(RECENT_KEY, JSON.stringify(arr.slice(0, 6))); } catch {} };

const HeaderSearch = ({ navigate }) => {
  const D = window.SHEFFIELD_DATA;
  const KES = D.KES;
  const [q, setQ] = React.useState("");
  const [open, setOpen] = React.useState(false);
  const [focusedIdx, setFocusedIdx] = React.useState(-1);
  const [recent, setRecent] = React.useState(() => loadRecent());
  const ref = React.useRef(null);
  const inputRef = React.useRef(null);

  // Keyboard shortcut ⌘K / Ctrl+K
  React.useEffect(() => {
    const onKey = (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === "k") {
        e.preventDefault();
        inputRef.current?.focus();
        inputRef.current?.select();
      }
      if (e.key === "Escape") { setOpen(false); inputRef.current?.blur(); }
    };
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, []);

  // Outside click
  React.useEffect(() => {
    if (!open) return;
    const onClick = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
    document.addEventListener("mousedown", onClick);
    return () => document.removeEventListener("mousedown", onClick);
  }, [open]);

  // Build suggestion sets
  const query = q.trim().toLowerCase();
  const matches = (s) => s && s.toLowerCase().includes(query);
  const products = !query ? [] : D.products.filter(p =>
    matches(p.name) || matches(p.sku) || matches(p.kind) ||
    matches(D.brands.find(b => b.slug === p.brand)?.name || "") ||
    (p.tags || []).some(matches)
  ).slice(0, 5);
  const categories = !query ? [] : D.categories.filter(c => matches(c.name) || matches(c.blurb)).slice(0, 3);
  const brands = !query ? [] : D.brands.filter(b => matches(b.name) || matches(b.country)).slice(0, 3);

  // Build flat list for keyboard nav
  const flat = [
    ...(query ? [{ kind: "header-q", label: `Search Sheffield for "${q}"`, q }] : []),
    ...products.map(p => ({ kind: "product", p })),
    ...categories.map(c => ({ kind: "category", c })),
    ...brands.map(b => ({ kind: "brand", b })),
  ];

  const choose = (item) => {
    setOpen(false);
    inputRef.current?.blur();
    if (item.kind === "product") { navigate("product", { slug: item.p.slug }); pushRecent(item.p.name); }
    else if (item.kind === "category") { navigate("category", { slug: item.c.slug }); pushRecent(item.c.name); }
    else if (item.kind === "brand") { navigate("catalog", { brand: item.b.slug }); pushRecent(item.b.name); }
    else { navigate("catalog", { q: item.q }); pushRecent(item.q); }
  };

  const pushRecent = (term) => {
    const next = [term, ...recent.filter(r => r !== term)].slice(0, 6);
    setRecent(next); saveRecent(next);
  };
  const clearRecent = () => { setRecent([]); saveRecent([]); };

  const onKeyDown = (e) => {
    if (!open) return;
    if (e.key === "ArrowDown") { e.preventDefault(); setFocusedIdx(i => Math.min(i + 1, flat.length - 1)); }
    if (e.key === "ArrowUp")   { e.preventDefault(); setFocusedIdx(i => Math.max(i - 1, 0)); }
    if (e.key === "Enter") {
      e.preventDefault();
      if (focusedIdx >= 0 && flat[focusedIdx]) choose(flat[focusedIdx]);
      else if (q.trim()) choose({ kind: "header-q", q });
    }
  };

  React.useEffect(() => { setFocusedIdx(-1); }, [q]);

  return (
    <div ref={ref} style={{ flex: 1, maxWidth: 540, position: "relative", marginLeft: 16 }}>
      <IconSearch size={16} style={{
        position: "absolute", left: 14, top: 14, color: open ? "var(--accent)" : "var(--ink-3)",
        transition: "color 120ms ease", pointerEvents: "none",
      }}/>
      <input
        ref={inputRef}
        className="input"
        value={q}
        onChange={(e) => { setQ(e.target.value); setOpen(true); }}
        onFocus={() => setOpen(true)}
        onKeyDown={onKeyDown}
        placeholder="Search ovens, refrigeration, brands, SKU..."
        style={{
          paddingLeft: 40, paddingRight: q ? 76 : 60, height: 44,
          borderColor: open ? "var(--accent)" : "var(--line)",
          boxShadow: open ? "0 0 0 3px hsl(354 68% 45% / 0.12)" : "none",
        }}
      />
      {q && (
        <button onClick={() => { setQ(""); inputRef.current?.focus(); }}
          aria-label="Clear" style={{
            position: "absolute", right: 38, top: 10,
            background: "transparent", border: 0, color: "var(--ink-3)", padding: 6,
            display: "inline-flex", alignItems: "center", justifyContent: "center",
          }}>
          <IconClose size={14} sw={1.8}/>
        </button>
      )}
      <span style={{ position: "absolute", right: 10, top: 13 }}>
        <span className="kbd">⌘ K</span>
      </span>

      {/* Dropdown panel */}
      <div style={{
        position: "absolute", top: "calc(100% + 8px)", left: 0, right: 0,
        background: "var(--bg-elev)",
        border: "1px solid var(--line)", borderRadius: "var(--radius-lg)",
        boxShadow: "0 24px 60px -20px rgba(20,16,8,0.22), 0 2px 6px -2px rgba(20,16,8,0.08)",
        overflow: "hidden",
        zIndex: 60,
        opacity: open ? 1 : 0,
        transform: open ? "translateY(0)" : "translateY(-6px)",
        pointerEvents: open ? "auto" : "none",
        transition: "opacity 140ms ease, transform 160ms cubic-bezier(.2,.8,.2,1)",
        maxHeight: "calc(100vh - 200px)", overflowY: "auto",
      }}>
        {!query ? (
          // Empty state — recent + trending only
          <div>
            {recent.length > 0 && (
              <SearchSection title="Recent" right={
                <button onClick={clearRecent} style={{ background: "transparent", border: 0, fontSize: 11, color: "var(--ink-3)", textDecoration: "underline", padding: 0 }}>Clear</button>
              }>
                {recent.map((r) => (
                  <SearchItem key={r} icon={<IconClock size={14} sw={1.6}/>}
                    onClick={() => { setQ(r); setOpen(true); inputRef.current?.focus(); }}
                    label={r}/>
                ))}
              </SearchSection>
            )}
            <SearchSection title="Trending">
              <div style={{ display: "flex", flexWrap: "wrap", gap: 6, padding: "4px 14px 14px" }}>
                {TRENDING.map(t => (
                  <button key={t} onClick={() => { setQ(t); inputRef.current?.focus(); }}
                    style={{
                      height: 28, padding: "0 12px", borderRadius: 999,
                      background: "var(--bg-sunken)", color: "var(--ink-2)",
                      border: 0, fontSize: 12.5, fontWeight: 500, cursor: "pointer",
                    }}>{t}</button>
                ))}
              </div>
            </SearchSection>
          </div>
        ) : (
          // Results
          <div>
            <SearchItem icon={<IconSearch size={14} sw={1.6}/>}
              focused={focusedIdx === 0}
              onClick={() => choose({ kind: "header-q", q })}
              label={<span>Search Sheffield for <strong style={{ color: "var(--ink)" }}>"{q}"</strong></span>}
              right={<IconArrow size={12} sw={1.8} style={{ color: "var(--ink-3)" }}/>}/>

            {products.length > 0 && (
              <SearchSection title="Products" count={products.length}>
                {products.map((p, idx) => {
                  const flatIdx = 1 + idx;
                  return (
                    <ProductSuggestion key={p.slug} product={p} q={q}
                      focused={focusedIdx === flatIdx}
                      onClick={() => choose({ kind: "product", p })}/>
                  );
                })}
              </SearchSection>
            )}

            {categories.length > 0 && (
              <SearchSection title="Categories">
                {categories.map((c, idx) => {
                  const flatIdx = 1 + products.length + idx;
                  return (
                    <SearchItem key={c.slug}
                      icon={<IconGrid size={14} sw={1.6}/>}
                      focused={focusedIdx === flatIdx}
                      onClick={() => choose({ kind: "category", c })}
                      label={<Highlighted text={c.name} q={q}/>}
                      right={<span style={{ fontSize: 11, color: "var(--ink-4)" }}>{c.count}</span>}/>
                  );
                })}
              </SearchSection>
            )}

            {brands.length > 0 && (
              <SearchSection title="Brands">
                {brands.map((b, idx) => {
                  const flatIdx = 1 + products.length + categories.length + idx;
                  return (
                    <SearchItem key={b.slug}
                      icon={<span style={{ width: 14, height: 14, borderRadius: 3, background: "var(--bg-sunken)", border: "1px solid var(--line)", display: "inline-flex", alignItems: "center", justifyContent: "center", fontSize: 8, fontWeight: 700, color: "var(--ink-3)" }}>{b.name[0]}</span>}
                      focused={focusedIdx === flatIdx}
                      onClick={() => choose({ kind: "brand", b })}
                      label={<Highlighted text={b.name} q={q}/>}
                      right={<span style={{ fontSize: 11, color: "var(--ink-4)" }}>{b.country}</span>}/>
                  );
                })}
              </SearchSection>
            )}

            {flat.length === 1 && (
              <div style={{ padding: "20px 18px", textAlign: "center", color: "var(--ink-3)", fontSize: 13 }}>
                <div style={{ fontWeight: 500, color: "var(--ink-2)" }}>No matches for "{q}"</div>
                <div style={{ fontSize: 12, marginTop: 4 }}>Try a brand, category or SKU — or <a href="#" onClick={(e) => { e.preventDefault(); choose({ kind: "header-q", q: "request a quote" }); navigate("catalog", { quote: true }); }} style={{ color: "var(--accent)" }}>request a quote</a> and we'll source it.</div>
              </div>
            )}
          </div>
        )}

        {/* Subtle footer with shortcut hint only */}
        <div style={{
          borderTop: "1px solid var(--line)",
          padding: "8px 14px", display: "flex", justifyContent: "flex-end",
          fontSize: 11, color: "var(--ink-4)",
        }}>
          <span style={{ display: "inline-flex", gap: 8, alignItems: "center" }}>
            <span className="kbd">↑</span><span className="kbd">↓</span><span style={{ marginLeft: 2 }}>to navigate</span>
            <span style={{ marginLeft: 10 }}><span className="kbd">esc</span> to close</span>
          </span>
        </div>
      </div>
    </div>
  );
};

// ───────── Sub-components ─────────
const SearchSection = ({ title, count, right, children }) => (
  <div style={{ borderTop: "1px solid var(--line)" }}>
    <div style={{ padding: "10px 18px 6px", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
      <div style={{ fontSize: 10.5, fontWeight: 700, letterSpacing: "0.1em", textTransform: "uppercase", color: "var(--ink-3)" }}>
        {title} {count != null && <span style={{ color: "var(--ink-4)", marginLeft: 4 }}>({count})</span>}
      </div>
      {right}
    </div>
    {children}
  </div>
);

const SearchItem = ({ icon, label, sub, right, focused, onClick }) => (
  <button onClick={onClick} style={{
    width: "100%", textAlign: "left", padding: "10px 18px",
    background: focused ? "var(--bg-sunken)" : "transparent",
    border: 0, cursor: "pointer",
    display: "grid", gridTemplateColumns: "20px 1fr auto", gap: 12, alignItems: "center",
    color: "var(--ink-2)",
  }}
  onMouseEnter={(e) => e.currentTarget.style.background = "var(--bg-sunken)"}
  onMouseLeave={(e) => e.currentTarget.style.background = focused ? "var(--bg-sunken)" : "transparent"}>
    <span style={{ color: "var(--ink-3)", display: "inline-flex" }}>{icon}</span>
    <div style={{ minWidth: 0 }}>
      <div style={{ fontSize: 13.5, color: "var(--ink)", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{label}</div>
      {sub && <div style={{ fontSize: 11.5, color: "var(--ink-3)", marginTop: 2 }}>{sub}</div>}
    </div>
    {right}
  </button>
);

const ProductSuggestion = ({ product, q, focused, onClick }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const brand = window.SHEFFIELD_DATA.brands.find(b => b.slug === product.brand);
  return (
    <button onClick={onClick} style={{
      width: "100%", textAlign: "left", padding: "10px 18px",
      background: focused ? "var(--bg-sunken)" : "transparent",
      border: 0, cursor: "pointer",
      display: "grid", gridTemplateColumns: "44px 1fr auto", gap: 12, alignItems: "center",
    }}
    onMouseEnter={(e) => e.currentTarget.style.background = "var(--bg-sunken)"}
    onMouseLeave={(e) => e.currentTarget.style.background = focused ? "var(--bg-sunken)" : "transparent"}>
      <div style={{ width: 44, height: 44, background: "#fff", border: "1px solid var(--line)", borderRadius: 6, padding: 3 }}>
        <ProductIllustration kind={product.kind} photo={product.photos?.[0]}/>
      </div>
      <div style={{ minWidth: 0 }}>
        <div style={{ fontSize: 10.5, fontWeight: 700, letterSpacing: "0.08em", textTransform: "uppercase", color: "var(--brand-blue-600)" }}>
          {brand?.name}
        </div>
        <div style={{ fontSize: 13.5, color: "var(--ink)", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap", lineHeight: 1.3, marginTop: 1 }}>
          <Highlighted text={product.name} q={q}/>
        </div>
        <div style={{ fontSize: 11, color: "var(--ink-4)", marginTop: 2, fontVariantNumeric: "tabular-nums" }}>{product.sku}</div>
      </div>
      <div style={{ textAlign: "right", alignSelf: "center" }}>
        <div style={{ fontWeight: 600, fontSize: 13, whiteSpace: "nowrap", fontVariantNumeric: "tabular-nums" }}>{KES(product.price)}</div>
      </div>
    </button>
  );
};

const Highlighted = ({ text, q }) => {
  if (!q) return text;
  const idx = text.toLowerCase().indexOf(q.toLowerCase());
  if (idx === -1) return text;
  return (
    <>
      {text.slice(0, idx)}
      <mark style={{ background: "hsl(354 68% 45% / 0.18)", color: "var(--ink)", padding: 0, fontWeight: 600 }}>
        {text.slice(idx, idx + q.length)}
      </mark>
      {text.slice(idx + q.length)}
    </>
  );
};

Object.assign(window, { HeaderSearch });
