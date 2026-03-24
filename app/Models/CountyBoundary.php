<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyBoundary extends Model
{
    protected $fillable = [
        'county_id',
        'geojson',
        'bbox_min_lat',
        'bbox_max_lat',
        'bbox_min_lng',
        'bbox_max_lng'
    ];
}
