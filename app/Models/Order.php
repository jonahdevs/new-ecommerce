<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SapSyncStatus;
use App\Events\OrderPlaced;
use App\Jobs\SyncOrderToSapJob;
use App\Models\Concerns\HasStatusHistory;
use App\Notifications\Orders\NewOrderReceived;
use App\Notifications\Orders\OrderConfirmed;
use App\Services\Sap\SapConfig;
use App\Settings\CheckoutSettings;
use App\Support\NumberSequence;
use App\Support\StaffRecipients;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['user_id', 'address_id', 'delivery_zone_id', 'shipping_method_id', 'warehouse_id', 'order_number', 'status', 'subtotal_cents', 'vat_cents', 'delivery_cents', 'installation_cents', 'total_cents', 'payment_method', 'notes', 'staff_notes', 'confirmed_at', 'shipped_at', 'delivered_at', 'cancelled_at', 'sap_doc_entry', 'sap_doc_number', 'sap_sync_status', 'sap_synced_at', 'sap_sync_attempts', 'sap_sync_error', 'cu_number', 'receipt_path',
    'packing_list_path', 'delivery_note_path'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasStatusHistory, LogsActivity;

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
            'sap_sync_status' => SapSyncStatus::class,
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'sap_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Order $order): void {
            $order->recordStatusChange(null, OrderStatus::PENDING);
        });
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

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

    public function sapSyncLogs(): HasMany
    {
        return $this->hasMany(SapSyncLog::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function quote(): HasOne
    {
        return $this->hasOne(Quote::class, 'order_id');
    }

    // ==================================================
    // HELPERS
    // ==================================================

    public function isPaid(): bool
    {
        return $this->payments()->where('status', PaymentStatus::SUCCESS)->exists();
    }

    public function wasConvertedFromQuote(): bool
    {
        return $this->quote()->exists();
    }

    public function hasKraReceipt(): bool
    {
        return $this->receipt_path !== null
            && Storage::disk('local')->exists($this->receipt_path);
    }

    public function isAwaitingKraValidation(): bool
    {
        return $this->sap_sync_status === SapSyncStatus::AWAITING_CU;
    }

    public function hasSapSyncFailed(): bool
    {
        return $this->sap_sync_status === SapSyncStatus::FAILED;
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
        $this->recordStatusChange(OrderStatus::PENDING, OrderStatus::PROCESSING);

        $this->deductStock();

        $this->user?->notify(new OrderConfirmed($this));
        Notification::send(StaffRecipients::for('orders.manage'), new NewOrderReceived($this));
        OrderPlaced::dispatch($this);

        $sapConfig = app(SapConfig::class);
        if ($sapConfig->isEnabled() && $sapConfig->autoSyncOrders()) {
            SyncOrderToSapJob::dispatch($this);
        }
    }

    private function deductStock(): void
    {
        $this->loadMissing('items.product', 'items.variant');

        foreach ($this->items as $item) {
            // Prefer variant-level stock tracking when a variant exists.
            if ($item->variant && $item->variant->stock_quantity !== null) {
                $item->variant->decrement('stock_quantity', min($item->quantity, $item->variant->stock_quantity));

                continue;
            }

            if ($item->product && $item->product->stock_quantity !== null) {
                $item->product->decrement('stock_quantity', min($item->quantity, $item->product->stock_quantity));
            }
        }
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
        $year = now()->year;
        $sequence = NumberSequence::next("order:{$year}");

        return $prefix.$year.'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
