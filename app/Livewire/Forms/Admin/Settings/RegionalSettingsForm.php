<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\RegionalSettings;
use Livewire\Form;

class RegionalSettingsForm extends Form
{
    public string $weight_unit = 'kg';   // kg | lb | g | oz

    public string $dimension_unit = 'cm';   // cm | m | inch | ft

    public function rules(): array
    {
        return [
            'weight_unit' => ['required', 'in:kg,lb,g,oz'],
            'dimension_unit' => ['required', 'in:cm,m,inch,ft'],
        ];
    }

    public function fromSettings(RegionalSettings $settings): void
    {
        $this->weight_unit = $settings->weight_unit;
        $this->dimension_unit = $settings->dimension_unit;
    }

    public function save(RegionalSettings $settings): void
    {
        $this->validate();

        $settings->weight_unit = $this->weight_unit;
        $settings->dimension_unit = $this->dimension_unit;

        $settings->save();
    }
}
