<?php

namespace Database\Seeders;

use App\Enums\QuoteStatus;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'customer@sheffield.test')->firstOrFail();
        $products = Product::inRandomOrder()->take(10)->get();

        if ($products->isEmpty()) {
            return;
        }

        Quote::factory()->awaitingApproval()->create([
            'user_id' => $user->id,
            'total_cents' => 642000000,
        ]);

        Quote::factory()->create([
            'user_id' => $user->id,
            'status' => QuoteStatus::SENT,
            'total_cents' => 164450000,
        ]);

        Quote::factory(2)->create(['user_id' => $user->id])->each(function (Quote $quote) use ($products) {
            $items = $products->random(fake()->numberBetween(1, 4));

            foreach ($items as $product) {
                $qty = fake()->numberBetween(1, 2);
                $price = $product->sale_price ?? $product->price ?? 30000000;

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'product_snapshot' => [
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'model_number' => $product->model_number,
                    ],
                    'unit_price_cents' => $price,
                    'quantity' => $qty,
                    'line_total_cents' => $price * $qty,
                ]);
            }
        });
    }
}
