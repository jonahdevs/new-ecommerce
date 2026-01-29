<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attribute as ProductAttribute;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Number;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'model_number',
        'description',
        'short_description',
        'slug',
        'sku',
        'type',
        'image_path',
        'technical_specification',
        'is_active',
        'is_featured',
        'weight',
        'height',
        'width',
        'length',
        'price',
        'sale_price',
        'cost_price',
        'tax_rate',
        'manage_stock',
        'stock_quantity',
        'low_stock_threshold',
        'stock_status',
        'allow_backorders',
        'max_backorder_quantity',
        'expected_restock_date',
        'backorder_message',
        'estimated_delivery_time',
        'shipping_information',
        'warranty_information',
        'return_policy',
        'status',
        'views_count',
        'sales_count',
        'average_rating',
        'reviews_count',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'brand_id',
        'requires_quotation',
        'min_order_quantity',
        'quotation_notes',
    ];

    protected function casts(): array
    {
        return [
            'technical_specification' => 'array',
            'meta_keywords' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'manage_stock' => 'boolean',
            'allow_backorders' => 'boolean',
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
            'width' => 'decimal:2',
            'length' => 'decimal:2',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'average_rating' => 'decimal:2',
            'expected_restock_date' => 'date',
            'requires_quotation' => 'boolean',
            'min_order_quantity' => 'decimal:2',
        ];
    }

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    /**
     * Get the brand that owns the product
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get all variants for the product
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    /**
     * Get all tags for the product
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
            ->withTimestamps();
    }

    /**
     * Get all attributes for the product
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttribute::class, 'product_attributes')
            ->withPivot(['is_variation_attribute', 'is_visible', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }


    /**
     * Get the categories for the product
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withPivot(['is_primary', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get all images for the product
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Get accessories for the product
     */
    public function accessories(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_accessories', 'product_id', 'accessory_id')
            ->withTimestamps();
    }

    /**
     * Get all reviews for the product
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // ===============================================
    // SCOPES
    // ===============================================

    /**
     * Scope a query to only include active products.
     */
    #[Scope]
    protected function active(Builder $query)
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function published(Builder $query)
    {
        $query->where('status', 'published');
    }

    #[Scope()]
    protected function newArrivals(Builder $query): void
    {
        $query->where('created_at', '>=', now()->subDays(30));
    }

    // ===============================================
    // ACCESSORS
    // ===============================================

    /**
     * Get the product's image URL
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->image_path ? asset('storage/' . $this->image_path) : null,
        );
    }

    protected function finalPrice(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->sale_price && $this->sale_price < $this->price ? $this->sale_price : $this->price,
        );
    }

    protected function formattedFinalPrice(): Attribute
    {
        return Attribute::make(
            get: fn() => format_currency($this->final_price)
        );
    }

    protected function formattedSalePrice(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->sale_price ? format_currency($this->sale_price ?? 0) : null
        );
    }

    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn() => format_currency($this->price)
        );
    }


    // ===============================================
    // METHODS
    // ===============================================

    public function hasDiscount(): bool
    {
        return $this->sale_price && $this->sale_price < $this->price;
    }

    public function discountPercentage()
    {
        if ($this->hasDiscount()) {
            return Number::percentage(round((($this->price - $this->sale_price) / $this->price) * 100, 2));
        }
        return null;
    }

    public function primaryCategory()
    {
        return $this->categories()
            ->wherePivot('is_primary', true)
            ->first()
            ?? $this->categories()->first();
    }
}
