<?php

namespace Database\Factories;

use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'type' => 'delivery',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function delivery(): static
    {
        return $this->state(['type' => 'delivery']);
    }

    public function pickup(): static
    {
        return $this->state(['type' => 'pickup']);
    }
}
