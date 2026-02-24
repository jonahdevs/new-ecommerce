<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth', 'staff', 'verified'])->prefix('admin/settings')->group(function () {
    Route::redirect('/', 'settings/profile');

    Route::livewire('/profile', 'pages::admin.settings.profile')->name('profile.edit');
    Route::livewire('/password', 'pages::admin.settings.password')->name('user-password.edit');
    Route::livewire('/appearance', 'pages::admin.settings.appearance')->name('appearance.edit');

    Route::livewire('/two-factor', 'pages::admin.settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    Route::livewire('general', 'pages::admin.settings.system.general')->name('admin.settings.general');
    Route::livewire('mail', 'pages::admin.settings.system.mail')->name('admin.settings.mail');
    Route::livewire('maintenance', 'pages::admin.settings.system.maintenance')->name('admin.settings.maintenance');
    Route::livewire('payment', 'pages::admin.settings.system.payment')->name('admin.settings.payment');
    Route::livewire('seo', 'pages::admin.settings.system.seo')->name('admin.settings.seo');
    Route::livewire('shipping', 'pages::admin.settings.system.shipping')->name('admin.settings.shipping');
    Route::livewire('social', 'pages::admin.settings.system.social')->name('admin.settings.social');
});

Route::middleware(['auth', 'verified'])->group(function () {});
