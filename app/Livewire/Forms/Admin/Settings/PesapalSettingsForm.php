<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\PesapalSettings;
use Livewire\Form;

class PesapalSettingsForm extends Form
{
    public bool $enabled = false;

    public string $environment = 'sandbox';

    public string $ipn_id = '';

    public string $callback_url = '';

    // Encrypted — user must re-enter to change
    public string $consumer_key = '';

    public string $consumer_secret = '';

    public bool $has_consumer_key = false;

    public bool $has_consumer_secret = false;

    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'environment' => ['required', 'in:sandbox,live'],
            'ipn_id' => ['nullable', 'string', 'max:255'],
            'callback_url' => ['nullable', 'url', 'max:255'],
            'consumer_key' => ['nullable', 'string', 'max:255'],
            'consumer_secret' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function fromSettings(PesapalSettings $settings): void
    {
        $this->enabled = $settings->enabled;
        $this->environment = $settings->environment;
        $this->ipn_id = $settings->ipn_id ?? '';
        $this->callback_url = $settings->callback_url ?? '';

        $this->has_consumer_key = ! empty($settings->consumer_key);
        $this->has_consumer_secret = ! empty($settings->consumer_secret);
    }

    public function save(PesapalSettings $settings): void
    {
        $this->validate();

        $settings->enabled = $this->enabled;
        $settings->environment = $this->environment;
        $settings->ipn_id = $this->ipn_id ?: null;
        $settings->callback_url = $this->callback_url ?: null;

        if ($this->consumer_key) {
            $settings->consumer_key = $this->consumer_key;
        }
        if ($this->consumer_secret) {
            $settings->consumer_secret = $this->consumer_secret;
        }

        $settings->save();

        $this->has_consumer_key = ! empty($settings->consumer_key);
        $this->has_consumer_secret = ! empty($settings->consumer_secret);
        $this->consumer_key = '';
        $this->consumer_secret = '';
    }
}
