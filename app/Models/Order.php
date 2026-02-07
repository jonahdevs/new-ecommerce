<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'reference',
        'status',
        'currency',
        'subtotal_cents',
        'discount_cents',
        'shipping_cents',
        'tax_cents',
        'total_cents',
        'shipping_address',
        'billing_address',
        'placed_at',
        'shipping_zone_id',
        'warehouse_id',
        'is_pickup',
        'pickup_ready_at',
        'pickup_collected_at',
        'estimated_delivery_from',
        'estimated_delivery_to',
        'actual_delivery_date',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'placed_at' => 'datetime',
            'is_pickup' => 'boolean',
            'pickup_ready_at' => 'datetime',
            'pickup_collected_at' => 'datetime',
            'estimated_delivery_from' => 'date',
            'estimated_delivery_to' => 'date',
            'actual_delivery_date' => 'date',
        ];
    }

    // ===============================================
    // Relationships
    // ===============================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function statusHistory(): HasMany
    {

        return $this->hasMany(OrderStatusHistory::class);
    }


    // Accessors for money fields
    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->subtotal_cents / 100,
        );
    }

    protected function discount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->discount_cents / 100,
        );
    }

    protected function shipping(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->shipping_cents / 100,
        );
    }

    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->total_cents / 100,
        );
    }
}
