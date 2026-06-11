// Sheffield — Auth pages: login, register, forgot password, reset, verify email.

const AuthLayout = ({ children, title, eyebrow, footer }) => {
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";
  return (
    <div style={{
      minHeight: "calc(100vh - 200px)",
      display: "grid", gridTemplateColumns: "1.05fr 1fr", gap: 0,
      background: "var(--bg)",
    }}>
      {/* Brand / aside */}
      <aside style={{
        background: "var(--warm-3)", color: "#f3eadd",
        padding: 64, position: "relative", overflow: "hidden",
        display: "flex", flexDirection: "column", justifyContent: "space-between",
      }}>
        {/* decorative pattern */}
        <div aria-hidden style={{
          position: "absolute", inset: 0, opacity: 0.5, pointerEvents: "none",
          background:
            "radial-gradient(circle at 100% 100%, var(--brand-700) 0, transparent 40%)," +
            "radial-gradient(circle at 0% 0%, rgba(184, 115, 51, 0.18) 0, transparent 40%)",
        }}/>

        <div style={{ position: "relative", display: "flex", alignItems: "center", gap: 8 }}>
          <SheffieldLogo variant="dark" height={32}/>
        </div>

        <div style={{ position: "relative", maxWidth: 460 }}>
          <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.12em", textTransform: "uppercase", color: "var(--warm-1)" }}>
            Trade portal
          </div>
          <h2 style={{
            fontFamily: "var(--font-heading)", color: "#f6ecd9",
            fontWeight: 400, marginTop: 14,
            fontSize: direction === "workshop" ? 36 : 46, lineHeight: 1.08,
          }}>
            Built for the kitchens that <span style={{ fontStyle: "italic", color: "var(--accent)" }}>feed the region</span>.
          </h2>
          <p style={{ marginTop: 16, fontSize: 14.5, color: "#c9bea4", lineHeight: 1.6 }}>
            Sheffield trade accounts unlock business pricing, multi-user permissions, repeat ordering, Net 30 terms and direct access to a specialist for quotes and project work.
          </p>
          <ul style={{ marginTop: 22, padding: 0, listStyle: "none", display: "flex", flexDirection: "column", gap: 10, fontSize: 13.5, color: "#d8c79d" }}>
            {[
              "Saved kitchens & re-order from past projects",
              "Track delivery, install and service tickets",
              "Multi-site billing with consolidated invoices",
              "Direct line to a specialist for quotes",
            ].map((t, i) => (
              <li key={i} style={{ display: "flex", gap: 10, alignItems: "center" }}>
                <span style={{ color: "var(--brand-500)", display: "inline-flex" }}><IconCheck size={16} sw={2.2}/></span> {t}
              </li>
            ))}
          </ul>
        </div>

        <div style={{ position: "relative", display: "flex", justifyContent: "space-between", alignItems: "center", fontSize: 12, color: "#9c927c" }}>
          <span>© Sheffield East Africa Ltd.</span>
          <span style={{ display: "flex", gap: 14 }}>
            <a href="#" onClick={(e) => e.preventDefault()} style={{ color: "#c9bea4" }}>Privacy</a>
            <a href="#" onClick={(e) => e.preventDefault()} style={{ color: "#c9bea4" }}>Help</a>
          </span>
        </div>
      </aside>

      {/* Form */}
      <main style={{ padding: 64, display: "flex", flexDirection: "column", justifyContent: "center" }}>
        <div style={{ width: "100%", maxWidth: 440 }}>
          {eyebrow && <div className="kicker" style={{ marginBottom: 8 }}>{eyebrow}</div>}
          <h1 style={{
            fontFamily: "var(--font-heading)", fontWeight: 400,
            fontSize: direction === "workshop" ? 32 : 40, lineHeight: 1.05,
          }}>{title}</h1>
          <div style={{ marginTop: 28 }}>{children}</div>
          {footer && <div style={{ marginTop: 32, fontSize: 13.5, color: "var(--ink-3)" }}>{footer}</div>}
        </div>
      </main>
    </div>
  );
};

// ───────── Login ─────────
const LoginPage = ({ navigate, setUser }) => {
  const [showPw, setShowPw] = React.useState(false);
  const [email, setEmail] = React.useState("anita@tribe.co.ke");
  const [pw, setPw] = React.useState("•••••••••••");
  const [loading, setLoading] = React.useState(false);

  const submit = (e) => {
    e?.preventDefault?.();
    setLoading(true);
    setTimeout(() => {
      setUser({ name: "Anita Wanjiru", role: "Executive Chef · Tribe Hotels", initials: "AW", email });
      navigate("account");
    }, 900);
  };

  return (
    <AuthLayout
      eyebrow="Sign in"
      title={<>Welcome back, chef.</>}
      footer={<>
        New to Sheffield? <a href="#" onClick={(e) => { e.preventDefault(); navigate("register"); }} style={{ color: "var(--accent)", fontWeight: 500 }}>Create an account →</a>
      </>}>
      <form onSubmit={submit} style={{ display: "flex", flexDirection: "column", gap: 14 }}>
        <SocialRow/>

        <Divider>or with your work email</Divider>

        <Field label="Email" required>
          <input className="input" type="email" autoComplete="username"
            value={email} onChange={(e) => setEmail(e.target.value)}
            placeholder="you@company.co.ke"/>
        </Field>

        <Field label="Password" required
          right={<a href="#" onClick={(e) => { e.preventDefault(); navigate("forgot"); }} style={{ fontSize: 12, color: "var(--accent)" }}>Forgot password?</a>}>
          <div style={{ position: "relative" }}>
            <input className="input" type={showPw ? "text" : "password"} autoComplete="current-password"
              value={pw} onChange={(e) => setPw(e.target.value)} placeholder="••••••••••"/>
            <button type="button" onClick={() => setShowPw(s => !s)}
              aria-label={showPw ? "Hide password" : "Show password"}
              style={{ position: "absolute", right: 10, top: "50%", transform: "translateY(-50%)", background: "transparent", border: 0, color: "var(--ink-3)", fontSize: 12, fontWeight: 600, padding: "4px 6px" }}>
              {showPw ? "HIDE" : "SHOW"}
            </button>
          </div>
        </Field>

        <Checkbox label="Keep me signed in on this device"/>

        <button type="submit" className="btn btn-primary btn-lg" style={{ marginTop: 6 }} disabled={loading}>
          {loading ? <Spinner/> : <>Sign in <IconArrow size={16} sw={2}/></>}
        </button>

        <div style={{ marginTop: 6, padding: "12px 14px", background: "var(--bg-sunken)", borderRadius: "var(--radius)", fontSize: 12.5, color: "var(--ink-2)", display: "flex", gap: 10, alignItems: "start" }}>
          <IconShield size={16} style={{ color: "var(--secondary)", flexShrink: 0, marginTop: 1 }}/>
          <span>Two-factor authentication is supported. You'll be prompted for a code after sign-in if it's enabled on your account.</span>
        </div>
      </form>
    </AuthLayout>
  );
};

// ───────── Register ─────────
const RegisterPage = ({ navigate, setUser, params = {} }) => {
  const [tab, setTab] = React.useState(params.trade ? "business" : "personal");
  const [loading, setLoading] = React.useState(false);

  const submit = (e) => {
    e?.preventDefault?.();
    setLoading(true);
    setTimeout(() => {
      setUser({ name: "Daniel Mwangi", role: tab === "business" ? "Buyer · Trade account" : "Member", initials: "DM", email: "daniel@kilimani.co.ke" });
      navigate(tab === "business" ? "register" : "account", { verify: true });
      if (tab !== "business") setLoading(false);
      else setLoading(false);
    }, 900);
  };

  return (
    <AuthLayout
      eyebrow="Create account"
      title={<>Start with Sheffield.</>}
      footer={<>Already have an account? <a href="#" onClick={(e) => { e.preventDefault(); navigate("login"); }} style={{ color: "var(--accent)", fontWeight: 500 }}>Sign in →</a></>}>

      {/* Account type tabs */}
      <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10, marginBottom: 24 }}>
        {[
          { id: "personal", title: "Personal", desc: "Owner-operated kitchens, single buyer." },
          { id: "business", title: "Business / Trade", desc: "VAT receipt, Net 30, multi-user." },
        ].map(t => (
          <button key={t.id} onClick={() => setTab(t.id)} type="button"
            style={{
              padding: "14px 14px", textAlign: "left", cursor: "pointer",
              background: tab === t.id ? "var(--bg-sunken)" : "#fff",
              border: `1.5px solid ${tab === t.id ? "var(--accent)" : "var(--line)"}`,
              borderRadius: "var(--radius)",
            }}>
            <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
              <span style={{
                width: 14, height: 14, borderRadius: 999,
                border: "2px solid " + (tab === t.id ? "var(--accent)" : "var(--line-strong)"),
                background: tab === t.id ? "radial-gradient(circle, var(--accent) 0 3px, #fff 4px)" : "transparent",
              }}/>
              <span style={{ fontWeight: 600, fontSize: 14 }}>{t.title}</span>
            </div>
            <div style={{ fontSize: 12, color: "var(--ink-3)", marginTop: 4 }}>{t.desc}</div>
          </button>
        ))}
      </div>

      <form onSubmit={submit} style={{ display: "flex", flexDirection: "column", gap: 14 }}>
        <SocialRow label="Continue with"/>
        <Divider>or with email</Divider>

        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
          <Field label="First name" required><input className="input" placeholder="Anita"/></Field>
          <Field label="Last name" required><input className="input" placeholder="Wanjiru"/></Field>
        </div>

        <Field label="Work email" required>
          <input className="input" type="email" placeholder="you@company.co.ke"/>
        </Field>

        {tab === "business" && (
          <>
            <Field label="Company / kitchen name" required><input className="input" placeholder="Tribe Hotels Ltd"/></Field>
            <div style={{ display: "grid", gridTemplateColumns: "1.4fr 1fr", gap: 12 }}>
              <Field label="KRA PIN"><input className="input" placeholder="P051..." style={{ fontFamily: "var(--font-mono)" }}/></Field>
              <Field label="Country">
                <select className="select" defaultValue="KE">
                  <option value="KE">Kenya</option><option value="UG">Uganda</option>
                  <option value="TZ">Tanzania</option><option value="RW">Rwanda</option>
                </select>
              </Field>
            </div>
            <Field label="Role">
              <select className="select" defaultValue="chef">
                <option value="chef">Executive / Head Chef</option>
                <option value="owner">Owner / Founder</option>
                <option value="ops">Operations Manager</option>
                <option value="proc">Procurement Officer</option>
                <option value="other">Other</option>
              </select>
            </Field>
          </>
        )}

        <Field label="Phone">
          <div style={{ display: "flex", gap: 8 }}>
            <select className="select" style={{ width: 100 }} defaultValue="+254">
              <option>+254</option><option>+256</option><option>+255</option><option>+250</option>
            </select>
            <input className="input" placeholder="7XX XXX XXX" type="tel" style={{ flex: 1 }}/>
          </div>
        </Field>

        <Field label="Password" required help="At least 12 characters with a number and a symbol.">
          <input className="input" type="password" placeholder="••••••••••"/>
          <PasswordStrength score={3}/>
        </Field>

        <Checkbox label={<>I agree to the <a href="#" onClick={(e) => e.preventDefault()} style={{ color: "var(--accent)" }}>Terms</a> and <a href="#" onClick={(e) => e.preventDefault()} style={{ color: "var(--accent)" }}>Privacy Policy</a>.</>} required/>
        <Checkbox label="Send me product news, project stories and seasonal catalogs." defaultChecked/>

        <button type="submit" className="btn btn-primary btn-lg" style={{ marginTop: 4 }} disabled={loading}>
          {loading ? <Spinner/> : <>{tab === "business" ? "Apply for trade account" : "Create account"} <IconArrow size={16} sw={2}/></>}
        </button>

        {tab === "business" && (
          <div style={{ fontSize: 12.5, color: "var(--ink-3)", lineHeight: 1.55, marginTop: 4 }}>
            Trade accounts are reviewed within one business day. You'll get an email once approved, with access to business pricing and Net 30 terms.
          </div>
        )}
      </form>
    </AuthLayout>
  );
};

// ───────── Forgot password ─────────
const ForgotPage = ({ navigate }) => {
  const [sent, setSent] = React.useState(false);
  return (
    <AuthLayout
      eyebrow="Forgot password"
      title={sent ? <>Check your email.</> : <>Let's reset that.</>}
      footer={<><a href="#" onClick={(e) => { e.preventDefault(); navigate("login"); }} style={{ color: "var(--ink-2)" }}>← Back to sign in</a></>}>
      {sent ? (
        <div>
          <div style={{ padding: 20, background: "var(--bg-sunken)", borderRadius: "var(--radius)", display: "flex", gap: 14 }}>
            <div style={{ width: 36, height: 36, borderRadius: 999, background: "var(--accent)", color: "#fff", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
              <IconMail size={18} sw={1.6}/>
            </div>
            <div>
              <div style={{ fontWeight: 600 }}>Reset link sent</div>
              <p style={{ fontSize: 13.5, color: "var(--ink-2)", marginTop: 6 }}>
                If <strong>anita@tribe.co.ke</strong> is registered, a reset link is on its way. The link expires in 30 minutes.
              </p>
            </div>
          </div>
          <button className="btn btn-outline" style={{ width: "100%", marginTop: 16 }} onClick={() => setSent(false)}>
            Use a different email
          </button>
          <button className="btn btn-ghost btn-sm" style={{ width: "100%", marginTop: 4 }}
            onClick={() => navigate("reset")}>
            (Demo) Open the reset screen →
          </button>
        </div>
      ) : (
        <form onSubmit={(e) => { e.preventDefault(); setSent(true); }} style={{ display: "flex", flexDirection: "column", gap: 14 }}>
          <p style={{ fontSize: 14, color: "var(--ink-2)", marginTop: -16, lineHeight: 1.55 }}>
            Enter the email on your Sheffield account and we'll send a secure link to reset your password.
          </p>
          <Field label="Email"><input className="input" type="email" placeholder="you@company.co.ke"/></Field>
          <button type="submit" className="btn btn-primary btn-lg">Send reset link <IconArrow size={16} sw={2}/></button>
          <div style={{ marginTop: 8, fontSize: 12.5, color: "var(--ink-3)" }}>
            Don't have the original email anymore? <a href="#" onClick={(e) => e.preventDefault()} style={{ color: "var(--accent)" }}>Contact support →</a>
          </div>
        </form>
      )}
    </AuthLayout>
  );
};

// ───────── Reset password ─────────
const ResetPage = ({ navigate }) => {
  const [done, setDone] = React.useState(false);
  return (
    <AuthLayout
      eyebrow="Set a new password"
      title={done ? <>Password updated.</> : <>Set a new password.</>}
      footer={!done && <><a href="#" onClick={(e) => { e.preventDefault(); navigate("login"); }} style={{ color: "var(--ink-2)" }}>← Back to sign in</a></>}>
      {done ? (
        <div>
          <div style={{ padding: 20, background: "var(--bg-sunken)", borderRadius: "var(--radius)", display: "flex", gap: 14 }}>
            <div style={{ width: 36, height: 36, borderRadius: 999, background: "#2f7a4a", color: "#fff", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
              <IconCheck size={20} sw={2.4}/>
            </div>
            <div>
              <div style={{ fontWeight: 600 }}>You're all set</div>
              <p style={{ fontSize: 13.5, color: "var(--ink-2)", marginTop: 6 }}>
                Your password has been updated. All other devices have been signed out as a security precaution.
              </p>
            </div>
          </div>
          <button className="btn btn-primary btn-lg" style={{ width: "100%", marginTop: 18 }} onClick={() => navigate("login")}>
            Sign in now <IconArrow size={16} sw={2}/>
          </button>
        </div>
      ) : (
        <form onSubmit={(e) => { e.preventDefault(); setDone(true); }} style={{ display: "flex", flexDirection: "column", gap: 14 }}>
          <Field label="New password"><input className="input" type="password" placeholder="••••••••••"/><PasswordStrength score={4}/></Field>
          <Field label="Confirm new password"><input className="input" type="password" placeholder="••••••••••"/></Field>
          <ul style={{ padding: 0, margin: 0, listStyle: "none", display: "flex", flexDirection: "column", gap: 6, fontSize: 12.5, color: "var(--ink-2)" }}>
            {[
              "At least 12 characters",
              "A number and a special character",
              "Different from your last 5 passwords",
            ].map((r, i) => (
              <li key={i} style={{ display: "flex", gap: 8, alignItems: "center" }}>
                <IconCheck size={14} sw={2.4} stroke="#2f7a4a"/> {r}
              </li>
            ))}
          </ul>
          <button type="submit" className="btn btn-primary btn-lg" style={{ marginTop: 6 }}>Update password <IconArrow size={16} sw={2}/></button>
        </form>
      )}
    </AuthLayout>
  );
};

// ───────── Verify email ─────────
const VerifyEmailPage = ({ navigate }) => {
  const [code, setCode] = React.useState(["", "", "", "", "", ""]);
  const refs = React.useRef([]);

  const updateAt = (i, v) => {
    v = v.replace(/\D/g, "").slice(0, 1);
    const next = [...code]; next[i] = v; setCode(next);
    if (v && i < 5) refs.current[i + 1]?.focus();
  };

  return (
    <AuthLayout eyebrow="Verify email"
      title={<>Verify your email.</>}
      footer={<><a href="#" onClick={(e) => { e.preventDefault(); navigate("login"); }} style={{ color: "var(--ink-2)" }}>← Back to sign in</a></>}>
      <p style={{ fontSize: 14, color: "var(--ink-2)", marginTop: -18, lineHeight: 1.55 }}>
        We sent a 6-digit code to <strong>daniel@kilimani.co.ke</strong>. Enter it below to confirm this email belongs to you.
      </p>
      <div style={{ marginTop: 22, display: "flex", gap: 10, justifyContent: "center" }}>
        {code.map((v, i) => (
          <input key={i} ref={el => refs.current[i] = el}
            className="input" inputMode="numeric" maxLength={1}
            value={v} onChange={(e) => updateAt(i, e.target.value)}
            style={{ width: 52, height: 64, textAlign: "center", fontFamily: "var(--font-heading)", fontSize: 28 }}/>
        ))}
      </div>
      <button className="btn btn-primary btn-lg" style={{ marginTop: 22, width: "100%" }} onClick={() => navigate("account")}>
        Verify and continue
      </button>
      <div style={{ marginTop: 14, fontSize: 13, color: "var(--ink-3)", textAlign: "center" }}>
        Didn't get the code? <a href="#" onClick={(e) => e.preventDefault()} style={{ color: "var(--accent)" }}>Resend</a>
        <span style={{ margin: "0 8px", color: "var(--ink-4)" }}>·</span>
        <a href="#" onClick={(e) => e.preventDefault()}>Use a different email</a>
      </div>
    </AuthLayout>
  );
};

// ───────── Account dashboard (lightweight) ─────────
const AccountPage = ({ navigate, user, setUser, params = {} }) => {
  const tabs = [
    { id: "overview", label: "Overview" },
    { id: "orders", label: "Orders" },
    { id: "quotes", label: "Quotes & RFQs" },
    { id: "wishlist", label: "Wishlist" },
    { id: "service", label: "Service" },
    { id: "addresses", label: "Addresses" },
    { id: "team", label: "Team & permissions" },
    { id: "settings", label: "Settings" },
  ];
  const [tab, setTab] = React.useState(params.tab || "overview");

  if (!user) {
    return (
      <div className="page-fade">
        <div className="container" style={{ paddingTop: 80, paddingBottom: 80, textAlign: "center" }}>
          <IconUser size={36} sw={1.2} style={{ margin: "0 auto", color: "var(--ink-4)" }}/>
          <h1 style={{ fontFamily: "var(--font-heading)", fontSize: 36, fontWeight: 400, marginTop: 18 }}>Sign in to view your account.</h1>
          <p style={{ color: "var(--ink-3)", marginTop: 8 }}>Orders, quotes, wishlist, service tickets — all in one place.</p>
          <div style={{ marginTop: 22, display: "flex", gap: 10, justifyContent: "center" }}>
            <button className="btn btn-primary" onClick={() => navigate("login")}>Sign in</button>
            <button className="btn btn-outline" onClick={() => navigate("register")}>Create account</button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="page-fade">
      <div className="container" style={{ paddingTop: 32, paddingBottom: 80 }}>
        <div style={{ display: "grid", gridTemplateColumns: "260px 1fr", gap: 40 }}>
          {/* Sidebar */}
          <aside>
            <div style={{ padding: 20, background: "var(--bg-sunken)", borderRadius: "var(--radius-lg)" }}>
              <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
                <div style={{ width: 48, height: 48, borderRadius: 999, background: "var(--accent)", color: "#fff", fontWeight: 700, display: "flex", alignItems: "center", justifyContent: "center", fontSize: 16 }}>{user.initials}</div>
                <div style={{ minWidth: 0 }}>
                  <div style={{ fontWeight: 600 }}>{user.name}</div>
                  <div style={{ fontSize: 12, color: "var(--ink-3)" }}>{user.role}</div>
                </div>
              </div>
              <button className="btn btn-outline btn-sm" style={{ width: "100%", marginTop: 14 }} onClick={() => setUser(null)}>Sign out</button>
            </div>
            <nav style={{ marginTop: 18, display: "flex", flexDirection: "column", gap: 2 }}>
              {tabs.map(t => (
                <button key={t.id} onClick={() => setTab(t.id)} style={{
                  textAlign: "left", padding: "10px 14px", background: tab === t.id ? "var(--ink)" : "transparent",
                  color: tab === t.id ? "#fff" : "var(--ink-2)", border: 0,
                  borderRadius: "var(--radius)", fontSize: 13.5, fontWeight: tab === t.id ? 600 : 500,
                  cursor: "pointer",
                }}>{t.label}</button>
              ))}
            </nav>
          </aside>

          <div>
            {tab === "overview" && <AccountOverview user={user} navigate={navigate}/>}
            {tab === "orders" && <AccountOrders navigate={navigate}/>}
            {tab === "quotes" && <AccountQuotes/>}
            {tab === "wishlist" && (
              <div>
                <h2 style={{ fontSize: 28, fontWeight: 400, marginBottom: 12 }}>Wishlist</h2>
                <p style={{ color: "var(--ink-3)" }}>See the dedicated wishlist page for full management.</p>
                <button className="btn btn-primary" style={{ marginTop: 16 }} onClick={() => navigate("wishlist")}>Open wishlist →</button>
              </div>
            )}
            {(tab === "service" || tab === "addresses" || tab === "team" || tab === "settings") && (
              <div style={{ padding: 40, background: "var(--bg-sunken)", borderRadius: "var(--radius-lg)", textAlign: "center" }}>
                <h2 style={{ fontFamily: "var(--font-heading)", fontSize: 28, fontWeight: 400 }}>{tabs.find(t => t.id === tab).label}</h2>
                <p style={{ color: "var(--ink-3)", marginTop: 8 }}>This area is a placeholder — let me know if you'd like it built out next.</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

const AccountOverview = ({ user, navigate }) => (
  <div>
    <h2 style={{ fontFamily: "var(--font-heading)", fontSize: 36, fontWeight: 400 }}>Welcome back, {user.name.split(" ")[0]}.</h2>
    <p style={{ color: "var(--ink-3)", marginTop: 6 }}>Here's what's happening across your account.</p>

    <div style={{ marginTop: 28, display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 14 }}>
      {[
        { label: "Open orders", value: "3", sub: "1 arriving Tuesday" },
        { label: "Pending quotes", value: "2", sub: "Awaiting your approval" },
        { label: "Service tickets", value: "1", sub: "Engineer dispatched" },
      ].map((s, i) => (
        <div key={i} style={{ padding: 20, background: "#fff", border: "1px solid var(--line)", borderRadius: "var(--radius-lg)" }}>
          <div style={{ fontSize: 12.5, color: "var(--ink-3)" }}>{s.label}</div>
          <div style={{ fontFamily: "var(--font-heading)", fontSize: 40, marginTop: 4 }}>{s.value}</div>
          <div style={{ fontSize: 12, color: "var(--ink-3)" }}>{s.sub}</div>
        </div>
      ))}
    </div>

    <div style={{ marginTop: 28, padding: 20, background: "var(--ink)", color: "#f3eadd", borderRadius: "var(--radius-lg)" }}>
      <div style={{ display: "flex", gap: 18, alignItems: "center", justifyContent: "space-between" }}>
        <div>
          <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.12em", textTransform: "uppercase", color: "var(--warm-1)" }}>Trade benefit</div>
          <div style={{ fontFamily: "var(--font-heading)", fontSize: 22, marginTop: 4 }}>Your Net 30 line is at KES 2.4M of 4M used</div>
        </div>
        <button className="btn btn-primary">Pay invoices</button>
      </div>
      <div style={{ marginTop: 14, height: 8, background: "rgba(255,255,255,0.08)", borderRadius: 999, overflow: "hidden" }}>
        <div style={{ width: "60%", height: "100%", background: "var(--accent)" }}/>
      </div>
    </div>
  </div>
);

const AccountOrders = ({ navigate }) => {
  const KES = window.SHEFFIELD_DATA.KES;
  const orders = [
    { id: "SHF-2026-04412", date: "26 May 2026", status: "Out for delivery", total: 1722600, items: 3, kind: "combi-oven" },
    { id: "SHF-2026-04318", date: "12 May 2026", status: "Installed", total: 685000, items: 1, kind: "refrigerator" },
    { id: "SHF-2026-04205", date: "28 Apr 2026", status: "Delivered", total: 198000, items: 2, kind: "slicer" },
    { id: "SHF-2026-04102", date: "16 Apr 2026", status: "Completed", total: 86500, items: 1, kind: "blender" },
  ];
  const statusColor = (s) => s === "Out for delivery" ? "var(--accent)" : s === "Installed" ? "#2f7a4a" : "var(--ink-3)";
  return (
    <div>
      <h2 style={{ fontFamily: "var(--font-heading)", fontSize: 32, fontWeight: 400 }}>Orders</h2>
      <p style={{ color: "var(--ink-3)", marginTop: 6 }}>All your Sheffield orders, with invoices and delivery status.</p>
      <div style={{ marginTop: 22, display: "flex", flexDirection: "column", gap: 10 }}>
        {orders.map(o => (
          <div key={o.id} style={{
            display: "grid", gridTemplateColumns: "60px 1fr auto auto auto", gap: 18, alignItems: "center",
            padding: 16, background: "#fff", border: "1px solid var(--line)", borderRadius: "var(--radius-lg)",
          }}>
            <div style={{ width: 60, height: 60, background: "var(--bg-sunken)", borderRadius: 8, padding: 6 }}>
              <ProductIllustration kind={o.kind}/>
            </div>
            <div>
              <div style={{ fontWeight: 600, fontSize: 14 }}>{o.id}</div>
              <div style={{ fontSize: 12, color: "var(--ink-3)" }}>{o.date} · {o.items} item{o.items === 1 ? "" : "s"}</div>
            </div>
            <span style={{ fontSize: 12, fontWeight: 600, letterSpacing: "0.04em", color: statusColor(o.status) }}>● {o.status}</span>
            <div style={{ fontFamily: "var(--font-heading)", fontSize: 18, whiteSpace: "nowrap", fontVariantNumeric: "tabular-nums" }}>{KES(o.total)}</div>
            <button className="btn btn-outline btn-sm">View</button>
          </div>
        ))}
      </div>
    </div>
  );
};

const AccountQuotes = () => {
  const KES = window.SHEFFIELD_DATA.KES;
  const quotes = [
    { id: "RFQ-2026-04183", title: "Westlands kitchen build — 24 line items", status: "Awaiting your approval", total: 6420000, date: "23 May 2026" },
    { id: "RFQ-2026-04167", title: "Combi oven + installation", status: "Sent", total: 1644500, date: "20 May 2026" },
  ];
  return (
    <div>
      <h2 style={{ fontFamily: "var(--font-heading)", fontSize: 32, fontWeight: 400 }}>Quotes & RFQs</h2>
      <p style={{ color: "var(--ink-3)", marginTop: 6 }}>Pending and historical quotations. Approve a quote to convert it into an order.</p>
      <div style={{ marginTop: 22, display: "flex", flexDirection: "column", gap: 10 }}>
        {quotes.map(q => (
          <div key={q.id} style={{
            padding: 18, background: "#fff", border: "1px solid var(--line)", borderRadius: "var(--radius-lg)",
            display: "grid", gridTemplateColumns: "1fr auto auto", gap: 18, alignItems: "center",
          }}>
            <div>
              <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.06em", textTransform: "uppercase", color: "var(--ink-3)" }}>{q.id}</div>
              <div style={{ fontSize: 15, fontWeight: 600, marginTop: 4 }}>{q.title}</div>
              <div style={{ fontSize: 12, color: "var(--ink-3)", marginTop: 4 }}>{q.date} · <span style={{ color: q.status === "Awaiting your approval" ? "var(--accent)" : "var(--ink-3)" }}>{q.status}</span></div>
            </div>
            <div style={{ fontFamily: "var(--font-heading)", fontSize: 20, whiteSpace: "nowrap", fontVariantNumeric: "tabular-nums" }}>{KES(q.total)}</div>
            <div style={{ display: "flex", gap: 6 }}>
              <button className="btn btn-outline btn-sm">View</button>
              {q.status === "Awaiting your approval" && <button className="btn btn-primary btn-sm">Approve</button>}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

// ───────── Reusable bits ─────────
const Field = ({ label, help, required, right, children }) => (
  <div>
    <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline", marginBottom: 6 }}>
      <label style={{ fontSize: 12.5, fontWeight: 600, color: "var(--ink-2)" }}>
        {label} {required && <span style={{ color: "var(--accent)" }}>*</span>}
      </label>
      {right}
    </div>
    {children}
    {help && <div style={{ fontSize: 11.5, color: "var(--ink-3)", marginTop: 6 }}>{help}</div>}
  </div>
);

const Checkbox = ({ label, defaultChecked, required }) => {
  const [c, setC] = React.useState(defaultChecked || false);
  return (
    <label style={{ display: "flex", alignItems: "start", gap: 10, fontSize: 13, color: "var(--ink-2)", cursor: "pointer", lineHeight: 1.45 }}>
      <span style={{
        width: 16, height: 16, marginTop: 2, flexShrink: 0,
        border: "1.5px solid " + (c ? "var(--accent)" : "var(--line-strong)"),
        borderRadius: 3, display: "flex", alignItems: "center", justifyContent: "center",
        background: c ? "var(--accent)" : "transparent",
      }}>{c && <IconCheck size={12} sw={2.4} stroke="#fff"/>}</span>
      <input type="checkbox" checked={c} onChange={() => setC(!c)} required={required} style={{ display: "none" }}/>
      <span>{label}</span>
    </label>
  );
};

const SocialRow = ({ label = "Continue with" }) => (
  <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 8 }}>
    {[
      { name: "Google", glyph: <GoogleMark/> },
      { name: "Microsoft", glyph: <MicrosoftMark/> },
      { name: "Apple", glyph: <AppleMark/> },
    ].map(p => (
      <button key={p.name} type="button" className="btn btn-outline"
        style={{ height: 44, fontSize: 13, fontWeight: 500, gap: 8 }}>
        {p.glyph} {p.name}
      </button>
    ))}
  </div>
);

const GoogleMark = () => (
  <svg width="16" height="16" viewBox="0 0 16 16">
    <path d="M15.4 8.2c0-.5 0-1-.1-1.5H8v2.9h4.2c-.2 1-.7 1.8-1.6 2.4v2h2.6c1.5-1.4 2.4-3.5 2.4-5.8z" fill="#4285F4"/>
    <path d="M8 16c2.2 0 4-.7 5.3-2l-2.6-2c-.7.5-1.6.8-2.7.8-2.1 0-3.8-1.4-4.5-3.3H1v2.1A8 8 0 0 0 8 16z" fill="#34A853"/>
    <path d="M3.6 9.6A4.8 4.8 0 0 1 3.3 8c0-.6.1-1.1.3-1.6V4.3H1A8 8 0 0 0 0 8c0 1.3.3 2.5.9 3.7l2.6-2z" fill="#FBBC05"/>
    <path d="M8 3.2c1.2 0 2.3.4 3.1 1.2l2.3-2.3A8 8 0 0 0 8 0 8 8 0 0 0 1 4.3l2.6 2C4.2 4.6 5.9 3.2 8 3.2z" fill="#EA4335"/>
  </svg>
);
const MicrosoftMark = () => (
  <svg width="16" height="16" viewBox="0 0 16 16">
    <rect x="1" y="1" width="6.5" height="6.5" fill="#F25022"/>
    <rect x="8.5" y="1" width="6.5" height="6.5" fill="#7FBA00"/>
    <rect x="1" y="8.5" width="6.5" height="6.5" fill="#00A4EF"/>
    <rect x="8.5" y="8.5" width="6.5" height="6.5" fill="#FFB900"/>
  </svg>
);
const AppleMark = () => (
  <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
    <path d="M11.7 8.6c0-1.9 1.6-2.8 1.6-2.9-.9-1.3-2.2-1.5-2.7-1.5-1.1-.1-2.2.7-2.8.7-.6 0-1.5-.7-2.5-.7-1.3 0-2.5.8-3.2 2-1.3 2.3-.3 5.7 1 7.5.6.9 1.4 1.9 2.4 1.9.9 0 1.3-.6 2.4-.6 1.1 0 1.4.6 2.4.6 1 0 1.7-.9 2.3-1.8.7-1 1-2.1 1-2.2-.1 0-2-.7-2-3zM9.9 3c.5-.6.8-1.4.7-2.3-.7 0-1.5.5-2 1.1-.4.5-.8 1.3-.7 2.1.8.1 1.6-.4 2-.9z"/>
  </svg>
);

const Divider = ({ children }) => (
  <div style={{ display: "flex", alignItems: "center", gap: 12, margin: "6px 0", fontSize: 11.5, color: "var(--ink-3)", textTransform: "uppercase", letterSpacing: "0.08em" }}>
    <span style={{ flex: 1, height: 1, background: "var(--line)" }}/>
    <span>{children}</span>
    <span style={{ flex: 1, height: 1, background: "var(--line)" }}/>
  </div>
);

const PasswordStrength = ({ score = 3 }) => {
  const labels = ["Weak", "Fair", "Good", "Strong", "Very strong"];
  const colors = ["#c64646", "#d97706", "#a3a307", "#4a8a3a", "#2f7a4a"];
  return (
    <div style={{ marginTop: 8 }}>
      <div style={{ display: "flex", gap: 4 }}>
        {[0,1,2,3,4].map(i => (
          <div key={i} style={{ flex: 1, height: 4, borderRadius: 2, background: i <= score ? colors[score] : "var(--line)" }}/>
        ))}
      </div>
      <div style={{ fontSize: 11.5, color: colors[score], marginTop: 4, fontWeight: 500 }}>{labels[score]}</div>
    </div>
  );
};

const Spinner = () => (
  <span style={{
    display: "inline-block", width: 14, height: 14, border: "2px solid rgba(255,255,255,0.3)",
    borderTopColor: "#fff", borderRadius: 999, animation: "spin 700ms linear infinite",
  }}/>
);

// inject keyframes for spinner
if (typeof document !== "undefined" && !document.getElementById("__sheffield_spin_kf")) {
  const s = document.createElement("style"); s.id = "__sheffield_spin_kf";
  s.textContent = "@keyframes spin { to { transform: rotate(360deg); } }";
  document.head.appendChild(s);
}

Object.assign(window, { LoginPage, RegisterPage, ForgotPage, ResetPage, VerifyEmailPage, AccountPage });
