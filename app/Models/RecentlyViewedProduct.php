<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecentlyViewedProduct extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    /**
     * Get the user who viewed the product
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that was viewed
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ===============================================
    // SCOPES
    // ===============================================

    /**
     * Scope to get recent views
     */
    #[Scope]
    protected function recent(Builder $query, int $days = 90)
    {
        $query->where('viewed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get views for a specific user
     */
    #[Scope]
    protected function forUser(Builder $query, int $userId)
    {
        $query->where('user_id', $userId);
    }
}
