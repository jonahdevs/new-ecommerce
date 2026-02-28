<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRateAddon extends Model
{
    public function shippingRate()
    {
        return $this->belongsTo(ShippingRate::class);
    }
    public function pickupStation()
    {
        return $this->belongsTo(PickupStation::class);
    }
}
