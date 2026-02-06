<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreeShippingRule extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'shipping_zone_id',
        'shipping_method_id',
        'min_order_amount',
        'max_weight',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'min_order_amount' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Relationship: The specific zone this rule applies to.
     * If null, the rule is usually considered "Global".
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    /**
     * Relationship: The specific shipping method this rule applies to.
     * If null, it applies to all methods (e.g., Standard and Express both become free).
     */
    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }
}
