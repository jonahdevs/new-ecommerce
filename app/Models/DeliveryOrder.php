<?php

namespace App\Models;

use App\Enums\DeliveryOrderStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'logistics_provider_id',
        'shipping_method_id',
        'shipping_zone_id',
        'shipping_rate_id',
        'vehicle_rate_id',
        'pickup_station_id',
        'distance_km',
        'cost_breakdown',
        'shipping_cost',
        'package_weight_kg',
        'is_return',
        'provider_reference',
        'status',
        'estimated_delivery_at',
        'delivered_at',
        'collection_deadline_at',
    ];

    protected $casts = [
        'cost_breakdown' => 'array',
        'shipping_cost' => 'decimal:2',
        'package_weight_kg' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'is_return' => 'boolean',
        'status' => DeliveryOrderStatus::class,
        'estimated_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'collection_deadline_at' => 'datetime',
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function logisticsProvider(): BelongsTo
    {
        return $this->belongsTo(LogisticsProvider::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class);
    }

    public function vehicleRate(): BelongsTo
    {
        return $this->belongsTo(VehicleRate::class);
    }

    public function pickupStation(): BelongsTo
    {
        return $this->belongsTo(PickupStation::class);
    }

    // ===============================================
    // SCOPE
    // ===============================================
    #[Scope()]
    protected function forward($query)
    {
        $query->where('is_return', false);
    }

    #[Scope()]
    public function returns($query)
    {
        $query->where('is_return', true);
    }

    #[Scope()]
    public function atStation($query)
    {
        $query->where('status', DeliveryOrderStatus::ATSTATION->value);
    }

    #[Scope()]
    public function overdueCollection($query)
    {
        $query->where('status', DeliveryOrderStatus::ATSTATION->value)
            ->where('collection_deadline_at', '<', now());
    }

    #[Scope()]
    public function active($query)
    {
        $query->whereNotIn('status', [
            DeliveryOrderStatus::DELIVERED->value,
            DeliveryOrderStatus::COLLECTED->value,
            DeliveryOrderStatus::RETURNED->value,
            DeliveryOrderStatus::CANCELLED->value,
        ]);
    }

    // ===============================================
    // STATUS HELPERS
    // ===============================================

    public function getStatusEnum(): DeliveryOrderStatus
    {
        return $this->status instanceof DeliveryOrderStatus
            ? $this->status
            : DeliveryOrderStatus::from($this->status);
    }

    public function isTerminal(): bool
    {
        return $this->getStatusEnum()->isTerminal();
    }

    public function isAtStation(): bool
    {
        return $this->getStatusEnum() === DeliveryOrderStatus::ATSTATION;
    }

    public function isOverdueCollection(): bool
    {
        return $this->isAtStation()
            && $this->collection_deadline_at !== null
            && $this->collection_deadline_at->isPast();
    }

    public function isReturn(): bool
    {
        return $this->is_return;
    }


    // ===============================================
    // PUS HELPERS
    // ===============================================

    /**
     * Set the collection deadline from the pickup station's holding days.
     * Call this when status transitions to at_station.
     */
    public function setCollectionDeadline(): void
    {
        if ($this->pickupStation) {
            $this->collection_deadline_at = $this->pickupStation->collectionDeadline(now());
        }
    }

    // ===============================================
    // COST BREAKDOWN ACCESSORS
    // ===============================================
    public function getPricingModel(): ?string
    {
        return $this->cost_breakdown['model'] ?? null;
    }

    public function getCostBreakdownTotal(): float
    {
        return $this->cost_breakdown['total'] ?? $this->shipping_cost;
    }
}
