<?php

namespace App\Services;

use App\Models\DeliveryPromotion;
use App\Models\DeliveryZone;
use Illuminate\Support\Collection;

/**
 * Single source of truth for delivery serviceability and pricing. Resolves a
 * map pin to a circular zone, then prices it through the promotion matrix.
 * Used by both checkout and the address form so the rules never drift.
 */
class DeliveryResolver
{
    /**
     * The active zone whose circle contains the pin. When zones overlap the
     * highest priority (then smallest radius) wins. Null = not serviceable.
     */
    public function resolveZone(?float $latitude, ?float $longitude): ?DeliveryZone
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return DeliveryZone::query()
            ->active()
            ->get()
            ->filter(fn (DeliveryZone $zone) => $zone->containsPoint($latitude, $longitude))
            ->sortBy([
                ['priority', 'desc'],
                ['radius_meters', 'asc'],
            ])
            ->first();
    }

    /**
     * Price delivery for a resolved zone and order subtotal. Starts from the
     * zone base fee, then applies the single best live promotion (lowest
     * resulting fee, tie-broken by priority). Honors the free-over threshold.
     */
    public function quote(?DeliveryZone $zone, int $subtotalCents): DeliveryQuoteResult
    {
        if (! $zone instanceof DeliveryZone) {
            return DeliveryQuoteResult::unserviceable();
        }

        $baseFeeCents = $zone->base_fee_cents;

        if ($zone->free_over_cents !== null && $subtotalCents >= $zone->free_over_cents) {
            $baseFeeCents = 0;
        }

        $bestPromotion = $this->bestPromotionFor($zone, $subtotalCents, $baseFeeCents);

        $feeCents = $bestPromotion !== null
            ? $bestPromotion->applyTo($baseFeeCents)
            : $baseFeeCents;

        return new DeliveryQuoteResult(
            serviceable: true,
            feeCents: $feeCents,
            isFree: $feeCents === 0,
            zone: $zone,
            promotionName: $feeCents < $baseFeeCents ? $bestPromotion?->name : null,
            etaLabel: $zone->eta_label,
        );
    }

    /**
     * Convenience: resolve the pin and price it in one call.
     */
    public function quoteForPin(?float $latitude, ?float $longitude, int $subtotalCents): DeliveryQuoteResult
    {
        return $this->quote($this->resolveZone($latitude, $longitude), $subtotalCents);
    }

    private function bestPromotionFor(DeliveryZone $zone, int $subtotalCents, int $baseFeeCents): ?DeliveryPromotion
    {
        return $this->applicablePromotions($zone, $subtotalCents)
            ->sortBy([
                fn (DeliveryPromotion $promotion) => $promotion->applyTo($baseFeeCents),
                fn (DeliveryPromotion $promotion) => -$promotion->priority,
            ])
            ->first();
    }

    /**
     * @return Collection<int, DeliveryPromotion>
     */
    private function applicablePromotions(DeliveryZone $zone, int $subtotalCents): Collection
    {
        return DeliveryPromotion::query()
            ->where('is_active', true)
            ->where(function ($query) use ($zone) {
                $query->where('scope', 'global')
                    ->orWhere(function ($inner) use ($zone) {
                        $inner->where('scope', 'zone')->where('zone_id', $zone->id);
                    });
            })
            ->get()
            ->filter(fn (DeliveryPromotion $promotion) => $promotion->appliesTo($zone, $subtotalCents))
            ->values();
    }
}
