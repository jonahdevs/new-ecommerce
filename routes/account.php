<?php

use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Customer self-service (authenticated + verified)
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('account', 'pages::account.dashboard')->name('account.dashboard');
    Route::livewire('account/orders', 'pages::account.orders.index')->name('account.orders.index');
    Route::livewire('account/orders/{order}', 'pages::account.orders.show')->name('account.orders.show');
    Route::livewire('account/quotes', 'pages::account.quotes.index')->name('account.quotes.index');
    Route::livewire('account/quotes/{quote}', 'pages::account.quotes.show')->name('account.quotes.show');
    Route::livewire('account/addresses', 'pages::account.addresses.index')->name('account.addresses.index');
});

// ---------------------------------------------------------------------------
// Settings — URLs live under /account/settings/* but route names are kept
// short (profile.edit / security.edit / appearance.edit) so existing layout
// and component references don't need to change.
// ---------------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    Route::redirect('account/settings', 'account/settings/profile');

    Route::livewire('account/settings/profile', 'pages::account.settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('account/settings/notifications', 'pages::account.settings.notifications')->name('notifications.edit');
    Route::livewire('account/settings/appearance', 'pages::account.settings.appearance')->name('appearance.edit');

    Route::livewire('account/settings/security', 'pages::account.settings.security')
        ->middleware(['password.confirm'])
        ->name('security.edit');
});
