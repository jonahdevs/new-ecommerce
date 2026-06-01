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
            'is_hq' => false,
            'sort_order' => 0,
        ];
    }

    public function headquarters(): static
    {
        return $this->state(['is_hq' => true, 'sort_order' => 0]);
    }
}
