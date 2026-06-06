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
            'currency' => 'KES',
            'phone' => '2547'.fake()->numerify('########'),
            'account_reference' => 'SHF-'.now()->year.'-'.fake()->numerify('#####'),
            'merchant_request_id' => fake()->uuid(),
            'checkout_request_id' => 'ws_CO_'.fake()->numerify('############'),
        ];
    }

    public function stripe(): static
    {
        return $this->state([
            'provider' => 'stripe',
            'phone' => null,
            'merchant_request_id' => null,
            'checkout_request_id' => null,
            'stripe_payment_intent_id' => 'pi_test_'.fake()->bothify('??????????'),
        ]);
    }

    public function successful(): static
    {
        return $this->state(function (array $attributes) {
            $isStripe = ($attributes['provider'] ?? 'mpesa') === 'stripe';

            return array_filter([
                'status' => PaymentStatus::SUCCESS,
                'paid_at' => now(),
                // M-Pesa success fields
                'mpesa_receipt' => $isStripe ? null : strtoupper(fake()->bothify('???#####??')),
                'result_code' => $isStripe ? null : 0,
                'result_desc' => $isStripe ? null : 'The service request is processed successfully.',
                // Stripe success fields
                'stripe_charge_id' => $isStripe ? 'ch_test_'.fake()->bothify('??????????') : null,
                'card_brand' => $isStripe ? 'visa' : null,
                'card_last4' => $isStripe ? '4242' : null,
            ], fn ($v) => $v !== null);
        });
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
