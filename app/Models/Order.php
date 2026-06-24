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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['user_id', 'address_id', 'delivery_zone_id', 'shipping_method_id', 'warehouse_id', 'order_number', 'status', 'subtotal_cents', 'vat_cents', 'tax_inclusive', 'delivery_cents', 'installation_cents', 'discount_cents', 'total_cents', 'coupon_id', 'coupon_code', 'payment_method', 'notes', 'staff_notes', 'confirmed_at', 'shipped_at', 'delivered_at', 'cancelled_at', 'sap_doc_entry', 'sap_doc_number', 'sap_sync_status', 'sap_synced_at', 'sap_sync_attempts', 'sap_sync_error', 'cu_number', 'receipt_path',
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

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
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
     * the operations team. Uses an atomic conditional UPDATE so concurrent webhook
     * retries can never double-fire — only the worker that actually flips the row
     * from PENDING→PROCESSING continues; all others get 0 affected rows and return.
     */
    public function markConfirmed(): void
    {
        $flipped = Order::where('id', $this->id)
            ->where('status', OrderStatus::PENDING)
            ->update(['status' => OrderStatus::PROCESSING, 'confirmed_at' => now()]);

        if (! $flipped) {
            return;
        }

        $this->status = OrderStatus::PROCESSING;

        $this->recordStatusChange(OrderStatus::PENDING, OrderStatus::PROCESSING);
        $this->deductStock();
        $this->recordCouponUse();

        $this->user?->notify(new OrderConfirmed($this));
        Notification::send(StaffRecipients::for('orders.manage'), new NewOrderReceived($this));
        OrderPlaced::dispatch($this);

        $sapConfig = app(SapConfig::class);
        if ($sapConfig->isEnabled() && $sapConfig->autoSyncOrders()) {
            SyncOrderToSapJob::dispatch($this);
        }
    }

    /**
     * Atomically deduct stock using a raw UPDATE so the floor stays at 0 even
     * under concurrent orders — unlike decrement() with a stale min() cap, which
     * races and can drive stock negative. Items are sorted by product_id so all
     * concurrent transactions acquire row locks in the same order, eliminating
     * the lock-inversion cycle that causes deadlocks.
     */
    private function deductStock(): void
    {
        $this->loadMissing('items.product', 'items.variant');

        foreach ($this->items->sortBy('product_id') as $item) {
            if ($item->variant && $item->variant->stock_quantity !== null) {
                DB::table('product_variants')
                    ->where('id', $item->variant->id)
                    ->update([
                        'stock_quantity' => DB::raw('GREATEST(0, CAST(stock_quantity AS SIGNED) - '.(int) $item->quantity.')'),
                    ]);

                continue;
            }

            if ($item->product && $item->product->stock_quantity !== null) {
                DB::table('products')
                    ->where('id', $item->product->id)
                    ->update([
                        'stock_quantity' => DB::raw('GREATEST(0, CAST(stock_quantity AS SIGNED) - '.(int) $item->quantity.')'),
                    ]);
            }
        }
    }

    /**
     * Record coupon usage atomically: lock the coupon row, create the use record,
     * and increment the counter in a single transaction so concurrent checkouts
     * cannot exceed max_uses.
     */
    private function recordCouponUse(): void
    {
        if (! $this->coupon_id) {
            return;
        }

        DB::transaction(function () {
            $coupon = Coupon::lockForUpdate()->find($this->coupon_id);

            if (! $coupon) {
                return;
            }

            if (CouponUse::where('order_id', $this->id)->exists()) {
                return;
            }

            CouponUse::create([
                'coupon_id' => $coupon->id,
                'order_id' => $this->id,
                'user_id' => $this->user_id,
                'discount_cents' => $this->discount_cents,
                'used_at' => now(),
            ]);

            $coupon->increment('uses_count');
        }, 3);
    }

    /**
     * VAT summary label using the rate snapshotted on the order's items, so it
     * reflects the rate charged at purchase rather than the current setting.
     * Shows the percentage and inclusive flag, e.g. "VAT 16% (incl.)".
     * Falls back to "VAT (mixed rates)" when items carry different rates.
     */
    /**
     * HTML label for web views — "(incl.)" is rendered smaller.
     * Use vatLabelText() for plain-text contexts like emails.
     */
    public function vatLabel(): string
    {
        $incl = $this->tax_inclusive ? ' <span class="text-xs opacity-60">(incl.)</span>' : '';
        $rates = $this->items->pluck('tax_rate')->map(fn ($rate) => (float) $rate)->filter()->unique();

        if ($rates->count() !== 1) {
            return 'VAT (mixed rates)'.$incl;
        }

        $pct = rtrim(rtrim(number_format($rates->first(), 2), '0'), '.');

        return "VAT {$pct}%{$incl}";
    }

    public function vatLabelText(): string
    {
        $suffix = $this->tax_inclusive ? ' (incl.)' : '';
        $rates = $this->items->pluck('tax_rate')->map(fn ($rate) => (float) $rate)->filter()->unique();

        if ($rates->count() !== 1) {
            return 'VAT (mixed rates)'.$suffix;
        }

        $pct = rtrim(rtrim(number_format($rates->first(), 2), '0'), '.');

        return "VAT {$pct}%{$suffix}";
    }

    /**
     * Whether items carry more than one tax rate (e.g. standard + zero-rated).
     */
    public function hasMixedTaxRates(): bool
    {
        return $this->items->pluck('tax_rate')->map(fn ($r) => (float) $r)->unique()->count() > 1;
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
