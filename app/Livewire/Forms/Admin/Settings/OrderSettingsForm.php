<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\OrderSettings;
use Livewire\Form;

class OrderSettingsForm extends Form
{
    public string $order_id_prefix = 'ORD-';

    public ?float $minimum_order_amount = null;

    public bool $auto_cancel_unpaid = false;

    public int $auto_cancel_hours = 24;

    public string $default_order_status = 'pending';

    public ?string $purchase_note = null;

    public function rules(): array
    {
        return [
            'order_id_prefix' => ['required', 'string', 'max:20'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'auto_cancel_unpaid' => ['boolean'],
            'auto_cancel_hours' => ['required_if:auto_cancel_unpaid,true', 'integer', 'min:1', 'max:720'],
            'default_order_status' => ['required', 'in:pending,processing,on-hold'],
            'purchase_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function fromSettings(OrderSettings $settings): void
    {
        $this->order_id_prefix = $settings->order_id_prefix;
        $this->minimum_order_amount = $settings->minimum_order_amount;
        $this->auto_cancel_unpaid = $settings->auto_cancel_unpaid;
        $this->auto_cancel_hours = $settings->auto_cancel_hours;
        $this->default_order_status = $settings->default_order_status;
        $this->purchase_note = $settings->purchase_note;
    }

    public function save(OrderSettings $settings): void
    {
        $this->validate();

        $settings->order_id_prefix = $this->order_id_prefix;
        $settings->minimum_order_amount = $this->minimum_order_amount ?: null;
        $settings->auto_cancel_unpaid = $this->auto_cancel_unpaid;
        $settings->auto_cancel_hours = $this->auto_cancel_hours;
        $settings->default_order_status = $this->default_order_status;
        $settings->purchase_note = $this->purchase_note ?: null;

        $settings->save();
    }
}
