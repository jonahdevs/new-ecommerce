<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class County extends Model
{
    protected $fillable = [
        'name',
        'code',
        'shipping_zone_id',
        'sort_order'
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }
}
