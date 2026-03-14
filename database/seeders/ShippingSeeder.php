<?php

namespace Database\Seeders;

use App\Enums\ShippingMethodStatus;
use App\Enums\ShippingRateStatus;
use App\Enums\ShippingZoneStatus;
use App\Enums\PickupStationStatus;
use App\Enums\FreeShippingRuleStatus;
use App\Enums\LogisticsProviderStatus;
use App\Enums\VehicleRateStatus;
use App\Enums\AddonType;
use App\Enums\ShippingRateAddonStatus;
use App\Models\Area;
use App\Models\County;
use App\Models\LogisticsProvider;
use App\Models\ShippingRate;
use App\Models\ShippingRateAddon;
use App\Models\ShippingZone;
use App\Models\ShippingMethod;
use App\Models\FreeShippingRule;
use App\Models\PickupStation;
use App\Models\VehicleRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/counties.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("❌ JSON file not found: {$jsonPath}");
            return;
        }

        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('❌ Invalid JSON: ' . json_last_error_msg());
            return;
        }

        $this->command->info('🚀 Starting Kenya Shipping Seeder...');

        DB::beginTransaction();

        try {
            $this->command->info('🏢 Creating logistics provider...');
            $provider = $this->createProvider();

            $this->command->info('📦 Creating shipping zones...');
            $zones = $this->createShippingZones();

            $this->command->info('🚚 Creating shipping methods...');
            $methods = $this->createShippingMethods($provider);

            $this->command->info('🏛️  Creating counties and areas...');
            $this->processCounties($data['counties'], $zones);

            $this->command->info('💰 Creating shipping rates...');
            $this->createShippingRates($zones, $methods);

            $this->command->info('🚐 Creating vehicle rates...');
            $this->createVehicleRates($methods);

            $this->command->info('📍 Creating pickup stations...');
            $this->createPickupStations($provider);

            $this->command->info('➕ Creating rate addons...');
            $this->createRateAddons($zones, $methods);

            $this->command->info('🎁 Creating free shipping rules...');
            $this->createFreeShippingRules($zones, $methods);

            DB::commit();
            $this->command->info('✅ Successfully seeded all shipping data!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    //  Provider 

    private function createProvider(): LogisticsProvider
    {
        $provider = LogisticsProvider::create([
            'name' => 'Cossim Logistics',
            'code' => 'cossim',
            'type' => 'internal',
            'description' => 'In-house logistics arm. Handles standard, express, and pickup station deliveries across Kenya.',
            'status' => LogisticsProviderStatus::ACTIVE->value,
        ]);

        $this->command->info("  ✓ Created provider: {$provider->name}");

        return $provider;
    }

    //  Zones 

    private function createShippingZones(): array
    {
        $zoneDefinitions = [
            'NAIROBI' => [
                'name' => 'Nairobi',
                'code' => 'nairobi',
                'description' => 'All delivery locations within Nairobi County including CBD and surrounding metropolitan areas.',
                'status' => ShippingZoneStatus::ACTIVE->value,
                'is_delivery_available' => true,
            ],
            'UPCOUNTRY' => [
                'name' => 'Upcountry',
                'code' => 'upcountry',
                'description' => 'All delivery locations outside Nairobi County including major towns and counties across Kenya.',
                'status' => ShippingZoneStatus::ACTIVE->value,
                'is_delivery_available' => false,
            ],
        ];

        $zones = [];

        foreach ($zoneDefinitions as $key => $definition) {
            $zones[$key] = ShippingZone::create($definition);
            $this->command->info("  ✓ Created zone: {$definition['name']}");
        }

        return $zones;
    }

    //  Methods 

    private function createShippingMethods(LogisticsProvider $provider): array
    {
        $methodDefinitions = [
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
            'express' => [
                'name' => 'Express Delivery',
                'code' => 'express',
                'description' => 'Fast delivery with priority handling.',
                'type' => 'flat',
                'logistics_provider_id' => $provider->id,
                'supports_returns' => true,
                'delivery_time_unit' => 'days',
                'sort_order' => 2,
                'status' => ShippingMethodStatus::ACTIVE->value,
            ],
            'pickup' => [
                'name' => 'Pickup Station',
                'code' => 'pickup',
                'description' => 'Collect your order from the nearest pickup station.',
                'type' => 'pus',
                'logistics_provider_id' => $provider->id,
                'supports_returns' => false,
                'delivery_time_unit' => 'days',
                'sort_order' => 3,
                'status' => ShippingMethodStatus::ACTIVE->value,
            ],
            'on_demand' => [
                'name' => 'On-Demand Delivery',
                'code' => 'on_demand',
                'description' => 'Dedicated vehicle dispatched to your location. Price based on distance and vehicle size.',
                'type' => 'distance',
                'logistics_provider_id' => $provider->id,
                'supports_returns' => true,
                'delivery_time_unit' => 'hours',
                'sort_order' => 4,
                'status' => ShippingMethodStatus::ACTIVE->value,
            ],
        ];

        $methods = [];

        foreach ($methodDefinitions as $key => $definition) {
            $methods[$key] = ShippingMethod::create($definition);
            $this->command->info("  ✓ Created method: {$definition['name']} ({$definition['type']})");
        }

        return $methods;
    }

    //  Counties & Areas 

    private function processCounties(array $counties, array $zones): void
    {
        $countyCount = 0;
        $areaCount = 0;

        foreach ($counties as $countyData) {
            $regionKey = $countyData['region'];

            if (!isset($zones[$regionKey])) {
                $this->command->warn("  ⚠ Unknown region '{$regionKey}' for county: {$countyData['name']}");
                continue;
            }

            $county = County::create([
                'name' => $countyData['name'],
                'code' => $countyData['number'],
                'shipping_zone_id' => $zones[$regionKey]->id,
            ]);

            $countyCount++;

            // Areas do NOT inherit shipping_zone_id from their county unless
            // they genuinely need to override it. We leave it null here.
            if (!empty($countyData['main_towns'])) {
                foreach ($countyData['main_towns'] as $town) {
                    Area::create([
                        'name' => $town,
                        'county_id' => $county->id,
                        'shipping_zone_id' => null, // county zone applies by default
                    ]);
                    $areaCount++;
                }
            }

            $townCount = count($countyData['main_towns'] ?? []);
            $this->command->info("  ✓ {$countyData['number']} — {$countyData['name']} ({$townCount} areas)");
        }

        $this->command->info("📊 {$countyCount} counties, {$areaCount} areas created");
    }

    //  Shipping Rates 

    private function createShippingRates(array $zones, array $methods): void
    {
        // Weight tier definitions — shared across zone/method combinations.
        // Each tier gets a human-readable label used in the flat-rate matrix UI.
        $tiers = [
            ['min' => 0, 'max' => 5, 'label' => 'Small (0–5 Kg)'],
            ['min' => 5.1, 'max' => 20, 'label' => 'Medium (5–20 Kg)'],
            ['min' => 20.1, 'max' => 60, 'label' => 'Large (20–60 Kg)'],
            ['min' => 60.1, 'max' => null, 'label' => 'XL (60 Kg+)'],
        ];

        // Base prices per zone per tier — index matches $tiers above
        $standardPrices = [
            'NAIROBI' => [400, 800, 1200, 1800],
            'UPCOUNTRY' => [600, 1200, 1800, 2700],
        ];

        // Delivery windows per zone per tier [min_days, max_days]
        $standardDays = [
            'NAIROBI' => [[1, 2], [1, 3], [2, 3], [2, 4]],
            'UPCOUNTRY' => [[2, 4], [3, 5], [4, 6], [5, 7]],
        ];

        $totalRates = 0;

        foreach ($zones as $regionKey => $zone) {
            if (!isset($standardPrices[$regionKey])) {
                continue;
            }

            foreach ($tiers as $i => $tier) {
                $stdPrice = $standardPrices[$regionKey][$i];
                $stdDays = $standardDays[$regionKey][$i];

                //  Standard 
                ShippingRate::create([
                    'shipping_zone_id' => $zone->id,
                    'shipping_method_id' => $methods['standard']->id,
                    'min_weight' => $tier['min'],
                    'max_weight' => $tier['max'],
                    'weight_label' => $tier['label'],
                    'price' => $stdPrice,
                    'estimated_days_min' => $stdDays[0],
                    'estimated_days_max' => $stdDays[1],
                    'status' => ShippingRateStatus::ACTIVE->value,
                ]);
                $totalRates++;

                //  Express — 30% more, roughly half the delivery time 
                ShippingRate::create([
                    'shipping_zone_id' => $zone->id,
                    'shipping_method_id' => $methods['express']->id,
                    'min_weight' => $tier['min'],
                    'max_weight' => $tier['max'],
                    'weight_label' => $tier['label'],
                    'price' => (int) round($stdPrice * 1.3),
                    'estimated_days_min' => max(1, (int) ceil($stdDays[0] * 0.5)),
                    'estimated_days_max' => max(1, (int) ceil($stdDays[1] * 0.5)),
                    'status' => ShippingRateStatus::ACTIVE->value,
                ]);
                $totalRates++;

                //  Pickup Station — flat discount vs standard 
                // Only makes sense where we have stations (Nairobi to start)
                if (in_array($regionKey, ['NAIROBI', 'UPCOUNTRY'])) {
                    ShippingRate::create([
                        'shipping_zone_id' => $zone->id,
                        'shipping_method_id' => $methods['pickup']->id,
                        'min_weight' => $tier['min'],
                        'max_weight' => $tier['max'],
                        'weight_label' => $tier['label'],
                        'price' => 0,
                        'estimated_days_min' => $stdDays[0] + 1,
                        'estimated_days_max' => $stdDays[1] + 1,
                        'status' => ShippingRateStatus::ACTIVE->value,
                    ]);
                    $totalRates++;
                }
            }

            $this->command->info("  ✓ Created rates for zone: {$zone->name}");
        }

        $this->command->info("📊 {$totalRates} shipping rates created");
    }

    //  Vehicle Rates 

    private function createVehicleRates(array $methods): void
    {
        // Formula: base_rate + max(0, actual_km − base_km) × extra_km_rate
        // base_km = free distance included in the base rate
        $vehicles = [
            [
                'vehicle_type' => 'motorbike',
                'vehicle_label' => 'Motorbike',
                'base_rate' => 800,
                'base_km' => 30,
                'extra_km_rate' => 40,
                'max_weight_kg' => 5,
                'max_volume_m3' => null,
            ],
            [
                'vehicle_type' => 'van',
                'vehicle_label' => 'Van',
                'base_rate' => 7500,
                'base_km' => 50,
                'extra_km_rate' => 70,
                'max_weight_kg' => 1000,
                'max_volume_m3' => 8.0,
            ],
            [
                'vehicle_type' => 'truck_3t',
                'vehicle_label' => '3T Truck',
                'base_rate' => 8500,
                'base_km' => 50,
                'extra_km_rate' => 70,
                'max_weight_kg' => 3000,
                'max_volume_m3' => 20.0,
            ],
            [
                'vehicle_type' => 'truck_5t',
                'vehicle_label' => '5T Truck',
                'base_rate' => 10000,
                'base_km' => 50,
                'extra_km_rate' => 90,
                'max_weight_kg' => 5000,
                'max_volume_m3' => 30.0,
            ],
            [
                'vehicle_type' => 'truck_7t',
                'vehicle_label' => '7T Truck',
                'base_rate' => 12000,
                'base_km' => 50,
                'extra_km_rate' => 90,
                'max_weight_kg' => 7000,
                'max_volume_m3' => 40.0,
            ],
            [
                'vehicle_type' => 'truck_10t',
                'vehicle_label' => '10T Truck',
                'base_rate' => 15000,
                'base_km' => 50,
                'extra_km_rate' => 90,
                'max_weight_kg' => 10000,
                'max_volume_m3' => 60.0,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            VehicleRate::create([
                'shipping_method_id' => $methods['on_demand']->id,
                'vehicle_type' => $vehicle['vehicle_type'],
                'vehicle_label' => $vehicle['vehicle_label'],
                'base_rate' => $vehicle['base_rate'],
                'base_km' => $vehicle['base_km'],
                'extra_km_rate' => $vehicle['extra_km_rate'],
                'max_weight_kg' => $vehicle['max_weight_kg'],
                'max_volume_m3' => $vehicle['max_volume_m3'],
                'status' => VehicleRateStatus::ACTIVE->value,
            ]);

            $this->command->info("  ✓ {$vehicle['vehicle_label']} — KES {$vehicle['base_rate']} base, {$vehicle['base_km']} km included");
        }
    }

    //  Rate Addons (PUS Surcharges) 

    private function createRateAddons(array $zones, array $methods): void
    {
        // PUS surcharges stack on top of the pickup station flat rates.
        // One addon per weight tier for Nairobi (the only zone with PUS stations).
        // NULL pickup_station_id = applies to ALL stations globally.
        $surcharges = [
            'Small (0–5 Kg)' => 100,
            'Medium (5–20 Kg)' => 200,
            'Large (20–60 Kg)' => 300,
            'XL (60 Kg+)' => 400,
        ];

        // Find the active PUS rates for Nairobi
        $pusRates = ShippingRate::where('shipping_method_id', $methods['pickup']->id)
            ->where('shipping_zone_id', $zones['NAIROBI']->id)
            ->where('status', ShippingRateStatus::ACTIVE->value)
            ->get()
            ->keyBy('weight_label');

        $addonCount = 0;

        foreach ($surcharges as $weightLabel => $amount) {
            $rate = $pusRates->get($weightLabel);

            if (!$rate) {
                $this->command->warn("  ⚠ PUS rate not found for tier: {$weightLabel}");
                continue;
            }

            ShippingRateAddon::create([
                'shipping_rate_id' => $rate->id,
                'addon_type' => AddonType::PUS->value,
                'label' => 'Pickup Station Surcharge',
                'addon_amount' => $amount,
                'pickup_station_id' => null, // applies to all stations
                'status' => ShippingRateAddonStatus::ACTIVE->value,
            ]);

            $addonCount++;
            $this->command->info("  ✓ PUS addon: {$weightLabel} → +KES {$amount}");
        }

        $this->command->info("📊 {$addonCount} rate addons created");
    }

    //  Pickup Stations 

    private function createPickupStations(LogisticsProvider $provider): void
    {
        $nairobi = County::where('name', 'Nairobi')->first();

        if (!$nairobi) {
            $this->command->warn('  ⚠ Nairobi county not found — skipping pickup stations');
            return;
        }

        // Optionally resolve the specific Nairobi area if it exists
        $syokimauArea = Area::where('county_id', $nairobi->id)
            ->where('name', 'like', '%Embakasi%')
            ->first();

        $stations = [
            [
                'name' => 'Nairobi Pickup — Syokimau',
                'code' => 'nbo-syokimau',
                'logistics_provider_id' => $provider->id,
                'county_id' => $nairobi->id,
                'area_id' => $syokimauArea?->id,
                'address' => 'Off Old Mombasa Road, before the Nairobi SGR Terminus',
                'phone' => '+254712345678',
                'operating_hours' => 'Mon–Fri: 8:00 AM – 8:00 PM, Sat: 8:00 AM – 1:00 PM, Sun: Closed',
                'holding_days' => 7,
                'latitude' => -1.2864,
                'longitude' => 36.8172,
                'status' => PickupStationStatus::ACTIVE->value,
            ],
        ];

        foreach ($stations as $station) {
            PickupStation::create($station);
            $this->command->info("  ✓ Created station: {$station['name']}");
        }
    }

    //  Free Shipping Rules 

    private function createFreeShippingRules(array $zones, array $methods): void
    {
        // Nairobi only — standard & express — spend KES 5,000+ and get free shipping
        FreeShippingRule::create([
            'name' => 'Nairobi Free Shipping (Standard)',
            'shipping_zone_id' => $zones['NAIROBI']->id,
            'shipping_method_id' => $methods['standard']->id,
            'min_order_amount' => 5000,
            'max_weight' => 10,
            'starts_at' => null,
            'ends_at' => null,
            'status' => FreeShippingRuleStatus::INACTIVE->value, // activate manually
        ]);

        // Nationwide — any method — spend KES 10,000+ for free shipping
        FreeShippingRule::create([
            'name' => 'Nationwide Free Shipping',
            'shipping_zone_id' => null,
            'shipping_method_id' => null,
            'min_order_amount' => 10000,
            'max_weight' => 20,
            'starts_at' => null,
            'ends_at' => null,
            'status' => FreeShippingRuleStatus::INACTIVE->value, // activate manually
        ]);

        $this->command->info('  ✓ Created free shipping rules (inactive — activate when ready)');
    }
}
