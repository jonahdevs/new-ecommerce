<?php

namespace App\Services;

/**
 * Resolves a coordinate to its Kenyan county entirely offline, by testing the
 * point against the ADM1 county boundaries in public/maps/kenya-counties.geojson
 * (geoBoundaries, 47 counties). No external geocoding API — deterministic, free,
 * and the same dataset powers the choropleth map. The parsed boundaries are
 * memoised per process.
 */
class CountyResolver
{
    private const FILE = 'maps/kenya-counties.geojson';

    /** @var array<int, array{name: string, rings: array<int, array<int, array{0: float, 1: float}>>}>|null */
    private static ?array $counties = null;

    public function countyFor(?float $latitude, ?float $longitude): ?string
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        foreach ($this->counties() as $county) {
            foreach ($county['rings'] as $ring) {
                if ($this->pointInRing($longitude, $latitude, $ring)) {
                    return $county['name'];
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, array{name: string, rings: array}>
     */
    private function counties(): array
    {
        if (self::$counties !== null) {
            return self::$counties;
        }

        $path = public_path(self::FILE);

        if (! is_file($path)) {
            return self::$counties = [];
        }

        $geojson = json_decode((string) file_get_contents($path), true);
        $counties = [];

        foreach ($geojson['features'] ?? [] as $feature) {
            $name = $feature['properties']['shapeName'] ?? null;
            $geometry = $feature['geometry'] ?? [];

            if ($name === null || empty($geometry['coordinates'])) {
                continue;
            }

            // Outer rings only — county boundaries don't have meaningful holes,
            // and a MultiPolygon's islands each contribute their own outer ring.
            $rings = match ($geometry['type'] ?? '') {
                'Polygon' => [$geometry['coordinates'][0]],
                'MultiPolygon' => array_map(fn ($polygon) => $polygon[0], $geometry['coordinates']),
                default => [],
            };

            if ($rings !== []) {
                $counties[] = ['name' => $name, 'rings' => $rings];
            }
        }

        return self::$counties = $counties;
    }

    /**
     * Standard ray-casting point-in-polygon. Ring vertices are [lng, lat], so
     * $x is the longitude and $y the latitude.
     *
     * @param  array<int, array{0: float, 1: float}>  $ring
     */
    private function pointInRing(float $x, float $y, array $ring): bool
    {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = $ring[$i][0];
            $yi = $ring[$i][1];
            $xj = $ring[$j][0];
            $yj = $ring[$j][1];

            if ((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
