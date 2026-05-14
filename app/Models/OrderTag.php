<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrderTag extends Model
{
    protected $fillable = [
        'name',
        'color',
        'description',
    ];

    // =========================================================================
    //  Available colors for tags (Tailwind color names)
    // =========================================================================

    public const COLORS = [
        'zinc' => 'Gray',
        'red' => 'Red',
        'orange' => 'Orange',
        'amber' => 'Amber',
        'yellow' => 'Yellow',
        'lime' => 'Lime',
        'green' => 'Green',
        'emerald' => 'Emerald',
        'teal' => 'Teal',
        'cyan' => 'Cyan',
        'sky' => 'Sky',
        'blue' => 'Blue',
        'indigo' => 'Indigo',
        'violet' => 'Violet',
        'purple' => 'Purple',
        'fuchsia' => 'Fuchsia',
        'pink' => 'Pink',
        'rose' => 'Rose',
    ];

    // =========================================================================
    //  Relationships
    // =========================================================================

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_order_tag')
            ->withPivot('added_by_user_id')
            ->withTimestamps();
    }
}
