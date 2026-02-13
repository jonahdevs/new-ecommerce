<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InventoryReservation Model
 * 
 * Tracks temporary stock holds while payment is being processed.
 * Prevents overselling by "reserving" inventory until:
 * 1. Payment is confirmed (reservation deleted, stock deducted)
 * 2. Payment fails/expires (reservation deleted, stock released)
 * 3. Reservation expires (auto-cleanup via scheduled job)
 */
class InventoryReservation extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'reservable_type',
        'reservable_id',
        'order_id',
        'quantity',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'quantity' => 'integer',
    ];

    /**
     * Get the reservable item (Product or ProductVariant)
     * 
     * This polymorphic relationship allows the same table to track
     * reservations for both simple products and product variants.
     */
    public function reservable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the order that created this reservation
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope: Get only active (non-expired) reservations
     * 
     * Usage: InventoryReservation::active()->get()
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope: Get expired reservations
     * 
     * Usage: InventoryReservation::expired()->get()
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope: Get reservations for a specific order
     * 
     * Usage: InventoryReservation::forOrder($orderId)->get()
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Check if this reservation has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    /**
     * Check if this reservation is still active
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Get a human-readable description of this reservation
     */
    public function getDescriptionAttribute(): string
    {
        $productName = $this->reservable?->name ?? 'Unknown Product';
        return "{$this->quantity} units of {$productName} reserved for Order #{$this->order_id}";
    }
}
