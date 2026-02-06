<?php

namespace App\Livewire\Forms\Admin;

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CategoryForm extends Form
{
    public ?Category $category = null;

    #[Validate('required|min:3|max:255')]
    public $name = '';

    #[Validate('nullable|max:255')] // Unique check handled in store/update
    public $slug = '';

    public $parent_id = null;
    public $description = '';
    public $is_active = true;
    public $is_featured = false;
    public $show_in_navbar = false;

    // Media
    #[Validate('nullable|image|max:2048')] // 2MB Max
    public $image_path;

    #[Validate('nullable|image|max:1024')] // 1MB Max
    public $image_icon;

    public $icon_svg = '';

    // SEO
    public $meta_title = '';
    public $meta_description = '';
    public $meta_keywords = [];

    public function setCategory(Category $category)
    {
        $this->category = $category;
        $this->fill($category->toArray());
        // Fix for JSON field
        $this->meta_keywords = $category->meta_keywords ?? [];
    }

    public function store()
    {
        $this->validate(['slug' => 'nullable|unique:categories,slug']);

        $data = $this->prepareData();
        Category::create($data);
    }

    public function update()
    {
        $this->validate(['slug' => 'nullable|unique:categories,slug,' . $this->category->id]);

        $data = $this->prepareData();
        $this->category->update($data);
    }

    protected function prepareData()
    {
        $data = $this->except(['category', 'image_path', 'image_icon']);

        if (empty($this->slug)) {
            $data['slug'] = Str::slug($this->name);
        }

        if ($this->image_path) {
            $data['image_path'] = $this->image_path->store('categories/banners', 'public');
        }

        if ($this->image_icon) {
            $data['image_icon'] = $this->image_icon->store('categories/icons', 'public');
        }

        return $data;
    }
}
