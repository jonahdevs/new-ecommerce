<?php

namespace App\Models;

use Database\Factories\ShowroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['city', 'country', 'address', 'pobox', 'phones', 'email', 'is_hq', 'sort_order'])]
class Showroom extends Model
{
    /** @use HasFactory<ShowroomFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'phones' => 'array',
            'is_hq' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
