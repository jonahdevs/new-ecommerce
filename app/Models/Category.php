<?php

namespace App\Models;

use App\Enums\CategoryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'parent_id', 'description', 'image', 'thumbnail', 'icon', 'icon_svg', 'status', 'sort_order', 'meta_title', 'meta_description', 'canonical_url'])]
class Category extends Model
{
    protected function casts(): array
    {
        return [
            'status' => CategoryStatus::class,
        ];
    }

    // ==================================================
    // ACCESSORS
    // ==================================================

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => ProductImage::resolveUrl($this->image));
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn () => ProductImage::resolveUrl($this->thumbnail));
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::get(fn () => ProductImage::resolveUrl($this->icon));
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
}
