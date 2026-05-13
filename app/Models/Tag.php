<?php

namespace App\Models;

use Spatie\Tags\Tag as SpatieTag;

class Tag extends SpatieTag
{
    protected $fillable = ['name', 'slug', 'type', 'order_column', 'color'];

    public function products()
    {
        return $this->morphedByMany(Product::class, 'taggable');
    }

    /**
     * Returns the badge color as an inline CSS style string.
     * Falls back to a neutral grey if no color is set.
     */
    public function badgeStyle(): string
    {
        $color = $this->color ?: '#6b7280';
        return "background-color: {$color};";
    }
}
