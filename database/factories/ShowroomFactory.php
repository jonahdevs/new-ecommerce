<?php

namespace Database\Factories;

use App\Models\Showroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Showroom>
 */
class ShowroomFactory extends Factory
{
    public function definition(): array
    {
        $city = fake()->city();

        return [
            'city' => $city,
            'country' => fake()->country(),
            'address' => fake()->streetAddress(),
            'pobox' => null,
            'phones' => [fake()->phoneNumber()],
            'email' => fake()->companyEmail(),
            'whatsapp' => fake()->phoneNumber(),
            'hours' => 'Mon–Fri · 8:00 – 17:30 · Sat · 9:00 – 14:00',
            'services' => ['Showroom', 'Service & Spares'],
            'latitude' => fake()->latitude(-5, 5),
            'longitude' => fake()->longitude(28, 42),
            'is_hq' => false,
            'sort_order' => 0,
        ];
    }

    public function headquarters(): static
    {
        return $this->state(['is_hq' => true, 'sort_order' => 0]);
    }
}
