<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'is_active',
        'is_visible',
        'used_for_variations',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'used_for_variations' => 'boolean',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    /**
     * Get all values for this attribute
     */
    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
