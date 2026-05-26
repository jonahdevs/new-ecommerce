<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'rate', 'description', 'is_active'])]
class TaxClass extends Model
{
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
