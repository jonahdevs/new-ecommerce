<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;

class MpesaSettings extends Settings
{
    public bool $enabled;
    public string $environment;         // sandbox | live
    public ?string $shortcode;
    public string $shortcode_type;      // till | paybill
    public ?string $initiator_name;
    public ?string $callback_url;

    #[ShouldBeEncrypted()]
    public ?string $consumer_key;

    #[ShouldBeEncrypted()]
    public ?string $consumer_secret;

    #[ShouldBeEncrypted()]
    public ?string $passkey;

    #[ShouldBeEncrypted()]
    public ?string $initiator_password;

    public static function group(): string
    {
        return 'mpesa';
    }
}
