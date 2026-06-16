<?php

use App\Http\Controllers\Admin\CustomerExportController;
use App\Http\Controllers\Admin\OrderDocumentController;
use App\Http\Controllers\Admin\OrderExportController;
use App\Http\Controllers\Admin\ProductExportController;
use App\Http\Controllers\Admin\QuoteExportController;
use App\Http\Controllers\Admin\SubscriberExportController;
use App\Http\Middleware\EnsureTwoFactorWhenRequired;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Admin / Staff — requires an assigned role and per-section permissions.
// The outer group blocks any authenticated user who has no staff role at all.
// Individual sub-groups enforce granular permission checks.
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'verified', EnsureTwoFactorWhenRequired::class, 'staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // ── Dashboard — all staff ────────────────────────────────────────────
        Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

        // ── Products ─────────────────────────────────────────────────────────
        Route::middleware('permission:products.view')->group(function () {
            Route::livewire('/products', 'pages::admin.products.index')->name('products.index');
            Route::controller(ProductExportController::class)
                ->prefix('products')
                ->name('products.')
                ->group(function () {
                    Route::get('export', 'download')->name('export');
                    Route::get('pdf', 'pdf')->name('pdf');
                    Route::get('import-template', 'template')->name('import-template');
                });
            Route::livewire('/products/create', 'pages::admin.products.form')->middleware('permission:products.manage')->name('products.create');
            Route::livewire('/products/{product}/edit', 'pages::admin.products.form')->middleware('permission:products.manage')->name('products.edit');
        });

        // ── Catalog ──────────────────────────────────────────────────────────
        Route::middleware('permission:catalog.manage')->group(function () {
            Route::livewire('/categories', 'pages::admin.categories.index')->name('categories.index');
            Route::livewire('/categories/create', 'pages::admin.categories.create')->name('categories.create');
            Route::livewire('/categories/{category}/edit', 'pages::admin.categories.edit')->name('categories.edit');
            Route::livewire('/brands', 'pages::admin.brands.index')->name('brands.index');
            Route::livewire('/attributes', 'pages::admin.attributes.index')->name('attributes.index');
            Route::livewire('/attributes/create', 'pages::admin.attributes.create')->name('attributes.create');
            Route::livewire('/attributes/{attribute}/edit', 'pages::admin.attributes.edit')->name('attributes.edit');
            Route::livewire('/tax-classes', 'pages::admin.tax-classes.index')->name('tax-classes.index');
        });

        Route::middleware('permission:tags.manage')->group(function () {
            Route::livewire('/tags', 'pages::admin.tags.index')->name('tags.index');
        });

        // ── Orders ───────────────────────────────────────────────────────────
        Route::middleware('permission:orders.view')->group(function () {
            Route::livewire('/orders', 'pages::admin.orders.index')->name('orders.index');
            Route::controller(OrderExportController::class)
                ->prefix('orders')
                ->name('orders.')
                ->group(function () {
                    Route::get('export', 'download')->name('export');
                    Route::get('pdf', 'pdf')->name('pdf');
                });
            Route::livewire('/sap-sync', 'pages::admin.sap-sync')->name('sap-sync');
            Route::livewire('/orders/{order}', 'pages::admin.orders.show')->name('orders.show');
            Route::controller(OrderDocumentController::class)
                ->prefix('orders/{order}')
                ->name('orders.')
                ->group(function () {
                    Route::get('packing-list', 'packingList')->name('packing-list');
                    Route::get('delivery-note', 'deliveryNote')->name('delivery-note');
                    Route::get('kra-receipt', 'kraReceipt')->name('kra-receipt');
                });
        });

        // ── Quotes ───────────────────────────────────────────────────────────
        Route::middleware('permission:quotes.view')->group(function () {
            Route::livewire('/quotes', 'pages::admin.quotes.index')->name('quotes.index');
            Route::controller(QuoteExportController::class)
                ->prefix('quotes')
                ->name('quotes.')
                ->group(function () {
                    Route::get('export', 'download')->name('export');
                    Route::get('pdf', 'pdf')->name('pdf');
                });
            Route::livewire('/quotes/create', 'pages::admin.quotes.create')->middleware('permission:quotes.manage')->name('quotes.create');
            Route::livewire('/quotes/{quote}', 'pages::admin.quotes.show')->name('quotes.show');
            Route::livewire('/quotes/{quote}/preview', 'pages::admin.quotes.preview')->name('quotes.preview');
        });

        // ── Payments ─────────────────────────────────────────────────────────
        Route::middleware('permission:payments.view')->group(function () {
            Route::livewire('/payments', 'pages::admin.payments.index')->name('payments.index');
            Route::livewire('/payments/{payment}', 'pages::admin.payments.show')->name('payments.show');
        });

        // ── Customers ────────────────────────────────────────────────────────
        Route::middleware('permission:customers.view')->group(function () {
            Route::livewire('/customers', 'pages::admin.customers.index')->name('customers.index');
            Route::controller(CustomerExportController::class)
                ->prefix('customers')
                ->name('customers.')
                ->group(function () {
                    Route::get('export', 'download')->name('export');
                    Route::get('pdf', 'pdf')->name('pdf');
                });
            Route::livewire('/customers/create', 'pages::admin.customers.create')->middleware('permission:customers.manage')->name('customers.create');
            Route::livewire('/customers/{customer}/edit', 'pages::admin.customers.edit')->middleware('permission:customers.manage')->name('customers.edit');
            Route::livewire('/customers/{customer}', 'pages::admin.customers.show')->name('customers.show');
            Route::livewire('/subscribers', 'pages::admin.subscribers.index')->name('subscribers.index');
            Route::get('/subscribers/export', SubscriberExportController::class)->name('subscribers.export');
        });

        // ── Reviews ──────────────────────────────────────────────────────────
        Route::middleware('permission:reviews.manage')->group(function () {
            Route::livewire('/reviews', 'pages::admin.reviews.index')->name('reviews.index');
        });

        // ── Logistics ────────────────────────────────────────────────────────
        Route::middleware('permission:delivery.manage')->group(function () {
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
            Route::livewire('/showrooms', 'pages::admin.showrooms.index')->name('showrooms.index');
            Route::livewire('/showrooms/create', 'pages::admin.showrooms.create')->name('showrooms.create');
            Route::livewire('/showrooms/{showroom}/edit', 'pages::admin.showrooms.edit')->name('showrooms.edit');
        });

        // ── Content ──────────────────────────────────────────────────────────
        Route::middleware('permission:settings.manage')->group(function () {
            Route::livewire('/pages', 'pages::admin.pages.index')->name('pages.index');
            Route::livewire('/pages/create', 'pages::admin.pages.form')->name('pages.create');
            Route::livewire('/pages/{page}/edit', 'pages::admin.pages.form')->name('pages.edit');
        });

        // ── Settings & Activity ──────────────────────────────────────────────
        Route::middleware('permission:settings.manage')->group(function () {
            Route::redirect('/settings', '/admin/settings/general');
            Route::livewire('/settings/general', 'pages::admin.settings.general')->name('settings.general');
            Route::livewire('/settings/website', 'pages::admin.settings.website')->name('settings.website');
            Route::livewire('/settings/app', 'pages::admin.settings.app')->name('settings.app');
            Route::livewire('/settings/financial', 'pages::admin.settings.financial')->name('settings.financial');
            Route::livewire('/settings/system', 'pages::admin.settings.system')->name('settings.system');
            Route::livewire('/settings/other', 'pages::admin.settings.other')->name('settings.other');
            Route::livewire('/activity/{logName}', 'pages::admin.activity.index')->name('activity.show');
            Route::livewire('/activity/{logName}/{id}', 'pages::admin.activity.item')->name('activity.item');
        });

        // ── Staff management ─────────────────────────────────────────────────
        Route::middleware('permission:staff.manage')->group(function () {
            Route::livewire('/staff', 'pages::admin.staff.index')->name('staff.index');
            Route::livewire('/staff/create', 'pages::admin.staff.create')->name('staff.create');
            Route::livewire('/users/create', 'pages::admin.users.create')->name('users.create');
            Route::livewire('/users/{user}/edit', 'pages::admin.users.edit')->name('users.edit');
        });

        // ── Roles & Permissions ───────────────────────────────────────────────
        Route::middleware('permission:roles.manage')->group(function () {
            Route::livewire('/roles', 'pages::admin.roles.index')->name('roles.index');
            Route::livewire('/roles/create', 'pages::admin.roles.form')->name('roles.create');
            Route::livewire('/roles/{role}/edit', 'pages::admin.roles.form')->name('roles.edit');
            Route::livewire('/permissions', 'pages::admin.permissions.index')->name('permissions.index');
        });
    });
