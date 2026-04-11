<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SapSyncStatus;
use App\Notifications\OrderStatusNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quote_id',
        'reference',
        'invoice_path',
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
        $year = now()->year;
        $count = static::whereYear('created_at', $year)->count();

        return sprintf('SO-%d-%06d', $year, $count + 1);
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

        $this->statusHistories()->create([
            'from_status' => $old->value,
            'to_status' => $new->value,
            'changed_by_user_id' => auth()->id(),
            'changed_by_type' => auth()->check() ? 'user' : $changedByType,
            'notes' => $notes,
        ]);

        // Notify customer of status change for relevant statuses
        $notifiableStatuses = [
            OrderStatus::CONFIRMED,
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
            OrderStatus::CANCELLED,
        ];

        if ($this->user && in_array($new, $notifiableStatuses)) {
            $this->user->notify(new OrderStatusNotification($this, $new));
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
}
