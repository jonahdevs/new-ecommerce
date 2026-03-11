<?php

namespace App\Models;

use App\Enums\{OrdersStatus, PaymentStatus};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasManyThrough, HasOne};


class Order extends Model
{
    protected $fillable = [
        'user_id',
        'reference',
        'status',
        'payment_status',
        'currency',
        'subtotal_cents',
        'discount_cents',
        'shipping_cents',
        'tax_cents',
        'total_cents',
        'shipping_address',
        'billing_address',
        'shipping_snapshot',
        'expires_at'
    ];

    protected function casts(): array
    {
        return [
            'shipping_address'  => 'array',
            'billing_address'   => 'array',
            'shipping_snapshot' => 'array',
            'expires_at'        => 'datetime',
            'status'            => OrdersStatus::class,
            'payment_status' => PaymentStatus::class,
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

    public function statusHistories(): HasMany
    {

        return $this->hasMany(OrderStatusHistory::class);
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            OrderItem::class,
            'order_id',      // FK on order_items
            'id',            // FK on products
            'id',            // local key on orders
            'product_id',    // local key on order_items
        );
    }

    public function deliveryOrder(): HasOne
    {
        return $this->hasOne(DeliveryOrder::class);
    }


    // ===============================================
    // ACCESSORS
    // ===============================================
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

    // ===============================================
    // Helper Method
    // ===============================================

    public function transitionTo(OrdersStatus $new, ?string $notes = null, string $changedByType = 'system'): void
    {
        if (!$this->status->canTransitionTo($new)) {
            throw new \Exception(
                "Cannot transition order from {$this->status->label()} to {$new->label()}."
            );
        }

        $old = $this->status;

        $this->update(['status' => $new]);

        // Auto-record every transition
        $this->statusHistories()->create([
            'from_status'        => $old->value,
            'to_status'          => $new->value,
            'changed_by_user_id' => auth()->id(),
            'changed_by_type'    => auth()->check() ? 'user' : $changedByType,
            'notes'              => $notes,
        ]);
    }
}
