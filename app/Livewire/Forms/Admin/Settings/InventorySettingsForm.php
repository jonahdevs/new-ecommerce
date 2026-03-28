<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\InventorySettings;
use Livewire\Form;

class InventorySettingsForm extends Form
{
    public bool $inventory_tracking_enabled = true;
    public int $low_stock_threshold = 5;
    public string $out_of_stock_behaviour = 'show_with_notice';
    public bool $backorders_allowed = false;
    public string $backorders_message = 'Available on backorder';
    public bool $notify_admin_low_stock = true;
    public bool $notify_admin_out_of_stock = true;

    public function rules(): array
    {
        return [
            'inventory_tracking_enabled' => ['boolean'],
            'low_stock_threshold' => ['required', 'integer', 'min:1', 'max:1000'],
            'out_of_stock_behaviour' => ['required', 'in:hide,show,show_with_notice'],
            'backorders_allowed' => ['boolean'],
            'backorders_message' => ['required_if:backorders_allowed,true', 'string', 'max:255'],
            'notify_admin_low_stock' => ['boolean'],
            'notify_admin_out_of_stock' => ['boolean'],
        ];
    }

    public function fromSettings(InventorySettings $settings): void
    {
        $this->inventory_tracking_enabled = $settings->inventory_tracking_enabled;
        $this->low_stock_threshold = $settings->low_stock_threshold;
        $this->out_of_stock_behaviour = $settings->out_of_stock_behaviour;
        $this->backorders_allowed = $settings->backorders_allowed;
        $this->backorders_message = $settings->backorders_message;
        $this->notify_admin_low_stock = $settings->notify_admin_low_stock;
        $this->notify_admin_out_of_stock = $settings->notify_admin_out_of_stock;
    }

    public function save(InventorySettings $settings): void
    {
        $this->validate();

        $settings->inventory_tracking_enabled = $this->inventory_tracking_enabled;
        $settings->low_stock_threshold = $this->low_stock_threshold;
        $settings->out_of_stock_behaviour = $this->out_of_stock_behaviour;
        $settings->backorders_allowed = $this->backorders_allowed;
        $settings->backorders_message = $this->backorders_message;
        $settings->notify_admin_low_stock = $this->notify_admin_low_stock;
        $settings->notify_admin_out_of_stock = $this->notify_admin_out_of_stock;

        $settings->save();
    }
}
