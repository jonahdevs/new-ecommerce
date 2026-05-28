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
            'label' => fake()->randomElement(['Home', 'Office', 'Warehouse', 'Site']),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '+254'.fake()->numerify('7########'),
            'line1' => fake()->buildingNumber().' '.fake()->streetName(),
            'line2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->randomElement(self::$nairobiAreas).', Nairobi',
            'postal_code' => fake()->numerify('#####'),
            'country' => 'KE',
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
