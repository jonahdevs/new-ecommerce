<?php

namespace App\Models;

use App\Enums\CategorySection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryPlacement extends Model
{
    protected $fillable = [
        'category_id',
        'section',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'section' => CategorySection::class,
            'sort_order' => 'integer',
        ];
    }

    // ===============================================
    // Relationships
    // ===============================================

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
