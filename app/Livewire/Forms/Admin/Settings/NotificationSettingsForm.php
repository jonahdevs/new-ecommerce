<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\NotificationSettings;
use Livewire\Form;

class NotificationSettingsForm extends Form
{
    public bool $notify_new_order = true;

    public bool $notify_low_stock = true;

    public bool $notify_new_review = false;

    public bool $notify_new_user = false;

    public bool $notify_failed_payment = true;

    public bool $notify_out_of_stock = true;

    public bool $notify_new_quote = true;

    public bool $notify_quote_accepted = true;

    public bool $notify_quote_rejected = false;

    public function rules(): array
    {
        return [
            'notify_new_order' => ['boolean'],
            'notify_low_stock' => ['boolean'],
            'notify_new_review' => ['boolean'],
            'notify_new_user' => ['boolean'],
            'notify_failed_payment' => ['boolean'],
            'notify_out_of_stock' => ['boolean'],
            'notify_new_quote' => ['boolean'],
            'notify_quote_accepted' => ['boolean'],
            'notify_quote_rejected' => ['boolean'],
        ];
    }

    public function fromSettings(NotificationSettings $settings): void
    {
        $this->notify_new_order = $settings->notify_new_order;
        $this->notify_low_stock = $settings->notify_low_stock;
        $this->notify_new_review = $settings->notify_new_review;
        $this->notify_new_user = $settings->notify_new_user;
        $this->notify_failed_payment = $settings->notify_failed_payment;
        $this->notify_out_of_stock = $settings->notify_out_of_stock;
        $this->notify_new_quote = $settings->notify_new_quote;
        $this->notify_quote_accepted = $settings->notify_quote_accepted;
        $this->notify_quote_rejected = $settings->notify_quote_rejected;
    }

    public function save(NotificationSettings $settings): void
    {
        $this->validate();

        $settings->notify_new_order = $this->notify_new_order;
        $settings->notify_low_stock = $this->notify_low_stock;
        $settings->notify_new_review = $this->notify_new_review;
        $settings->notify_new_user = $this->notify_new_user;
        $settings->notify_failed_payment = $this->notify_failed_payment;
        $settings->notify_out_of_stock = $this->notify_out_of_stock;
        $settings->notify_new_quote = $this->notify_new_quote;
        $settings->notify_quote_accepted = $this->notify_quote_accepted;
        $settings->notify_quote_rejected = $this->notify_quote_rejected;

        $settings->save();
    }
}
