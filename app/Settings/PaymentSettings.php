<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    public bool $mpesa_enabled;

    public string $mpesa_shortcode;

    public string $mpesa_type;

    public bool $card_enabled;

    public string $card_provider;

    public bool $bank_transfer_enabled;

    public string $bank_details;

    public bool $cash_on_delivery_enabled;

    public static function group(): string
    {
        return 'payments';
    }
}
