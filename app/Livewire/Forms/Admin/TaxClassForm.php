<?php

namespace App\Livewire\Forms\Admin;

use App\Models\TaxClass;
use Livewire\Form;

class TaxClassForm extends Form
{
    public ?TaxClass $taxClass = null;

    public string $name = '';

    public string $rate = '';

    public string $description = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function setTaxClass(TaxClass $taxClass): void
    {
        $this->taxClass = $taxClass;
        $this->name = $taxClass->name;
        $this->rate = (string) $taxClass->rate;
        $this->description = $taxClass->description ?? '';
    }

    public function store(): void
    {
        $this->validate();

        TaxClass::create([
            'name' => $this->name,
            'rate' => $this->rate,
            'description' => $this->description ?: null,
        ]);
    }

    public function update(): void
    {
        $this->validate();

        $this->taxClass->update([
            'name' => $this->name,
            'rate' => $this->rate,
            'description' => $this->description ?: null,
        ]);
    }
}
