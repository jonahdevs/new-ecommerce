// Sheffield — header, promo banner, footer, cart dropdown, user dropdown, compare tray.

const SheffieldLogo = ({ height = 28, variant = "light" }) => {
  // variant "light" = original red+blue logo (for light backgrounds)
  // variant "dark"  = white silhouette (for dark backgrounds — footer, auth aside)
  // variant "mark"  = just the flame mark (favicon-style, no wordmark)
  if (variant === "mark") {
    return <img src="brand/favicon.png" alt="Sheffield" style={{ height, width: "auto", display: "block" }}/>;
  }
  return (
    <img
      src="brand/logo.png"
      alt="Sheffield — Commercial Kitchen Equipment, Since 2003"
      style={{
        height, width: "auto", display: "block",
        filter: variant === "dark" ? "brightness(0) invert(1)" : "none",
      }}
    />
  );
};

// ───────── Promo banner ─────────
const PromoBanner = () => {
  const [dismissed, setDismissed] = React.useState(false);
  if (dismissed) return null;
  return (
    <div style={{
      background: "var(--warm-3)",
      color: "#f2ead9",
      fontSize: 12.5,
      padding: "8px 0",
      letterSpacing: "0.01em",
    }}>
      <div className="container" style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 16 }}>
        <div style={{ display: "flex", gap: 28, alignItems: "center", overflow: "hidden" }}>
          <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
            <IconTruck size={14} sw={1.5}/> Free installation on orders over KES 500,000
          </span>
          <span style={{ opacity: 0.6 }}>·</span>
          <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
            <IconShield size={14} sw={1.5}/> Local parts & service across East Africa
          </span>
          <span style={{ opacity: 0.6 }}>·</span>
          <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
            <IconPhone size={14} sw={1.5}/> +254 20 234 5600
          </span>
        </div>
        <div style={{ display: "flex", gap: 14, alignItems: "center", color: "#d8c79d" }}>
          <a href="#" onClick={(e) => e.preventDefault()} style={{ textDecoration: "none" }}>Trade Login</a>
          <span style={{ opacity: 0.5 }}>·</span>
          <a href="#" onClick={(e) => e.preventDefault()}>KES</a>
          <button onClick={() => setDismissed(true)} aria-label="Dismiss" style={{
            background: "transparent", border: 0, color: "#d8c79d", padding: 4, marginLeft: 4
          }}>
            <IconClose size={14} sw={1.5}/>
          </button>
        </div>
      </div>
    </div>
  );
};

// ───────── Hook: open-state with outside-click + escape ─────────
function useDropdown() {
  const [open, setOpen] = React.useState(false);
  const ref = React.useRef(null);
  React.useEffect(() => {
    if (!open) return;
    const onClick = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
    const onKey = (e) => { if (e.key === "Escape") setOpen(false); };
    document.addEventListener("mousedown", onClick);
    document.addEventListener("keydown", onKey);
    return () => { document.removeEventListener("mousedown", onClick); document.removeEventListener("keydown", onKey); };
  }, [open]);
  return { open, setOpen, ref };
}

// ───────── Header ─────────
const Header = ({ route, navigate, cart, setCart, compare, wishlist, setWishlist, user, setUser }) => {
  const isEditorial = (document.documentElement.getAttribute("data-direction") || "editorial") !== "workshop";
  const cartCount = cart.reduce((n, l) => n + l.qty, 0);
  const wishCount = wishlist.length;

  return (
    <>
      <header style={{
        position: "sticky", top: 0, zIndex: 40,
        background: "var(--bg)",
        borderBottom: "1px solid var(--line)",
      }}>
        <PromoBanner/>
        <div className="container" style={{ display: "flex", alignItems: "center", gap: 24, height: isEditorial ? 84 : 72 }}>
        <button onClick={() => navigate("home")} style={{ background: "transparent", border: 0, padding: 0 }}>
          <SheffieldLogo height={isEditorial ? 36 : 30}/>
        </button>

        {/* Search */}
        <HeaderSearch navigate={navigate}/>

        <nav style={{ display: "flex", gap: 22, alignItems: "center", marginLeft: 12, fontSize: 14, color: "var(--ink-2)" }}>
          <a onClick={(e) => { e.preventDefault(); navigate("catalog"); }} href="#" style={{ display: "flex", alignItems: "center", gap: 6 }}>
            Shop <IconChevron size={14} sw={1.6}/>
          </a>
          <a onClick={(e) => { e.preventDefault(); navigate("catalog", { quote: true }); }} href="#">Request quote</a>
          <a onClick={(e) => e.preventDefault()} href="#">Service</a>
        </nav>

        <div style={{ flex: "0 0 auto", marginLeft: "auto", display: "flex", gap: 4, alignItems: "center" }}>
          {/* Compare */}
          <button className="btn btn-ghost btn-sm" onClick={() => navigate("compare")} style={{ position: "relative", width: 40, padding: 0 }} aria-label="Compare">
            <IconCompare size={18} sw={1.5}/>
            {compare.length > 0 && <CountBadge value={compare.length} color="var(--accent)"/>}
          </button>

          {/* Wishlist */}
          <button className="btn btn-ghost btn-sm" onClick={() => navigate("wishlist")} style={{ position: "relative", width: 40, padding: 0 }} aria-label="Wishlist">
            <IconHeart size={18} sw={1.5}/>
            {wishCount > 0 && <CountBadge value={wishCount} color="var(--accent)"/>}
          </button>

          {/* User */}
          <UserMenu user={user} setUser={setUser} navigate={navigate}/>

          {/* Cart */}
          <CartButton cart={cart} setCart={setCart} navigate={navigate} cartCount={cartCount}/>
        </div>
      </div>
      </header>

      <CategoryNav route={route} navigate={navigate}/>
    </>
  );
};

const CountBadge = ({ value, color }) => (
  <span style={{
    position: "absolute", top: 0, right: 0,
    background: color || "var(--ink)", color: "#fff",
    fontSize: 10, fontWeight: 700, padding: "2px 6px", borderRadius: 999,
    transform: "translate(30%, -30%)",
    fontVariantNumeric: "tabular-nums",
  }}>{value}</span>
);

// ───────── Cart button + dropdown ─────────
const CartButton = ({ cart, setCart, navigate, cartCount }) => {
  const { open, setOpen, ref } = useDropdown();
  return (
    <div ref={ref} style={{ position: "relative" }}>
      <button className="btn btn-ghost btn-sm" onClick={() => setOpen(o => !o)} style={{ position: "relative", width: 40, padding: 0 }} aria-label="Cart">
        <IconCart size={18} sw={1.5}/>
        {cartCount > 0 && <CountBadge value={cartCount}/>}
      </button>
      <DropdownContainer open={open} width={420}>
        <CartDropdown cart={cart} setCart={setCart} navigate={(...args) => { setOpen(false); navigate(...args); }} onClose={() => setOpen(false)}/>
      </DropdownContainer>
    </div>
  );
};

const DropdownContainer = ({ open, width = 380, children }) => (
  <div style={{
    position: "absolute", top: "calc(100% + 10px)", right: 0,
    width,
    background: "var(--bg-elev)",
    border: "1px solid var(--line)",
    borderRadius: "var(--radius-lg)",
    boxShadow: "0 24px 60px -20px rgba(20,16,8,0.22), 0 2px 6px -2px rgba(20,16,8,0.08)",
    zIndex: 50,
    overflow: "hidden",
    transformOrigin: "top right",
    transform: open ? "scale(1)" : "scale(0.96)",
    opacity: open ? 1 : 0,
    pointerEvents: open ? "auto" : "none",
    transition: "opacity 140ms ease, transform 160ms cubic-bezier(.2,.8,.2,1)",
  }}>
    {children}
  </div>
);

const CartDropdown = ({ cart, setCart, navigate, onClose }) => {
  const D = window.SHEFFIELD_DATA;
  const KES = D.KES;
  const lines = cart.map(l => ({ ...l, product: D.products.find(p => p.slug === l.slug) })).filter(l => l.product);
  const subtotal = lines.reduce((s, l) => s + l.product.price * l.qty, 0);

  return (
    <div>
      <div style={{ padding: "14px 18px", borderBottom: "1px solid var(--line)", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
        <div>
          <div style={{ fontFamily: "var(--font-heading)", fontSize: 18 }}>Your cart</div>
          <div style={{ fontSize: 12, color: "var(--ink-3)" }}>{lines.length} item{lines.length === 1 ? "" : "s"}</div>
        </div>
        {lines.length > 0 && (
          <button onClick={() => setCart([])} style={{ background: "transparent", border: 0, fontSize: 12, color: "var(--ink-3)", textDecoration: "underline" }}>Clear</button>
        )}
      </div>

      {lines.length === 0 ? (
        <div style={{ padding: "28px 20px", textAlign: "center" }}>
          <IconCart size={28} sw={1.2} style={{ margin: "0 auto 10px", color: "var(--ink-4)" }}/>
          <div style={{ fontFamily: "var(--font-heading)", fontSize: 18 }}>Cart is empty</div>
          <p style={{ fontSize: 12.5, color: "var(--ink-3)", marginTop: 6, marginBottom: 14 }}>
            Browse equipment or request a quote for tendered projects.
          </p>
          <button className="btn btn-primary btn-sm" onClick={() => navigate("catalog")}>Shop the catalog</button>
        </div>
      ) : (
        <>
          <div style={{ maxHeight: 320, overflowY: "auto" }}>
            {lines.map(line => (
              <div key={line.slug} style={{
                display: "grid", gridTemplateColumns: "56px 1fr auto", gap: 12,
                padding: "12px 18px", borderBottom: "1px solid var(--line)", alignItems: "center",
              }}>
                <div style={{ width: 56, height: 56, background: "var(--bg-sunken)", borderRadius: 6, padding: 4, cursor: "pointer" }}
                  onClick={() => navigate("product", { slug: line.slug })}>
                  <ProductIllustration kind={line.product.kind} photo={line.product.photos?.[0]}/>
                </div>
                <div style={{ minWidth: 0 }}>
                  <a href="#" onClick={(e) => { e.preventDefault(); navigate("product", { slug: line.slug }); }}
                    style={{ display: "block", fontSize: 13, fontWeight: 500, lineHeight: 1.3, color: "var(--ink)" }}>{line.product.name}</a>
                  <div style={{ display: "flex", alignItems: "center", gap: 8, marginTop: 6 }}>
                    <div style={{ display: "inline-flex", alignItems: "center", border: "1px solid var(--line)", borderRadius: 4, height: 26 }}>
                      <button onClick={() => setCart(cart.map(c => c.slug === line.slug ? { ...c, qty: Math.max(1, c.qty - 1) } : c))}
                        style={{ background: "transparent", border: 0, width: 24, height: "100%", color: "var(--ink-2)", fontSize: 14 }}>−</button>
                      <span style={{ minWidth: 22, textAlign: "center", fontVariantNumeric: "tabular-nums", fontSize: 12, fontWeight: 500 }}>{line.qty}</span>
                      <button onClick={() => setCart(cart.map(c => c.slug === line.slug ? { ...c, qty: c.qty + 1 } : c))}
                        style={{ background: "transparent", border: 0, width: 24, height: "100%", color: "var(--ink-2)", fontSize: 14 }}>+</button>
                    </div>
                    <button onClick={() => setCart(cart.filter(c => c.slug !== line.slug))}
                      aria-label="Remove" style={{ background: "transparent", border: 0, color: "var(--ink-3)", padding: 0, display: "inline-flex" }}>
                      <IconClose size={14} sw={1.6}/>
                    </button>
                  </div>
                </div>
                <div style={{ textAlign: "right" }}>
                  <div style={{ fontWeight: 600, fontSize: 13, whiteSpace: "nowrap", fontVariantNumeric: "tabular-nums" }}>{KES(line.product.price * line.qty)}</div>
                </div>
              </div>
            ))}
          </div>

          <div style={{ padding: "14px 18px", background: "var(--bg-sunken)" }}>
            <div style={{ display: "flex", justifyContent: "space-between", fontSize: 13, color: "var(--ink-2)" }}>
              <span>Subtotal</span><span style={{ whiteSpace: "nowrap", fontVariantNumeric: "tabular-nums" }}>{KES(subtotal)}</span>
            </div>
            <div style={{ display: "flex", justifyContent: "space-between", fontSize: 11.5, color: "var(--ink-3)", marginTop: 4 }}>
              <span>VAT & delivery calculated at checkout</span>
            </div>
            <div style={{ display: "flex", gap: 8, marginTop: 12 }}>
              <button className="btn btn-outline btn-sm" style={{ flex: 1 }} onClick={() => navigate("cart")}>View cart</button>
              <button className="btn btn-primary btn-sm" style={{ flex: 1 }} onClick={() => navigate("checkout")}>Checkout</button>
            </div>
            <button className="btn btn-ghost btn-sm" style={{ width: "100%", marginTop: 6, color: "var(--secondary)", height: 30, fontSize: 12.5 }}
              onClick={() => navigate("cart")}>Convert to a formal quote →</button>
          </div>
        </>
      )}
    </div>
  );
};

// ───────── User dropdown ─────────
const UserMenu = ({ user, setUser, navigate }) => {
  const { open, setOpen, ref } = useDropdown();
  const close = () => setOpen(false);

  return (
    <div ref={ref} style={{ position: "relative" }}>
      <button className="btn btn-ghost btn-sm" onClick={() => setOpen(o => !o)} aria-label={user ? "Account" : "Sign in"}>
        {user ? (
          <div style={{ width: 22, height: 22, borderRadius: 999, background: "var(--accent)", color: "#fff", fontSize: 11, fontWeight: 700, display: "flex", alignItems: "center", justifyContent: "center" }}>
            {user.initials}
          </div>
        ) : (
          <IconUser size={18} sw={1.5}/>
        )}
      </button>
      <DropdownContainer open={open} width={300}>
        {user ? (
          <div>
            <div style={{ padding: "16px 18px", borderBottom: "1px solid var(--line)", display: "flex", alignItems: "center", gap: 12 }}>
              <div style={{ width: 40, height: 40, borderRadius: 999, background: "var(--accent)", color: "#fff", fontWeight: 700, display: "flex", alignItems: "center", justifyContent: "center" }}>{user.initials}</div>
              <div style={{ minWidth: 0 }}>
                <div style={{ fontWeight: 600, fontSize: 14, overflow: "hidden", textOverflow: "ellipsis" }}>{user.name}</div>
                <div style={{ fontSize: 12, color: "var(--ink-3)" }}>{user.role}</div>
              </div>
            </div>
            <MenuList items={[
              { icon: <IconUser size={16}/>, label: "Account dashboard", onClick: () => { close(); navigate("account"); } },
              { icon: <IconDocument size={16}/>, label: "Orders & invoices", onClick: () => { close(); navigate("account", { tab: "orders" }); } },
              { icon: <IconHeart size={16}/>, label: "Wishlist", onClick: () => { close(); navigate("wishlist"); } },
              { icon: <IconCompare size={16}/>, label: "My quotes", onClick: () => { close(); navigate("account", { tab: "quotes" }); } },
              { icon: <IconShield size={16}/>, label: "Service contracts", onClick: () => { close(); navigate("account", { tab: "service" }); } },
            ]}/>
            <div style={{ height: 1, background: "var(--line)" }}/>
            <MenuList items={[
              { icon: <IconWrench size={16}/>, label: "Account settings", onClick: () => { close(); navigate("account", { tab: "settings" }); } },
              { icon: <IconChat size={16}/>, label: "Contact specialist", onClick: () => {} },
              { icon: <IconClose size={16}/>, label: "Sign out", onClick: () => { setUser(null); close(); }, danger: true },
            ]}/>
          </div>
        ) : (
          <div style={{ padding: "16px 18px" }}>
            <div style={{ fontFamily: "var(--font-heading)", fontSize: 18 }}>Welcome to Sheffield</div>
            <p style={{ fontSize: 12.5, color: "var(--ink-3)", marginTop: 4 }}>Sign in to track orders, save quotes and manage your service contracts.</p>
            <div style={{ marginTop: 14, display: "flex", flexDirection: "column", gap: 8 }}>
              <button className="btn btn-primary" style={{ width: "100%" }} onClick={() => { close(); navigate("login"); }}>Sign in</button>
              <button className="btn btn-outline" style={{ width: "100%" }} onClick={() => { close(); navigate("register"); }}>Create an account</button>
            </div>
            <div style={{ height: 1, background: "var(--line)", margin: "16px -18px" }}/>
            <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.08em", textTransform: "uppercase", color: "var(--ink-3)", marginBottom: 8 }}>For businesses</div>
            <a href="#" onClick={(e) => { e.preventDefault(); close(); navigate("register", { trade: true }); }}
              style={{ display: "flex", alignItems: "center", gap: 10, fontSize: 13, color: "var(--ink)" }}>
              <IconCertified size={16} style={{ color: "var(--accent)" }}/>
              Apply for trade account & Net 30 →
            </a>
          </div>
        )}
      </DropdownContainer>
    </div>
  );
};

const MenuList = ({ items }) => (
  <div style={{ padding: "6px 0" }}>
    {items.map((it, i) => (
      <button key={i} onClick={it.onClick} style={{
        background: "transparent", border: 0, width: "100%", textAlign: "left",
        padding: "8px 18px", display: "flex", alignItems: "center", gap: 12,
        fontSize: 13.5, color: it.danger ? "var(--accent)" : "var(--ink)",
        cursor: "pointer",
      }}
      onMouseEnter={(e) => e.currentTarget.style.background = "var(--bg-sunken)"}
      onMouseLeave={(e) => e.currentTarget.style.background = "transparent"}>
        <span style={{ color: it.danger ? "var(--accent)" : "var(--ink-3)", display: "inline-flex" }}>{it.icon}</span>
        {it.label}
      </button>
    ))}
  </div>
);

// ───────── Category nav ─────────
const CategoryNav = ({ route, navigate }) => {
  const cats = window.SHEFFIELD_DATA.categories;

  return (
    <nav style={{ background: "var(--bg)", borderBottom: "1px solid var(--line)" }}>
      <div className="container">
        <div style={{
          display: "grid",
          gridTemplateColumns: "repeat(6, 1fr)",
          gridTemplateRows: "repeat(2, auto)",
          gridAutoRows: 0,
          overflow: "hidden",
          gap: 1,
          background: "var(--line)",
          borderLeft: "1px solid var(--line)",
          borderRight: "1px solid var(--line)",
        }}>
          {cats.map((c) => {
            const active = route.name === "category" && route.params.slug === c.slug;
            return (
              <a key={c.slug} href="#"
                onClick={(e) => { e.preventDefault(); navigate("category", { slug: c.slug }); }}
                style={{
                  display: "flex", alignItems: "center", gap: 8,
                  padding: "10px 12px",
                  background: active ? "var(--bg-sunken)" : "var(--bg)",
                  color: active ? "var(--accent)" : "var(--ink-2)",
                  fontSize: 13,
                  fontWeight: active ? 600 : 500,
                  transition: "background 120ms ease, color 120ms ease",
                }}
                onMouseEnter={(e) => { if (!active) { e.currentTarget.style.background = "var(--bg-sunken)"; e.currentTarget.style.color = "var(--ink)"; } }}
                onMouseLeave={(e) => { if (!active) { e.currentTarget.style.background = "var(--bg)"; e.currentTarget.style.color = "var(--ink-2)"; } }}>
                {c.icon ? (
                  <img src={c.icon} alt="" style={{
                    width: 22, height: 22, objectFit: "contain", flexShrink: 0,
                    filter: active ? "none" : "opacity(0.78)",
                  }}/>
                ) : (
                  <span style={{ width: 22, height: 22, display: "inline-flex", alignItems: "center", justifyContent: "center", color: active ? "var(--accent)" : "var(--ink-3)" }}>
                    <IconGrid size={18} sw={1.6}/>
                  </span>
                )}
                <span style={{
                  overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap", flex: 1,
                }}>{c.name}</span>
              </a>
            );
          })}
        </div>
      </div>
    </nav>
  );
};

// ───────── Footer ─────────
const Footer = () => (
  <footer style={{
    background: "var(--warm-3)", color: "#e6ddc8",
    marginTop: 80, paddingTop: 72, paddingBottom: 32,
  }}>
    <div className="container">
      <div style={{ display: "grid", gridTemplateColumns: "1.4fr 1fr 1fr 1fr 1.2fr", gap: 40 }}>
        <div>
          <SheffieldLogo variant="dark" height={32}/>
          <p style={{ marginTop: 16, fontSize: 14, lineHeight: 1.65, color: "#c9bea4", maxWidth: 280 }}>
            Commercial kitchen equipment for restaurants, hotels and catering operations across East Africa. Since 2003.
          </p>
          <div style={{ marginTop: 22, display: "flex", flexDirection: "column", gap: 8, fontSize: 13.5, color: "#c9bea4" }}>
            <span style={{ display: "inline-flex", gap: 8, alignItems: "center" }}><IconMail size={14} sw={1.5}/> sales@sheffield.co.ke</span>
            <span style={{ display: "inline-flex", gap: 8, alignItems: "center" }}><IconChat size={14} sw={1.5}/> WhatsApp +254 711 234 567</span>
          </div>
        </div>
        <FooterLocationCol locations={window.SHEFFIELD_LOCATIONS.slice(0, 2)}/>
        <FooterLocationCol locations={window.SHEFFIELD_LOCATIONS.slice(2, 4)}/>
        <FooterCol title="Business" links={["Request a quote", "Trade accounts", "Installation", "Service contracts", "Spec sheets", "Project consultation"]}/>
        <FooterCol title="Company" links={["About Sheffield", "Showrooms", "Careers", "News & projects", "Contact", "Press"]}/>
      </div>

      <div style={{
        marginTop: 56, paddingTop: 24, borderTop: "1px solid rgba(230,221,200,0.16)",
        display: "flex", justifyContent: "space-between", alignItems: "center", flexWrap: "wrap", gap: 16,
        fontSize: 12.5, color: "#9c927c",
      }}>
        <div style={{ display: "flex", gap: 18, alignItems: "center" }}>
          <span>© 2026 Sheffield East Africa Ltd.</span>
          <a href="#" onClick={(e) => e.preventDefault()}>Terms</a>
          <a href="#" onClick={(e) => e.preventDefault()}>Privacy</a>
          <a href="#" onClick={(e) => e.preventDefault()}>Cookies</a>
        </div>
        <div style={{ display: "flex", gap: 16, alignItems: "center" }}>
          <span>Authorised distributor</span>
          <span style={{ height: 18, width: 1, background: "rgba(230,221,200,0.2)" }}/>
          <span style={{ fontFamily: "var(--font-heading)", color: "#d8c79d", fontSize: 14 }}>NSF · CE · KEBS</span>
        </div>
      </div>
    </div>
  </footer>
);

const FooterCol = ({ title, links }) => (
  <div>
    <div style={{ fontSize: 12, fontWeight: 700, textTransform: "uppercase", letterSpacing: "0.1em", color: "#d8c79d", marginBottom: 16 }}>
      {title}
    </div>
    <ul style={{ listStyle: "none", padding: 0, margin: 0, display: "flex", flexDirection: "column", gap: 10, fontSize: 13.5, color: "#c9bea4" }}>
      {links.map((l, i) => <li key={i}><a href="#" onClick={(e) => e.preventDefault()}>{l}</a></li>)}
    </ul>
  </div>
);

const FooterLocationCol = ({ locations }) => (
  <div>
    <div style={{ fontSize: 12, fontWeight: 700, textTransform: "uppercase", letterSpacing: "0.1em", color: "#d8c79d", marginBottom: 16 }}>
      Showrooms
    </div>
    <div style={{ display: "flex", flexDirection: "column", gap: 18 }}>
      {locations.map(loc => (
        <div key={loc.slug}>
          <div style={{ display: "inline-flex", alignItems: "center", gap: 6, fontSize: 13, fontWeight: 600, color: "#f3eadd" }}>
            {loc.city}
            {loc.isHQ && <span style={{ fontSize: 9, padding: "1px 5px", background: "var(--accent)", color: "#fff", borderRadius: 2, letterSpacing: "0.06em" }}>HQ</span>}
          </div>
          <div style={{ fontSize: 12, color: "#c9bea4", marginTop: 4, lineHeight: 1.5 }}>
            {loc.address}<br/>
            {loc.suburb}, {loc.country}
          </div>
          <a href="#" onClick={(e) => e.preventDefault()} style={{ display: "inline-block", marginTop: 4, fontSize: 12, color: "#d8c79d" }}>
            {loc.phone}
          </a>
        </div>
      ))}
    </div>
  </div>
);

// ───────── Compare tray ─────────
const CompareTray = ({ items, setItems, navigate }) => {
  const products = window.SHEFFIELD_DATA.products;
  const lines = items.map(s => products.find(p => p.slug === s)).filter(Boolean);
  if (lines.length === 0) return null;
  return (
    <div style={{
      position: "fixed", bottom: 24, left: "50%", transform: "translateX(-50%)",
      zIndex: 30,
      background: "var(--ink)", color: "#fff",
      padding: "12px 16px", borderRadius: 999,
      boxShadow: "0 12px 32px -8px rgba(20,16,8,0.4)",
      display: "flex", alignItems: "center", gap: 14,
      fontSize: 13.5,
    }}>
      <IconCompare size={16}/>
      <span style={{ fontWeight: 500 }}>Comparing {lines.length}</span>
      <div style={{ display: "flex" }}>
        {lines.map((p, i) => (
          <div key={p.slug} title={p.name} style={{
            width: 32, height: 32, borderRadius: 6, background: "#fff",
            marginLeft: i === 0 ? 0 : -8, border: "2px solid var(--ink)", overflow: "hidden",
          }}>
            <ProductIllustration kind={p.kind} photo={p.photos?.[0]}/>
          </div>
        ))}
      </div>
      <button className="btn btn-sm" style={{ background: "#fff", color: "var(--ink)" }}
        onClick={() => navigate("compare")}>Compare specs →</button>
      <button onClick={() => setItems([])} aria-label="Clear" style={{
        background: "transparent", border: 0, color: "rgba(255,255,255,0.7)", padding: 4,
      }}><IconClose size={16}/></button>
    </div>
  );
};

Object.assign(window, {
  SheffieldLogo, PromoBanner, Header, CategoryNav, Footer, CompareTray,
  CartDropdown, UserMenu, DropdownContainer, useDropdown,
});
