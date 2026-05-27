// Sheffield — Catalog page (full listing with filters) + Category landing.

const CatalogPage = ({ navigate, addToCart, compare, setCompare, wishlist, toggleWish, params = {} }) => {
  const D = window.SHEFFIELD_DATA;
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";

  const [filters, setFilters] = React.useState({
    categories: params.slug ? [params.slug] : [],
    brands: [],
    priceMax: 3000000,
    inStockOnly: false,
    bulkOnly: false,
    newOnly: params.filter === "new",
    featuredOnly: params.filter === "featured",
  });
  const [sort, setSort] = React.useState("popularity");
  const [view, setView] = React.useState("grid");
  const [page, setPage] = React.useState(1);

  const filtered = D.products.filter(p => {
    if (filters.categories.length && !filters.categories.includes(p.category)) return false;
    if (filters.brands.length && !filters.brands.includes(p.brand)) return false;
    if (p.price > filters.priceMax) return false;
    if (filters.inStockOnly && p.inStock === 0) return false;
    if (filters.bulkOnly && !p.bulkPrice) return false;
    if (filters.newOnly && !p.isNew) return false;
    if (filters.featuredOnly && !p.featured) return false;
    return true;
  });

  const sorted = [...filtered].sort((a, b) => {
    if (sort === "price-asc") return a.price - b.price;
    if (sort === "price-desc") return b.price - a.price;
    if (sort === "newest") return (b.badge === "New" ? 1 : 0) - (a.badge === "New" ? 1 : 0);
    if (sort === "rating") return b.rating - a.rating;
    return (b.reviews) - (a.reviews); // popularity
  });

  const categoryObj = params.slug ? D.categories.find(c => c.slug === params.slug) : null;

  return (
    <div className="page-fade">
      {/* CATEGORY LANDING HEADER */}
      {categoryObj && <CategoryHero category={categoryObj} navigate={navigate}/>}

      <div className="container" style={{ paddingTop: categoryObj ? 24 : 36, paddingBottom: 80 }}>
        {/* Breadcrumb */}
        <div style={{ fontSize: 12.5, color: "var(--ink-3)", marginBottom: 18, display: "flex", gap: 6, alignItems: "center" }}>
          <a href="#" onClick={(e) => { e.preventDefault(); navigate("home"); }}>Home</a>
          <IconChevronR size={12} sw={1.6}/>
          {categoryObj ? (
            <>
              <a href="#" onClick={(e) => { e.preventDefault(); navigate("catalog"); }}>Catalog</a>
              <IconChevronR size={12} sw={1.6}/>
              <span style={{ color: "var(--ink)" }}>{categoryObj.name}</span>
            </>
          ) : <span style={{ color: "var(--ink)" }}>Catalog</span>}
        </div>

        {!categoryObj && (
          <>
            <h1 style={{ fontSize: direction === "workshop" ? 32 : 56, fontWeight: 400, lineHeight: 1.05 }}>
              {direction === "workshop" ? "Catalog" : <>The full catalog.</>}
            </h1>
            <p style={{ marginTop: 10, color: "var(--ink-3)", maxWidth: 560, fontSize: 14.5 }}>
              {D.products.length} products across {D.categories.length} categories from {D.brands.length} authorised brands.
            </p>
          </>
        )}

        <div style={{ display: "grid", gridTemplateColumns: "260px 1fr", gap: 32, marginTop: 32 }}>
          {/* FILTERS */}
          <aside style={{ position: "sticky", top: 200, alignSelf: "start" }}>
            <FiltersPanel filters={filters} setFilters={setFilters}/>
          </aside>

          {/* RESULTS */}
          <div>
            {/* Toolbar */}
            <div style={{
              display: "flex", justifyContent: "space-between", alignItems: "center",
              padding: "10px 0", borderBottom: "1px solid var(--line)", marginBottom: 20,
            }}>
              <div style={{ fontSize: 13.5, color: "var(--ink-3)" }}>
                Showing <span style={{ color: "var(--ink)", fontWeight: 600 }}>{sorted.length}</span> products
                {filters.categories.length + filters.brands.length > 0 && (
                  <button onClick={() => setFilters({ ...filters, categories: [], brands: [] })}
                    style={{ marginLeft: 10, background: "transparent", border: 0, color: "var(--accent)", fontSize: 13, textDecoration: "underline" }}>
                    Clear filters
                  </button>
                )}
              </div>
              <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
                <label style={{ fontSize: 13, color: "var(--ink-3)" }}>Sort:</label>
                <select value={sort} onChange={(e) => setSort(e.target.value)} className="select" style={{ width: "auto", height: 36, fontSize: 13 }}>
                  <option value="popularity">Most popular</option>
                  <option value="newest">Newest</option>
                  <option value="rating">Highest rated</option>
                  <option value="price-asc">Price — low to high</option>
                  <option value="price-desc">Price — high to low</option>
                </select>
                <div style={{ display: "inline-flex", border: "1px solid var(--line)", borderRadius: "var(--radius)" }}>
                  <button onClick={() => setView("grid")} aria-label="Grid"
                    style={{ width: 36, height: 36, background: view === "grid" ? "var(--bg-sunken)" : "transparent", border: 0, color: "var(--ink-2)" }}>
                    <IconGrid size={16} sw={1.6}/>
                  </button>
                  <button onClick={() => setView("rows")} aria-label="Rows"
                    style={{ width: 36, height: 36, background: view === "rows" ? "var(--bg-sunken)" : "transparent", border: 0, color: "var(--ink-2)" }}>
                    <IconRows size={16} sw={1.6}/>
                  </button>
                </div>
              </div>
            </div>

            {/* Active filter chips */}
            {(filters.categories.length > 0 || filters.brands.length > 0 || filters.inStockOnly || filters.bulkOnly || filters.newOnly || filters.featuredOnly) && (
              <div style={{ display: "flex", flexWrap: "wrap", gap: 8, marginBottom: 20 }}>
                {filters.newOnly && <ActiveChip label="New arrivals" onRemove={() => setFilters({...filters, newOnly: false})}/>}
                {filters.featuredOnly && <ActiveChip label="Featured" onRemove={() => setFilters({...filters, featuredOnly: false})}/>}
                {filters.categories.map(c => {
                  const cat = D.categories.find(x => x.slug === c);
                  return <ActiveChip key={c} label={cat?.name} onRemove={() => setFilters({...filters, categories: filters.categories.filter(x => x !== c)})}/>;
                })}
                {filters.brands.map(b => {
                  const br = D.brands.find(x => x.slug === b);
                  return <ActiveChip key={b} label={br?.name} onRemove={() => setFilters({...filters, brands: filters.brands.filter(x => x !== b)})}/>;
                })}
                {filters.inStockOnly && <ActiveChip label="In stock only" onRemove={() => setFilters({...filters, inStockOnly: false})}/>}
                {filters.bulkOnly && <ActiveChip label="Bulk pricing" onRemove={() => setFilters({...filters, bulkOnly: false})}/>}
              </div>
            )}

            {/* Grid or rows */}
            {sorted.length === 0 ? (
              <div style={{ padding: 60, textAlign: "center", color: "var(--ink-3)", background: "var(--bg-sunken)", borderRadius: "var(--radius-lg)" }}>
                <div style={{ fontFamily: "var(--font-heading)", fontSize: 22, color: "var(--ink)" }}>No products match these filters</div>
                <p style={{ marginTop: 6 }}>Try widening your price range, or removing brand/category constraints.</p>
              </div>
            ) : view === "grid" ? (
              <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: direction === "workshop" ? 14 : 24 }}>
                {sorted.map(p => <ProductCard key={p.slug} product={p} navigate={navigate} compare={compare} setCompare={setCompare} addToCart={addToCart} wishlist={wishlist} toggleWish={toggleWish}/>)}
              </div>
            ) : (
              <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
                {sorted.map(p => <ProductRow key={p.slug} product={p} navigate={navigate} compare={compare} setCompare={setCompare} addToCart={addToCart} wishlist={wishlist} toggleWish={toggleWish}/>)}
              </div>
            )}

            {/* Pagination */}
            {sorted.length > 0 && (
              <div style={{ marginTop: 40, display: "flex", justifyContent: "center", gap: 6 }}>
                <button className="btn btn-outline btn-sm" disabled style={{ opacity: 0.5 }}><IconArrowL size={14} sw={1.8}/></button>
                {[1, 2, 3, 4, 5].map(n => (
                  <button key={n} className="btn btn-sm" style={{
                    background: n === page ? "var(--ink)" : "transparent",
                    color: n === page ? "#fff" : "var(--ink-2)",
                    border: "1px solid var(--line)",
                    width: 36, padding: 0,
                  }} onClick={() => setPage(n)}>{n}</button>
                ))}
                <span style={{ alignSelf: "center", color: "var(--ink-3)", fontSize: 13, padding: "0 8px" }}>… 12</span>
                <button className="btn btn-outline btn-sm"><IconArrow size={14} sw={1.8}/></button>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

// ───────── Category hero ─────────
const CategoryHero = ({ category, navigate }) => {
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";

  if (direction === "workshop") {
    return (
      <div style={{ background: "var(--bg-sunken)", borderBottom: "1px solid var(--line)", padding: "32px 0" }}>
        <div className="container" style={{ display: "grid", gridTemplateColumns: "1fr 200px", alignItems: "center", gap: 24 }}>
          <div style={{ display: "flex", gap: 18, alignItems: "center" }}>
            {category.icon && (
              <img src={category.icon} alt="" style={{ width: 48, height: 48, objectFit: "contain", flexShrink: 0 }}/>
            )}
            <div>
              <div style={{ fontSize: 12, fontWeight: 700, letterSpacing: "0.1em", textTransform: "uppercase", color: "var(--ink-3)" }}>Category</div>
              <h1 style={{ fontSize: 36, marginTop: 6 }}>{category.name}</h1>
              <p style={{ fontSize: 14, color: "var(--ink-3)", marginTop: 8, maxWidth: 580 }}>{category.blurb}</p>
            </div>
          </div>
          {category.image && (
            <div style={{ width: 200, height: 120, borderRadius: "var(--radius)", overflow: "hidden" }}>
              <img src={category.image} alt="" style={{ width: "100%", height: "100%", objectFit: "cover" }}/>
            </div>
          )}
        </div>
      </div>
    );
  }

  return (
    <div style={{
      position: "relative",
      paddingTop: 0, paddingBottom: 0,
      borderBottom: "1px solid var(--line)",
      overflow: "hidden",
    }}>
      {/* Hero image bg */}
      {category.image && (
        <div aria-hidden style={{ position: "absolute", inset: 0 }}>
          <img src={category.image} alt="" style={{ width: "100%", height: "100%", objectFit: "cover" }}/>
          <div style={{
            position: "absolute", inset: 0,
            background: "linear-gradient(to right, rgba(28,26,20,0.92) 0%, rgba(28,26,20,0.7) 40%, rgba(28,26,20,0.18) 80%)",
          }}/>
        </div>
      )}
      <div className="container" style={{ position: "relative", paddingTop: 80, paddingBottom: 72, color: "#f6ecd9", minHeight: 320 }}>
        <div className="kicker" style={{ color: "var(--warm-1)" }}>Category · {category.count} products</div>
        <div style={{ display: "flex", alignItems: "center", gap: 18, marginTop: 14 }}>
          {category.icon && (
            <div style={{
              width: 52, height: 52, borderRadius: 999,
              background: "rgba(255,255,255,0.94)",
              padding: 10, display: "flex", alignItems: "center", justifyContent: "center",
              flexShrink: 0,
            }}>
              <img src={category.icon} alt="" style={{ width: "100%", height: "100%", objectFit: "contain" }}/>
            </div>
          )}
          <h1 style={{ fontSize: 64, fontWeight: 400, lineHeight: 1, color: "#f6ecd9" }}>{category.name}.</h1>
        </div>
        <p style={{ fontSize: 17, color: "rgba(246,236,217,0.85)", marginTop: 18, maxWidth: 560, lineHeight: 1.55 }}>
          {category.blurb} All products carry full Sheffield warranty, parts in stock and factory-trained installation across the region.
        </p>
      </div>
    </div>
  );
};

// ───────── Filters panel ─────────
const FiltersPanel = ({ filters, setFilters }) => {
  const D = window.SHEFFIELD_DATA;
  const KES = D.KES;

  const toggle = (key, v) => {
    setFilters({
      ...filters,
      [key]: filters[key].includes(v) ? filters[key].filter(x => x !== v) : [...filters[key], v],
    });
  };

  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 28, fontSize: 14 }}>
      <FilterGroup title="Category">
        {D.categories.map(c => (
          <FilterCheck key={c.slug} label={c.name} count={c.count}
            checked={filters.categories.includes(c.slug)}
            onChange={() => toggle("categories", c.slug)}/>
        ))}
      </FilterGroup>

      <FilterGroup title="Brand">
        {D.brands.slice(0, 6).map(b => (
          <FilterCheck key={b.slug} label={b.name}
            checked={filters.brands.includes(b.slug)}
            onChange={() => toggle("brands", b.slug)}/>
        ))}
        <a href="#" onClick={(e) => e.preventDefault()} style={{ fontSize: 12.5, color: "var(--accent)", marginTop: 4 }}>Show all brands</a>
      </FilterGroup>

      <FilterGroup title="Price">
        <div style={{ display: "flex", justifyContent: "space-between", fontSize: 12.5, color: "var(--ink-3)" }}>
          <span>KES 0</span>
          <span style={{ color: "var(--ink)", fontWeight: 600 }}>up to {KES(filters.priceMax)}</span>
        </div>
        <input type="range" min={50000} max={3000000} step={50000} value={filters.priceMax}
          onChange={(e) => setFilters({...filters, priceMax: +e.target.value})}
          style={{ width: "100%", accentColor: "var(--accent)" }}/>
        <div style={{ display: "flex", gap: 6, marginTop: 6 }}>
          <input className="input" placeholder="Min" style={{ height: 36, fontSize: 13 }}/>
          <input className="input" placeholder="Max" style={{ height: 36, fontSize: 13 }}/>
        </div>
      </FilterGroup>

      <FilterGroup title="Availability">
        <FilterCheck label="In stock — ships now" checked={filters.inStockOnly} onChange={() => setFilters({...filters, inStockOnly: !filters.inStockOnly})}/>
        <FilterCheck label="Has bulk pricing" checked={filters.bulkOnly} onChange={() => setFilters({...filters, bulkOnly: !filters.bulkOnly})}/>
      </FilterGroup>

      <FilterGroup title="Power">
        <FilterCheck label="Electric"/>
        <FilterCheck label="Gas"/>
        <FilterCheck label="Dual fuel"/>
      </FilterGroup>

      <FilterGroup title="Certifications">
        <FilterCheck label="NSF"/>
        <FilterCheck label="ENERGY STAR"/>
        <FilterCheck label="CE"/>
        <FilterCheck label="KEBS"/>
      </FilterGroup>
    </div>
  );
};

const FilterGroup = ({ title, children }) => (
  <div>
    <div style={{ fontSize: 12, fontWeight: 700, letterSpacing: "0.08em", textTransform: "uppercase", color: "var(--ink-2)", marginBottom: 12, paddingBottom: 6, borderBottom: "1px solid var(--line)" }}>
      {title}
    </div>
    <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>{children}</div>
  </div>
);

const FilterCheck = ({ label, count, checked = false, onChange = () => {} }) => (
  <label style={{ display: "flex", alignItems: "center", gap: 10, cursor: "pointer", fontSize: 13.5, color: "var(--ink-2)" }}>
    <span style={{
      width: 16, height: 16, border: "1.5px solid " + (checked ? "var(--accent)" : "var(--line-strong)"),
      borderRadius: 3, display: "flex", alignItems: "center", justifyContent: "center",
      background: checked ? "var(--accent)" : "transparent",
      transition: "background 120ms, border-color 120ms",
    }}>
      {checked && <IconCheck size={12} sw={2.4} stroke="#fff"/>}
    </span>
    <input type="checkbox" checked={checked} onChange={onChange} style={{ display: "none" }}/>
    <span style={{ flex: 1 }}>{label}</span>
    {count != null && <span style={{ color: "var(--ink-4)", fontSize: 12 }}>{count}</span>}
  </label>
);

const ActiveChip = ({ label, onRemove }) => (
  <span style={{
    display: "inline-flex", alignItems: "center", gap: 6,
    height: 28, padding: "0 10px", borderRadius: 999,
    background: "var(--tag-bg)", color: "var(--ink-2)",
    fontSize: 12.5, fontWeight: 500,
  }}>
    {label}
    <button onClick={onRemove} style={{ background: "transparent", border: 0, padding: 0, color: "var(--ink-3)", display: "flex" }}>
      <IconClose size={12} sw={2}/>
    </button>
  </span>
);

// ───────── Product row (list view) ─────────
const ProductRow = ({ product, navigate, compare, setCompare, addToCart }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const brand = window.SHEFFIELD_DATA.brands.find(b => b.slug === product.brand);
  const isCompared = compare.includes(product.slug);
  return (
    <article onClick={() => navigate("product", { slug: product.slug })} style={{
      display: "grid", gridTemplateColumns: "140px 1fr auto auto", gap: 20,
      padding: 16, background: "#fff", border: "1px solid var(--line)",
      borderRadius: "var(--radius)", cursor: "pointer",
      alignItems: "center",
    }}>
      <div style={{ width: 140, height: 120, background: "var(--bg-sunken)", borderRadius: 6 }}>
        <ProductIllustration kind={product.kind} photo={product.photos?.[0]}/>
      </div>
      <div>
        <div style={{ fontSize: 11.5, fontWeight: 600, letterSpacing: "0.06em", textTransform: "uppercase", color: "var(--warm-2)" }}>{brand?.name}</div>
        <div style={{ fontSize: 16, fontWeight: 500, marginTop: 4 }}>{product.name}</div>
        <div style={{ fontSize: 13, color: "var(--ink-3)", marginTop: 4 }}>{product.tagline}</div>
        <div style={{ display: "flex", gap: 16, marginTop: 8, fontSize: 12, color: "var(--ink-2)" }}>
          {product.power && <span>⚡ {product.power}</span>}
          {product.capacity && <span>{product.capacity}</span>}
          {product.weight && <span>{product.weight}</span>}
          <span style={{ color: "var(--warm-3)" }}>{product.warranty.split(" ").slice(0, 3).join(" ")}</span>
        </div>
      </div>
      <div style={{ textAlign: "right" }}>
        {product.compareAt && <div style={{ fontSize: 12, color: "var(--ink-4)", textDecoration: "line-through" }}>{KES(product.compareAt)}</div>}
        <div style={{ fontFamily: "var(--font-heading)", fontSize: 22 }}>{KES(product.price)}</div>
        <div style={{ fontSize: 11.5, color: product.inStock > 0 ? "#2f7a4a" : "var(--ink-3)" }}>{product.inStock > 0 ? `● ${product.inStock} in stock` : "Made to order"}</div>
      </div>
      <div data-stop style={{ display: "flex", flexDirection: "column", gap: 6 }}>
        <button className="btn btn-primary btn-sm" onClick={(e) => { e.stopPropagation(); addToCart(product.slug); }}>Add to cart</button>
        <button className="btn btn-outline btn-sm" onClick={(e) => { e.stopPropagation(); setCompare(isCompared ? compare.filter(s => s !== product.slug) : [...compare, product.slug].slice(0, 4)); }}>
          {isCompared ? "Comparing" : "Compare"}
        </button>
      </div>
    </article>
  );
};

Object.assign(window, { CatalogPage });
