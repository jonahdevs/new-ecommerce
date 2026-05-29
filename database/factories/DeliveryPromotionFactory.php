<?php

namespace Database\Factories;

use App\Enums\DeliveryPromotionEffect;
use App\Enums\DeliveryPromotionScope;
use App\Models\DeliveryPromotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryPromotion>
 */
class DeliveryPromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Free delivery',
            'is_active' => true,
            'priority' => 0,
            'scope' => DeliveryPromotionScope::GLOBAL,
            'zone_id' => null,
            'effect' => DeliveryPromotionEffect::FREE,
            'value_cents' => null,
            'percent' => null,
            'min_subtotal_cents' => 0,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function flatFee(int $cents): static
    {
        return $this->state([
            'effect' => DeliveryPromotionEffect::FLAT_FEE,
            'value_cents' => $cents,
        ]);
    }

    public function percentOff(int $percent): static
    {
        return $this->state([
            'effect' => DeliveryPromotionEffect::PERCENT_OFF,
            'percent' => $percent,
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }
}
