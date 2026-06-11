<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Register the `errors` view namespace the way the framework's exception
 * handler does at render time, so each custom error view can be rendered and
 * asserted directly.
 */
beforeEach(function () {
    View::addNamespace('errors', resource_path('views/errors'));
});

function renderError(int $code, string $message = ''): string
{
    return view("errors::{$code}", [
        'exception' => new HttpException($code, $message),
    ])->render();
}

/**
 * Render an error view as though the request targeted the given path, so the
 * component's admin-vs-storefront detection can be exercised without a route.
 */
function renderErrorFrom(int $code, string $path): string
{
    $original = app('request');
    app()->instance('request', Request::create($path, 'GET'));

    try {
        return view("errors::{$code}", ['exception' => new HttpException($code)])->render();
    } finally {
        app()->instance('request', $original);
    }
}

it('renders each custom error page with its status code and branded copy', function (int $code, string $needle) {
    expect(renderError($code))
        ->toContain((string) $code)
        ->toContain($needle);
})->with([
    '404 not found' => [404, 'find that page'],
    '403 forbidden' => [403, 'access to this page'],
    '419 page expired' => [419, 'timed out'],
    '429 rate limited' => [429, 'slow down'],
    '500 server error' => [500, 'wrong on our end'],
    '503 unavailable' => [503, 'right back'],
]);

it('does not render a product search box on error pages', function () {
    expect(renderError(404))->not->toContain('name="q"');
});

it('surfaces a custom 403 abort message (e.g. banned IP)', function () {
    expect(renderError(403, 'Your IP address has been banned.'))
        ->toContain('Your IP address has been banned.');
});

it('shows a custom 503 maintenance message when provided, otherwise a default', function () {
    expect(renderError(503, 'Upgrading our payment system.'))
        ->toContain('Upgrading our payment system.');

    // The framework's bare "Service Unavailable" reason is treated as no message.
    expect(renderError(503, 'Service Unavailable'))
        ->toContain('quick maintenance');
});

it('wraps healthy-app errors in storefront chrome', function () {
    // Storefront context (default): the 404 keeps the navbar + footer so users
    // can navigate away. "Request quote" is a storefront nav link.
    expect(renderError(404))->toContain('Request quote');
});

it('renders fatal errors standalone, without storefront chrome', function () {
    expect(renderError(500))->not->toContain('Request quote');
    expect(renderError(503))->not->toContain('Request quote');
});

it('returns the branded 404 in storefront chrome for an unknown URL end-to-end', function () {
    $this->get('/a-page-that-does-not-exist-'.uniqid())
        ->assertStatus(404)
        ->assertSee('find that page')
        ->assertSee('Request quote'); // storefront chrome present
});

it('returns the branded 404 with the admin sidebar, permitted nav and navbar', function () {
    $admin = actingAsAdmin();

    $this->get('/admin/a-page-that-does-not-exist-'.uniqid())
        ->assertStatus(404)
        ->assertSee('find that page')
        ->assertSee('Dashboard')          // sidebar present
        ->assertSee('Products')           // permission-gated nav resolves (auth available via fallback route)
        ->assertSee($admin->initials())   // navbar account menu rendered for the signed-in user
        ->assertSee('Back to dashboard')  // admin-oriented actions, not the storefront ones
        ->assertDontSee('Browse the shop');
});

it('keeps the admin sidebar for staff errors raised outside /admin (e.g. Livewire)', function () {
    actingAsAdmin();

    // Errors thrown inside a Livewire action render for /livewire/update, not the
    // /admin URL — the admin referer keeps staff on the sidebar layout.
    $this->withHeader('Referer', url('/admin/products'))
        ->get('/a-page-that-does-not-exist-'.uniqid())
        ->assertStatus(404)
        ->assertSee('find that page')
        ->assertSee('Dashboard');
});

it('keeps the admin sidebar (never the storefront) for an admin URL without auth', function () {
    // e.g. a 419/expired session on an admin page: the sidebar-only layout has no
    // auth-dependent topbar, so it renders the sidebar and never leaks the
    // customer storefront chrome.
    expect(renderErrorFrom(404, '/admin/products'))
        ->toContain('find that page')
        ->toContain('Dashboard')          // admin sidebar present
        ->not->toContain('Request quote'); // storefront chrome absent
});
