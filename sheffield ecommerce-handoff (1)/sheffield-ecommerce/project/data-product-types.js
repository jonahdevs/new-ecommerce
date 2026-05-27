// Sheffield — sample products for the 6 product types: simple, variant, bundled, grouped, virtual, downloadable.

window.SHEFFIELD_TYPE_PRODUCTS = [
  // ───────── VARIANT (Configurable) ─────────
  {
    slug: "rational-icombi-pro",
    name: "Rational iCombi Pro",
    sku: "RAT-ICP",
    brand: "rational",
    category: "burners",
    kind: "combi-oven",
    type: "variant",
    tagline: "Electric combi-steamer with iCookingSuite intelligence. Configure for your kitchen volume and utility supply.",
    price: 1485000,
    compareAt: null,
    badge: "Configurable",
    rating: 4.9, reviews: 218,
    inStock: 8, leadTime: "Ships in 3–5 days",
    images: 5,
    warranty: "24 months parts & labour",
    origin: "Made in Landsberg, Germany",
    featured: false,
    variantAxes: [
      {
        id: "capacity", label: "Capacity",
        kind: "swatch",
        options: [
          { id: "6-1-1",  label: "6 × GN 1/1",   sub: "60–300 covers",   diff: 0 },
          { id: "10-1-1", label: "10 × GN 1/1",  sub: "100–500 covers",  diff: 320000 },
          { id: "20-1-1", label: "20 × GN 1/1",  sub: "300–1,000 covers", diff: 760000 },
        ],
      },
      {
        id: "fuel", label: "Fuel",
        kind: "chip",
        options: [
          { id: "electric", label: "Electric",  diff: 0 },
          { id: "gas",      label: "Gas",       diff: 45000 },
        ],
      },
      {
        id: "controls", label: "Controls",
        kind: "chip",
        options: [
          { id: "standard", label: "Standard panel",  diff: 0 },
          { id: "connected", label: "ConnectedCooking", sub: "WiFi + cloud HACCP", diff: 86000 },
        ],
      },
    ],
    specs: [
      { label: "Grid capacity",   value: "6 / 10 / 20 × GN 1/1" },
      { label: "Cooking modes",   value: "Steam, hot air, combination 30–300°C" },
      { label: "Water connection", value: "R 3/4″ cold + soft" },
      { label: "Cabinet",         value: "1.4301 stainless steel" },
      { label: "iCookingSuite",   value: "Yes — 7 cooking processes" },
      { label: "HACCP logging",   value: "Built-in, USB export" },
    ],
    tags: ["iCombi", "Bake", "Steam", "Grill"],
  },

  // ───────── BUNDLED (build your package) ─────────
  {
    slug: "starter-restaurant-bundle",
    name: "Sheffield Starter Restaurant Bundle",
    sku: "SHF-BUN-START",
    brand: "rational",
    category: "burners",
    kind: "combi-oven",
    type: "bundled",
    tagline: "A turnkey kitchen-line bundle. Pick your hot side, cold side and prep station — we deliver, install and train in one go.",
    price: 0, // calculated
    compareAt: null,
    badge: "Bundle · Save 8%",
    rating: 5.0, reviews: 12,
    inStock: 99, leadTime: "Ships in 5–7 days",
    images: 4,
    warranty: "Combined warranty on all line items",
    origin: "Assembled at Sheffield Nairobi",
    featured: false,
    bundleDiscount: 0.08,
    bundleSlots: [
      {
        id: "hot",  label: "Hot side",  required: true,
        helper: "Pick one combi-steamer or range",
        options: ["rational-icombi-pro-6-1e", "lacanche-cluny-1400-range", "rational-ivario-pro-l"],
      },
      {
        id: "cold", label: "Cold side", required: true,
        helper: "Pick a reach-in or blast chiller",
        options: ["true-t-49-2-door-refrigerator", "electrolux-blast-chiller-bce5"],
      },
      {
        id: "prep", label: "Preparation", required: true,
        helper: "Pick your primary prep machine",
        options: ["hobart-hl600-60qt-mixer", "robot-coupe-r502-processor", "hobart-edge-13-slicer"],
      },
      {
        id: "extra", label: "Bar & service", required: false,
        helper: "Optional — add a coffee, juice or display unit",
        options: ["true-tcgg-50-display-case", "robot-coupe-mp-450-blender"],
      },
    ],
    specs: [
      { label: "Sub-units",       value: "3–4 main items + smallwares" },
      { label: "Delivery window", value: "One coordinated delivery" },
      { label: "Installation",    value: "Single-day, included" },
      { label: "Training",        value: "Half-day brigade session" },
    ],
    tags: ["Bundle", "Turnkey"],
  },

  // ───────── GROUPED (kit of related items) ─────────
  {
    slug: "coffee-bar-essentials-kit",
    name: "Coffee Bar Essentials — 12-Piece Kit",
    sku: "SHF-KIT-CFE",
    brand: "victorinox",
    category: "coffee_machine",
    kind: "blender",
    type: "grouped",
    tagline: "The everyday consumables a 200-cover coffee bar runs through in a quarter. Buy the kit; tune the quantities to your throughput.",
    price: 0, // sum of selections
    compareAt: null,
    badge: "Kit",
    rating: 4.7, reviews: 87,
    inStock: 50, leadTime: "Ships same day",
    images: 3,
    warranty: "Manufacturer warranty per item",
    origin: "Multi-origin",
    featured: false,
    groupedItems: [
      { sku: "VIC-FIBROX-25",        name: "Victorinox Fibrox Pro 25 cm Chef's Knife", brand: "victorinox",  unit: "piece",      defaultQty: 2,  price: 8900,  kind: "knife" },
      { sku: "RC-MP450",             name: "Robot Coupe MP 450 Immersion Blender",     brand: "robot-coupe", unit: "piece",      defaultQty: 1,  price: 86500, kind: "blender" },
      { sku: "SHF-FILTER-58",        name: "Espresso Portafilter Filter Baskets — 58 mm", brand: "victorinox", unit: "set of 4",  defaultQty: 1,  price: 4200,  kind: "knife" },
      { sku: "SHF-TAMPER",           name: "Calibrated Tamper 58 mm — Stainless",       brand: "victorinox",  unit: "piece",      defaultQty: 1,  price: 2800,  kind: "knife" },
      { sku: "SHF-MILK-PITCH-600",   name: "Milk Pitcher 600 ml — Stainless",          brand: "victorinox",  unit: "piece",      defaultQty: 4,  price: 1900,  kind: "knife" },
      { sku: "SHF-BARISTA-CLOTH",    name: "Barista Microfibre Cloth — 5-pack",        brand: "victorinox",  unit: "pack",       defaultQty: 1,  price: 1450,  kind: "knife" },
      { sku: "SHF-CLEAN-CAFIZA",     name: "Cafiza Espresso Machine Cleaner",          brand: "robot-coupe", unit: "566 g jar",  defaultQty: 2,  price: 3200,  kind: "blender" },
      { sku: "SHF-CLEAN-GRINDZ",     name: "Grindz Grinder Cleaner",                   brand: "robot-coupe", unit: "430 g jar",  defaultQty: 1,  price: 2800,  kind: "blender" },
    ],
    specs: [
      { label: "Items in kit", value: "12 SKUs" },
      { label: "Recommended replenishment", value: "Quarterly for 200-cover bars" },
    ],
    tags: ["Kit", "Smallwares", "Coffee"],
  },

  // ───────── VIRTUAL (service / subscription) ─────────
  {
    slug: "preventive-service-contract",
    name: "Preventive Service Contract",
    sku: "SHF-SVC-PREV",
    brand: "rational",
    category: "cleaning_solutions",
    kind: "dishwasher",
    type: "virtual",
    tagline: "Annual preventive maintenance contract. Quarterly engineer visits, parts discount and a 48-hour response SLA — across your whole fleet.",
    price: 0, // tier-based
    compareAt: null,
    badge: "Service",
    rating: 4.9, reviews: 156,
    inStock: 999, leadTime: "Activates within 48 hours",
    images: 1,
    warranty: "Contract guaranteed for full term",
    origin: "Delivered by Sheffield Engineering",
    featured: false,
    serviceTiers: [
      {
        id: "essential",
        name: "Essential",
        annualPrice: 24000,
        unit: "per unit / year",
        sla: "72-hour response",
        visits: 2,
        features: ["2 preventive visits / year", "10% spares discount", "Phone & email support"],
      },
      {
        id: "premium",
        name: "Premium",
        annualPrice: 48000,
        unit: "per unit / year",
        sla: "48-hour response",
        visits: 4,
        features: ["4 preventive visits / year", "15% spares discount", "Priority phone line", "HACCP audit support", "Annual training refresh"],
        recommended: true,
      },
      {
        id: "operations",
        name: "Operations",
        annualPrice: 78000,
        unit: "per unit / year",
        sla: "24-hour response",
        visits: 6,
        features: ["6 preventive visits / year", "25% spares discount", "Dedicated account engineer", "Loan unit during major repairs", "Multi-site rollup billing"],
      },
    ],
    specs: [
      { label: "Coverage", value: "All Sheffield-supplied equipment" },
      { label: "Visit type", value: "On-site, factory-trained engineer" },
      { label: "Spares", value: "Discount on genuine parts" },
      { label: "Reporting", value: "Quarterly service report, HACCP-ready" },
    ],
    tags: ["Service", "Contract"],
  },

  // ───────── DOWNLOADABLE ─────────
  {
    slug: "haccp-cleaning-library",
    name: "HACCP & Cleaning Procedures Library",
    sku: "SHF-DL-HACCP",
    brand: "rational",
    category: "cleaning_solutions",
    kind: "knife",
    type: "downloadable",
    tagline: "A complete pack of HACCP-ready cleaning, sanitation and maintenance procedures for your kitchen — printable, editable, and ready for KEBS audits.",
    price: 14500,
    compareAt: 18000,
    badge: "Digital",
    rating: 4.8, reviews: 240,
    inStock: 999, leadTime: "Instant download after purchase",
    images: 1,
    warranty: "Free updates for 12 months",
    origin: "Compiled by Sheffield Food-Safety team",
    featured: false,
    downloadFiles: [
      { name: "01 — Daily kitchen cleaning checklist",    pages: 4,   size: "320 KB", format: "PDF" },
      { name: "02 — Weekly deep-clean schedule",          pages: 8,   size: "640 KB", format: "PDF" },
      { name: "03 — Monthly equipment maintenance log",   pages: 6,   size: "480 KB", format: "PDF" },
      { name: "04 — Combi oven HACCP record",             pages: 12,  size: "1.1 MB", format: "PDF" },
      { name: "05 — Cold chain temperature log",          pages: 24,  size: "1.8 MB", format: "XLSX" },
      { name: "06 — Cleaning chemical safety sheets",     pages: 48,  size: "3.4 MB", format: "PDF" },
      { name: "07 — Kitchen briefing posters (A3)",       pages: 12,  size: "8.2 MB", format: "PDF" },
      { name: "08 — Editable templates (Word)",           pages: 96,  size: "2.8 MB", format: "DOCX" },
    ],
    licenseTerms: "Single-site licence. Unlimited internal copies for your team. Free updates for 12 months from purchase.",
    specs: [
      { label: "Format",  value: "PDF, XLSX & DOCX bundle" },
      { label: "Pages",   value: "210 pages, 8 documents" },
      { label: "Language", value: "English (Swahili coming Q3)" },
      { label: "Editable", value: "Yes — DOCX & XLSX masters included" },
      { label: "Audit-ready", value: "Aligned with KEBS, NEMA & ServSafe" },
    ],
    tags: ["HACCP", "Documents", "Downloadable"],
  },
];

// Merge into main products list (do this once, idempotent)
(function () {
  const D = window.SHEFFIELD_DATA;
  if (!D) return;
  const existing = new Set(D.products.map(p => p.slug));
  for (const p of window.SHEFFIELD_TYPE_PRODUCTS) {
    if (!existing.has(p.slug)) D.products.push(p);
  }
})();
