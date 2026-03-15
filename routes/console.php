<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



// ==============================================
// QUOTATIONS: Expire overdure quotes
//
// Runs every hour. Finds all QUOTE_SENT quotations whose expires_at is in the past and transitions them to QUOTE_EXPIRED via QuotationService.
//
// hourly() = fires once per hour at minute 0 (e.g. 09:00, 10:00, 11:00)
//
// withoutOverlapping() prevents a second instance starting if the previous run is still processing - safe for large datasets
//
// onOneServer() ensures this only runs on one server - prevents duplicate transitions on the same quotations
//
// runInBackground() lets other scheduled tasks run in parallel rather than waiting for this one to finish first.
// ==============================================
Schedule::command('quotations:expire')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
