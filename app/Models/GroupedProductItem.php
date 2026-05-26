<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['group_product_id', 'child_product_id', 'sort_order'])]
class GroupedProductItem extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function group(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'group_product_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'child_product_id');
    }
}
