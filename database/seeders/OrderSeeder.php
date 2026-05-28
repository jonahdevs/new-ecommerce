<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'customer@sheffield.test')->firstOrFail();
        $products = Product::inRandomOrder()->take(10)->get();

        if ($products->isEmpty()) {
            return;
        }

        Order::factory(6)->create(['user_id' => $user->id])->each(function (Order $order) use ($products) {
            $items = $products->random(fake()->numberBetween(1, 3));

            foreach ($items as $product) {
                $qty = fake()->numberBetween(1, 3);
                $price = $product->sale_price ?? $product->price ?? 50000000;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'unit_price_cents' => $price,
                    'quantity' => $qty,
                    'line_total_cents' => $price * $qty,
                ]);
            }
        });
    }
}
