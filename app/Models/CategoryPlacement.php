<?php

namespace App\Models;

use App\Enums\CategorySection;
use App\Enums\CategoryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['category_id', 'location', 'status', 'sort_order'])]
class CategoryPlacement extends Model
{
    protected function casts(): array
    {
        return [
            'location' => CategorySection::class,
            'status' => CategoryStatus::class,
            'sort_order' => 'integer',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
