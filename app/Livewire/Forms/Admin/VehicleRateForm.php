<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\VehicleRateStatus;
use App\Enums\VehicleType;
use App\Models\VehicleRate;
use Livewire\Form;

class VehicleRateForm extends Form
{
    public ?VehicleRate $vehicleRate = null;

    public string|int   $shipping_method_id = '';
    public string       $vehicle_type       = '';
    public string       $vehicle_label      = '';
    public string|float $base_rate          = '';
    public string|int   $base_km            = '';
    public string|float $extra_km_rate      = '';
    public string|float $max_weight_kg      = '';
    public string|float $max_volume_m3      = '';
    public string       $status             = 'active';

    public function rules(): array
    {
        return [
            'shipping_method_id' => 'required|exists:shipping_methods,id',
            'vehicle_type'       => 'required|string|in:' . implode(',', array_column(VehicleType::cases(), 'value')),
            'vehicle_label'      => 'required|string|max:100',
            'base_rate'          => 'required|numeric|min:0',
            'base_km'            => 'required|integer|min:1',
            'extra_km_rate'      => 'required|numeric|min:0',
            'max_weight_kg'      => 'nullable|numeric|min:0',
            'max_volume_m3'      => 'nullable|numeric|min:0',
            'status'             => 'required|string|in:' . implode(',', array_column(VehicleRateStatus::cases(), 'value')),
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_type.in'   => 'Please select a valid vehicle type.',
            'base_rate.required' => 'Base rate is required.',
            'base_km.required'  => 'Base KM is required.',
        ];
    }

    public function setVehicleRate(VehicleRate $vehicleRate): void
    {
        $this->vehicleRate       = $vehicleRate;
        $this->shipping_method_id = $vehicleRate->shipping_method_id;
        $this->vehicle_type      = $vehicleRate->vehicle_type instanceof VehicleType
            ? $vehicleRate->vehicle_type->value
            : $vehicleRate->vehicle_type;
        $this->vehicle_label     = $vehicleRate->vehicle_label;
        $this->base_rate         = $vehicleRate->base_rate;
        $this->base_km           = $vehicleRate->base_km;
        $this->extra_km_rate     = $vehicleRate->extra_km_rate;
        $this->max_weight_kg     = $vehicleRate->max_weight_kg ?? '';
        $this->max_volume_m3     = $vehicleRate->max_volume_m3 ?? '';
        $this->status            = $vehicleRate->status instanceof VehicleRateStatus
            ? $vehicleRate->status->value
            : $vehicleRate->status;
    }

    public function store(): void
    {
        $this->validate();

        VehicleRate::create($this->formData());
    }

    /**
     * Versioning: deprecate the existing rate and create a fresh one.
     * Old rate is kept intact for historical delivery order lookups.
     */
    public function update(): void
    {
        $this->validate();

        $this->vehicleRate->update(['status' => VehicleRateStatus::DEPRECATED->value]);

        VehicleRate::create($this->formData());
    }

    private function formData(): array
    {
        return [
            'shipping_method_id' => $this->shipping_method_id,
            'vehicle_type'       => $this->vehicle_type,
            'vehicle_label'      => $this->vehicle_label,
            'base_rate'          => $this->base_rate,
            'base_km'            => $this->base_km,
            'extra_km_rate'      => $this->extra_km_rate,
            'max_weight_kg'      => $this->max_weight_kg ?: null,
            'max_volume_m3'      => $this->max_volume_m3 ?: null,
            'status'             => VehicleRateStatus::ACTIVE->value,
        ];
    }
}
