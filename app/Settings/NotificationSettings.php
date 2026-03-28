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
    public ?string $admin_notification_email;  // defaults to store email if null

    public static function group(): string
    {
        return 'notifications';
    }
}
