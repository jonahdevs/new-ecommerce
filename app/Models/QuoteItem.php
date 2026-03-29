<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'original_price_cents',
        'quoted_price_cents',
        'discount_cents',
        'total_cents',
        'product_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'product_snapshot' => 'array',
        ];
    }

    // =====================================================
    // Relationships
    // =====================================================

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // =====================================================
    // Accessors
    // =====================================================

    protected function originalPrice(): Attribute
    {
        return Attribute::make(get: fn() => $this->original_price_cents / 100);
    }

    protected function quotedPrice(): Attribute
    {
        return Attribute::make(get: fn() => $this->quoted_price_cents ? $this->quoted_price_cents / 100 : null);
    }

    protected function effectivePrice(): Attribute
    {
        return Attribute::make(get: fn() => ($this->quoted_price_cents ?? $this->original_price_cents) / 100);
    }

    protected function total(): Attribute
    {
        return Attribute::make(get: fn() => $this->total_cents / 100);
    }

    // =====================================================
    // Helpers
    // =====================================================

    public function hasCustomPrice(): bool
    {
        return $this->quoted_price_cents !== null
            && $this->quoted_price_cents !== $this->original_price_cents;
    }

    public function productName(): string
    {
        return $this->product_snapshot['name'] ?? $this->product?->name ?? 'Unknown Product';
    }

    public function productSku(): string
    {
        return $this->product_snapshot['sku'] ?? $this->product?->sku ?? '';
    }

    public function productImageUrl(): ?string
    {
        return $this->product_snapshot['image_url'] ?? $this->product?->image_url;
    }
}
