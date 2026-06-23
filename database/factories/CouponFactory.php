<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'type' => CouponType::PERCENT,
            'value' => $this->faker->numberBetween(5, 30),
            'min_subtotal_cents' => 0,
            'max_uses' => null,
            'max_uses_per_user' => 1,
            'uses_count' => 0,
            'is_active' => true,
            'description' => null,
            'starts_at' => null,
            'expires_at' => null,
        ];
    }

    public function fixed(int $valueCents = 50000): static
    {
        return $this->state(['type' => CouponType::FIXED, 'value' => $valueCents]);
    }

    public function percent(int $percent = 10): static
    {
        return $this->state(['type' => CouponType::PERCENT, 'value' => $percent]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function notStarted(): static
    {
        return $this->state(['starts_at' => now()->addDay()]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function exhausted(): static
    {
        return $this->state(['max_uses' => 1, 'uses_count' => 1]);
    }
}
