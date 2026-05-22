<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Admin alert toggles
        $this->migrator->add('notifications.notify_new_order', true);
        $this->migrator->add('notifications.notify_low_stock', true);
        $this->migrator->add('notifications.notify_out_of_stock', true);
        $this->migrator->add('notifications.notify_new_review', false);
        $this->migrator->add('notifications.notify_new_user', false);
        $this->migrator->add('notifications.notify_failed_payment', true);
        $this->migrator->add('notifications.notify_new_quote', true);
        $this->migrator->add('notifications.notify_quote_accepted', true);
        $this->migrator->add('notifications.notify_quote_rejected', false);

        // System channels
        $this->migrator->add('notifications.email_notifications_enabled', true);
        $this->migrator->add('notifications.sms_notifications_enabled', false);
        $this->migrator->add('notifications.push_notifications_enabled', false);

        // Recipient
        $this->migrator->add('notifications.admin_notification_email', null);

        // Customer notification toggles
        $this->migrator->add('customer_notifications.order_confirmation', true);
        $this->migrator->add('customer_notifications.order_processing', true);
        $this->migrator->add('customer_notifications.order_shipped', true);
        $this->migrator->add('customer_notifications.order_delivered', true);
        $this->migrator->add('customer_notifications.order_cancelled', true);
        $this->migrator->add('customer_notifications.order_refunded', true);
        $this->migrator->add('customer_notifications.abandoned_cart', false);
        $this->migrator->add('customer_notifications.abandoned_cart_delay', 1);
        $this->migrator->add('customer_notifications.review_request', false);
        $this->migrator->add('customer_notifications.review_request_delay', 3);
        $this->migrator->add('customer_notifications.quote_sent', true);
        $this->migrator->add('customer_notifications.quote_expiring_reminder', true);
        $this->migrator->add('customer_notifications.quote_expiring_days', 2);
        $this->migrator->add('customer_notifications.quote_received', true);
    }
};
