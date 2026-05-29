<?php

use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Admin / Staff
// TODO: add ->middleware('role:admin') once spatie/laravel-permission is installed.
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/', 'pages.admin.dashboard')->name('dashboard');
        Route::livewire('/delivery-zones', 'pages::admin.delivery-zones')->name('delivery-zones');
    });
