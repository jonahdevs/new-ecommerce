<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;

class PaypalSettings extends Settings
{
    public bool $enabled;
    public string $environment;       // sandbox | live
    public ?string $client_id;         // not encrypted (semi-public)

    #[ShouldBeEncrypted]
    public ?string $client_secret;

    public static function group(): string
    {
        return 'paypal';
    }
}
