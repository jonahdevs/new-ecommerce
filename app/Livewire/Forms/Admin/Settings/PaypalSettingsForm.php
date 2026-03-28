<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\PaypalSettings;
use Livewire\Form;

class PaypalSettingsForm extends Form
{
    public bool $enabled = false;
    public string $environment = 'sandbox';
    public string $client_id = ''; // semi-public — safe to pre-fill

    // Encrypted — user must re-enter to change
    public string $client_secret = '';
    public bool $has_client_secret = false;

    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'environment' => ['required', 'in:sandbox,live'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function fromSettings(PaypalSettings $settings): void
    {
        $this->enabled = $settings->enabled;
        $this->environment = $settings->environment;
        $this->client_id = $settings->client_id ?? '';

        $this->has_client_secret = !empty($settings->client_secret);
    }

    public function save(PaypalSettings $settings): void
    {
        $this->validate();

        $settings->enabled = $this->enabled;
        $settings->environment = $this->environment;
        $settings->client_id = $this->client_id ?: null;

        if ($this->client_secret)
            $settings->client_secret = $this->client_secret;

        $settings->save();

        $this->has_client_secret = !empty($settings->client_secret);
        $this->client_secret = '';
    }
}
