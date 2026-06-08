<?php

namespace App\Models;

use Database\Factories\BannedIpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ip_address', 'comment', 'expires_at', 'created_by_id'])]
class BannedIp extends Model
{
    /** @use HasFactory<BannedIpFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
