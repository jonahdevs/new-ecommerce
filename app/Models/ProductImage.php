<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'image_path',
        'webp_path',
        'thumbnail_path',
        'alt_text',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    /**
     * Get the product that owns the image
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant that owns the image (if any)
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    // ===============================================
    // ACCESSORS
    // ===============================================

    /**
     * Get the product's image URL
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path ? asset('storage/'.$this->image_path) : null,
        );
    }

    protected function webpUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->webp_path ? asset('storage/'.$this->webp_path) : null,
        );
    }
}
