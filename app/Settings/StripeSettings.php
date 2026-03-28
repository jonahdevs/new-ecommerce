<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;


class StripeSettings extends Settings
{
    public bool $enabled;
    public string $environment;        // sandbox | live
    public ?string $public_key;         // publishable key — not encrypted (safe to expose)

    #[ShouldBeEncrypted]
    public ?string $secret_key;

    #[ShouldBeEncrypted]
    public ?string $webhook_secret;

    public static function group(): string
    {
        return 'stripe';
    }
}
