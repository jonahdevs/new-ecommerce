<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;

class PesapalSettings extends Settings
{
    public bool $enabled;
    public string $environment;       // sandbox | live
    public ?string $ipn_id;
    public ?string $callback_url;

    #[ShouldBeEncrypted]
    public ?string $consumer_key;

    #[ShouldBeEncrypted]
    public ?string $consumer_secret;

    public static function group(): string
    {
        return 'pesapal';
    }
}
