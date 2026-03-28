<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\TaxSettings;
use Livewire\Form;

class TaxSettingsForm extends Form
{
    public bool $tax_enabled = true;
    public string $tax_name = 'VAT';
    public float $tax_rate = 16.00;
    public string $tax_type = 'exclusive';
    public string $tax_registration_number = '';
    public bool $taxable_shipping = false;

    public function rules(): array
    {
        return [
            'tax_enabled' => ['boolean'],
            'tax_name' => ['required_if:tax_enabled,true', 'string', 'max:30'],
            'tax_rate' => ['required_if:tax_enabled,true', 'numeric', 'min:0', 'max:100'],
            'tax_type' => ['required_if:tax_enabled,true', 'in:inclusive,exclusive'],
            'tax_registration_number' => ['nullable', 'string', 'max:50'],
            'taxable_shipping' => ['boolean'],
        ];
    }

    public function fromSettings(TaxSettings $settings): void
    {
        $this->tax_enabled = $settings->tax_enabled;
        $this->tax_name = $settings->tax_name;
        $this->tax_rate = $settings->tax_rate;
        $this->tax_type = $settings->tax_type;
        $this->tax_registration_number = $settings->tax_registration_number ?? '';
        $this->taxable_shipping = $settings->taxable_shipping;
    }

    public function save(TaxSettings $settings): void
    {
        $this->validate();

        $settings->tax_enabled = $this->tax_enabled;
        $settings->tax_name = $this->tax_name;
        $settings->tax_rate = $this->tax_rate;
        $settings->tax_type = $this->tax_type;
        $settings->tax_registration_number = $this->tax_registration_number ?: null;
        $settings->taxable_shipping = $this->taxable_shipping;

        $settings->save();
    }
}
