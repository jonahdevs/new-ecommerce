<?php

namespace Database\Factories;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subscriber>
 */
class SubscriberFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'interests' => fake()->randomElements(['new-products', 'seasonal-catalogs', 'trade-pricing', 'projects'], 2),
            'token' => Str::random(64),
            'source' => '/',
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['subscribed_at' => now(), 'unsubscribed_at' => null]);
    }

    public function pending(): static
    {
        return $this->state(['subscribed_at' => null, 'unsubscribed_at' => null]);
    }

    public function unsubscribed(): static
    {
        return $this->state(['subscribed_at' => now()->subDays(30), 'unsubscribed_at' => now()]);
    }
}
