<?php

namespace App\Models;

use App\Enums\DeliveryPromotionEffect;
use App\Enums\DeliveryPromotionScope;
use Database\Factories\DeliveryPromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'is_active', 'priority', 'scope', 'zone_id', 'effect', 'value_cents', 'percent', 'min_subtotal_cents', 'starts_at', 'ends_at'])]
class DeliveryPromotion extends Model
{
    /** @use HasFactory<DeliveryPromotionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'scope' => DeliveryPromotionScope::class,
            'effect' => DeliveryPromotionEffect::class,
            'value_cents' => 'integer',
            'percent' => 'integer',
            'min_subtotal_cents' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }

    /**
     * Whether this promotion is enabled and within its active time window.
     */
    public function isLiveNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Whether this live promotion applies to the given zone and order subtotal.
     */
    public function appliesTo(DeliveryZone $zone, int $subtotalCents): bool
    {
        if (! $this->isLiveNow()) {
            return false;
        }

        if ($subtotalCents < $this->min_subtotal_cents) {
            return false;
        }

        if ($this->scope === DeliveryPromotionScope::ZONE && $this->zone_id !== $zone->id) {
            return false;
        }

        return true;
    }

    /**
     * Apply this promotion's effect to a base delivery fee, in cents.
     */
    public function applyTo(int $baseFeeCents): int
    {
        return match ($this->effect) {
            DeliveryPromotionEffect::FREE => 0,
            DeliveryPromotionEffect::FLAT_FEE => max(0, (int) $this->value_cents),
            DeliveryPromotionEffect::PERCENT_OFF => (int) round($baseFeeCents * (100 - (int) $this->percent) / 100),
        };
    }
}
