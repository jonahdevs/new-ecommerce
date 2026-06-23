<?php

namespace App\Models;

use App\Enums\CouponType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'type', 'value', 'min_subtotal_cents', 'max_uses', 'max_uses_per_user', 'uses_count', 'is_active', 'description', 'starts_at', 'expires_at'])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function uses(): HasMany
    {
        return $this->hasMany(CouponUse::class);
    }

    // ==================================================
    // VALIDATION LOGIC
    // ==================================================

    /**
     * Returns null when valid, or a human-readable error message.
     */
    public function validateFor(int $subtotalCents, ?int $userId = null): ?string
    {
        if (! $this->is_active) {
            return 'This coupon is not active.';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'This coupon is not yet valid.';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'This coupon has expired.';
        }

        if ($subtotalCents < $this->min_subtotal_cents) {
            return 'Your cart does not meet the minimum order value for this coupon.';
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return 'This coupon has reached its usage limit.';
        }

        if ($userId !== null && $this->max_uses_per_user > 0) {
            $userUses = $this->uses()->where('user_id', $userId)->count();
            if ($userUses >= $this->max_uses_per_user) {
                return 'You have already used this coupon.';
            }
        }

        return null;
    }

    /**
     * Calculate the discount in cents for the given subtotal.
     */
    public function discountFor(int $subtotalCents): int
    {
        return match ($this->type) {
            CouponType::FIXED => min((int) $this->value, $subtotalCents),
            CouponType::PERCENT => (int) round($subtotalCents * $this->value / 100),
        };
    }

    /**
     * Formatted value label (e.g., "KES 500 off" or "10% off").
     */
    public function valueLabel(): string
    {
        return match ($this->type) {
            CouponType::FIXED => money((int) $this->value).' off',
            CouponType::PERCENT => $this->value.'% off',
        };
    }
}
