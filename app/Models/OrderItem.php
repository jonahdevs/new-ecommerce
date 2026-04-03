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
        'quantity',
        'unit_price_cents',
        'unit_tax_cents',
        'discount_cents',
        'total_cents',
        'product_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'product_snapshot' => 'array',
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
            get: fn () => $this->unit_price_cents / 100,
        );
    }

    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_cents / 100,
        );
    }

    protected function unitTax(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->unit_tax_cents / 100,
        );
    }

    protected function discount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->discount_cents / 100,
        );
    }

    protected function productImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => isset($this->product_snapshot['image_path']) && $this->product_snapshot['image_path']
            ? asset('storage/'.$this->product_snapshot['image_path'])
            : null,
        );
    }

    // Helper methods for accessing snapshot data
    public function getProductName(): string
    {
        return $this->product_snapshot['name'] ?? $this->product?->name ?? 'Unknown Product';
    }

    public function getProductSku(): string
    {
        return $this->product_snapshot['sku'] ?? $this->product?->sku ?? '';
    }

    public function getProductImagePath(): ?string
    {
        return $this->product_snapshot['image_path'] ?? $this->product?->image_path ?? null;
    }
}
