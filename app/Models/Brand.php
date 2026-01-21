<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'logo_path',
        'website_url',
        'is_active',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
    ];

    protected function casts(): array
    {
        return [
            'meta_keywords' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================


}
