<?php

namespace App\Listeners;

use App\Notifications\NewUserNotification;
use App\Settings\NotificationSettings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendNewUserNotification
{
    public function __construct(
        private readonly NotificationSettings $notificationSettings
    ) {}

    public function handle(Registered $event): void
    {
        // Only notify for customer registrations, not staff
        if ($event->user->hasRole(['admin', 'super_admin', 'manager', 'staff'])) {
            return;
        }

        if (! $this->notificationSettings->notify_new_user) {
            return;
        }

        try {
            $adminEmail = $this->notificationSettings->admin_notification_email
                ?? config('mail.from.address');

            Notification::route('mail', $adminEmail)
                ->notify(new NewUserNotification($event->user));

            Log::info('New user notification sent to admin', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'admin_email' => $adminEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send new user notification to admin', [
                'user_id' => $event->user->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
