<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(50000, 5000000) * 100;
        $quantity = fake()->numberBetween(1, 4);

        return [
            'quote_id' => Quote::factory(),
            'product_id' => null,
            'product_snapshot' => [
                'name' => fake()->words(3, true),
                'sku' => 'SKU-'.fake()->unique()->numberBetween(1000, 99999),
                'model_number' => null,
            ],
            'unit_price_cents' => $unitPrice,
            'quantity' => $quantity,
            'line_total_cents' => $unitPrice * $quantity,
        ];
    }

    /**
     * Link the line item to a concrete product, snapshotting its details.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn () => [
            'product_id' => $product->id,
            'product_snapshot' => [
                'name' => $product->name,
                'sku' => $product->sku,
                'model_number' => $product->model_number,
            ],
        ]);
    }
}
