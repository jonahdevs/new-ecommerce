<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['product_id', 'path', 'alt', 'is_cover', 'sort_order'])]
class ProductImage extends Model
{
    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::url($this->path));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
