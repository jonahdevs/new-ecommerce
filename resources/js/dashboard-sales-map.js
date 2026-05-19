/**
 * Dashboard "Top Sales Locations" — Unovis TopoJSONMap of Kenya counties.
 *
 * Loads a Kenya boundary file from /maps/. Accepts either:
 *   - GeoJSON FeatureCollection (file extension .geojson)
 *   - TopoJSON Topology (file extension .topojson)
 *
 * Feature properties used for county matching (first match wins):
 *   COUNTY_NAM | NAME_1 | name | COUNTY | county   →  matched against row.name
 *   COUNTY_COD | code | CODE                        →  matched against row.code
 *
 * Each county's color = linear scale from light surface to brand red,
 * keyed by revenue from the Livewire `topSalesLocations` computed.
 */
import { TopoJSONMap } from '@unovis/ts/components/topojson-map';
import { SingleContainer } from '@unovis/ts/containers/single-container';
import { topology } from 'topojson-server';

// We attach our own tooltip via plain DOM listeners rather than Unovis's
// Tooltip component — the latter relies on event delegation with hashed CSS
// classes and proved flaky when paired with an externally-loaded TopoJSON.
// A small vanilla tooltip gives us reliable hover behaviour with no surprises.

// Try the TopoJSON file first, then fall back to GeoJSON (auto-converted).
const CANDIDATE_PATHS = ['/maps/kenya-counties.topojson', '/maps/kenya.geojson'];
let topologyPromise = null;

function loadTopology() {
    if (topologyPromise) return topologyPromise;
    topologyPromise = (async () => {
        for (const url of CANDIDATE_PATHS) {
            try {
                const res = await fetch(url);
                if (!res.ok) continue;
                const data = await res.json();
                // GeoJSON FeatureCollection — convert to TopoJSON on the fly
                if (data?.type === 'FeatureCollection') {
                    return { data: topology({ counties: data }), source: url, layerName: 'counties' };
                }
                // Already TopoJSON
                if (data?.type === 'Topology') {
                    return { data, source: url, layerName: Object.keys(data.objects || {})[0] };
                }
            } catch (_) {
                // try next path
            }
        }
        throw new Error('No usable Kenya map file found in /maps/');
    })();
    return topologyPromise;
}

// Match TopoJSON feature properties against our county data by name or code.
// Returns the matching row (with revenue/orders) or null.
function buildMatcher(data) {
    const byName = {};
    const byCode = {};
    data.forEach((d) => {
        if (d.name) byName[String(d.name).toLowerCase().trim()] = d;
        if (d.code) byCode[String(d.code).toLowerCase().trim()] = d;
    });

    return (props) => {
        if (!props) return null;
        const nameCandidates = [
            props.name, props.NAME, props.NAME_1, props.COUNTY, props.county, props.County,
            props.COUNTY_NAM, props.COUNTY_NAME,
        ]
            .filter(Boolean)
            .map((s) => String(s).toLowerCase().trim());
        for (const candidate of nameCandidates) {
            // Try exact, then strip "County" suffix
            if (byName[candidate]) return byName[candidate];
            const stripped = candidate.replace(/\s+county$/i, '').trim();
            if (byName[stripped]) return byName[stripped];
        }
        const codeCandidates = [props.code, props.CODE, props.COUNTY_COD, props.COUNTY_CODE]
            .filter((v) => v !== undefined && v !== null)
            .map((s) => String(s).toLowerCase().trim());
        for (const candidate of codeCandidates) {
            if (byCode[candidate]) return byCode[candidate];
        }
        return null;
    };
}

// Quantile-style color scale: counties with more orders get darker shades.
// Using sqrt easing on the order count so low-order counties don't all collapse
// into the lightest shade when one or two counties dominate.
function makeColorScale(maxOrders) {
    const cMin = [241, 245, 249]; // ~zinc-100  (no orders)
    const cMax = [193, 36, 53]; // sheffield primary (max orders)
    return (orderCount) => {
        if (!orderCount || orderCount <= 0) return `rgb(${cMin.join(',')})`;
        const t = Math.min(1, Math.sqrt(orderCount / maxOrders));
        const r = Math.round(cMin[0] + (cMax[0] - cMin[0]) * t);
        const g = Math.round(cMin[1] + (cMax[1] - cMin[1]) * t);
        const b = Math.round(cMin[2] + (cMax[2] - cMin[2]) * t);
        return `rgb(${r}, ${g}, ${b})`;
    };
}

window.__initSalesMap = async function (elementId, data = []) {
    const container = document.getElementById(elementId);
    if (!container) return;

    // Tear down previous render (e.g. after a Livewire morph)
    container.innerHTML = '';

    let topojson, layerName;
    try {
        const loaded = await loadTopology();
        topojson = loaded.data;
        layerName = loaded.layerName;
    } catch (err) {
        container.innerHTML =
            '<div class="p-8 text-center text-zinc-400 text-sm">' +
            'Kenya map data not found. Add a file to <code>public/maps/</code> &mdash; ' +
            'see <code>public/maps/README.md</code>.' +
            '</div>';
        return;
    }

    if (!layerName) {
        container.innerHTML = '<div class="p-8 text-center text-zinc-400 text-sm">Invalid map data: no object layers.</div>';
        return;
    }

    const match = buildMatcher(data);
    const maxOrders = Math.max(1, ...data.map((d) => Number(d.orders) || 0));
    const colorScale = makeColorScale(maxOrders);

    // Build one area record per TopoJSON geometry, with a unique id and the
    // matched revenue/orders/name from our PHP data (or zero if no match).
    const areas = topojson.objects[layerName].geometries
        .map((g, i) => {
            const props = g.properties || {};
            const row = match(props);
            const id = g.id ?? props.id ?? props.OBJECTID ?? `feature-${i}`;
            return {
                id,
                revenue: row?.revenue ?? 0,
                orders: row?.orders ?? 0,
                name: row?.name ?? props.name ?? props.COUNTY_NAM ?? props.NAME_1 ?? '—',
            };
        });

    const map = new TopoJSONMap({
        topojson,
        mapFeatureName: layerName,
        areaId: (d) => d.id,
        areaColor: (d) => colorScale(d.orders),
        areaCursor: 'pointer',
        disableZoom: false,
    });

    // eslint-disable-next-line no-new
    new SingleContainer(container, { component: map }, { areas });

    // ── Hand-rolled tooltip — attaches listeners after Unovis renders the SVG.
    // We look up the matched area record by joining the bound datum's id to our
    // local `areas[]` lookup, which always has the merged revenue/orders/name.
    attachTooltip(container, areas);
};

function attachTooltip(container, areas) {
    // Build a fast lookup table keyed by area.id
    const areaById = {};
    areas.forEach((a) => {
        areaById[String(a.id)] = a;
    });

    // Make sure the container can position children absolutely
    const cs = window.getComputedStyle(container);
    if (cs.position === 'static') container.style.position = 'relative';

    // Reuse a single tooltip element per container
    let tip = container.querySelector('.dashboard-sales-tooltip');
    if (!tip) {
        tip = document.createElement('div');
        tip.className = 'dashboard-sales-tooltip';
        Object.assign(tip.style, {
            position: 'absolute',
            pointerEvents: 'none',
            background: 'rgba(255,255,255,0.97)',
            border: '1px solid #e4e4e7',
            borderRadius: '6px',
            padding: '8px 10px',
            fontSize: '12px',
            lineHeight: '1.4',
            color: '#111',
            boxShadow: '0 4px 12px rgba(0,0,0,0.08)',
            zIndex: '50',
            opacity: '0',
            transition: 'opacity 120ms ease',
            whiteSpace: 'nowrap',
        });
        container.appendChild(tip);
    }

    const hide = () => {
        tip.style.opacity = '0';
    };
    const show = (html, x, y) => {
        tip.innerHTML = html;
        tip.style.opacity = '1';
        // Place above and slightly right of cursor, clamped inside the container
        const rect = container.getBoundingClientRect();
        const tipRect = tip.getBoundingClientRect();
        const left = Math.min(Math.max(8, x - rect.left + 12), rect.width - tipRect.width - 8);
        const top = Math.max(8, y - rect.top - tipRect.height - 12);
        tip.style.left = `${left}px`;
        tip.style.top = `${top}px`;
    };

    // Wait for Unovis to paint, then attach listeners to every path element
    // inside the map SVG. We use container.querySelectorAll so we catch all
    // features regardless of the hashed Unovis CSS class.
    requestAnimationFrame(() => {
        const paths = container.querySelectorAll('svg path');
        paths.forEach((path) => {
            // Skip paths that aren't features (e.g. background rect, point markers).
            // Features have a non-empty `d` attribute that starts with M and tend
            // to be the largest set — we filter by presence of bound datum below.
            path.addEventListener('mousemove', (e) => {
                const d = path.__data__; // d3 binds data to the DOM element
                if (!d) return hide();
                const id = d.id ?? d.properties?.id ?? d.properties?.OBJECTID;
                const matched = areaById[String(id)];
                const props = d.properties || {};
                const name =
                    matched?.name ??
                    d.name ??
                    props.name ??
                    props.COUNTY_NAM ??
                    props.NAME_1 ??
                    '—';
                const orders = matched?.orders ?? d.orders ?? 0;
                const revenue = matched?.revenue ?? d.revenue ?? 0;
                const constituency = props.CONSTITUEN ? `<div style="color:#a1a1aa;font-size:10px;margin-top:2px">${props.CONSTITUEN}</div>` : '';

                let body;
                if (!orders) {
                    body = `<div style="color:#a1a1aa;margin-top:2px">No orders</div>`;
                } else {
                    const orderLabel = orders === 1 ? 'order' : 'orders';
                    body = `<div style="color:#71717a;margin-top:2px">${orders} ${orderLabel} &middot; KES ${Number(revenue).toLocaleString(undefined, { maximumFractionDigits: 2 })}</div>`;
                }

                show(
                    `<div style="font-weight:600;color:#111">${name}</div>${constituency}${body}`,
                    e.clientX,
                    e.clientY,
                );
            });
            path.addEventListener('mouseleave', hide);
        });
    });
}
