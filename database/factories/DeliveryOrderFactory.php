<?php

namespace Database\Factories;

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Models\LogisticsProvider;
use App\Models\PickupStation;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\VehicleRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for DeliveryOrder.
 *
 * Three pricing engines are supported via dedicated state methods:
 *
 *   DeliveryOrder::factory()->flat()->create()
 *   DeliveryOrder::factory()->distance()->create()
 *   DeliveryOrder::factory()->pus()->create()
 *
 * Status helpers:
 *   ->pending()        ->pickedUp()      ->inTransit()
 *   ->outForDelivery() ->delivered()     ->failed()
 *   ->atStation()      ->collected()     ->returning()
 *   ->returned()       ->cancelled()
 *
 * Other helpers:
 *   ->return()         — reverse logistics order
 *   ->overdue()        — at_station with passed collection deadline
 *   ->recentDays(14)   — created within the last N days
 */
class DeliveryOrderFactory extends Factory
{
    protected $model = DeliveryOrder::class;

    // ── Default definition ────────────────────────────────────────────────────

    public function definition(): array
    {
        // Default to a flat rate order — most common type
        $method = ShippingMethod::where('type', 'flat')->where('status', 'active')->inRandomOrder()->first();
        $zone = ShippingZone::where('status', 'active')->inRandomOrder()->first();
        $provider = LogisticsProvider::where('status', 'active')->first();

        $rate = ShippingRate::where('shipping_method_id', $method?->id)
            ->where('shipping_zone_id', $zone?->id)
            ->where('status', 'active')
            ->inRandomOrder()
            ->first();

        $weight = $rate ? $this->weightInTier($rate) : $this->faker->randomFloat(2, 0.5, 30);
        $cost = $rate?->price ?? $this->faker->numberBetween(400, 2700);
        $status = $this->randomForwardStatus();
        $createdAt = $this->faker->dateTimeBetween('-60 days', 'now');

        return [
            'order_id' => $this->faker->unique()->numberBetween(10000, 99999),
            'logistics_provider_id' => $provider?->id ?? 1,
            'shipping_method_id' => $method?->id,
            'shipping_zone_id' => $zone?->id,
            'shipping_rate_id' => $rate?->id,
            'vehicle_rate_id' => null,
            'pickup_station_id' => null,
            'distance_km' => null,
            'package_weight_kg' => $weight,
            'shipping_cost' => $cost,
            'cost_breakdown' => $this->flatBreakdown($zone, $rate, $weight, $cost),
            'is_return' => false,
            'provider_reference' => $this->faker->optional(0.6)->bothify('CSM-####-???'),
            'status' => $status->value,
            'estimated_delivery_at' => $this->estimatedDelivery($createdAt, $rate),
            'delivered_at' => $this->deliveredAt($status, $createdAt),
            'collection_deadline_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    // ── Pricing model states ──────────────────────────────────────────────────

    /**
     * Flat rate delivery (standard / express).
     */
    public function flat(): static
    {
        return $this->state(function () {
            $method = ShippingMethod::where('type', 'flat')
                ->where('status', 'active')
                ->inRandomOrder()->first();

            $zone = ShippingZone::where('status', 'active')->inRandomOrder()->first();

            $rate = ShippingRate::where('shipping_method_id', $method?->id)
                ->where('shipping_zone_id', $zone?->id)
                ->where('status', 'active')
                ->inRandomOrder()->first();

            $weight = $rate ? $this->weightInTier($rate) : $this->faker->randomFloat(2, 0.5, 30);
            $cost = $rate?->price ?? 600;

            return [
                'shipping_method_id' => $method?->id,
                'shipping_zone_id' => $zone?->id,
                'shipping_rate_id' => $rate?->id,
                'vehicle_rate_id' => null,
                'pickup_station_id' => null,
                'distance_km' => null,
                'package_weight_kg' => $weight,
                'shipping_cost' => $cost,
                'cost_breakdown' => $this->flatBreakdown($zone, $rate, $weight, $cost),
            ];
        });
    }

    /**
     * Distance-based (on-demand) delivery.
     */
    public function distance(): static
    {
        return $this->state(function () {
            $method = ShippingMethod::where('type', 'distance')
                ->where('status', 'active')
                ->first();

            $zone = ShippingZone::where('status', 'active')->inRandomOrder()->first();
            $vehicleRate = VehicleRate::where('status', 'active')->inRandomOrder()->first();
            $distanceKm = $this->faker->randomFloat(1, 5, 120);
            $provider = LogisticsProvider::where('status', 'active')->first();

            $breakdown = $vehicleRate
                ? $vehicleRate->buildBreakdown($distanceKm)
                : $this->fallbackDistanceBreakdown($distanceKm);

            $cost = $breakdown['total'];
            $weight = $vehicleRate
                ? $this->faker->randomFloat(2, 1, min(500, $vehicleRate->max_weight_kg ?? 500))
                : $this->faker->randomFloat(2, 1, 200);

            return [
                'logistics_provider_id' => $provider?->id ?? 1,
                'shipping_method_id' => $method?->id,
                'shipping_zone_id' => $zone?->id,
                'shipping_rate_id' => null,
                'vehicle_rate_id' => $vehicleRate?->id,
                'pickup_station_id' => null,
                'distance_km' => $distanceKm,
                'package_weight_kg' => $weight,
                'shipping_cost' => $cost,
                'cost_breakdown' => $breakdown,
            ];
        });
    }

    /**
     * Pickup Station (PUS) delivery.
     */
    public function pus(): static
    {
        return $this->state(function () {
            $method = ShippingMethod::where('type', 'pus')
                ->where('status', 'active')->first();

            $station = PickupStation::where('status', 'active')->inRandomOrder()->first();
            $zone = ShippingZone::where('status', 'active')->inRandomOrder()->first();

            $rate = ShippingRate::where('shipping_method_id', $method?->id)
                ->where('shipping_zone_id', $zone?->id)
                ->where('status', 'active')
                ->inRandomOrder()->first();

            $weight = $rate ? $this->weightInTier($rate) : $this->faker->randomFloat(2, 0.5, 20);
            $lineHaul = $rate?->price ?? 300;
            $surcharge = $this->pusSurcharge($weight);
            $cost = $lineHaul + $surcharge;

            $status = $this->randomPusStatus();
            $createdAt = $this->faker->dateTimeBetween('-30 days', 'now');

            // Collection deadline only meaningful when at station
            $deadline = null;
            if ($status === DeliveryOrderStatus::ATSTATION && $station) {
                $arrivedAt = $this->faker->dateTimeBetween('-6 days', 'now');
                $deadline = (clone $arrivedAt)->modify("+{$station->holding_days} days");
            }

            return [
                'shipping_method_id' => $method?->id,
                'shipping_zone_id' => $zone?->id,
                'shipping_rate_id' => $rate?->id,
                'vehicle_rate_id' => null,
                'pickup_station_id' => $station?->id,
                'distance_km' => null,
                'package_weight_kg' => $weight,
                'shipping_cost' => $cost,
                'cost_breakdown' => $this->pusBreakdown($zone, $rate, $station, $weight, $lineHaul, $surcharge),
                'status' => $status->value,
                'delivered_at' => $status === DeliveryOrderStatus::COLLECTED ? $createdAt : null,
                'collection_deadline_at' => $deadline ? $deadline->format('Y-m-d H:i:s') : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        });
    }

    // ── Status states ─────────────────────────────────────────────────────────

    public function pending(): static
    {
        return $this->state(['status' => DeliveryOrderStatus::PENDING->value, 'delivered_at' => null]);
    }

    public function pickedUp(): static
    {
        return $this->state(['status' => DeliveryOrderStatus::PICKEDUP->value, 'delivered_at' => null]);
    }

    public function inTransit(): static
    {
        return $this->state(['status' => DeliveryOrderStatus::INTRANSIT->value, 'delivered_at' => null]);
    }

    public function outForDelivery(): static
    {
        return $this->state(['status' => DeliveryOrderStatus::OUTFORDELIVERY->value, 'delivered_at' => null]);
    }

    public function delivered(): static
    {
        return $this->state(fn() => [
            'status' => DeliveryOrderStatus::DELIVERED->value,
            'delivered_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function failed(): static
    {
        return $this->state(['status' => DeliveryOrderStatus::FAILED->value, 'delivered_at' => null]);
    }

    public function atStation(): static
    {
        return $this->state(function () {
            $station = PickupStation::where('status', 'active')->inRandomOrder()->first();
            $arrivedAt = $this->faker->dateTimeBetween('-5 days', 'now');
            $deadline = $station
                ? (clone $arrivedAt)->modify("+{$station->holding_days} days")
                : (clone $arrivedAt)->modify('+7 days');

            return [
                'status' => DeliveryOrderStatus::ATSTATION->value,
                'pickup_station_id' => $station?->id,
                'collection_deadline_at' => $deadline->format('Y-m-d H:i:s'),
                'delivered_at' => null,
            ];
        });
    }

    public function collected(): static
    {
        return $this->state(fn() => [
            'status' => DeliveryOrderStatus::COLLECTED->value,
            'delivered_at' => $this->faker->dateTimeBetween('-14 days', 'now'),
        ]);
    }

    public function returning(): static
    {
        return $this->state(['status' => DeliveryOrderStatus::RETURNING->value, 'delivered_at' => null]);
    }

    public function returned(): static
    {
        return $this->state(fn() => [
            'status' => DeliveryOrderStatus::RETURNED->value,
            'delivered_at' => $this->faker->dateTimeBetween('-14 days', 'now'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => DeliveryOrderStatus::CANCELLED->value, 'delivered_at' => null]);
    }

    // ── Other helpers ─────────────────────────────────────────────────────────

    /**
     * Reverse logistics order (customer → seller).
     */
    public function return(): static
    {
        return $this->state(['is_return' => true]);
    }

    /**
     * Parcel at station with an already-passed collection deadline.
     */
    public function overdue(): static
    {
        return $this->state(function () {
            $station = PickupStation::where('status', 'active')->inRandomOrder()->first();
            $arrivedAt = $this->faker->dateTimeBetween('-20 days', '-10 days');
            $deadline = (clone $arrivedAt)->modify('+7 days'); // already in the past

            return [
                'status' => DeliveryOrderStatus::ATSTATION->value,
                'pickup_station_id' => $station?->id,
                'collection_deadline_at' => $deadline->format('Y-m-d H:i:s'),
                'delivered_at' => null,
            ];
        });
    }

    /**
     * Spread created_at over the last N days for realistic charts.
     */
    public function recentDays(int $days = 30): static
    {
        return $this->state(fn() => [
            'created_at' => $this->faker->dateTimeBetween("-{$days} days", 'now'),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function randomForwardStatus(): DeliveryOrderStatus
    {
        // Weighted so the dashboard looks realistic — more delivered than failed
        return $this->faker->randomElement([
            DeliveryOrderStatus::PENDING,
            DeliveryOrderStatus::PENDING,
            DeliveryOrderStatus::PICKEDUP,
            DeliveryOrderStatus::INTRANSIT,
            DeliveryOrderStatus::INTRANSIT,
            DeliveryOrderStatus::OUTFORDELIVERY,
            DeliveryOrderStatus::DELIVERED,
            DeliveryOrderStatus::DELIVERED,
            DeliveryOrderStatus::DELIVERED,
            DeliveryOrderStatus::DELIVERED,
            DeliveryOrderStatus::FAILED,
            DeliveryOrderStatus::RETURNING,
            DeliveryOrderStatus::CANCELLED,
        ]);
    }

    private function randomPusStatus(): DeliveryOrderStatus
    {
        return $this->faker->randomElement([
            DeliveryOrderStatus::INTRANSIT,
            DeliveryOrderStatus::ATSTATION,
            DeliveryOrderStatus::ATSTATION,
            DeliveryOrderStatus::ATSTATION,
            DeliveryOrderStatus::COLLECTED,
            DeliveryOrderStatus::COLLECTED,
            DeliveryOrderStatus::RETURNING,
        ]);
    }

    /**
     * Pick a realistic weight that falls inside the rate's weight bracket.
     */
    private function weightInTier(ShippingRate $rate): float
    {
        $min = (float) $rate->min_weight;
        $max = $rate->max_weight ? (float) $rate->max_weight : $min + 30;

        return $this->faker->randomFloat(2, $min, $max);
    }

    /**
     * PUS surcharge based on weight — mirrors what the seeder creates.
     */
    private function pusSurcharge(float $weight): int
    {
        return match (true) {
            $weight <= 5 => 100,
            $weight <= 20 => 200,
            $weight <= 60 => 300,
            default => 400,
        };
    }

    private function estimatedDelivery(\DateTime $createdAt, ?ShippingRate $rate): ?string
    {
        $days = $rate?->estimated_days_max ?? $this->faker->numberBetween(2, 5);
        return (clone $createdAt)->modify("+{$days} days")->format('Y-m-d H:i:s');
    }

    private function deliveredAt(DeliveryOrderStatus $status, \DateTime $createdAt): ?string
    {
        if (!in_array($status, [DeliveryOrderStatus::DELIVERED, DeliveryOrderStatus::COLLECTED])) {
            return null;
        }

        return $this->faker->dateTimeBetween($createdAt, 'now')->format('Y-m-d H:i:s');
    }

    // ── Cost breakdown builders ───────────────────────────────────────────────

    private function flatBreakdown(?ShippingZone $zone, ?ShippingRate $rate, float $weight, float $cost): array
    {
        return [
            'model' => 'flat',
            'weight_kg' => $weight,
            'weight_tier' => $rate?->weight_label ?? 'Unknown',
            'zone' => $zone?->name ?? 'Unknown',
            'line_haul' => $cost,
            'total' => $cost,
        ];
    }

    private function pusBreakdown(
        ?ShippingZone $zone,
        ?ShippingRate $rate,
        ?PickupStation $station,
        float $weight,
        float $lineHaul,
        int $surcharge
    ): array {
        return [
            'model' => 'pus',
            'weight_kg' => $weight,
            'weight_tier' => $rate?->weight_label ?? 'Unknown',
            'zone' => $zone?->name ?? 'Unknown',
            'line_haul' => $lineHaul,
            'pus_surcharge' => $surcharge,
            'station' => $station?->name ?? 'Unknown',
            'total' => $lineHaul + $surcharge,
        ];
    }

    private function fallbackDistanceBreakdown(float $distanceKm): array
    {
        $baseRate = 8500;
        $baseKm = 50;
        $extraKmRate = 70;
        $extraKm = max(0, $distanceKm - $baseKm);
        $extraCost = round($extraKm * $extraKmRate, 2);
        $total = $baseRate + $extraCost;

        return [
            'model' => 'distance',
            'vehicle' => '3T Truck',
            'distance_km' => $distanceKm,
            'base_km' => $baseKm,
            'base_rate' => $baseRate,
            'extra_km' => $extraKm,
            'extra_km_rate' => $extraKmRate,
            'extra_km_cost' => $extraCost,
            'total' => $total,
        ];
    }
}
