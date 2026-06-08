<?php

namespace App\Settings;

use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class PaymentApiSettings extends Settings
{
    public ?string $mpesa_env;

    #[ShouldBeEncrypted]
    public ?string $mpesa_consumer_key;

    #[ShouldBeEncrypted]
    public ?string $mpesa_consumer_secret;

    #[ShouldBeEncrypted]
    public ?string $mpesa_passkey;

    public ?string $mpesa_callback_url;

    public ?string $stripe_key;

    #[ShouldBeEncrypted]
    public ?string $stripe_secret;

    #[ShouldBeEncrypted]
    public ?string $stripe_webhook_secret;

    public static function group(): string
    {
        return 'payment_api';
    }
}
