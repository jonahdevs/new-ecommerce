<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');  
Route::livewire('/products', 'pages::products')->name('products');  

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
