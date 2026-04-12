<?php

namespace App\Listeners;

use App\Mail\WelcomeMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    public function handle(Registered $event): void
    {
        // Only send to customers, not staff accounts
        if ($event->user->hasRole(['admin', 'super_admin', 'manager', 'staff'])) {
            return;
        }

        Mail::to($event->user->email)->queue(new WelcomeMail($event->user));
    }
}
