<?php

namespace App\Listeners;

use App\Services\CartService;
use Illuminate\Auth\Events\Login;

class SyncCartOnLogin
{
    protected CartService $cartService;

    /**
     * Create the event listener.
     */
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $oldSessionId = session()->getId();

        $this->cartService->mergeGuestCart($oldSessionId);
    }
}
