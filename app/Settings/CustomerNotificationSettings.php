<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CustomerNotificationSettings extends Settings
{
    // Order notifications
    public bool $order_confirmation;   // email on order placed

    public bool $order_updates;        // email when order status changes (processing/shipped/delivered/cancelled/refunded)

    // Cart notifications
    public bool $abandoned_cart;          // email for abandoned carts

    public int $abandoned_cart_delay;    // hours before sending abandoned cart email

    // Review notifications
    public bool $review_request;          // email requesting review after delivery

    public int $review_request_delay;    // days after delivery to send review request

    // Quotation notifications
    public bool $quote_sent;              // email when quote is sent to customer

    public bool $quote_expiring_reminder; // reminder before quote expires

    public int $quote_expiring_days;     // days before expiry to send reminder

    public static function group(): string
    {
        return 'customer_notifications';
    }
}
