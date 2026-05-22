<?php

namespace App\Models;

use App\Concerns\LogsModelChanges;
use App\Enums\DeliveryOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SapSyncStatus;
use App\Events\OrderUpdated;
use App\Notifications\OrderStatusNotification;
use App\Services\PackingListService;
use App\Settings\OrderSettings;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    use HasFactory, LogsModelChanges;

    protected static function booted(): void
    {
        // Broadcast when a new order is created
        static::created(function (Order $order) {
            OrderUpdated::dispatch($order, 'created', auth()->id());
        });

        // Broadcast when payment status changes
        static::updated(function (Order $order) {
            if ($order->wasChanged('payment_status')) {
                OrderUpdated::dispatch($order, 'payment', auth()->id());
            }
        });
    }

    protected $fillable = [
        'user_id',
        'quote_id',
        'reference',
        'invoice_path',
        'packing_list_path',
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
        'guest_info',
        'customer_notes',
        'tracking_number',
        'courier_name',
        'preferred_county',
        'preferred_area',
        'expires_at',

        // SAP document references (named as SAP returns them)
        'sap_doc_number',
        'sap_doc_entry',

        // SAP sync lifecycle
        'sap_sync_status',
        'sap_synced_at',
        'sap_sync_attempts',
        'sap_sync_error',

        // KRA receipt fields
        'kra_cu_number',
        'kra_validated_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'shipping_snapshot' => 'array',
            'guest_info' => 'array',
            'expires_at' => 'datetime',
            'sap_synced_at' => 'datetime',
            'kra_validated_at' => 'datetime',
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'sap_sync_status' => SapSyncStatus::class,
        ];
    }

    // =====================================================
    // Relationships
    // =====================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The quote this order was converted from.
     * Null on direct cart checkouts.
     * Populated by Quote::convertToOrder() when the quote system is built.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
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

    public function sapSyncLogs(): HasMany
    {
        return $this->hasMany(SapSyncLog::class);
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            OrderItem::class,
            'order_id',
            'id',
            'id',
            'product_id',
        );
    }

    public function deliveryOrder(): HasOne
    {
        return $this->hasOne(DeliveryOrder::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class)->latest();
    }

    public function pinnedNotes(): HasMany
    {
        return $this->hasMany(OrderNote::class)->where('is_pinned', true)->latest();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(OrderTag::class, 'order_order_tag')
            ->withPivot('added_by_user_id')
            ->withTimestamps();
    }

    // =====================================================
    // Accessors
    // =====================================================

    protected function subtotal(): Attribute
    {
        return Attribute::make(get: fn () => $this->subtotal_cents / 100);
    }

    protected function discount(): Attribute
    {
        return Attribute::make(get: fn () => $this->discount_cents / 100);
    }

    protected function shipping(): Attribute
    {
        return Attribute::make(get: fn () => $this->shipping_cents / 100);
    }

    protected function tax(): Attribute
    {
        return Attribute::make(get: fn () => $this->tax_cents / 100);
    }

    protected function total(): Attribute
    {
        return Attribute::make(get: fn () => $this->total_cents / 100);
    }

    // =====================================================
    // Predicates
    // =====================================================

    /**
     * True when this order was converted from an accepted quote
     * rather than placed directly through the cart.
     */
    public function wasConvertedFromQuote(): bool
    {
        return ! is_null($this->quote_id);
    }

    // =====================================================
    // SAP / KRA predicates
    // =====================================================

    public function isSapSynced(): bool
    {
        return in_array($this->sap_sync_status, [
            SapSyncStatus::CU_PENDING,
            SapSyncStatus::CU_RECEIVED,
        ]);
    }

    public function hasKraReceipt(): bool
    {
        return ! is_null($this->kra_cu_number) && ! is_null($this->invoice_path);
    }

    public function isAwaitingKraValidation(): bool
    {
        return $this->sap_sync_status === SapSyncStatus::CU_PENDING;
    }

    public function hasSapSyncFailed(): bool
    {
        return $this->sap_sync_status === SapSyncStatus::FAILED;
    }

    // =====================================================
    // Reference generator — SO-2026-000001
    // =====================================================

    public static function generateReference(): string
    {
        $prefix = rtrim(app(OrderSettings::class)->order_id_prefix, '-').'-';
        $year = now()->year;

        // Use max() instead of count() to avoid race conditions
        // Get the highest number used this year
        // Numbers are zero-padded to 6 digits so lexicographic DESC == numeric DESC;
        // this avoids SUBSTRING_INDEX which is MySQL-only and breaks SQLite in tests.
        $lastReference = static::whereYear('created_at', $year)
            ->where('reference', 'like', "{$prefix}{$year}-%")
            ->orderBy('reference', 'desc')
            ->value('reference');

        if ($lastReference) {
            // Extract the number from the last reference (e.g., "ORD-2026-000005" -> 5)
            $lastNumber = (int) substr($lastReference, strrpos($lastReference, '-') + 1);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s%d-%06d', $prefix, $year, $nextNumber);
    }

    // =====================================================
    // Status transition
    // =====================================================

    public function transitionTo(OrderStatus $new, ?string $notes = null, string $changedByType = 'system'): void
    {
        if (! $this->status->canTransitionTo($new)) {
            throw new \Exception(
                "Cannot transition order from {$this->status->label()} to {$new->label()}."
            );
        }

        $old = $this->status;

        $this->update(['status' => $new]);

        if ($new === OrderStatus::PROCESSING) {
            $this->generatePackingList();
        }

        if ($new === OrderStatus::SHIPPED) {
            $this->createDeliveryOrderOnShip();
        }

        if ($new === OrderStatus::CANCELLED) {
            $deliveryOrder = $this->deliveryOrder()->first();
            if ($deliveryOrder && ! $deliveryOrder->isTerminal()) {
                $deliveryOrder->update(['status' => DeliveryOrderStatus::CANCELLED]);
            }
        }

        $this->statusHistories()->create([
            'from_status' => $old->value,
            'to_status' => $new->value,
            'changed_by_user_id' => auth()->id(),
            'changed_by_type' => auth()->check() ? 'user' : $changedByType,
            'notes' => $notes,
        ]);

        // Notify customer of status change for relevant statuses
        $notifiableStatuses = [
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
            OrderStatus::CANCELLED,
            OrderStatus::RETURNED,
        ];

        if ($this->user && in_array($new, $notifiableStatuses)) {
            $this->user->notify(new OrderStatusNotification($this, $new));
        }

        // Broadcast real-time update to admin dashboard
        OrderUpdated::dispatch($this, 'status', auth()->id());
    }

    /**
     * Creates a delivery order when the order is marked as shipped.
     * Idempotent — safe to call multiple times.
     */
    private function createDeliveryOrderOnShip(): void
    {
        if ($this->deliveryOrder()->exists()) {
            return;
        }

        $snapshot = $this->shipping_snapshot;

        if (! $snapshot) {
            Log::warning('Skipped delivery order creation — shipping snapshot missing', ['order_id' => $this->id]);

            return;
        }

        DeliveryOrder::create([
            'order_id' => $this->id,
            'shipping_method_id' => $snapshot['method_id'] ?? null,
            'shipping_rate_id' => $snapshot['rate_id'] ?? null,
            'shipping_zone_id' => $snapshot['zone_id'] ?? null,
            'shipping_cost' => $snapshot['cost'] ?? 0,
            'pickup_station_id' => $snapshot['station_id'] ?? null,
            'provider_reference' => $this->tracking_number,
            'status' => DeliveryOrderStatus::PENDING,
        ]);
    }

    /**
     * Generates and stores the packing slip PDF on transition to PROCESSING.
     * Failures are logged but never bubble up — a missing slip must not
     * block the status transition.
     */
    private function generatePackingList(): void
    {
        try {
            app(PackingListService::class)->generate($this);
        } catch (\Throwable $e) {
            Log::error('Failed to generate packing list', [
                'order_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =====================================================
    // Customer helpers
    // =====================================================

    public function customerName(): string
    {
        return $this->user?->name ?? $this->guest_info['name'] ?? 'Guest';
    }

    public function customerEmail(): string
    {
        return $this->user?->email ?? $this->guest_info['email'] ?? '';
    }

    public function customerPhone(): string
    {
        return $this->user?->phone ?? $this->guest_info['phone'] ?? '';
    }

    // =====================================================
    // Changelog tracking
    // =====================================================

    /**
     * Get the attributes that should be logged when changed.
     *
     * Tracks changes to order status, payment status, and customer notes
     * for audit trail purposes.
     *
     * @return array<int, string>
     */
    protected function getLoggedAttributes(): array
    {
        return ['status', 'payment_status', 'customer_notes'];
    }
}
