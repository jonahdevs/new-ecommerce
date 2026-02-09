<?php

namespace App\Livewire\Forms\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Livewire\Form;
use Livewire\Attributes\Validate;
use Illuminate\Support\Str;

class ProductForm extends Form
{
    public ?Product $product = null;

    // Basic Information

    public string $name = '';

    public ?string $model_number = '';

    public string $slug = '';

    public ?string $short_description = null;

    public ?string $description = null;

    // Tabs
    // General Tab
    public float $price = 0;

    public ?float $sale_price = null;

    public ?float $cost_price = null;

    // Inventory
    public string $sku = '';

    public bool $manage_stock = false;

    public int $stock_quantity = 0;

    public string $allow_backorder = 'no';

    public int $low_stock_threshold = 10;

    public string $stock_status = 'in_stock';

    public bool $sold_individually = false;


    // Shipping
    public ?float $weight = null;

    public ?float $length = null;
    public ?float $width = null;
    public ?float $height = null;

    // Linked products


    // SEO & Meta Information
    public ?string $meta_title = null;

    public ?string $meta_description = null;

    public $meta_keywords = null;

    public ?string $canonical_url = null;


    // Status Visibility
    public string $status = 'draft';

    public bool $is_featured = false;


    // image properties
    public $image = null;

    public $images = [];

    public $existing_image = null;

    public $existingImages = [];

    public $imagesToDelete = [];

    // categories

    public array $category_ids = [];

    // tags
    public array $tag_ids = [];
    public string $newTagInput = '';

    // brand
    public  $brand_id = '';

    // new category
    public string $newCategoryName = '';
    public ?int $newCategoryParentId = null;

    // new brand
    public string $newBrandName = '';
    public ?string $newBrandWebsite = null;

    /**
     * Set the product for editing
     */
    public function setProduct(Product $product): void
    {
        $this->product = $product;

        // Fill basic information
        $this->name = $product->name;
        $this->model_number = $product->model_number;
        $this->slug = $product->slug;
        $this->short_description = $product->short_description;
        $this->description = $product->description;

        // Fill pricing
        $this->price = $product->price;
        $this->sale_price = $product->sale_price;
        $this->cost_price = $product->cost_price;

        // Fill inventory
        $this->sku = $product->sku;
        $this->manage_stock = $product->manage_stock;
        $this->stock_quantity = $product->stock_quantity;
        $this->allow_backorder = $product->allow_backorder;
        $this->low_stock_threshold = $product->low_stock_threshold;
        $this->stock_status = $product->stock_status;
        $this->sold_individually = $product->sold_individually;

        // Fill shipping
        $this->weight = $product->weight;
        $this->length = $product->length;
        $this->width = $product->width;
        $this->height = $product->height;

        // Fill SEO
        $this->meta_title = $product->meta_title;
        $this->meta_description = $product->meta_description;
        $this->meta_keywords = $product->meta_keywords;
        $this->canonical_url = $product->canonical_url;

        // Fill status
        $this->status = $product->status;
        $this->is_featured = $product->is_featured;

        // Fill categories
        $this->category_ids = $product->categories->pluck('id')->toArray();

        // Fill tags
        $this->tag_ids = $product->tags->pluck('id')->toArray();

        // Fill brand
        $this->brand_id = $product->brand_id;

        // Fill existing images
        $this->existing_image = $product->image;
        $this->existingImages = $product->images ?? [];
    }

    /**
     * Add tags from comma-separated input
     */
    public function addTags()
    {
        // Trim and filter empty values
        $tagNames = array_filter(
            array_map('trim', explode(',', $this->newTagInput)),
            fn($name) => !empty($name)
        );


        if (empty($tagNames)) {
            return;
        }

        foreach ($tagNames as $tagName) {
            $this->addOrAttachTag($tagName);
        }

        // Clear input after adding
        $this->newTagInput = '';
    }

    /**
     * Add a single tag or attach existing one
     */
    private function addOrAttachTag(string $tagName): void
    {
        // Normalize the tag name
        $normalizedName = trim($tagName);

        if (empty($normalizedName)) {
            return;
        }

        // Check if tag exists (case-insensitive)
        $tag = Tag::whereRaw('LOWER(name) = ?', [strtolower($normalizedName)])->first();

        // If tag doesn't exist, create it
        if (!$tag) {
            $tag = Tag::create([
                'name' => $normalizedName,
                'slug' => Str::slug($normalizedName),
                'color' => '#3B82F6', // Default blue color
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }

        // Add to tag_ids if not already present
        if (!in_array($tag->id, $this->tag_ids)) {
            $this->tag_ids[] = $tag->id;
        }
    }
    /**
     * Remove a tag from the selection
     */
    public function removeTag(int $tagId): void
    {
        $this->tag_ids = array_values(
            array_filter($this->tag_ids, fn($id) => $id != $tagId)
        );
    }

    /**
     * Add multiple tags from the "most used" modal
     */
    public function addSelectedTags(array $tagIds): void
    {
        foreach ($tagIds as $tagId) {
            if (!in_array($tagId, $this->tag_ids)) {
                $this->tag_ids[] = $tagId;
            }
        }
    }

    /**
     * Get selected tags as collection
     */
    public function getSelectedTags()
    {
        if (empty($this->tag_ids)) {
            return collect();
        }

        return Tag::whereIn('id', $this->tag_ids)->get();
    }

    /**
     * Create a new category and add it to selection
     */
    public function createCategory(): ?Category
    {
        // Normalize the category name
        $this->newCategoryName = trim($this->newCategoryName);

        if (empty($this->newCategoryName)) {
            return null;
        }

        // Check if category already exists (case-insensitive)
        $existingCategory = Category::whereRaw('LOWER(name) = ?', [strtolower($this->newCategoryName)])->first();

        if ($existingCategory) {
            // If category exists, just add it to selection
            if (!in_array($existingCategory->id, $this->category_ids)) {
                $this->category_ids[] = $existingCategory->id;
            }

            $this->resetCategoryForm();
            return $existingCategory;
        }

        // Create the category
        $category = Category::create([
            'name' => $this->newCategoryName,
            'slug' => Str::slug($this->newCategoryName),
            'parent_id' => $this->newCategoryParentId,
            'is_active' => true,
            'is_featured' => false,
            'show_in_navbar' => false,
            'sort_order' => 0,
        ]);

        // Add to selected categories
        if (!in_array($category->id, $this->category_ids)) {
            $this->category_ids = array_merge($this->category_ids, [$category->id]);
        }


        // Reset form
        $this->resetCategoryForm();

        return $category;
    }

    /**
     * Reset category creation form
     */
    public function resetCategoryForm(): void
    {
        $this->newCategoryName = '';
        $this->newCategoryParentId = null;
    }

    /**
     * Create a new brand and select it
     */
    public function createBrand(): ?Brand
    {
        // Normalize the brand name
        $this->newBrandName = trim($this->newBrandName);

        if (empty($this->newBrandName)) {
            return null;
        }

        // Check if brand already exists (case-insensitive)
        $existingBrand = Brand::whereRaw('LOWER(name) = ?', [strtolower($this->newBrandName)])->first();

        if ($existingBrand) {
            // If brand exists, just select it
            $this->brand_id = $existingBrand->id;
            $this->resetBrandForm();
            return $existingBrand;
        }

        // Create the brand
        $brand = Brand::create([
            'name' => $this->newBrandName,
            'slug' => Str::slug($this->newBrandName),
            'website_url' => $this->newBrandWebsite,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        // Select the new brand
        $this->brand_id = $brand->id;

        // Reset form
        $this->resetBrandForm();

        return $brand;
    }

    /**
     * Reset brand creation form
     */
    public function resetBrandForm(): void
    {
        $this->newBrandName = '';
        $this->newBrandWebsite = null;
    }


    /**
     * Store the product
     */
    public function store(): Product
    {
        // Add your validation here

        $product = Product::create([
            'name' => $this->name,
            'model_number' => $this->model_number,
            'slug' => $this->slug ?: Str::slug($this->name),
            'short_description' => $this->short_description,
            'description' => $this->description,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'cost_price' => $this->cost_price,
            'sku' => $this->sku,
            'manage_stock' => $this->manage_stock,
            'stock_quantity' => $this->stock_quantity,
            'allow_backorder' => $this->allow_backorder,
            'low_stock_threshold' => $this->low_stock_threshold,
            'stock_status' => $this->stock_status,
            'sold_individually' => $this->sold_individually,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'canonical_url' => $this->canonical_url,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
        ]);

        // Sync categories
        if (!empty($this->category_ids)) {
            $product->categories()->sync($this->category_ids);
        }

        // Sync tags
        if (!empty($this->tag_ids)) {
            $product->tags()->sync($this->tag_ids);
        }

        // Handle image upload (you'll need to implement this based on your storage strategy)
        // $this->handleImageUpload($product);

        return $product;
    }

    /**
     * Update the product
     */
    public function update(): void
    {
        // Add your validation here

        $this->product->update([
            'name' => $this->name,
            'model_number' => $this->model_number,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'cost_price' => $this->cost_price,
            'sku' => $this->sku,
            'manage_stock' => $this->manage_stock,
            'stock_quantity' => $this->stock_quantity,
            'allow_backorder' => $this->allow_backorder,
            'low_stock_threshold' => $this->low_stock_threshold,
            'stock_status' => $this->stock_status,
            'sold_individually' => $this->sold_individually,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'canonical_url' => $this->canonical_url,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
        ]);

        // Sync categories
        $this->product->categories()->sync($this->category_ids);

        // Sync tags
        $this->product->tags()->sync($this->tag_ids);

        // Handle image upload
        // $this->handleImageUpload($this->product);
    }
}
