<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\OrderSettings;
use Livewire\Form;

class OrderSettingsForm extends Form
{
    public string $order_id_prefix = 'ORD-';
    public ?float $minimum_order_amount = null;
    public bool $guest_checkout_enabled = true;
    public bool $auto_cancel_unpaid = false;
    public int $auto_cancel_hours = 24;
    public bool $stock_reduce_on_order = true;
    public string $default_order_status = 'pending';

    public function rules(): array
    {
        return [
            'order_id_prefix' => ['required', 'string', 'max:20'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'guest_checkout_enabled' => ['boolean'],
            'auto_cancel_unpaid' => ['boolean'],
            'auto_cancel_hours' => ['required_if:auto_cancel_unpaid,true', 'integer', 'min:1', 'max:720'],
            'stock_reduce_on_order' => ['boolean'],
            'default_order_status' => ['required', 'in:pending,processing,on-hold'],
        ];
    }

    public function fromSettings(OrderSettings $settings): void
    {
        $this->order_id_prefix = $settings->order_id_prefix;
        $this->minimum_order_amount = $settings->minimum_order_amount;
        $this->guest_checkout_enabled = $settings->guest_checkout_enabled;
        $this->auto_cancel_unpaid = $settings->auto_cancel_unpaid;
        $this->auto_cancel_hours = $settings->auto_cancel_hours;
        $this->stock_reduce_on_order = $settings->stock_reduce_on_order;
        $this->default_order_status = $settings->default_order_status;
    }

    public function save(OrderSettings $settings): void
    {
        $this->validate();

        $settings->order_id_prefix = $this->order_id_prefix;
        $settings->minimum_order_amount = $this->minimum_order_amount ?: null;
        $settings->guest_checkout_enabled = $this->guest_checkout_enabled;
        $settings->auto_cancel_unpaid = $this->auto_cancel_unpaid;
        $settings->auto_cancel_hours = $this->auto_cancel_hours;
        $settings->stock_reduce_on_order = $this->stock_reduce_on_order;
        $settings->default_order_status = $this->default_order_status;

        $settings->save();
    }
}
