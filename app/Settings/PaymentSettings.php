<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    // Active gateway — only one at a time
    public string $active_gateway; // pesawise | pesapal | paypal | custom

    // ── Pesawise ──
    public bool    $pesawise_mode_production;
    public ?string $pesawise_api_key;
    public ?string $pesawise_api_secret;
    public ?string $pesawise_account_number;
    public ?string $pesawise_webhook_secret;

    // ── Pesapal ──
    public bool    $pesapal_mode_production;
    public ?string $pesapal_consumer_key;
    public ?string $pesapal_consumer_secret;
    public ?string $pesapal_webhook_secret;
    public ?string $pesapal_ipn_id;

    // ── PayPal ──
    public bool    $paypal_mode_production;
    public ?string $paypal_client_id;
    public ?string $paypal_client_secret;
    public ?string $paypal_webhook_id;


    // Stripe
    public bool    $stripe_mode_production;
    public ?string $stripe_public_key;
    public ?string $stripe_secret_key;
    public ?string $stripe_webhook_secret;

    // M-Pesa Daraja
    public bool    $mpesa_mode_production;
    public ?string $mpesa_consumer_key;
    public ?string $mpesa_consumer_secret;
    public ?string $mpesa_shortcode;
    public ?string $mpesa_passkey;
    public ?string $mpesa_callback_url;

    public static function group(): string
    {
        return 'payment';
    }

    public static function encrypted(): array
    {
        return [
            // Pesawise
            'pesawise_api_secret',
            'pesawise_webhook_secret',

            // Pesapal
            'pesapal_consumer_secret',
            'pesapal_webhook_secret',

            // PayPal
            'paypal_client_secret',
            'paypal_webhook_id',

            // Stripe
            'stripe_secret_key',
            'stripe_webhook_secret',

            // M-Pesa
            'mpesa_consumer_secret',
            'mpesa_passkey',
        ];
    }
}
