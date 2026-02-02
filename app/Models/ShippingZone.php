<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function counties(): HasMany
    {
        return $this->hasMany(County::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function shippingRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    // ===============================================
    // SCOPES
    // ===============================================
    #[Scope]
    protected function active(Builder $query)
    {
        $query->where('is_active', true);
    }


    // Helper method

}
