<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'mpesa',
            'status' => PaymentStatus::PENDING,
            'amount_cents' => fake()->numberBetween(1000, 5000000),
            'phone' => '2547'.fake()->numerify('########'),
            'account_reference' => 'SHF-'.now()->year.'-'.fake()->numerify('#####'),
            'merchant_request_id' => fake()->uuid(),
            'checkout_request_id' => 'ws_CO_'.fake()->numerify('############'),
        ];
    }

    public function successful(): static
    {
        return $this->state([
            'status' => PaymentStatus::SUCCESS,
            'mpesa_receipt' => strtoupper(fake()->bothify('???#####??')),
            'result_code' => 0,
            'result_desc' => 'The service request is processed successfully.',
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => PaymentStatus::FAILED,
            'result_code' => 1032,
            'result_desc' => 'Request cancelled by user',
        ]);
    }
}
