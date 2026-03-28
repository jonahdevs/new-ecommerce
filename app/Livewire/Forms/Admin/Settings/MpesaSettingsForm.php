<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\MpesaSettings;
use Livewire\Form;

class MpesaSettingsForm extends Form
{
    public bool $enabled = false;
    public string $environment = 'sandbox';
    public string $shortcode = '';
    public string $shortcode_type = 'paybill';
    public string $initiator_name = '';
    public string $callback_url = '';

    // Encrypted fields — never pre-fill from settings for security
    // User must re-enter to change
    public string $consumer_key = '';
    public string $consumer_secret = '';
    public string $passkey = '';
    public string $initiator_password = '';

    // Track which encrypted fields are already set in DB
    public bool $has_consumer_key = false;
    public bool $has_consumer_secret = false;
    public bool $has_passkey = false;
    public bool $has_initiator_password = false;

    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'environment' => ['required', 'in:sandbox,live'],
            'shortcode' => ['required_if:enabled,true', 'string', 'max:20'],
            'shortcode_type' => ['required', 'in:till,paybill'],
            'initiator_name' => ['nullable', 'string', 'max:100'],
            'callback_url' => ['nullable', 'url', 'max:255'],
            'consumer_key' => ['nullable', 'string', 'max:255'],
            'consumer_secret' => ['nullable', 'string', 'max:255'],
            'passkey' => ['nullable', 'string', 'max:255'],
            'initiator_password' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function fromSettings(MpesaSettings $settings): void
    {
        $this->enabled = $settings->enabled;
        $this->environment = $settings->environment;
        $this->shortcode = $settings->shortcode ?? '';
        $this->shortcode_type = $settings->shortcode_type;
        $this->initiator_name = $settings->initiator_name ?? '';
        $this->callback_url = $settings->callback_url ?? '';

        // Track presence of encrypted fields without exposing values
        $this->has_consumer_key = !empty($settings->consumer_key);
        $this->has_consumer_secret = !empty($settings->consumer_secret);
        $this->has_passkey = !empty($settings->passkey);
        $this->has_initiator_password = !empty($settings->initiator_password);
    }

    public function save(MpesaSettings $settings): void
    {
        $this->validate();

        $settings->enabled = $this->enabled;
        $settings->environment = $this->environment;
        $settings->shortcode = $this->shortcode ?: null;
        $settings->shortcode_type = $this->shortcode_type;
        $settings->initiator_name = $this->initiator_name ?: null;
        $settings->callback_url = $this->callback_url ?: null;

        // Only update encrypted fields if the user typed a new value
        if ($this->consumer_key)
            $settings->consumer_key = $this->consumer_key;
        if ($this->consumer_secret)
            $settings->consumer_secret = $this->consumer_secret;
        if ($this->passkey)
            $settings->passkey = $this->passkey;
        if ($this->initiator_password)
            $settings->initiator_password = $this->initiator_password;

        $settings->save();

        // Update presence flags, clear typed values
        $this->has_consumer_key = !empty($settings->consumer_key);
        $this->has_consumer_secret = !empty($settings->consumer_secret);
        $this->has_passkey = !empty($settings->passkey);
        $this->has_initiator_password = !empty($settings->initiator_password);

        $this->consumer_key = '';
        $this->consumer_secret = '';
        $this->passkey = '';
        $this->initiator_password = '';
    }
}
