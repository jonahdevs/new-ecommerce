<?php

namespace App\Listeners;

use App\Services\ProductService;

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
