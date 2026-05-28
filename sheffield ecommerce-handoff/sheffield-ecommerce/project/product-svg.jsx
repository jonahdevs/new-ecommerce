// Sheffield — placeholder commercial kitchen equipment SVG illustrations.
// Flat, manufacturer-cutout style. Drawn at viewBox 0 0 240 280.
// Components are dispatched by product.kind.

const eqStyles = {
  shellStroke: "#2a2a2a",
  shellLight: "#f3f3f3",
  shellMid: "#dcdcdc",
  shellDark: "#b8b8b8",
  steel: "#cfd0d2",
  steelDk: "#9a9c9f",
  steelDkr: "#5d5f63",
  glass: "rgba(120,160,200,0.18)",
  glassEdge: "#a8b8c8",
  rubber: "#1c1c1c",
  brand: "hsl(354, 68%, 45%)",
  warm: "#b87333",
  forest: "#2f3e2e",
  cream: "#faf6ee",
};

// ───────── shared bits ─────────
const Bolts = ({ xs, ys, color = "#7d7e80" }) => (
  <g>
    {xs.map((x, i) => ys.map((y, j) => (
      <circle key={`${i}-${j}`} cx={x} cy={y} r="1.6" fill={color} />
    )))}
  </g>
);

const ControlPanel = ({ x, y, w, h, screen = true, knobs = 0 }) => (
  <g>
    <rect x={x} y={y} width={w} height={h} fill="#1f1f1f" rx="2" />
    {screen && <rect x={x + 6} y={y + 5} width={w - 36} height={h - 10} fill="#7fb6c9" opacity="0.7" rx="1" />}
    {screen && <rect x={x + 6} y={y + 5} width={(w - 36) * 0.7} height={2} fill="#fff" opacity="0.5" />}
    {Array.from({ length: knobs }).map((_, i) => (
      <g key={i}>
        <circle cx={x + w - 14 - i * 14} cy={y + h / 2} r="4.5" fill="#3a3a3a" />
        <circle cx={x + w - 14 - i * 14} cy={y + h / 2} r="3" fill="#1a1a1a" />
        <line x1={x + w - 14 - i * 14} y1={y + h / 2 - 3} x2={x + w - 14 - i * 14} y2={y + h / 2} stroke="#d4d4d4" strokeWidth="0.8" />
      </g>
    ))}
  </g>
);

// ───────── Combi oven (Rational style) ─────────
const SVGCombiOven = ({ accent = eqStyles.brand }) => (
  <svg viewBox="0 0 240 280" fill="none" xmlns="http://www.w3.org/2000/svg">
    {/* base feet */}
    <rect x="30" y="262" width="14" height="8" fill={eqStyles.rubber} />
    <rect x="196" y="262" width="14" height="8" fill={eqStyles.rubber} />
    {/* main body */}
    <rect x="22" y="40" width="196" height="225" rx="4" fill={eqStyles.shellLight} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* top vent strip */}
    <rect x="22" y="40" width="196" height="14" fill={eqStyles.shellMid} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {[...Array(14)].map((_, i) => (
      <line key={i} x1={36 + i * 12} y1="44" x2={36 + i * 12} y2="51" stroke={eqStyles.steelDkr} strokeWidth="0.8" />
    ))}
    {/* door */}
    <rect x="34" y="64" width="172" height="180" rx="3" fill={eqStyles.shellLight} stroke={eqStyles.shellStroke} strokeWidth="1" />
    {/* glass viewing window */}
    <rect x="48" y="86" width="120" height="145" rx="2" fill={eqStyles.glass} stroke={eqStyles.glassEdge} strokeWidth="1" />
    <rect x="50" y="88" width="116" height="141" fill="none" stroke={eqStyles.steelDk} strokeWidth="0.4" strokeDasharray="2 2" opacity="0.5" />
    {/* horizontal racks behind glass */}
    {[...Array(6)].map((_, i) => (
      <line key={i} x1="52" y1={102 + i * 22} x2="164" y2={102 + i * 22} stroke={eqStyles.steelDk} strokeWidth="0.6" opacity="0.6" />
    ))}
    {/* door handle */}
    <rect x="178" y="86" width="22" height="145" rx="2" fill={eqStyles.steel} stroke={eqStyles.steelDkr} strokeWidth="0.8" />
    <rect x="185" y="100" width="8" height="118" rx="1" fill={eqStyles.steelDk} />
    {/* control panel above door */}
    <ControlPanel x="34" y="60" w="172" h="20" screen knobs={2} />
    {/* brand accent stripe */}
    <rect x="22" y="252" width="196" height="3" fill={accent} />
    {/* logo placeholder */}
    <rect x="118" y="258" width="40" height="4" fill={eqStyles.shellDark} opacity="0.4" />
  </svg>
);

// ───────── Reach-in refrigerator ─────────
const SVGRefrigerator = ({ accent = eqStyles.brand }) => (
  <svg viewBox="0 0 240 280" fill="none">
    {/* feet */}
    <rect x="28" y="270" width="10" height="6" fill={eqStyles.rubber} />
    <rect x="202" y="270" width="10" height="6" fill={eqStyles.rubber} />
    {/* main cabinet */}
    <rect x="22" y="20" width="196" height="252" rx="3" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* top vent grille */}
    <rect x="22" y="20" width="196" height="22" fill={eqStyles.steelDk} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {[...Array(20)].map((_, i) => (
      <line key={i} x1={28 + i * 9.5} y1="26" x2={28 + i * 9.5} y2="36" stroke={eqStyles.steelDkr} strokeWidth="0.6" />
    ))}
    {/* two doors */}
    <rect x="28" y="48" width="92" height="216" rx="2" fill={eqStyles.steel} stroke={eqStyles.steelDk} strokeWidth="0.8" />
    <rect x="120" y="48" width="92" height="216" rx="2" fill={eqStyles.steel} stroke={eqStyles.steelDk} strokeWidth="0.8" />
    {/* door handles */}
    <rect x="100" y="70" width="6" height="170" rx="1" fill={eqStyles.steelDkr} />
    <rect x="134" y="70" width="6" height="170" rx="1" fill={eqStyles.steelDkr} />
    {/* hinge details */}
    <circle cx="36" cy="58" r="2" fill={eqStyles.steelDkr} />
    <circle cx="36" cy="254" r="2" fill={eqStyles.steelDkr} />
    <circle cx="204" cy="58" r="2" fill={eqStyles.steelDkr} />
    <circle cx="204" cy="254" r="2" fill={eqStyles.steelDkr} />
    {/* small status LED */}
    <circle cx="186" cy="32" r="1.8" fill={accent} />
    {/* brand badge */}
    <rect x="92" y="252" width="56" height="6" fill={eqStyles.shellDark} opacity="0.35" />
  </svg>
);

// ───────── Planetary mixer (Hobart) ─────────
const SVGMixer = ({ accent = eqStyles.brand }) => (
  <svg viewBox="0 0 240 280" fill="none">
    {/* base */}
    <path d="M 50 268 L 60 220 L 180 220 L 190 268 Z" fill={eqStyles.shellMid} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    <rect x="46" y="268" width="148" height="6" fill={eqStyles.rubber} />
    {/* column */}
    <rect x="148" y="60" width="36" height="170" fill={eqStyles.shellLight} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* head */}
    <rect x="58" y="60" width="126" height="50" rx="6" fill={eqStyles.shellLight} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    <rect x="58" y="100" width="126" height="14" fill={eqStyles.shellMid} />
    {/* control dial / lever on column */}
    <circle cx="166" cy="140" r="9" fill="#1f1f1f" />
    <circle cx="166" cy="140" r="6" fill="#3a3a3a" />
    <line x1="166" y1="135" x2="166" y2="140" stroke={eqStyles.cream} strokeWidth="1" />
    {/* speed lever */}
    <rect x="160" y="170" width="14" height="40" rx="3" fill="#1f1f1f" />
    <circle cx="167" cy="175" r="4" fill={eqStyles.steel} />
    {/* bowl support */}
    <rect x="78" y="148" width="60" height="6" fill={eqStyles.steelDkr} />
    <line x1="80" y1="154" x2="78" y2="178" stroke={eqStyles.steelDkr} strokeWidth="3" />
    <line x1="136" y1="154" x2="138" y2="178" stroke={eqStyles.steelDkr} strokeWidth="3" />
    {/* bowl */}
    <path d="M 70 178 L 75 230 L 141 230 L 146 178 Z" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    <ellipse cx="108" cy="178" rx="38" ry="4" fill={eqStyles.steelDk} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* whisk visible */}
    <line x1="108" y1="114" x2="108" y2="180" stroke={eqStyles.steelDkr} strokeWidth="3" />
    <path d="M 100 180 Q 108 190 116 180 Q 108 195 100 180" fill="none" stroke={eqStyles.steelDk} strokeWidth="1.2" />
    <path d="M 96 175 Q 108 196 120 175" fill="none" stroke={eqStyles.steelDk} strokeWidth="1.2" />
    {/* brand strip */}
    <rect x="58" y="82" width="126" height="2" fill={accent} />
  </svg>
);

// ───────── Pass-through dishwasher ─────────
const SVGDishwasher = ({ accent = eqStyles.brand }) => (
  <svg viewBox="0 0 240 280" fill="none">
    {/* feet */}
    <rect x="38" y="268" width="10" height="8" fill={eqStyles.rubber} />
    <rect x="192" y="268" width="10" height="8" fill={eqStyles.rubber} />
    {/* base */}
    <rect x="30" y="180" width="180" height="92" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* hood */}
    <path d="M 30 180 L 30 120 Q 30 80 70 80 L 170 80 Q 210 80 210 120 L 210 180 Z" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* hood handle */}
    <rect x="100" y="92" width="40" height="6" rx="3" fill={eqStyles.steelDkr} />
    {/* opening line */}
    <line x1="30" y1="180" x2="210" y2="180" stroke={eqStyles.steelDkr} strokeWidth="1" />
    {/* rack inside */}
    <rect x="50" y="195" width="140" height="62" fill="none" stroke={eqStyles.steelDk} strokeWidth="0.8" />
    {[...Array(7)].map((_, i) => (
      <line key={i} x1={50 + i * 20} y1="195" x2={50 + i * 20} y2="257" stroke={eqStyles.steelDk} strokeWidth="0.5" />
    ))}
    {[...Array(4)].map((_, i) => (
      <line key={i} x1="50" y1={195 + i * 16} x2="190" y2={195 + i * 16} stroke={eqStyles.steelDk} strokeWidth="0.5" />
    ))}
    {/* control panel right */}
    <ControlPanel x="160" y="148" w="46" h="24" screen knobs={2} />
    {/* brand */}
    <rect x="110" y="170" width="30" height="3" fill={accent} />
  </svg>
);

// ───────── Cooking range (Lacanche) ─────────
const SVGRange = ({ accent = eqStyles.brand }) => (
  <svg viewBox="0 0 240 280" fill="none">
    {/* feet */}
    <rect x="22" y="268" width="14" height="8" fill={eqStyles.rubber} />
    <rect x="204" y="268" width="14" height="8" fill={eqStyles.rubber} />
    {/* back splash */}
    <rect x="14" y="36" width="212" height="36" rx="2" fill={eqStyles.warm} stroke={eqStyles.shellStroke} strokeWidth="1" opacity="0.85" />
    {/* hob top */}
    <rect x="14" y="72" width="212" height="44" fill={eqStyles.shellStroke} stroke={eqStyles.shellStroke} strokeWidth="1" />
    {/* burners */}
    {[0,1,2,3,4,5].map(i => {
      const x = 30 + (i % 3) * 64;
      const y = 86 + Math.floor(i / 3) * 22;
      return (
        <g key={i}>
          <circle cx={x} cy={y} r="11" fill="#1a1a1a" stroke="#444" strokeWidth="0.6" />
          <circle cx={x} cy={y} r="7" fill="#0f0f0f" />
          <circle cx={x} cy={y} r="2.4" fill="#2a2a2a" />
        </g>
      );
    })}
    {/* main body warm-color */}
    <rect x="14" y="116" width="212" height="154" rx="2" fill={eqStyles.warm} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* knobs strip */}
    <rect x="14" y="116" width="212" height="22" fill={eqStyles.forest} />
    {[...Array(7)].map((_, i) => (
      <g key={i}>
        <circle cx={26 + i * 30} cy="127" r="5.4" fill="#d4af6a" />
        <circle cx={26 + i * 30} cy="127" r="3" fill="#1f1f1f" />
        <line x1={26 + i * 30} y1="123" x2={26 + i * 30} y2="127" stroke="#fff" strokeWidth="0.8" />
      </g>
    ))}
    {/* two oven doors */}
    <rect x="24" y="146" width="92" height="112" rx="2" fill={eqStyles.warm} stroke={eqStyles.shellStroke} strokeWidth="0.8" />
    <rect x="124" y="146" width="92" height="112" rx="2" fill={eqStyles.warm} stroke={eqStyles.shellStroke} strokeWidth="0.8" />
    {/* oven windows */}
    <rect x="36" y="162" width="68" height="68" fill={eqStyles.glass} stroke={eqStyles.glassEdge} strokeWidth="0.8" />
    <rect x="136" y="162" width="68" height="68" fill={eqStyles.glass} stroke={eqStyles.glassEdge} strokeWidth="0.8" />
    {/* handles brass */}
    <rect x="34" y="238" width="72" height="6" rx="3" fill="#d4af6a" stroke="#8a6e3c" strokeWidth="0.5" />
    <rect x="134" y="238" width="72" height="6" rx="3" fill="#d4af6a" stroke="#8a6e3c" strokeWidth="0.5" />
    {/* brand stripe on backsplash */}
    <rect x="14" y="64" width="212" height="3" fill={accent} opacity="0" />
  </svg>
);

// ───────── Food processor ─────────
const SVGProcessor = ({ accent = eqStyles.brand }) => (
  <svg viewBox="0 0 240 280" fill="none">
    {/* base */}
    <path d="M 50 250 Q 50 270 70 270 L 170 270 Q 190 270 190 250 L 190 170 L 50 170 Z" fill={eqStyles.shellLight} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* base sides bevel */}
    <line x1="50" y1="200" x2="190" y2="200" stroke={eqStyles.steelDk} strokeWidth="0.6" />
    {/* control buttons */}
    <circle cx="78" cy="232" r="6" fill="#1a1a1a" />
    <circle cx="78" cy="232" r="3.5" fill={accent} />
    <rect x="98" y="225" width="44" height="14" rx="2" fill="#1a1a1a" />
    <rect x="102" y="229" width="6" height="6" fill={eqStyles.cream} opacity="0.6" />
    <rect x="116" y="229" width="6" height="6" fill={eqStyles.cream} opacity="0.6" />
    <rect x="130" y="229" width="6" height="6" fill={eqStyles.cream} opacity="0.6" />
    <circle cx="162" cy="232" r="6" fill="#1a1a1a" />
    <circle cx="162" cy="232" r="3.5" fill={eqStyles.steel} />
    {/* bowl */}
    <rect x="62" y="80" width="116" height="92" rx="4" fill={eqStyles.glass} stroke={eqStyles.glassEdge} strokeWidth="1.2" />
    {/* lid */}
    <rect x="56" y="68" width="128" height="18" rx="3" fill={eqStyles.shellLight} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* feed tube */}
    <rect x="100" y="38" width="38" height="34" fill={eqStyles.shellLight} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    <rect x="104" y="44" width="30" height="22" fill={eqStyles.glass} stroke={eqStyles.glassEdge} strokeWidth="0.6" />
    {/* pusher */}
    <rect x="108" y="22" width="22" height="22" fill={eqStyles.shellMid} stroke={eqStyles.shellStroke} strokeWidth="1" />
    {/* blade silhouette */}
    <path d="M 84 156 L 156 156 L 152 148 L 88 148 Z" fill={eqStyles.steelDkr} />
    <circle cx="120" cy="146" r="4" fill={eqStyles.steelDkr} />
    {/* brand on base */}
    <rect x="100" y="252" width="40" height="3" fill={accent} />
  </svg>
);

// ───────── Blast chiller ─────────
const SVGChiller = ({ accent = eqStyles.brand }) => (
  <svg viewBox="0 0 240 280" fill="none">
    {/* feet */}
    <rect x="32" y="268" width="12" height="8" fill={eqStyles.rubber} />
    <rect x="196" y="268" width="12" height="8" fill={eqStyles.rubber} />
    {/* body */}
    <rect x="24" y="32" width="192" height="240" rx="3" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* top control */}
    <ControlPanel x="36" y="44" w="168" h="34" screen knobs={3} />
    {/* status LEDs row */}
    <circle cx="48" cy="92" r="2.2" fill={accent} />
    <circle cx="58" cy="92" r="2.2" fill="#4ec57f" />
    <circle cx="68" cy="92" r="2.2" fill="#f5b400" />
    {/* door */}
    <rect x="36" y="106" width="168" height="146" rx="2" fill={eqStyles.steel} stroke={eqStyles.steelDk} strokeWidth="0.8" />
    <rect x="50" y="120" width="118" height="118" fill={eqStyles.glass} stroke={eqStyles.glassEdge} strokeWidth="1" />
    {/* tray slots */}
    {[...Array(5)].map((_, i) => (
      <line key={i} x1="52" y1={130 + i * 22} x2="166" y2={130 + i * 22} stroke={eqStyles.steelDk} strokeWidth="0.6" />
    ))}
    {/* handle */}
    <rect x="180" y="120" width="20" height="118" rx="2" fill={eqStyles.steel} stroke={eqStyles.steelDkr} strokeWidth="0.6" />
    <rect x="186" y="130" width="8" height="98" rx="1" fill={eqStyles.steelDk} />
  </svg>
);

// ───────── Slicer ─────────
const SVGSlicer = ({ accent = eqStyles.brand }) => (
  <svg viewBox="0 0 240 280" fill="none">
    {/* base platform */}
    <rect x="20" y="200" width="200" height="60" rx="4" fill={eqStyles.shellLight} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    <rect x="20" y="252" width="200" height="14" fill={eqStyles.shellMid} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* product carriage */}
    <rect x="36" y="180" width="80" height="22" rx="2" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1" />
    {/* carriage rail */}
    <rect x="28" y="196" width="100" height="6" fill={eqStyles.steelDk} />
    {/* slice catcher tray */}
    <path d="M 134 192 L 200 192 L 196 220 L 138 220 Z" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1" />
    {/* blade housing */}
    <circle cx="138" cy="130" r="62" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* blade */}
    <circle cx="138" cy="130" r="50" fill={eqStyles.steelDk} stroke={eqStyles.shellStroke} strokeWidth="0.8" />
    <circle cx="138" cy="130" r="46" fill="none" stroke={eqStyles.shellStroke} strokeWidth="0.4" strokeDasharray="2 3" />
    <circle cx="138" cy="130" r="8" fill="#1a1a1a" />
    <circle cx="138" cy="130" r="3" fill={eqStyles.steel} />
    {/* blade guard partial */}
    <path d="M 76 130 A 62 62 0 0 1 138 68 L 138 84 A 46 46 0 0 0 92 130 Z" fill={eqStyles.shellLight} stroke={eqStyles.shellStroke} strokeWidth="0.8" />
    {/* thickness dial */}
    <circle cx="58" cy="148" r="14" fill="#1a1a1a" />
    <circle cx="58" cy="148" r="9" fill={eqStyles.steel} />
    <line x1="58" y1="139" x2="58" y2="148" stroke="#1a1a1a" strokeWidth="1.4" />
    {/* brand */}
    <rect x="118" y="240" width="40" height="3" fill={accent} />
  </svg>
);

// ───────── Display case ─────────
const SVGDisplay = ({ accent = eqStyles.brand }) => (
  <svg viewBox="0 0 240 280" fill="none">
    {/* base */}
    <rect x="12" y="200" width="216" height="72" rx="3" fill={eqStyles.shellLight} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    <rect x="12" y="256" width="216" height="16" fill={eqStyles.shellMid} />
    {/* curved glass front */}
    <path d="M 12 200 L 12 130 Q 12 60 80 60 L 228 60 L 228 200 Z" fill={eqStyles.glass} stroke={eqStyles.glassEdge} strokeWidth="1.4" />
    {/* shelves angled */}
    <path d="M 18 110 L 222 78" stroke={eqStyles.steelDk} strokeWidth="0.8" />
    <path d="M 18 140 L 222 112" stroke={eqStyles.steelDk} strokeWidth="0.8" />
    <path d="M 18 170 L 222 146" stroke={eqStyles.steelDk} strokeWidth="0.8" />
    {/* products on shelves */}
    {[0,1,2].map(row => {
      const yBase = 102 + row * 30;
      const yShift = row * -8;
      return [0,1,2,3,4].map(col => (
        <rect key={`${row}-${col}`}
          x={32 + col * 38}
          y={yBase + (col * (yShift / 4))}
          width="22" height="14"
          fill={row === 0 ? "#d4a574" : row === 1 ? "#b87333" : "#8a6e3c"}
          stroke={eqStyles.shellStroke} strokeWidth="0.4"
          opacity="0.85"
          rx="1"
        />
      ));
    })}
    {/* top LED strip */}
    <rect x="20" y="68" width="200" height="3" fill="#f5e6a8" />
    {/* control panel right */}
    <rect x="200" y="210" width="20" height="40" fill="#1f1f1f" rx="1" />
    <circle cx="210" cy="220" r="2" fill={accent} />
    <circle cx="210" cy="230" r="2" fill="#4ec57f" />
  </svg>
);

// ───────── Chef's knife ─────────
const SVGKnife = ({ accent = eqStyles.brand }) => (
  <svg viewBox="0 0 240 280" fill="none">
    {/* blade */}
    <path d="M 30 138 L 30 150 L 152 152 Q 175 152 175 138 Q 152 130 30 138 Z" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* blade highlight */}
    <path d="M 36 142 L 155 142 Q 168 142 168 138" fill="none" stroke="#fff" strokeWidth="0.6" opacity="0.7" />
    {/* bolster */}
    <rect x="170" y="132" width="14" height="24" rx="2" fill={eqStyles.steelDkr} stroke={eqStyles.shellStroke} strokeWidth="0.8" />
    {/* handle */}
    <path d="M 184 130 L 222 132 L 222 156 L 184 158 Z" fill="#1a1a1a" stroke={eqStyles.shellStroke} strokeWidth="1" />
    {/* fibrox texture lines */}
    {[...Array(6)].map((_, i) => (
      <line key={i} x1={190 + i * 5} y1="134" x2={190 + i * 5} y2="156" stroke="#3a3a3a" strokeWidth="0.4" />
    ))}
    {/* tang rivets */}
    <circle cx="195" cy="144" r="1.6" fill={eqStyles.steel} />
    <circle cx="215" cy="144" r="1.6" fill={eqStyles.steel} />
    {/* brand stamp on blade */}
    <text x="60" y="146" fontSize="6" fill={eqStyles.steelDkr} fontFamily="ui-sans-serif" opacity="0.6">VICTORINOX</text>
    <circle cx="100" cy="144" r="1.4" fill={accent} opacity="0.7" />
  </svg>
);

// ───────── Tilting pan / iVario ─────────
const SVGTiltingPan = ({ accent = eqStyles.brand }) => (
  <svg viewBox="0 0 240 280" fill="none">
    {/* legs */}
    <rect x="40" y="180" width="6" height="92" fill={eqStyles.steelDk} />
    <rect x="194" y="180" width="6" height="92" fill={eqStyles.steelDk} />
    <rect x="32" y="270" width="22" height="6" fill={eqStyles.rubber} />
    <rect x="186" y="270" width="22" height="6" fill={eqStyles.rubber} />
    {/* lower frame */}
    <rect x="32" y="260" width="176" height="10" fill={eqStyles.steelDk} />
    {/* pan body */}
    <path d="M 20 100 L 20 180 Q 20 200 50 200 L 190 200 Q 220 200 220 180 L 220 100 Z" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1.4" />
    {/* pan inner */}
    <path d="M 30 110 L 30 178 Q 30 190 50 190 L 190 190 Q 210 190 210 178 L 210 110 Z" fill={eqStyles.shellLight} stroke={eqStyles.steelDk} strokeWidth="0.8" />
    {/* lid (raised, behind) */}
    <path d="M 30 100 Q 120 70 210 100 L 210 90 Q 120 60 30 90 Z" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* lid handle */}
    <rect x="108" y="62" width="24" height="6" rx="2" fill={eqStyles.steelDkr} />
    {/* control panel */}
    <rect x="32" y="206" width="176" height="44" rx="2" fill={eqStyles.shellLight} stroke={eqStyles.shellStroke} strokeWidth="1" />
    <ControlPanel x="44" y="212" w="100" h="32" screen knobs={0} />
    <circle cx="170" cy="228" r="11" fill="#1a1a1a" />
    <circle cx="170" cy="228" r="6.5" fill={eqStyles.steel} />
    <line x1="170" y1="220" x2="170" y2="228" stroke="#1a1a1a" strokeWidth="1.4" />
    {/* brand */}
    <rect x="106" y="252" width="28" height="3" fill={accent} />
  </svg>
);

// ───────── Immersion blender (stick) ─────────
const SVGStickBlender = ({ accent = eqStyles.brand }) => (
  <svg viewBox="0 0 240 280" fill="none">
    {/* motor head */}
    <rect x="100" y="30" width="44" height="80" rx="6" fill={eqStyles.shellLight} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    {/* vent grille */}
    {[...Array(6)].map((_, i) => (
      <line key={i} x1="106" y1={42 + i * 6} x2="138" y2={42 + i * 6} stroke={eqStyles.steelDk} strokeWidth="0.4" />
    ))}
    {/* button */}
    <circle cx="122" cy="88" r="6" fill={accent} />
    <circle cx="122" cy="88" r="3" fill="#fff" opacity="0.6" />
    {/* trigger button */}
    <rect x="116" y="98" width="12" height="6" rx="2" fill="#1a1a1a" />
    {/* speed dial */}
    <circle cx="122" cy="118" r="6" fill="#1a1a1a" />
    <circle cx="122" cy="118" r="3" fill={eqStyles.steel} />
    {/* coupling */}
    <rect x="110" y="110" width="24" height="20" fill={eqStyles.shellMid} stroke={eqStyles.shellStroke} strokeWidth="0.8" />
    {/* shaft */}
    <rect x="116" y="128" width="12" height="118" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1" />
    {/* shaft segments */}
    {[...Array(7)].map((_, i) => (
      <line key={i} x1="116" y1={140 + i * 15} x2="128" y2={140 + i * 15} stroke={eqStyles.steelDk} strokeWidth="0.4" />
    ))}
    {/* foot bell */}
    <path d="M 102 246 L 142 246 L 138 270 L 106 270 Z" fill={eqStyles.steel} stroke={eqStyles.shellStroke} strokeWidth="1.2" />
    <ellipse cx="122" cy="270" rx="16" ry="3" fill={eqStyles.steelDk} stroke={eqStyles.shellStroke} strokeWidth="0.6" />
    {/* power cord coming out top */}
    <path d="M 122 30 Q 100 18 88 32" fill="none" stroke="#1a1a1a" strokeWidth="2" />
  </svg>
);

// ───────── Dispatcher ─────────
const ProductIllustration = ({ kind, accent, style, photo }) => {
  if (photo) {
    return (
      <div className="prod-illust" style={{ width: "100%", height: "100%", display: "flex", alignItems: "center", justifyContent: "center", ...style }}>
        <img src={photo} alt="" loading="lazy"
          style={{ width: "100%", height: "100%", objectFit: "contain", display: "block", padding: 8 }}/>
      </div>
    );
  }
  const Comp = {
    "combi-oven":  SVGCombiOven,
    refrigerator:  SVGRefrigerator,
    mixer:         SVGMixer,
    dishwasher:    SVGDishwasher,
    range:         SVGRange,
    processor:     SVGProcessor,
    chiller:       SVGChiller,
    slicer:        SVGSlicer,
    display:       SVGDisplay,
    knife:         SVGKnife,
    "tilting-pan": SVGTiltingPan,
    blender:       SVGStickBlender,
  }[kind] || SVGCombiOven;
  return (
    <div className="prod-illust" style={{ width: "100%", height: "100%", display: "flex", alignItems: "center", justifyContent: "center", ...style }}>
      <Comp accent={accent} />
    </div>
  );
};

Object.assign(window, {
  ProductIllustration,
  SVGCombiOven, SVGRefrigerator, SVGMixer, SVGDishwasher, SVGRange,
  SVGProcessor, SVGChiller, SVGSlicer, SVGDisplay, SVGKnife,
  SVGTiltingPan, SVGStickBlender,
});
