<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    // Active gateway & mode
    public string $active_gateway;
    public string $mode; // sandbox | production

    // Pesawise
    public ?string $pesawise_api_key;
    public ?string $pesawise_api_secret;
    public ?string $pesawise_account_number;
    public ?string $pesawise_webhook_secret;

    // Paystack
    public ?string $paystack_public_key;
    public ?string $paystack_secret_key;
    public ?string $paystack_webhook_secret;

    // Stripe
    public ?string $stripe_public_key;
    public ?string $stripe_secret_key;
    public ?string $stripe_webhook_secret;

    // PayPal
    public ?string $paypal_client_id;
    public ?string $paypal_client_secret;
    public ?string $paypal_webhook_id;

    // Custom / Manual
    public ?string $custom_name;
    public ?string $custom_instructions;

    public static function group(): string
    {
        return 'payment';
    }

    public static function encrypted(): array
    {
        return [
            'pesawise_api_secret',
            'pesawise_webhook_secret',
            'paystack_secret_key',
            'paystack_webhook_secret',
            'stripe_secret_key',
            'stripe_webhook_secret',
            'paypal_client_secret',
            'paypal_webhook_id',
        ];
    }
}
