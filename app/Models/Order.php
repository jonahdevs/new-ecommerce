<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Settings\CheckoutSettings;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'address_id', 'delivery_zone_id', 'shipping_method_id', 'warehouse_id', 'order_number', 'status', 'subtotal_cents', 'vat_cents', 'delivery_cents', 'installation_cents', 'total_cents', 'payment_method', 'notes'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function isPaid(): bool
    {
        return $this->payments()->where('status', PaymentStatus::SUCCESS)->exists();
    }

    /**
     * Next order number, formatted {prefix}{year}-{sequence} (e.g. SHF-2026-00001).
     * The prefix comes from {@see CheckoutSettings}.
     */
    public static function generateNumber(): string
    {
        $prefix = app(CheckoutSettings::class)->order_prefix;
        $sequence = static::whereYear('created_at', now()->year)->count() + 1;

        return $prefix.now()->year.'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
