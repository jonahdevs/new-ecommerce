<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\InventorySettings;
use Livewire\Form;

class InventorySettingsForm extends Form
{
    public bool $inventory_tracking_enabled = true;

    public int $low_stock_threshold = 5;

    public function rules(): array
    {
        return [
            'inventory_tracking_enabled' => ['boolean'],
            'low_stock_threshold' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function fromSettings(InventorySettings $settings): void
    {
        $this->inventory_tracking_enabled = $settings->inventory_tracking_enabled;
        $this->low_stock_threshold = $settings->low_stock_threshold;
    }

    public function save(InventorySettings $settings): void
    {
        $this->validate();

        $settings->inventory_tracking_enabled = $this->inventory_tracking_enabled;
        $settings->low_stock_threshold = $this->low_stock_threshold;

        $settings->save();
    }
}
