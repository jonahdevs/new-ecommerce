<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
 *
 * Default polygon: a small square around Nairobi CBD, useful for tests that
 * need a zone without caring about precise boundaries.
 * Use withPolygonContaining() to create a zone that contains a specific point.
 */
class DeliveryZoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'county' => 'Nairobi',
            'is_active' => true,
            'sort_order' => 0,
            'priority' => 0,
            // Default: small square around Nairobi CBD (~5km side).
            'polygon' => self::squareAround(-1.2921, 36.8219, 0.045),
        ];
    }

    /**
     * Build a zone polygon that contains the given point.
     * Creates a square ~radius degrees on each side centred on the point.
     */
    public function containingPoint(float $lat, float $lng, float $margin = 0.045): static
    {
        return $this->state([
            'polygon' => self::squareAround($lat, $lng, $margin),
        ]);
    }

    /**
     * Build a zone centred at the given coordinates (test convenience — matches
     * old centeredAt() API so existing tests need minimal changes).
     * The resulting polygon is a square that roughly matches a circle of the
     * given radius in metres (1 degree ≈ 111 km).
     */
    public function centeredAt(float $lat, float $lng, int $radiusMeters = 5000): static
    {
        $margin = $radiusMeters / 111_000;

        return $this->state([
            'polygon' => self::squareAround($lat, $lng, $margin),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** @return array<int, array{lat: float, lng: float}> */
    private static function squareAround(float $lat, float $lng, float $margin): array
    {
        return [
            ['lat' => $lat + $margin, 'lng' => $lng - $margin], // NW
            ['lat' => $lat + $margin, 'lng' => $lng + $margin], // NE
            ['lat' => $lat - $margin, 'lng' => $lng + $margin], // SE
            ['lat' => $lat - $margin, 'lng' => $lng - $margin], // SW
        ];
    }
}
