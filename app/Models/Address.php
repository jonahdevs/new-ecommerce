<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone_number',
        'alternative_phone_number',
        'county_id',
        'area_id',
        'address',
        'additional_information',
        'shipping_zone_id',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }
}
