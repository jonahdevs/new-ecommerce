<?php

namespace App\Models;

use App\Concerns\LogsModelChanges;
use App\Enums\CategorySection;
use App\Enums\CategoryStatus;
use App\Observers\CategoryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([CategoryObserver::class])]
class Category extends Model
{
    use HasFactory;
    use LogsModelChanges;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
        'image_path',
        'image_icon',
        'icon_svg',
        'status',
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
            'status' => CategoryStatus::class,
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

    // Category placements
    public function placements(): HasMany
    {
        return $this->hasMany(CategoryPlacement::class);
    }

    // ==================================================
    // SCOPES
    // ==================================================

    #[Scope]
    protected function active(Builder $query)
    {
        $query->where('status', CategoryStatus::ACTIVE);
    }

    #[Scope]
    protected function inSection(Builder $query, CategorySection $section): Builder
    {
        return $query
            ->join('category_placements', 'categories.id', '=', 'category_placements.category_id')
            ->where('category_placements.section', $section)
            ->where('status', 'active')
            ->orderBy('category_placements.sort_order')
            ->select('categories.*', 'category_placements.sort_order as placement_order');
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

    // ==================================================
    // CHANGELOG TRACKING
    // ==================================================

    /**
     * Get the attributes that should be logged when changed.
     *
     * @return array<int, string>
     */
    protected function getLoggedAttributes(): array
    {
        return [
            'name',
            'parent_id',
            'status',
            'sort_order',
        ];
    }
}
