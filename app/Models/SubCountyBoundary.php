<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubCountyBoundary extends Model
{
    protected $fillable = [
        'sub_county_id',
        'geojson',
        'bbox_min_lat',
        'bbox_max_lat',
        'bbox_min_lng',
        'bbox_max_lng',
    ];

    public function subCounty(): BelongsTo
    {
        return $this->belongsTo(SubCounty::class);
    }
}
