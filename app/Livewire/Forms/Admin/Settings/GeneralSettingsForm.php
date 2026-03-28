<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\GeneralSettings;
use Livewire\Form;

class GeneralSettingsForm extends Form
{
    // Store Identity
    public string $store_name = '';
    public string $store_tagline = '';
    public $store_logo = null;
    public ?string $existing_logo = null;
    public $store_favicon = null;
    public ?string $existing_favicon = null;

    // Contact
    public string $store_email = '';
    public string $store_phone = '';

    // Address
    public string $store_address = '';
    public string $store_address_line_2 = '';
    public string $store_city = '';
    public string $store_state = '';
    public string $store_postal_code = '';
    public string $store_country = '';

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:100'],
            'store_tagline' => ['nullable', 'string', 'max:160'],
            'store_logo' => ['nullable', 'image', 'max:2048'],
            'store_favicon' => ['nullable', 'image', 'max:512', 'dimensions:max_width=64,max_height=64'],
            'store_email' => ['nullable', 'email', 'max:100'],
            'store_phone' => ['nullable', 'string', 'max:30'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'store_address_line_2' => ['nullable', 'string', 'max:255'],
            'store_city' => ['nullable', 'string', 'max:100'],
            'store_state' => ['nullable', 'string', 'max:100'],
            'store_postal_code' => ['nullable', 'string', 'max:20'],
            'store_country' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function fromSettings(GeneralSettings $settings): void
    {
        $this->store_name = $settings->store_name ?? '';
        $this->store_tagline = $settings->store_tagline ?? '';
        $this->existing_logo = $settings->store_logo;
        $this->existing_favicon = $settings->store_favicon;
        $this->store_email = $settings->store_email ?? '';
        $this->store_phone = $settings->store_phone ?? '';
        $this->store_address = $settings->store_address ?? '';
        $this->store_address_line_2 = $settings->store_address_line_2 ?? '';
        $this->store_city = $settings->store_city ?? '';
        $this->store_state = $settings->store_state ?? '';
        $this->store_postal_code = $settings->store_postal_code ?? '';
        $this->store_country = $settings->store_country ?? '';
    }

    public function save(GeneralSettings $settings): void
    {
        $this->validate();

        $settings->store_name = $this->store_name;
        $settings->store_tagline = $this->store_tagline ?: null;
        $settings->store_email = $this->store_email ?: null;
        $settings->store_phone = $this->store_phone ?: null;
        $settings->store_address = $this->store_address ?: null;
        $settings->store_address_line_2 = $this->store_address_line_2 ?: null;
        $settings->store_city = $this->store_city ?: null;
        $settings->store_state = $this->store_state ?: null;
        $settings->store_postal_code = $this->store_postal_code ?: null;
        $settings->store_country = $this->store_country ?: null;

        if ($this->store_logo) {
            $settings->store_logo = $this->store_logo->store('settings/logo', 'public');
            $this->existing_logo = $settings->store_logo;
            $this->store_logo = null;
        }

        if ($this->store_favicon) {
            $settings->store_favicon = $this->store_favicon->store('settings/favicon', 'public');
            $this->existing_favicon = $settings->store_favicon;
            $this->store_favicon = null;
        }

        $settings->save();
    }

    public function removeLogo(GeneralSettings $settings): void
    {
        $settings->store_logo = null;
        $this->existing_logo = null;
        $settings->save();
    }

    public function removeFavicon(GeneralSettings $settings): void
    {
        $settings->store_favicon = null;
        $this->existing_favicon = null;
        $settings->save();
    }
}
