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
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'shipping_snapshot' => 'array',
            'expires_at' => 'datetime',
            'quoted_at' => 'datetime',
            'status' => OrdersStatus::class,
            'payment_status' => PaymentStatus::class,
            'guest_info'               => 'array',
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

    // ==============================================
    // Quotation relationshiops
    //
    // parentQuotation() -> on a SALES ORDER, points back to the quotation it was converted from. Null on direct sales orders
    //
    // convertedOrder() - on a QUOTATION, points to the sale order that was created when the customer accepted the quote. Null until QUOTE_ACCEPTED convert() fires.
    // ==============================================
    public function parentQuotation(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_quotation_id');
    }

    public function convertedOrder(): HasOne
    {
        return $this->hasOne(Order::class, 'parent_quotation_id');
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
    // HELPER PREDICATES
    // Use these in Blade, Livewire, and policies instead of comparing document_type strings directly.
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

    // True when the quotation has been submitted and is awaiting admin pricing
    public function isAwaitingAdminAction(): bool
    {
        return $this->status->isAwaitingAdminAction();
    }

    //  True when this sales order was converted from a quotation
    public function wasConverted(): bool
    {
        return $this->isSalesOrder() && !is_null($this->parent_quotation_id);
    }

    // True when this quotation has been accepted and a sales order exists
    public function hasBeenConverted(): bool
    {
        return $this->isQuotation() && $this->convertedOrder()->exists();
    }

    // =================================================
    // REFERENCE GENERATORS
    // Generates a document-type-aware reference number.
    //
    // sale_order -> SO-2026-000001
    // quotation -> QTN-2026-000001
    //
    // Call this statically before creating an order:
    //  $refrence = Order::generateReference('sale_order');
    //  $refrence = Order::generateReference('quotation');
    //
    // The sequence is per document_type and per year, so SO and QTN each have their own independent numbering - mirrors SAP exactly
    // =================================================
    public static function generateReference(string $documentType): string
    {
        $year = now()->year;

        $prefix = match ($documentType) {
            'quotation' => 'QTN',
            'sales_order' => 'SO',
            default => 'SO'
        };

        $count = static::where('document_type', $documentType)
            ->whereYear('created_at', $year)
            ->count();

        return sprintf('%s-%d-%06d', $prefix, $year, $count + 1);
    }

    // ===============================================
    // QUOTATION CONVERSION
    // Create a new sales order from an accepted quotation.
    //
    // Usage (in QuotationService or Livewire action):
    // $salesOrder = $quotation->convertToSalesOrder();
    //
    // What it does:
    // 1. Validates the quotations is in QUOTE_ACCEPTED status
    // 2. Creates a new Order with document_type=sales_order
    // 3. Copies all financial fields + snapshots from the quotation
    // 4. Clones all order items onto the new sales order
    // 5. Returns the new sales order (ready for payment)
    //
    // The quotation record is never modified here - it remains the permanent historical document. The transitionTo (QUOTE_ACCEPTED) call must happen BEFORE calling this method
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
            throw new \LogicException("This quotation has already been converted to a sales order.");
        }

        $salesOrder = static::create([
            'user_id' => $this->user_id,
            'reference' => static::generateReference('sales_order'),
            'document_type' => 'sales_order',
            'quotation_type' => null,
            'parent_quotation_id' => $this->id,
            'status' => OrdersStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'currency' => $this->currency,

            // Copy all financial fields exactly — these were priced by admin
            'subtotal_cents' => $this->subtotal_cents,
            'discount_cents' => $this->discount_cents,
            'shipping_cents' => $this->shipping_cents,
            'tax_cents' => $this->tax_cents,
            'total_cents' => $this->total_cents,

            // Copy address and shipping snapshots
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'shipping_snapshot' => $this->shipping_snapshot,

            // Sales orders don't have quote-specific timestamps
            'expires_at' => null,
            'quoted_at' => null,
        ]);

        // Clone all order items onto the new sales order
        foreach ($this->items as $item) {
            $salesOrder->items()->create([
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unit_price_cents,
                'unit_tax_cents' => $item->unit_tax_cents,
                'discount_cents' => $item->discount_cents,
                'total_cents' => $item->total_cents,
                'product_snapshot' => $item->product_snapshot,
            ]);
        }

        // Record the conversion in the quotation's own status history
        $this->statusHistories()->create([
            'from_status' => $this->status->value,
            'to_status' => $this->status->value,
            'changed_by_user_id' => auth()->id(),
            'changed_by_type' => 'system',
            'notes' => "Converted to sales order {$salesOrder->reference}.",
            'metadata' => ['converted_order_id' => $salesOrder->id],
        ]);

        return $salesOrder;
    }

    // ======================================================
    // STATUS TRANSITION
    // The OrderStatus enum's canTransitionTo() enforces correct lifecycle for each document type automatically.
    // ======================================================




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
            'from_status' => $old->value,
            'to_status' => $new->value,
            'changed_by_user_id' => auth()->id(),
            'changed_by_type' => auth()->check() ? 'user' : $changedByType,
            'notes' => $notes,
        ]);
    }
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
