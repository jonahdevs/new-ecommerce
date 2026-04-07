<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\CategoryStatus;
use App\Enums\ProductRelationshipType;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * ProductForm
 *
 * Livewire Form object for the product create/edit workflow.
 * Holds all product field values, validation rules, and persistence logic
 * for the core product record and its relationships.
 *
 * Responsibilities:
 * - Define and validate all product fields
 * - store() / update() the Product model
 * - Sync all relationships (categories, tags, upsells, cross-sells, accessories, grouped)
 * - Handle main image and gallery image uploads/deletions
 * - Provide inline category and brand creation
 * - Provide tag management (add, remove, select from most used)
 *
 * NOT responsible for:
 * - Attributes and variations (owned by BaseProductComponent)
 * - Product-level downloads (loaded by BaseProductComponent, synced via ProductDownloadService)
 * - Grouped products and accessories display state (owned by BaseProductComponent)
 */
class ProductForm extends Form
{
    // =========================================================================
    // PRODUCT MODEL REFERENCE
    // =========================================================================

    /** The product being edited, or null when creating a new product. */
    public ?Product $product = null;

    // =========================================================================
    // BASIC INFORMATION
    // =========================================================================

    /** Product display name. Required. */
    public string $name = '';

    /** Optional manufacturer or internal model reference number. */
    public ?string $model_number = '';

    /**
     * URL slug for the product page.
     * Auto-generated from name on store() if left blank.
     * Must be unique across all products.
     */
    public string $slug = '';

    /** Short marketing summary shown in listings and product cards. Max 500 chars. */
    public ?string $short_description = null;

    /**
     * Full product description rendered on the product detail page.
     * Required. Must contain at least 10 characters of actual text after HTML is stripped.
     */
    public ?string $description = null;

    /**
     * Product type — determines which tabs and fields are shown.
     * One of: simple, variable, grouped, virtual, downloadable.
     */
    public string $type = 'simple';

    // =========================================================================
    // PRICING
    // =========================================================================

    /**
     * Regular selling price.
     * Required for all types except grouped (where price is derived from children).
     */
    public ?float $price = null;

    /**
     * Discounted sale price.
     * Optional. Must be less than regular price when set.
     */
    public ?float $sale_price = null;

    /**
     * Internal cost price for margin calculations.
     * Never shown to customers. Optional.
     */
    public ?float $cost_price = null;

    // =========================================================================
    // INVENTORY
    // =========================================================================

    /**
     * Stock Keeping Unit — unique product identifier.
     * Required for all types except grouped.
     * Must be unique across all products.
     */
    public string $sku = '';

    /**
     * Whether to actively track stock quantity for this product.
     * When false, stock_status is used instead of stock_quantity.
     */
    public bool $manage_stock = false;

    /** Current stock quantity. Only relevant when manage_stock = true. */
    public int $stock_quantity = 0;

    /**
     * Whether to allow orders when stock runs out.
     * Values: 'no' | 'notify' | 'yes'
     * Only relevant when manage_stock = true.
     */
    public ?string $allow_backorder = 'no';

    /**
     * Stock level at which a low-stock notification is triggered.
     * Default 10. Only relevant when manage_stock = true.
     */
    public int $low_stock_threshold = 10;

    /**
     * Manual stock status used when manage_stock = false.
     * Values: 'in_stock' | 'out_of_stock' | 'backorder'
     */
    public string $stock_status = 'in_stock';

    /**
     * When true, customers can only buy one unit of this product per order.
     * Useful for personalised or made-to-order items.
     */
    public ?bool $sold_individually = false;

    // =========================================================================
    // SHIPPING
    // =========================================================================

    /** Product weight in kilograms. Nulled on save for virtual/grouped products. */
    public ?float $weight = null;

    /** Product length in centimetres. Nulled on save for virtual/grouped products. */
    public ?float $length = null;

    /** Product width in centimetres. Nulled on save for virtual/grouped products. */
    public ?float $width = null;

    /** Product height in centimetres. Nulled on save for virtual/grouped products. */
    public ?float $height = null;

    // =========================================================================
    // SEO & META
    // =========================================================================

    /** SEO page title. Recommended 50–60 chars for search engine display. */
    public ?string $meta_title = null;

    /** SEO meta description. Recommended 150–160 chars for search engine display. */
    public ?string $meta_description = null;

    /**
     * Comma-separated keyword string in the form.
     * Stored as a JSON array in the database via productData().
     * Loaded back as an imploded string in setProduct().
     */
    public ?string $meta_keywords = null;

    /**
     * Canonical URL path (path portion only, not including the domain).
     * The domain prefix is shown visually in the SEO partial.
     */
    public ?string $canonical_url = null;

    // =========================================================================
    // STATUS & VISIBILITY
    // =========================================================================

    /**
     * Publication status.
     * Values: 'draft' | 'published' | 'scheduled' | 'archived'
     * Enum: ProductStatus
     */
    public string $status = 'draft';

    /**
     * Storefront visibility.
     * Values: 'public' | 'hidden' | 'catalog_only' | 'search_only'
     * Enum: ProductVisibility
     */
    public string $visibility = 'public';

    /**
     * Scheduled publish date/time.
     * Required when status = 'scheduled'. Must be a future date.
     */
    public $published_at = null;

    // =========================================================================
    // IMAGES
    // =========================================================================

    /** New main product image file upload staging field. */
    public $image = null;

    /** New gallery image file upload staging array. */
    public $images = [];

    /** Path of the currently saved main image, used for the edit page preview. */
    public $existing_image = null;

    /**
     * Currently saved gallery images loaded from the product_images relationship.
     * Each entry: [id, path, url, alt]
     */
    public $existingImages = [];

    /**
     * IDs of gallery images queued for deletion on next save.
     * Populated by BaseProductComponent::removeGalleryImage().
     */
    public $imagesToDelete = [];

    // =========================================================================
    // CATEGORIES
    // =========================================================================

    /** Array of category IDs currently selected for this product. */
    public array $category_ids = [];

    /**
     * The ID of the primary category — used for breadcrumbs and canonical URL.
     * Set via the "Set primary" button in the category sidebar card.
     * Synced to the categories pivot table with is_primary = true.
     */
    public ?int $primaryCategoryId = null;

    // =========================================================================
    // TAGS
    // =========================================================================

    /** Array of tag IDs currently attached to this product. */
    public array $tag_ids = [];

    /**
     * Raw input string from the tag input field.
     * Parsed as comma-separated values in addTags().
     * Cleared after tags are added.
     */
    public string $newTagInput = '';

    // =========================================================================
    // BRAND
    // =========================================================================

    /** ID of the selected brand. Nullable — products may have no brand. */
    public $brand_id = '';

    // =========================================================================
    // TAX
    // =========================================================================

    /** ID of the assigned tax class. Null means use the global default rate. */
    public $tax_class_id = '';

    // =========================================================================
    // INLINE CATEGORY CREATION
    // =========================================================================

    /** Name input for the inline "Add new category" form. */
    public string $newCategoryName = '';

    /** Optional parent category ID for the new category being created. */
    public ?int $newCategoryParentId = null;

    // =========================================================================
    // INLINE BRAND CREATION
    // =========================================================================

    /** Name input for the inline "Add new brand" form. */
    public string $newBrandName = '';

    /** Optional website URL for the new brand being created. */
    public ?string $newBrandWebsite = null;

    // =========================================================================
    // LINKED PRODUCTS
    // =========================================================================

    /**
     * IDs of upsell products — higher-end alternatives shown on the product page.
     * Cannot include the product itself.
     */
    public array $selected_upsells = [];

    /**
     * IDs of cross-sell products — related items suggested in the cart.
     * Cannot include the product itself.
     */
    public array $selected_cross_sells = [];

    /**
     * Accessories synced from BaseProductComponent::accessories before save.
     * Each entry: [id, quantity]
     * Populated in BaseProductComponent::persistProduct() from the component array.
     */
    public array $accessories = [];

    /**
     * Grouped product items synced from BaseProductComponent::groupedProducts before save.
     * Each entry: [id, quantity]
     * Populated in BaseProductComponent::persistProduct() from the component array.
     */
    public array $grouped_products = [];

    // =========================================================================
    // VIRTUAL & DOWNLOADABLE FLAGS
    // =========================================================================

    /**
     * When true, this product has no physical form and requires no shipping.
     * Shipping tab is hidden and dimensions are nulled on save.
     */
    public bool $is_virtual = false;

    /**
     * When true, this product delivers digital files after purchase.
     * Shows the Downloads section in the General tab.
     * At least one download file must be attached when this is true.
     */
    public bool $is_downloadable = false;

    // =========================================================================
    // DOWNLOAD SETTINGS
    // =========================================================================

    /**
     * Maximum number of times a customer can download the files.
     * 0 = unlimited. Applied at product level.
     */
    public int $download_limit = 0;

    /**
     * Number of days the download link remains valid after purchase.
     * 0 = never expires. Applied at product level.
     */
    public int $download_expiry = 0;

    /**
     * Product-level download file rows.
     * Each entry: [id, name, file, file_path, file_name, file_type, file_size, formatted_file_size]
     * Loaded from product_downloads where variant_id IS NULL.
     * Variant-level downloads are managed separately in BaseProductComponent::variants[].
     */
    public array $downloads = [];

    // =========================================================================
    // ADVANCED SETTINGS
    // =========================================================================

    /**
     * Message shown to the customer on their order confirmation after purchase.
     * E.g. "Thank you! Download instructions will be emailed to you."
     */
    public ?string $purchase_note = null;

    /**
     * Display order in product listings.
     * Lower numbers appear first. Default 0.
     */
    public int $sort_order = 0;

    /** Whether customers can leave reviews on this product. Default true. */
    public bool $reviews_enabled = true;

    /**
     * When true, replaces the "Add to Cart" button with "Request Quote" on the storefront.
     * Enables the min_order_quantity and quotation_notes fields.
     */
    public bool $requires_quotation = false;

    /**
     * Minimum quantity the customer must include in their quote request.
     * Required when requires_quotation = true.
     */
    public ?float $min_order_quantity = null;

    /**
     * Internal notes or instructions shown on the quote request form.
     * E.g. "Bulk discounts available for orders over 100 units. Lead time 2–3 weeks."
     */
    public ?string $quotation_notes = null;

    // =========================================================================
    // POLICY & DELIVERY INFORMATION
    // =========================================================================

    /**
     * Warranty terms displayed on the product page.
     * Informational only — not for sale.
     * e.g. "12-month manufacturer warranty covering defects in materials and workmanship."
     */
    public ?string $warranty_information = null;

    /**
     * Per-product return policy.
     * Overrides the global store return policy when set.
     * Leave blank to use the global policy.
     * e.g. "Non-returnable — made to order" or "30-day returns accepted."
     */
    public ?string $return_policy = null;

    /**
     * Supplementary shipping/delivery notes specific to this product.
     * Shown on the product page and delivery sidebar.
     * Not a replacement for shipping zone pricing — purely informational.
     * e.g. "Requires delivery vehicle with tail lift."
     */
    public ?string $shipping_information = null;

    // =========================================================================
    // VALIDATION RULES
    // =========================================================================

    /**
     * Returns all validation rules for the product form.
     * Rules are conditionally applied based on product type and flags
     * using Rule::when() to keep invalid fields from blocking saves.
     */
    public function rules(): array
    {
        $productId = $this->product?->id;

        return [
            // ── Basic Information ──────────────────────────────────────────
            'name' => 'required|string|max:255',
            'model_number' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,'.$productId,
            'short_description' => 'nullable|string|max:500',

            // Strip HTML before checking length — rich text editors output <p></p>
            // for empty content which would otherwise pass a plain 'required' rule
            'description' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (strlen(trim(strip_tags($value))) < 10) {
                        $fail('The product description must contain at least 10 characters of text.');
                    }
                },
            ],

            'type' => ['required', Rule::enum(ProductType::class)],

            // ── Pricing ────────────────────────────────────────────────────
            // Grouped products derive their price from children — price is optional
            'price' => [
                Rule::when($this->type !== 'grouped', ['required', 'numeric', 'min:0']),
                Rule::when($this->type === 'grouped', ['nullable', 'numeric', 'min:0']),
            ],

            // Use !is_null() instead of !empty() so a price of 0 still triggers the lt check
            'sale_price' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when(! is_null($this->price), ['lt:price']),
            ],

            'cost_price' => 'nullable|numeric|min:0',

            // ── Inventory ──────────────────────────────────────────────────
            // SKU is required for all types except grouped (used as kit reference only)
            'sku' => [
                Rule::when(
                    $this->type !== 'grouped',
                    ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
                    ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
                ),
            ],

            // Virtual and grouped products don't manage stock
            'manage_stock' => [
                Rule::when(
                    $this->is_virtual || $this->type === 'grouped',
                    ['nullable'],
                    ['boolean']
                ),
            ],

            'stock_quantity' => [
                Rule::when(
                    $this->type !== 'grouped' && ! $this->is_virtual && $this->manage_stock,
                    ['required', 'integer', 'min:0'],
                    ['nullable', 'integer', 'min:0']
                ),
            ],

            'allow_backorder' => [
                Rule::when(
                    $this->type !== 'grouped' && ! $this->is_virtual && $this->manage_stock,
                    ['required', 'in:no,notify,yes'],
                    ['nullable']
                ),
            ],

            'low_stock_threshold' => 'nullable|integer|min:0',

            'stock_status' => [
                Rule::when(
                    $this->type !== 'grouped' && ! $this->is_virtual,
                    ['required_without:manage_stock', 'in:in_stock,out_of_stock,backorder'],
                    ['nullable']
                ),
            ],

            'sold_individually' => 'boolean',

            // ── Shipping ───────────────────────────────────────────────────
            // These are always nullable — productData() nulls them for virtual/grouped
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',

            // ── SEO ────────────────────────────────────────────────────────
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|string|max:255',

            // ── Status ─────────────────────────────────────────────────────
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'visibility' => ['required', Rule::enum(ProductVisibility::class)],

            // Scheduled products must have a future publish date
            'published_at' => [
                'nullable',
                'date',
                Rule::when(
                    $this->status === 'scheduled',
                    ['required', 'after:now']
                ),
            ],

            // ── Images ─────────────────────────────────────────────────────
            // On create: always required. On edit: required unless an existing image is present.
            'image' => [
                Rule::when(is_null($this->product), ['required'], ['nullable']),
                Rule::when(! is_null($this->product), ['required_without:existing_image']),
                'image',
                'max:2048',
                'mimes:jpg,jpeg,png,gif,webp',
            ],

            // Gallery images — each must be a valid image file
            'images.*' => 'nullable|image|max:2048|mimes:jpg,jpeg,png,gif,webp',

            // ── Relationships ──────────────────────────────────────────────
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',

            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',

            'brand_id' => 'nullable|exists:brands,id',
            'tax_class_id' => 'nullable|exists:tax_classes,id',

            // Self-reference prevention — a product cannot upsell or cross-sell itself
            'selected_upsells' => 'nullable|array',
            'selected_upsells.*' => [
                'exists:products,id',
                Rule::notIn([$this->product?->id]),
            ],

            'selected_cross_sells' => 'nullable|array',
            'selected_cross_sells.*' => [
                'exists:products,id',
                Rule::notIn([$this->product?->id]),
            ],

            'accessories' => 'nullable|array',
            'accessories.*.id' => 'required|exists:products,id',
            'accessories.*.quantity' => 'required|integer|min:1',

            'grouped_products' => 'nullable|array',
            'grouped_products.*.id' => 'required|exists:products,id',
            'grouped_products.*.quantity' => 'required|integer|min:1',

            // ── Virtual & Downloadable ─────────────────────────────────────
            'is_virtual' => 'boolean',
            'is_downloadable' => 'boolean',

            // ── Downloads ──────────────────────────────────────────────────
            'download_limit' => 'nullable|integer|min:0',
            'download_expiry' => 'nullable|integer|min:0',

            // When downloadable, at least one file must be attached
            'downloads' => [
                Rule::when(
                    $this->is_downloadable,
                    ['required', 'array', 'min:1'],
                    ['nullable', 'array']
                ),
            ],

            'downloads.*.name' => 'nullable|string|max:255',
            'downloads.*.file' => [
                'nullable',
                'file',
                'max:102400', // 100MB per file
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,jpg,jpeg,png,gif,mp4,mp3',
            ],

            // ── Advanced ───────────────────────────────────────────────────
            'purchase_note' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'reviews_enabled' => 'boolean',
            'requires_quotation' => 'boolean',

            // min_order_quantity is required only when requires_quotation is enabled
            'min_order_quantity' => [
                Rule::when(
                    $this->requires_quotation,
                    ['required', 'numeric', 'min:1'],
                    ['nullable', 'numeric', 'min:1']
                ),
            ],

            'quotation_notes' => 'nullable|string|max:1000',

            // Policy & Delivery
            'warranty_information' => 'nullable|string|max:2000',
            'return_policy' => 'nullable|string|max:2000',
            'shipping_information' => 'nullable|string|max:2000',
        ];
    }

    // =========================================================================
    // VALIDATION MESSAGES
    // =========================================================================

    /**
     * Custom human-readable messages for validation failures.
     * Only defined where the default Laravel message is unclear or too technical.
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
            'published_at.required' => 'A publish date is required for scheduled products.',
            'published_at.after' => 'The publish date must be a future date.',
            'image.image' => 'The file must be an image.',
            'image.max' => 'Image size must not exceed 2MB.',
            'downloads.*.file.mimes' => 'Only PDF, Office documents, images, zip, and media files are allowed.',
            'downloads.*.file.max' => 'Download file must not exceed 100MB.',
            'downloads.*.name.max' => 'Download file name must not exceed 255 characters.',
            'selected_upsells.*.not_in' => 'A product cannot upsell itself.',
            'selected_cross_sells.*.not_in' => 'A product cannot cross-sell itself.',
        ];
    }

    // =========================================================================
    // VALIDATION ATTRIBUTE NAMES
    // =========================================================================

    /**
     * Maps field names to human-readable labels used in validation error messages.
     * E.g. "The form.sku field is required" becomes "The SKU field is required."
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
            'min_order_quantity' => 'minimum order quantity',
            'quotation_notes' => 'quotation notes',
            'warranty_information' => 'warranty information',
            'return_policy' => 'return policy',
            'shipping_information' => 'shipping information',
            'purchase_note' => 'purchase note',
        ];
    }

    // =========================================================================
    // PRODUCT LOADING
    // =========================================================================

    /**
     * Populates all form fields from an existing Product model for editing.
     * Called by the Edit component's mount() method.
     * Converts any model casts back to the string/array types the form expects.
     */
    public function setProduct(Product $product): void
    {
        $this->product = $product;

        // Basic information
        $this->name = $product->name;
        $this->model_number = $product->model_number;
        $this->slug = $product->slug;
        $this->short_description = $product->short_description;
        $this->description = $product->description;
        $this->type = $product->type->value ?? ProductType::SIMPLE->value;

        // Pricing
        $this->price = $product->price;
        $this->sale_price = $product->sale_price;
        $this->cost_price = $product->cost_price;

        // Inventory
        $this->sku = $product->sku;
        $this->manage_stock = $product->manage_stock;
        $this->stock_quantity = $product->stock_quantity;
        $this->allow_backorder = $product->allow_backorder;
        $this->low_stock_threshold = $product->low_stock_threshold;
        $this->stock_status = $product->stock_status;
        $this->sold_individually = $product->sold_individually;

        // Shipping
        $this->weight = $product->weight;
        $this->length = $product->length;
        $this->width = $product->width;
        $this->height = $product->height;

        // SEO
        // meta_keywords is stored as JSON array in DB but displayed as comma-separated string
        $this->meta_title = $product->meta_title;
        $this->meta_description = $product->meta_description;
        $this->meta_keywords = is_array($product->meta_keywords)
            ? implode(', ', $product->meta_keywords)
            : $product->meta_keywords;
        $this->canonical_url = $product->canonical_url;

        // Status
        $this->status = $product->status->value;
        $this->visibility = $product->visibility->value;
        $this->published_at = $product->published_at;

        // Categories — load IDs and determine which one is primary
        $this->category_ids = $product->categories->pluck('id')->toArray();

        $primaryCategory = $product->categories()->wherePivot('is_primary', true)->first();
        $this->primaryCategoryId = $primaryCategory?->id ?? $product->categories()->first()?->id;

        // Tags
        $this->tag_ids = $product->tags->pluck('id')->toArray();

        // Brand
        $this->brand_id = $product->brand_id;

        // Tax
        $this->tax_class_id = $product->tax_class_id;

        // Linked products
        $this->selected_upsells = $product->upsells->pluck('id')->toArray();
        $this->selected_cross_sells = $product->crossSells->pluck('id')->toArray();
        // Note: accessories and grouped_products are NOT loaded here —
        // they are owned and loaded by BaseProductComponent (loadAccessories / loadGroupedProducts)

        // Images — load existing images from the product_images relationship
        $this->existing_image = $product->image_path;
        $this->existingImages = $product->images()
            ->get()
            ->map(fn ($img) => [
                'id' => $img->id,
                'path' => $img->image_path,
                'url' => Storage::url($img->image_path),
                'alt' => $img->alt_text,
            ])
            ->toArray();

        // Virtual & downloadable
        $this->is_virtual = $product->is_virtual;
        $this->is_downloadable = $product->is_downloadable;

        // Download settings
        $this->download_limit = $product->download_limit ?? 0;
        $this->download_expiry = $product->download_expiry ?? 0;
        // Note: download file rows are loaded by BaseProductComponent::loadProductDownloads()

        // Advanced
        $this->purchase_note = $product->purchase_note;
        $this->sort_order = $product->sort_order;
        $this->reviews_enabled = $product->reviews_enabled;
        $this->requires_quotation = $product->requires_quotation;
        $this->min_order_quantity = $product->min_order_quantity;
        $this->quotation_notes = $product->quotation_notes;

        // Policy & delivery information
        $this->warranty_information = $product->warranty_information;
        $this->return_policy = $product->return_policy;
        $this->shipping_information = $product->shipping_information;
    }

    // =========================================================================
    // TAG MANAGEMENT
    // =========================================================================

    /**
     * Parses newTagInput as comma-separated tag names and attaches each one.
     * Creates new tags via Spatie if they don't exist.
     * Clears newTagInput after processing.
     */
    public function addTags(): void
    {
        $tagNames = array_filter(
            array_map('trim', explode(',', $this->newTagInput)),
            fn ($name) => ! empty($name)
        );

        if (empty($tagNames)) {
            return;
        }

        foreach ($tagNames as $tagName) {
            $this->addOrAttachTag($tagName);
        }

        $this->newTagInput = '';
    }

    /**
     * Removes a tag from the current selection by ID.
     * Does not delete the tag from the database.
     */
    public function removeTag(int $tagId): void
    {
        $this->tag_ids = array_values(
            array_filter($this->tag_ids, fn ($id) => $id != $tagId)
        );
    }

    /**
     * Bulk-adds tags from an array of IDs (e.g. from the "most used tags" modal).
     * Skips any IDs already in the selection.
     */
    public function addSelectedTags(array $tagIds): void
    {
        foreach ($tagIds as $tagId) {
            if (! in_array($tagId, $this->tag_ids)) {
                $this->tag_ids[] = $tagId;
            }
        }
    }

    /**
     * Returns the currently selected tags as a Tag collection.
     * Used by the selectedTags computed property in BaseProductComponent
     * to render the tag badges in the sidebar.
     */
    public function getSelectedTags()
    {
        if (empty($this->tag_ids)) {
            return collect();
        }

        return Tag::whereIn('id', $this->tag_ids)->get();
    }

    // =========================================================================
    // INLINE CATEGORY CREATION
    // =========================================================================

    /**
     * Creates a new category from newCategoryName and adds it to the selection.
     * If a category with the same name already exists (case-insensitive), selects it instead.
     * Generates a unique slug with an incrementing counter on collision.
     * Requires the 'create Category' gate authorization.
     */
    public function createCategory(): ?Category
    {
        Gate::authorize('create', Category::class);

        $this->newCategoryName = trim($this->newCategoryName);

        if (empty($this->newCategoryName)) {
            return null;
        }

        // Re-use existing category if the name already exists
        $existingCategory = Category::whereRaw('LOWER(name) = ?', [strtolower($this->newCategoryName)])->first();

        if ($existingCategory) {
            if (! in_array($existingCategory->id, $this->category_ids)) {
                $this->category_ids[] = $existingCategory->id;
            }
            $this->resetCategoryForm();

            return $existingCategory;
        }

        $category = Category::create([
            'name' => $this->newCategoryName,
            'slug' => $this->generateUniqueSlug($this->newCategoryName, 'categories'),
            'parent_id' => $this->newCategoryParentId,
            'status' => CategoryStatus::ACTIVE,
            'sort_order' => 0,
        ]);

        if (! in_array($category->id, $this->category_ids)) {
            $this->category_ids = array_merge($this->category_ids, [$category->id]);
        }

        $this->resetCategoryForm();

        return $category;
    }

    /** Resets the inline category creation form fields. */
    public function resetCategoryForm(): void
    {
        $this->newCategoryName = '';
        $this->newCategoryParentId = null;
    }

    // =========================================================================
    // SLUG HELPERS
    // =========================================================================

    /**
     * Generates a unique slug for the given name by checking the target table.
     * Appends an incrementing counter suffix on collisions.
     *
     * @param  string  $name  Human-readable name to slugify.
     * @param  string  $table  Database table to check for uniqueness.
     * @param  string  $column  Column on that table that holds the slug (default 'slug').
     */
    private function generateUniqueSlug(string $name, string $table, string $column = 'slug'): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (DB::table($table)->where($column, $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        return $slug;
    }

    // =========================================================================
    // INLINE BRAND CREATION
    // =========================================================================

    /**
     * Creates a new brand from newBrandName and selects it.
     * If a brand with the same name already exists (case-insensitive), selects it instead.
     * Generates a unique slug with an incrementing counter on collision.
     * Requires the 'create Brand' gate authorization.
     */
    public function createBrand(): ?Brand
    {
        Gate::authorize('create', Brand::class);

        $this->newBrandName = trim($this->newBrandName);

        if (empty($this->newBrandName)) {
            return null;
        }

        // Re-use existing brand if the name already exists
        $existingBrand = Brand::whereRaw('LOWER(name) = ?', [strtolower($this->newBrandName)])->first();

        if ($existingBrand) {
            $this->brand_id = $existingBrand->id;
            $this->resetBrandForm();

            return $existingBrand;
        }

        $brand = Brand::create([
            'name' => $this->newBrandName,
            'slug' => $this->generateUniqueSlug($this->newBrandName, 'brands'),
            'website_url' => $this->newBrandWebsite,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->brand_id = $brand->id;
        $this->resetBrandForm();

        return $brand;
    }

    /** Resets the inline brand creation form fields. */
    public function resetBrandForm(): void
    {
        $this->newBrandName = '';
        $this->newBrandWebsite = null;
    }

    // =========================================================================
    // PERSISTENCE
    // =========================================================================

    /**
     * Creates a new Product record and syncs all relationships.
     * Called by the Create component's executeSave() inside a DB transaction.
     *
     * @return Product The newly created product model.
     */
    public function store(): Product
    {
        $product = Product::create($this->productData());
        $this->syncRelationships($product);

        return $product;
    }

    /**
     * Updates the existing product record and re-syncs all relationships.
     * Called by the Edit component's executeSave() inside a DB transaction.
     */
    public function update(): void
    {
        $this->product->update($this->productData());
        $this->syncRelationships($this->product);
    }

    /**
     * Builds the array of scalar product fields for create/update.
     * Applies conditional nulling of irrelevant fields based on type and flags:
     * - Grouped: price = null
     * - Virtual/grouped: stock fields forced off, shipping dimensions = null
     * - Not downloadable: download settings = null
     * - Not requires_quotation: quotation fields = null
     * - meta_keywords: stored as JSON array even though form holds a string
     */
    private function productData(): array
    {
        return [
            'name' => $this->name,
            'model_number' => $this->model_number,
            'slug' => $this->slug ?: Str::slug($this->name),
            'short_description' => $this->short_description,
            'description' => $this->description,
            'type' => $this->type,

            // Grouped products have no base price — derived from children
            'price' => $this->type === 'grouped' ? null : $this->price,
            'sale_price' => $this->sale_price,
            'cost_price' => $this->cost_price,

            'sku' => $this->sku,
            'manage_stock' => $this->hasStock() ? $this->manage_stock : false,
            'stock_quantity' => $this->hasStock() ? $this->stock_quantity : 0,
            'allow_backorder' => $this->allow_backorder,
            'low_stock_threshold' => $this->low_stock_threshold,
            'stock_status' => $this->hasStock() ? $this->stock_status : 'in_stock',
            'sold_individually' => $this->sold_individually,

            // Nulled for virtual and grouped products
            'weight' => $this->isPhysical() ? $this->weight : null,
            'length' => $this->isPhysical() ? $this->length : null,
            'width' => $this->isPhysical() ? $this->width : null,
            'height' => $this->isPhysical() ? $this->height : null,

            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,

            // Convert comma-separated string back to JSON array for storage
            'meta_keywords' => $this->meta_keywords
                ? array_values(array_filter(array_map('trim', explode(',', $this->meta_keywords))))
                : null,

            'canonical_url' => $this->canonical_url,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'published_at' => $this->published_at,

            'brand_id' => $this->brand_id ?: null,
            'tax_class_id' => $this->tax_class_id ?: null,

            'is_virtual' => $this->is_virtual,
            'is_downloadable' => $this->is_downloadable,

            // Nulled when not downloadable to avoid stale values persisting after toggling off
            'download_limit' => $this->is_downloadable ? $this->download_limit : null,
            'download_expiry' => $this->is_downloadable ? $this->download_expiry : null,

            'purchase_note' => $this->purchase_note,
            'sort_order' => $this->sort_order,
            'reviews_enabled' => $this->reviews_enabled,

            'requires_quotation' => $this->requires_quotation,

            // Nulled when quotation is disabled to avoid stale values persisting
            'min_order_quantity' => $this->requires_quotation ? $this->min_order_quantity : null,
            'quotation_notes' => $this->requires_quotation ? $this->quotation_notes : null,

            'warranty_information' => $this->warranty_information,
            'return_policy' => $this->return_policy,
            'shipping_information' => $this->shipping_information,
        ];
    }

    /**
     * Syncs all many-to-many relationships after store/update.
     * Order matters — image upload runs last since it may update the product record again.
     */
    private function syncRelationships(Product $product): void
    {
        // Categories — sync with is_primary flag on the pivot
        $product->categories()->sync(
            collect($this->category_ids)
                ->mapWithKeys(fn ($id) => [
                    $id => ['is_primary' => $id == $this->primaryCategoryId],
                ])
                ->toArray()
        );

        // Tags — detach existing badge-type tags and re-attach the current selection
        $product->detachTags($product->tagsWithType('badge'));
        if (! empty($this->tag_ids)) {
            $tags = Tag::whereIn('id', $this->tag_ids)->get();
            $product->attachTags($tags, 'badge');
        }

        // Upsells — sync with relationship type and sort order on the pivot
        $product->upsells()->sync(
            collect($this->selected_upsells)
                ->mapWithKeys(fn ($id, $index) => [
                    $id => [
                        'type' => ProductRelationshipType::UP_SELLS->value,
                        'sort_order' => $index,
                        'quantity' => 1,
                    ],
                ])
                ->toArray()
        );

        // Cross-sells — sync with relationship type and sort order
        $product->crossSells()->sync(
            collect($this->selected_cross_sells)
                ->mapWithKeys(fn ($id, $index) => [
                    $id => [
                        'type' => ProductRelationshipType::CROSS_SELL->value,
                        'sort_order' => $index,
                        'quantity' => 1,
                    ],
                ])
                ->toArray()
        );

        // Accessories — use component state if populated
        // sync([]) wiping existing accessories when Livewire drops state from snapshot
        $product->accessories()->sync(
            collect($this->accessories)
                ->mapWithKeys(fn ($item, $index) => [
                    $item['id'] => [
                        'type' => ProductRelationshipType::ACCESSORY->value,
                        'quantity' => $item['quantity'] ?? 1,
                        'sort_order' => $index,
                    ],
                ])
                ->toArray()
        );

        // Grouped products — same guard as accessories above
        $groupedToSync = ! empty($this->grouped_products)
            ? collect($this->grouped_products)
            : $product->groupedProducts()->get()->map(fn ($p) => [
                'id' => $p->id,
                'quantity' => $p->pivot->quantity ?? 1,
            ]);

        $product->groupedProducts()->sync(
            collect($groupedToSync)
                ->mapWithKeys(fn ($item, $index) => [
                    $item['id'] => [
                        'type' => ProductRelationshipType::GROUPED->value,
                        'quantity' => $item['quantity'] ?? 1,
                        'sort_order' => $index,
                    ],
                ])
                ->toArray()
        );

        // Images — always last since handleImageUpload() calls product->update()
        $this->handleImageUpload($product);
    }

    /**
     * Handles main image and gallery image uploads and deletions.
     *
     * Upload order is intentionally safe:
     * 1. Store the new file first
     * 2. Update the product record with the new path
     * 3. Delete the old file only after a successful store
     *
     * This prevents the product being left with no image if storage fails.
     * Gallery deletions also run after all additions are complete.
     */
    private function handleImageUpload(Product $product): void
    {
        // Main product image
        if ($this->image) {
            $newPath = $this->image->store('products', 'public');

            if ($newPath) {
                $oldPath = $product->image_path;
                $product->update(['image_path' => $newPath]);

                // Only delete the old file after the new one is saved
                if ($oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        // Gallery images — store each new image and create a product_images record
        if (! empty($this->images)) {
            foreach ($this->images as $image) {
                $path = $image->store('products/gallery', 'public');
                if ($path) {
                    $product->images()->create(['image_path' => $path]);
                }
            }
        }

        // Gallery deletions — run after all additions to avoid partial failure
        if (! empty($this->imagesToDelete)) {
            foreach ($this->imagesToDelete as $imageId) {
                $img = $product->images()->find($imageId);
                if ($img) {
                    Storage::disk('public')->delete($img->image_path);
                    $img->delete();
                }
            }

            $this->imagesToDelete = [];
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Finds or creates a tag by name using Spatie and adds its ID to tag_ids.
     * Normalises the name and prevents duplicate IDs in the selection.
     */
    private function addOrAttachTag(string $tagName): void
    {
        $normalizedName = trim($tagName);

        if (empty($normalizedName)) {
            return;
        }

        $tag = Tag::findOrCreate($normalizedName, 'badge');

        if (! in_array($tag->id, $this->tag_ids)) {
            $this->tag_ids[] = $tag->id;
        }
    }

    /**
     * Returns true when shipping dimensions should be saved.
     * Grouped and virtual products are not physical and have no shipping data.
     */
    private function isPhysical(): bool
    {
        return $this->type !== 'grouped' && ! $this->is_virtual;
    }

    /**
     * Returns true when stock management fields should be saved.
     * Grouped and virtual products do not manage stock.
     */
    private function hasStock(): bool
    {
        return $this->type !== 'grouped' && ! $this->is_virtual;
    }

    // =========================================================================
    // UTILITIES
    // =========================================================================

    /**
     * Returns the ID of the product being edited, or null when creating.
     * Used throughout BaseProductComponent to distinguish create from edit context.
     */
    public function getProductId(): ?int
    {
        return $this->product?->id;
    }
}
