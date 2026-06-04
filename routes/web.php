<?php

use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\Payments\MpesaCallbackController;
use App\Http\Controllers\Payments\StripeWebhookController;
use App\Http\Controllers\SocialAuthController;
use App\Settings\SeoSettings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Payment provider callbacks (server-to-server, no auth, CSRF-exempt)
// ---------------------------------------------------------------------------
Route::post('/payments/mpesa/callback', MpesaCallbackController::class)->name('payments.mpesa.callback');
Route::post('/payments/stripe/webhook', StripeWebhookController::class)->name('payments.stripe.webhook');

// ---------------------------------------------------------------------------
// Storefront (guests + logged-in browsing)
// ---------------------------------------------------------------------------
Route::livewire('/', 'pages::storefront.home')->name('home');
Route::livewire('/shop', 'pages::storefront.catalog')->name('catalog');
Route::livewire('/shop/{category:slug}', 'pages::storefront.category')->name('category.show');
Route::livewire('/cart', 'pages::storefront.cart')->name('cart');
Route::livewire('/wishlist', 'pages::storefront.wishlist')->name('wishlist');
Route::livewire('/compare', 'pages::storefront.compare')->name('compare');
Route::livewire('/contact', 'pages::storefront.contact')->name('contact');
Route::livewire('/request-quote', 'pages::storefront.request-quote')->name('quote.request');
Route::livewire('/checkout', 'pages::storefront.checkout')->name('checkout')->middleware('auth');
Route::livewire('/pay/{order}', 'pages::storefront.payment')->name('payment.page')->middleware('auth');
Route::livewire('/product/{product:slug}', 'pages::storefront.product')->name('product.show');

// ---------------------------------------------------------------------------
// SEO — sitemap + robots, both driven by SeoSettings.
// ---------------------------------------------------------------------------
Route::get('/sitemap.xml', function () {
    abort_unless(app(SeoSettings::class)->generate_sitemap, 404);

    $path = public_path('sitemap.xml');

    // Generate on-the-fly if the file does not exist yet (e.g. fresh deploy).
    if (! file_exists($path)) {
        Artisan::call('sitemap:generate');
    }

    abort_unless(file_exists($path), 404);

    return response()->file($path, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::get('/robots.txt', function () {
    $seo = app(SeoSettings::class);

    $lines = $seo->index_site
        ? ['User-agent: *', 'Allow: /']
        : ['User-agent: *', 'Disallow: /'];

    if ($seo->index_site && $seo->generate_sitemap) {
        $lines[] = 'Sitemap: '.route('sitemap');
    }

    return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain');
})->name('robots');

// ---------------------------------------------------------------------------
// Newsletter — confirm & unsubscribe (public, no auth)
// ---------------------------------------------------------------------------
Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// ---------------------------------------------------------------------------
// Social auth — Google
// ---------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// ---------------------------------------------------------------------------
// Post-login landing — branches by role.
// Customers go to their account dashboard; admins are bounced to /admin.
// TODO: swap the hasRole check for spatie/laravel-permission once installed.
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'verified'])->get('/dashboard', function () {
    $user = auth()->user();

    if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('account.dashboard');
})->name('dashboard');

require __DIR__.'/account.php';
require __DIR__.'/admin.php';

// ---------------------------------------------------------------------------
// CMS pages — registered LAST so every explicit route above wins. Single-segment
// slugs only; the component 404s on unpublished/unknown pages.
// ---------------------------------------------------------------------------
Route::livewire('/{page:slug}', 'pages::storefront.page')->name('page.show');
