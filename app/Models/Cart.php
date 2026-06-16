<?php

namespace App\Models;

use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A persisted shopping cart. One row per user (and optionally per guest, keyed
 * by a cookie token). Mirrors the session cart so items survive across sessions
 * and devices, and so abandoned-cart flows have something durable to act on.
 */
#[Fillable(['user_id', 'token', 'last_activity_at', 'reminders_sent', 'last_reminded_at', 'recovered_at'])]
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'reminders_sent' => 'integer',
            'last_reminded_at' => 'datetime',
            'recovered_at' => 'datetime',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // ==================================================
    // HELPERS
    // ==================================================

    /**
     * Stamp the cart as freshly touched, for idle/abandoned detection. If the
     * customer re-engages after a reminder was sent, count it recovered and
     * reset the reminder cycle so a fresh abandonment can be reminded again.
     */
    public function markActive(): void
    {
        $attributes = ['last_activity_at' => now()];

        if ($this->reminders_sent > 0) {
            $attributes['recovered_at'] = now();
            $attributes['reminders_sent'] = 0;
            $attributes['last_reminded_at'] = null;
        }

        $this->forceFill($attributes)->save();
    }

    /**
     * Cart subtotal in cents, using the same price resolution as the live
     * session cart (variant price when a variant was chosen, else the product's
     * sale/base price). Requires items.product (and items.variant) to be loaded.
     */
    public function subtotalCents(): int
    {
        return (int) $this->items->sum(function (CartItem $item): int {
            $unit = $item->variant
                ? (int) ($item->variant->compare_at_price ?? $item->variant->price ?? 0)
                : (int) ($item->product?->sale_price ?? $item->product?->price ?? 0);

            return $unit * $item->quantity;
        });
    }
}
