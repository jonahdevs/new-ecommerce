<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\ShippingZoneStatus;
use App\Models\ShippingZone;
use Livewire\Form;

class ShippingZoneForm extends Form
{
    public ?ShippingZone $zone = null;

    public string $name = '';
    public string $code = '';
    public string $description = '';
    public string $status = 'active';

    public function rules(): array
    {
        $uniqueCode = 'nullable|string|max:50|alpha_dash|unique:shipping_zones,code';

        if ($this->zone) {
            $uniqueCode .= ",{$this->zone->id}";
        }

        return [
            'name'        => 'required|string|max:100',
            'code'        => $uniqueCode,
            'description' => 'nullable|string|max:500',
            'status'      => 'required|string|in:' . implode(',', array_column(ShippingZoneStatus::cases(), 'value')),
        ];
    }

    public function messages(): array
    {
        return [
            'code.alpha_dash' => 'Code may only contain letters, numbers, dashes and underscores.',
            'code.unique'     => 'This code is already taken by another zone.',
        ];
    }

    public function setZone(ShippingZone $zone): void
    {
        $this->zone        = $zone;
        $this->name        = $zone->name;
        $this->code        = $zone->code ?? '';
        $this->description = $zone->description ?? '';
        $this->status      = $zone->status instanceof ShippingZoneStatus
            ? $zone->status->value
            : $zone->status;
    }

    public function store(): void
    {
        $this->validate();

        ShippingZone::create([
            'name'        => $this->name,
            'code'        => $this->code ?: null,
            'description' => $this->description ?: null,
            'status'      => $this->status,
        ]);
    }

    public function update(): void
    {
        $this->validate();

        $this->zone->update([
            'name'        => $this->name,
            'code'        => $this->code ?: null,
            'description' => $this->description ?: null,
            'status'      => $this->status,
        ]);
    }
}
