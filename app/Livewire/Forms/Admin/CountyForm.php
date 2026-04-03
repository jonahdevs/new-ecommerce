<?php

namespace App\Livewire\Forms\Admin;

use App\Models\County;
use Livewire\Form;

class CountyForm extends Form
{
    public ?County $county = null;

    public string $name = '';

    public string $code = '';

    public string|int $shipping_zone_id = '';

    public function rules(): array
    {
        $uniqueCode = 'nullable|string|max:20|alpha_dash|unique:counties,code';

        if ($this->county) {
            $uniqueCode .= ",{$this->county->id}";
        }

        return [
            'name' => 'required|string|max:100',
            'code' => $uniqueCode,
            'shipping_zone_id' => 'required|exists:shipping_zones,id',
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_zone_id.required' => 'Please assign a shipping zone to this county.',
            'shipping_zone_id.exists' => 'The selected zone does not exist.',
            'code.alpha_dash' => 'Code may only contain letters, numbers, dashes and underscores.',
        ];
    }

    public function setCounty(County $county): void
    {
        $this->county = $county;
        $this->name = $county->name;
        $this->code = $county->code ?? '';
        $this->shipping_zone_id = $county->shipping_zone_id;
    }

    public function store(): void
    {
        $this->validate();

        County::create([
            'name' => $this->name,
            'code' => $this->code ?: null,
            'shipping_zone_id' => $this->shipping_zone_id,
        ]);
    }

    public function update(): void
    {
        $this->validate();

        $this->county->update([
            'name' => $this->name,
            'code' => $this->code ?: null,
            'shipping_zone_id' => $this->shipping_zone_id,
        ]);
    }
}
