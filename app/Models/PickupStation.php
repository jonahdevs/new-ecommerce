<?php

namespace App\Models;

use App\Enums\PickupStationStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickupStation extends Model
{
    protected $fillable = [
        'logistics_provider_id',
        'name',
        'code',
        'county_id',
        'area_id',
        'address',
        'phone',
        'operating_hours',
        'latitude',
        'longitude',
        'holding_days',
        'status',
        'is_primary',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'holding_days' => 'integer',
        'status' => PickupStationStatus::class,
        'is_primary' => 'boolean',
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function logisticsProvider(): BelongsTo
    {
        return $this->belongsTo(LogisticsProvider::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function rateAddons(): HasMany
    {
        return $this->hasMany(ShippingRateAddon::class);
    }

    // ===============================================
    // Scope
    // ===============================================
    #[Scope()]
    protected function active($query)
    {
        $query->where('status', PickupStationStatus::ACTIVE->value);
    }

    protected function acceptingParcels($query)
    {
        // Both active and temporarily_closed stations appear in the
        // list but temporarily_closed ones do not accept new parcels.
        $query->where('status', PickupStationStatus::ACTIVE->value);
    }

    // ===============================================
    // HELPERS
    // ===============================================

    public function isActive(): bool
    {
        return $this->status === PickupStationStatus::ACTIVE;
    }

    public function isTemporarilyClosed(): bool
    {
        return $this->status === PickupStationStatus::TEMPORARILYCLOSED;
    }

    public function isAcceptingParcels(): bool
    {
        return $this->isActive();
    }

    /**
     * Calculate the collection deadline for a parcel arriving today.
     */
    public function collectionDeadline(?Carbon $arrivedAt = null): Carbon
    {
        return ($arrivedAt ?? now())->addDays($this->holding_days)->endOfDay();
    }
}
