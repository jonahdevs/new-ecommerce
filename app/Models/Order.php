<?php

namespace App\Models;

use App\Enums\{OrdersStatus, PaymentStatus, SapSyncStatus};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasManyThrough, HasOne};

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'reference',
        'document_type',
        'quotation_type',
        'parent_quotation_id',
        'quoted_at',
        'invoice_path',
        'quotation_pdf_path',
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
        'expires_at',
        'guest_info',
        'customer_notes',
        'preferred_county',
        'preferred_area',
        'lpo_number',

        // SAP document references
        'sap_order_number',
        'sap_invoice_number',
        'sap_payment_number',

        // SAP sync lifecycle
        'sap_sync_status',
        'sap_synced_at',
        'sap_sync_attempts',
        'sap_sync_error',

        // eTIMS device fields
        'etims_cu_serial_no',
        'etims_cu_datetime',
        'etims_qr_code',
        'etims_status',

        // KRA receipt fields
        'kra_cu_number',
        'kra_invoice_number',
        'kra_validated_at',
        'kra_receipt_path',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address'  => 'array',
            'billing_address'   => 'array',
            'shipping_snapshot' => 'array',
            'guest_info'        => 'array',
            'expires_at'        => 'datetime',
            'quoted_at'         => 'datetime',
            'sap_synced_at'     => 'datetime',
            'etims_cu_datetime' => 'datetime',
            'kra_validated_at'  => 'datetime',
            'status'            => OrdersStatus::class,
            'payment_status'    => PaymentStatus::class,
            'sap_sync_status'   => SapSyncStatus::class,
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

    public function parentQuotation(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_quotation_id');
    }

    public function convertedOrder(): HasOne
    {
        return $this->hasOne(Order::class, 'parent_quotation_id');
    }

    // ===============================================
    // Accessors
    // ===============================================

    protected function subtotal(): Attribute
    {
        return Attribute::make(get: fn() => $this->subtotal_cents / 100);
    }

    protected function discount(): Attribute
    {
        return Attribute::make(get: fn() => $this->discount_cents / 100);
    }

    protected function shipping(): Attribute
    {
        return Attribute::make(get: fn() => $this->shipping_cents / 100);
    }

    protected function total(): Attribute
    {
        return Attribute::make(get: fn() => $this->total_cents / 100);
    }

    // ===============================================
    // Document type predicates
    // ===============================================

    public function isSalesOrder(): bool
    {
        return $this->document_type === 'sales_order';
    }

    public function isQuotation(): bool
    {
        return $this->document_type === 'quotation';
    }

    public function isDeliveryQuotation(): bool
    {
        return $this->isQuotation() && $this->quotation_type === 'delivery';
    }

    public function isProductQuotation(): bool
    {
        return $this->isQuotation() && $this->quotation_type === 'product';
    }

    public function isAwaitingAdminAction(): bool
    {
        return $this->status->isAwaitingAdminAction();
    }

    public function wasConverted(): bool
    {
        return $this->isSalesOrder() && !is_null($this->parent_quotation_id);
    }

    public function hasBeenConverted(): bool
    {
        return $this->isQuotation() && $this->convertedOrder()->exists();
    }

    // ===============================================
    // SAP / KRA predicates
    // Use these instead of comparing sap_sync_status strings directly.
    // ===============================================

    /**
     * True once the order has at least reached SAP successfully.
     * Covers synced, cu_pending, and cu_received — i.e. anything past the
     * initial sync step.
     */
    public function isSapSynced(): bool
    {
        return in_array($this->sap_sync_status, [
            SapSyncStatus::SYNCED,
            SapSyncStatus::CU_PENDING,
            SapSyncStatus::CU_RECEIVED,
        ]);
    }

    /**
     * True when both the CU number and the receipt PDF exist.
     * Safe to call from Blade without hitting the DB.
     */
    public function hasKraReceipt(): bool
    {
        return !is_null($this->kra_cu_number) && !is_null($this->kra_receipt_path);
    }

    /**
     * True when the order is in SAP but still waiting for eTIMS/KRA to
     * return the CU number via webhook.
     */
    public function isAwaitingKraValidation(): bool
    {
        return $this->sap_sync_status === SapSyncStatus::CU_PENDING;
    }

    /**
     * True when the sync has permanently failed (exhausted all retries).
     */
    public function hasSapSyncFailed(): bool
    {
        return $this->sap_sync_status === SapSyncStatus::FAILED;
    }

    // ===============================================
    // Reference generators
    // ===============================================

    public static function generateReference(string $documentType): string
    {
        $year = now()->year;

        $prefix = match ($documentType) {
            'quotation'   => 'QTN',
            'sales_order' => 'SO',
            default       => 'SO',
        };

        $count = static::where('document_type', $documentType)
            ->whereYear('created_at', $year)
            ->count();

        return sprintf('%s-%d-%06d', $prefix, $year, $count + 1);
    }

    // ===============================================
    // Quotation conversion
    // ===============================================

    public function convertToSalesOrder(): static
    {
        if (!$this->isQuotation()) {
            throw new \LogicException('Only quotations can be converted to sales orders.');
        }

        if ($this->status !== OrdersStatus::QUOTE_ACCEPTED) {
            throw new \LogicException(
                "Quotation must be in QUOTE_ACCEPTED status to convert. Current status: {$this->status->label()}."
            );
        }

        if ($this->hasBeenConverted()) {
            throw new \LogicException('This quotation has already been converted to a sales order.');
        }

        $salesOrder = static::create([
            'user_id'             => $this->user_id,
            'reference'           => static::generateReference('sales_order'),
            'document_type'       => 'sales_order',
            'quotation_type'      => null,
            'parent_quotation_id' => $this->id,
            'status'              => OrdersStatus::PENDING,
            'payment_status'      => PaymentStatus::PENDING,
            'currency'            => $this->currency,
            'subtotal_cents'      => $this->subtotal_cents,
            'discount_cents'      => $this->discount_cents,
            'shipping_cents'      => $this->shipping_cents,
            'tax_cents'           => $this->tax_cents,
            'total_cents'         => $this->total_cents,
            'shipping_address'    => $this->shipping_address,
            'billing_address'     => $this->billing_address,
            'shipping_snapshot'   => $this->shipping_snapshot,
            'expires_at'          => null,
            'quoted_at'           => null,
        ]);

        foreach ($this->items as $item) {
            $salesOrder->items()->create([
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity'           => $item->quantity,
                'unit_price_cents'   => $item->unit_price_cents,
                'unit_tax_cents'     => $item->unit_tax_cents,
                'discount_cents'     => $item->discount_cents,
                'total_cents'        => $item->total_cents,
                'product_snapshot'   => $item->product_snapshot,
            ]);
        }

        $this->statusHistories()->create([
            'from_status'          => $this->status->value,
            'to_status'            => $this->status->value,
            'changed_by_user_id'   => auth()->id(),
            'changed_by_type'      => 'system',
            'notes'                => "Converted to sales order {$salesOrder->reference}.",
            'metadata'             => ['converted_order_id' => $salesOrder->id],
        ]);

        return $salesOrder;
    }

    // ===============================================
    // Status transitions
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

        $this->statusHistories()->create([
            'from_status'          => $old->value,
            'to_status'            => $new->value,
            'changed_by_user_id'   => auth()->id(),
            'changed_by_type'      => auth()->check() ? 'user' : $changedByType,
            'notes'                => $notes,
        ]);
    }

    // ===============================================
    // Customer helpers
    // ===============================================

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
