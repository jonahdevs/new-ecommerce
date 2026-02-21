<?php

namespace App\Livewire\Forms\Admin;

use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;
use App\Models\Tag;

class TagForm extends Form
{
    public ?Tag $tag = null;
    public $name = '';
    public $slug = '';
    public $description = '';
    public $color = '#6366F1';
    public $is_active = true;
    public $sort_order = 0;

    public function rules()
    {
        $tagId = $this->tag?->id;

        return [
            'name' => 'required|string|max:255|unique:tags,name,' . $tagId,
            'slug' => 'nullable|string|max:255|unique:tags,slug,' . $tagId,
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:7',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function setTag(Tag $tag): void
    {
        $this->tag = $tag;

        $this->fill($tag->only([
            'name',
            'slug',
            'description',
            'color',
            'is_active',
            'sort_order',
        ]));
    }

    public function store(): Tag
    {
        $data = $this->validate();

        $data['slug'] = $this->resolveSlug();

        $tag = Tag::create($data);

        $this->reset();

        return $tag;
    }

    public function update(): void
    {
        $data = $this->validate();

        $data['slug'] = $this->resolveSlug();

        $this->tag->update($data);
    }

    // -----------------------------------------------

    private function resolveSlug(): string
    {
        return Str::slug($this->slug ?: $this->name);
    }
}
