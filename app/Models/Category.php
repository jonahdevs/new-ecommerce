<?php

namespace App\Models;

use App\Enums\CategoryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['name', 'slug', 'parent_id', 'description', 'image', 'thumbnail', 'icon', 'icon_svg', 'status', 'sort_order', 'meta_title', 'meta_description', 'canonical_url'])]
class Category extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    /**
     * Distinct image roles, each a single-file collection:
     * - banner: wide hero stripe at the top of the category page
     * - square: square tile used in grids (home "Shop by category", menus)
     * - icon:   small glyph in the category navigation
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')->singleFile();
        $this->addMediaCollection('square')->singleFile();
        $this->addMediaCollection('icon')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('web')
            ->performOnCollections('banner')
            ->fit(Fit::Crop, 1920, 600)
            ->nonQueued();

        $this->addMediaConversion('card')
            ->performOnCollections('square')
            ->fit(Fit::Crop, 600, 600)
            ->nonQueued();

        // Small square crop of the main image, shown in the admin category list.
        $this->addMediaConversion('thumb')
            ->performOnCollections('square')
            ->fit(Fit::Crop, 120, 120)
            ->nonQueued();

        // Tiny low-quality image placeholder (LQIP) inlined as base64 for blur-up loading.
        $this->addMediaConversion('lqip')
            ->performOnCollections('banner', 'square')
            ->width(64)
            ->nonQueued();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'status', 'parent_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('category');
    }

    protected function casts(): array
    {
        return [
            'status' => CategoryStatus::class,
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(CategoryPlacement::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('sort_order');
    }

    // ==================================================
    // ACCESSORS
    // ==================================================

    /**
     * Wide hero banner shown at the top of the category page (null when unset).
     * Prefers the Media Library 'web' conversion, falling back to the legacy
     * image column so seeded/un-migrated rows still render.
     */
    protected function bannerUrl(): Attribute
    {
        return Attribute::get(fn () => $this->getFirstMediaUrl('banner', 'web')
            ?: ProductImage::resolveUrl($this->image));
    }

    /** Square tile for grids/menus; falls back to the banner until a square is uploaded. */
    protected function squareUrl(): Attribute
    {
        return Attribute::get(fn () => $this->getFirstMediaUrl('square', 'card')
            ?: (ProductImage::resolveUrl($this->thumbnail) ?? $this->banner_url));
    }

    /** Small square crop of the main image for the admin list; falls back to the full square/banner. */
    protected function imageThumbUrl(): Attribute
    {
        return Attribute::get(fn () => $this->getFirstMediaUrl('square', 'thumb')
            ?: $this->square_url);
    }

    /** Inline base64 LQIP for the banner, shown blurred until the full image loads. */
    protected function bannerPlaceholder(): Attribute
    {
        return Attribute::get(fn () => $this->mediaPlaceholder('banner'));
    }

    /** Inline base64 LQIP for the main square image; falls back to the banner's. */
    protected function imagePlaceholder(): Attribute
    {
        return Attribute::get(fn () => $this->mediaPlaceholder('square') ?? $this->mediaPlaceholder('banner'));
    }

    /** Small navigation icon image (SVG markup in icon_svg is handled separately). */
    protected function iconImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->getFirstMediaUrl('icon')
            ?: ProductImage::resolveUrl($this->icon));
    }

    /**
     * Build (and cache) a base64 data-URI from a collection's 'lqip' conversion.
     * Returns null when no media or the conversion has not been generated.
     */
    private function mediaPlaceholder(string $collection): ?string
    {
        $media = $this->getFirstMedia($collection);

        if (! $media || ! $media->hasGeneratedConversion('lqip')) {
            return null;
        }

        return cache()->rememberForever(
            "category-lqip-{$media->id}-{$media->updated_at?->timestamp}",
            function () use ($media) {
                $path = $media->getPath('lqip');

                if (! is_file($path)) {
                    return null;
                }

                return 'data:'.($media->mime_type ?: 'image/jpeg').';base64,'.base64_encode(file_get_contents($path));
            }
        );
    }

    // Backwards-compatible aliases for the previous column-based accessors.

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->banner_url);
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn () => $this->square_url);
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::get(fn () => $this->icon_image_url);
    }
}
