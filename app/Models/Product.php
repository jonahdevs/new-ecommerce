<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'sku', 'type', 'short_description', 'description', 'price', 'sale_price', 'cost_price', 'is_taxable', 'tax_class_id', 'requires_shipping', 'weight', 'length', 'width', 'height', 'stock_status', 'stock_quantity', 'manage_stock', 'allow_backorder', 'low_stock_threshold', 'is_active', 'is_featured', 'visibility', 'meta_title', 'meta_description', 'sort_order'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'stock_status' => StockStatus::class,
            'visibility' => ProductVisibility::class,
            'is_taxable' => 'boolean',
            'requires_shipping' => 'boolean',
            'manage_stock' => 'boolean',
            'allow_backorder' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function downloadableFiles(): HasMany
    {
        return $this->hasMany(DownloadableFile::class)->orderBy('sort_order');
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_product_id')->orderBy('sort_order');
    }

    public function groupedItems(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'grouped_product_items', 'group_product_id', 'child_product_id')
            ->withPivot('sort_order');
    }
}
