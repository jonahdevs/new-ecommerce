<?php

namespace App\Models;

use Database\Factories\QuoteItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quote_id', 'product_id', 'product_snapshot', 'unit_price_cents', 'quantity', 'line_total_cents'])]
class QuoteItem extends Model
{
    /** @use HasFactory<QuoteItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'product_snapshot' => 'array',
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'line_total_cents' => 'integer',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
