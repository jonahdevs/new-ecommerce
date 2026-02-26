<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MailSettings extends Settings
{
    public string $active_driver = 'smtp';

    // SMTP
    public string $smtp_host = '';
    public int $smtp_port = 587;
    public string $smtp_username = '';
    public string $smtp_password = '';
    public string $smtp_encryption = 'tls';

    // Mailgun
    public string $mailgun_domain = '';
    public string $mailgun_secret = '';
    public string $mailgun_endpoint = 'api.mailgun.net';

    // Amazon SES
    public string $ses_key = '';
    public string $ses_secret = '';
    public string $ses_region = 'us-east-1';

    // Postmark
    public string $postmark_token = '';

    // Sender
    public string $from_address = '';
    public string $from_name = '';

    public static function group(): string
    {
        return 'mail';
    }

    public static function encrypted(): array
    {
        return [
            'smtp_password',
            'mailgun_secret',
            'ses_key',
            'ses_secret',
            'postmark_token',
        ];
    }
}
