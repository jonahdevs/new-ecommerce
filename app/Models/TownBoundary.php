<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TownBoundary extends Model
{
    protected $fillable = [
        'town_id',
        'geojson',
        'bbox_min_lat',
        'bbox_max_lat',
        'bbox_min_lng',
        'bbox_max_lng',
    ];

    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }
}
