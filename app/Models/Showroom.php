<?php

namespace App\Models;

use Database\Factories\ShowroomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Showroom extends Model
{
    /** @use HasFactory<ShowroomFactory> */
    use HasFactory;

    protected $fillable = [
        'city',
        'country',
        'address',
        'pobox',
        'phones',
        'email',
        'is_hq',
        'sort_order',
    ];

    /**
     * @return array{phones: 'array', is_hq: 'boolean', sort_order: 'integer'}
     */
    protected function casts(): array
    {
        return [
            'phones' => 'array',
            'is_hq' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
