<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReviewImage extends Model
{
    protected $fillable = [
        'review_id',
        'image_path',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    /**
     * Get the review that owns the image
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    // ===============================================
    // ACCESSORS
    // ===============================================

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path ? (Str::startsWith($this->image_path, 'http') ? $this->image_path : asset('storage/'.$this->image_path)) : null
        );
    }
}
