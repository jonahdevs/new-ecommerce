<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => null,
            'author_name' => fake()->name(),
            'rating' => fake()->numberBetween(3, 5),
            'title' => fake()->optional()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => ReviewStatus::PENDING,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status' => ReviewStatus::APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => ReviewStatus::REJECTED]);
    }
}
