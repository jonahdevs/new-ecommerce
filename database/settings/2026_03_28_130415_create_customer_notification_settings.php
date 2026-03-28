<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('customer_notifications.order_confirmation', true);
        $this->migrator->add('customer_notifications.order_processing', true);
        $this->migrator->add('customer_notifications.order_shipped', true);
        $this->migrator->add('customer_notifications.order_delivered', true);
        $this->migrator->add('customer_notifications.order_cancelled', true);
        $this->migrator->add('customer_notifications.order_refunded', true);
        $this->migrator->add('customer_notifications.abandoned_cart', false);
        $this->migrator->add('customer_notifications.abandoned_cart_delay', 1); // hours
        $this->migrator->add('customer_notifications.review_request', false);
        $this->migrator->add('customer_notifications.review_request_delay', 3); // days after delivery
    }
};
