<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Orders\OrderReceiptController;
use App\Http\Controllers\Payment\CallbackController;
use App\Http\Controllers\Webhooks\PesawiseWebhookController;
use App\Http\Controllers\Webhooks\MpesaWebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// PUBLIC ROUTES
// ============================================================================

Route::livewire('/', 'pages::home.index')->name('home');

// ----------------------------------------------------------------------------
// Catalog — Shop, Categories, Products
// ----------------------------------------------------------------------------

Route::prefix('shop')->name('shop.')->group(function () {
    Route::livewire('/', 'pages::products')->name('index');
    Route::livewire('/category/{category:slug}', 'pages::category-products')->name('category');
});

Route::prefix('products')->name('products.')->group(function () {
    Route::livewire('/compare', 'pages::product-compare')->name('compare');
    Route::livewire('/{product:slug}', 'pages::product-details.index')->name('show');
    Route::livewire('/{product:slug}/reviews', 'pages::product-reviews')->name('reviews');
});

// ----------------------------------------------------------------------------
// Wishlist & Cart
// ----------------------------------------------------------------------------

Route::livewire('/wishlist', 'pages::wishlist')->name('wishlist');
Route::livewire('/cart', 'pages::cart')->name('cart');

// ----------------------------------------------------------------------------
// Social Auth
// ----------------------------------------------------------------------------

Route::middleware('guest')
    ->controller(SocialiteController::class)
    ->prefix('auth')
    ->name('socialite.')
    ->group(function () {
        Route::get('/{provider}/redirect', 'redirect')
            ->name('redirect')
            ->where('provider', 'google|facebook');

        Route::get('/{provider}/callback', 'callback')
            ->name('callback')
            ->where('provider', 'google|facebook');
    });

// ----------------------------------------------------------------------------
// Payment Callbacks (gateway redirects back to site)
// ----------------------------------------------------------------------------

Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/callback/success', [CallbackController::class, 'success'])->name('callback.success');
    Route::get('/callback/cancel', [CallbackController::class, 'cancel'])->name('callback.cancel');
});

// ----------------------------------------------------------------------------
// Webhooks — CSRF-exempt, verified by gateway signatures
// ----------------------------------------------------------------------------

Route::prefix('webhooks')
    ->name('webhooks.')
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
    ])
    ->group(function () {
        Route::post('/pesawise', PesawiseWebhookController::class)->name('pesawise');
        Route::post('/mpesa', MpesaWebhookController::class)->name('mpesa');
        Route::post('/stripe', StripeWebhookController::class)->name('stripe');
    });

// ============================================================================
// CHECKOUT — Authenticated customers only
// ============================================================================

// Payment pages — auth + customer only (no cart_not_empty check, order already exists)
Route::middleware(['auth', 'customer'])
    ->prefix('checkout')
    ->name('checkout.')
    ->group(function () {
        Route::livewire('/pay/{order}', 'pages::checkout.pay')->name('pay');
        Route::livewire('/card-payment/{order}', 'pages::checkout.card-payment')->name('card-payment');
    });

// Checkout flow — auth + customer + must have items in cart
Route::middleware(['auth', 'customer', 'cart_not_empty'])
    ->prefix('checkout')
    ->name('checkout.')
    ->group(function () {
        Route::livewire('/shipping', 'pages::checkout.shipping')->name('shipping');
        Route::livewire('/summary', 'pages::checkout.summary')->name('summary');
        Route::livewire('/payment-methods', 'pages::checkout.payment')->name('payment-methods');

        Route::prefix('addresses')->name('addresses.')->group(function () {
            Route::livewire('/', 'pages::checkout.address.index')->name('index');
            Route::livewire('/create', 'pages::checkout.address.create')->name('create');
            Route::livewire('/{address}/edit', 'pages::checkout.address.edit')->name('edit');
        });
    });

// ============================================================================
// CUSTOMER PORTAL — Authenticated, verified customers
// ============================================================================

Route::middleware(['auth', 'customer', 'verified'])
    ->name('customer.')
    ->group(function () {
        Route::livewire('/account', 'pages::customer.account')->name('account');

        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::livewire('/', 'pages::customer.orders.index')->name('index');
            Route::livewire('/{order}', 'pages::customer.orders.show')->name('show');
            Route::livewire('/{order}/confirmation', 'pages::customer.orders.confirmation')->name('confirmation');
            Route::livewire('/{order}/tracking', 'pages::customer.orders.tracking')->name('tracking');
            Route::get('/{order}/receipt', OrderReceiptController::class)->name('receipt');
        });

        // Address Book
        Route::prefix('address-book')->name('address-book.')->group(function () {
            Route::livewire('/', 'pages::customer.address-book.index')->name('index');
            Route::livewire('/create', 'pages::customer.address-book.create')->name('create');
            Route::livewire('/{address}/edit', 'pages::customer.address-book.edit')->name('edit');
        });
    });

// ============================================================================
// ADMIN PANEL — Authenticated, verified staff only
// ============================================================================

Route::middleware(['auth', 'staff', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');

        // --------------------------------------------------------------------
        // Sales
        // --------------------------------------------------------------------

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::livewire('/', 'pages::admin.sales.orders.index')->name('index');
            Route::livewire('/{order}', 'pages::admin.sales.orders.show')->name('show');
        });

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::livewire('/', 'pages::admin.sales.payments.index')->name('index');
            Route::livewire('/{payment}', 'pages::admin.sales.payments.show')->name('show');
        });

        // --------------------------------------------------------------------
        // Catalog
        // --------------------------------------------------------------------

        Route::prefix('catalog')->name('catalog.')->group(function () {

            Route::prefix('categories')->name('categories.')->group(function () {
                Route::livewire('/', 'pages::admin.catalog.categories.index')->name('index');
                Route::livewire('/create', 'pages::admin.catalog.categories.create')->name('create');
                Route::livewire('/{category}/edit', 'pages::admin.catalog.categories.edit')->name('edit');
            });

            Route::prefix('products')->name('products.')->group(function () {
                Route::livewire('/', 'pages::admin.catalog.products.index')->name('index');
                Route::livewire('/create', 'pages::admin.catalog.products.create')->name('create');
                Route::livewire('/{product}/edit', 'pages::admin.catalog.products.edit')->name('edit');
            });

            Route::prefix('brands')->name('brands.')->group(function () {
                Route::livewire('/', 'pages::admin.catalog.brands.index')->name('index');
                Route::livewire('/create', 'pages::admin.catalog.brands.create')->name('create');
                Route::livewire('/{brand}/edit', 'pages::admin.catalog.brands.edit')->name('edit');
            });

            Route::prefix('attributes')->name('attributes.')->group(function () {
                Route::livewire('/', 'pages::admin.catalog.attributes.index')->name('index');
                Route::livewire('/{attribute}/values', 'pages::admin.catalog.attributes.values')->name('values');
            });

            Route::prefix('tags')->name('tags.')->group(function () {
                Route::livewire('/', 'pages::admin.catalog.tags.index')->name('index');
                Route::livewire('/create', 'pages::admin.catalog.tags.create')->name('create');
                Route::livewire('/{tag}/edit', 'pages::admin.catalog.tags.edit')->name('edit');
            });
        });

        // --------------------------------------------------------------------
        // Logistics
        // --------------------------------------------------------------------

        Route::prefix('logistics')->name('logistics.')->group(function () {
            Route::livewire('/overview', 'pages::admin.logistics.dashboard')->name('overview');

            Route::prefix('configuration')->name('configuration.')->group(function () {
                Route::livewire('/providers', 'pages::admin.logistics.configuration.providers')->name('providers');
                Route::livewire('/zones', 'pages::admin.logistics.configuration.zones')->name('zones');
                Route::livewire('/methods', 'pages::admin.logistics.configuration.methods')->name('methods');
                Route::livewire('/pickup-stations', 'pages::admin.logistics.configuration.pickup-stations')->name('pickup-stations');
                Route::livewire('/free-shipping-rules', 'pages::admin.logistics.configuration.free-shipping-rules')->name('free-shipping-rules');

                Route::prefix('locations')->name('locations.')->group(function () {
                    Route::livewire('/counties', 'pages::admin.logistics.configuration.locations.counties')->name('counties');
                    Route::livewire('/areas', 'pages::admin.logistics.configuration.locations.areas')->name('areas');
                });

                Route::prefix('rates')->name('rates.')->group(function () {
                    Route::livewire('/addons', 'pages::admin.logistics.configuration.rates.addons')->name('addons');
                    Route::livewire('/flat', 'pages::admin.logistics.configuration.rates.flat')->name('flat');
                    Route::livewire('/vehicle', 'pages::admin.logistics.configuration.rates.vehicle')->name('vehicle');
                });
            });

            Route::prefix('operations')->name('operations.')->group(function () {
                Route::livewire('/delivery-orders', 'pages::admin.logistics.operations.delivery-orders')->name('delivery-orders');
                Route::livewire('/pus-tracker', 'pages::admin.logistics.operations.pus-tracker')->name('pus-tracker');
                Route::livewire('/returns', 'pages::admin.logistics.operations.returns')->name('returns');
            });
        });

        // --------------------------------------------------------------------
        // Engagement
        // --------------------------------------------------------------------

        Route::prefix('customers')->name('customers.')->group(function () {
            Route::livewire('/', 'pages::admin.engagement.customers.index')->name('index');
            Route::livewire('/create', 'pages::admin.engagement.customers.create')->name('create');
            Route::livewire('/{customer}/edit', 'pages::admin.engagement.customers.edit')->name('edit');
            Route::livewire('/{customer}', 'pages::admin.engagement.customers.show')->name('show');
        });

        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::livewire('/', 'pages::admin.engagement.reviews.index')->name('index');
            Route::livewire('/{review}', 'pages::admin.engagement.reviews.show')->name('show');
        });

        // --------------------------------------------------------------------
        // Access Control
        // --------------------------------------------------------------------

        Route::prefix('access-control')->name('access-control.')->group(function () {
            Route::prefix('roles')->name('roles.')->group(function () {
                Route::livewire('/', 'pages::admin.access-control.roles.index')->name('index');
                Route::livewire('/{role}/edit', 'pages::admin.access-control.roles.edit')->name('edit');
            });

            Route::livewire('/permissions', 'pages::admin.access-control.permissions.index')->name('permissions');

            Route::prefix('users')->name('users.')->group(function () {
                Route::livewire('/create', 'pages::admin.access-control.users.create')->name('create');
                Route::livewire('/{user}/edit', 'pages::admin.access-control.users.edit')->name('edit');
            });
        });
    });

// ============================================================================
// DEVELOPMENT ONLY
// ============================================================================

if (app()->isLocal()) {
    Route::livewire('/test-broadcast', 'pages::broadcast-test')
        ->middleware('auth')
        ->name('test.broadcast');
}

// ============================================================================
// ADDITIONAL ROUTE FILES
// ============================================================================

require __DIR__ . '/settings.php';
