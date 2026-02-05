<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class County extends Model
{
    protected $fillable = [
        'name',
        'code',
        'shipping_zone_id',
        'sort_order'
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    
    // ===============================================
    // SCOPES
    // ===============================================

    /**
     * Scope to get only counties that have shipping rates active
     */
    #[Scope]
    protected function withShippingRates(Builder $query)
    {
        $query->whereHas('shippingZone.shippingRates', function ($query) {
            $query->where('is_active', true);
        });
    }
}
