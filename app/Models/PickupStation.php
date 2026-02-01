<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupStation extends Model
{
    protected $fillable = [
        'name',
        'code',
        'county_id',
        'area_id',
        'address',
        'phone',
        'operating_hours',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
