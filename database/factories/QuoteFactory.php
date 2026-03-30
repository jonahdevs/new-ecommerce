<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(QuoteStatus::cases());
        $createdAt = $this->faker->dateTimeBetween('-60 days', 'now');
        
        return [
            'user_id' => User::where('is_staff', false)->inRandomOrder()->first()?->id ?? User::factory(),
            'reference' => Quote::generateReference(),
            'status' => $status->value,
            'currency' => 'KES',
            'subtotal_cents' => 0,
            'discount_cents' => 0,
            'shipping_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'preferred_county' => $this->faker->randomElement(['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret']),
            'preferred_area' => $this->faker->optional()->streetName(),
            'customer_notes' => $this->faker->optional(0.3)->sentence(),
            'admin_notes' => null,
            'quoted_at' => null,
            'expires_at' => null,
            'reminder_sent_at' => null,
            'accepted_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'document_path' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    // Status states
    public function pending(): static
    {
        return $this->state([
            'status' => QuoteStatus::PENDING->value,
            'quoted_at' => null,
            'expires_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(function (array $attributes) {
            $quotedAt = $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            $expiresAt = (clone $quotedAt)->modify('+7 days');
            
            return [
                'status' => QuoteStatus::SENT->value,
                'quoted_at' => $quotedAt,
                'expires_at' => $expiresAt,
                'shipping_cents' => $this->faker->numberBetween(50000, 200000),
            ];
        });
    }

    public function accepted(): static
    {
        return $this->state(function (array $attributes) {
            $quotedAt = $this->faker->dateTimeBetween($attributes['created_at'], '-2 days');
            $expiresAt = (clone $quotedAt)->modify('+7 days');
            $acceptedAt = $this->faker->dateTimeBetween($quotedAt, min($expiresAt, new \DateTime()));
            
            return [
                'status' => QuoteStatus::ACCEPTED->value,
                'quoted_at' => $quotedAt,
                'expires_at' => $expiresAt,
                'accepted_at' => $acceptedAt,
                'shipping_cents' => $this->faker->numberBetween(50000, 200000),
            ];
        });
    }

    public function rejected(): static
    {
        return $this->state(function (array $attributes) {
            $quotedAt = $this->faker->dateTimeBetween($attributes['created_at'], '-2 days');
            $expiresAt = (clone $quotedAt)->modify('+7 days');
            $rejectedAt = $this->faker->dateTimeBetween($quotedAt, min($expiresAt, new \DateTime()));
            
            return [
                'status' => QuoteStatus::REJECTED->value,
                'quoted_at' => $quotedAt,
                'expires_at' => $expiresAt,
                'rejected_at' => $rejectedAt,
                'rejection_reason' => $this->faker->randomElement([
                    'Price too high',
                    'Found better offer elsewhere',
                    'Project cancelled',
                    'Budget constraints',
                ]),
                'shipping_cents' => $this->faker->numberBetween(50000, 200000),
            ];
        });
    }

    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $quotedAt = $this->faker->dateTimeBetween('-30 days', '-10 days');
            $expiresAt = (clone $quotedAt)->modify('+7 days');
            
            return [
                'status' => QuoteStatus::EXPIRED->value,
                'quoted_at' => $quotedAt,
                'expires_at' => $expiresAt,
                'shipping_cents' => $this->faker->numberBetween(50000, 200000),
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => QuoteStatus::CANCELLED->value,
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(function () {
            $quotedAt = $this->faker->dateTimeBetween('-6 days', '-5 days');
            $expiresAt = (clone $quotedAt)->modify('+7 days');
            
            return [
                'status' => QuoteStatus::SENT->value,
                'quoted_at' => $quotedAt,
                'expires_at' => $expiresAt,
                'shipping_cents' => $this->faker->numberBetween(50000, 200000),
            ];
        });
    }

    public function recentDays(int $days = 30): static
    {
        return $this->state(fn() => [
            'created_at' => $this->faker->dateTimeBetween("-{$days} days", 'now'),
        ]);
    }

    /**
     * Create quote with items and calculate totals.
     */
    public function withItems(int $count = null): static
    {
        return $this->afterCreating(function (Quote $quote) use ($count) {
            $itemCount = $count ?? $this->faker->numberBetween(1, 5);
            $products = Product::active()->inRandomOrder()->take($itemCount)->get();
            
            $subtotal = 0;
            
            foreach ($products as $product) {
                $quantity = $this->faker->numberBetween(1, 10);
                $originalPrice = $product->price * 100;
                $quotedPrice = $quote->status !== QuoteStatus::PENDING 
                    ? $originalPrice * $this->faker->randomFloat(2, 0.85, 1.0) 
                    : null;
                
                $effectivePrice = $quotedPrice ?? $originalPrice;
                $lineTotal = $effectivePrice * $quantity;
                $subtotal += $lineTotal;
                
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'quantity' => $quantity,
                    'original_price_cents' => $originalPrice,
                    'quoted_price_cents' => $quotedPrice ? (int) $quotedPrice : null,
                    'discount_cents' => 0,
                    'total_cents' => (int) $lineTotal,
                    'product_snapshot' => [
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'image_url' => $product->image_url,
                        'brand' => $product->brand?->name,
                    ],
                ]);
            }
            
            $quote->update([
                'subtotal_cents' => (int) $subtotal,
                'total_cents' => (int) ($subtotal - $quote->discount_cents + $quote->shipping_cents),
            ]);
        });
    }
}
