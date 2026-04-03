<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\ShippingMethodStatus;
use App\Models\ShippingMethod;
use Livewire\Form;

class ShippingMethodForm extends Form
{
    public ?ShippingMethod $method = null;

    public string $name = '';

    public string $code = '';

    public string|int $logistics_provider_id = '';

    public string $type = 'flat';

    public string $delivery_time_unit = 'days';

    public bool $supports_returns = false;

    public string $description = '';

    public string $icon = '';

    public int $sort_order = 0;

    public string $status = 'active';

    public function rules(): array
    {
        $uniqueCode = 'required|string|max:50|alpha_dash|unique:shipping_methods,code';

        if ($this->method) {
            $uniqueCode .= ",{$this->method->id}";
        }

        return [
            'name' => 'required|string|max:100',
            'code' => $uniqueCode,
            'logistics_provider_id' => 'required|exists:logistics_providers,id',
            'type' => 'required|in:flat,distance,pus',
            'delivery_time_unit' => 'required|in:hours,days',
            'supports_returns' => 'boolean',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'integer|min:0',
            'status' => 'required|string|in:'.implode(',', array_column(ShippingMethodStatus::cases(), 'value')),
        ];
    }

    public function messages(): array
    {
        return [
            'code.alpha_dash' => 'Code may only contain letters, numbers, dashes and underscores.',
            'code.unique' => 'This code is already used by another method.',
            'logistics_provider_id.required' => 'Please assign a logistics provider.',
            'logistics_provider_id.exists' => 'The selected provider does not exist.',
        ];
    }

    public function setMethod(ShippingMethod $method): void
    {
        $this->method = $method;
        $this->name = $method->name;
        $this->code = $method->code;
        $this->logistics_provider_id = $method->logistics_provider_id;
        $this->type = $method->type;
        $this->delivery_time_unit = $method->delivery_time_unit;
        $this->supports_returns = $method->supports_returns;
        $this->description = $method->description ?? '';
        $this->icon = $method->icon ?? '';
        $this->sort_order = $method->sort_order;
        $this->status = $method->status instanceof ShippingMethodStatus
            ? $method->status->value
            : $method->status;
    }

    public function store(): void
    {
        $this->validate();

        ShippingMethod::create([
            'name' => $this->name,
            'code' => $this->code,
            'logistics_provider_id' => $this->logistics_provider_id,
            'type' => $this->type,
            'delivery_time_unit' => $this->delivery_time_unit,
            'supports_returns' => $this->supports_returns,
            'description' => $this->description ?: null,
            'icon' => $this->icon ?: null,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ]);
    }

    public function update(): void
    {
        $this->validate();

        $this->method->update([
            'name' => $this->name,
            'code' => $this->code,
            'logistics_provider_id' => $this->logistics_provider_id,
            'type' => $this->type,
            'delivery_time_unit' => $this->delivery_time_unit,
            'supports_returns' => $this->supports_returns,
            'description' => $this->description ?: null,
            'icon' => $this->icon ?: null,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ]);
    }
}
