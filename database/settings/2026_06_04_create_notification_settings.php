<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Staff email routing
        $this->migrator->add('notifications.staff_email_routing', 'individual');
        $this->migrator->add('notifications.staff_central_email', 'notifications@sheffieldsteelsystems.com');

        // Channels
        $this->migrator->add('notifications.email_channel_enabled', true);
        $this->migrator->add('notifications.inapp_channel_enabled', true);
        $this->migrator->add('notifications.whatsapp_channel_enabled', false);
        $this->migrator->addEncrypted('notifications.whatsapp_api_token', null);
        $this->migrator->add('notifications.whatsapp_phone_number_id', null);
        $this->migrator->add('notifications.whatsapp_business_account_id', null);

        // Customer — Orders & Shipping
        $this->migrator->add('notifications.customer_order_confirmation_email', true);
        $this->migrator->add('notifications.customer_order_confirmation_inapp', true);
        $this->migrator->add('notifications.customer_order_confirmation_whatsapp', false);

        $this->migrator->add('notifications.customer_order_updates_email', true);
        $this->migrator->add('notifications.customer_order_updates_inapp', true);
        $this->migrator->add('notifications.customer_order_updates_whatsapp', false);

        // Customer — Quotations
        $this->migrator->add('notifications.customer_quote_received_email', true);
        $this->migrator->add('notifications.customer_quote_received_inapp', true);
        $this->migrator->add('notifications.customer_quote_received_whatsapp', false);

        $this->migrator->add('notifications.customer_quote_updates_email', true);
        $this->migrator->add('notifications.customer_quote_updates_inapp', true);
        $this->migrator->add('notifications.customer_quote_updates_whatsapp', false);

        // Customer — Marketing & Account
        $this->migrator->add('notifications.customer_marketing_email', true);
        $this->migrator->add('notifications.customer_marketing_inapp', false);
        $this->migrator->add('notifications.customer_marketing_whatsapp', false);

        $this->migrator->add('notifications.customer_account_security_email', true);
        $this->migrator->add('notifications.customer_account_security_inapp', true);
        $this->migrator->add('notifications.customer_account_security_whatsapp', false);

        // Staff — Orders & Payments
        $this->migrator->add('notifications.staff_new_order_email', true);
        $this->migrator->add('notifications.staff_new_order_inapp', true);
        $this->migrator->add('notifications.staff_new_order_whatsapp', false);

        // Staff — Customers & Reviews
        $this->migrator->add('notifications.staff_new_review_email', true);
        $this->migrator->add('notifications.staff_new_review_inapp', true);
        $this->migrator->add('notifications.staff_new_review_whatsapp', false);

        // Staff — Inventory
        $this->migrator->add('notifications.staff_low_stock_email', true);
        $this->migrator->add('notifications.staff_low_stock_inapp', true);
        $this->migrator->add('notifications.staff_low_stock_whatsapp', false);

        // Staff — Quotations
        $this->migrator->add('notifications.staff_new_quote_email', true);
        $this->migrator->add('notifications.staff_new_quote_inapp', true);
        $this->migrator->add('notifications.staff_new_quote_whatsapp', false);
        $this->migrator->add('notifications.staff_quote_decision_email', true);
        $this->migrator->add('notifications.staff_quote_decision_inapp', true);
        $this->migrator->add('notifications.staff_quote_decision_whatsapp', false);
    }
};
