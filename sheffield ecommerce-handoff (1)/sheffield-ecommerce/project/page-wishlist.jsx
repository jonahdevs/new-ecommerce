// Sheffield — Wishlist page.

const WishlistPage = ({ navigate, wishlist, setWishlist, toggleWish, addToCart, compare, setCompare }) => {
  const D = window.SHEFFIELD_DATA;
  const KES = D.KES;
  const products = wishlist.map(s => D.products.find(p => p.slug === s)).filter(Boolean);
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";

  const total = products.reduce((s, p) => s + p.price, 0);

  return (
    <div className="page-fade">
      <div className="container" style={{ paddingTop: 32, paddingBottom: 80 }}>
        <div style={{ fontSize: 12.5, color: "var(--ink-3)", marginBottom: 18 }}>
          <a href="#" onClick={(e) => { e.preventDefault(); navigate("home"); }}>Home</a> <span style={{ margin: "0 6px" }}>›</span> <span style={{ color: "var(--ink)" }}>Wishlist</span>
        </div>

        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "end", flexWrap: "wrap", gap: 16 }}>
          <div>
            <div className="kicker">Saved for later</div>
            <h1 style={{ fontSize: direction === "workshop" ? 32 : 56, fontWeight: 400, marginTop: 8, lineHeight: 1.05 }}>
              {direction === "workshop" ? "Wishlist" : <>Your wishlist.</>}
            </h1>
            <p style={{ color: "var(--ink-3)", marginTop: 8, fontSize: 14.5 }}>
              {products.length === 0
                ? "Nothing saved yet — tap the heart on any product."
                : `${products.length} item${products.length === 1 ? "" : "s"} · Estimated total ${KES(total)}`}
            </p>
          </div>

          {products.length > 0 && (
            <div style={{ display: "flex", gap: 10 }}>
              <button className="btn btn-outline" onClick={() => setWishlist([])}>Clear wishlist</button>
              <button className="btn btn-primary" onClick={() => { products.forEach(p => addToCart(p.slug)); }}>
                <IconCart size={16} sw={1.8}/> Add all to cart
              </button>
            </div>
          )}
        </div>

        {products.length === 0 ? (
          <EmptyWish navigate={navigate}/>
        ) : (
          <div style={{
            marginTop: 32, display: "grid",
            gridTemplateColumns: "1fr", gap: 12,
          }}>
            {products.map(p => (
              <WishlistRow key={p.slug} product={p} navigate={navigate} addToCart={addToCart}
                onRemove={() => setWishlist(wishlist.filter(s => s !== p.slug))}
                compare={compare} setCompare={setCompare}/>
            ))}
          </div>
        )}

        {products.length > 0 && (
          <div style={{
            marginTop: 28, padding: 20, background: "var(--ink)", color: "#f3eadd",
            borderRadius: "var(--radius-lg)", display: "grid", gridTemplateColumns: "1fr auto", gap: 24, alignItems: "center",
          }}>
            <div>
              <div style={{ fontFamily: "var(--font-heading)", fontSize: 22 }}>Need a formal quote for this list?</div>
              <div style={{ fontSize: 13, color: "#c9bea4", marginTop: 4 }}>
                Convert your wishlist to a costed quotation with delivery, installation and lead times. Response in 24 business hours.
              </div>
            </div>
            <button className="btn btn-primary">Convert to quote →</button>
          </div>
        )}

        {/* Recommendations */}
        <div style={{ marginTop: 80 }}>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline", marginBottom: 20 }}>
            <h2 style={{ fontSize: direction === "workshop" ? 22 : 32, fontWeight: 400 }}>
              {direction === "workshop" ? "You might also want" : "While you're thinking it over."}
            </h2>
            <a href="#" onClick={(e) => { e.preventDefault(); navigate("catalog"); }} style={{ fontSize: 13, color: "var(--ink-2)" }}>Browse all →</a>
          </div>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: direction === "workshop" ? 14 : 24 }}>
            {D.products.filter(p => !wishlist.includes(p.slug)).slice(0, 4).map(p => (
              <ProductCard key={p.slug} product={p} navigate={navigate} compare={compare} setCompare={setCompare} addToCart={addToCart} wishlist={wishlist} toggleWish={toggleWish}/>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

const EmptyWish = ({ navigate }) => (
  <div style={{
    marginTop: 40, padding: 64, textAlign: "center",
    background: "var(--bg-sunken)", borderRadius: "var(--radius-lg)",
  }}>
    <IconHeart size={48} sw={1.2} style={{ margin: "0 auto", color: "var(--ink-4)" }}/>
    <h2 style={{ fontFamily: "var(--font-heading)", fontSize: 26, marginTop: 20, fontWeight: 400 }}>No saved items yet.</h2>
    <p style={{ color: "var(--ink-3)", marginTop: 8, maxWidth: 460, marginLeft: "auto", marginRight: "auto" }}>
      Tap the heart on any product to save it here. Wishlists keep across devices once you're signed in, and can be converted into a formal quote with one click.
    </p>
    <div style={{ marginTop: 24, display: "flex", gap: 10, justifyContent: "center" }}>
      <button className="btn btn-primary" onClick={() => navigate("catalog")}>Browse the catalog</button>
      <button className="btn btn-outline" onClick={() => navigate("login")}>Sign in to sync</button>
    </div>
  </div>
);

const WishlistRow = ({ product, navigate, addToCart, onRemove, compare, setCompare }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const brand = window.SHEFFIELD_DATA.brands.find(b => b.slug === product.brand);
  const isCompared = compare.includes(product.slug);
  return (
    <article style={{
      display: "grid", gridTemplateColumns: "120px 1fr auto auto", gap: 20,
      padding: 16, background: "#fff", border: "1px solid var(--line)",
      borderRadius: "var(--radius-lg)", alignItems: "center",
    }}>
      <div onClick={() => navigate("product", { slug: product.slug })}
        style={{ width: 120, height: 120, background: "var(--bg-sunken)", borderRadius: 8, padding: 8, cursor: "pointer" }}>
        <ProductIllustration kind={product.kind} photo={product.photos?.[0]}/>
      </div>
      <div>
        <div style={{ fontSize: 11.5, fontWeight: 600, letterSpacing: "0.06em", textTransform: "uppercase", color: "var(--warm-2)" }}>{brand?.name}</div>
        <a href="#" onClick={(e) => { e.preventDefault(); navigate("product", { slug: product.slug }); }}
          style={{ fontSize: 16, fontWeight: 500, marginTop: 4, display: "block", color: "var(--ink)" }}>{product.name}</a>
        <div style={{ fontSize: 13, color: "var(--ink-3)", marginTop: 4 }}>{product.tagline}</div>
        <div style={{ marginTop: 8, display: "flex", gap: 10, fontSize: 12, color: "var(--ink-2)" }}>
          <span>SKU: {product.sku}</span>
          <span>·</span>
          <span style={{ color: product.inStock > 0 ? "var(--warm-3)" : "var(--ink-3)" }}>
            {product.inStock > 0 ? "● In stock" : "● Made to order"}
          </span>
        </div>
      </div>
      <div style={{ textAlign: "right", minWidth: 130 }}>
        {product.compareAt && <div style={{ fontSize: 12, color: "var(--ink-4)", textDecoration: "line-through", whiteSpace: "nowrap" }}>{KES(product.compareAt)}</div>}
        <div style={{ fontFamily: "var(--font-heading)", fontSize: 22, whiteSpace: "nowrap", fontVariantNumeric: "tabular-nums" }}>{KES(product.price)}</div>
      </div>
      <div style={{ display: "flex", flexDirection: "column", gap: 6, minWidth: 140 }}>
        <button className="btn btn-primary btn-sm" onClick={() => addToCart(product.slug)}>
          <IconCart size={14} sw={1.8}/> Add to cart
        </button>
        <button className="btn btn-outline btn-sm" onClick={() => setCompare(isCompared ? compare.filter(s => s !== product.slug) : [...compare, product.slug].slice(0, 4))}>
          {isCompared ? "Comparing" : "Compare"}
        </button>
        <button onClick={onRemove} style={{ background: "transparent", border: 0, fontSize: 12, color: "var(--ink-3)", textDecoration: "underline" }}>Remove</button>
      </div>
    </article>
  );
};

Object.assign(window, { WishlistPage });
