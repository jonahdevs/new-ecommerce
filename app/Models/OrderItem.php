<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'product_id', 'product_variant_id', 'product_snapshot', 'unit_price_cents', 'quantity', 'line_total_cents', 'tax_rate', 'tax_cents'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'product_snapshot' => 'array',
            'tax_rate' => 'decimal:2',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // ==================================================
    // ACCESSORS
    // ==================================================

    protected function productName(): Attribute
    {
        return Attribute::get(fn () => $this->product_snapshot['name'] ?? null);
    }

    protected function productSku(): Attribute
    {
        return Attribute::get(fn () => $this->product_snapshot['sku'] ?? null);
    }

    protected function productModelNumber(): Attribute
    {
        return Attribute::get(fn () => $this->product_snapshot['model_number'] ?? null);
    }
}
