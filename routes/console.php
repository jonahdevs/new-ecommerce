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
