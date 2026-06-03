<?php

namespace Database\Factories;

use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => 'VAR-'.fake()->unique()->numerify('#####'),
            'price' => fake()->numberBetween(10000, 1000000),
            'stock_status' => StockStatus::IN_STOCK->value,
            'stock_quantity' => fake()->numberBetween(0, 500),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
