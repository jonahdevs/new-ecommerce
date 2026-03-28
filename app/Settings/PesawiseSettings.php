<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;

class PesawiseSettings extends Settings
{
    public bool $enabled;
    public string $environment;       // sandbox | live
    public ?string $account_number;
    public ?string $callback_url;

    #[ShouldBeEncrypted]
    public ?string $api_key;

    #[ShouldBeEncrypted]
    public ?string $api_secret;

    public static function group(): string
    {
        return 'pesawise';
    }
}
