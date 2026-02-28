<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    public function logisticsProvider()
    {
        return $this->belongsTo(LogisticsProvider::class);
    }
    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }
    public function shippingZone()
    {
        return $this->belongsTo(ShippingZone::class);
    }
    public function pickupStation()
    {
        return $this->belongsTo(PickupStation::class);
    }
    public function shippingRate()
    {
        return $this->belongsTo(ShippingRate::class);
    }
    public function vehicleRate()
    {
        return $this->belongsTo(VehicleRate::class);
    }
}
