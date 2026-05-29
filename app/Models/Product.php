<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Observers\ProductObserver;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'sku', 'brand_id', 'primary_category_id', 'model_number', 'type', 'short_description', 'description', 'technical_specification', 'price', 'sale_price', 'cost_price', 'is_taxable', 'tax_class_id', 'requires_shipping', 'weight', 'length', 'width', 'height', 'stock_status', 'stock_quantity', 'allow_backorder', 'low_stock_threshold', 'requires_quotation', 'quotation_notes', 'min_order_quantity', 'visibility', 'meta_title', 'meta_description', 'canonical_url', 'sort_order'])]
#[ObservedBy(ProductObserver::class)]
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
            'allow_backorder' => 'boolean',
            'requires_quotation' => 'boolean',
            'min_order_quantity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // ==================================================
    // ACCESSORS
    // ==================================================

    protected function coverUrl(): Attribute
    {
        return Attribute::get(function () {
            $cover = $this->images->firstWhere('is_cover', true) ?? $this->images->first();

            return $cover?->url;
        });
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('sort_order');
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    public function accessories(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_accessories', 'product_id', 'accessory_product_id')
            ->withPivot('sort_order');
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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->approved()->latest();
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
