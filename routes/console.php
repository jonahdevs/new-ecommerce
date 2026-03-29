<?php

use App\Jobs\CleanupExpiredOrders;
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


// ==============================================
// QUOTATIONS: Send expiring reminders
//
// Runs daily at 9 AM. Sends reminder emails to customers whose
// quotes are expiring within the configured reminder window.
//
// Only sends one reminder per quote (tracks via reminder_sent_at).
// Respects customer_notifications.quote_expiring_reminder setting.
// ==============================================
Schedule::command('quotations:remind-expiring')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));


// ==============================================
// ORDERS: Cleanup expired orders and reservations
//
// Runs every 5 minutes. Finds pending orders with expired payments
// and releases their inventory reservations.
//
// This prevents phantom stock depletion when users abandon checkout
// or payment links expire without completion.
// ==============================================
Schedule::job(new CleanupExpiredOrders)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
