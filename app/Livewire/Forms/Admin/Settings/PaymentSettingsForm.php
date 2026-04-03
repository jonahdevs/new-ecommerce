<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\PaymentSettings;
use Livewire\Form;

class PaymentSettingsForm extends Form
{
    public string $gateway_mode = 'individual'; // individual | aggregator

    public string $active_aggregator = 'pesapal';    // pesapal | pesawise

    public bool $cod_enabled = false;

    public string $cod_instructions = '';

    public string $payment_instructions = '';

    public string $payment_currency = 'KES';

    public function rules(): array
    {
        return [
            'gateway_mode' => ['required', 'in:individual,aggregator'],
            'active_aggregator' => ['required_if:gateway_mode,aggregator', 'in:pesapal,pesawise'],
            'cod_enabled' => ['boolean'],
            'cod_instructions' => ['nullable', 'string', 'max:500'],
            'payment_instructions' => ['nullable', 'string', 'max:500'],
            'payment_currency' => ['required', 'string', 'max:10'],
        ];
    }

    public function fromSettings(PaymentSettings $settings): void
    {
        $this->gateway_mode = $settings->gateway_mode;
        $this->active_aggregator = $settings->active_aggregator;
        $this->cod_enabled = $settings->cod_enabled;
        $this->cod_instructions = $settings->cod_instructions ?? '';
        $this->payment_instructions = $settings->payment_instructions ?? '';
        $this->payment_currency = $settings->payment_currency;
    }

    public function save(PaymentSettings $settings): void
    {
        $this->validate();

        $settings->gateway_mode = $this->gateway_mode;
        $settings->active_aggregator = $this->active_aggregator;
        $settings->cod_enabled = $this->cod_enabled;
        $settings->cod_instructions = $this->cod_instructions ?: null;
        $settings->payment_instructions = $this->payment_instructions ?: null;
        $settings->payment_currency = $this->payment_currency;

        $settings->save();
    }
}
