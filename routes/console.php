<?php

use App\Jobs\CleanupExpiredOrders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ==============================================
// PRODUCTS: Auto-publish scheduled products
//
// Runs every minute. Finds SCHEDULED products whose published_at has
// passed and transitions them to PUBLISHED.
//
// everyMinute() ensures publish times are honoured within a ~1 min window.
// withoutOverlapping() prevents a second run starting if the previous is
// still in progress.
// ==============================================
Schedule::command('products:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

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
// ORDERS: Auto-cancel unpaid orders
//
// Runs hourly. Cancels PENDING orders that have not been paid within
// the auto_cancel_hours window configured in OrderSettings.
//
// Only executes when auto_cancel_unpaid is enabled in settings — the
// command itself performs this guard so no wasted runs.
// ==============================================
Schedule::command('orders:cancel-unpaid')
    ->hourly()
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

// ==============================================
// BACKUPS: Automated backup management
//
// Daily database backup at 2:00 AM - captures all data changes
// Weekly full backup on Sundays at 3:00 AM - includes files and database
// Monthly cleanup on first day at 4:00 AM - removes old backups per retention policy
// Daily health monitoring at 6:00 AM - checks backup integrity
// ==============================================

// Daily database backup
Schedule::command('backup:scheduled database --notify')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Weekly full backup (Sundays)
Schedule::command('backup:scheduled full --notify')
    ->weeklyOn(0, '03:00') // 0 = Sunday
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Monthly cleanup (first day of month)
Schedule::command('backup:clean')
    ->monthlyOn(1, '04:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Daily backup health monitoring
Schedule::command('backup:monitor')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/backup.log'));
