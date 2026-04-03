<?php

namespace App\Livewire\Forms\Admin;

use App\Models\Area;
use Livewire\Form;

class AreaForm extends Form
{
    public ?Area $area = null;

    public string $name = '';

    public string|int $county_id = '';

    public string|int $shipping_zone_id = ''; // Optional override

    public function rules(): array
    {
        // Name must be unique within the same county
        $uniqueName = 'required|string|max:150|unique:areas,name,NULL,id,county_id,'.($this->county_id ?: 'NULL');

        if ($this->area) {
            $uniqueName = 'required|string|max:150|unique:areas,name,'.$this->area->id.',id,county_id,'.($this->county_id ?: 'NULL');
        }

        return [
            'name' => $uniqueName,
            'county_id' => 'required|exists:counties,id',
            'shipping_zone_id' => 'nullable|exists:shipping_zones,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'An area with this name already exists in the selected county.',
            'county_id.required' => 'Please select a county.',
            'shipping_zone_id.exists' => 'The selected zone does not exist.',
        ];
    }

    public function setArea(Area $area): void
    {
        $this->area = $area;
        $this->name = $area->name;
        $this->county_id = $area->county_id;
        $this->shipping_zone_id = $area->shipping_zone_id ?? '';
    }

    public function store(): void
    {
        $this->validate();

        Area::create([
            'name' => $this->name,
            'county_id' => $this->county_id,
            'shipping_zone_id' => $this->shipping_zone_id ?: null,
        ]);
    }

    public function update(): void
    {
        $this->validate();

        $this->area->update([
            'name' => $this->name,
            'county_id' => $this->county_id,
            'shipping_zone_id' => $this->shipping_zone_id ?: null,
        ]);
    }
}
