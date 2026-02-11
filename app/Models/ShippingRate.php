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
        'shipping_method_id',
        'min_weight',
        'max_weight',
        'price',
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

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
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

    // ===============================================
    // HELPER METHODS
    // ===============================================

    /**
     * Get formatted estimated delivery time
     */
    public function getEstimatedDeliveryAttribute(): ?string
    {
        if (!$this->estimated_days_min && !$this->estimated_days_max) {
            return null;
        }

        if ($this->estimated_days_min === $this->estimated_days_max) {
            return "{$this->estimated_days_min} days";
        }

        return "{$this->estimated_days_min}-{$this->estimated_days_max} days";
    }
}
