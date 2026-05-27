<?php

use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Customer self-service (authenticated)
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('account', 'pages.account.dashboard')->name('account.dashboard');
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
    Route::livewire('account/settings/appearance', 'pages::account.settings.appearance')->name('appearance.edit');

    Route::livewire('account/settings/security', 'pages::account.settings.security')
        ->middleware(['password.confirm'])
        ->name('security.edit');
});
