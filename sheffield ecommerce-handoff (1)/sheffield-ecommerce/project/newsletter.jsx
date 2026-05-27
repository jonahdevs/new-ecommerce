// Sheffield — Newsletter subscribe band. Shown above the footer.

const NewsletterBand = () => {
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";
  const [email, setEmail] = React.useState("");
  const [submitted, setSubmitted] = React.useState(false);
  const [interests, setInterests] = React.useState(["new-products"]);
  const [error, setError] = React.useState("");

  const toggleInterest = (id) => setInterests(prev => prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]);

  const submit = (e) => {
    e.preventDefault();
    setError("");
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) { setError("Enter a valid work email."); return; }
    setSubmitted(true);
  };

  // ─── Editorial: warm magazine band ───
  if (direction !== "workshop") {
    return (
      <section style={{ marginTop: 64 }}>
        <div className="container">
          <div style={{
            position: "relative", overflow: "hidden",
            background: "var(--bg-sunken)",
            borderRadius: "var(--radius-lg)",
            padding: "64px 72px",
            display: "grid", gridTemplateColumns: "1.2fr 1fr", gap: 64, alignItems: "center",
          }}>
            <div>
              <div className="kicker" style={{ color: "var(--warm-2)" }}>The Sheffield Quarterly</div>
              <h2 style={{
                fontFamily: "var(--font-heading)", fontWeight: 400,
                fontSize: "clamp(36px, 4vw, 52px)", lineHeight: 1.05, marginTop: 14,
              }}>
                One letter, four times a year. <span style={{ fontStyle: "italic", color: "var(--accent)" }}>Worth the read.</span>
              </h2>
              <p style={{ marginTop: 16, fontSize: 15.5, lineHeight: 1.55, color: "var(--ink-2)", maxWidth: 460 }}>
                Seasonal catalog drops, a kitchen-floor project story, our pick of new arrivals, and what's moving in the trade. No promotions for the sake of it — promise.
              </p>

              {submitted ? (
                <SuccessCard email={email}/>
              ) : (
                <form onSubmit={submit} style={{ marginTop: 26 }}>
                  <div style={{ display: "flex", gap: 8, maxWidth: 460 }}>
                    <div style={{ position: "relative", flex: 1 }}>
                      <IconMail size={16} style={{ position: "absolute", left: 14, top: 14, color: "var(--ink-3)" }}/>
                      <input className="input" type="email" placeholder="you@kitchen.co.ke"
                        value={email} onChange={(e) => setEmail(e.target.value)}
                        style={{ paddingLeft: 40, height: 52, fontSize: 15 }}/>
                    </div>
                    <button type="submit" className="btn btn-primary btn-lg">Subscribe <IconArrow size={16} sw={2}/></button>
                  </div>
                  {error && <div style={{ marginTop: 8, fontSize: 12.5, color: "var(--accent)" }}>{error}</div>}

                  <div style={{ marginTop: 18, display: "flex", flexWrap: "wrap", gap: 8 }}>
                    {[
                      { id: "new-products", label: "New products" },
                      { id: "seasonal-catalogs", label: "Seasonal catalogs" },
                      { id: "trade-pricing", label: "Trade-only offers" },
                      { id: "projects", label: "Project stories" },
                    ].map(o => (
                      <InterestChip key={o.id} label={o.label}
                        active={interests.includes(o.id)}
                        onClick={() => toggleInterest(o.id)}/>
                    ))}
                  </div>
                  <p style={{ marginTop: 16, fontSize: 11.5, color: "var(--ink-3)", maxWidth: 460 }}>
                    By subscribing you agree to our <a href="#" onClick={(e) => e.preventDefault()} style={{ color: "var(--ink-2)" }}>Privacy Policy</a>. One-click unsubscribe in every issue.
                  </p>
                </form>
              )}
            </div>

            {/* Decorative side: stack of "issues" */}
            <div style={{ position: "relative", height: 320 }} aria-hidden>
              <IssueCard offset={0}  label="Issue 12 · Autumn 2025" headline="Combi takeover" tone="warm"/>
              <IssueCard offset={1}  label="Issue 11 · Summer 2025" headline="Refrigeration, refined" tone="forest"/>
              <IssueCard offset={2}  label="Issue 10 · Spring 2025" headline="Bake stations" tone="cream"/>
            </div>
          </div>
        </div>
      </section>
    );
  }

  // ─── Workshop: brand-blue feature band ───
  return (
    <section style={{ marginTop: 48 }}>
      <div className="container">
        <div style={{
          background: "var(--brand-blue-700)", color: "#f3eadd",
          borderRadius: "var(--radius-lg)",
          padding: "48px 56px",
          display: "grid", gridTemplateColumns: "1fr 1.2fr", gap: 48, alignItems: "center",
        }}>
          <div>
            <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.12em", textTransform: "uppercase", color: "var(--warm-1)" }}>
              The Sheffield Quarterly
            </div>
            <h2 style={{
              fontFamily: "var(--font-heading)", color: "#f6ecd9", fontWeight: 400,
              fontSize: "clamp(28px, 3vw, 38px)", lineHeight: 1.1, marginTop: 10,
            }}>
              Catalog drops, project stories, trade-only offers — four times a year.
            </h2>
            <ul style={{ padding: 0, margin: "16px 0 0", listStyle: "none", display: "flex", gap: 18, fontSize: 12.5, color: "#c9bea4" }}>
              <li style={{ display: "flex", gap: 6 }}><IconCheck size={14} sw={2.2} stroke="var(--brand-500)"/> No spam</li>
              <li style={{ display: "flex", gap: 6 }}><IconCheck size={14} sw={2.2} stroke="var(--brand-500)"/> 1-click unsubscribe</li>
              <li style={{ display: "flex", gap: 6 }}><IconCheck size={14} sw={2.2} stroke="var(--brand-500)"/> 4,800+ trade subscribers</li>
            </ul>
          </div>

          <div>
            {submitted ? (
              <div style={{ background: "rgba(255,255,255,0.06)", padding: 24, borderRadius: "var(--radius)", display: "flex", gap: 14, alignItems: "center" }}>
                <div style={{ width: 44, height: 44, borderRadius: 999, background: "var(--accent)", color: "#fff", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                  <IconCheck size={22} sw={2.4}/>
                </div>
                <div>
                  <div style={{ fontFamily: "var(--font-heading)", fontSize: 20, color: "#f6ecd9" }}>You're on the list.</div>
                  <div style={{ fontSize: 13, color: "#c9bea4", marginTop: 4 }}>Confirmation sent to <strong style={{ color: "#f6ecd9" }}>{email}</strong>. Issue 13 lands in early September.</div>
                </div>
              </div>
            ) : (
              <form onSubmit={submit}>
                <div style={{ display: "flex", gap: 8 }}>
                  <input className="input" type="email" placeholder="you@kitchen.co.ke"
                    value={email} onChange={(e) => setEmail(e.target.value)}
                    style={{
                      height: 52, fontSize: 14.5, flex: 1,
                      background: "rgba(255,255,255,0.06)", color: "#f6ecd9",
                      border: "1px solid rgba(255,255,255,0.16)",
                    }}/>
                  <button type="submit" className="btn btn-primary btn-lg">Subscribe</button>
                </div>
                {error && <div style={{ marginTop: 8, fontSize: 12.5, color: "var(--warm-1)" }}>{error}</div>}

                <div style={{ marginTop: 14, display: "flex", flexWrap: "wrap", gap: 6 }}>
                  {[
                    { id: "new-products", label: "New products" },
                    { id: "seasonal-catalogs", label: "Catalogs" },
                    { id: "trade-pricing", label: "Trade offers" },
                    { id: "projects", label: "Projects" },
                  ].map(o => (
                    <InterestChipDark key={o.id} label={o.label}
                      active={interests.includes(o.id)}
                      onClick={() => toggleInterest(o.id)}/>
                  ))}
                </div>
                <p style={{ marginTop: 12, fontSize: 11, color: "#9c927c" }}>
                  By subscribing you agree to our <a href="#" onClick={(e) => e.preventDefault()} style={{ color: "#c9bea4" }}>Privacy Policy</a>.
                </p>
              </form>
            )}
          </div>
        </div>
      </div>
    </section>
  );
};

// ─── Bits ───
const InterestChip = ({ label, active, onClick }) => (
  <button type="button" onClick={onClick} style={{
    height: 28, padding: "0 12px", borderRadius: 999,
    background: active ? "var(--ink)" : "transparent",
    color: active ? "#fff" : "var(--ink-2)",
    border: "1px solid " + (active ? "var(--ink)" : "var(--line-strong)"),
    fontSize: 12, fontWeight: 500, cursor: "pointer",
    transition: "background 120ms ease, color 120ms ease, border-color 120ms ease",
    display: "inline-flex", alignItems: "center", gap: 6,
  }}>
    {active && <IconCheck size={11} sw={2.4} stroke="#fff"/>} {label}
  </button>
);

const InterestChipDark = ({ label, active, onClick }) => (
  <button type="button" onClick={onClick} style={{
    height: 26, padding: "0 12px", borderRadius: 999,
    background: active ? "var(--accent)" : "rgba(255,255,255,0.08)",
    color: active ? "#fff" : "#d8c79d",
    border: "1px solid " + (active ? "var(--accent)" : "rgba(255,255,255,0.14)"),
    fontSize: 11.5, fontWeight: 500, cursor: "pointer",
    transition: "background 120ms ease, color 120ms ease",
    display: "inline-flex", alignItems: "center", gap: 5,
  }}>
    {active && <IconCheck size={10} sw={2.6} stroke="#fff"/>} {label}
  </button>
);

const SuccessCard = ({ email }) => (
  <div style={{
    marginTop: 22, padding: 20,
    background: "var(--ink)", color: "#f3eadd",
    borderRadius: "var(--radius)", maxWidth: 460,
    display: "flex", gap: 14, alignItems: "start",
  }}>
    <div style={{ width: 40, height: 40, borderRadius: 999, background: "var(--accent)", color: "#fff", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
      <IconCheck size={20} sw={2.4}/>
    </div>
    <div>
      <div style={{ fontFamily: "var(--font-heading)", fontSize: 20, color: "#f6ecd9" }}>You're on the list.</div>
      <div style={{ fontSize: 13, color: "#c9bea4", marginTop: 6 }}>Confirmation sent to <strong style={{ color: "#f6ecd9" }}>{email}</strong>. Issue 13 lands in early September.</div>
    </div>
  </div>
);

const IssueCard = ({ offset, label, headline, tone }) => {
  const palette = {
    warm:   { bg: "#b87333", ink: "#fffaf0" },
    forest: { bg: "#2f3e2e", ink: "#e6ddc8" },
    cream:  { bg: "#efe1c1", ink: "#1c1a14" },
  }[tone] || { bg: "#1c1a14", ink: "#fff" };
  return (
    <div style={{
      position: "absolute",
      top: offset * 24, left: offset * 14,
      width: 240, height: 280,
      background: palette.bg, color: palette.ink,
      borderRadius: "var(--radius)",
      padding: 18,
      transform: `rotate(${offset * -2.4 - 4}deg)`,
      transformOrigin: "bottom left",
      boxShadow: "0 16px 32px -10px rgba(35,28,14,0.25)",
      display: "flex", flexDirection: "column", justifyContent: "space-between",
    }}>
      <div style={{ fontSize: 10.5, fontWeight: 600, letterSpacing: "0.08em", textTransform: "uppercase", opacity: 0.75 }}>
        {label}
      </div>
      <div>
        <div style={{ fontFamily: "var(--font-heading)", fontSize: 26, lineHeight: 1.05, fontWeight: 400 }}>{headline}</div>
        <div style={{ marginTop: 14, fontSize: 11.5, opacity: 0.7, display: "flex", justifyContent: "space-between" }}>
          <span>Sheffield Quarterly</span>
          <span>↗</span>
        </div>
      </div>
    </div>
  );
};

Object.assign(window, { NewsletterBand });
