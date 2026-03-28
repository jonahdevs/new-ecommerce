<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;

class MailSettings extends Settings
{
    public string $mailer;
    public ?string $host;
    public ?int $port;
    public ?string $username;
    public ?string $encryption;
    public string $from_address;
    public string $from_name;
    public ?string $reply_to_address;

    #[ShouldBeEncrypted]
    public ?string $password;

    public static function group(): string
    {
        return 'mail';
    }
}
