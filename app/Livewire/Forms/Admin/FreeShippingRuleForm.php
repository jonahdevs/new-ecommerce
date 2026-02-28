<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\FreeShippingRuleStatus;
use App\Models\FreeShippingRule;
use Livewire\Form;

class FreeShippingRuleForm extends Form
{
    public ?FreeShippingRule $rule = null;

    public string       $name              = '';
    public string|int   $shipping_zone_id  = '';   // Empty = all zones
    public string|int   $shipping_method_id = '';  // Empty = all methods
    public string|float $min_order_amount  = '';
    public string|float $max_weight        = '';   // Empty = no weight ceiling
    public string       $starts_at         = '';
    public string       $ends_at           = '';
    public string       $status            = 'inactive';

    public function rules(): array
    {
        return [
            'name'               => 'required|string|max:150',
            'shipping_zone_id'   => 'nullable|exists:shipping_zones,id',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'min_order_amount'   => 'required|numeric|min:0',
            'max_weight'         => 'nullable|numeric|min:0',
            'starts_at'          => 'nullable|date',
            'ends_at'            => 'nullable|date|after:starts_at',
            'status'             => 'required|string|in:' . implode(',', array_column(FreeShippingRuleStatus::cases(), 'value')),
        ];
    }

    public function messages(): array
    {
        return [
            'min_order_amount.required' => 'Please enter a minimum order amount.',
            'ends_at.after'             => 'End date must be after the start date.',
        ];
    }

    public function setRule(FreeShippingRule $rule): void
    {
        $this->rule                = $rule;
        $this->name                = $rule->name;
        $this->shipping_zone_id    = $rule->shipping_zone_id ?? '';
        $this->shipping_method_id  = $rule->shipping_method_id ?? '';
        $this->min_order_amount    = $rule->min_order_amount;
        $this->max_weight          = $rule->max_weight ?? '';
        $this->starts_at           = $rule->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at             = $rule->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->status              = $rule->status instanceof FreeShippingRuleStatus
            ? $rule->status->value
            : $rule->status;
    }

    public function store(): void
    {
        $this->validate();

        FreeShippingRule::create($this->formData());
    }

    public function update(): void
    {
        $this->validate();

        $this->rule->update($this->formData());
    }

    private function formData(): array
    {
        return [
            'name'               => $this->name,
            'shipping_zone_id'   => $this->shipping_zone_id ?: null,
            'shipping_method_id' => $this->shipping_method_id ?: null,
            'min_order_amount'   => $this->min_order_amount,
            'max_weight'         => $this->max_weight ?: null,
            'starts_at'          => $this->starts_at ?: null,
            'ends_at'            => $this->ends_at ?: null,
            'status'             => $this->status,
        ];
    }
}
