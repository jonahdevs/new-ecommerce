<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Area extends Model
{
    protected $fillable = [
        'name',
        'county_id',
        'shipping_zone_id',
    ];


    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }
}
