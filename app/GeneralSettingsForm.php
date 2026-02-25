<?php

namespace App\Livewire\Forms\Admin;

use App\Settings\GeneralSettings;
use Livewire\Form;

class GeneralSettingsForm extends Form
{
    // Identity
    public string $company_name = '';
    public string $email_address = '';
    public string $phone_number = '';

    // Images
    public $logo_light = null;
    public $logo_dark = null;
    public $logo_icon = null;
    public $favicon = null;

    public ?string $existing_logo_light = null;
    public ?string $existing_logo_dark = null;
    public ?string $existing_logo_icon = null;
    public ?string $existing_favicon = null;

    // Address
    public string $address = '';
    public string $country = '';
    public string $town = '';
    public string $postal_code = '';

    // Localization
    public string $currency = 'KES';
    public string $currency_symbol = 'KSh';
    public string $timezone = 'Africa/Nairobi';

    // Business
    public string $vat_number = '';
    public string $registration_number = '';

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:100'],
            'email_address' => ['required', 'email', 'max:100'],
            'phone_number' => ['required', 'string', 'max:20'],

            'logo_light' => ['nullable', 'image', 'max:2048'],
            'logo_dark' => ['nullable', 'image', 'max:2048'],
            'logo_icon' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:512'],

            'address' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:100'],
            'town' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],

            'currency' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'timezone'],

            'vat_number' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function fromSettings(GeneralSettings $settings): void
    {
        $this->company_name = $settings->company_name;
        $this->email_address = $settings->email_address;
        $this->phone_number = $settings->phone_number;

        $this->existing_logo_light = $settings->logo_light;
        $this->existing_logo_dark = $settings->logo_dark;
        $this->existing_logo_icon = $settings->logo_icon;
        $this->existing_favicon = $settings->favicon;

        $this->address = $settings->address ?? '';
        $this->country = $settings->country ?? '';
        $this->town = $settings->town ?? '';
        $this->postal_code = $settings->postal_code ?? '';

        $this->currency = $settings->currency ?? 'KES';
        $this->currency_symbol = $settings->currency_symbol ?? 'KSh';
        $this->timezone = $settings->timezone ?? 'Africa/Nairobi';

        $this->vat_number = $settings->vat_number ?? '';
        $this->registration_number = $settings->registration_number ?? '';
    }

    public function save(GeneralSettings $settings): void
    {
        $this->validate();

        $settings->company_name = $this->company_name;
        $settings->email_address = $this->email_address;
        $settings->phone_number = $this->phone_number;

        $settings->address = $this->address;
        $settings->country = $this->country;
        $settings->town = $this->town;
        $settings->postal_code = $this->postal_code;

        $settings->currency = $this->currency;
        $settings->currency_symbol = $this->currency_symbol;
        $settings->timezone = $this->timezone;

        $settings->vat_number = $this->vat_number ?: null;
        $settings->registration_number = $this->registration_number ?: null;

        foreach (['logo_light', 'logo_dark', 'logo_icon', 'favicon'] as $image) {
            if ($this->$image) {
                $path = $this->$image->store('settings', 'public');
                $settings->$image = $path;
                $this->{"existing_{$image}"} = $path;
                $this->$image = null;
            }
        }

        $settings->save();
    }

    public function removeImage(GeneralSettings $settings, string $image): void
    {
        $settings->$image = null;
        $settings->save();
        $this->{"existing_{$image}"} = null;
    }
}
