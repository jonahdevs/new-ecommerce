// Sheffield — main app shell. Routing, state, tweaks.

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "direction": "editorial",
  "headingFont": "Newsreader",
  "categoryNav": "transparent"
}/*EDITMODE-END*/;

const HEADING_FONTS = {
  "Newsreader":         `"Newsreader", "EB Garamond", Georgia, serif`,
  "Instrument Serif":   `"Instrument Serif", "EB Garamond", Georgia, serif`,
  "DM Serif Display":   `"DM Serif Display", "EB Garamond", Georgia, serif`,
  "EB Garamond":        `"EB Garamond", Georgia, serif`,
  "Crimson Pro":        `"Crimson Pro", Georgia, serif`,
};

const PERSISTED_KEYS = {
  cart: "sheffield-cart",
  compare: "sheffield-compare",
  wishlist: "sheffield-wishlist",
  user: "sheffield-user",
};
const loadJSON = (k, fallback) => {
  try { const v = localStorage.getItem(k); return v ? JSON.parse(v) : fallback; }
  catch { return fallback; }
};

function App() {
  const [t, setTweak] = useTweaks(TWEAK_DEFAULTS);
  const [route, setRoute] = React.useState({ name: "home", params: {} });
  const [cart, setCart] = React.useState(() => loadJSON(PERSISTED_KEYS.cart, []));
  const [compare, setCompare] = React.useState(() => loadJSON(PERSISTED_KEYS.compare, []));
  const [wishlist, setWishlist] = React.useState(() => loadJSON(PERSISTED_KEYS.wishlist, []));
  const [user, setUser] = React.useState(() => loadJSON(PERSISTED_KEYS.user, null));
  const [toast, setToast] = React.useState(null);

  // Apply direction & font to root
  React.useEffect(() => {
    document.documentElement.setAttribute("data-direction", t.direction);
    document.documentElement.setAttribute("data-catnav", t.categoryNav);
    document.documentElement.style.setProperty("--font-heading", HEADING_FONTS[t.headingFont] || HEADING_FONTS["Newsreader"]);
    setRoute(r => ({ ...r }));
  }, [t.direction, t.headingFont, t.categoryNav]);

  // Persist
  React.useEffect(() => { localStorage.setItem(PERSISTED_KEYS.cart, JSON.stringify(cart)); }, [cart]);
  React.useEffect(() => { localStorage.setItem(PERSISTED_KEYS.compare, JSON.stringify(compare)); }, [compare]);
  React.useEffect(() => { localStorage.setItem(PERSISTED_KEYS.wishlist, JSON.stringify(wishlist)); }, [wishlist]);
  React.useEffect(() => {
    if (user) localStorage.setItem(PERSISTED_KEYS.user, JSON.stringify(user));
    else      localStorage.removeItem(PERSISTED_KEYS.user);
  }, [user]);

  const navigate = React.useCallback((name, params = {}) => {
    setRoute({ name, params });
    window.scrollTo({ top: 0, behavior: "instant" });
  }, []);

  const addToCart = React.useCallback((slug, qty = 1) => {
    setCart(prev => {
      const existing = prev.find(l => l.slug === slug);
      if (existing) return prev.map(l => l.slug === slug ? { ...l, qty: l.qty + qty } : l);
      return [...prev, { slug, qty }];
    });
    setToast({ kind: "cart", slug, qty, ts: Date.now() });
  }, []);

  const toggleWish = React.useCallback((slug) => {
    setWishlist(prev => {
      if (prev.includes(slug)) return prev.filter(s => s !== slug);
      return [...prev, slug];
    });
  }, []);

  // Toast auto-dismiss
  React.useEffect(() => {
    if (!toast) return;
    const id = setTimeout(() => setToast(null), 3500);
    return () => clearTimeout(id);
  }, [toast]);

  const pageProps = {
    navigate, addToCart, cart, setCart, compare, setCompare,
    wishlist, setWishlist, toggleWish,
    user, setUser,
    params: route.params,
  };

  let page;
  switch (route.name) {
    case "catalog":      page = <CatalogPage {...pageProps}/>; break;
    case "category":     page = <CatalogPage {...pageProps}/>; break;
    case "product":      page = <ProductPage {...pageProps}/>; break;
    case "cart":         page = <CartPage {...pageProps}/>; break;
    case "checkout":     page = <CheckoutPage {...pageProps}/>; break;
    case "confirmation": page = <ConfirmationPage {...pageProps}/>; break;
    case "compare":      page = <ComparePage {...pageProps}/>; break;
    case "wishlist":     page = <WishlistPage {...pageProps}/>; break;
    case "login":        page = <LoginPage {...pageProps}/>; break;
    case "register":     page = <RegisterPage {...pageProps}/>; break;
    case "forgot":       page = <ForgotPage {...pageProps}/>; break;
    case "reset":        page = <ResetPage {...pageProps}/>; break;
    case "verify":       page = <VerifyEmailPage {...pageProps}/>; break;
    case "account":      page = <AccountPage {...pageProps}/>; break;
    case "contact":      page = <ContactPage {...pageProps}/>; break;
    default:             page = <HomePage {...pageProps}/>;
  }

  const screenLabel = {
    home: "Home",
    catalog: "Catalog",
    category: `Category · ${route.params.slug || ""}`,
    product: `Product · ${route.params.slug || ""}`,
    cart: "Cart",
    checkout: "Checkout",
    confirmation: "Confirmation",
    compare: "Compare",
    wishlist: "Wishlist",
    login: "Sign in",
    register: "Register",
    forgot: "Forgot password",
    reset: "Reset password",
    verify: "Verify email",
    account: `Account · ${route.params.tab || "overview"}`,
    contact: "Contact",
  }[route.name];

  const isAuthPage = ["login", "register", "forgot", "reset", "verify"].includes(route.name);

  return (
    <div className="page-root" data-screen-label={screenLabel}>
      {!isAuthPage && (
        <Header
          route={route} navigate={navigate}
          cart={cart} setCart={setCart}
          compare={compare}
          wishlist={wishlist} setWishlist={setWishlist}
          user={user} setUser={setUser}/>
      )}
      <main key={route.name + JSON.stringify(route.params)}>
        {page}
      </main>
      {!isAuthPage && <NewsletterBand/>}
      {!isAuthPage && <Footer navigate={navigate}/>}

      <CompareTray items={compare} setItems={setCompare} navigate={navigate}/>
      <ToastStack toast={toast} navigate={navigate}/>

      <TweaksPanel>
        <TweakSection label="Direction"/>
        <TweakRadio label="Visual" value={t.direction}
          options={["editorial", "workshop"]}
          onChange={(v) => setTweak("direction", v)}/>
        <p style={{ fontSize: 11, color: "rgba(41,38,27,.55)", margin: "0 0 8px", lineHeight: 1.4 }}>
          <strong style={{ color: "var(--accent)" }}>Editorial</strong> — warm, magazine-led with serif display.<br/>
          <strong style={{ color: "var(--accent)" }}>Workshop</strong> — denser, catalog-led with structured cards.
        </p>

        <TweakSection label="Typography"/>
        <TweakSelect label="Heading font" value={t.headingFont}
          options={Object.keys(HEADING_FONTS)}
          onChange={(v) => setTweak("headingFont", v)}/>

        <TweakSection label="Category nav"/>
        <TweakSelect label="Surface" value={t.categoryNav}
          options={["transparent", "sunken", "white", "dark", "blue"]}
          onChange={(v) => setTweak("categoryNav", v)}/>

        <TweakSection label="Demo navigation"/>
        <TweakButton label="Open Login" onClick={() => navigate("login")}/>
        <TweakButton label="Open Register" onClick={() => navigate("register")}/>
        <TweakButton label="Open Wishlist" onClick={() => navigate("wishlist")}/>
        <TweakButton label="Open Account" onClick={() => navigate("account")}/>
      </TweaksPanel>
    </div>
  );
}

// ───────── Toast ─────────
const ToastStack = ({ toast, navigate }) => {
  if (!toast) return null;
  const D = window.SHEFFIELD_DATA;
  const p = D.products.find(x => x.slug === toast.slug);
  if (!p) return null;
  return (
    <div style={{
      position: "fixed", bottom: 88, right: 24,
      zIndex: 60, width: 320,
      background: "var(--ink)", color: "#f3eadd",
      borderRadius: "var(--radius-lg)",
      boxShadow: "0 20px 40px -10px rgba(20,16,8,0.4)",
      padding: 14,
      animation: "toastIn 220ms cubic-bezier(.2,.8,.2,1) both",
    }}>
      <div style={{ display: "flex", gap: 12 }}>
        <div style={{ width: 48, height: 48, background: "#fff", borderRadius: 6, padding: 4, flexShrink: 0 }}>
          <ProductIllustration kind={p.kind}/>
        </div>
        <div style={{ minWidth: 0 }}>
          <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.08em", textTransform: "uppercase", color: "var(--warm-1)" }}>Added to cart</div>
          <div style={{ fontSize: 13, fontWeight: 500, marginTop: 4, lineHeight: 1.35, overflow: "hidden", textOverflow: "ellipsis", display: "-webkit-box", WebkitLineClamp: 2, WebkitBoxOrient: "vertical" }}>{p.name}</div>
          <div style={{ marginTop: 8, display: "flex", gap: 6 }}>
            <button className="btn btn-sm" style={{ background: "#fff", color: "var(--ink)" }} onClick={() => navigate("cart")}>View cart</button>
            <button className="btn btn-sm btn-ghost" style={{ color: "#d8c79d" }} onClick={() => navigate("checkout")}>Checkout</button>
          </div>
        </div>
      </div>
    </div>
  );
};

// inject toast keyframes
if (typeof document !== "undefined" && !document.getElementById("__sheffield_toast_kf")) {
  const s = document.createElement("style"); s.id = "__sheffield_toast_kf";
  s.textContent = "@keyframes toastIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }";
  document.head.appendChild(s);
}

const root = ReactDOM.createRoot(document.getElementById("root"));
root.render(<App/>);
