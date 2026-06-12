<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected static array $nairobiAreas = [
        'Westlands', 'Karen', 'Kilimani', 'Lavington', 'Parklands',
        'Upperhill', 'Gigiri', 'Runda', 'Muthaiga', 'Spring Valley',
    ];

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Home', 'Work', 'Other']),
            'name' => fake()->name(),
            'phone' => '+254'.fake()->numerify('7########'),
            'alternative_phone' => fake()->optional()->phoneNumber(),
            'line1' => fake()->randomElement(self::$nairobiAreas).', '.fake()->buildingNumber().' '.fake()->streetName(),
            'delivery_instructions' => fake()->optional()->sentence(),
            'is_default' => false,
            // Jittered around central Nairobi so addresses fall within seeded zones.
            'latitude' => fake()->randomFloat(7, -1.33, -1.25),
            'longitude' => fake()->randomFloat(7, 36.76, 36.86),
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
