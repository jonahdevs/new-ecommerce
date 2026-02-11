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
    public function availableShippingMethods()
    {
        return ShippingMethod::whereHas('shippingRates', function ($query) {
            $query->where('shipping_zone_id', $this->id)
                ->where('is_active', true);
        })->where('is_active', true)
            ->get();
    }

    // Get rates for a specific method and weight
    public function getRateForMethod($methodId, $weight)
    {
        return $this->shippingRates()
            ->where('shipping_method_id', $methodId)
            ->where('is_active', true)
            ->where('min_weight', '<=', $weight)
            ->where('max_weight', '>=', $weight)
            ->first();
    }
}
