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
    public bool $notify_new_quote;           // New quote request received
    public bool $notify_quote_accepted;      // Customer accepted a quote
    public bool $notify_quote_rejected;      // Customer rejected a quote
    public ?string $admin_notification_email;  // defaults to store email if null

    public static function group(): string
    {
        return 'notifications';
    }
}
