<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ==================================================
        // ROUTING
        // ==================================================
        $this->migrator->add('notifications.staff_email_routing', 'central');
        $this->migrator->add('notifications.staff_central_email', 'notifications@sheffieldsteelsystems.com');

        // ==================================================
        // CHANNELS
        // ==================================================
        $this->migrator->add('notifications.email_channel_enabled', true);
        $this->migrator->add('notifications.inapp_channel_enabled', true);
        $this->migrator->add('notifications.whatsapp_channel_enabled', false);
        $this->migrator->addEncrypted('notifications.whatsapp_api_token', null);
        $this->migrator->add('notifications.whatsapp_phone_number_id', null);
        $this->migrator->add('notifications.whatsapp_business_account_id', null);

        // ==================================================
        // CUSTOMER — ORDERS & SHIPPING
        // ==================================================
        $this->migrator->add('notifications.customer_order_confirmation_email', true);
        $this->migrator->add('notifications.customer_order_confirmation_inapp', true);
        $this->migrator->add('notifications.customer_order_confirmation_whatsapp', false);

        $this->migrator->add('notifications.customer_order_updates_email', true);
        $this->migrator->add('notifications.customer_order_updates_inapp', true);
        $this->migrator->add('notifications.customer_order_updates_whatsapp', false);

        // ==================================================
        // CUSTOMER — QUOTATIONS
        // ==================================================
        $this->migrator->add('notifications.customer_quote_received_email', true);
        $this->migrator->add('notifications.customer_quote_received_inapp', true);
        $this->migrator->add('notifications.customer_quote_received_whatsapp', false);

        $this->migrator->add('notifications.customer_quote_updates_email', true);
        $this->migrator->add('notifications.customer_quote_updates_inapp', true);
        $this->migrator->add('notifications.customer_quote_updates_whatsapp', false);

        // ==================================================
        // CUSTOMER — MARKETING & ACCOUNT
        // ==================================================
        $this->migrator->add('notifications.customer_marketing_email', true);
        $this->migrator->add('notifications.customer_marketing_inapp', false);
        $this->migrator->add('notifications.customer_marketing_whatsapp', false);

        $this->migrator->add('notifications.customer_account_security_email', true);
        $this->migrator->add('notifications.customer_account_security_inapp', true);
        $this->migrator->add('notifications.customer_account_security_whatsapp', false);

        // ==================================================
        // STAFF — ORDERS & PAYMENTS
        // ==================================================
        $this->migrator->add('notifications.staff_new_order_email', true);
        $this->migrator->add('notifications.staff_new_order_inapp', true);
        $this->migrator->add('notifications.staff_new_order_whatsapp', false);

        // ==================================================
        // STAFF — CUSTOMERS & REVIEWS
        // ==================================================
        $this->migrator->add('notifications.staff_new_review_email', true);
        $this->migrator->add('notifications.staff_new_review_inapp', true);
        $this->migrator->add('notifications.staff_new_review_whatsapp', false);

        // ==================================================
        // STAFF — INVENTORY
        // ==================================================
        $this->migrator->add('notifications.staff_low_stock_email', true);
        $this->migrator->add('notifications.staff_low_stock_inapp', true);
        $this->migrator->add('notifications.staff_low_stock_whatsapp', false);

        // ==================================================
        // STAFF — QUOTATIONS
        // ==================================================
        $this->migrator->add('notifications.staff_new_quote_email', true);
        $this->migrator->add('notifications.staff_new_quote_inapp', true);
        $this->migrator->add('notifications.staff_new_quote_whatsapp', false);
        $this->migrator->add('notifications.staff_quote_decision_email', true);
        $this->migrator->add('notifications.staff_quote_decision_inapp', true);
        $this->migrator->add('notifications.staff_quote_decision_whatsapp', false);
    }
};
