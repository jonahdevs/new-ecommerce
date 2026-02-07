<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{

    protected $fillable = [
        'order_id',
        'amount_cents',
        'currency',
        'status',
        'gateway',
        'transaction_id',
        'payment_method_token',
        'card_brand',
        'card_last4',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }


    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    /**
     * Get the order that this payment belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Accessor for amount in currency units
    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }
}
