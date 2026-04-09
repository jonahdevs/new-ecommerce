<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'attribute_hash',
        'name',
        'attributes',
        'price',
        'sale_price',
        'cost_price',
        'sku',
        'manage_stock',
        'stock_quantity',
        'allow_backorders',
        'max_backorder_quantity',
        'expected_restock_date',
        'backorder_message',
        'low_stock_threshold',
        'stock_status',
        'weight',
        'height',
        'width',
        'length',
        'image_path',
        'is_default',
        'is_active',
        'sort_order',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
            'width' => 'decimal:2',
            'length' => 'decimal:2',
            'manage_stock' => 'boolean',
            'allow_backorders' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'expected_restock_date' => 'date',
        ];
    }

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    /**
     * Get the product that owns the variant
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get all images for the variant
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Get all attribute values for this variant
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_variant_attribute_values',
            'variant_id',
            'attribute_value_id'
        )
            ->withTimestamps();
    }

    protected function finalPrice(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->sale_price ?? $this->price,
        );
    }

    public function reservations(): MorphMany
    {
        return $this->morphMany(InventoryReservation::class, 'reservable');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(ProductDownload::class, 'variant_id')->orderBy('sort_order');
    }
}
