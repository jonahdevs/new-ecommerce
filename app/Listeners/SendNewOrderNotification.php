<?php

namespace App\Listeners;

use App\Events\PaymentConfirmed;
use App\Notifications\NewOrderNotification;
use App\Settings\NotificationSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendNewOrderNotification
{
    public function __construct(
        private readonly NotificationSettings $notificationSettings
    ) {}

    public function handle(PaymentConfirmed $event): void
    {
        if (!$this->notificationSettings->notify_new_order) {
            return;
        }

        try {
            $adminEmail = $this->notificationSettings->admin_notification_email
                ?? config('mail.from.address');

            Notification::route('mail', $adminEmail)
                ->notify(new NewOrderNotification($event->order));

            Log::info('New order notification sent to admin', [
                'order_id' => $event->order->id,
                'reference' => $event->order->reference,
                'admin_email' => $adminEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send new order notification to admin', [
                'order_id' => $event->order->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
