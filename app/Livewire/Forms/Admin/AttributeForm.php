<?php

namespace App\Livewire\Forms\Admin;

use App\Models\Attribute;
use Livewire\Form;
use Livewire\Attributes\Validate;
use Illuminate\Support\Str;

class AttributeForm extends Form
{
    public ?Attribute $attribute = null;

    #[Validate('required|min:2|max:255')]
    public $name = '';

    #[Validate('nullable|max:255')]
    public $slug = '';

    #[Validate('required|in:select,radio,checkbox,text,textarea,color,swatch,button')]
    public $type = 'select';

    public $description = '';
    public $is_active = true;
    public $is_visible = true;
    public $used_for_variations = true;
    public $sort_order = 0;

    public function setAttribute(Attribute $attribute)
    {
        $this->attribute = $attribute;
        $this->fill($attribute->toArray());
    }

    public function store()
    {
        $this->validate(['slug' => 'nullable|unique:attributes,slug']);

        if (empty($this->slug)) {
            $this->slug = Str::slug($this->name);
        }

        return Attribute::create($this->all());
    }

    public function update()
    {
        $this->validate(['slug' => 'nullable|unique:attributes,slug,' . $this->attribute->id]);

        if (empty($this->slug)) {
            $this->slug = Str::slug($this->name);
        }

        $this->attribute->update($this->all());
    }
}
