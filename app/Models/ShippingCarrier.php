<?php

namespace App\Models;

use App\Enums\CarrierDriver;
use App\Logistics\Contracts\LogisticsDriver;
use App\Logistics\LogisticsManager;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'driver', 'credentials', 'tracking_url_template', 'priority', 'is_active', 'sort_order'])]
class ShippingCarrier extends Model
{
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'priority' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'driver' => CarrierDriver::class,
        ];
    }

    public function trackingUrlFor(string $trackingNumber): ?string
    {
        if (! $this->tracking_url_template) {
            return null;
        }

        return str_replace('{number}', $trackingNumber, $this->tracking_url_template);
    }

    public function logisticsDriver(): LogisticsDriver
    {
        return app(LogisticsManager::class)->driverForCarrier($this);
    }

    public function isSelfManaged(): bool
    {
        return $this->driver === CarrierDriver::SELF_MANAGED;
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function carrierZones(): HasMany
    {
        return $this->hasMany(CarrierZone::class, 'carrier_id');
    }

    public function carrierRates(): HasMany
    {
        return $this->hasMany(CarrierRate::class, 'carrier_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'carrier_id');
    }
}
