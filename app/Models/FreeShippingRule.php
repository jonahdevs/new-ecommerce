<?php

namespace App\Models;

use App\Enums\FreeShippingRuleStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreeShippingRule extends Model
{
    protected $fillable = [
        'name',
        'shipping_zone_id',
        'shipping_method_id',
        'min_order_amount',
        'max_weight',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'min_order_amount' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'status' => FreeShippingRuleStatus::class,
    ];

    // ===============================================
    // RELATIONSHIP
    // ===============================================

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    // ===============================================
    // Scope
    // ===============================================
    #[Scope()]
    protected function active($query)
    {
        $query->where('status', FreeShippingRuleStatus::ACTIVE->value);
    }

    /**
     * Rules currently applicable at checkout.
     * Matches active rules that are either unscoped or match the given
     * zone/method combination.
     */
    #[Scope]
    protected function applicable($query, int $zoneId, int $methodId)
    {
        return $query->where('status', FreeShippingRuleStatus::ACTIVE->value)
            ->where(
                fn ($q) => $q->whereNull('shipping_zone_id')
                    ->orWhere('shipping_zone_id', $zoneId)
            )
            ->where(
                fn ($q) => $q->whereNull('shipping_method_id')
                    ->orWhere('shipping_method_id', $methodId)
            );
    }

    // ===============================================
    // HELPERS
    // ===============================================

    public function isActive(): bool
    {
        return $this->status === FreeShippingRuleStatus::ACTIVE;
    }

    /**
     * Check if a given order qualifies for free shipping under this rule.
     */
    public function qualifies(float $orderAmount, ?float $weightKg = null): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($orderAmount < $this->min_order_amount) {
            return false;
        }

        if ($this->max_weight !== null && $weightKg !== null && $weightKg > $this->max_weight) {
            return false;
        }

        return true;
    }

    /**
     * Whether this rule applies to all zones (no zone restriction).
     */
    public function isNationwide(): bool
    {
        return $this->shipping_zone_id === null;
    }
}
