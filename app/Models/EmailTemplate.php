<?php

namespace App\Models;

use App\Enums\EmailTemplateType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
        'subject',
        'body_html',
        'body_json',
        'variables',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type'      => EmailTemplateType::class,
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, EmailTemplateType|string $type): Builder
    {
        $value = $type instanceof EmailTemplateType ? $type->value : $type;

        return $query->where('type', $value);
    }
}
