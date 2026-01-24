<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');

// Products Routes
Route::livewire('/products', 'pages::products')->name('products');
Route::livewire('/products/{product:slug}', 'pages::product-details')->name('products.show');
Route::livewire('/wishlist', 'pages::wishlist')->name('wishlist');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__ . '/settings.php';
