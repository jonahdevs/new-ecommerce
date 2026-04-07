<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxClass extends Model
{
    /** @use HasFactory<\Database\Factories\TaxClassFactory> */
    use HasFactory;

    protected $fillable = ['name', 'rate', 'description'];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function rateLabel(): string
    {
        return rtrim(rtrim(number_format((float) $this->rate, 2), '0'), '.') . '%';
    }
}
