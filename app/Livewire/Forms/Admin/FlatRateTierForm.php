<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\ShippingRateStatus;
use App\Models\ShippingRate;
use Livewire\Form;

/**
 * Used when adding a brand-new weight tier to the matrix.
 * Creates one ShippingRate row per zone for the given tier.
 * The $prices array is keyed by zone_id so the blade can
 * loop through zones and bind each price field dynamically.
 */
class FlatRateTierForm extends Form
{
    public string|int $shipping_method_id = '';

    public string|float $min_weight = '';

    public string|float $max_weight = '';   // Empty = no upper limit (XL tier)

    public string $weight_label = '';

    public string|int $estimated_days_min = '';

    public string|int $estimated_days_max = '';

    // Keyed by zone_id → price value. Populated in the blade loop.
    public array $prices = [];

    public function rules(): array
    {
        $priceRules = [];
        foreach ($this->prices as $zoneId => $price) {
            $priceRules["prices.{$zoneId}"] = 'nullable|numeric|min:0';
        }

        return array_merge([
            'shipping_method_id' => 'required|exists:shipping_methods,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'nullable|numeric|gt:min_weight',
            'weight_label' => 'required|string|max:100',
            'estimated_days_min' => 'nullable|integer|min:1',
            'estimated_days_max' => 'nullable|integer|min:1|gte:estimated_days_min',
        ], $priceRules);
    }

    public function messages(): array
    {
        return [
            'max_weight.gt' => 'Max weight must be greater than min weight.',
            'estimated_days_max.gte' => 'Max delivery time must be ≥ min.',
            'shipping_method_id.required' => 'Please select a shipping method.',
        ];
    }

    public function store(): void
    {
        $this->validate();

        foreach ($this->prices as $zoneId => $price) {
            // Skip zones where no price was entered
            if ($price === '' || $price === null) {
                continue;
            }

            ShippingRate::create([
                'shipping_zone_id' => $zoneId,
                'shipping_method_id' => $this->shipping_method_id,
                'min_weight' => $this->min_weight,
                'max_weight' => $this->max_weight ?: null,
                'weight_label' => $this->weight_label,
                'price' => $price,
                'estimated_days_min' => $this->estimated_days_min ?: null,
                'estimated_days_max' => $this->estimated_days_max ?: null,
                'status' => ShippingRateStatus::ACTIVE->value,
            ]);
        }
    }
}
