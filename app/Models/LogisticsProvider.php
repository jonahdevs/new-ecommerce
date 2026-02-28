<?php

namespace App\Models;

use App\Enums\LogisticsProviderStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsProvider extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'status',
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function shippingMethods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }

    public function pickupStations(): HasMany
    {
        return $this->hasMany(PickupStation::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    // ===============================================
    // SCOPES
    // ===============================================
    #[Scope()]
    protected function active($query)
    {
        $query->where('status', LogisticsProviderStatus::ACTIVE->value);
    }

    protected function internal($query)
    {
        $query->where('type', 'internal');
    }

    protected function external($query)
    {
        $query->where('type', 'external');
    }

    // ===============================================
    // HELPERS
    // ===============================================
    public function isInternal(): bool
    {
        return $this->type === 'internal';
    }

    public function isActive(): bool
    {
        return $this->status === LogisticsProviderStatus::ACTIVE;
    }
}
