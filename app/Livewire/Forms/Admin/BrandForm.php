<?php

namespace App\Livewire\Forms\Admin;

use App\Models\Brand;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Livewire\Form;

class BrandForm extends Form
{
    public ?Brand $brand = null;

    public $name = '';

    public $slug = '';

    public $description = '';

    public $website_url = '';

    public $is_active = true;

    public $sort_order = 0;

    // Media
    public $logo_path;

    // SEO
    public $meta_title = '';

    public $meta_description = '';

    public $meta_keywords = '';

    public function rules()
    {
        $brandId = $this->brand?->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:brands,slug,'.$brandId],
            'description' => ['nullable', 'string'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
        ];
    }

    public function setBrand(Brand $brand)
    {
        $this->brand = $brand;
        $this->fill($brand->toArray());
        // Fix for JSON field
        $this->meta_keywords = $brand->meta_keywords ?? [];
    }

    public function store()
    {
        $this->validate();

        $data = $this->prepareData();

        return Brand::create($data);
    }

    public function update()
    {
        $this->validate();

        $data = $this->prepareData();
        $this->brand->update($data);

        return $this->brand;
    }

    protected function prepareData()
    {
        $data = $this->except(['brand', 'logo_path']);

        // Auto-generate slug if empty
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($this->name);
        }

        // Handle logo upload
        $data = array_merge($data, $this->handleLogoUpload());

        return $data;
    }

    protected function handleLogoUpload(): array
    {
        if (! ($this->logo_path instanceof UploadedFile)) {
            return [];
        }

        $paths = app(ImageService::class)->storeWithWebP($this->logo_path, 'brands/logos');

        return [
            'logo_path' => $paths['original'],
            'logo_webp' => $paths['webp'],
        ];
    }
}
