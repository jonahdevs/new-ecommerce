<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\QuotationSettings;
use Livewire\Form;

class QuotationSettingsForm extends Form
{
    public bool $enabled = true;

    public string $quote_id_prefix = 'QT-';

    public int $default_validity_days = 7;

    public int $min_validity_days = 1;

    public int $max_validity_days = 30;

    public bool $allow_guest_quotes = true;

    public bool $require_phone = true;

    public bool $auto_expire_enabled = true;

    public ?string $admin_notification_email = null;

    public ?string $quote_terms = null;

    public ?string $quote_footer_note = null;

    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'quote_id_prefix' => ['required', 'string', 'max:20'],
            'default_validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'min_validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'max_validity_days' => ['required', 'integer', 'min:1', 'max:365', 'gte:min_validity_days'],
            'allow_guest_quotes' => ['boolean'],
            'require_phone' => ['boolean'],
            'auto_expire_enabled' => ['boolean'],
            'admin_notification_email' => ['nullable', 'email', 'max:255'],
            'quote_terms' => ['nullable', 'string', 'max:5000'],
            'quote_footer_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function fromSettings(QuotationSettings $settings): void
    {
        $this->enabled = $settings->enabled;
        $this->quote_id_prefix = $settings->quote_id_prefix;
        $this->default_validity_days = $settings->default_validity_days;
        $this->min_validity_days = $settings->min_validity_days;
        $this->max_validity_days = $settings->max_validity_days;
        $this->allow_guest_quotes = $settings->allow_guest_quotes;
        $this->require_phone = $settings->require_phone;
        $this->auto_expire_enabled = $settings->auto_expire_enabled;
        $this->admin_notification_email = $settings->admin_notification_email;
        $this->quote_terms = $settings->quote_terms;
        $this->quote_footer_note = $settings->quote_footer_note;
    }

    public function save(QuotationSettings $settings): void
    {
        $this->validate();

        $settings->enabled = $this->enabled;
        $settings->quote_id_prefix = $this->quote_id_prefix;
        $settings->default_validity_days = $this->default_validity_days;
        $settings->min_validity_days = $this->min_validity_days;
        $settings->max_validity_days = $this->max_validity_days;
        $settings->allow_guest_quotes = $this->allow_guest_quotes;
        $settings->require_phone = $this->require_phone;
        $settings->auto_expire_enabled = $this->auto_expire_enabled;
        $settings->admin_notification_email = $this->admin_notification_email ?: null;
        $settings->quote_terms = $this->quote_terms ?: null;
        $settings->quote_footer_note = $this->quote_footer_note ?: null;

        $settings->save();
    }
}
