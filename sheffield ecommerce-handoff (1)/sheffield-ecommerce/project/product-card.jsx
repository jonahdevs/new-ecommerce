// Sheffield — reusable product card. Adapts to direction (editorial vs workshop).

const ProductCard = ({ product, navigate, compare, setCompare, addToCart, wishlist = [], toggleWish, variant = "auto" }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const brand = window.SHEFFIELD_DATA.brands.find(b => b.slug === product.brand);
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";
  const isCompared = compare.includes(product.slug);
  const isWished = wishlist.includes(product.slug);
  const [hover, setHover] = React.useState(false);

  // shared
  const handleClick = (e) => {
    if (e.target.closest("[data-stop]")) return;
    navigate("product", { slug: product.slug });
  };

  if (direction === "workshop" || variant === "workshop") {
    return (
      <article onClick={handleClick}
        onMouseEnter={() => setHover(true)} onMouseLeave={() => setHover(false)}
        style={{
          background: "#fff",
          border: "1px solid var(--line)",
          borderRadius: "var(--radius)",
          overflow: "hidden", cursor: "pointer",
          display: "flex", flexDirection: "column",
          transition: "border-color 120ms ease, box-shadow 120ms ease",
          ...(hover ? { borderColor: "var(--line-strong)", boxShadow: "var(--shadow-sm)" } : {}),
        }}>
        <div style={{
          position: "relative",
          background: "var(--bg-sunken)",
          height: 200, padding: 12,
        }}>
          {product.badge && (
            <span className="badge" style={{
              position: "absolute", top: 10, left: 10, zIndex: 2,
              background: product.badge.startsWith("Save") ? "var(--accent)" :
                          product.badge === "New" ? "var(--secondary)" :
                          product.badge === "Bestseller" ? "var(--ink)" :
                          "var(--warm-3)",
            }}>{product.badge}</span>
          )}
          <ProductIllustration kind={product.kind} photo={product.photos?.[0]}/>
          <div data-stop style={{
            position: "absolute", top: 10, right: 10, display: "flex", flexDirection: "column", gap: 6,
            opacity: hover ? 1 : 0, transition: "opacity 120ms ease",
          }}>
            <button onClick={(e) => { e.stopPropagation(); setCompare(isCompared ? compare.filter(s => s !== product.slug) : [...compare, product.slug].slice(0, 4)); }}
              className="btn btn-sm"
              style={{ width: 32, height: 32, padding: 0, background: isCompared ? "var(--accent)" : "#fff", color: isCompared ? "#fff" : "var(--ink)", border: "1px solid var(--line)" }}
              title={isCompared ? "Remove from comparison" : "Add to comparison"}>
              <IconCompare size={14} sw={1.6}/>
            </button>
            <button className="btn btn-sm" onClick={(e) => { e.stopPropagation(); toggleWish && toggleWish(product.slug); }}
              style={{ width: 32, height: 32, padding: 0, background: isWished ? "var(--accent)" : "#fff", color: isWished ? "#fff" : "var(--ink)", border: "1px solid var(--line)" }}
              title={isWished ? "Remove from wishlist" : "Save to wishlist"}>
              <IconHeart size={14} sw={1.6}/>
            </button>
          </div>
        </div>

        <div style={{ padding: "14px 16px 16px", borderTop: "1px solid var(--line)", display: "flex", flexDirection: "column", flex: 1 }}>
          <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.08em", textTransform: "uppercase", color: "var(--brand-blue-600)", marginBottom: 4 }}>
            {brand?.name}
          </div>
          <div style={{ fontSize: 14, fontWeight: 500, lineHeight: 1.35, color: "var(--ink)", minHeight: 38 }}>{product.name}</div>

          <div style={{ marginTop: 8, fontSize: 12, color: "var(--ink-3)", display: "flex", gap: 4, alignItems: "center" }}>
            <IconStarFill size={12} style={{ color: "var(--warm-1)" }}/>
            <span style={{ color: "var(--ink-2)", fontVariantNumeric: "tabular-nums" }}>{product.rating}</span>
          </div>

          <div style={{ flex: 1 }}/>
          <div style={{ marginTop: 14, display: "flex", alignItems: "end", justifyContent: "space-between", gap: 8 }}>
            <div>
              {product.compareAt && (
                <div style={{ fontSize: 12, color: "var(--ink-4)", textDecoration: "line-through" }}>{KES(product.compareAt)}</div>
              )}
              <div style={{ fontSize: 16, fontWeight: 700, color: "var(--ink)", fontVariantNumeric: "tabular-nums", whiteSpace: "nowrap" }}>{KES(product.price)}</div>
            </div>
            <button className="btn btn-primary btn-sm" data-stop onClick={(e) => { e.stopPropagation(); addToCart(product.slug); }}>
              <IconCart size={14} sw={1.8}/>
            </button>
          </div>
        </div>
      </article>
    );
  }

  // editorial style
  return (
    <article onClick={handleClick}
      onMouseEnter={() => setHover(true)} onMouseLeave={() => setHover(false)}
      style={{ cursor: "pointer", display: "flex", flexDirection: "column" }}>
      <div style={{
        position: "relative", aspectRatio: "1 / 1",
        background: "var(--surface)", border: "1px solid var(--line)",
        borderRadius: "var(--radius-lg)", overflow: "hidden",
        transition: "border-color 160ms ease, transform 200ms ease",
        ...(hover ? { borderColor: "var(--line-strong)" } : {}),
      }}>
        {product.badge && (
          <span style={{
            position: "absolute", top: 14, left: 14, zIndex: 2,
            fontSize: 11, fontWeight: 600, letterSpacing: "0.08em", textTransform: "uppercase",
            color: product.badge === "Bestseller" ? "var(--accent)" : "var(--ink-2)",
          }}>● {product.badge}</span>
        )}
        <button data-stop className="btn btn-sm"
          onClick={(e) => { e.stopPropagation(); setCompare(isCompared ? compare.filter(s => s !== product.slug) : [...compare, product.slug].slice(0, 4)); }}
          style={{
            position: "absolute", top: 10, right: 54, zIndex: 2,
            width: 36, height: 36, padding: 0, borderRadius: 999,
            background: isCompared ? "var(--accent)" : "rgba(255,255,255,0.92)",
            color: isCompared ? "#fff" : "var(--ink)",
            opacity: hover || isCompared ? 1 : 0, transition: "opacity 160ms ease",
            boxShadow: "0 1px 4px rgba(0,0,0,0.08)",
          }}>
          <IconCompare size={16} sw={1.6}/>
        </button>
        <button data-stop className="btn btn-sm"
          onClick={(e) => { e.stopPropagation(); toggleWish && toggleWish(product.slug); }}
          style={{
            position: "absolute", top: 10, right: 10, zIndex: 2,
            width: 36, height: 36, padding: 0, borderRadius: 999,
            background: isWished ? "var(--accent)" : "rgba(255,255,255,0.92)",
            color: isWished ? "#fff" : "var(--ink)",
            opacity: hover || isWished ? 1 : 0, transition: "opacity 160ms ease",
            boxShadow: "0 1px 4px rgba(0,0,0,0.08)",
          }}>
          <IconHeart size={16} sw={1.6} fill={isWished ? "currentColor" : "none"}/>
        </button>
        <div style={{ position: "absolute", inset: 0, padding: 32, transition: "transform 240ms ease", transform: hover ? "scale(1.04)" : "scale(1)" }}>
          <ProductIllustration kind={product.kind} photo={product.photos?.[0]}/>
        </div>
        <button data-stop className="btn btn-sm"
          onClick={(e) => { e.stopPropagation(); addToCart(product.slug); }}
          style={{
            position: "absolute", bottom: 12, left: 12, right: 12,
            background: "var(--ink)", color: "#fff",
            opacity: hover ? 1 : 0, transform: hover ? "translateY(0)" : "translateY(6px)",
            transition: "opacity 160ms ease, transform 200ms ease",
          }}>
          <IconCart size={14} sw={1.8}/> Add to cart
        </button>
      </div>

      <div style={{ paddingTop: 16 }}>
        <div style={{ fontSize: 11.5, fontWeight: 600, letterSpacing: "0.1em", textTransform: "uppercase", color: "var(--warm-2)" }}>
          {brand?.name}
        </div>
        <div style={{
          fontFamily: "var(--font-heading)",
          fontSize: 19, lineHeight: 1.2, marginTop: 6,
          color: "var(--ink)",
        }}>{product.name}</div>
        <div style={{ marginTop: 8, fontSize: 13, color: "var(--ink-2)", display: "flex", gap: 5, alignItems: "center" }}>
          <IconStarFill size={12} style={{ color: "var(--warm-1)" }}/>
          <span style={{ fontVariantNumeric: "tabular-nums" }}>{product.rating}</span>
        </div>
        <div style={{ marginTop: 12, display: "flex", alignItems: "baseline", gap: 8 }}>
          <span style={{ fontFamily: "var(--font-heading)", fontSize: 22, color: "var(--ink)", fontVariantNumeric: "tabular-nums", whiteSpace: "nowrap" }}>{KES(product.price)}</span>
          {product.compareAt && (
            <span style={{ fontSize: 13, color: "var(--ink-4)", textDecoration: "line-through", whiteSpace: "nowrap" }}>{KES(product.compareAt)}</span>
          )}
        </div>
      </div>
    </article>
  );
};

Object.assign(window, { ProductCard });
