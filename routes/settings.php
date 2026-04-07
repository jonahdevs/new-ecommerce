<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;


Route::middleware(['auth', 'staff', 'verified'])
    ->prefix('admin/settings')
    ->group(function () {

        // Account (personal)
        Route::redirect('/', 'admin/settings/account/profile');
        Route::livewire('account/profile', 'pages::admin.settings.account.profile')->name('profile.edit');
        Route::livewire('account/password', 'pages::admin.settings.account.password')->name('user-password.edit');
        Route::livewire('account/appearance', 'pages::admin.settings.account.appearance')->name('appearance.edit');
        Route::livewire('account/two-factor', 'pages::admin.settings.account.two-factor')->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )->name('two-factor.show');

        // General
        Route::livewire('general/store-info', 'pages::admin.settings.general.store-info')->name('settings.store-info');
        Route::livewire('general/localization', 'pages::admin.settings.general.localization')->name('settings.localization');
        Route::livewire('general/regional', 'pages::admin.settings.general.regional')->name('settings.regional');

        // Commerce
        Route::livewire('commerce/orders', 'pages::admin.settings.commerce.orders')->name('settings.orders');
        Route::livewire('commerce/quotations', 'pages::admin.settings.commerce.quotations')->name('settings.quotations');
        Route::livewire('commerce/tax', 'pages::admin.settings.commerce.tax')->name('settings.tax');
        Route::livewire('commerce/tax-classes', 'pages::admin.settings.commerce.tax-classes')->name('settings.tax-classes');
        Route::livewire('commerce/reviews', 'pages::admin.settings.commerce.reviews')->name('settings.reviews');
        Route::livewire('commerce/inventory', 'pages::admin.settings.commerce.inventory')->name('settings.inventory');

        // Payments
        Route::livewire('payments/gateways', 'pages::admin.settings.payments.gateways')->name('settings.payments.gateways');
        Route::livewire('payments/mpesa', 'pages::admin.settings.payments.mpesa')->name('settings.payments.mpesa');
        Route::livewire('payments/stripe', 'pages::admin.settings.payments.stripe')->name('settings.payments.stripe');
        Route::livewire('payments/paypal', 'pages::admin.settings.payments.paypal')->name('settings.payments.paypal');
        Route::livewire('payments/pesapal', 'pages::admin.settings.payments.pesapal')->name('settings.payments.pesapal');
        Route::livewire('payments/pesawise', 'pages::admin.settings.payments.pesawise')->name('settings.payments.pesawise');
        Route::livewire('payments/cod', 'pages::admin.settings.payments.cod')->name('settings.payments.cod');

        // Notifications
        Route::livewire('notifications/mail', 'pages::admin.settings.notifications.mail')->name('settings.mail');
        Route::livewire('notifications/admin-alerts', 'pages::admin.settings.notifications.admin-alerts')->name('settings.admin-alerts');
        Route::livewire('notifications/customer-emails', 'pages::admin.settings.notifications.customer-emails')->name('settings.customer-emails');

        // SEO & Marketing
        Route::livewire('seo/seo', 'pages::admin.settings.seo.seo')->name('settings.seo');
        Route::livewire('seo/social', 'pages::admin.settings.seo.social')->name('settings.social');

        // System
        Route::livewire('system/maintenance', 'pages::admin.settings.system.maintenance')->name('settings.maintenance');
    });

Route::middleware(['auth', 'verified'])->group(function () { });
