// Sheffield Admin — derived mock data: orders, KPIs, revenue series.
// Builds on window.SHEFFIELD_DATA (data.js must load first).

window.SHEFFIELD_ADMIN = (function () {
  const D = window.SHEFFIELD_DATA;
  const P = D.products;

  // ───────── Customers (East African hospitality businesses) ─────────
  const customers = [
    { name: "Sankara Nairobi",          type: "Hotel",      city: "Nairobi" },
    { name: "Artcaffé Group",            type: "Restaurant", city: "Nairobi" },
    { name: "Serena Hotels",             type: "Hotel",      city: "Nairobi" },
    { name: "Java House Africa",         type: "Café",       city: "Nairobi" },
    { name: "Tamarind Collection",       type: "Restaurant", city: "Mombasa" },
    { name: "Radisson Blu Upper Hill",   type: "Hotel",      city: "Nairobi" },
    { name: "Talisman Karen",            type: "Restaurant", city: "Nairobi" },
    { name: "Kempinski Villa Rosa",      type: "Hotel",      city: "Nairobi" },
    { name: "Cultiva Naivasha",          type: "Restaurant", city: "Naivasha" },
    { name: "Big Square Kitchens",       type: "QSR",        city: "Nairobi" },
    { name: "Pride Inn Azure",           type: "Hotel",      city: "Nairobi" },
    { name: "Mawimbi Seafood",           type: "Restaurant", city: "Mombasa" },
    { name: "About Thyme",               type: "Restaurant", city: "Nairobi" },
    { name: "Trademark Hotel",           type: "Hotel",      city: "Nairobi" },
  ];

  // ───────── Orders ─────────
  // Deterministic generation so numbers are stable across reloads.
  const STATUSES = ["paid", "processing", "shipped", "made-to-order", "cancelled"];
  const statusMeta = {
    "paid":          { label: "Paid",          tone: "green"  },
    "processing":    { label: "Processing",    tone: "amber"  },
    "shipped":       { label: "Shipped",       tone: "blue"   },
    "made-to-order": { label: "Made to order", tone: "violet" },
    "cancelled":     { label: "Cancelled",     tone: "red"    },
  };

  // simple seeded pseudo-random
  let seed = 20260529;
  const rnd = () => { seed = (seed * 1103515245 + 12345) & 0x7fffffff; return seed / 0x7fffffff; };
  const pick = (arr) => arr[Math.floor(rnd() * arr.length)];

  const today = new Date(2026, 4, 29); // May 29 2026
  const orders = [];
  const ORDER_COUNT = 32;
  for (let i = 0; i < ORDER_COUNT; i++) {
    const daysAgo = Math.floor(rnd() * 60);
    const date = new Date(today); date.setDate(today.getDate() - daysAgo);
    const lineCount = 1 + Math.floor(rnd() * 3);
    const lines = [];
    let subtotal = 0;
    for (let l = 0; l < lineCount; l++) {
      const prod = pick(P);
      const qty = 1 + Math.floor(rnd() * 3);
      const unit = prod.price;
      subtotal += unit * qty;
      lines.push({ slug: prod.slug, name: prod.name, qty, unit });
    }
    const cust = pick(customers);
    // status weighted by recency / made-to-order products
    let status;
    const hasMTO = lines.some(ln => { const p = P.find(x => x.slug === ln.slug); return p && p.inStock === 0; });
    if (hasMTO && rnd() > 0.4) status = "made-to-order";
    else if (daysAgo < 4) status = pick(["paid", "processing", "processing"]);
    else if (daysAgo < 14) status = pick(["shipped", "paid", "processing"]);
    else status = pick(["shipped", "shipped", "paid", "cancelled"]);

    orders.push({
      id: "SHF-" + (10428 - i),
      customer: cust.name,
      customerType: cust.type,
      city: cust.city,
      date,
      status,
      lines,
      items: lines.reduce((s, l) => s + l.qty, 0),
      total: subtotal,
    });
  }
  orders.sort((a, b) => b.date - a.date);

  // ───────── KPIs ─────────
  const inWindow = (o, days) => (today - o.date) / 86400000 <= days;
  const valid = orders.filter(o => o.status !== "cancelled");

  const revenue30 = valid.filter(o => inWindow(o, 30)).reduce((s, o) => s + o.total, 0);
  const revenuePrev30 = valid.filter(o => !inWindow(o, 30) && inWindow(o, 60)).reduce((s, o) => s + o.total, 0);
  const orders30 = orders.filter(o => inWindow(o, 30)).length;
  const ordersPrev30 = orders.filter(o => !inWindow(o, 30) && inWindow(o, 60)).length;

  const inventoryValue = P.reduce((s, p) => s + p.price * p.inStock, 0);
  const lowStock = P.filter(p => p.inStock > 0 && p.inStock <= 5);
  const outOfStock = P.filter(p => p.inStock === 0);

  const pct = (cur, prev) => prev === 0 ? 100 : Math.round(((cur - prev) / prev) * 100);

  // ───────── Revenue series (last 8 months) ─────────
  const monthNames = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  const revenueSeries = [];
  const baseVals = [4.2, 5.1, 4.8, 6.3, 5.9, 7.1, 6.8, 8.4]; // millions KES, hand-tuned shape
  for (let m = 0; m < 8; m++) {
    const d = new Date(today); d.setMonth(today.getMonth() - (7 - m));
    revenueSeries.push({
      label: monthNames[d.getMonth()],
      value: Math.round(baseVals[m] * 1000000),
    });
  }

  // ───────── Orders by status (last 30d) ─────────
  const statusCounts = {};
  STATUSES.forEach(s => statusCounts[s] = 0);
  orders.filter(o => inWindow(o, 30)).forEach(o => statusCounts[o.status]++);

  // ───────── Top selling (by units in valid orders) ─────────
  const unitsBySlug = {};
  valid.forEach(o => o.lines.forEach(ln => { unitsBySlug[ln.slug] = (unitsBySlug[ln.slug] || 0) + ln.qty; }));
  const topSelling = Object.entries(unitsBySlug)
    .map(([slug, units]) => {
      const p = P.find(x => x.slug === slug);
      return { product: p, units, revenue: units * p.price };
    })
    .sort((a, b) => b.revenue - a.revenue)
    .slice(0, 5);

  return {
    customers, orders, statusMeta, STATUSES,
    kpis: {
      revenue30, revenueDelta: pct(revenue30, revenuePrev30),
      orders30, ordersDelta: pct(orders30, ordersPrev30),
      inventoryValue,
      lowStockCount: lowStock.length,
      outOfStockCount: outOfStock.length,
      avgOrder: Math.round(revenue30 / Math.max(1, orders30)),
    },
    lowStock, outOfStock,
    revenueSeries, statusCounts, topSelling,
    today,
  };
})();
