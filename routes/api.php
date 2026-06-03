<?php

use App\Http\Controllers\Integrations\SapSyncController;
use App\Http\Middleware\VerifySapSecret;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/products/sync', SapSyncController::class)
    ->middleware(VerifySapSecret::class)
    ->name('products.sync');
