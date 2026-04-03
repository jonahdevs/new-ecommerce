<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\CategorySection;
use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Models\CategoryPlacement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Livewire\Form;
use Livewire\WithFileUploads;

class CategoryForm extends Form
{
    use WithFileUploads;

    public ?Category $category = null;

    // General
    public string $name = '';

    public string $slug = '';

    public $parent_id = null;

    public string $description = '';

    // Status
    public string $status = CategoryStatus::DRAFT->value;

    // Placements — array of section values e.g. ['navbar', 'homepage_featured']
    public array $placements = [];

    // Media
    public $image_path = null;

    public $image_icon = null;

    public string $icon_svg = '';

    // Existing media paths (for previews)
    public ?string $existingBanner = null;

    public ?string $existingImageIcon = null;

    // SEO
    public string $meta_title = '';

    public string $meta_description = '';

    public array $meta_keywords = [];

    public function rules(): array
    {
        $categoryId = $this->category?->id;

        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug,'.$categoryId],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:'.implode(',', array_column(CategoryStatus::cases(), 'value'))],
            'placements' => ['array'],
            'placements.*' => ['string', 'in:'.implode(',', array_column(CategorySection::cases(), 'value'))],
            'image_path' => ['nullable', 'image', 'max:2048'],
            'image_icon' => ['nullable', 'image', 'max:1024'],
            'icon_svg' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'array'],
        ];
    }

    public function fromCategory(Category $category): void
    {
        $this->category = $category;

        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->parent_id = $category->parent_id;
        $this->description = $category->description ?? '';
        $this->status = $category->status->value;
        $this->icon_svg = $category->icon_svg ?? '';
        $this->meta_title = $category->meta_title ?? '';
        $this->meta_description = $category->meta_description ?? '';
        $this->meta_keywords = $category->meta_keywords ?? [];

        // Load existing placements as array of section values
        $this->placements = $category->placements
            ->pluck('section')
            ->map(fn ($section) => $section->value)
            ->toArray();

        // Previews
        $this->existingBanner = $category->image_path;
        $this->existingImageIcon = $category->image_icon;
    }

    public function store(): Category
    {
        $this->validate();

        $category = Category::create($this->prepareData());

        $this->syncPlacements($category);

        return $category;
    }

    public function update(): Category
    {
        $this->validate();

        $this->category->update($this->prepareData());

        $this->syncPlacements($this->category);

        return $this->category->fresh();
    }

    // Sync placements — delete removed, add new, preserve existing sort_order
    protected function syncPlacements(Category $category): void
    {
        $incoming = collect($this->placements);

        // Delete placements that were unchecked
        $category->placements()
            ->whereNotIn('section', $incoming->toArray())
            ->delete();

        // Add newly checked placements (skip existing ones)
        $existing = $category->placements()->pluck('section')->map(fn ($s) => $s->value);

        $incoming->diff($existing)->each(function (string $sectionValue) use ($category) {
            // Append at the end of the section's current order
            $maxOrder = CategoryPlacement::where('section', $sectionValue)->max('sort_order') ?? 0;

            $category->placements()->create([
                'section' => CategorySection::from($sectionValue),
                'sort_order' => $maxOrder + 1,
            ]);
        });
    }

    protected function prepareData(): array
    {
        $data = [
            'name' => $this->name,
            'slug' => $this->slug ?: Str::slug($this->name),
            'parent_id' => $this->parent_id ?: null,
            'description' => $this->description ?: null,
            'status' => $this->status,
            'icon_svg' => $this->icon_svg ?: null,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'meta_keywords' => ! empty($this->meta_keywords) ? $this->meta_keywords : null,
        ];

        // Handle image uploads
        if ($this->image_path instanceof UploadedFile) {
            $data['image_path'] = $this->image_path->store('categories/banners', 'public');
        } else {
            $data['image_path'] = $this->existingBanner;
        }

        if ($this->image_icon instanceof UploadedFile) {
            $data['image_icon'] = $this->image_icon->store('categories/icons', 'public');
        } else {
            $data['image_icon'] = $this->existingImageIcon;
        }

        return $data;
    }
}
