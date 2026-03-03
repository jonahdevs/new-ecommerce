<?php

use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home.index')->name('home');

Route::middleware('guest')->controller(SocialiteController::class)->group(function () {
    Route::get('/auth/{provider}/redirect',  'redirect')->name('socialite.redirect')->where('provider', 'google|facebook');
    Route::get('/auth/{provider}/callback',  'callback')->name('socialite.callback')->where('provider', 'google|facebook');
});

// Products Routes
Route::livewire('/products', 'pages::products')->name('products');
Route::livewire('/products/{product:slug}', 'pages::product-details')->name('products.show');
Route::livewire('/products/{product:slug}/reviews', 'pages::product-reviews')->name('product.reviews');
Route::livewire('compare', 'pages::product-compare')->name('products.compare');

Route::livewire('/wishlist', 'pages::wishlist')->name('wishlist');
Route::livewire('/cart', 'pages::cart')->name('cart');


Route::middleware(['auth', 'customer'])->group(function () {
    Route::livewire('/checkout/shipping', 'pages::checkout.shipping')->name('checkout.shipping');
    Route::livewire('/checkout/summary', 'pages::checkout.summary')->name('checkout.summary');
    Route::livewire('/checkout/card-payment', 'pages::checkout.card-payment')->name('checkout.card-payment');
    Route::livewire('/checkout/payment-methods', 'pages::checkout.payment')->name('checkout.payment-methods');

    Route::livewire('customer/address/index', 'pages::customer.address.index')->name('customer.address.index');
    Route::livewire('/checkout/addresses', 'pages::checkout.address.index')->name('checkout.addresses');
    Route::livewire('/checkout/addresses/create', 'pages::checkout.address.create')->name('checkout.addresses.create');
    Route::livewire('/checkout/addresses/{address}/edit', 'pages::checkout.address.edit')->name('checkout.addresses.edit');
});

// Payment callbacks (redirect-back from gateway)
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/callback/success', [App\Http\Controllers\Payment\CallbackController::class, 'success'])->name('callback.success');
    Route::get('/callback/cancel',  [App\Http\Controllers\Payment\CallbackController::class, 'cancel'])->name('callback.cancel');
});

// Webhooks — CSRF exempt, verified by gateway signatures
Route::prefix('webhooks')->name('payment.webhook.')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    Route::post('/pesawise', App\Http\Controllers\Webhooks\PesawiseWebhookController::class)->name('pesawise');
    Route::post('/mpesa',    App\Http\Controllers\Webhooks\MpesaWebhookController::class)->name('mpesa');
    Route::post('/stripe',   App\Http\Controllers\Webhooks\StripeWebhookController::class)->name('stripe');
});

// customer
Route::middleware(['auth', 'customer', 'verified'])->name('customer')->group(function () {
    Route::livewire('account', 'pages::customer.account')->name('.account');

    Route::livewire('orders/{order}/confirmation', 'pages::customer.orders.confirmation')->name('.orders.confirmation');
    Route::livewire('orders', 'pages::customer.orders.index')->name('.orders.index');
    Route::livewire('orders/{order}', 'pages::customer.orders.show')->name('.orders.show');
    Route::livewire('orders/{order}/tracking', 'pages::customer.orders.tracking')->name('.orders.tracking');

    Route::prefix('address-book')->name('.address-book')->group(function () {
        Route::livewire('/', 'pages::customer.address-book.index')->name('.index');
        Route::livewire('/create', 'pages::customer.address-book.create')->name('.create');
        Route::livewire('/{address}/edit', 'pages::customer.address-book.edit')->name('.edit');
    });
});

Route::middleware(['auth', 'staff', 'verified'])->prefix('admin')->name('admin')->group(function () {
    Route::view('dashboard', 'dashboard')->name('.dashboard');

    // Sales
    Route::livewire('orders', 'pages::admin.sales.orders.index')->name('.orders.index');
    Route::livewire('orders/{order}', 'pages::admin.sales.orders.show')->name('.orders.show');

    Route::livewire('payments', 'pages::admin.sales.payments.index')->name('.payments.index');
    Route::livewire('payments/{payment}', 'pages::admin.sales.payments.show')->name('.payments.show');

    // catalog
    Route::livewire('/categories', 'pages::admin.catalog.categories.index')->name('.categories.index');
    Route::livewire('/categories/create', 'pages::admin.catalog.categories.create')->name('.categories.create');
    Route::livewire('/categories/{category}/edit', 'pages::admin.catalog.categories.edit')->name('.categories.edit');

    Route::livewire('/attributes', 'pages::admin.catalog.attributes.index')->name('.attributes.index');
    Route::livewire('/attributes/{attribute}/values', 'pages::admin.catalog.attributes.values')->name('.attributes.values');

    Route::livewire('/products', 'pages::admin.catalog.products.index')->name('.products.index');
    Route::livewire('/products/create', 'pages::admin.catalog.products.create')->name('.products.create');
    Route::livewire('/products/{product}/edit', 'pages::admin.catalog.products.edit')->name('.products.edit');

    Route::livewire('/brands', 'pages::admin.catalog.brands.index')->name('.brands.index');
    Route::livewire('/brands/create', 'pages::admin.catalog.brands.create')->name('.brands.create');
    Route::livewire('/brands/{brand}/edit', 'pages::admin.catalog.brands.edit')->name('.brands.edit');

    Route::livewire('/tags', 'pages::admin.catalog.tags.index')->name('.tags.index');
    Route::livewire('/tags/create', 'pages::admin.catalog.tags.create')->name('.tags.create');
    Route::livewire('/tags/{tag}/edit', 'pages::admin.catalog.tags.edit')->name('.tags.edit');

    Route::livewire('/roles', 'pages::admin.access-control.roles.index')->name('.roles.index');
    Route::livewire('/roles/{role}/edit', 'pages::admin.access-control.roles.edit')->name('.roles.edit');
    Route::livewire('/permissions', 'pages::admin.access-control.permissions.index')->name('.permissions.index');

    Route::livewire('/users/create', 'pages::admin.access-control.users.create')->name('.users.create');
    Route::livewire('/users/{user}/edit', 'pages::admin.access-control.users.edit')->name('.users.edit');

    // Logistics
    Route::prefix('logistics')->name('.logistics')->group(function () {
        Route::livewire('overview', 'pages::admin.logistics.dashboard')->name('.overview');

        Route::prefix('configuration')->name('.configurations')->group(function () {
            Route::livewire('providers', 'pages::admin.logistics.configuration.providers')->name('.providers');
            Route::livewire('zones', 'pages::admin.logistics.configuration.zones')->name('.zones');
            Route::livewire('methods', 'pages::admin.logistics.configuration.methods')->name('.methods');

            Route::prefix('locations')->name('.locations')->group(function () {
                Route::livewire('counties', 'pages::admin.logistics.configuration.locations.counties')->name('.counties');
                Route::livewire('areas', 'pages::admin.logistics.configuration.locations.areas')->name('.areas');
            });

            // rates
            Route::prefix('rates')->name('.rates')->group(function () {
                Route::livewire('addons', 'pages::admin.logistics.configuration.rates.addons')->name('.addons');
                Route::livewire('flat', 'pages::admin.logistics.configuration.rates.flat')->name('.flat');
                Route::livewire('vehicle', 'pages::admin.logistics.configuration.rates.vehicle')->name('.vehicle');
            });

            Route::livewire('pickup-stations', 'pages::admin.logistics.configuration.pickup-stations')->name('.pickup-stations');
            Route::livewire('free-shipping-rules', 'pages::admin.logistics.configuration.free-shipping-rules')->name('.free-shipping-rules');
        });

        // operations
        Route::prefix('operations')->name('.operations')->group(function () {
            Route::livewire('delivery-orders', 'pages::admin.logistics.operations.delivery-orders')->name('.delivery-orders');
            Route::livewire('pus-tracker', 'pages::admin.logistics.operations.pus-tracker')->name('.pus-tracker');
            Route::livewire('returns', 'pages::admin.logistics.operations.returns')->name('.returns');
        });
    });

    // Engagement
    Route::livewire('/customers', 'pages::admin.engagement.customers.index')->name('.customers.index');
    Route::livewire('/customers/create', 'pages::admin.engagement.customers.create')->name('.customers.create');
    Route::livewire('/customers/{customer}/edit', 'pages::admin.engagement.customers.edit')->name('.customers.edit');
    Route::livewire('/customers/{customer}/show', 'pages::admin.engagement.customers.show')->name('.customers.show');

    Route::livewire('reviews', 'pages::admin.engagement.reviews.index')->name('.reviews.index');
    Route::livewire('reviews/{review}', 'pages::admin.engagement.reviews.show')->name('.reviews.show');
});


require __DIR__ . '/settings.php';
