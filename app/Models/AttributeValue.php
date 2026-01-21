<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $fillable = [
        'attribute_id',
        'value',
        'label',
        'slug',
        'color_code',
        'image_path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }


    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    /**
     * Get the attribute that owns this value
     */
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
