<?php

namespace App\Models;

use App\Enums\AttributeType;
use Database\Factories\AttributeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'type', 'watch_shape', 'watch_size', 'is_active', 'sort_order'])]
class Attribute extends Model
{
    /** @use HasFactory<AttributeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => AttributeType::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }
}
