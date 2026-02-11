<?php

namespace App\Livewire\Forms\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Support\Facades\Storage;
use Livewire\Form;
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

    public string $product_type = 'simple';

    // Tabs
    // General Tab
    public ?float $price = null;

    public ?float $sale_price = null;

    public ?float $cost_price = null;

    // Inventory
    public string $sku = '';

    public bool $manage_stock = false;

    public int $stock_quantity = 0;

    public ?string $allow_backorder = 'no';

    public int $low_stock_threshold = 10;

    public string $stock_status = 'in_stock';

    public ?bool $sold_individually = false;


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

    public $published_at = null;

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
    public $brand_id = '';

    // new category
    public string $newCategoryName = '';
    public ?int $newCategoryParentId = null;

    // new brand
    public string $newBrandName = '';
    public ?string $newBrandWebsite = null;

    public array $selectedUpsells = [];

    public array $selectedCrossSells = [];

    /**
     * Validation rules
     */
    public function rules(): array
    {
        $productId = $this->product?->id;

        return [
            // Basic Information
            'name' => 'required|string|max:255',
            'model_number' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $productId,
            'short_description' => 'nullable|string|max:500',
            'description' => 'required|string',
            'product_type' => 'required|in:simple,variable',

            // Pricing
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'cost_price' => 'nullable|numeric|min:0',

            // Inventory
            'sku' => 'required|string|max:100|unique:products,sku,' . $productId,

            'manage_stock' => 'boolean',
            'stock_quantity' => 'required_if:manage_stock,true|integer|min:0',
            'allow_backorder' => 'required_if:manage_stock,true|in:no,notify,yes',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'stock_status' => 'required_without:manage_stock|in:in_stock,out_of_stock,backorder',

            'sold_individually' => 'boolean',

            // Shipping
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',

            // SEO
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|string|max:255',

            // Status
            'status' => 'required|in:draft,scheduled,published,archived',
            'published_at' => 'required_if:status,scheduled|nullable|date',
            'is_featured' => 'boolean',

            // Images
            'image' => [
                !is_null($this->product)
                ? 'required_without:existing_image'
                : 'required',
                'nullable',
                'image',
                'max:2048'
            ],
            'images.*' => 'nullable|image|max:2048',

            // Relationships
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'brand_id' => 'nullable|exists:brands,id',
            'selectedUpsells' => 'nullable|array',
            'selectedUpsells.*' => 'exists:products,id',
            'selectedCrossSells' => 'nullable|array',
            'selectedCrossSells.*' => 'exists:products,id',
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'slug.required' => 'Product slug is required.',
            'slug.unique' => 'This slug is already taken.',
            'price.required' => 'Price is required.',
            'price.min' => 'Price must be at least 0.',
            'sale_price.lt' => 'Sale price must be less than regular price.',
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU is already taken.',
            'stock_quantity.required_if' => 'Stock quantity is required when managing stock.',
            'published_at.required_if' => 'Published date is required for scheduled products.',
            'image.image' => 'The file must be an image.',
            'image.max' => 'Image size must not exceed 2MB.',
        ];
    }

    /**
     * Custom attribute names
     */
    public function validationAttributes(): array
    {
        return [
            'name' => 'product name',
            'model_number' => 'model number',
            'short_description' => 'short description',
            'sale_price' => 'sale price',
            'cost_price' => 'cost price',
            'stock_quantity' => 'stock quantity',
            'allow_backorder' => 'backorder setting',
            'low_stock_threshold' => 'low stock threshold',
            'stock_status' => 'stock status',
            'meta_title' => 'meta title',
            'meta_description' => 'meta description',
            'meta_keywords' => 'meta keywords',
            'canonical_url' => 'canonical URL',
            'published_at' => 'published date',
            'category_ids' => 'categories',
            'brand_id' => 'brand',
        ];
    }

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

        $this->product_type = $product->product_type ?? 'simple';

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
        $this->published_at = $product->published_at;
        $this->is_featured = $product->is_featured;

        // Fill categories
        $this->category_ids = $product->categories->pluck('id')->toArray();

        // Fill tags
        $this->tag_ids = $product->tags->pluck('id')->toArray();

        // Fill brand
        $this->brand_id = $product->brand_id;

        $this->selectedUpsells = $product->upsells()->pluck('related_product_id')->toArray();
        $this->selectedCrossSells = $product->crossSells()->pluck('related_product_id')->toArray();

        // Fill existing images
        $this->existing_image = $product->image_path;
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
        // Validate the form
        $this->validate();

        $product = Product::create([
            'name' => $this->name,
            'model_number' => $this->model_number,
            'slug' => $this->slug ?: Str::slug($this->name),
            'short_description' => $this->short_description,
            'description' => $this->description,
            'product_type' => $this->product_type,
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
            'published_at' => $this->published_at,
            'is_featured' => $this->is_featured,
            'brand_id' => $this->brand_id ?: null,
        ]);

        // Sync categories
        if (!empty($this->category_ids)) {
            $product->categories()->sync($this->category_ids);
        }

        // Sync tags
        if (!empty($this->tag_ids)) {
            $product->tags()->sync($this->tag_ids);
        }

        if (!empty($this->selectedUpsells)) {
            $product->upsells()->sync($this->selectedUpsells);
        }

        if (!empty($this->selectedCrossSells)) {
            $product->crossSells()->sync($this->selectedCrossSells);
        }

        // Handle image upload
        $this->handleImageUpload($product);

        return $product;
    }

    /**
     * Update the product
     */
    public function update(): void
    {
        \Log::info('Updating product with data: ' . json_encode($this->all(), JSON_PRETTY_PRINT));
        // Validate the form
        $this->validate();

        $this->product->update([
            'name' => $this->name,
            'model_number' => $this->model_number,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'product_type' => $this->product_type,
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
            'published_at' => $this->published_at,
            'is_featured' => $this->is_featured,
            'brand_id' => $this->brand_id ?: null,
        ]);

        // Sync categories
        $this->product->categories()->sync($this->category_ids);

        // Sync tags
        $this->product->tags()->sync($this->tag_ids);

        //  Sync upsells
        $this->product->upsells()->sync($this->selectedUpsells);

        //  Sync cross-sells
        $this->product->crossSells()->sync($this->selectedCrossSells);

        // Handle image upload
        $this->handleImageUpload($this->product);
    }

    /**
     * Handle image upload for product
     */
    private function handleImageUpload(Product $product): void
    {
        // Handle main product image
        if ($this->image) {
            // Delete old image if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            // Store new image
            $imagePath = $this->image->store('products', 'public');

            $product->update(['image_path' => $imagePath]);
        }

        // Handle gallery images
        if (!empty($this->images)) {
            $existingImages = $product->images ?? [];

            foreach ($this->images as $image) {
                $imagePath = $image->store('products/gallery', 'public');
                $existingImages[] = $imagePath;
            }

            $product->update(['images' => $existingImages]);
        }

        // Handle image deletions
        if (!empty($this->imagesToDelete)) {
            foreach ($this->imagesToDelete as $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $existingImages = $product->images ?? [];
            $remainingImages = array_diff($existingImages, $this->imagesToDelete);

            $product->update(['images' => array_values($remainingImages)]);

            // Clear the deletion queue
            $this->imagesToDelete = [];
        }
    }

    /**
     * Mark a gallery image for deletion
     */
    public function removeGalleryImage(string $imagePath): void
    {
        if (!in_array($imagePath, $this->imagesToDelete)) {
            $this->imagesToDelete[] = $imagePath;
        }

        // Remove from existing images display
        $this->existingImages = array_filter(
            $this->existingImages,
            fn($path) => $path !== $imagePath
        );
    }

    /**
     * Remove a newly uploaded image before saving
     */
    public function removeNewImage(int $index): void
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }
}
