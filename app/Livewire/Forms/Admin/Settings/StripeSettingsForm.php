<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\StripeSettings;
use Livewire\Form;

class StripeSettingsForm extends Form
{
    public bool $enabled = false;

    public string $environment = 'sandbox';

    public string $public_key = ''; // publishable key — not encrypted, safe to show

    // Encrypted — user must re-enter to change
    public string $secret_key = '';

    public string $webhook_secret = '';

    // Track presence of encrypted fields
    public bool $has_secret_key = false;

    public bool $has_webhook_secret = false;

    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'environment' => ['required', 'in:sandbox,live'],
            'public_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function fromSettings(StripeSettings $settings): void
    {
        $this->enabled = $settings->enabled;
        $this->environment = $settings->environment;
        $this->public_key = $settings->public_key ?? ''; // safe to pre-fill

        $this->has_secret_key = ! empty($settings->secret_key);
        $this->has_webhook_secret = ! empty($settings->webhook_secret);
    }

    public function save(StripeSettings $settings): void
    {
        $this->validate();

        $settings->enabled = $this->enabled;
        $settings->environment = $this->environment;
        $settings->public_key = $this->public_key ?: null;

        if ($this->secret_key) {
            $settings->secret_key = $this->secret_key;
        }
        if ($this->webhook_secret) {
            $settings->webhook_secret = $this->webhook_secret;
        }

        $settings->save();

        $this->has_secret_key = ! empty($settings->secret_key);
        $this->has_webhook_secret = ! empty($settings->webhook_secret);
        $this->secret_key = '';
        $this->webhook_secret = '';
    }
}
