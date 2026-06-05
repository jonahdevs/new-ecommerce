<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    protected static array $kitchens = [
        'Westlands kitchen build',
        'Hotel restaurant fit-out',
        'Hospital canteen equipment',
        'School kitchen refurbishment',
        'Combi oven + installation',
        'Bakery equipment package',
        'Coffee shop setup',
        'Cold room installation',
    ];

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quote_number' => 'RFQ-'.now()->year.'-'.str_pad((string) fake()->numberBetween(1000, 99999), 5, '0', STR_PAD_LEFT),
            'status' => fake()->randomElement(QuoteStatus::cases()),
            'total_cents' => fake()->numberBetween(100000, 10000000) * 100,
            'notes' => fake()->optional()->paragraph(),
            'expires_at' => fake()->optional(0.7)->dateTimeBetween('now', '+60 days'),
        ];
    }

    public function awaitingApproval(): static
    {
        return $this->state(['status' => QuoteStatus::AWAITING_APPROVAL]);
    }
}
