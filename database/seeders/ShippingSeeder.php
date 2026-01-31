<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\County;
use App\Models\ShippingZone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ShippingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Load JSON file
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

        $this->command->info('🚀 Starting Kenya Counties Seeder...');

        DB::beginTransaction();

        try {
            // Create Shipping Zones based on regions
            $this->command->info('📦 Creating shipping zones...');
            $zones = $this->createShippingZones();

            // Process counties from JSON
            $this->command->info('🏛️  Creating counties and areas...');
            $this->processCounties($data['counties'], $zones);

            DB::commit();
            $this->command->info('✅ Successfully seeded all Kenya counties and areas!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Create shipping zones based on regions
     */
    private function createShippingZones(): array
    {
        $zoneDefinitions = [
            'Nairobi' => [
                'name' => 'Nairobi Metro',
                'code' => 'NAIROBI',
                'description' => 'Nairobi County and immediate suburbs',
                'sort_order' => 1,
            ],
            'Central' => [
                'name' => 'Central Region',
                'code' => 'CENTRAL',
                'description' => 'Central Kenya counties',
                'sort_order' => 2,
            ],
            'Coast' => [
                'name' => 'Coast Region',
                'code' => 'COAST',
                'description' => 'Coastal counties',
                'sort_order' => 3,
            ],
            'Eastern' => [
                'name' => 'Eastern Region',
                'code' => 'EASTERN',
                'description' => 'Eastern Kenya counties',
                'sort_order' => 4,
            ],
            'North Eastern' => [
                'name' => 'North Eastern Region',
                'code' => 'NORTH_EASTERN',
                'description' => 'North Eastern counties',
                'sort_order' => 5,
            ],
            'Western' => [
                'name' => 'Western Region',
                'code' => 'WESTERN',
                'description' => 'Western Kenya counties',
                'sort_order' => 6,
            ],
            'Nyanza' => [
                'name' => 'Nyanza Region',
                'code' => 'NYANZA',
                'description' => 'Nyanza counties',
                'sort_order' => 7,
            ],
            'Rift Valley' => [
                'name' => 'Rift Valley Region',
                'code' => 'RIFT_VALLEY',
                'description' => 'Rift Valley counties',
                'sort_order' => 8,
            ],
        ];

        $zones = [];

        foreach ($zoneDefinitions as $key => $definition) {
            $zones[$key] = ShippingZone::create($definition);
            $this->command->info("  ✓ Created zone: {$definition['name']}");
        }

        return $zones;
    }

    /**
     * Process counties and their areas from JSON data
     */
    private function processCounties(array $counties, array $zones): void
    {
        $countyCount = 0;
        $areaCount = 0;

        foreach ($counties as $countyData) {
            // Get the appropriate shipping zone
            $region = $countyData['region'];

            if (!isset($zones[$region])) {
                $this->command->warn("  ⚠ Unknown region: {$region} for county: {$countyData['name']}");
                continue;
            }

            // Create county
            $county = County::create([
                'name' => $countyData['name'],
                'code' => $countyData['number'],
                'shipping_zone_id' => $zones[$region]->id,
            ]);

            $countyCount++;

            // Create areas (towns) for this county
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
}
