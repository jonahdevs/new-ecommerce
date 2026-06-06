<?php

use App\Http\Controllers\Integrations\SapSyncController;
use App\Http\Controllers\Integrations\SapWebhookController;
use App\Http\Middleware\VerifySapSecret;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware([VerifySapSecret::class])->group(function () {
    Route::post('/webhooks/sap', SapWebhookController::class)
        ->name('webhooks.sap');

    Route::post('/products/sync', SapSyncController::class)
        ->name('products.sync');
});
