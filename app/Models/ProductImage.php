<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ==================================================
    // ACCESSORS
    // ==================================================

    protected function url(): Attribute
    {
        return Attribute::get(fn () => self::resolveUrl($this->path));
    }

    // ==================================================
    // HELPERS
    // ==================================================

    public static function resolveUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
