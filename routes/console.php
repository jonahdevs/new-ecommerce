<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Flip scheduled products live once their publish time has passed.
Schedule::command('products:publish-scheduled')->everyFiveMinutes();

// Regenerate the static sitemap.xml daily.
Schedule::command('sitemap:generate')->daily();

// Flip sent quotes whose validity window has lapsed to expired.
Schedule::command('quotes:expire')->daily();

// Email customers about carts they left idle (abandoned-cart reminders).
Schedule::command('cart:remind-abandoned')->everyFifteenMinutes();

// Erase raw gateway payloads (PII) past the 5-year retention window — DPA 2019
// storage limitation; structured payment columns are retained for the record.
Schedule::command('payments:prune-payloads')->weekly();
