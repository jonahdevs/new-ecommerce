<?php

namespace App\Livewire\Forms\Admin;

use App\Models\Tag;
use Livewire\Form;

class TagForm extends Form
{
    public ?Tag $tag = null;

    public string $name = '';

    public ?string $type = null;

    public int $order_column = 0;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'order_column' => 'nullable|integer|min:0',
        ];
    }

    public function setTag(Tag $tag): void
    {
        $this->tag = $tag;
        $this->name = $tag->name;
        $this->type = $tag->type;
        $this->order_column = $tag->order_column ?? 0;
    }

    public function store(): Tag
    {
        $this->validate();

        $tag = Tag::findOrCreate($this->name, $this->type);
        $tag->order_column = $this->order_column;
        $tag->save();

        $this->reset();

        return $tag;
    }

    public function update(): void
    {
        $this->validate();

        $this->tag->setTranslation('name', app()->getLocale(), $this->name);
        $this->tag->type = $this->type;
        $this->tag->order_column = $this->order_column;
        $this->tag->save();
    }
}
