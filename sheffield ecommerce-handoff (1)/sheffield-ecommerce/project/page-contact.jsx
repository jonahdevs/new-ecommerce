// Sheffield — Contact page. Direction-aware (editorial / workshop).
// Uses shared tokens, .input/.select/.btn classes, SHEFFIELD_LOCATIONS, RegionMap.

const CONTACT_CHANNELS = [
  {
    key: "sales",
    icon: IconCart,
    title: "Sales & quotes",
    desc: "Spec a new kitchen, price a fit-out, or convert a basket into a formal quote.",
    lines: [
      { icon: IconPhone, label: "+254 20 234 5600" },
      { icon: IconMail, label: "sales@sheffield.co.ke" },
    ],
    sla: "Same-day response",
  },
  {
    key: "service",
    icon: IconWrench,
    title: "Service & spares",
    desc: "Breakdowns, preventive maintenance and genuine parts for equipment in the field.",
    lines: [
      { icon: IconPhone, label: "+254 20 234 5612" },
      { icon: IconMail, label: "service@sheffield.co.ke" },
    ],
    sla: "48-hr response SLA",
  },
  {
    key: "trade",
    icon: IconCertified,
    title: "Trade accounts",
    desc: "Business pricing, Net 30 terms, multi-site ordering and a dedicated specialist.",
    lines: [
      { icon: IconPhone, label: "+254 20 234 5620" },
      { icon: IconMail, label: "trade@sheffield.co.ke" },
    ],
    sla: "Approval in 2 business days",
  },
  {
    key: "projects",
    icon: IconDocument,
    title: "Project consultation",
    desc: "Full-kitchen design, ventilation, electrical load and installation planning.",
    lines: [
      { icon: IconPhone, label: "+254 711 234 590" },
      { icon: IconMail, label: "projects@sheffield.co.ke" },
    ],
    sla: "Book a site visit",
  },
];

const INQUIRY_TYPES = [
  "Sales enquiry",
  "Request a quote",
  "Service & spares",
  "Installation",
  "Trade account",
  "Project consultation",
];

// ───────── Small field helpers ─────────
const Field = ({ label, required, hint, children }) => (
  <label style={{ display: "block" }}>
    <span style={{ display: "flex", alignItems: "baseline", justifyContent: "space-between", marginBottom: 7 }}>
      <span style={{ fontSize: 13, fontWeight: 600, color: "var(--ink-2)" }}>
        {label}{required && <span style={{ color: "var(--accent)", marginLeft: 3 }}>*</span>}
      </span>
      {hint && <span style={{ fontSize: 11.5, color: "var(--ink-4)" }}>{hint}</span>}
    </span>
    {children}
  </label>
);

const ContactPage = ({ navigate, params }) => {
  const direction = document.documentElement.getAttribute("data-direction") || "editorial";
  const isWorkshop = direction === "workshop";
  const locations = window.SHEFFIELD_LOCATIONS;

  const [form, setForm] = React.useState({
    inquiry: params.inquiry || "Sales enquiry",
    name: "", business: "", email: "", phone: "",
    location: "nairobi", message: "", consent: false,
  });
  const [sent, setSent] = React.useState(false);
  const [errors, setErrors] = React.useState({});
  const [activeLoc, setActiveLoc] = React.useState("nairobi");
  const loc = locations.find(l => l.slug === activeLoc);

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const submit = (e) => {
    e.preventDefault();
    const errs = {};
    if (!form.name.trim()) errs.name = true;
    if (!/.+@.+\..+/.test(form.email)) errs.email = true;
    if (!form.message.trim()) errs.message = true;
    if (!form.consent) errs.consent = true;
    setErrors(errs);
    if (Object.keys(errs).length === 0) {
      setSent(true);
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  };

  const ref = "SHF-" + Math.floor(100000 + Math.random() * 900000);

  return (
    <div className="page-fade">

      {/* ───────── Masthead ───────── */}
      <section style={{ background: "var(--bg-sunken)", borderBottom: "1px solid var(--line)" }}>
        <div className="container" style={{ paddingTop: isWorkshop ? 44 : 64, paddingBottom: isWorkshop ? 44 : 64 }}>
          <div style={{ display: "grid", gridTemplateColumns: "1.25fr 0.9fr", gap: 48, alignItems: "stretch" }}>
            {/* Left: copy */}
            <div style={{ alignSelf: "center" }}>
              <div style={{ display: "flex", alignItems: "center", gap: 8, fontSize: 12.5, color: "var(--ink-3)", marginBottom: 14 }}>
                <a href="#" onClick={(e) => { e.preventDefault(); navigate("home"); }} style={{ color: "var(--ink-3)" }}>Home</a>
                <IconChevronR size={13} sw={1.6} style={{ color: "var(--ink-4)" }}/>
                <span style={{ color: "var(--ink-2)" }}>Contact</span>
              </div>
              <span className="kicker">We're here to help</span>
              <h1 style={{
                fontSize: isWorkshop ? 40 : 50, lineHeight: 1.04, marginTop: 12,
                letterSpacing: "-0.02em",
              }}>
                Talk to a kitchen<br/>
                equipment <span style={{ color: "var(--accent)", fontStyle: isWorkshop ? "normal" : "italic" }}>specialist</span>.
              </h1>
              <p style={{ marginTop: 18, fontSize: 16, lineHeight: 1.6, color: "var(--ink-2)", maxWidth: 520 }}>
                Sizing, electrical load, ventilation or installation — get it right before you commit.
                Reach our team by phone, WhatsApp or the form below, or walk into any of our four showrooms.
              </p>
              <div style={{ marginTop: 26, display: "flex", gap: 28, flexWrap: "wrap" }}>
                {[
                  { icon: IconChat, k: "Avg. first reply", v: "Under 2 hours" },
                  { icon: IconLocation, k: "Showrooms", v: "4 across East Africa" },
                  { icon: IconShield, k: "Service SLA", v: "48-hour response" },
                ].map((s, i) => (
                  <div key={i} style={{ display: "flex", gap: 11, alignItems: "center" }}>
                    <span style={{
                      width: 38, height: 38, borderRadius: isWorkshop ? 5 : 10, flexShrink: 0,
                      background: "var(--surface)", border: "1px solid var(--line)",
                      display: "flex", alignItems: "center", justifyContent: "center", color: "var(--secondary)",
                    }}>
                      <s.icon size={18} sw={1.6}/>
                    </span>
                    <div>
                      <div style={{ fontSize: 11.5, color: "var(--ink-3)" }}>{s.k}</div>
                      <div style={{ fontSize: 14, fontWeight: 600, color: "var(--ink)" }}>{s.v}</div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Right: prominent call card */}
            <div style={{
              background: "var(--brand-blue-700)", color: "#f3eadd",
              borderRadius: "var(--radius-lg)", padding: 32,
              display: "flex", flexDirection: "column", justifyContent: "center",
              position: "relative", overflow: "hidden",
            }}>
              <div style={{
                position: "absolute", inset: 0,
                backgroundImage: "radial-gradient(rgba(255,255,255,0.05) 1px, transparent 1px)",
                backgroundSize: "16px 16px", pointerEvents: "none",
              }}/>
              <div style={{ position: "relative" }}>
                <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.12em", textTransform: "uppercase", color: "var(--warm-1)" }}>
                  Sales & quotes hotline
                </div>
                <a href="tel:+254202345600" style={{
                  display: "block", marginTop: 12, fontFamily: "var(--font-heading)",
                  fontSize: 32, color: "#fff", letterSpacing: "-0.01em",
                }}>
                  +254 20 234 5600
                </a>
                <div style={{ fontSize: 13, color: "#c9bea4", marginTop: 6 }}>Mon–Fri 8:00–17:30 · Sat 9:00–14:00 (EAT)</div>

                <div style={{ height: 1, background: "rgba(255,255,255,0.14)", margin: "22px 0" }}/>

                <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
                  <a href="#" onClick={(e) => e.preventDefault()} className="btn" style={{
                    background: "#25D366", color: "#0b2a16", fontWeight: 600, justifyContent: "center", width: "100%",
                  }}>
                    <IconChat size={17} sw={1.7}/> WhatsApp +254 711 234 567
                  </a>
                  <a href="mailto:sales@sheffield.co.ke" className="btn" style={{
                    background: "rgba(255,255,255,0.1)", color: "#f6ecd9",
                    border: "1px solid rgba(255,255,255,0.18)", justifyContent: "center", width: "100%",
                  }}>
                    <IconMail size={17} sw={1.6}/> sales@sheffield.co.ke
                  </a>
                </div>

                <div style={{ marginTop: 18, display: "flex", alignItems: "center", gap: 8, fontSize: 12.5, color: "#d8c79d" }}>
                  <IconCertified size={15} sw={1.6} style={{ color: "var(--warm-1)" }}/>
                  Trade account? <a href="#" onClick={(e) => { e.preventDefault(); navigate("register", { trade: true }); }} style={{ color: "#fff", textDecoration: "underline", textUnderlineOffset: 2 }}>Apply for Net 30 →</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ───────── Channel cards ───────── */}
      <section style={{ paddingTop: isWorkshop ? 48 : 72 }}>
        <div className="container">
          <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: isWorkshop ? 14 : 18 }}>
            {CONTACT_CHANNELS.map(ch => (
              <div key={ch.key} style={{
                background: "var(--surface)", border: "1px solid var(--line)",
                borderRadius: "var(--radius-lg)", padding: 22,
                display: "flex", flexDirection: "column", boxShadow: "var(--shadow-sm)",
              }}>
                <span style={{
                  width: 44, height: 44, borderRadius: isWorkshop ? 6 : 11,
                  background: "var(--tag-bg)", color: "var(--secondary)",
                  display: "flex", alignItems: "center", justifyContent: "center",
                }}>
                  <ch.icon size={22} sw={1.6}/>
                </span>
                <div style={{ fontFamily: "var(--font-heading)", fontSize: 19, marginTop: 16 }}>{ch.title}</div>
                <p style={{ fontSize: 13, color: "var(--ink-3)", lineHeight: 1.5, marginTop: 7, marginBottom: 16, flex: 1 }}>{ch.desc}</p>
                <div style={{ display: "flex", flexDirection: "column", gap: 7 }}>
                  {ch.lines.map((ln, i) => (
                    <span key={i} style={{ display: "inline-flex", alignItems: "center", gap: 8, fontSize: 13, color: "var(--ink-2)" }}>
                      <ln.icon size={14} sw={1.6} style={{ color: "var(--ink-4)", flexShrink: 0 }}/> {ln.label}
                    </span>
                  ))}
                </div>
                <div style={{
                  marginTop: 14, paddingTop: 12, borderTop: "1px solid var(--line)",
                  fontSize: 11.5, fontWeight: 600, letterSpacing: "0.04em", textTransform: "uppercase",
                  color: "var(--secondary)", display: "inline-flex", alignItems: "center", gap: 6,
                }}>
                  <IconClock size={13} sw={1.7}/> {ch.sla}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ───────── Form + sidebar ───────── */}
      <section style={{ paddingTop: isWorkshop ? 48 : 72 }}>
        <div className="container">
          <div style={{ display: "grid", gridTemplateColumns: "1.5fr 0.85fr", gap: 40, alignItems: "start" }}>

            {/* Form card */}
            <div style={{
              background: "var(--surface)", border: "1px solid var(--line)",
              borderRadius: "var(--radius-lg)", padding: 32, boxShadow: "var(--shadow-sm)",
            }}>
              {sent ? (
                <div style={{ padding: "20px 0", textAlign: "center" }}>
                  <div style={{
                    width: 64, height: 64, borderRadius: 999, margin: "0 auto 18px",
                    background: "hsl(145 50% 92%)", color: "hsl(150 60% 32%)",
                    display: "flex", alignItems: "center", justifyContent: "center",
                  }}>
                    <IconCheck size={30} sw={2}/>
                  </div>
                  <h2 style={{ fontSize: 26 }}>Message received</h2>
                  <p style={{ fontSize: 15, color: "var(--ink-2)", maxWidth: 440, margin: "12px auto 0", lineHeight: 1.6 }}>
                    Thanks, {form.name.split(" ")[0] || "there"}. A Sheffield specialist will be in touch within
                    {" "}<strong style={{ color: "var(--ink)" }}>2 working hours</strong>. We've sent a copy to {form.email}.
                  </p>
                  <div style={{
                    display: "inline-flex", alignItems: "center", gap: 8, marginTop: 20,
                    padding: "8px 14px", background: "var(--bg-sunken)", borderRadius: 999,
                    fontSize: 13, color: "var(--ink-2)",
                  }}>
                    <span style={{ color: "var(--ink-3)" }}>Reference</span>
                    <strong style={{ fontFamily: "var(--font-mono)", letterSpacing: "0.02em" }}>{ref}</strong>
                  </div>
                  <div style={{ marginTop: 24, display: "flex", gap: 10, justifyContent: "center" }}>
                    <button className="btn btn-primary" onClick={() => navigate("catalog")}>Browse the catalog <IconArrow size={15} sw={2}/></button>
                    <button className="btn btn-outline" onClick={() => { setSent(false); setForm(f => ({ ...f, message: "", consent: false })); }}>Send another</button>
                  </div>
                </div>
              ) : (
                <form onSubmit={submit}>
                  <h2 style={{ fontSize: isWorkshop ? 24 : 28 }}>Send us a message</h2>
                  <p style={{ fontSize: 14, color: "var(--ink-3)", marginTop: 6, marginBottom: 24 }}>
                    Tell us what you're working on. The more detail, the faster we can help.
                  </p>

                  {/* Inquiry type chips */}
                  <div style={{ marginBottom: 22 }}>
                    <span style={{ fontSize: 13, fontWeight: 600, color: "var(--ink-2)", display: "block", marginBottom: 10 }}>What's this about?</span>
                    <div style={{ display: "flex", flexWrap: "wrap", gap: 8 }}>
                      {INQUIRY_TYPES.map(t => {
                        const on = form.inquiry === t;
                        return (
                          <button type="button" key={t} onClick={() => set("inquiry", t)} style={{
                            height: 36, padding: "0 14px", fontSize: 13, fontWeight: 500,
                            borderRadius: isWorkshop ? 4 : 999,
                            border: "1px solid " + (on ? "var(--accent)" : "var(--line-strong)"),
                            background: on ? "var(--accent)" : "var(--surface)",
                            color: on ? "var(--accent-ink)" : "var(--ink-2)",
                            transition: "all 120ms ease",
                          }}>{t}</button>
                        );
                      })}
                    </div>
                  </div>

                  <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 18 }}>
                    <Field label="Full name" required>
                      <input className="input" value={form.name} onChange={e => set("name", e.target.value)}
                        placeholder="Jane Mwangi"
                        style={errors.name ? { borderColor: "var(--accent)" } : null}/>
                    </Field>
                    <Field label="Business name" hint="Optional">
                      <input className="input" value={form.business} onChange={e => set("business", e.target.value)}
                        placeholder="e.g. Artcaffé Group"/>
                    </Field>
                    <Field label="Email" required>
                      <input className="input" type="email" value={form.email} onChange={e => set("email", e.target.value)}
                        placeholder="jane@business.co.ke"
                        style={errors.email ? { borderColor: "var(--accent)" } : null}/>
                    </Field>
                    <Field label="Phone" hint="Optional">
                      <input className="input" value={form.phone} onChange={e => set("phone", e.target.value)}
                        placeholder="+254 7…"/>
                    </Field>
                  </div>

                  <div style={{ marginTop: 18 }}>
                    <Field label="Nearest showroom">
                      <select className="select" value={form.location} onChange={e => set("location", e.target.value)}>
                        {locations.map(l => (
                          <option key={l.slug} value={l.slug}>{l.city}, {l.country}{l.isHQ ? " (HQ)" : ""}</option>
                        ))}
                      </select>
                    </Field>
                  </div>

                  <div style={{ marginTop: 18 }}>
                    <Field label="How can we help?" required>
                      <textarea value={form.message} onChange={e => set("message", e.target.value)}
                        rows={5} placeholder="Tell us about the equipment, kitchen or project — quantities, timelines and any constraints."
                        style={{
                          width: "100%", padding: "12px 14px", background: "var(--surface)",
                          border: "1px solid " + (errors.message ? "var(--accent)" : "var(--line)"),
                          borderRadius: "var(--radius)", color: "var(--ink)", fontSize: 14,
                          lineHeight: 1.55, resize: "vertical", outline: "none", fontFamily: "var(--font-body)",
                        }}/>
                    </Field>
                  </div>

                  <label style={{ display: "flex", gap: 11, alignItems: "flex-start", marginTop: 18, cursor: "pointer" }}>
                    <input type="checkbox" checked={form.consent} onChange={e => set("consent", e.target.checked)}
                      style={{ width: 18, height: 18, marginTop: 2, accentColor: "var(--accent)", flexShrink: 0 }}/>
                    <span style={{ fontSize: 13, color: errors.consent ? "var(--accent)" : "var(--ink-3)", lineHeight: 1.5 }}>
                      I agree to Sheffield contacting me about this enquiry and accept the{" "}
                      <a href="#" onClick={e => e.preventDefault()} style={{ color: "var(--secondary)", textDecoration: "underline" }}>privacy policy</a>.
                    </span>
                  </label>

                  <div style={{ marginTop: 24, display: "flex", alignItems: "center", gap: 14, flexWrap: "wrap" }}>
                    <button type="submit" className="btn btn-primary btn-lg">Send message <IconArrow size={16} sw={2}/></button>
                    <span style={{ fontSize: 12.5, color: "var(--ink-4)", display: "inline-flex", alignItems: "center", gap: 6 }}>
                      <IconShield size={14} sw={1.6}/> We reply within 2 working hours
                    </span>
                  </div>
                </form>
              )}
            </div>

            {/* Sidebar: what happens next + hours */}
            <div style={{ display: "flex", flexDirection: "column", gap: 18 }}>
              <div style={{
                background: "var(--surface)", border: "1px solid var(--line)",
                borderRadius: "var(--radius-lg)", padding: 24, boxShadow: "var(--shadow-sm)",
              }}>
                <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.1em", textTransform: "uppercase", color: "var(--ink-3)" }}>
                  What happens next
                </div>
                <div style={{ marginTop: 16, display: "flex", flexDirection: "column", gap: 16 }}>
                  {[
                    { t: "We read & route it", d: "Your enquiry reaches the right specialist — sales, service or projects." },
                    { t: "A specialist replies", d: "Usually within 2 working hours, by your preferred channel." },
                    { t: "We scope it together", d: "Sizing, quotes, a showroom visit or a site survey as needed." },
                  ].map((s, i) => (
                    <div key={i} style={{ display: "flex", gap: 13 }}>
                      <span style={{
                        width: 26, height: 26, borderRadius: 999, flexShrink: 0,
                        background: "var(--tag-bg)", color: "var(--secondary)",
                        display: "flex", alignItems: "center", justifyContent: "center",
                        fontSize: 13, fontWeight: 700, fontFamily: "var(--font-heading)",
                      }}>{i + 1}</span>
                      <div>
                        <div style={{ fontSize: 14, fontWeight: 600, color: "var(--ink)" }}>{s.t}</div>
                        <div style={{ fontSize: 12.5, color: "var(--ink-3)", lineHeight: 1.5, marginTop: 2 }}>{s.d}</div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              <div style={{
                background: "var(--warm-3)", color: "#e6ddc8",
                borderRadius: "var(--radius-lg)", padding: 24,
              }}>
                <div style={{ fontSize: 11.5, fontWeight: 700, letterSpacing: "0.1em", textTransform: "uppercase", color: "#d8c79d" }}>
                  Head office hours
                </div>
                <div style={{ marginTop: 14, display: "flex", flexDirection: "column", gap: 9, fontSize: 13.5 }}>
                  {[["Mon – Fri", "8:00 – 17:30"], ["Saturday", "9:00 – 14:00"], ["Sunday", "Closed"]].map(([d, h], i) => (
                    <div key={i} style={{ display: "flex", justifyContent: "space-between", color: "#c9bea4" }}>
                      <span>{d}</span><span style={{ color: "#f3eadd", fontWeight: 500 }}>{h}</span>
                    </div>
                  ))}
                </div>
                <div style={{ height: 1, background: "rgba(230,221,200,0.16)", margin: "16px 0" }}/>
                <div style={{ fontSize: 12.5, color: "#c9bea4", lineHeight: 1.55 }}>
                  All times East Africa (EAT, GMT+3). Emergency service line operates 24/7 for active contract holders.
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* ───────── Showrooms ───────── */}
      <section style={{ paddingTop: isWorkshop ? 56 : 88 }}>
        <div className="container">
          <div style={{ display: "flex", alignItems: "flex-end", justifyContent: "space-between", gap: 20, marginBottom: 24, flexWrap: "wrap" }}>
            <div>
              <span className="kicker">Walk in & see it working</span>
              <h2 style={{ fontSize: isWorkshop ? 30 : 36, marginTop: 10 }}>Visit a Sheffield showroom</h2>
            </div>
            <p style={{ fontSize: 14, color: "var(--ink-3)", maxWidth: 380, lineHeight: 1.55 }}>
              Equipment on the floor for hands-on demos, spares in stock and engineers on call across four cities.
            </p>
          </div>

          <div style={{
            border: "1px solid var(--line)", borderRadius: "var(--radius-lg)", overflow: "hidden",
            display: "grid", gridTemplateColumns: "1fr 1.15fr", minHeight: 440, background: "var(--surface)",
          }}>
            {/* Map */}
            <div style={{ position: "relative", minHeight: 440 }}>
              <ShowroomMap activeSlug={activeLoc} onPick={setActiveLoc}/>
            </div>

            {/* Detail */}
            <div style={{ padding: 32, display: "flex", flexDirection: "column" }}>
              <div style={{ display: "flex", gap: 6, flexWrap: "wrap", borderBottom: "1px solid var(--line)", paddingBottom: 14 }}>
                {locations.map(l => {
                  const on = l.slug === activeLoc;
                  return (
                    <button key={l.slug} onClick={() => setActiveLoc(l.slug)} style={{
                      height: 34, padding: "0 13px", fontSize: 13, fontWeight: on ? 600 : 500,
                      borderRadius: isWorkshop ? 4 : 999,
                      border: "1px solid " + (on ? "var(--ink)" : "var(--line)"),
                      background: on ? "var(--ink)" : "var(--surface)",
                      color: on ? "#fff" : "var(--ink-2)",
                      display: "inline-flex", alignItems: "center", gap: 6,
                    }}>
                      {l.city}
                      {l.isHQ && <span style={{ fontSize: 9, padding: "1px 5px", background: on ? "var(--accent)" : "var(--tag-bg)", color: on ? "#fff" : "var(--ink-3)", borderRadius: 3, letterSpacing: "0.06em" }}>HQ</span>}
                    </button>
                  );
                })}
              </div>

              <div style={{ marginTop: 20, flex: 1 }}>
                <div style={{ fontFamily: "var(--font-heading)", fontSize: 24 }}>{loc.address}</div>
                <div style={{ fontSize: 13.5, color: "var(--ink-3)", marginTop: 5 }}>
                  {loc.suburb} · {loc.city}, {loc.country} · {loc.postcode}
                </div>

                <div style={{ marginTop: 20, display: "grid", gridTemplateColumns: "1fr 1fr", gap: "14px 20px" }}>
                  {[
                    { icon: IconPhone, label: "Phone", value: loc.phone },
                    { icon: IconChat, label: "WhatsApp", value: loc.whatsapp },
                    { icon: IconMail, label: "Email", value: loc.email },
                    { icon: IconClock, label: "Hours today", value: loc.hours.weekday.replace("Mon–Fri · ", "") },
                  ].map((row, i) => (
                    <div key={i} style={{ display: "flex", gap: 11, alignItems: "flex-start" }}>
                      <span style={{ color: "var(--secondary)", marginTop: 2 }}><row.icon size={16} sw={1.6}/></span>
                      <div>
                        <div style={{ fontSize: 11.5, color: "var(--ink-4)" }}>{row.label}</div>
                        <div style={{ fontSize: 13.5, color: "var(--ink)", fontWeight: 500 }}>{row.value}</div>
                      </div>
                    </div>
                  ))}
                </div>

                <div style={{ marginTop: 20, display: "flex", flexWrap: "wrap", gap: 7 }}>
                  {loc.services.map(s => (
                    <span key={s} className="chip">{s}</span>
                  ))}
                </div>
              </div>

              <div style={{ marginTop: 22, display: "flex", gap: 10 }}>
                <button className="btn btn-primary">Get directions <IconArrow size={15} sw={2}/></button>
                <button className="btn btn-outline">Book a showroom visit</button>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
  );
};

Object.assign(window, { ContactPage });
