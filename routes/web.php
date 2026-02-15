<?php

use App\Http\Controllers\PaymentCallbackController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');

// Products Routes
Route::livewire('/products', 'pages::products')->name('products');
Route::livewire('/products/{product:slug}', 'pages::product-details')->name('products.show');
Route::livewire('/products/{product:slug}/reviews', 'pages::product-reviews')->name('product.reviews');
Route::livewire('compare', 'pages::product-compare')->name('products.compare');

Route::livewire('/wishlist', 'pages::wishlist')->name('wishlist');
Route::livewire('/cart', 'pages::cart')->name('cart');

Route::controller(PaymentCallbackController::class)->prefix('payment/callback')->name('payment.callback')->group(function () {
    Route::get('success', 'handleSuccess')->name('.success');
    Route::get('cancel', 'handleCancel')->name('.cancel');
});

// Payment callback routes - MUST be outside auth middleware so Pesawise can access them
Route::livewire('/payment/success', 'pages::checkout.success')->name('checkout.success-page');
Route::livewire('/payment/cancel', 'pages::checkout.cancel')->name('payment.cancel');


Route::middleware(['auth', 'cart_not_empty'])->group(function () {
    Route::livewire('/checkout/summary', 'pages::checkout.summary')->name('checkout.summary');

    Route::livewire('customer/address/index', 'pages::customer.address.index')->name('customer.address.index');
    Route::livewire('/checkout/addresses', 'pages::checkout.address.index')->name('checkout.addresses');
    Route::livewire('/checkout/addresses/create', 'pages::checkout.address.create')->name('checkout.addresses.create');
    Route::livewire('/checkout/addresses/{address}/edit', 'pages::checkout.address.edit')->name('checkout.addresses.edit');

    Route::livewire('/checkout/shipping-options', 'pages::checkout.shipping-options')->name('checkout.shipping-options');
});

Route::middleware('auth')->prefix('admin')->name('admin')->group(function () {
    // Sales
    Route::livewire('orders', 'pages::admin.sales.orders.index')->name('.orders');
    Route::livewire('orders/{order}', 'pages::admin.sales.orders.show')->name('.orders.show');

    Route::livewire('payments', 'pages::admin.sales.payments.index')->name('.payments');
    Route::livewire('payments/{order}', 'pages::admin.sales.payments.show')->name('.payments.show');

    // catalog
    Route::livewire('/categories', 'pages::admin.catalog.categories.index')->name('.categories.index');
    Route::livewire('/categories/create', 'pages::admin.catalog.categories.create')->name('.categories.create');
    Route::livewire('/categories/{category}/edit', 'pages::admin.catalog.categories.edit')->name('.categories.edit');

    Route::livewire('/attributes', 'pages::admin.catalog.attributes.index')->name('.attributes.index');
    Route::livewire('/attributes/create', 'pages::admin.catalog.attributes.create')->name('.attributes.create');
    Route::livewire('/attributes/{attribute}/edit', 'pages::admin.catalog.attributes.edit')->name('.attributes.edit');

    Route::livewire('/products', 'pages::admin.catalog.products.index')->name('.products.index');
    Route::livewire('/products/create', 'pages::admin.catalog.products.create')->name('.products.create');
    Route::livewire('/products/{product}/edit', 'pages::admin.catalog.products.edit')->name('.products.edit');

    Route::livewire('/brands', 'pages::admin.catalog.brands.index')->name('.brands.index');
    Route::livewire('/brands/create', 'pages::admin.catalog.brands.create')->name('.brands.create');
    Route::livewire('/brands/{brand}/edit', 'pages::admin.catalog.brands.edit')->name('.brands.edit');

    Route::livewire('/tags', 'pages::admin.catalog.tags.index')->name('.tags.index');
    Route::livewire('/tags/create', 'pages::admin.catalog.tags.create')->name('.tags.create');
    Route::livewire('/tags/{tag}/edit', 'pages::admin.catalog.tags.edit')->name('.tags.edit');

    // Logistics
    Route::livewire('zones', 'pages::admin.logistics.zones')->name('.zones');
    Route::livewire('counties', 'pages::admin.logistics.counties')->name('.counties');
    Route::livewire('areas', 'pages::admin.logistics.areas')->name('.areas');
    Route::livewire('shipping-methods', 'pages::admin.logistics.shipping-methods')->name('.shipping-methods');
    Route::livewire('shipping-rates', 'pages::admin.logistics.shipping-rates')->name('.shipping-rates');
    Route::livewire('pickup-stations', 'pages::admin.logistics.pickup-stations')->name('.pickup-stations');
    Route::livewire('free-shipping', 'pages::admin.logistics.free-shipping')->name('.free-shipping');

    // Engagement
    Route::livewire('reviews', 'pages::admin.engagement.reviews.index')->name('.reviews');
    Route::livewire('reviews/{review}', 'pages::admin.engagement.reviews.show')->name('.reviews.show');
});
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__ . '/settings.php';
