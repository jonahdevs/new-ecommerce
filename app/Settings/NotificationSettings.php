<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class NotificationSettings extends Settings
{
    // Admin Alerts
    public bool $notify_new_order;

    public bool $notify_low_stock;

    public bool $notify_new_review;

    public bool $notify_new_user;

    public bool $notify_failed_payment;

    public bool $notify_out_of_stock;

    public bool $notify_new_quote;

    public bool $notify_quote_accepted;

    public bool $notify_quote_rejected;

    // Notification channels (system-wide)
    public bool $email_notifications_enabled;

    public bool $sms_notifications_enabled;

    public bool $push_notifications_enabled;

    // Recipient
    public ?string $admin_notification_email;

    public static function group(): string
    {
        return 'notifications';
    }
}
