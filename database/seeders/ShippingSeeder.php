<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\County;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingMethod;
use App\Models\FreeShippingRule;
use App\Models\PickupStation;
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
            $this->command->info('🚚 Creating shipping methods...');
            $methods = $this->createShippingMethods();

            $this->command->info('📦 Creating shipping zones...');
            $zones = $this->createShippingZones();

            $this->command->info('🏛️  Creating counties and areas...');
            $this->processCounties($data['counties'], $zones);

            $this->command->info('💰 Creating shipping rates...');
            $this->createShippingRates($zones, $methods);

            $this->command->info('📍 Creating pickup stations...');
            $this->createPickupStations();

            $this->command->info('🎁 Creating free shipping rules...');
            $this->createFreeShippingRules($zones);

            DB::commit();
            $this->command->info('✅ Successfully seeded all shipping data!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create shipping methods
     */
    private function createShippingMethods(): array
    {
        $methodDefinitions = [
            [
                'name' => 'Standard Delivery',
                'code' => 'standard',
                'description' => 'Regular delivery to your doorstep',
                'icon' => 'truck',
                'sort_order' => 1,
            ],
            [
                'name' => 'Express Delivery',
                'code' => 'express',
                'description' => 'Fast delivery with priority handling',
                'icon' => 'bolt',
                'sort_order' => 2,
            ],
            [
                'name' => 'Pickup Station',
                'code' => 'pickup',
                'description' => 'Collect from nearest pickup point',
                'icon' => 'map-pin',
                'sort_order' => 3,
            ],
        ];

        $methods = [];

        foreach ($methodDefinitions as $definition) {
            $method = ShippingMethod::create($definition);
            $methods[$definition['code']] = $method;
            $this->command->info("  ✓ Created method: {$definition['name']}");
        }

        return $methods;
    }

    private function createShippingZones(): array
    {
        $zoneDefinitions = [
            'Nairobi' => [
                'name' => 'Nairobi Region',
                'code' => 'NAIROBI',
                'description' => 'Nairobi County and immediate suburbs',
            ],
            'Central' => [
                'name' => 'Central Region',
                'code' => 'CENTRAL',
                'description' => 'Central Kenya counties',
            ],
            'Coast' => [
                'name' => 'Coast Region',
                'code' => 'COAST',
                'description' => 'Coastal counties',
            ],
            'Eastern' => [
                'name' => 'Eastern Region',
                'code' => 'EASTERN',
                'description' => 'Eastern Kenya counties',
            ],
            'North Eastern' => [
                'name' => 'North Eastern Region',
                'code' => 'NORTH_EASTERN',
                'description' => 'North Eastern counties',
            ],
            'Western' => [
                'name' => 'Western Region',
                'code' => 'WESTERN',
                'description' => 'Western Kenya counties',
            ],
            'Nyanza' => [
                'name' => 'Nyanza Region',
                'code' => 'NYANZA',
                'description' => 'Nyanza counties',
            ],
            'Rift Valley' => [
                'name' => 'Rift Valley Region',
                'code' => 'RIFT_VALLEY',
                'description' => 'Rift Valley counties',
            ],
        ];

        $zones = [];

        foreach ($zoneDefinitions as $key => $definition) {
            $zones[$key] = ShippingZone::create($definition);
            $this->command->info("  ✓ Created zone: {$definition['name']}");
        }

        return $zones;
    }

    private function processCounties(array $counties, array $zones): void
    {
        $countyCount = 0;
        $areaCount = 0;

        foreach ($counties as $countyData) {
            $region = $countyData['region'];

            if (!isset($zones[$region])) {
                $this->command->warn("  ⚠ Unknown region: {$region} for county: {$countyData['name']}");
                continue;
            }

            $county = County::create([
                'name' => $countyData['name'],
                'code' => $countyData['number'],
                'shipping_zone_id' => $zones[$region]->id,
            ]);

            $countyCount++;

            if (isset($countyData['main_towns']) && is_array($countyData['main_towns'])) {
                foreach ($countyData['main_towns'] as $town) {
                    Area::create([
                        'name' => $town,
                        'county_id' => $county->id,
                        'shipping_zone_id' => $zones[$region]->id,
                    ]);
                    $areaCount++;
                }
            }

            $townCount = count($countyData['main_towns'] ?? []);
            $this->command->info("  ✓ {$countyData['number']} - {$countyData['name']} ({$townCount} towns)");
        }

        $this->command->info("📊 Summary: {$countyCount} counties, {$areaCount} areas created");
    }

    /**
     * Create shipping rates for each zone, method, and weight range
     */
    private function createShippingRates(array $zones, array $methods): void
    {
        // Standard Delivery Rates
        $standardRates = [
            'Nairobi' => [
                ['min' => 0, 'max' => 1, 'price' => 200, 'days_min' => 1, 'days_max' => 2],
                ['min' => 1, 'max' => 5, 'price' => 350, 'days_min' => 1, 'days_max' => 3],
                ['min' => 5, 'max' => 10, 'price' => 500, 'days_min' => 2, 'days_max' => 3],
                ['min' => 10, 'max' => 20, 'price' => 800, 'days_min' => 2, 'days_max' => 4],
                ['min' => 20, 'max' => 50, 'price' => 1500, 'days_min' => 3, 'days_max' => 5],
            ],
            // Add other zones with similar structure...
        ];

        // Express Delivery Rates (30% more expensive, 50% faster)
        $expressRates = [];
        foreach ($standardRates as $zone => $rates) {
            $expressRates[$zone] = array_map(function ($rate) {
                return [
                    'min' => $rate['min'],
                    'max' => $rate['max'],
                    'price' => $rate['price'] * 1.3, // 30% more
                    'days_min' => max(1, ceil($rate['days_min'] * 0.5)),
                    'days_max' => max(1, ceil($rate['days_max'] * 0.5)),
                ];
            }, $rates);
        }

        $totalRates = 0;

        // Create rates for each method
        foreach ($zones as $regionName => $zone) {
            // Standard
            if (isset($standardRates[$regionName])) {
                foreach ($standardRates[$regionName] as $rate) {
                    ShippingRate::create([
                        'shipping_zone_id' => $zone->id,
                        'shipping_method_id' => $methods['standard']->id,
                        'min_weight' => $rate['min'],
                        'max_weight' => $rate['max'],
                        'price' => $rate['price'],
                        'estimated_days_min' => $rate['days_min'],
                        'estimated_days_max' => $rate['days_max'],
                        'is_active' => true,
                    ]);
                    $totalRates++;
                }
            }

            // Express
            if (isset($expressRates[$regionName])) {
                foreach ($expressRates[$regionName] as $rate) {
                    ShippingRate::create([
                        'shipping_zone_id' => $zone->id,
                        'shipping_method_id' => $methods['express']->id,
                        'min_weight' => $rate['min'],
                        'max_weight' => $rate['max'],
                        'price' => $rate['price'],
                        'estimated_days_min' => $rate['days_min'],
                        'estimated_days_max' => $rate['days_max'],
                        'is_active' => true,
                    ]);
                    $totalRates++;
                }
            }

            // Pickup
            if (isset($pickupRates[$regionName])) {
                foreach ($pickupRates[$regionName] as $rate) {
                    ShippingRate::create([
                        'shipping_zone_id' => $zone->id,
                        'shipping_method_id' => $methods['pickup']->id,
                        'min_weight' => $rate['min'],
                        'max_weight' => $rate['max'],
                        'price' => $rate['price'],
                        'estimated_days_min' => $rate['days_min'],
                        'estimated_days_max' => $rate['days_max'],
                        'is_active' => true,
                    ]);
                    $totalRates++;
                }
            }

            $this->command->info("  ✓ Created rates for {$zone->name}");
        }

        $this->command->info("📊 Total shipping rates created: {$totalRates}");
    }

    /**
     * Create pickup stations
     */
    private function createPickupStations(): void
    {
        // Get Nairobi county
        $nairobi = County::where('name', 'Nairobi')->first();

        if (!$nairobi) {
            $this->command->warn("  ⚠ Nairobi county not found, skipping pickup stations");
            return;
        }

        $stations = [
            [
                'name' => 'Nairobi Pickup ',
                'code' => 'NBO_SYK',
                'county_id' => $nairobi->id,
                'address' => 'Off Old Mombasa Road before the Nairobi SGR Terminus',
                'phone' => '+254712345678',
                'operating_hours' => 'Mon-Sat: 8:00 AM - 8:00 PM, Sat: 8:00 AM - 1:00 PM, Sun: Closed',
                'latitude' => -1.2864,
                'longitude' => 36.8172,
            ],
        ];

        foreach ($stations as $station) {
            PickupStation::create($station);
            $this->command->info("  ✓ Created pickup station: {$station['name']}");
        }
    }

    private function createFreeShippingRules(array $zones): void
    {
        FreeShippingRule::create([
            'name' => 'Nairobi Free Shipping',
            'shipping_zone_id' => $zones['Nairobi']->id,
            'min_order_amount' => 5000,
            'max_weight' => 10,
            'is_active' => true,
        ]);

        FreeShippingRule::create([
            'name' => 'Nationwide Free Shipping',
            'shipping_zone_id' => null,
            'min_order_amount' => 10000,
            'max_weight' => 20,
            'is_active' => true,
        ]);

        $this->command->info("  ✓ Created free shipping rules");
    }
}
