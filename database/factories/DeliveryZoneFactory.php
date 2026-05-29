<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
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
            // Centred on Nairobi by default.
            'center_lat' => -1.2921,
            'center_lng' => 36.8219,
            'radius_meters' => 5000,
            'base_fee_cents' => fake()->numberBetween(2, 15) * 100000,
            'free_over_cents' => null,
            'eta_label' => fake()->randomElement(['Same day', '1–2 days', 'Next day']),
        ];
    }

    public function centeredAt(float $lat, float $lng, int $radiusMeters = 5000): static
    {
        return $this->state([
            'center_lat' => $lat,
            'center_lng' => $lng,
            'radius_meters' => $radiusMeters,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
