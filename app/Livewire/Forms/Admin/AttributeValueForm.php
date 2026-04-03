<?php

namespace App\Livewire\Forms\Admin;

use App\Models\AttributeValue;
use Illuminate\Support\Str;
use Livewire\Form;

class AttributeValueForm extends Form
{
    public ?AttributeValue $attributeValue = null;

    public $value = '';

    public $slug = '';

    public $description = '';

    public $color_code = '#000000';

    public $image_path = '';

    public $sort_order = 0;

    public $is_active = true;

    public function rules(): array
    {
        return [
            'value' => 'required|min:1|max:255',
            'slug' => [
                'nullable',
                'max:255',
                $this->attributeValue
                    ? 'unique:attribute_values,slug,'.$this->attributeValue->id
                    : 'unique:attribute_values,slug',
            ],
            'description' => 'nullable|max:1000',
            'color_code' => 'nullable|max:20',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function setAttributeValue(AttributeValue $attributeValue): void
    {
        $this->attributeValue = $attributeValue;
        $this->fill($attributeValue->only([
            'value',
            'slug',
            'description',
            'color_code',
            'image_path',
            'sort_order',
            'is_active',
        ]));
    }

    public function store(int $attributeId): AttributeValue
    {
        $this->validate();

        if (empty($this->slug)) {
            $this->slug = Str::slug($this->value);
        }

        return AttributeValue::create(array_merge(
            $this->only(['value', 'slug', 'description', 'color_code', 'image_path', 'sort_order', 'is_active']),
            ['attribute_id' => $attributeId]
        ));
    }

    public function update(): void
    {
        $this->validate();

        if (empty($this->slug)) {
            $this->slug = Str::slug($this->value);
        }

        $this->attributeValue->update(
            $this->only(['value', 'slug', 'description', 'color_code', 'image_path', 'sort_order', 'is_active'])
        );
    }
}
