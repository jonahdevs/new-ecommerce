<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');

// Products Routes
Route::livewire('/products', 'pages::products')->name('products');
Route::livewire('/products/{product:slug}', 'pages::product-details')->name('products.show');
Route::livewire('/products/{product:slug}/reviews', 'pages::product-reviews')->name('product.reviews');
Route::livewire('compare', 'pages::product-compare')->name('products.compare');

Route::livewire('/wishlist', 'pages::wishlist')->name('wishlist');
Route::livewire('/cart', 'pages::cart')->name('cart');

Route::middleware(['auth', 'cart_not_empty'])->group(function () {
    Route::livewire('/checkout/summary', 'pages::checkout.summary')->name('checkout.summary');

    Route::livewire('/checkout/addresses', 'pages::checkout.address.index')->name('checkout.addresses');
    Route::livewire('/checkout/addresses/create', 'pages::checkout.address.create')->name('checkout.addresses.create');
    Route::livewire('/checkout/addresses/{address}/edit', 'pages::checkout.address.edit')->name('checkout.addresses.edit');

    Route::livewire('/checkout/shipping-options', 'pages::checkout.shipping-options')->name('checkout.shipping-options');

    Route::livewire('customer/address/index', 'pages::customer.address.index')->name('customer.address.index');
});


Route::middleware('auth')->prefix('admin')->name('admin')->group(function () {
    Route::livewire('zones', 'pages::admin.logistics.zones')->name('.zones');
    Route::livewire('counties', 'pages::admin.logistics.counties')->name('.counties');
    Route::livewire('areas', 'pages::admin.logistics.areas')->name('.areas');
    Route::livewire('shipping-methods', 'pages::admin.logistics.shipping-methods')->name('.shipping-methods');
    Route::livewire('shipping-rates', 'pages::admin.logistics.shipping-rates')->name('.shipping-rates');
});
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__ . '/settings.php';
