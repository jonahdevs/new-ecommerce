<?php

namespace App\Models;

use App\Console\Commands\ExpireQuotes;
use App\Enums\QuoteStatus;
use App\Models\Concerns\HasStatusHistory;
use App\Settings\QuotationSettings;
use App\Support\NumberSequence;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notification as NotificationInstance;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['user_id', 'order_id', 'contact_name', 'contact_email', 'contact_phone', 'contact_company', 'quote_number', 'status', 'subtotal_cents', 'vat_cents', 'vat_rate', 'tax_inclusive', 'shipping_cents', 'discount_type', 'discount_value', 'discount_cents', 'total_cents', 'notes', 'internal_notes', 'terms', 'document_path', 'delivery_required', 'delivery_address', 'expires_at'])]
class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory, HasStatusHistory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_cents', 'expires_at', 'notes', 'terms'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('quote');
    }

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'expires_at' => 'datetime',
            'vat_rate' => 'float',
            'tax_inclusive' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Quote $quote): void {
            $quote->recordStatusChange(null, QuoteStatus::DRAFT);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    /**
     * Whether this quote has a valid, customer-facing price. A fresh request is
     * a draft with a zero total until staff prepare the formal quotation, so no
     * price should be shown or trusted until then.
     */
    public function isPriced(): bool
    {
        return $this->status !== QuoteStatus::DRAFT && $this->total_cents > 0;
    }

    /**
     * Whether the quote's validity window has lapsed. Pricing on a lapsed quote
     * is stale and must not be approved, even if a stale browser tab or an old
     * email link still surfaces the approve action. EXPIRED quotes (flipped by
     * the {@see ExpireQuotes} command) always count.
     */
    public function hasExpired(): bool
    {
        if ($this->status === QuoteStatus::EXPIRED) {
            return true;
        }

        return in_array($this->status, [QuoteStatus::SENT, QuoteStatus::AWAITING_APPROVAL], true)
            && $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    /**
     * Whether the customer can still approve this quote — it is awaiting their
     * decision and its validity window has not lapsed.
     */
    public function isApprovable(): bool
    {
        return $this->status === QuoteStatus::AWAITING_APPROVAL && ! $this->hasExpired();
    }

    /**
     * Send a customer-facing notification to whoever owns the quote: the
     * registered user when present, otherwise the guest contact email.
     */
    public function notifyContact(NotificationInstance $notification): void
    {
        if ($this->user) {
            $this->user->notify($notification);

            return;
        }

        if ($this->contact_email) {
            Notification::route('mail', $this->contact_email)->notify($notification);
        }
    }

    /**
     * Next quote number, formatted {prefix}{year}-{sequence} (e.g. RFQ-2026-00001).
     * The prefix comes from {@see QuotationSettings}.
     */
    public static function generateNumber(): string
    {
        $prefix = app(QuotationSettings::class)->quote_prefix;
        $year = now()->year;
        $sequence = NumberSequence::next("quote:{$year}");

        return $prefix.$year.'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
