<?php

namespace App\Services;

use App\Settings\PaymentApiSettings;
use App\Settings\PaymentSettings;

/**
 * Single source of truth for payment gateway credentials.
 * Settings stored in the database take priority; values fall back to the
 * corresponding config/env entry when the setting is null or empty.
 */
class PaymentCredentials
{
    public function __construct(
        private PaymentApiSettings $apiSettings,
        private PaymentSettings $paymentSettings,
    ) {}

    // -------------------------------------------------------------------------
    // Stripe
    // -------------------------------------------------------------------------

    public function stripeKey(): string
    {
        return $this->apiSettings->stripe_key ?: (string) config('services.stripe.key', '');
    }

    public function stripeSecret(): string
    {
        return $this->apiSettings->stripe_secret ?: (string) config('services.stripe.secret', '');
    }

    public function stripeWebhookSecret(): string
    {
        return $this->apiSettings->stripe_webhook_secret ?: (string) config('services.stripe.webhook_secret', '');
    }

    // -------------------------------------------------------------------------
    // M-Pesa
    // -------------------------------------------------------------------------

    public function mpesaEnv(): string
    {
        return $this->apiSettings->mpesa_env ?: (string) config('services.mpesa.env', 'sandbox');
    }

    public function mpesaConsumerKey(): string
    {
        return $this->apiSettings->mpesa_consumer_key ?: (string) config('services.mpesa.consumer_key', '');
    }

    public function mpesaConsumerSecret(): string
    {
        return $this->apiSettings->mpesa_consumer_secret ?: (string) config('services.mpesa.consumer_secret', '');
    }

    public function mpesaPasskey(): string
    {
        return $this->apiSettings->mpesa_passkey ?: (string) config('services.mpesa.passkey', '');
    }

    public function mpesaShortcode(): string
    {
        return $this->paymentSettings->mpesa_shortcode ?: (string) config('services.mpesa.shortcode', '174379');
    }

    public function mpesaCallbackUrl(): string
    {
        return $this->apiSettings->mpesa_callback_url ?: (string) config('services.mpesa.callback_url', '');
    }

    /**
     * Build the config array that DarajaClient expects.
     *
     * @return array<string, string>
     */
    public function mpesaConfig(): array
    {
        return [
            'env' => $this->mpesaEnv(),
            'consumer_key' => $this->mpesaConsumerKey(),
            'consumer_secret' => $this->mpesaConsumerSecret(),
            'passkey' => $this->mpesaPasskey(),
            'shortcode' => $this->mpesaShortcode(),
            'callback_url' => $this->mpesaCallbackUrl(),
        ];
    }
}
