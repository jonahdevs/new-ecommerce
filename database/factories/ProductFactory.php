<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);
        
        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'type' => ProductType::SIMPLE->value,
            'status' => ProductStatus::PUBLISHED->value,
            'visibility' => ProductVisibility::PUBLIC->value,
            'price' => fake()->randomFloat(2, 10, 1000),
            'sku' => 'SKU-' . strtoupper(fake()->unique()->bothify('???-####')),
            'manage_stock' => true,
            'stock_quantity' => fake()->numberBetween(0, 100),
            'stock_status' => 'in_stock',
            'low_stock_threshold' => 10,
            'sort_order' => 0,
            'min_order_quantity' => 1,
            'requires_quotation' => false,
            'allow_backorder' => 'no',
            'sold_individually' => false,
            'is_virtual' => false,
            'is_downloadable' => false,
            'reviews_enabled' => true,
            'published_at' => now(),
            'image_path' => 'products/default.jpg',
        ];
    }

    public function simple(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::SIMPLE->value,
        ]);
    }

    public function variable(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::VARIABLE->value,
        ]);
    }

    public function grouped(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::GROUPED->value,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::DRAFT->value,
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::PUBLISHED->value,
            'published_at' => now(),
        ]);
    }
}
