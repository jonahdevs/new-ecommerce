<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\StorefrontSession;
use Illuminate\Auth\Events\Login;

/**
 * On every authentication (login, registration, 2FA, passkey), merge the guest's
 * session cart into the user's persisted cart and rehydrate the session from the
 * result — so items added before signing in are never lost and a saved cart
 * follows the user across devices.
 */
class SyncCartOnLogin
{
    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            StorefrontSession::mergeIntoUserCart($event->user);
        }
    }
}
