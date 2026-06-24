/**
 * Catalog load test — simulates concurrent visitors browsing the storefront.
 *
 * Install k6 (Windows): winget install k6 --source winget
 * Refresh PATH:  $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
 *
 * Run (basic):   k6 run tests/load/catalog.js
 * Run (smoke):   k6 run --env SCENARIO=smoke tests/load/catalog.js
 * Run (soak):    k6 run --env SCENARIO=soak  tests/load/catalog.js
 *
 * NOTE: Disable Telescope before running (APP_TELESCOPE=false or comment out in
 * TelescopeServiceProvider) — it writes every request to the DB and inflates
 * response times by 50-200ms per request.
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'https://new-ecommerce.test';

const errorRate  = new Rate('errors');
const serverTime = new Trend('server_time_ms');

// Scenario presets — choose via k6 run --env SCENARIO=smoke|load|soak
const SCENARIOS = {
    smoke: { stages: [{ duration: '30s', target: 5 }] },
    load:  { stages: [{ duration: '30s', target: 20 }, { duration: '1m', target: 50 }, { duration: '30s', target: 0 }] },
    soak:  { stages: [{ duration: '1m',  target: 30 }, { duration: '5m', target: 30 }, { duration: '30s', target: 0 }] },
};

const scenario = SCENARIOS[__ENV.SCENARIO] || SCENARIOS.load;

export const options = {
    // Skip TLS verification for local Herd dev (self-signed cert).
    // Remove this line when running against a real HTTPS endpoint in production.
    insecureSkipTLSVerify: true,
    scenarios: {
        catalog_browse: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: scenario.stages,
        },
    },
    thresholds: {
        // Adjust these for production — local dev with Telescope active will be 3-5× slower.
        'http_req_duration{type:static}': ['p(95)<300'],
        'http_req_duration{type:dynamic}': ['p(95)<2000'],
        errors: ['rate<0.01'],
    },
};

// Real URLs from the app — mix of high-traffic pages.
// Add more product/category slugs from: php artisan tinker --execute 'Product::published()->pluck("slug")->take(10);'
const PAGES = [
    { url: '/',                                                              type: 'dynamic' },
    { url: '/shop',                                                          type: 'dynamic' },
    { url: '/shop/commercial-kitchen',                                       type: 'dynamic' },
    { url: '/shop/cold-room',                                                type: 'dynamic' },
    { url: '/shop/laundry',                                                  type: 'dynamic' },
    { url: '/product/vegetable-processor-pa7-imgfpr00042',                   type: 'dynamic' },
    { url: '/product/manual-vegetable-slicer-systematic-jscv-2200-imgfpr00130', type: 'dynamic' },
    { url: '/product/salad-and-vegetable-dryer-40-lt-220v-sy40-09-imgfpr00234', type: 'dynamic' },
    { url: '/cart',                                                          type: 'dynamic' },
    { url: '/contact',                                                       type: 'dynamic' },
    { url: '/request-quote',                                                 type: 'dynamic' },
];

export default function () {
    const page = PAGES[Math.floor(Math.random() * PAGES.length)];
    const res  = http.get(`${BASE_URL}${page.url}`, {
        headers: { Accept: 'text/html' },
        tags:    { type: page.type, page: page.url },
    });

    const ok = check(res, {
        'status 200':     (r) => r.status === 200,
        'no server error': (r) => r.status < 500,
    });

    errorRate.add(!ok);

    // Read Server-Timing header when available (requires APP_DEBUG=true or custom middleware).
    const timing = res.headers['Server-Timing'] || '';
    const match  = timing.match(/app;dur=([\d.]+)/);
    if (match) {
        serverTime.add(parseFloat(match[1]));
    }

    sleep(Math.random() * 2 + 0.5);
}

export function handleSummary(data) {
    const dur  = data.metrics.http_req_duration?.values;
    const reqs = data.metrics.http_reqs?.values?.count ?? 0;
    const errs = data.metrics.errors?.values?.rate ?? 0;

    console.log('\n── Catalog Load Test Results ──────────────────────────────');
    console.log(`Requests:      ${reqs}`);
    console.log(`Error rate:    ${(errs * 100).toFixed(1)}%`);
    if (dur) {
        console.log(`Avg response:  ${dur.avg.toFixed(0)} ms`);
        console.log(`P95 response:  ${dur['p(95)'].toFixed(0)} ms`);
        console.log(`Max response:  ${dur.max.toFixed(0)} ms`);
    }
    console.log('─────────────────────────────────────────────────────────\n');

    return {
        'tests/load/results/catalog-summary.json': JSON.stringify(data, null, 2),
    };
}
