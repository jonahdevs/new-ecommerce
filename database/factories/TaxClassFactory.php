<?php

namespace Database\Factories;

use App\Models\TaxClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxClass>
 */
class TaxClassFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Standard Rate', 'Reduced Rate', 'Zero-Rated', 'Exempt']),
            'rate' => $this->faker->randomElement([16.00, 8.00, 0.00]),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
