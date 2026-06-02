<?php

namespace App\Models;

use App\Enums\ShippingMethodType;
use Database\Factories\ShippingMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'type', 'is_active', 'sort_order'])]
class ShippingMethod extends Model
{
    /** @use HasFactory<ShippingMethodFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => ShippingMethodType::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isDelivery(): bool
    {
        return $this->type === ShippingMethodType::DELIVERY;
    }

    public function isPickup(): bool
    {
        return $this->type === ShippingMethodType::PICKUP;
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function carrierRates(): HasMany
    {
        return $this->hasMany(CarrierRate::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
