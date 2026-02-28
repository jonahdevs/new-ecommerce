<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsProvider extends Model
{
    public function shippingMethods()
    {
        return $this->hasMany(ShippingMethod::class);
    }
}
