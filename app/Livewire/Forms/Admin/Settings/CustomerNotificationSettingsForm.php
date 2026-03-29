<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\CustomerNotificationSettings;
use Livewire\Form;

class CustomerNotificationSettingsForm extends Form
{
    public bool $order_confirmation = true;
    public bool $order_processing = true;
    public bool $order_shipped = true;
    public bool $order_delivered = true;
    public bool $order_cancelled = true;
    public bool $order_refunded = true;
    public bool $abandoned_cart = false;
    public int $abandoned_cart_delay = 1;
    public bool $review_request = false;
    public int $review_request_delay = 3;
    public bool $quote_sent = true;
    public bool $quote_expiring_reminder = true;
    public int $quote_expiring_days = 2;

    public function rules(): array
    {
        return [
            'order_confirmation' => ['boolean'],
            'order_processing' => ['boolean'],
            'order_shipped' => ['boolean'],
            'order_delivered' => ['boolean'],
            'order_cancelled' => ['boolean'],
            'order_refunded' => ['boolean'],
            'abandoned_cart' => ['boolean'],
            'abandoned_cart_delay' => ['required_if:abandoned_cart,true', 'integer', 'min:1', 'max:72'],
            'review_request' => ['boolean'],
            'review_request_delay' => ['required_if:review_request,true', 'integer', 'min:1', 'max:30'],
            'quote_sent' => ['boolean'],
            'quote_expiring_reminder' => ['boolean'],
            'quote_expiring_days' => ['required_if:quote_expiring_reminder,true', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function fromSettings(CustomerNotificationSettings $settings): void
    {
        $this->order_confirmation = $settings->order_confirmation;
        $this->order_processing = $settings->order_processing;
        $this->order_shipped = $settings->order_shipped;
        $this->order_delivered = $settings->order_delivered;
        $this->order_cancelled = $settings->order_cancelled;
        $this->order_refunded = $settings->order_refunded;
        $this->abandoned_cart = $settings->abandoned_cart;
        $this->abandoned_cart_delay = $settings->abandoned_cart_delay;
        $this->review_request = $settings->review_request;
        $this->review_request_delay = $settings->review_request_delay;
        $this->quote_sent = $settings->quote_sent;
        $this->quote_expiring_reminder = $settings->quote_expiring_reminder;
        $this->quote_expiring_days = $settings->quote_expiring_days;
    }

    public function save(CustomerNotificationSettings $settings): void
    {
        $this->validate();

        $settings->order_confirmation = $this->order_confirmation;
        $settings->order_processing = $this->order_processing;
        $settings->order_shipped = $this->order_shipped;
        $settings->order_delivered = $this->order_delivered;
        $settings->order_cancelled = $this->order_cancelled;
        $settings->order_refunded = $this->order_refunded;
        $settings->abandoned_cart = $this->abandoned_cart;
        $settings->abandoned_cart_delay = $this->abandoned_cart_delay;
        $settings->review_request = $this->review_request;
        $settings->review_request_delay = $this->review_request_delay;
        $settings->quote_sent = $this->quote_sent;
        $settings->quote_expiring_reminder = $this->quote_expiring_reminder;
        $settings->quote_expiring_days = $this->quote_expiring_days;

        $settings->save();
    }
}
