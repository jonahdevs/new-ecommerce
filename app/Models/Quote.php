<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Settings\QuotationSettings;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'contact_company',
        'quote_number',
        'title',
        'status',
        'total_cents',
        'notes',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
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
