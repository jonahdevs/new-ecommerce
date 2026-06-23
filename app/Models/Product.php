<?php

namespace App\Models;

use App\Enums\ProductLinkType;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Observers\ProductObserver;
use App\Settings\InventorySettings;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Tags\HasTags;

#[Fillable(['name', 'slug', 'sku', 'brand_id', 'primary_category_id', 'model_number', 'type', 'status', 'published_at', 'short_description', 'description', 'technical_specification', 'price', 'sale_price', 'cost_price', 'is_taxable', 'tax_class_id', 'requires_shipping', 'is_virtual', 'is_downloadable', 'weight', 'length', 'width', 'height', 'weight_unit', 'dimension_unit', 'stock_status', 'stock_quantity', 'allow_backorder', 'low_stock_threshold', 'requires_quotation', 'quotation_notes', 'min_order_quantity', 'visibility', 'meta_title', 'meta_description', 'canonical_url', 'sort_order', 'default_variant_id', 'sap_last_synced_at'])]
#[ObservedBy(ProductObserver::class)]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasTags, InteractsWithMedia, LogsActivity, SoftDeletes;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('images')
            ->fit(Fit::Crop, 120, 120);

        $this->addMediaConversion('card')
            ->performOnCollections('images')
            ->fit(Fit::Crop, 600, 600);

        $this->addMediaConversion('card-webp')
            ->performOnCollections('images')
            ->fit(Fit::Crop, 600, 600)
            ->format('webp')
            ->quality(85);

        $this->addMediaConversion('zoom')
            ->performOnCollections('images')
            ->fit(Fit::Max, 1200, 1200);

        $this->addMediaConversion('zoom-webp')
            ->performOnCollections('images')
            ->fit(Fit::Max, 1200, 1200)
            ->format('webp')
            ->quality(85);

        $this->addMediaConversion('lqip')
            ->performOnCollections('images')
            ->width(64)
            ->quality(20)
            ->format('jpg');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'sku', 'status', 'visibility', 'price', 'sale_price', 'stock_quantity', 'stock_status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('product');
    }

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'status' => ProductStatus::class,
            'stock_status' => StockStatus::class,
            'visibility' => ProductVisibility::class,
            'published_at' => 'datetime',
            'is_taxable' => 'boolean',
            'requires_shipping' => 'boolean',
            'is_virtual' => 'boolean',
            'is_downloadable' => 'boolean',
            'allow_backorder' => 'boolean',
            'requires_quotation' => 'boolean',
            'min_order_quantity' => 'integer',
            'sort_order' => 'integer',
            'sap_last_synced_at' => 'datetime',
        ];
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

    /**
     * All curated link rows owned by this product (upsells, cross-sells,
     * accessories, spare parts), ordered for editing.
     */
    public function links(): HasMany
    {
        return $this->hasMany(ProductLink::class)->orderBy('sort_order');
    }

    public function upsells(): BelongsToMany
    {
        return $this->linkedProductsOfType(ProductLinkType::UPSELL);
    }

    public function crossSells(): BelongsToMany
    {
        return $this->linkedProductsOfType(ProductLinkType::CROSS_SELL);
    }

    public function accessories(): BelongsToMany
    {
        return $this->linkedProductsOfType(ProductLinkType::ACCESSORY);
    }

    public function spareParts(): BelongsToMany
    {
        return $this->linkedProductsOfType(ProductLinkType::SPARE_PART);
    }

    private function linkedProductsOfType(ProductLinkType $type): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_links', 'product_id', 'linked_product_id')
            ->wherePivot('type', $type->value)
            ->withPivot('type', 'is_required', 'default_quantity', 'sort_order')
            ->orderByPivot('sort_order');
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

    public function views(): HasMany
    {
        return $this->hasMany(ProductView::class);
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

    // ==================================================
    // SCOPES
    // ==================================================

    /**
     * Products that are live right now: explicitly published, or scheduled
     * with a publish time that has already passed.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->where('status', ProductStatus::PUBLISHED)
                ->orWhere(fn (Builder $scheduled) => $scheduled
                    ->where('status', ProductStatus::SCHEDULED)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now()));
        });
    }

    /** Products that appear in catalog/category listings (VISIBLE or CATALOG). */
    #[Scope]
    protected function visibleInCatalog(Builder $query): void
    {
        $query->whereIn('visibility', [ProductVisibility::VISIBLE, ProductVisibility::CATALOG]);
    }

    /** Products that appear in search results (VISIBLE or SEARCH). */
    #[Scope]
    protected function visibleInSearch(Builder $query): void
    {
        $query->whereIn('visibility', [ProductVisibility::VISIBLE, ProductVisibility::SEARCH]);
    }

    /**
     * Apply the store-wide out-of-stock display rule from {@see InventorySettings}.
     * When set to "hide", out-of-stock products are excluded from storefront
     * listings; in-stock and backorderable products are unaffected.
     */
    #[Scope]
    protected function honorStockVisibility(Builder $query): void
    {
        if (app(InventorySettings::class)->out_of_stock_behavior === 'hide') {
            $query->where('stock_status', '!=', StockStatus::OUT_OF_STOCK);
        }
    }

    // ==================================================
    // ACCESSORS
    // ==================================================

    /**
     * Cover image URL — prefers the 'card' conversion when generated, falls back
     * to the original so images show immediately after import before conversions run.
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(function () {
            $cover = $this->getFirstMedia('images', ['is_cover' => true])
                ?? $this->getFirstMedia('images');

            if (! $cover) {
                return null;
            }

            return $cover->hasGeneratedConversion('card')
                ? $cover->getUrl('card')
                : $cover->getUrl();
        });
    }

    /** WebP version of the cover image; null until the conversion has been generated. */
    protected function coverWebpUrl(): Attribute
    {
        return Attribute::get(function () {
            $cover = $this->getFirstMedia('images', ['is_cover' => true])
                ?? $this->getFirstMedia('images');

            return ($cover && $cover->hasGeneratedConversion('card-webp'))
                ? $cover->getUrl('card-webp')
                : null;
        });
    }

    /** Small 120×120 thumbnail of the cover image; used in admin lists and line-item previews. */
    protected function thumbUrl(): Attribute
    {
        return Attribute::get(function () {
            $cover = $this->getFirstMedia('images', ['is_cover' => true])
                ?? $this->getFirstMedia('images');

            if (! $cover) {
                return null;
            }

            return $cover->hasGeneratedConversion('thumb')
                ? $cover->getUrl('thumb')
                : $cover->getUrl();
        });
    }

    /** Inline base64 LQIP for the cover image; null when no lqip conversion exists. */
    protected function coverPlaceholder(): Attribute
    {
        return Attribute::get(fn () => $this->mediaPlaceholder());
    }

    private function mediaPlaceholder(): ?string
    {
        $cover = $this->getFirstMedia('images', ['is_cover' => true])
            ?? $this->getFirstMedia('images');

        if (! $cover || ! $cover->hasGeneratedConversion('lqip')) {
            return null;
        }

        return cache()->rememberForever(
            "product-lqip-{$cover->id}-{$cover->updated_at?->timestamp}",
            function () use ($cover) {
                $path = $cover->getPath('lqip');

                if (! is_file($path)) {
                    return null;
                }

                return 'data:image/jpeg;base64,'.base64_encode(file_get_contents($path));
            }
        );
    }

    /**
     * All product images as a Media collection. Used by templates that previously
     * iterated over the ProductImage HasMany relationship ($product->images).
     */
    public function getImagesAttribute(): MediaCollection
    {
        return $this->getMedia('images');
    }

    // ==================================================
    // HELPERS
    // ==================================================

    /** Whether this product is currently live to the public. */
    public function isPublished(): bool
    {
        if ($this->status === ProductStatus::PUBLISHED) {
            return true;
        }

        return $this->status === ProductStatus::SCHEDULED
            && $this->published_at !== null
            && $this->published_at->isPast();
    }
}
