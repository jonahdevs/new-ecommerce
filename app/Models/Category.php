<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
        'image_path',
        'image_icon',
        'icon_svg',
        'is_active',
        'is_featured',
        'show_in_navbar',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
    ];

    protected function casts(): array
    {
        return [
            'meta_keywords' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'show_in_navbar' => 'boolean',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    /**
     * Get the parent category
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all products in this category
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['is_primary', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get active products only
     */
    public function activeProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->where('products.status', 'published')
            ->withPivot('is_primary', 'sort_order')
            ->withTimestamps()
            ->orderBy('sort_order');
    }


    // ==================================================
    // SCOPES
    // ==================================================

    #[Scope]
    protected function active(Builder $query)
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function navbar(Builder $query)
    {
        $query->where('show_in_navbar', true);
    }

    #[Scope]
    protected function featured(Builder $query)
    {
        $query->where('is_featured', true);
    }

    #[Scope()]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order');
    }

    // ==================================================
    // ACCESSORS & MUTATORS
    // ==================================================

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->image_path
                ? asset('storage/' . $this->image_path)
                : null
        );
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->image_icon
                ? asset('storage/' . $this->image_icon)
                : null
        );
    }
}
