<?php

namespace App\Models;

use App\Enums\VehicleRateStatus;
use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleRate extends Model
{
    protected $fillable = [
        'shipping_method_id',
        'vehicle_type',
        'vehicle_label',
        'base_rate',
        'base_km',
        'extra_km_rate',
        'max_weight_kg',
        'max_volume_m3',
        'status',
    ];

    protected $casts = [
        'vehicle_type' => VehicleType::class,
        'base_rate' => 'decimal:2',
        'base_km' => 'integer',
        'extra_km_rate' => 'decimal:2',
        'max_weight_kg' => 'decimal:2',
        'max_volume_m3' => 'decimal:3',
        'status' => VehicleRateStatus::class,
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    // ===============================================
    // Scope
    // ===============================================
    #[Scope()]
    protected function active($query)
    {
        $query->where('status', VehicleRateStatus::ACTIVE->value);
    }

    // ===============================================
    // PRICING
    // ===============================================

    /**
     * Calculate the cost for a given distance.
     * Formula: base_rate + max(0, distance_km - base_km) × extra_km_rate
     */
    public function calculateCost(float $distanceKm): float
    {
        $extraKm = max(0, $distanceKm - $this->base_km);
        $extraCost = $extraKm * $this->extra_km_rate;

        return round($this->base_rate + $extraCost, 2);
    }

    /**
     * Build the cost_breakdown JSON for a delivery order.
     */
    public function buildBreakdown(float $distanceKm): array
    {
        $extraKm = max(0, $distanceKm - $this->base_km);
        $extraCost = round($extraKm * $this->extra_km_rate, 2);
        $total = round($this->base_rate + $extraCost, 2);

        return [
            'model' => 'distance',
            'vehicle' => $this->vehicle_label,
            'distance_km' => $distanceKm,
            'base_km' => $this->base_km,
            'base_rate' => $this->base_rate,
            'extra_km' => $extraKm,
            'extra_km_rate' => $this->extra_km_rate,
            'extra_km_cost' => $extraCost,
            'total' => $total,
        ];
    }

    // ===============================================
    // HELPERS
    // ===============================================

    public function isActive(): bool
    {
        return $this->status === VehicleRateStatus::ACTIVE;
    }

    public function canCarryWeight(float $weightKg): bool
    {
        if ($this->max_weight_kg === null)
            return true;
        return $weightKg <= $this->max_weight_kg;
    }
}
