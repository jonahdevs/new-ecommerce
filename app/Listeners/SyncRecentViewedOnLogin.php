<?php

namespace App\Listeners;

use App\Services\ProductService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncRecentViewedOnLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        app(ProductService::class)->syncRecentlyViewedOnLogin();
    }
}
