<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'sku' => 'SKU-'.fake()->unique()->numerify('#####'),
            'type' => ProductType::SIMPLE->value,
            'price' => fake()->numberBetween(50000, 5000000) * 100,
            'stock_status' => StockStatus::IN_STOCK->value,
            'visibility' => ProductVisibility::VISIBLE->value,
        ];
    }
}
