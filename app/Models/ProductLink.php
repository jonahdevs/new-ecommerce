<?php

namespace App\Models;

use App\Enums\ProductLinkType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'linked_product_id', 'type', 'sort_order'])]
class ProductLink extends Model
{
    protected function casts(): array
    {
        return [
            'type' => ProductLinkType::class,
            'sort_order' => 'integer',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function linkedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'linked_product_id');
    }
}
