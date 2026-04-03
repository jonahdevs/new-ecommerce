<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\AddonType;
use App\Enums\ShippingRateAddonStatus;
use App\Models\ShippingRateAddon;
use Livewire\Form;

class ShippingRateAddonForm extends Form
{
    public ?ShippingRateAddon $addon = null;

    public string|int $shipping_rate_id = '';

    public string $addon_type = 'pus';

    public string $label = '';

    public string|float $addon_amount = '';

    public string|int $pickup_station_id = '';   // Empty = applies to all stations

    public string $status = 'active';

    public function rules(): array
    {
        return [
            'shipping_rate_id' => 'required|exists:shipping_rates,id',
            'addon_type' => 'required|string|in:'.implode(',', array_column(AddonType::cases(), 'value')),
            'label' => 'nullable|string|max:100',
            'addon_amount' => 'required|numeric|min:0',
            'pickup_station_id' => 'nullable|exists:pickup_stations,id',
            'status' => 'required|string|in:'.implode(',', array_column(ShippingRateAddonStatus::cases(), 'value')),
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_rate_id.required' => 'Please select a base rate to attach this addon to.',
            'addon_amount.required' => 'Please enter an addon amount.',
        ];
    }

    public function setAddon(ShippingRateAddon $addon): void
    {
        $this->addon = $addon;
        $this->shipping_rate_id = $addon->shipping_rate_id;
        $this->addon_type = $addon->addon_type instanceof AddonType
            ? $addon->addon_type->value
            : $addon->addon_type;
        $this->label = $addon->label ?? '';
        $this->addon_amount = $addon->addon_amount;
        $this->pickup_station_id = $addon->pickup_station_id ?? '';
        $this->status = $addon->status instanceof ShippingRateAddonStatus
            ? $addon->status->value
            : $addon->status;
    }

    public function store(): void
    {
        $this->validate();

        ShippingRateAddon::create($this->formData());
    }

    public function update(): void
    {
        $this->validate();

        $this->addon->update($this->formData());
    }

    private function formData(): array
    {
        return [
            'shipping_rate_id' => $this->shipping_rate_id,
            'addon_type' => $this->addon_type,
            'label' => $this->label ?: null,
            'addon_amount' => $this->addon_amount,
            'pickup_station_id' => $this->pickup_station_id ?: null,
            'status' => $this->status,
        ];
    }
}
