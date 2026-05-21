<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(OrderStatus::cases());
        $createdAt = $this->faker->dateTimeBetween('-60 days', 'now');
        $county = $this->faker->randomElement(['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret']);

        return [
            'user_id' => User::where('is_staff', false)->inRandomOrder()->first()?->id ?? User::factory(),
            'quote_id' => null,
            'reference' => Order::generateReference(),
            'invoice_path' => null,
            'status' => $status->value,
            'payment_status' => PaymentStatus::PENDING->value,
            'currency' => 'KES',
            'subtotal_cents' => 0,
            'discount_cents' => 0,
            'shipping_cents' => $this->faker->numberBetween(50000, 200000),
            'tax_cents' => 0,
            'total_cents' => 0,
            'shipping_address' => [
                'full_name' => $this->faker->name(),
                'phone_number' => $this->faker->phoneNumber(),
                'address' => $this->faker->streetAddress(),
                'county' => $county,
                'area' => $this->faker->streetName(),
            ],
            'billing_address' => [
                'full_name' => $this->faker->name(),
                'phone_number' => $this->faker->phoneNumber(),
                'address' => $this->faker->streetAddress(),
                'county' => $county,
                'area' => $this->faker->streetName(),
            ],
            'shipping_snapshot' => [
                'method' => $this->faker->randomElement(['Standard Delivery', 'Standard Delivery', 'Pickup Station']),
                'zone' => $county,
                'estimated_days' => $this->faker->numberBetween(2, 5),
            ],
            'guest_info' => null,
            'customer_notes' => $this->faker->optional(0.2)->sentence(),
            'preferred_county' => $county,
            'preferred_area' => $this->faker->optional()->streetName(),
            'expires_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    // Status states
    public function pending(): static
    {
        return $this->state([
            'status' => OrderStatus::PENDING->value,
            'payment_status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function processing(): static
    {
        return $this->state([
            'status' => OrderStatus::PROCESSING->value,
            'payment_status' => PaymentStatus::PAID->value,
        ]);
    }

    public function shipped(): static
    {
        return $this->state([
            'status' => OrderStatus::SHIPPED->value,
            'payment_status' => PaymentStatus::PAID->value,
        ]);
    }

    public function delivered(): static
    {
        return $this->state([
            'status' => OrderStatus::DELIVERED->value,
            'payment_status' => PaymentStatus::PAID->value,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => OrderStatus::CANCELLED->value,
            'payment_status' => PaymentStatus::REFUNDED->value,
        ]);
    }

    public function paid(): static
    {
        return $this->state([
            'payment_status' => PaymentStatus::PAID->value,
        ]);
    }

    public function unpaid(): static
    {
        return $this->state([
            'payment_status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function recentDays(int $days = 30): static
    {
        return $this->state(fn () => [
            'created_at' => $this->faker->dateTimeBetween("-{$days} days", 'now'),
        ]);
    }

    /**
     * Create order with items and calculate totals.
     */
    public function withItems(?int $count = null): static
    {
        return $this->afterCreating(function (Order $order) use ($count) {
            $itemCount = $count ?? $this->faker->numberBetween(1, 5);
            $products = Product::active()->with('brand')->inRandomOrder()->take($itemCount)->get();

            $subtotal = 0;

            foreach ($products as $product) {
                $quantity = $this->faker->numberBetween(1, 5);
                $unitPrice = $product->sale_price ?? $product->price;
                $originalPrice = $product->price;
                $unitPriceCents = $unitPrice * 100;
                $lineTotal = $unitPriceCents * $quantity;
                $subtotal += $lineTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'quantity' => $quantity,
                    'unit_price_cents' => (int) $unitPriceCents,
                    'unit_tax_cents' => 0,
                    'discount_cents' => (int) (($originalPrice - $unitPrice) * 100 * $quantity),
                    'total_cents' => (int) $lineTotal,
                    'product_snapshot' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'slug' => $product->slug,
                        'image_path' => $product->image_path,
                        'price' => $originalPrice,
                        'sale_price' => $product->sale_price,
                        'final_price' => $unitPrice,
                        'weight_kg' => $product->weight ?? 0.5,
                        'brand' => $product->brand?->name,
                        'variant' => null,
                    ],
                ]);
            }

            $total = $subtotal - $order->discount_cents + $order->shipping_cents;

            $order->update([
                'subtotal_cents' => (int) $subtotal,
                'total_cents' => (int) $total,
            ]);
        });
    }

    /**
     * Create payment record for the order.
     */
    public function withPayment(): static
    {
        return $this->afterCreating(function (Order $order) {
            if ($order->payment_status === PaymentStatus::PAID) {
                Payment::create([
                    'order_id' => $order->id,
                    'gateway' => $this->faker->randomElement(['mpesa', 'stripe', 'bank_transfer']),
                    'transaction_id' => strtoupper($this->faker->bothify('TXN-########')),
                    'amount_cents' => $order->total_cents,
                    'currency' => $order->currency,
                    'status' => PaymentStatus::PAID,
                    'paid_at' => $this->faker->dateTimeBetween($order->created_at, 'now'),
                    'meta' => [],
                ]);
            }
        });
    }
}
