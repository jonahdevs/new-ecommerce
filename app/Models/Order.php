<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Notifications\Orders\NewOrderReceived;
use App\Notifications\Orders\OrderConfirmed;
use App\Settings\CheckoutSettings;
use App\Support\StaffRecipients;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['user_id', 'address_id', 'delivery_zone_id', 'shipping_method_id', 'warehouse_id', 'order_number', 'status', 'subtotal_cents', 'vat_cents', 'delivery_cents', 'installation_cents', 'total_cents', 'payment_method', 'notes'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'payment_method', 'notes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('order');
    }

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
     * Confirm a paid order: move it into processing and notify the customer and
     * the operations team. Idempotent — only the first PENDING→PROCESSING
     * transition sends notifications, so a webhook and a poll can't double-fire.
     */
    public function markConfirmed(): void
    {
        if ($this->status !== OrderStatus::PENDING) {
            return;
        }

        $this->update(['status' => OrderStatus::PROCESSING]);

        $this->user?->notify(new OrderConfirmed($this));
        Notification::send(StaffRecipients::for('orders.manage'), new NewOrderReceived($this));
    }

    /**
     * VAT summary label using the rate snapshotted on the order's items, so it
     * reflects the rate charged at purchase rather than the current setting.
     * Falls back to a plain "VAT" when the rate is mixed or unavailable.
     */
    public function vatLabel(): string
    {
        $rates = $this->items->pluck('tax_rate')->map(fn ($rate) => (float) $rate)->filter()->unique();

        if ($rates->count() !== 1) {
            return 'VAT';
        }

        return 'VAT ('.rtrim(rtrim(number_format($rates->first(), 2), '0'), '.').'%)';
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
