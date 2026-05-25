<?php

namespace App\Services\Shipping;

use App\Models\ShippingZone;

/**
 * Resolves a shipping zone by checking whether a lat/lng point falls inside
 * any zone's custom-drawn polygon boundary.
 *
 * This runs as the highest-priority step in zone resolution — before the
 * admin-boundary hierarchy (town → sub-county → county). It lets admins
 * draw precise, arbitrary zone boundaries that are not constrained by
 * Kenya's administrative divisions.
 */
class ZonePolygonService
{
    /**
     * Find the first active zone whose stored polygon contains the given point.
     *
     * When polygons overlap, the zone with the lowest ID (created earliest)
     * wins. Admins can resolve ambiguity by drawing non-overlapping polygons.
     *
     * Returns null if no zone polygon contains the point, signalling that the
     * caller should fall back to the standard admin-boundary hierarchy.
     */
    public function resolveByCoordinates(float $lat, float $lng): ?ShippingZone
    {
        return ShippingZone::whereNotNull('geometry')
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->first(fn (ShippingZone $zone) => $this->pointInPolygon($lat, $lng, $zone->geometry));
    }

    /**
     * Ray-casting point-in-polygon test.
     *
     * Casts a horizontal ray from the point in the +longitude direction and
     * counts how many polygon edges it crosses. An odd count means the point
     * is inside the polygon.
     *
     * @param  array<int, array{0: float|int, 1: float|int}>  $polygon
     *                                                                  Array of [lat, lng] coordinate pairs (Google Maps path format).
     */
    public function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $n = count($polygon);

        if ($n < 3) {
            return false;
        }

        $inside = false;
        $j = $n - 1;

        for ($i = 0; $i < $n; $i++) {
            $iLat = (float) $polygon[$i][0];
            $iLng = (float) $polygon[$i][1];
            $jLat = (float) $polygon[$j][0];
            $jLng = (float) $polygon[$j][1];

            // Check whether this edge straddles the horizontal line y = $lat.
            // If so, compute the longitude at which the edge crosses that line
            // and test whether it is to the right of the point.
            if (($iLat > $lat) !== ($jLat > $lat)) {
                $crossLng = $iLng + ($lat - $iLat) * ($jLng - $iLng) / ($jLat - $iLat);

                if ($lng < $crossLng) {
                    $inside = ! $inside;
                }
            }

            $j = $i;
        }

        return $inside;
    }
}
