<?php

namespace App\Models;

use App\Enums\ProductRelationshipType;
use App\Enums\ProductStatus;
use App\Enums\ProductVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attribute as ProductAttribute;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Number;
use Spatie\Tags\HasTags;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasTags;

    protected $fillable = [
        'name',
        'model_number',
        'slug',
        'short_description',
        'type',
        'price',
        'sale_price',
        'cost_price',
        'sku',
        'manage_stock',
        'stock_quantity',
        'allow_backorder',
        'max_backorder_quantity',
        'expected_restock_date',
        'backorder_message',
        'low_stock_threshold',
        'stock_status',
        'sold_individually',
        'weight',
        'height',
        'width',
        'length',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'status',
        'published_at',
        'visibility',
        'brand_id',
        'image_path',
        'technical_specification',
        'estimated_delivery_time',
        'shipping_information',
        'warranty_information',
        'return_policy',
        'views_count',
        'sales_count',
        'average_rating',
        'reviews_count',
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
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
            'width' => 'decimal:2',
            'length' => 'decimal:2',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'average_rating' => 'decimal:2',
            'expected_restock_date' => 'date',
            'requires_quotation' => 'boolean',
            'min_order_quantity' => 'decimal:2',
            'status' => ProductStatus::class,
            'visibility' => ProductVisibility::class
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
     * Get upsell products for this product
     */
    public function upsells(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_relationships',
            'product_id',
            'related_product_id'
        )
            ->wherePivot('type', ProductRelationshipType::UP_SELLS)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get cross-sell products for this product
     */
    public function crossSells(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_relationships',
            'product_id',
            'related_product_id'
        )
            ->wherePivot('type', ProductRelationshipType::CROSS_SELL)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get grouped products 
     */
    public function groupedProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_relationships',
            'product_id',
            'related_product_id'
        )
            ->wherePivot('type', ProductRelationshipType::GROUPED)
            ->withPivot('sort_order', 'quantity')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get all reviews for the product
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function reservations(): MorphMany
    {
        return $this->morphMany(InventoryReservation::class, 'reservable');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_values')
            ->withTimestamps();
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
        $query->where('status', 'published')
            ->where(function ($q) {
                $q->where('price', '>', 0)
                    ->orWhere('sale_price', '>', 0);
            });
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
