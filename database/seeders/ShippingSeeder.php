<?php

namespace Database\Seeders;

use App\Enums\AddonType;
use App\Enums\FreeShippingRuleStatus;
use App\Enums\LogisticsProviderStatus;
use App\Enums\ShippingMethodStatus;
use App\Enums\ShippingRateAddonStatus;
use App\Enums\ShippingRateStatus;
use App\Enums\ShippingZoneStatus;
use App\Models\County;
use App\Models\FreeShippingRule;
use App\Models\LogisticsProvider;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingRateAddon;
use App\Models\ShippingZone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Seeds the two-zone shipping model.
 *
 *   Zones:    within_nairobi · upcountry
 *   Counties: Nairobi → within_nairobi · everything else → upcountry
 *             (satellite sub-counties are pulled into within_nairobi
 *              by ServiceAreaSeeder via sub_county.shipping_zone_id override)
 *   Methods:  standard (flat) · pickup (PUS, Syokimau station)
 */
class ShippingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $jsonPath = database_path('seeders/data/counties.json');

        if (! File::exists($jsonPath)) {
            $this->command->error("❌ JSON file not found: {$jsonPath}");

            return;
        }

        $data = json_decode(File::get($jsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('❌ Invalid JSON: '.json_last_error_msg());

            return;
        }

        $this->command->info('🚀 Starting Shipping Seeder (2-zone model)...');

        DB::beginTransaction();

        try {
            $provider = $this->createProvider();
            $zones = $this->createZones();
            $methods = $this->createMethods($provider);
            $this->createCounties($data['counties'], $zones);
            $this->createRates($zones, $methods);
            $this->createRateAddons($zones, $methods);
            $this->createFreeShippingRules($zones, $methods);

            DB::commit();
            $this->command->info('✅ Shipping seeded.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: '.$e->getMessage());
            throw $e;
        }
    }

    private function createProvider(): LogisticsProvider
    {
        $provider = LogisticsProvider::create([
            'name' => 'Sheffield Africa Logistics',
            'code' => 'sheffield',
            'type' => 'internal',
            'description' => 'Sheffield Africa in-house logistics. Door delivery across Kenya, with a lower rate within Nairobi and satellite towns.',
            'status' => LogisticsProviderStatus::ACTIVE->value,
        ]);

        $this->command->info("  ✓ Provider: {$provider->name}");

        return $provider;
    }

    /**
     * @return array<string, ShippingZone>
     */
    private function createZones(): array
    {
        $definitions = [
            'WITHIN_NAIROBI' => [
                'name' => 'Within Nairobi',
                'code' => 'within_nairobi',
                'description' => 'Nairobi County and nearby satellite towns (Syokimau, Mlolongo, Athi River, Kitengela, Rongai, Kiambu Town, Thika Town, etc.). Base rate tier.',
                'status' => ShippingZoneStatus::ACTIVE->value,
                'is_delivery_available' => true,
            ],
            'UPCOUNTRY' => [
                'name' => 'Upcountry',
                'code' => 'upcountry',
                'description' => 'Rest of Kenya outside the Nairobi metro ring. Door delivery available at a higher rate.',
                'status' => ShippingZoneStatus::ACTIVE->value,
                'is_delivery_available' => true,
            ],
        ];

        $zones = [];

        foreach ($definitions as $key => $definition) {
            $zones[$key] = ShippingZone::create($definition);
            $this->command->info("  ✓ Zone: {$definition['name']}");
        }

        return $zones;
    }

    /**
     * @return array<string, ShippingMethod>
     */
    private function createMethods(LogisticsProvider $provider): array
    {
        $definitions = [
            'standard' => [
                'name' => 'Standard Delivery',
                'code' => 'standard',
                'description' => 'Regular delivery to your doorstep.',
                'type' => 'flat',
                'logistics_provider_id' => $provider->id,
                'supports_returns' => true,
                'delivery_time_unit' => 'days',
                'sort_order' => 1,
                'status' => ShippingMethodStatus::ACTIVE->value,
            ],
            'pickup' => [
                'name' => 'Pickup Station',
                'code' => 'pickup',
                'description' => 'Collect your order from our Syokimau pickup station.',
                'type' => 'pus',
                'logistics_provider_id' => $provider->id,
                'supports_returns' => false,
                'delivery_time_unit' => 'days',
                'sort_order' => 2,
                'status' => ShippingMethodStatus::ACTIVE->value,
            ],
        ];

        $methods = [];

        foreach ($definitions as $key => $definition) {
            $methods[$key] = ShippingMethod::create($definition);
            $this->command->info("  ✓ Method: {$definition['name']} ({$definition['type']})");
        }

        return $methods;
    }

    /**
     * Nairobi county → within_nairobi. Everything else → upcountry.
     * ServiceAreaSeeder overrides specific satellite sub-counties to
     * within_nairobi via sub_county.shipping_zone_id.
     *
     * @param  array<int, array{number: string, name: string, region: string}>  $counties
     * @param  array<string, ShippingZone>  $zones
     */
    private function createCounties(array $counties, array $zones): void
    {
        foreach ($counties as $countyData) {
            $zoneKey = $countyData['region'] === 'NAIROBI' ? 'WITHIN_NAIROBI' : 'UPCOUNTRY';

            County::create([
                'name' => $countyData['name'],
                'code' => $countyData['number'],
                'shipping_zone_id' => $zones[$zoneKey]->id,
            ]);
        }

        $nairobiCount = collect($counties)->where('region', 'NAIROBI')->count();
        $upcountryCount = count($counties) - $nairobiCount;
        $this->command->info("  ✓ Counties: {$nairobiCount} within_nairobi, {$upcountryCount} upcountry");
    }

    /**
     * Weight-bracketed prices per (zone × method).
     *
     * @param  array<string, ShippingZone>  $zones
     * @param  array<string, ShippingMethod>  $methods
     */
    private function createRates(array $zones, array $methods): void
    {
        $tiers = [
            ['min' => 0,    'max' => 5,    'label' => 'Small (0–5 Kg)'],
            ['min' => 5.1,  'max' => 20,   'label' => 'Medium (5.1–20 Kg)'],
            ['min' => 20.1, 'max' => 60,   'label' => 'Large (20.1–60 Kg)'],
            ['min' => 60.1, 'max' => null, 'label' => 'XL (60.1 Kg+)'],
        ];

        // (zone_key, method_key) => [tier0Price, tier1Price, tier2Price, tier3Price], delivery_window
        $priceMatrix = [
            ['WITHIN_NAIROBI', 'standard', [300,  600,  900, 1400], ['min' => 1, 'max' => 2]],
            ['UPCOUNTRY',      'standard', [500,  900, 1400, 2000], ['min' => 3, 'max' => 7]],
            // PUS only available within Nairobi ring (Syokimau station).
            ['WITHIN_NAIROBI', 'pickup',   [200,  400,  700, 1100], ['min' => 2, 'max' => 4]],
        ];

        $count = 0;

        foreach ($priceMatrix as [$zoneKey, $methodKey, $prices, $window]) {
            $zone = $zones[$zoneKey];
            $method = $methods[$methodKey];

            foreach ($tiers as $index => $tier) {
                ShippingRate::create([
                    'shipping_zone_id' => $zone->id,
                    'shipping_method_id' => $method->id,
                    'min_weight' => $tier['min'],
                    'max_weight' => $tier['max'],
                    'weight_label' => $tier['label'],
                    'price' => $prices[$index],
                    'estimated_days_min' => $window['min'],
                    'estimated_days_max' => $window['max'],
                    'status' => ShippingRateStatus::ACTIVE->value,
                ]);
                $count++;
            }

            $this->command->info("  ✓ Rates: {$zone->name} × {$method->name}");
        }

        $this->command->info("  ✓ {$count} rate rows created");
    }

    /**
     * @param  array<string, ShippingZone>  $zones
     * @param  array<string, ShippingMethod>  $methods
     */
    private function createRateAddons(array $zones, array $methods): void
    {
        $pusRates = ShippingRate::where('shipping_method_id', $methods['pickup']->id)
            ->where('shipping_zone_id', $zones['WITHIN_NAIROBI']->id)
            ->where('status', ShippingRateStatus::ACTIVE->value)
            ->get();

        foreach ($pusRates as $rate) {
            ShippingRateAddon::create([
                'shipping_rate_id' => $rate->id,
                'addon_type' => AddonType::PUS->value,
                'label' => 'Syokimau Station Handling',
                'addon_amount' => 0,
                'pickup_station_id' => null,
                'status' => ShippingRateAddonStatus::ACTIVE->value,
            ]);
        }

        $this->command->info("  ✓ {$pusRates->count()} PUS addons created");
    }

    /**
     * @param  array<string, ShippingZone>  $zones
     * @param  array<string, ShippingMethod>  $methods
     */
    private function createFreeShippingRules(array $zones, array $methods): void
    {
        // Within Nairobi standard: free above KES 5,000.
        FreeShippingRule::create([
            'name' => 'Within Nairobi — Free Standard over KES 5,000',
            'shipping_zone_id' => $zones['WITHIN_NAIROBI']->id,
            'shipping_method_id' => $methods['standard']->id,
            'min_order_amount' => 5000,
            'max_weight' => 20,
            'status' => FreeShippingRuleStatus::ACTIVE->value,
        ]);

        // Upcountry standard: free above KES 15,000.
        FreeShippingRule::create([
            'name' => 'Upcountry — Free Standard over KES 15,000',
            'shipping_zone_id' => $zones['UPCOUNTRY']->id,
            'shipping_method_id' => $methods['standard']->id,
            'min_order_amount' => 15000,
            'max_weight' => 20,
            'status' => FreeShippingRuleStatus::ACTIVE->value,
        ]);

        // PUS: free above KES 5,000.
        FreeShippingRule::create([
            'name' => 'PUS — Free Pickup over KES 5,000',
            'shipping_zone_id' => $zones['WITHIN_NAIROBI']->id,
            'shipping_method_id' => $methods['pickup']->id,
            'min_order_amount' => 5000,
            'max_weight' => 20,
            'status' => FreeShippingRuleStatus::ACTIVE->value,
        ]);

        $this->command->info('  ✓ Free-shipping rules created');
    }
}
