<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(50000, 5000000) * 100;
        $vat = (int) round($subtotal * 0.16);
        $delivery = $subtotal > 50000000 ? 0 : 120000;
        $installation = fake()->boolean(40) ? (int) round($subtotal * 0.04) : 0;

        return [
            'user_id' => User::factory(),
            'order_number' => 'SHF-'.now()->year.'-'.str_pad((string) fake()->numberBetween(1000, 99999), 5, '0', STR_PAD_LEFT),
            'status' => fake()->randomElement(OrderStatus::cases()),
            'subtotal_cents' => $subtotal,
            'vat_cents' => $vat,
            'delivery_cents' => $delivery,
            'installation_cents' => $installation,
            'total_cents' => $subtotal + $vat + $delivery + $installation,
            'payment_method' => fake()->randomElement(['mpesa', 'card', 'bank_transfer', 'net_30']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function outForDelivery(): static
    {
        return $this->state(['status' => OrderStatus::OUT_FOR_DELIVERY]);
    }

    public function completed(): static
    {
        return $this->state(['status' => OrderStatus::COMPLETED]);
    }
}
