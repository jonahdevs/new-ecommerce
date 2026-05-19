<?php

namespace App\Livewire\Forms\Admin;

use App\Models\Attribute;
use Illuminate\Support\Str;
use Livewire\Form;

class AttributeForm extends Form
{
    public ?Attribute $attribute = null;

    public $name = '';

    public $slug = '';

    public $watch_type = 'select';

    public $watch_shape = 'default';

    public $watch_size = null;

    public $is_active = true;

    public $sort_order = 0;

    public function rules(): array
    {
        $attributeId = $this->attribute?->id;

        return [
            'name' => 'required|min:2|max:255',
            'slug' => ['nullable', 'max:255', 'unique:attributes,slug,'.$attributeId],
            'watch_type' => 'required|in:select,label,color,image',
            'watch_shape' => 'required|in:default,square,rounded-corners,circle',
            'watch_size' => 'nullable|integer|in:24,32,40,48,56,64',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function setAttribute(Attribute $attribute): void
    {
        $this->attribute = $attribute;
        $this->fill($attribute->only([
            'name',
            'slug',
            'watch_type',
            'watch_shape',
            'watch_size',
            'is_active',
            'sort_order',
        ]));
    }

    public function store(): Attribute
    {
        $this->validate();

        if (empty($this->slug)) {
            $this->slug = Str::slug($this->name);
        }

        return Attribute::create($this->only([
            'name',
            'slug',
            'watch_type',
            'watch_shape',
            'watch_size',
            'is_active',
            'sort_order',
        ]));
    }

    public function update(): void
    {
        $this->validate();

        if (empty($this->slug)) {
            $this->slug = Str::slug($this->name);
        }

        $this->attribute->update($this->only([
            'name',
            'slug',
            'watch_type',
            'watch_shape',
            'watch_size',
            'description',
            'is_active',
            'sort_order',
        ]));
    }
}
