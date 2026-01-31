<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = [
        'shipping_zone_id',
        'min_weight',
        'max_weight',
        'price',
        'name',
        'estimated_days_min',
        'estimated_days_max',
        'is_active'
    ];

    protected $casts = [
        'min_weight' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    // ===============================================
    // SCOPES
    // ===============================================
    #[Scope]
    protected function active(Builder $query)
    {
        $query->where('is_active', true);
    }
    #[Scope]
    protected function forWeight(Builder $query, $weight)
    {
        $query->where('min_weight', '<=', $weight)
            ->where('max_weight', '>=', $weight);
    }
}
