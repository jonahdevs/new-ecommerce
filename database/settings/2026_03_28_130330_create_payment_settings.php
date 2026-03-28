<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreatePaymentSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payment.gateway_mode', 'individual');   // individual | aggregator
        $this->migrator->add('payment.active_aggregator', 'pesapal'); // pesapal | pesawise
        $this->migrator->add('payment.cod_enabled', false);
        $this->migrator->add('payment.cod_instructions', null);
        $this->migrator->add('payment.payment_instructions', null);
        $this->migrator->add('payment.payment_currency', 'KES');
    }
}
