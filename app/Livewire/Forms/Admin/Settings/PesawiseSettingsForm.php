<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\PesawiseSettings;
use Livewire\Form;

class PesawiseSettingsForm extends Form
{
    public bool $enabled = false;
    public string $environment = 'sandbox';
    public string $account_number = '';
    public string $callback_url = '';

    // Encrypted — user must re-enter to change
    public string $api_key = '';
    public string $api_secret = '';

    public bool $has_api_key = false;
    public bool $has_api_secret = false;

    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'environment' => ['required', 'in:sandbox,live'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'callback_url' => ['nullable', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function fromSettings(PesawiseSettings $settings): void
    {
        $this->enabled = $settings->enabled;
        $this->environment = $settings->environment;
        $this->account_number = $settings->account_number ?? '';
        $this->callback_url = $settings->callback_url ?? '';

        $this->has_api_key = !empty($settings->api_key);
        $this->has_api_secret = !empty($settings->api_secret);
    }

    public function save(PesawiseSettings $settings): void
    {
        $this->validate();

        $settings->enabled = $this->enabled;
        $settings->environment = $this->environment;
        $settings->account_number = $this->account_number ?: null;
        $settings->callback_url = $this->callback_url ?: null;

        if ($this->api_key)
            $settings->api_key = $this->api_key;
        if ($this->api_secret)
            $settings->api_secret = $this->api_secret;

        $settings->save();

        $this->has_api_key = !empty($settings->api_key);
        $this->has_api_secret = !empty($settings->api_secret);
        $this->api_key = '';
        $this->api_secret = '';
    }
}
