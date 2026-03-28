<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CustomerNotificationSettings extends Settings
{
    public bool $order_confirmation;       // email on order placed
    public bool $order_processing;        // email when order moves to processing
    public bool $order_shipped;           // email when order is shipped
    public bool $order_delivered;         // email when order is delivered
    public bool $order_cancelled;         // email when order is cancelled
    public bool $order_refunded;          // email when order is refunded
    public bool $abandoned_cart;          // email for abandoned carts
    public int $abandoned_cart_delay;    // hours before sending abandoned cart email
    public bool $review_request;          // email requesting review after delivery
    public int $review_request_delay;    // days after delivery to send review request

    public static function group(): string
    {
        return 'customer_notifications';
    }
}
