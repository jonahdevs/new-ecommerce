<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'sku',
        'name',
        'quantity',
        'unit_price_cents',
        'unit_tax_cents',
        'discount_cents',
        'total_cents',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    // ===============================================
    // Relationships
    // ===============================================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }


    // ===============================================
    // Accessors
    // ===============================================
    protected function unitPrice(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->unit_price_cents / 100,
        );
    }
}
