<?php

namespace App\Settings;

use Spatie\LaravelSettings\Attributes\Encrypted;
use Spatie\LaravelSettings\Settings;

class EmailApiSettings extends Settings
{
    // SMTP
    public ?string $smtp_host;

    public ?int $smtp_port;

    public ?string $smtp_encryption;

    public ?string $smtp_username;

    #[Encrypted]
    public ?string $smtp_password;

    // Mailgun
    public ?string $mailgun_domain;

    #[Encrypted]
    public ?string $mailgun_secret;

    // Amazon SES
    public ?string $ses_key;

    #[Encrypted]
    public ?string $ses_secret;

    public ?string $ses_region;

    // Postmark
    #[Encrypted]
    public ?string $postmark_token;

    // Resend
    #[Encrypted]
    public ?string $resend_key;

    public static function group(): string
    {
        return 'email_api';
    }
}
