<?php

namespace App\Livewire\Forms\Admin;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CategoryForm extends Form
{
    public ?Category $category = null;

    public $name = '';
    public $slug = '';

    public $parent_id = null;
    public $description = '';
    public $is_active = true;
    public $is_featured = false;
    public $show_in_navbar = false;

    // Media
    public $image_path;
    public $image_icon;

    public $icon_svg = '';

    // SEO
    public $meta_title = '';
    public $meta_description = '';
    public $meta_keywords = '';

    public function rules()
    {
        $categoryId = $this->category?->id;

        return [
            "name" => ["required", "string", "min:3", "max:255"],
            "slug" => ["nullable", "string", "max:255", "unique:categories,slug," . $categoryId],
            "parent_id" => ["nullable", "exists:categories,id"],
            "description" => ["nullable", "string"],
            "is_active" => ["boolean"],
            "is_featured" => ["boolean"],
            "show_in_navbar" => ["boolean"],
            "icon_svg" => ["nullable", "string"],
            "meta_title" => ["nullable", "string", "max:255"],
            "meta_description" => ["nullable", "string"],
            "meta_keywords" => ["nullable", "string"],
        ];
    }

    public function setCategory(Category $category)
    {
        $this->category = $category;
        $this->fill($category->toArray());
        // Fix for JSON field
        $this->meta_keywords = $category->meta_keywords ?? [];
    }

    public function store()
    {
        $this->validate();

        $data = $this->prepareData();
        return Category::create($data);
    }

    public function update()
    {
        $this->validate();

        $data = $this->prepareData();
        $this->category->update($data);
        return $this->category;
    }

    protected function prepareData()
    {
        $data = $this->except(['category', 'image_path', 'image_icon']);

        // Auto-generate slug if empty
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($this->name);
        }

        // Handle image uploads
        $data = array_merge($data, $this->handleImageUploads());

        return $data;
    }

    protected function handleImageUploads(): array
    {
        $uploads = [];

        if ($this->image_path instanceof UploadedFile) {
            $uploads['image_path'] = $this->image_path->store('categories/banners', 'public');
        }

        if ($this->image_icon instanceof UploadedFile) {
            $uploads['image_icon'] = $this->image_icon->store('categories/icons', 'public');
        }

        return $uploads;
    }
}
