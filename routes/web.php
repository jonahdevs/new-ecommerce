<?php

use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Storefront (guests + logged-in browsing)
// ---------------------------------------------------------------------------
Route::livewire('/', 'pages::storefront.home')->name('home');

// ---------------------------------------------------------------------------
// Post-login landing — branches by role.
// Customers go to their account dashboard; admins are bounced to /admin.
// TODO: swap the hasRole check for spatie/laravel-permission once installed.
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'verified'])->get('/dashboard', function () {
    $user = auth()->user();

    if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('account.dashboard');
})->name('dashboard');

require __DIR__.'/account.php';
require __DIR__.'/admin.php';
