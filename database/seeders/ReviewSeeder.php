<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()->inRandomOrder()->take(8)->get();

        if ($products->isEmpty()) {
            return;
        }

        $customer = User::where('email', 'customer@sheffield.test')->first();

        $products->each(function (Product $product) use ($customer) {
            Review::factory()
                ->count(fake()->numberBetween(1, 4))
                ->approved()
                ->create([
                    'product_id' => $product->id,
                    'user_id' => $customer?->id,
                ]);

            if (fake()->boolean(40)) {
                Review::factory()->create([
                    'product_id' => $product->id,
                    'user_id' => $customer?->id,
                ]);
            }
        });
    }
}
