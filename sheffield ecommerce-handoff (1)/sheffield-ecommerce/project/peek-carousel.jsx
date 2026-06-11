// Sheffield — horizontal "peek" carousel for product rows.
// Manual only: drag/scroll-snap + arrow buttons in section header.
// No autoplay, no dots.

const PeekCarousel = ({ children, count, label = "products", headerSlot, gap = 14, cardWidth }) => {
  const trackRef = React.useRef(null);
  const [canPrev, setCanPrev] = React.useState(false);
  const [canNext, setCanNext] = React.useState(true);

  const updateAffordance = React.useCallback(() => {
    const el = trackRef.current;
    if (!el) return;
    const max = el.scrollWidth - el.clientWidth - 2;
    setCanPrev(el.scrollLeft > 2);
    setCanNext(el.scrollLeft < max);
  }, []);

  React.useEffect(() => {
    const el = trackRef.current;
    if (!el) return;
    el.addEventListener("scroll", updateAffordance, { passive: true });
    const ro = new ResizeObserver(updateAffordance);
    ro.observe(el);
    updateAffordance();
    return () => { el.removeEventListener("scroll", updateAffordance); ro.disconnect(); };
  }, [updateAffordance]);

  const scrollBy = (dir) => {
    const el = trackRef.current;
    if (!el) return;
    // Scroll by ~75% of viewport width for a comfortable shift
    const delta = Math.round(el.clientWidth * 0.78) * dir;
    el.scrollBy({ left: delta, behavior: "smooth" });
  };

  return (
    <div>
      <div style={{
        display: "flex", justifyContent: "space-between", alignItems: "end",
        marginBottom: 16, gap: 24,
      }}>
        <div style={{ flex: 1, minWidth: 0 }}>{headerSlot}</div>
        <div style={{ display: "flex", gap: 6, alignItems: "center", flexShrink: 0, paddingBottom: 4 }}>
          <button onClick={() => scrollBy(-1)} aria-label="Scroll left" disabled={!canPrev}
            style={arrowStyle(canPrev)}>
            <IconArrowL size={16} sw={2}/>
          </button>
          <button onClick={() => scrollBy(1)} aria-label="Scroll right" disabled={!canNext}
            style={arrowStyle(canNext)}>
            <IconArrow size={16} sw={2}/>
          </button>
        </div>
      </div>

      <div
        ref={trackRef}
        style={{
          display: "grid",
          gridAutoFlow: "column",
          gridAutoColumns: cardWidth || "minmax(0, calc((100% - 64px) / 4))",
          gap,
          overflowX: "auto",
          overflowY: "visible",
          scrollSnapType: "x mandatory",
          scrollPaddingLeft: 0,
          paddingBottom: 4,
          // Hide scrollbar
          scrollbarWidth: "none",
          msOverflowStyle: "none",
        }}
        className="peek-track"
      >
        {React.Children.map(children, (child) => (
          <div style={{ scrollSnapAlign: "start", minWidth: 0 }}>{child}</div>
        ))}
      </div>
    </div>
  );
};

const arrowStyle = (enabled) => ({
  width: 38, height: 38, padding: 0,
  background: "var(--bg-elev)",
  color: enabled ? "var(--ink)" : "var(--ink-4)",
  border: "1px solid var(--line)",
  borderRadius: 999,
  cursor: enabled ? "pointer" : "default",
  opacity: enabled ? 1 : 0.5,
  display: "inline-flex", alignItems: "center", justifyContent: "center",
  transition: "background 120ms ease, border-color 120ms ease, color 120ms ease",
});

Object.assign(window, { PeekCarousel });
