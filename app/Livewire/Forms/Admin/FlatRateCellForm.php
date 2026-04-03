<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\ShippingRateStatus;
use App\Models\ShippingRate;
use Livewire\Form;

/**
 * Used when editing a single price cell in the flat rate matrix.
 * Versioning: update() expires the old rate and creates a new active one.
 * The zone, method, and weight tier are carried over from the original rate —
 * only the price and delivery window are editable.
 */
class FlatRateCellForm extends Form
{
    public ?ShippingRate $rate = null;

    public string|float $price = '';

    public string|int $estimated_days_min = '';

    public string|int $estimated_days_max = '';

    public function rules(): array
    {
        return [
            'price' => 'required|numeric|min:0',
            'estimated_days_min' => 'nullable|integer|min:1',
            'estimated_days_max' => 'nullable|integer|min:1|gte:estimated_days_min',
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => 'Please enter a price for this rate.',
            'estimated_days_max.gte' => 'Max delivery time must be greater than or equal to min.',
        ];
    }

    public function setRate(ShippingRate $rate): void
    {
        $this->rate = $rate;
        $this->price = $rate->price;
        $this->estimated_days_min = $rate->estimated_days_min ?? '';
        $this->estimated_days_max = $rate->estimated_days_max ?? '';
    }

    /**
     * Expire the current rate and create a new active one.
     * This preserves the full history for audit/reporting.
     */
    public function update(): void
    {
        $this->validate();

        // Mark the old rate as expired — never delete
        $this->rate->update(['status' => ShippingRateStatus::EXPIRED->value]);

        // Create the replacement rate carrying over all dimensional fields
        ShippingRate::create([
            'shipping_zone_id' => $this->rate->shipping_zone_id,
            'shipping_method_id' => $this->rate->shipping_method_id,
            'min_weight' => $this->rate->min_weight,
            'max_weight' => $this->rate->max_weight,
            'weight_label' => $this->rate->weight_label,
            'price' => $this->price,
            'estimated_days_min' => $this->estimated_days_min ?: null,
            'estimated_days_max' => $this->estimated_days_max ?: null,
            'status' => ShippingRateStatus::ACTIVE->value,
        ]);
    }
}
