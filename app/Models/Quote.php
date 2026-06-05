<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Settings\QuotationSettings;
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
    use HasFactory, LogsActivity;

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
        $sequence = static::whereYear('created_at', now()->year)->count() + 1;

        return $prefix.now()->year.'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
