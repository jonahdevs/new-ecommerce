<?php

namespace Database\Factories;

use App\Models\BannedIp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BannedIp>
 */
class BannedIpFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ip_address' => fake()->unique()->ipv4(),
            'comment' => fake()->optional()->sentence(),
            'expires_at' => null,
            'created_by_id' => null,
        ];
    }
}
