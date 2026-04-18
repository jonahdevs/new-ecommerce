<?php

namespace App\Models;

use App\Concerns\LogsModelChanges;
use App\Observers\BrandObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([BrandObserver::class])]
class Brand extends Model
{
    use HasFactory;
    use LogsModelChanges;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'logo_path',
        'website_url',
        'is_active',
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
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================
    /**
     * Get all products for the brand
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('name');
    }

    /**
     * Get active products only
     */
    public function activeProducts(): HasMany
    {
        return $this->hasMany(Product::class)
            ->where('is_active', true)
            ->where('status', 'published')
            ->orderBy('name');
    }

    // ==================================================
    // SCOPES
    // ==================================================

    #[Scope()]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope to order brands
     */
    #[Scope()]
    protected function ordered(Builder $query)
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    // ===============================================
    // ACCESSORS
    // ===============================================

    /**
     * Get the product's image URL
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->logo_path ? asset('storage/' . $this->logo_path) : null,
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
            'is_active',
        ];
    }
}
