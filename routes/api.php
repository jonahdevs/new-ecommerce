<?php

use App\Http\Controllers\Api\SapProductSyncController;
use App\Http\Controllers\Webhooks\MpesaWebhookController;
use App\Http\Controllers\Webhooks\PesawiseWebhookController;
use App\Http\Controllers\Webhooks\SapWebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('webhooks')
    ->name('webhooks.')
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
    ])
    ->group(function () {
        Route::post('/pesawise', PesawiseWebhookController::class)->name('pesawise');
        Route::post('/mpesa', MpesaWebhookController::class)->name('mpesa');
        Route::post('/stripe', StripeWebhookController::class)->name('stripe');
        Route::post('/sap', SapWebhookController::class)->name('sap');
    });

// SAP Product Sync API - Batch only
Route::post('/products/sync', SapProductSyncController::class)
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
    ])
    ->name('products.sync');
