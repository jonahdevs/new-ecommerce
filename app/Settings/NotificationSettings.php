<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class NotificationSettings extends Settings
{
    // ==================================================
    // CHANNELS
    // ==================================================
    public bool $email_channel_enabled = true;

    public bool $inapp_channel_enabled = true;

    public bool $whatsapp_channel_enabled = false;

    public ?string $whatsapp_api_token = null;

    public ?string $whatsapp_phone_number_id = null;

    public ?string $whatsapp_business_account_id = null;

    // ==================================================
    // CUSTOMER — ORDERS & SHIPPING
    // ==================================================
    public bool $customer_order_confirmation_email = true;

    public bool $customer_order_confirmation_inapp = true;

    public bool $customer_order_confirmation_whatsapp = false;

    public bool $customer_order_updates_email = true;

    public bool $customer_order_updates_inapp = true;

    public bool $customer_order_updates_whatsapp = false;

    // ==================================================
    // CUSTOMER — QUOTATIONS
    // ==================================================
    public bool $customer_quote_received_email = true;

    public bool $customer_quote_received_inapp = true;

    public bool $customer_quote_received_whatsapp = false;

    public bool $customer_quote_updates_email = true;

    public bool $customer_quote_updates_inapp = true;

    public bool $customer_quote_updates_whatsapp = false;

    // ==================================================
    // CUSTOMER — MARKETING & ACCOUNT
    // ==================================================
    public bool $customer_marketing_email = true;

    public bool $customer_marketing_inapp = false;

    public bool $customer_marketing_whatsapp = false;

    public bool $customer_account_security_email = true;

    public bool $customer_account_security_inapp = true;

    public bool $customer_account_security_whatsapp = false;

    // ==================================================
    // STAFF — ORDERS & PAYMENTS
    // ==================================================
    public bool $staff_new_order_email = true;

    public bool $staff_new_order_inapp = true;

    public bool $staff_new_order_whatsapp = false;

    // ==================================================
    // STAFF — CUSTOMERS & REVIEWS
    // ==================================================
    public bool $staff_new_review_email = true;

    public bool $staff_new_review_inapp = true;

    public bool $staff_new_review_whatsapp = false;

    // ==================================================
    // STAFF — INVENTORY
    // ==================================================
    public bool $staff_low_stock_email = true;

    public bool $staff_low_stock_inapp = true;

    public bool $staff_low_stock_whatsapp = false;

    // ==================================================
    // STAFF — QUOTATIONS
    // ==================================================
    public bool $staff_new_quote_email = true;

    public bool $staff_new_quote_inapp = true;

    public bool $staff_new_quote_whatsapp = false;

    public bool $staff_quote_decision_email = true;

    public bool $staff_quote_decision_inapp = true;

    public bool $staff_quote_decision_whatsapp = false;

    public static function group(): string
    {
        return 'notifications';
    }
}
