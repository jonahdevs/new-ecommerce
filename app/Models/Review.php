<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'title',
        'review_text',
        'status',
        'is_verified_purchase',
        'helpful_count',
        'not_helpful_count',
        'moderated_by',
        'moderated_at',
    ];


    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_verified_purchase' => 'boolean',
            'helpful_count' => 'integer',
            'not_helpful_count' => 'integer',
            'moderated_at' => 'datetime',
        ];
    }


    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    /**
     * Get the user that wrote the review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product being reviewed
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get all images for the review
     */
    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class)->orderBy('order');
    }

    /**
     * Get all helpfulness votes for the review
     */
    public function helpfulnessVotes(): HasMany
    {
        return $this->hasMany(ReviewHelpfulness::class);
    }

    /**
     * Get the moderator who moderated this review
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

}
