// Sheffield — Cart, Checkout, Order Confirmation, Compare pages.

const CartPage = ({ navigate, cart, setCart }) => {
  const D = window.SHEFFIELD_DATA;
  const KES = D.KES;
  const lines = cart.map(l => ({ ...l, product: D.products.find(p => p.slug === l.slug) })).filter(l => l.product);
  const subtotal = lines.reduce((s, l) => s + l.product.price * l.qty, 0);
  const vat = Math.round(subtotal * 0.16);
  const delivery = subtotal > 500000 ? 0 : 12000;
  const total = subtotal + vat + delivery;
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";

  return (
    <div className="page-fade">
      <div className="container" style={{ paddingTop: 32, paddingBottom: 80 }}>
        <div style={{ fontSize: 12.5, color: "var(--ink-3)", marginBottom: 20 }}>
          <a href="#" onClick={(e) => { e.preventDefault(); navigate("home"); }}>Home</a> <span style={{ margin: "0 6px" }}>›</span> <span style={{ color: "var(--ink)" }}>Cart</span>
        </div>

        <h1 style={{ fontSize: direction === "workshop" ? 32 : 52, fontWeight: 400 }}>
          {direction === "workshop" ? "Cart" : <>Your cart.</>}
        </h1>
        <p style={{ marginTop: 8, color: "var(--ink-3)", fontSize: 14.5 }}>{lines.length} item{lines.length === 1 ? "" : "s"} · ready to check out, or convert to a formal quote.</p>

        {lines.length === 0 ? (
          <EmptyCart navigate={navigate}/>
        ) : (
          <div style={{ display: "grid", gridTemplateColumns: "1.5fr 1fr", gap: 32, marginTop: 32 }}>
            {/* Lines */}
            <div>
              <div style={{
                background: "#fff", border: "1px solid var(--line)", borderRadius: "var(--radius-lg)",
                overflow: "hidden",
              }}>
                {lines.map((line, i) => (
                  <CartLine key={line.slug} line={line} cart={cart} setCart={setCart} navigate={navigate}
                    last={i === lines.length - 1}/>
                ))}
              </div>

              <div style={{ marginTop: 24, padding: 20, background: "var(--bg-sunken)", borderRadius: "var(--radius)", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                <div style={{ display: "flex", gap: 10, alignItems: "center" }}>
                  <IconDocument size={20} style={{ color: "var(--secondary)" }}/>
                  <div>
                    <div style={{ fontWeight: 600, fontSize: 14 }}>Need a formal quotation instead?</div>
                    <div style={{ fontSize: 12.5, color: "var(--ink-3)" }}>Convert this cart to a signed quote with delivery and installation. PDF in 24 hours.</div>
                  </div>
                </div>
                <button className="btn btn-outline">Convert to quote →</button>
              </div>

              <div style={{ marginTop: 20, display: "flex", gap: 12 }}>
                <a href="#" onClick={(e) => { e.preventDefault(); navigate("catalog"); }} style={{ fontSize: 14, color: "var(--ink-2)", display: "inline-flex", alignItems: "center", gap: 6 }}>
                  <IconArrowL size={14} sw={1.6}/> Continue shopping
                </a>
              </div>
            </div>

            {/* Summary */}
            <aside style={{ position: "sticky", top: 200, alignSelf: "start" }}>
              <div style={{
                background: "#fff", border: "1px solid var(--line)", borderRadius: "var(--radius-lg)",
                padding: 24,
              }}>
                <div style={{ fontFamily: "var(--font-heading)", fontSize: 22, fontWeight: 400, marginBottom: 18 }}>Order summary</div>

                <Row label="Subtotal" value={KES(subtotal)}/>
                <Row label={`VAT (16%)`} value={KES(vat)}/>
                <Row label="Delivery — Nairobi metro" value={delivery === 0 ? "Free" : KES(delivery)}/>
                <Row label="Installation" value="Calculated by team" sub/>

                <div style={{ height: 1, background: "var(--line)", margin: "16px 0" }}/>
                <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline" }}>
                  <span style={{ fontSize: 14, fontWeight: 500 }}>Total due today</span>
                  <span style={{ fontFamily: "var(--font-heading)", fontSize: 28, fontVariantNumeric: "tabular-nums" }}>{KES(total)}</span>
                </div>

                <div style={{ marginTop: 18, padding: "10px 12px", background: "var(--bg-sunken)", borderRadius: "var(--radius)", fontSize: 12.5, color: "var(--ink-2)" }}>
                  <strong>Trade pricing.</strong> Verified business accounts get Net 30 terms. <a href="#" onClick={(e) => e.preventDefault()} style={{ color: "var(--accent)" }}>Apply →</a>
                </div>

                <button className="btn btn-primary btn-lg" style={{ width: "100%", marginTop: 18 }} onClick={() => navigate("checkout")}>
                  Checkout <IconArrow size={16} sw={2}/>
                </button>
                <div style={{ marginTop: 12, display: "flex", justifyContent: "center", gap: 10, alignItems: "center", fontSize: 11.5, color: "var(--ink-3)" }}>
                  <IconShield size={12} sw={1.6}/> Secure payment · Card, M-Pesa, bank transfer
                </div>
              </div>

              <div style={{ marginTop: 16, padding: 18, background: "var(--ink)", color: "#f3eadd", borderRadius: "var(--radius)" }}>
                <div style={{ display: "flex", gap: 10, alignItems: "start" }}>
                  <IconChat size={20} style={{ color: "var(--warm-1)", flexShrink: 0 }}/>
                  <div>
                    <div style={{ fontWeight: 600, fontSize: 14 }}>Talk to a specialist</div>
                    <div style={{ fontSize: 12.5, color: "#c9bea4", marginTop: 4 }}>Sizing, electrical or installation questions before you check out?</div>
                    <a href="#" onClick={(e) => e.preventDefault()} style={{ fontSize: 13, color: "var(--warm-1)", marginTop: 8, display: "inline-block" }}>+254 20 234 5600 →</a>
                  </div>
                </div>
              </div>
            </aside>
          </div>
        )}
      </div>
    </div>
  );
};

const EmptyCart = ({ navigate }) => (
  <div style={{
    marginTop: 40, padding: 64, textAlign: "center",
    background: "var(--bg-sunken)", borderRadius: "var(--radius-lg)",
  }}>
    <IconCart size={48} sw={1.2} style={{ margin: "0 auto", color: "var(--ink-4)" }}/>
    <h2 style={{ fontFamily: "var(--font-heading)", fontSize: 26, marginTop: 20, fontWeight: 400 }}>Your cart is empty.</h2>
    <p style={{ color: "var(--ink-3)", marginTop: 8, maxWidth: 420, marginLeft: "auto", marginRight: "auto" }}>
      Browse the catalog and add equipment, or request a formal quote for tendered projects.
    </p>
    <div style={{ marginTop: 24, display: "flex", gap: 10, justifyContent: "center" }}>
      <button className="btn btn-primary" onClick={() => navigate("catalog")}>Shop the catalog</button>
      <button className="btn btn-outline" onClick={() => navigate("catalog", { quote: true })}>Request a quote</button>
    </div>
  </div>
);

const CartLine = ({ line, cart, setCart, navigate, last }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const brand = window.SHEFFIELD_DATA.brands.find(b => b.slug === line.product.brand);
  return (
    <div style={{
      display: "grid", gridTemplateColumns: "120px 1fr auto auto", gap: 20, padding: 20,
      borderBottom: last ? "none" : "1px solid var(--line)", alignItems: "center",
    }}>
      <div onClick={() => navigate("product", { slug: line.slug })} style={{ width: 120, height: 120, background: "var(--bg-sunken)", borderRadius: 8, cursor: "pointer", padding: 8 }}>
        <ProductIllustration kind={line.product.kind} photo={line.product.photos?.[0]}/>
      </div>
      <div>
        <div style={{ fontSize: 11.5, fontWeight: 600, letterSpacing: "0.06em", textTransform: "uppercase", color: "var(--warm-2)" }}>{brand?.name}</div>
        <a href="#" onClick={(e) => { e.preventDefault(); navigate("product", { slug: line.slug }); }}
          style={{ fontSize: 15, fontWeight: 500, lineHeight: 1.3, marginTop: 4, display: "block", color: "var(--ink)" }}>{line.product.name}</a>
        <div style={{ fontSize: 12, color: "var(--ink-3)", marginTop: 4 }}>SKU: {line.product.sku} · {line.product.warranty.split(" ").slice(0, 3).join(" ")}</div>
        <div style={{ marginTop: 8, fontSize: 12, color: line.product.inStock > 0 ? "var(--warm-3)" : "var(--ink-3)", display: "inline-flex", alignItems: "center", gap: 6 }}>
          <IconCheck size={12} sw={2}/> {line.product.leadTime}
        </div>
      </div>
      <div style={{ display: "flex", alignItems: "center", border: "1px solid var(--line)", borderRadius: "var(--radius)", height: 40 }}>
        <button onClick={() => setCart(cart.map(c => c.slug === line.slug ? { ...c, qty: Math.max(1, c.qty - 1) } : c))}
          style={{ background: "transparent", border: 0, width: 36, height: "100%", color: "var(--ink-2)" }}><IconMinus size={14} sw={2}/></button>
        <span style={{ minWidth: 28, textAlign: "center", fontVariantNumeric: "tabular-nums", fontWeight: 500 }}>{line.qty}</span>
        <button onClick={() => setCart(cart.map(c => c.slug === line.slug ? { ...c, qty: c.qty + 1 } : c))}
          style={{ background: "transparent", border: 0, width: 36, height: "100%", color: "var(--ink-2)" }}><IconPlus size={14} sw={2}/></button>
      </div>
      <div style={{ textAlign: "right", minWidth: 130 }}>
        <div style={{ fontFamily: "var(--font-heading)", fontSize: 20 }}>{KES(line.product.price * line.qty)}</div>
        {line.qty > 1 && <div style={{ fontSize: 11.5, color: "var(--ink-3)", marginTop: 2 }}>{KES(line.product.price)} each</div>}
        <button onClick={() => setCart(cart.filter(c => c.slug !== line.slug))}
          style={{ marginTop: 8, background: "transparent", border: 0, fontSize: 12, color: "var(--ink-3)", textDecoration: "underline" }}>Remove</button>
      </div>
    </div>
  );
};

const Row = ({ label, value, sub }) => (
  <div style={{ display: "flex", justifyContent: "space-between", padding: "6px 0", fontSize: 14, color: sub ? "var(--ink-3)" : "var(--ink-2)" }}>
    <span>{label}</span>
    <span style={{ fontVariantNumeric: "tabular-nums" }}>{value}</span>
  </div>
);

// ════════════════════════════════════════════════════════════════
// CHECKOUT
// ════════════════════════════════════════════════════════════════
const CheckoutPage = ({ navigate, cart, setCart }) => {
  const D = window.SHEFFIELD_DATA;
  const KES = D.KES;
  const [step, setStep] = React.useState(1);
  const [payment, setPayment] = React.useState("mpesa");
  const [account, setAccount] = React.useState("business");
  const [installation, setInstallation] = React.useState(true);

  const lines = cart.map(l => ({ ...l, product: D.products.find(p => p.slug === l.slug) })).filter(l => l.product);
  const subtotal = lines.reduce((s, l) => s + l.product.price * l.qty, 0);
  const installCost = installation ? Math.round(subtotal * 0.04) : 0;
  const vat = Math.round((subtotal + installCost) * 0.16);
  const delivery = subtotal > 500000 ? 0 : 12000;
  const total = subtotal + installCost + vat + delivery;

  return (
    <div className="page-fade">
      <div className="container" style={{ paddingTop: 24, paddingBottom: 80 }}>
        <div style={{ fontSize: 12.5, color: "var(--ink-3)", marginBottom: 20 }}>
          <a href="#" onClick={(e) => { e.preventDefault(); navigate("cart"); }}>Cart</a> <span style={{ margin: "0 6px" }}>›</span> <span style={{ color: "var(--ink)" }}>Checkout</span>
        </div>

        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 32 }}>
          <h1 style={{ fontSize: 36, fontWeight: 400 }}>Checkout</h1>
          <CheckoutSteps step={step}/>
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "1.5fr 1fr", gap: 32 }}>
          <div style={{ display: "flex", flexDirection: "column", gap: 18 }}>
            {/* Account */}
            <Card title="Account type" step={1} done={step > 1}>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
                <RadioCard
                  selected={account === "business"} onSelect={() => setAccount("business")}
                  title="Business account" subtitle="VAT receipt, Net 30 available, multi-user. Recommended for restaurants & hotels."/>
                <RadioCard
                  selected={account === "personal"} onSelect={() => setAccount("personal")}
                  title="Personal purchase" subtitle="Simpler checkout, single receipt. Good for owner-operated kitchens."/>
              </div>
              {account === "business" && (
                <div style={{ marginTop: 16, display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
                  <FieldGroup label="Company name"><input className="input" placeholder="Tribe Hotels Ltd"/></FieldGroup>
                  <FieldGroup label="KRA PIN"><input className="input" placeholder="P051..." style={{ fontFamily: "var(--font-mono)" }}/></FieldGroup>
                  <FieldGroup label="Contact name"><input className="input" placeholder="Anita Wanjiru"/></FieldGroup>
                  <FieldGroup label="Email"><input className="input" type="email" placeholder="anita@tribe.co.ke"/></FieldGroup>
                  <FieldGroup label="Phone"><input className="input" type="tel" placeholder="+254 ..."/></FieldGroup>
                  <FieldGroup label="Role"><input className="input" placeholder="Executive Chef"/></FieldGroup>
                </div>
              )}
            </Card>

            {/* Delivery */}
            <Card title="Delivery & installation" step={2} done={step > 2}>
              <FieldGroup label="Delivery address"><input className="input" placeholder="Street, building, floor"/></FieldGroup>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 10, marginTop: 10 }}>
                <FieldGroup label="City"><input className="input" defaultValue="Nairobi"/></FieldGroup>
                <FieldGroup label="Postal code"><input className="input" placeholder="00100"/></FieldGroup>
                <FieldGroup label="Country">
                  <select className="select" defaultValue="KE">
                    <option value="KE">Kenya</option><option value="UG">Uganda</option>
                    <option value="TZ">Tanzania</option><option value="RW">Rwanda</option>
                  </select>
                </FieldGroup>
              </div>
              <FieldGroup label="Special instructions" style={{ marginTop: 10 }}>
                <textarea className="input" rows="2" style={{ height: "auto", paddingTop: 10 }}
                  placeholder="Loading bay access, lift dimensions, preferred delivery window..."/>
              </FieldGroup>

              <div style={{ marginTop: 18, padding: 16, background: "var(--bg-sunken)", borderRadius: "var(--radius)", display: "flex", gap: 14 }}>
                <input type="checkbox" checked={installation} onChange={(e) => setInstallation(e.target.checked)} style={{ marginTop: 4, accentColor: "var(--accent)" }}/>
                <div style={{ flex: 1 }}>
                  <div style={{ fontSize: 14, fontWeight: 600 }}>Add professional installation & commissioning</div>
                  <div style={{ fontSize: 12.5, color: "var(--ink-3)", marginTop: 4 }}>
                    Factory-trained engineer, on-site connection, first-run calibration, brigade walkthrough. ~4% of order value. Final cost confirmed after site survey.
                  </div>
                </div>
                <div style={{ fontFamily: "var(--font-heading)", fontSize: 18, alignSelf: "center" }}>+ {KES(installCost)}</div>
              </div>
            </Card>

            {/* Payment */}
            <Card title="Payment" step={3}>
              <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 10 }}>
                {[
                  { id: "mpesa", label: "M-Pesa", desc: "STK push to phone" },
                  { id: "card", label: "Card", desc: "Visa / Mastercard" },
                  { id: "bank", label: "Bank transfer", desc: "EFT / RTGS" },
                  { id: "net30", label: "Net 30", desc: "Trade account", disabled: account !== "business" },
                ].map(p => (
                  <button key={p.id} disabled={p.disabled} onClick={() => setPayment(p.id)} style={{
                    padding: "16px 12px", textAlign: "left", cursor: p.disabled ? "default" : "pointer",
                    background: payment === p.id ? "var(--bg-sunken)" : "#fff",
                    border: `1.5px solid ${payment === p.id ? "var(--accent)" : "var(--line)"}`,
                    borderRadius: "var(--radius)",
                    opacity: p.disabled ? 0.4 : 1,
                  }}>
                    <div style={{ fontSize: 14, fontWeight: 600 }}>{p.label}</div>
                    <div style={{ fontSize: 12, color: "var(--ink-3)", marginTop: 4 }}>{p.desc}</div>
                  </button>
                ))}
              </div>
              {payment === "card" && (
                <div style={{ marginTop: 18 }}>
                  <FieldGroup label="Card number"><input className="input" placeholder="4242 4242 4242 4242" style={{ fontFamily: "var(--font-mono)", letterSpacing: "0.05em" }}/></FieldGroup>
                  <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 10, marginTop: 10 }}>
                    <FieldGroup label="Expiry"><input className="input" placeholder="MM / YY"/></FieldGroup>
                    <FieldGroup label="CVC"><input className="input" placeholder="123"/></FieldGroup>
                    <FieldGroup label="Name on card"><input className="input" placeholder="A. Wanjiru"/></FieldGroup>
                  </div>
                </div>
              )}
              {payment === "mpesa" && (
                <div style={{ marginTop: 18, padding: 16, background: "var(--bg-sunken)", borderRadius: "var(--radius)", display: "flex", gap: 14 }}>
                  <div style={{ width: 44, height: 44, borderRadius: 999, background: "#0a8e3e", color: "#fff", display: "flex", alignItems: "center", justifyContent: "center", fontWeight: 700, fontSize: 12, letterSpacing: "0.05em" }}>M·P</div>
                  <div style={{ flex: 1 }}>
                    <div style={{ fontSize: 13.5, fontWeight: 600 }}>You'll receive an STK push to authorise the payment</div>
                    <FieldGroup label="" style={{ marginTop: 10 }}>
                      <input className="input" placeholder="2547XX XXX XXX" style={{ fontFamily: "var(--font-mono)" }}/>
                    </FieldGroup>
                  </div>
                </div>
              )}
              {payment === "net30" && (
                <div style={{ marginTop: 18, padding: 16, background: "var(--bg-sunken)", borderRadius: "var(--radius)", fontSize: 13 }}>
                  Invoice issued at delivery. Payment terms <strong>Net 30 from invoice date</strong>. We'll send the proforma to your registered billing email.
                </div>
              )}
            </Card>

            <button className="btn btn-primary btn-lg" style={{ alignSelf: "start" }}
              onClick={() => navigate("confirmation")}>
              Place order — {KES(total)} <IconArrow size={16} sw={2}/>
            </button>
          </div>

          {/* Summary side */}
          <aside style={{ position: "sticky", top: 200, alignSelf: "start" }}>
            <div style={{ background: "#fff", border: "1px solid var(--line)", borderRadius: "var(--radius-lg)", overflow: "hidden" }}>
              <div style={{ padding: 20, borderBottom: "1px solid var(--line)" }}>
                <div style={{ fontFamily: "var(--font-heading)", fontSize: 20 }}>Your order</div>
              </div>
              <div>
                {lines.map(l => (
                  <div key={l.slug} style={{ display: "grid", gridTemplateColumns: "48px 1fr auto", gap: 12, padding: 14, borderBottom: "1px solid var(--line)", alignItems: "center" }}>
                    <div style={{ width: 48, height: 48, background: "var(--bg-sunken)", borderRadius: 4, padding: 4, position: "relative" }}>
                      <ProductIllustration kind={l.product.kind} photo={l.product.photos?.[0]}/>
                      <span style={{ position: "absolute", top: -6, right: -6, background: "var(--ink)", color: "#fff", fontSize: 10, fontWeight: 700, padding: "2px 5px", borderRadius: 999 }}>{l.qty}</span>
                    </div>
                    <div>
                      <div style={{ fontSize: 13, fontWeight: 500, lineHeight: 1.3 }}>{l.product.name}</div>
                      <div style={{ fontSize: 11, color: "var(--ink-3)", marginTop: 2 }}>{l.product.sku}</div>
                    </div>
                    <div style={{ fontSize: 13, fontVariantNumeric: "tabular-nums" }}>{KES(l.product.price * l.qty)}</div>
                  </div>
                ))}
              </div>
              <div style={{ padding: 20 }}>
                <Row label="Subtotal" value={KES(subtotal)}/>
                {installation && <Row label="Installation" value={KES(installCost)}/>}
                <Row label="Delivery" value={delivery === 0 ? "Free" : KES(delivery)}/>
                <Row label="VAT (16%)" value={KES(vat)}/>
                <div style={{ height: 1, background: "var(--line)", margin: "14px 0" }}/>
                <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline" }}>
                  <span style={{ fontWeight: 600 }}>Total</span>
                  <span style={{ fontFamily: "var(--font-heading)", fontSize: 26 }}>{KES(total)}</span>
                </div>
              </div>
            </div>

            <div style={{ marginTop: 14, fontSize: 12, color: "var(--ink-3)", textAlign: "center" }}>
              <IconShield size={12} sw={1.6} style={{ verticalAlign: "middle", marginRight: 6 }}/>
              All transactions encrypted · PCI-DSS compliant
            </div>
          </aside>
        </div>
      </div>
    </div>
  );
};

const CheckoutSteps = ({ step }) => {
  const steps = ["Cart", "Information", "Payment", "Confirmation"];
  return (
    <div style={{ display: "flex", gap: 0, fontSize: 12.5, color: "var(--ink-3)" }}>
      {steps.map((s, i) => (
        <React.Fragment key={s}>
          <span style={{ color: i + 1 === step ? "var(--ink)" : i + 1 < step ? "var(--accent)" : "var(--ink-3)", fontWeight: i + 1 === step ? 600 : 500 }}>
            {i + 1 < step ? <IconCheck size={14} sw={2} style={{ verticalAlign: "middle", color: "var(--accent)", marginRight: 4 }}/> : ""}
            {s}
          </span>
          {i < steps.length - 1 && <span style={{ margin: "0 10px", color: "var(--ink-4)" }}>—</span>}
        </React.Fragment>
      ))}
    </div>
  );
};

const Card = ({ title, step, done, children }) => (
  <section style={{ background: "#fff", border: "1px solid var(--line)", borderRadius: "var(--radius-lg)", padding: 24 }}>
    <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 18 }}>
      <span style={{
        width: 26, height: 26, borderRadius: 999,
        background: done ? "var(--accent)" : "var(--ink)", color: "#fff",
        display: "inline-flex", alignItems: "center", justifyContent: "center",
        fontSize: 12, fontWeight: 700,
      }}>{done ? <IconCheck size={14} sw={2.4}/> : step}</span>
      <h2 style={{ fontSize: 18, fontWeight: 500 }}>{title}</h2>
    </div>
    {children}
  </section>
);

const RadioCard = ({ selected, onSelect, title, subtitle }) => (
  <button onClick={onSelect} style={{
    padding: "14px 16px", textAlign: "left", cursor: "pointer",
    background: selected ? "var(--bg-sunken)" : "#fff",
    border: `1.5px solid ${selected ? "var(--accent)" : "var(--line)"}`,
    borderRadius: "var(--radius)",
  }}>
    <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
      <span style={{
        width: 16, height: 16, borderRadius: 999,
        border: "2px solid " + (selected ? "var(--accent)" : "var(--line-strong)"),
        background: selected ? "radial-gradient(circle, var(--accent) 0 4px, #fff 5px 100%)" : "transparent",
      }}/>
      <span style={{ fontWeight: 600 }}>{title}</span>
    </div>
    <div style={{ fontSize: 12.5, color: "var(--ink-3)", marginTop: 6 }}>{subtitle}</div>
  </button>
);

// ════════════════════════════════════════════════════════════════
// CONFIRMATION
// ════════════════════════════════════════════════════════════════
const ConfirmationPage = ({ navigate, cart, setCart }) => {
  const D = window.SHEFFIELD_DATA;
  const KES = D.KES;
  const lines = cart.map(l => ({ ...l, product: D.products.find(p => p.slug === l.slug) })).filter(l => l.product);
  const subtotal = lines.reduce((s, l) => s + l.product.price * l.qty, 0);
  const total = Math.round(subtotal * 1.16);

  React.useEffect(() => { /* Don't auto-clear cart; user can navigate back */ }, []);

  return (
    <div className="page-fade">
      <div className="container" style={{ paddingTop: 60, paddingBottom: 80, maxWidth: 880 }}>
        <div style={{ textAlign: "center" }}>
          <div style={{
            width: 64, height: 64, borderRadius: 999,
            background: "var(--accent)", color: "#fff",
            display: "inline-flex", alignItems: "center", justifyContent: "center",
          }}>
            <IconCheck size={34} sw={2.4}/>
          </div>
          <h1 style={{ fontSize: 48, fontWeight: 400, marginTop: 24, lineHeight: 1.1 }}>
            <span style={{ fontStyle: "italic", color: "var(--accent)" }}>Order placed.</span> Thank you.
          </h1>
          <p style={{ color: "var(--ink-2)", marginTop: 14, fontSize: 16 }}>
            Order <strong>SHF-2026-04412</strong> · Confirmation sent to <strong>anita@tribe.co.ke</strong>
          </p>
        </div>

        <div style={{ marginTop: 40, display: "grid", gridTemplateColumns: "1fr 1fr", gap: 16 }}>
          {[
            { title: "Site survey", time: "Within 2 business days", desc: "We'll confirm site readiness — utilities, ventilation, access." },
            { title: "Delivery scheduled", time: "Tue 9 June, AM window", desc: "Loading bay access confirmed via the notes you left." },
            { title: "Commissioning", time: "Same day", desc: "Engineer arrives 2 hours after delivery for first-run calibration." },
            { title: "Brigade training", time: "Day 2 — 14:00", desc: "60-min walkthrough with your team. Includes cleaning & HACCP." },
          ].map((s, i) => (
            <div key={i} style={{ background: "#fff", border: "1px solid var(--line)", borderRadius: "var(--radius-lg)", padding: 20 }}>
              <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.08em", textTransform: "uppercase", color: "var(--accent)" }}>Step {i + 1}</div>
              <div style={{ fontFamily: "var(--font-heading)", fontSize: 20, marginTop: 6 }}>{s.title}</div>
              <div style={{ fontSize: 13.5, color: "var(--ink-2)", marginTop: 4 }}>{s.time}</div>
              <p style={{ fontSize: 13, color: "var(--ink-3)", marginTop: 8 }}>{s.desc}</p>
            </div>
          ))}
        </div>

        <div style={{ marginTop: 24, background: "#fff", border: "1px solid var(--line)", borderRadius: "var(--radius-lg)", padding: 24 }}>
          <div style={{ fontFamily: "var(--font-heading)", fontSize: 22, fontWeight: 400 }}>What you ordered</div>
          <div style={{ marginTop: 14, display: "flex", flexDirection: "column", gap: 12 }}>
            {lines.map(l => (
              <div key={l.slug} style={{ display: "grid", gridTemplateColumns: "56px 1fr auto", gap: 14, alignItems: "center" }}>
                <div style={{ width: 56, height: 56, background: "var(--bg-sunken)", borderRadius: 6, padding: 4 }}>
                  <ProductIllustration kind={l.product.kind} photo={l.product.photos?.[0]}/>
                </div>
                <div>
                  <div style={{ fontSize: 14, fontWeight: 500 }}>{l.product.name}</div>
                  <div style={{ fontSize: 12, color: "var(--ink-3)" }}>Qty {l.qty} · {l.product.sku}</div>
                </div>
                <div style={{ fontFamily: "var(--font-heading)", fontSize: 18 }}>{KES(l.product.price * l.qty)}</div>
              </div>
            ))}
          </div>
          <div style={{ height: 1, background: "var(--line)", margin: "16px 0" }}/>
          <div style={{ display: "flex", justifyContent: "space-between", fontSize: 16, fontWeight: 500 }}>
            <span>Total paid</span>
            <span style={{ fontFamily: "var(--font-heading)", fontSize: 24 }}>{KES(total)}</span>
          </div>
        </div>

        <div style={{ marginTop: 32, display: "flex", gap: 10, justifyContent: "center" }}>
          <button className="btn btn-outline btn-lg"><IconDownload size={16} sw={1.6}/> Download invoice (PDF)</button>
          <button className="btn btn-primary btn-lg" onClick={() => { setCart([]); navigate("home"); }}>Back to shop</button>
        </div>
      </div>
    </div>
  );
};

// ════════════════════════════════════════════════════════════════
// COMPARE
// ════════════════════════════════════════════════════════════════
const ComparePage = ({ navigate, compare, setCompare, addToCart }) => {
  const D = window.SHEFFIELD_DATA;
  const KES = D.KES;
  const products = compare.map(s => D.products.find(p => p.slug === s)).filter(Boolean);

  // Collect union of spec labels (in order seen)
  const specLabels = [];
  products.forEach(p => p.specs.forEach(s => { if (!specLabels.includes(s.label)) specLabels.push(s.label); }));

  return (
    <div className="page-fade">
      <div className="container" style={{ paddingTop: 32, paddingBottom: 80 }}>
        <div style={{ fontSize: 12.5, color: "var(--ink-3)", marginBottom: 18 }}>
          <a href="#" onClick={(e) => { e.preventDefault(); navigate("home"); }}>Home</a> <span style={{ margin: "0 6px" }}>›</span> <span style={{ color: "var(--ink)" }}>Compare</span>
        </div>
        <h1 style={{ fontSize: 48, fontWeight: 400, lineHeight: 1.1 }}>Side by side.</h1>
        <p style={{ marginTop: 8, color: "var(--ink-3)" }}>Comparing {products.length} of 4 max</p>

        {products.length === 0 ? (
          <div style={{ marginTop: 40, padding: 60, textAlign: "center", background: "var(--bg-sunken)", borderRadius: "var(--radius-lg)" }}>
            <IconCompare size={36} sw={1.2} style={{ margin: "0 auto", color: "var(--ink-4)" }}/>
            <h2 style={{ fontFamily: "var(--font-heading)", fontSize: 24, marginTop: 16, fontWeight: 400 }}>Nothing to compare yet.</h2>
            <p style={{ color: "var(--ink-3)", marginTop: 8 }}>Add up to 4 products to compare specs side-by-side.</p>
            <button className="btn btn-primary" style={{ marginTop: 18 }} onClick={() => navigate("catalog")}>Browse the catalog</button>
          </div>
        ) : (
          <div style={{ marginTop: 28, overflowX: "auto", border: "1px solid var(--line)", borderRadius: "var(--radius-lg)", background: "#fff" }}>
            <table style={{ borderCollapse: "collapse", width: "100%", minWidth: 200 + 220 * products.length }}>
              <thead>
                <tr>
                  <th style={cellSticky}></th>
                  {products.map(p => (
                    <th key={p.slug} style={{ ...cell, padding: 16, textAlign: "left", verticalAlign: "top", minWidth: 220 }}>
                      <div style={{ position: "relative" }}>
                        <button onClick={() => setCompare(compare.filter(s => s !== p.slug))} style={{
                          position: "absolute", top: 0, right: 0, background: "transparent", border: 0, color: "var(--ink-3)",
                        }}><IconClose size={14} sw={2}/></button>
                        <div style={{ aspectRatio: "1/1", background: "var(--bg-sunken)", borderRadius: 8, padding: 12, marginRight: 20 }}>
                          <ProductIllustration kind={p.kind} photo={p.photos?.[0]}/>
                        </div>
                        <div style={{ fontSize: 11.5, fontWeight: 600, textTransform: "uppercase", letterSpacing: "0.06em", color: "var(--warm-2)", marginTop: 12 }}>
                          {D.brands.find(b => b.slug === p.brand)?.name}
                        </div>
                        <a href="#" onClick={(e) => { e.preventDefault(); navigate("product", { slug: p.slug }); }}
                          style={{ fontFamily: "var(--font-heading)", fontSize: 18, lineHeight: 1.2, color: "var(--ink)", display: "block", marginTop: 4 }}>{p.name}</a>
                        <div style={{ fontFamily: "var(--font-heading)", fontSize: 22, marginTop: 8 }}>{KES(p.price)}</div>
                        <button className="btn btn-primary btn-sm" style={{ marginTop: 10, width: "100%" }} onClick={() => addToCart(p.slug)}>
                          <IconCart size={14} sw={1.8}/> Add to cart
                        </button>
                      </div>
                    </th>
                  ))}
                  {products.length < 4 && (
                    <th style={{ ...cell, padding: 16, minWidth: 220, verticalAlign: "top" }}>
                      <button onClick={() => navigate("catalog")} style={{
                        width: "100%", aspectRatio: "1/1", border: "2px dashed var(--line-strong)",
                        background: "transparent", borderRadius: 8, color: "var(--ink-3)", display: "flex",
                        flexDirection: "column", alignItems: "center", justifyContent: "center", gap: 8, cursor: "pointer",
                      }}><IconPlus size={20} sw={1.6}/> Add product</button>
                    </th>
                  )}
                </tr>
              </thead>
              <tbody>
                <CompareSectionHeader cols={products.length + 1}>Key facts</CompareSectionHeader>
                <CompareRow label="Rating" cells={products.map(p => `★ ${p.rating} (${p.reviews})`)}/>
                <CompareRow label="Lead time" cells={products.map(p => p.leadTime)}/>
                <CompareRow label="In stock" cells={products.map(p => p.inStock > 0 ? `${p.inStock} units` : "Made to order")}/>
                <CompareRow label="Origin" cells={products.map(p => p.origin)}/>
                <CompareRow label="Warranty" cells={products.map(p => p.warranty)}/>
                <CompareSectionHeader cols={products.length + 1}>Dimensions & power</CompareSectionHeader>
                <CompareRow label="Power" cells={products.map(p => p.power || "—")}/>
                <CompareRow label="Capacity" cells={products.map(p => p.capacity)}/>
                <CompareRow label="Weight" cells={products.map(p => p.weight)}/>
                <CompareRow label="Dimensions (W × D × H)" cells={products.map(p => p.dimensions)}/>
                <CompareSectionHeader cols={products.length + 1}>Detailed specs</CompareSectionHeader>
                {specLabels.map(label => (
                  <CompareRow key={label} label={label}
                    cells={products.map(p => p.specs.find(s => s.label === label)?.value || "—")}/>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};

const cell = { borderBottom: "1px solid var(--line)", padding: "12px 16px", fontSize: 13.5, verticalAlign: "top" };
const cellSticky = { ...cell, background: "var(--bg-sunken)", color: "var(--ink-2)", fontWeight: 600, width: 200, position: "sticky", left: 0, zIndex: 1 };
const CompareRow = ({ label, cells }) => (
  <tr>
    <td style={cellSticky}>{label}</td>
    {cells.map((c, i) => <td key={i} style={cell}>{c}</td>)}
  </tr>
);
const CompareSectionHeader = ({ children, cols }) => (
  <tr>
    <td colSpan={cols} style={{ background: "var(--ink)", color: "#fff", padding: "10px 16px", fontSize: 11.5, fontWeight: 700, letterSpacing: "0.1em", textTransform: "uppercase" }}>
      {children}
    </td>
  </tr>
);

// reuse FieldGroup from product page
const FieldGroup = ({ label, children, full, style }) => (
  <div style={{ gridColumn: full ? "1 / -1" : "auto", display: "flex", flexDirection: "column", gap: 6, ...(style || {}) }}>
    {label && <label style={{ fontSize: 12, fontWeight: 600, color: "var(--ink-2)" }}>{label}</label>}
    {children}
  </div>
);

Object.assign(window, { CartPage, CheckoutPage, ConfirmationPage, ComparePage });
