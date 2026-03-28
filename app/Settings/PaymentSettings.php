<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    public string $gateway_mode;          // individual | aggregator
    public string $active_aggregator;     // pesapal | pesawise — only when mode = aggregator
    public bool $cod_enabled;
    public ?string $cod_instructions;
    public ?string $payment_instructions;
    public string $payment_currency;

    public static function group(): string
    {
        return 'payment';
    }
}
