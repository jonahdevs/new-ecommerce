<?php

use App\Http\Middleware\EnsureTwoFactorWhenRequired;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Admin / Staff
// TODO: add ->middleware('role:admin') once spatie/laravel-permission is installed.
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'verified', EnsureTwoFactorWhenRequired::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/', 'pages.admin.dashboard')->name('dashboard');
        Route::livewire('/delivery-zones', 'pages::admin.delivery-zones')->name('delivery-zones');
        Route::livewire('/delivery-promotions', 'pages::admin.delivery-promotions')->name('delivery-promotions');
        Route::livewire('/shipping/methods', 'pages::admin.shipping.methods.index')->name('shipping.methods.index');
        Route::livewire('/shipping/methods/create', 'pages::admin.shipping.methods.create')->name('shipping.methods.create');
        Route::livewire('/shipping/methods/{shippingMethod}/edit', 'pages::admin.shipping.methods.edit')->name('shipping.methods.edit');
        Route::livewire('/shipping/carriers', 'pages::admin.shipping.carriers.index')->name('shipping.carriers.index');
        Route::livewire('/shipping/carriers/create', 'pages::admin.shipping.carriers.create')->name('shipping.carriers.create');
        Route::livewire('/shipping/carriers/{shippingCarrier}/edit', 'pages::admin.shipping.carriers.edit')->name('shipping.carriers.edit');
        Route::livewire('/shipping/warehouses', 'pages::admin.shipping.warehouses.index')->name('shipping.warehouses.index');
        Route::livewire('/shipping/warehouses/create', 'pages::admin.shipping.warehouses.create')->name('shipping.warehouses.create');
        Route::livewire('/shipping/warehouses/{warehouse}/edit', 'pages::admin.shipping.warehouses.edit')->name('shipping.warehouses.edit');
        Route::livewire('/showrooms', 'pages::admin.showrooms')->name('showrooms.index');
        Route::livewire('/pages', 'pages::admin.pages.index')->name('pages.index');
        Route::livewire('/pages/create', 'pages::admin.pages.form')->name('pages.create');
        Route::livewire('/pages/{page}/edit', 'pages::admin.pages.form')->name('pages.edit');
        Route::livewire('/products', 'pages::admin.products.index')->name('products.index');
        Route::livewire('/products/create', 'pages::admin.products.form')->name('products.create');
        Route::livewire('/products/{product}/edit', 'pages::admin.products.form')->name('products.edit');
        Route::livewire('/categories', 'pages::admin.categories.index')->name('categories.index');
        Route::livewire('/brands', 'pages::admin.brands.index')->name('brands.index');
        Route::livewire('/attributes', 'pages::admin.attributes.index')->name('attributes.index');
        Route::livewire('/attributes/create', 'pages::admin.attributes.create')->name('attributes.create');
        Route::livewire('/attributes/{attribute}/edit', 'pages::admin.attributes.edit')->name('attributes.edit');
        Route::livewire('/tags', 'pages::admin.tags.index')->name('tags.index');
        Route::livewire('/tax-classes', 'pages::admin.tax-classes.index')->name('tax-classes.index');
        Route::livewire('/orders', 'pages::admin.orders.index')->name('orders.index');
        Route::livewire('/orders/{order}', 'pages::admin.orders.show')->name('orders.show');
        Route::livewire('/quotes', 'pages::admin.quotes.index')->name('quotes.index');
        Route::livewire('/quotes/create', 'pages::admin.quotes.create')->name('quotes.create');
        Route::livewire('/quotes/{quote}', 'pages::admin.quotes.show')->name('quotes.show');
        Route::livewire('/payments', 'pages::admin.payments.index')->name('payments.index');
        Route::livewire('/payments/{payment}', 'pages::admin.payments.show')->name('payments.show');
        Route::livewire('/customers', 'pages::admin.customers.index')->name('customers.index');
        Route::livewire('/customers/create', 'pages::admin.customers.create')->name('customers.create');
        Route::livewire('/customers/{customer}/edit', 'pages::admin.customers.edit')->name('customers.edit');
        Route::livewire('/customers/{customer}', 'pages::admin.customers.show')->name('customers.show');
        Route::livewire('/reviews', 'pages::admin.reviews.index')->name('reviews.index');
        Route::redirect('/settings', '/admin/settings/general');
        Route::livewire('/settings/general', 'pages::admin.settings.general')->name('settings.general');
        Route::livewire('/settings/website', 'pages::admin.settings.website')->name('settings.website');
        Route::livewire('/settings/app', 'pages::admin.settings.app')->name('settings.app');
        Route::livewire('/settings/financial', 'pages::admin.settings.financial')->name('settings.financial');
        Route::livewire('/settings/system', 'pages::admin.settings.system')->name('settings.system');
        Route::livewire('/settings/other', 'pages::admin.settings.other')->name('settings.other');
        Route::livewire('/activity/{logName}', 'pages::admin.activity.index')->name('activity.show');

        Route::livewire('/staff', 'pages::admin.staff.index')->name('staff.index');
        Route::livewire('/staff/create', 'pages::admin.staff.create')->name('staff.create');
        Route::livewire('/roles', 'pages::admin.roles.index')->name('roles.index');
        Route::livewire('/roles/create', 'pages::admin.roles.form')->name('roles.create');
        Route::livewire('/roles/{role}/edit', 'pages::admin.roles.form')->name('roles.edit');
        Route::livewire('/permissions', 'pages::admin.permissions.index')->name('permissions.index');
    });
