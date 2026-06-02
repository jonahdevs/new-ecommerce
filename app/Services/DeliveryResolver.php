<?php

namespace App\Services;

use App\Models\DeliveryPromotion;
use App\Models\DeliveryZone;
use App\Models\ShippingMethod;
use Illuminate\Support\Collection;

/**
 * Single source of truth for delivery serviceability and pricing.
 *
 * Flow:
 *   1. resolveZone()   — point-in-polygon check across all active zones.
 *                        The highest-priority (then first) matching zone wins.
 *   2. quote()         — given a zone, find the highest-priority carrier covering
 *                        it, then resolve the rate for the requested method via
 *                        carrier_rates. Promotions are applied on top.
 *   3. quoteForPin()   — convenience wrapper: resolve + quote in one call.
 *
 * When no zone contains the address the result is unserviceable (delivery).
 * Pickup is always available regardless of zone — handled by the checkout layer.
 */
class DeliveryResolver
{
    /**
     * The active zone whose polygon contains the pin.
     * When zones overlap, the highest priority (then lowest id) wins.
     * Returns null when the pin is outside every zone.
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
            ->sortByDesc('priority')
            ->first();
    }

    /**
     * Price delivery for a resolved zone, method and cart subtotal.
     *
     * Finds the highest-priority active carrier that:
     *   a) covers the zone (carrier_zones)
     *   b) has an active rate for the requested method (carrier_rates)
     *
     * Then applies the best live promotion on top.
     */
    public function quote(?DeliveryZone $zone, ShippingMethod $method, int $subtotalCents): DeliveryQuoteResult
    {
        if (! $zone instanceof DeliveryZone) {
            return DeliveryQuoteResult::unserviceable();
        }

        // Find the best carrier for this zone + method combination.
        $rate = $zone->carrierRates()
            ->whereHas('carrier', fn ($q) => $q->where('is_active', true)
                ->whereHas('carrierZones', fn ($cz) => $cz
                    ->where('delivery_zone_id', $zone->id)
                    ->where('is_active', true)))
            ->where('shipping_method_id', $method->id)
            ->where('is_active', true)
            ->with('carrier')
            ->get()
            ->sortByDesc(fn ($r) => $r->carrier->priority)
            ->first();

        if (! $rate) {
            return DeliveryQuoteResult::unserviceable();
        }

        $baseFeeCents = $rate->calculateFee($subtotalCents);

        $bestPromotion = $this->bestPromotionFor($zone, $subtotalCents, $baseFeeCents);

        $feeCents = $bestPromotion !== null
            ? $bestPromotion->applyTo($baseFeeCents)
            : $baseFeeCents;

        return new DeliveryQuoteResult(
            serviceable: true,
            feeCents: $feeCents,
            isFree: $feeCents === 0,
            zone: $zone,
            carrier: $rate->carrier,
            promotionName: $feeCents < $baseFeeCents ? $bestPromotion?->name : null,
            etaLabel: $rate->eta_label,
        );
    }

    /**
     * Resolve + price for a specific method in one call.
     */
    public function quoteForPin(
        ?float $latitude,
        ?float $longitude,
        ShippingMethod $method,
        int $subtotalCents,
    ): DeliveryQuoteResult {
        return $this->quote($this->resolveZone($latitude, $longitude), $method, $subtotalCents);
    }

    /**
     * Auto-select the first available delivery method for the resolved zone.
     * Used by checkout before the customer has explicitly chosen a method.
     * Returns unserviceable when no carrier covers the address.
     */
    public function quoteDefault(?float $latitude, ?float $longitude, int $subtotalCents): DeliveryQuoteResult
    {
        $zone = $this->resolveZone($latitude, $longitude);

        if (! $zone) {
            return DeliveryQuoteResult::unserviceable();
        }

        // Pick the first active delivery method available for this zone,
        // ordered by sort_order ascending so "Standard" comes before "Express".
        $rate = $zone->carrierRates()
            ->where('is_active', true)
            ->whereHas('carrier', fn ($q) => $q->where('is_active', true)
                ->whereHas('carrierZones', fn ($cz) => $cz
                    ->where('delivery_zone_id', $zone->id)
                    ->where('is_active', true)))
            ->whereHas('shippingMethod', fn ($q) => $q->where('type', 'delivery')->where('is_active', true))
            ->with(['carrier', 'shippingMethod'])
            ->orderBy('sort_order')
            ->first();

        if (! $rate) {
            return DeliveryQuoteResult::unserviceable();
        }

        return $this->quote($zone, $rate->shippingMethod, $subtotalCents);
    }

    private function bestPromotionFor(DeliveryZone $zone, int $subtotalCents, int $baseFeeCents): ?DeliveryPromotion
    {
        return $this->applicablePromotions($zone, $subtotalCents)
            ->sortBy([
                fn (DeliveryPromotion $p) => $p->applyTo($baseFeeCents),
                fn (DeliveryPromotion $p) => -$p->priority,
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
                    ->orWhere(fn ($inner) => $inner
                        ->where('scope', 'zone')
                        ->where('zone_id', $zone->id));
            })
            ->get()
            ->filter(fn (DeliveryPromotion $p) => $p->appliesTo($zone, $subtotalCents))
            ->values();
    }
}
