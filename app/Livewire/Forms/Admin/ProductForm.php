<?php

namespace App\Livewire\Forms\Admin;

use App\Models\Product;
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

    public string $sku = '';

    public $brand_id = null;

    public ?string $description = null;

    public ?string $short_description = null;

    // image properties
    public $image = null;

    public $gallery_images = [];

    public $existing_image = null;

    public $existingGalleryImages = [];

    public $imagesToDelete = [];

    // Pricing
    public float $price = 0;

    public ?float $sale_price = null;

    public ?float $cost_price = null;

    public float $tax_rate = 0;

    // Physical Properties (Shipping)
    public ?float $weight = null;

    public ?float $height = null;

    public ?float $width = null;

    public ?float $length = null;

    // Stock Management
    public bool $manage_stock = true;

    public int $stock_quantity = 0;

    public int $low_stock_threshold = 10;

    public string $stock_status = 'in_stock';

    public int $quantity = 0;

    public bool $track_inventory = false;

    // Backorders
    public bool $allow_backorders = false;

    public ?int $max_backorder_quantity = null;

    public ?string $expected_restock_date = null;

    public ?string $backorder_message = null;

    // Additional Information
    public $technical_specification = null;

    public ?string $estimated_delivery_time = null;

    public ?string $shipping_information = null;

    public ?string $warranty_information = null;

    public ?string $return_policy = null;

    // Status
    public string $status = 'draft';

    public bool $is_active = true;

    public bool $is_featured = false;

    // SEO
    public ?string $meta_title = null;

    public ?string $meta_description = null;

    public $meta_keywords = null;

    public ?string $canonical_url = null;

    // Quotation Settings
    public bool $requires_quotation = false;

    public ?float $min_order_quantity = null;

    public ?string $quotation_notes = null;

    // Product type and variations properties
    public $product_type = 'simple';

    public $variants = [];

    public $variantsToDelete = [];

    public $selectedTab = 'general';

    // Relationships
    public array $selectedCategories = [];

    // Attributes for product
    public array $selectedAttributes = [
        [
            'attribute_id' => null,
            'name' => '',
            'is_new' => true,
            'visible' => true,
            'used_for_variations' => true,
            'values' => '',
        ],
    ];

    public ?int $selectedExistingAttribute = null;

    // Accessories & Related Products
    public array $selectedAccessories = [];

    public array $selectedUpsells = [];

    public array $selectedCrossSells = [];

    public array $selectedRelated = [];

    // Variations (only for variable products)
    // Tag properties
    public $selectedTags = [];

    public $newTag = '';

    // control the auto generation of slug
    public bool $auto_generate_slug = true;


    public function setProduct(Product $product)
    {
        $this->product = $product;
        $this->fill($product->toArray());

        // Ensure JSON fields are arrays
    }

    public function store()
    {
        $this->validate();

        if (empty($this->slug)) {
            $this->slug = Str::slug($this->name);
        }

        return Product::create($this->except('product'));
    }

    public function update()
    {
        $this->validate([
            'slug' => 'nullable|unique:products,slug,' . $this->product->id,
        ]);

        if (empty($this->slug)) {
            $this->slug = Str::slug($this->name);
        }

        $this->product->update($this->except('product'));
    }
}
