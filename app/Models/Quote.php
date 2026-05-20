<?php

namespace App\Models;

use App\Concerns\LogsModelChanges;
use App\Enums\QuoteStatus;
use App\Events\QuoteUpdated;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quote extends Model
{
    use HasFactory, LogsModelChanges;

    protected static function booted(): void
    {
        // Broadcast when a new quote is created
        static::created(function (Quote $quote) {
            QuoteUpdated::dispatch($quote, 'created');
        });
    }

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
        'delivery_type',
        'preferred_county',
        'preferred_area',
        'customer_notes',
        'guest_info',
        'admin_notes',
        'quoted_at',
        'expires_at',
        'reminder_sent_at',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
        'document_path',
    ];

    protected function casts(): array
    {
        return [
            'guest_info' => 'array',
            'quoted_at' => 'datetime',
            'expires_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'status' => QuoteStatus::class,
        ];
    }

    // =====================================================
    // Relationships
    // =====================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(QuoteStatusHistory::class);
    }

    /**
     * The order created when this quote was accepted.
     */
    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
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
    // Status predicates
    // =====================================================

    public function isPending(): bool
    {
        return $this->status === QuoteStatus::PENDING;
    }

    public function isSent(): bool
    {
        return $this->status === QuoteStatus::SENT;
    }

    public function isAccepted(): bool
    {
        return $this->status === QuoteStatus::ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === QuoteStatus::REJECTED;
    }

    public function isExpired(): bool
    {
        return $this->status === QuoteStatus::EXPIRED;
    }

    public function isCancelled(): bool
    {
        return $this->status === QuoteStatus::CANCELLED;
    }

    public function isResolved(): bool
    {
        return in_array($this->status, [
            QuoteStatus::ACCEPTED,
            QuoteStatus::REJECTED,
            QuoteStatus::EXPIRED,
            QuoteStatus::CANCELLED,
        ]);
    }

    public function canBeAccepted(): bool
    {
        return $this->status === QuoteStatus::SENT
            && $this->expires_at
            && $this->expires_at->isFuture();
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
        return $this->user?->phone_number ?? $this->guest_info['phone'] ?? '';
    }

    public function isGuest(): bool
    {
        return is_null($this->user_id);
    }

    // =====================================================
    // Reference generator — QT-2026-000001
    // =====================================================

    public static function generateReference(): string
    {
        $year = now()->year;
        $count = static::whereYear('created_at', $year)->count();

        return sprintf('QT-%d-%06d', $year, $count + 1);
    }

    // =====================================================
    // Status transition
    // =====================================================

    public function transitionTo(QuoteStatus $new, ?string $notes = null, string $changedByType = 'system'): void
    {
        if (! $this->status->canTransitionTo($new)) {
            throw new \RuntimeException("Cannot transition quote from {$this->status->value} to {$new->value}");
        }

        $old = $this->status;

        $this->update(['status' => $new]);

        $this->statusHistories()->create([
            'from_status' => $old->value,
            'to_status' => $new->value,
            'changed_by_user_id' => auth()->id(),
            'changed_by_type' => $changedByType,
            'notes' => $notes,
        ]);

        // Broadcast real-time update to customer
        QuoteUpdated::dispatch($this, 'status');
    }

    // =====================================================
    // Recalculate totals
    // =====================================================

    public function recalculateTotals(): void
    {
        $subtotal = $this->items->sum(function ($item) {
            $price = $item->quoted_price_cents ?? $item->original_price_cents;

            return $price * $item->quantity;
        });

        $this->update([
            'subtotal_cents' => $subtotal,
            'total_cents' => max(0, $subtotal - $this->discount_cents + $this->shipping_cents),
        ]);
    }

    // =====================================================
    // Activity Log Configuration
    // =====================================================

    protected function getLoggedAttributes(): array
    {
        return ['status', 'customer_notes', 'admin_notes'];
    }
}
