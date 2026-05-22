<?php

namespace App\Services;

use App\Models\Town;
use App\Models\TownBoundary;

/**
 * Resolves a latitude/longitude coordinate to the matching Town (ADM3 ward/locality)
 * using a bounding-box pre-filter followed by ray-casting point-in-polygon.
 *
 * Usage:
 *   $town = app(TownResolver::class)->resolve($lat, $lng);
 */
class TownResolver
{
    /**
     * Find the Town whose polygon contains the given coordinate.
     * Returns null if the point falls outside all known town boundaries.
     */
    public function resolve(float $lat, float $lng): ?Town
    {
        $candidates = TownBoundary::query()
            ->where('bbox_min_lat', '<=', $lat)
            ->where('bbox_max_lat', '>=', $lat)
            ->where('bbox_min_lng', '<=', $lng)
            ->where('bbox_max_lng', '>=', $lng)
            ->with('town.subCounty.shippingZone', 'town.county.shippingZone', 'town.shippingZone')
            ->get();

        foreach ($candidates as $boundary) {
            $geometry = json_decode($boundary->geojson, true);

            if ($geometry && $this->pointInPolygon($lat, $lng, $geometry)) {
                return $boundary->town;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $geometry
     */
    private function pointInPolygon(float $lat, float $lng, array $geometry): bool
    {
        $type = $geometry['type'] ?? '';
        $rings = [];

        if ($type === 'Polygon') {
            $rings = [$geometry['coordinates'][0] ?? []];
        } elseif ($type === 'MultiPolygon') {
            foreach ($geometry['coordinates'] as $polygon) {
                $rings[] = $polygon[0] ?? [];
            }
        }

        foreach ($rings as $ring) {
            if ($this->rayCast($lat, $lng, $ring)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<array{float, float}>  $ring
     */
    private function rayCast(float $lat, float $lng, array $ring): bool
    {
        $inside = false;
        $n = count($ring);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = [$ring[$i][0], $ring[$i][1]]; // [lng, lat]
            [$xj, $yj] = [$ring[$j][0], $ring[$j][1]];

            $intersects = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
