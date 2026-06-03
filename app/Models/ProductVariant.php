<?php

namespace App\Models;

use App\Enums\StockStatus;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['product_id', 'sku', 'barcode', 'price', 'compare_at_price', 'cost_price', 'stock_status', 'stock_quantity', 'allow_backorder', 'weight', 'length', 'width', 'height', 'description', 'image', 'is_active', 'sort_order', 'sap_last_synced_at'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['sku', 'price', 'compare_at_price', 'stock_quantity', 'stock_status', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('product_variant');
    }

    protected function casts(): array
    {
        return [
            'stock_status' => StockStatus::class,
            'allow_backorder' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'sap_last_synced_at' => 'datetime',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_attribute_values');
    }
}
