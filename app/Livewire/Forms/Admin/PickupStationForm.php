<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\PickupStationStatus;
use App\Models\PickupStation;
use Livewire\Form;

class PickupStationForm extends Form
{
    public ?PickupStation $station = null;

    public string|int $logistics_provider_id = '';

    public string $name = '';

    public string $code = '';

    public string|int $county_id = '';

    public string|int $area_id = '';

    public string $address = '';

    public string $phone = '';

    public string $operating_hours = '';

    public string|float $latitude = '';

    public string|float $longitude = '';

    public string|int $holding_days = 7;

    public string $status = 'active';

    public function rules(): array
    {
        $uniqueCode = 'required|string|max:100|unique:pickup_stations,code';

        if ($this->station) {
            $uniqueCode .= ",{$this->station->id}";
        }

        return [
            'logistics_provider_id' => 'required|exists:logistics_providers,id',
            'name' => 'required|string|max:150',
            'code' => $uniqueCode,
            'county_id' => 'required|exists:counties,id',
            'area_id' => 'nullable|exists:areas,id',
            'address' => 'required|string|max:500',
            'phone' => 'nullable|string|max:20',
            'operating_hours' => 'nullable|string|max:200',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'holding_days' => 'required|integer|min:1|max:30',
            'status' => 'required|string|in:'.implode(',', array_column(PickupStationStatus::cases(), 'value')),
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This code is already used by another station.',
            'logistics_provider_id.required' => 'Please assign a logistics provider.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
        ];
    }

    public function setStation(PickupStation $station): void
    {
        $this->station = $station;
        $this->logistics_provider_id = $station->logistics_provider_id;
        $this->name = $station->name;
        $this->code = $station->code;
        $this->county_id = $station->county_id;
        $this->area_id = $station->area_id ?? '';
        $this->address = $station->address;
        $this->phone = $station->phone ?? '';
        $this->operating_hours = $station->operating_hours ?? '';
        $this->latitude = $station->latitude ?? '';
        $this->longitude = $station->longitude ?? '';
        $this->holding_days = $station->holding_days;
        $this->status = $station->status instanceof PickupStationStatus
            ? $station->status->value
            : $station->status;
    }

    public function store(): void
    {
        $this->validate();

        PickupStation::create($this->formData());
    }

    public function update(): void
    {
        $this->validate();

        $this->station->update($this->formData());
    }

    private function formData(): array
    {
        return [
            'logistics_provider_id' => $this->logistics_provider_id,
            'name' => $this->name,
            'code' => $this->code,
            'county_id' => $this->county_id,
            'area_id' => $this->area_id ?: null,
            'address' => $this->address,
            'phone' => $this->phone ?: null,
            'operating_hours' => $this->operating_hours ?: null,
            'latitude' => $this->latitude ?: null,
            'longitude' => $this->longitude ?: null,
            'holding_days' => $this->holding_days,
            'status' => $this->status,
        ];
    }
}
