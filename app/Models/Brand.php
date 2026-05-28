<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'description', 'slug', 'logo', 'website_url', 'is_active', 'sort_order', 'meta_title', 'meta_description', 'canonical_url'])]
class Brand extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->logo ? Storage::url($this->logo) : null
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
