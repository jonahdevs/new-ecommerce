<?php

namespace Database\Seeders;

use App\Enums\AddonType;
use App\Enums\FreeShippingRuleStatus;
use App\Enums\LogisticsProviderStatus;
use App\Enums\PickupStationStatus;
use App\Enums\ShippingMethodStatus;
use App\Enums\ShippingRateAddonStatus;
use App\Enums\ShippingRateStatus;
use App\Enums\ShippingZoneStatus;
use App\Models\Area;
use App\Models\County;
use App\Models\FreeShippingRule;
use App\Models\LogisticsProvider;
use App\Models\PickupStation;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingRateAddon;
use App\Models\ShippingZone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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

        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('❌ Invalid JSON: '.json_last_error_msg());

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

            $this->command->info('📍 Creating pickup stations...');
            $this->createPickupStations($provider);

            $this->command->info('➕ Creating rate addons...');
            $this->createRateAddons($zones, $methods);

            $this->command->info('🎁 Creating free shipping rules...');
            $this->createFreeShippingRules($zones, $methods);

            $this->command->info('🌍 Assigning Greater Nairobi delivery areas...');
            $this->assignNairobiExtendedAreas($zones);

            DB::commit();
            $this->command->info('✅ Successfully seeded all shipping data!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: '.$e->getMessage());
            throw $e;
        }
    }

    // =========================================================================
    //  Provider
    // =========================================================================

    private function createProvider(): LogisticsProvider
    {
        $provider = LogisticsProvider::create([
            'name' => 'Sheffield Africa Logistics',
            'code' => 'sheffield',
            'type' => 'internal',
            'description' => 'Sheffield Africa in-house logistics arm. Handles standard and pickup station deliveries across Nairobi.',
            'status' => LogisticsProviderStatus::ACTIVE->value,
        ]);

        $this->command->info("  ✓ Created provider: {$provider->name}");

        return $provider;
    }

    // =========================================================================
    //  Zones
    //
    //  NAIROBI     → delivery available now
    //  UPCOUNTRY   → not yet available; counties are assigned but checkout
    //                is blocked by is_delivery_available = false
    // =========================================================================

    private function createShippingZones(): array
    {
        $definitions = [
            'NAIROBI' => [
                'name' => 'Nairobi',
                'code' => 'nairobi',
                'description' => 'All delivery locations within Nairobi County and selected nearby areas.',
                'status' => ShippingZoneStatus::ACTIVE->value,
                'is_delivery_available' => true,
            ],
            'UPCOUNTRY' => [
                'name' => 'Upcountry',
                'code' => 'upcountry',
                'description' => 'Counties outside Nairobi. Delivery not yet available — flip is_delivery_available when ready to expand.',
                'status' => ShippingZoneStatus::ACTIVE->value,
                'is_delivery_available' => false,
            ],
        ];

        $zones = [];

        foreach ($definitions as $key => $definition) {
            $zones[$key] = ShippingZone::create($definition);
            $this->command->info("  ✓ Created zone: {$definition['name']} (delivery: ".($definition['is_delivery_available'] ? 'yes' : 'no').')');
        }

        return $zones;
    }

    // =========================================================================
    //  Methods
    //
    //  Two methods only:
    //    standard  → doorstep delivery  (flat)
    //    pickup    → pickup station     (pus)
    //
    //  Both are shown at checkout whenever the resolved zone has
    //  is_delivery_available = true. The customer sees both options
    //  with KES 0 for each — no provider name is exposed.
    // =========================================================================

    private function createShippingMethods(LogisticsProvider $provider): array
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
                'description' => 'Collect your order from the nearest pickup station.',
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
            $this->command->info("  ✓ Created method: {$definition['name']} ({$definition['type']})");
        }

        return $methods;
    }

    private function resolveZoneKey(string $region): string
    {
        return match ($region) {
            'NAIROBI' => 'NAIROBI',
            default => 'UPCOUNTRY',
        };
    }

    // =========================================================================
    //  Counties & Areas
    //
    //  Each county is assigned the zone that matches its `region` key from
    //  the JSON. Areas get shipping_zone_id = null so they fall back to
    //  their county's zone by default. Set an area's shipping_zone_id
    //  explicitly only when it needs to override its county's zone
    //  (e.g. a border town served by a different rate bracket).
    // =========================================================================

    private function processCounties(array $counties, array $zones): void
    {
        $countyCount = 0;
        $areaCount = 0;

        foreach ($counties as $countyData) {
            $regionKey = $this->resolveZoneKey($countyData['region']);

            if (! isset($zones[$regionKey])) {
                $this->command->warn("  ⚠ No zone found for key '{$regionKey}' — county: {$countyData['name']}");

                continue;
            }

            $county = County::create([
                'name' => $countyData['name'],
                'code' => $countyData['number'],
                'shipping_zone_id' => $zones[$regionKey]->id,
            ]);

            $countyCount++;

            foreach (array_unique($countyData['main_towns'] ?? []) as $town) {
                Area::create([
                    'name' => $town,
                    'county_id' => $county->id,
                    'shipping_zone_id' => null,
                ]);
                $areaCount++;
            }

            $townCount = count(array_unique($countyData['main_towns'] ?? []));
            $this->command->info("  ✓ {$countyData['number']} — {$countyData['name']} ({$townCount} areas) → {$regionKey}");
        }

        $this->command->info("📊 {$countyCount} counties, {$areaCount} areas created");
    }

    // =========================================================================
    //  Shipping Rates
    //
    //  Rates are only created for zones where delivery is available.
    //  All prices are KES 0 — the cost is absorbed into product pricing.
    //  The weight tiers and delivery windows are kept so that when you
    //  eventually introduce paid shipping, you only need to update the
    //  price column — nothing structural changes.
    //
    //  Both methods (standard + pickup) get a rate row per tier so the
    //  checkout resolver can find and present both options.
    //
    //  Pickup delivery window is +1 day vs standard to account for the
    //  station transfer step.
    // =========================================================================

    private function createShippingRates(array $zones, array $methods): void
    {
        $tiers = [
            ['min' => 0, 'max' => 5, 'label' => 'Small (0–5 Kg)'],
            ['min' => 5.1, 'max' => 20, 'label' => 'Medium (5–20 Kg)'],
            ['min' => 20.1, 'max' => 60, 'label' => 'Large (20–60 Kg)'],
            ['min' => 60.1, 'max' => null, 'label' => 'XL (60 Kg+)'],
        ];

        // One flat window per method — weight affects cost, not speed.
        $deliveryWindows = [
            'NAIROBI' => [
                'standard' => ['min' => 1, 'max' => 2],
                'pickup' => ['min' => 2, 'max' => 3], // +1 day for station transfer
            ],
        ];

        $totalRates = 0;

        foreach ($zones as $regionKey => $zone) {

            if (! $zone->is_delivery_available) {
                $this->command->info("  — Skipped rates for zone: {$zone->name} (delivery unavailable)");

                continue;
            }

            $windows = $deliveryWindows[$regionKey] ?? [
                'standard' => ['min' => 3, 'max' => 5],
                'pickup' => ['min' => 4, 'max' => 6],
            ];

            foreach ($tiers as $tier) {

                ShippingRate::create([
                    'shipping_zone_id' => $zone->id,
                    'shipping_method_id' => $methods['standard']->id,
                    'min_weight' => $tier['min'],
                    'max_weight' => $tier['max'],
                    'weight_label' => $tier['label'],
                    'price' => 0,
                    'estimated_days_min' => $windows['standard']['min'],
                    'estimated_days_max' => $windows['standard']['max'],
                    'status' => ShippingRateStatus::ACTIVE->value,
                ]);
                $totalRates++;

                ShippingRate::create([
                    'shipping_zone_id' => $zone->id,
                    'shipping_method_id' => $methods['pickup']->id,
                    'min_weight' => $tier['min'],
                    'max_weight' => $tier['max'],
                    'weight_label' => $tier['label'],
                    'price' => 0,
                    'estimated_days_min' => $windows['pickup']['min'],
                    'estimated_days_max' => $windows['pickup']['max'],
                    'status' => ShippingRateStatus::ACTIVE->value,
                ]);
                $totalRates++;
            }

            $this->command->info("  ✓ Created rates for zone: {$zone->name}");
        }

        $this->command->info("📊 {$totalRates} shipping rates created");
    }

    // =========================================================================
    //  Rate Addons (PUS Surcharges)
    //
    //  All surcharges are KES 0 — consistent with the free delivery model.
    //  Rows are created so the PUS resolver finds a valid addon record
    //  and doesn't error. When you introduce paid PUS surcharges later,
    //  update addon_amount here — nothing structural changes.
    // =========================================================================

    private function createRateAddons(array $zones, array $methods): void
    {
        $pusRates = ShippingRate::where('shipping_method_id', $methods['pickup']->id)
            ->where('shipping_zone_id', $zones['NAIROBI']->id)
            ->where('status', ShippingRateStatus::ACTIVE->value)
            ->get();

        if ($pusRates->isEmpty()) {
            $this->command->warn('  ⚠ No PUS rates found for Nairobi — skipping addons');

            return;
        }

        $addonCount = 0;

        foreach ($pusRates as $rate) {
            ShippingRateAddon::create([
                'shipping_rate_id' => $rate->id,
                'addon_type' => AddonType::PUS->value,
                'label' => 'Pickup Station Surcharge',
                'addon_amount' => 0,
                'pickup_station_id' => null, // applies to all stations
                'status' => ShippingRateAddonStatus::ACTIVE->value,
            ]);
            $addonCount++;
        }

        $this->command->info("  ✓ Created {$addonCount} PUS addons (KES 0 each)");
    }

    // =========================================================================
    //  Pickup Stations
    // =========================================================================

    private function createPickupStations(LogisticsProvider $provider): void
    {
        $nairobi = County::where('name', 'Nairobi')->first();

        if (! $nairobi) {
            $this->command->warn('  ⚠ Nairobi county not found — skipping pickup stations');

            return;
        }

        $embakasi = Area::where('county_id', $nairobi->id)
            ->where('name', 'like', '%Embakasi%')
            ->first();

        $stations = [
            [
                'name' => 'Nairobi Pickup — Syokimau',
                'code' => 'nbo-syokimau',
                'logistics_provider_id' => $provider->id,
                'county_id' => $nairobi->id,
                'area_id' => $embakasi?->id,
                'address' => 'Off Old Mombasa Road, before the Nairobi SGR Terminus',
                'phone' => '+254712345678',
                'operating_hours' => 'Mon–Fri: 8:00 AM – 8:00 PM, Sat: 8:00 AM – 1:00 PM, Sun: Closed',
                'holding_days' => 7,
                'latitude' => -1.2864,
                'longitude' => 36.8172,
                'status' => PickupStationStatus::ACTIVE->value,
                'is_primary' => true,
            ],
        ];

        foreach ($stations as $station) {
            PickupStation::create($station);
            $this->command->info("  ✓ Created station: {$station['name']}");
        }
    }

    // =========================================================================
    //  Free Shipping Rules
    //
    //  Nairobi free shipping is ACTIVE with min_order_amount = 0, meaning
    //  every order qualifies regardless of basket size. Both methods get
    //  their own rule so the resolver can match on method + zone.
    //
    //  No max_weight cap — the free delivery promise is unconditional for now.
    //  Set max_weight when you want to exclude heavy shipments from the offer.
    //
    //  The nationwide rule is kept INACTIVE as a placeholder for future use.
    // =========================================================================

    // =========================================================================
    //  Greater Nairobi Extended Areas
    //
    //  Specific areas in neighbouring counties (Machakos, Kajiado, Kiambu)
    //  that fall within Sheffield's free-delivery catchment are assigned the
    //  NAIROBI shipping zone. This overrides the county's default UPCOUNTRY
    //  zone via area.shipping_zone_id, which Address::resolveShippingZone()
    //  checks first before falling back to the county zone.
    //
    //  To add more areas later: add a row to the relevant county array.
    //  To promote an entire county: update its shipping_zone_id directly.
    // =========================================================================

    private function assignNairobiExtendedAreas(array $zones): void
    {
        $nairobiZone = $zones['NAIROBI'];

        // Define the catchment by county → list of area names.
        // Names are matched case-insensitively; unmatched areas are created.
        $catchment = [
            'Machakos' => [
                'Athi River',
                'Mlolongo',
                'Syokimau',
            ],
            'Kajiado' => [
                'Ongata Rongai',
                'Kitengela',
                'Ngong',
            ],
            'Kiambu' => [
                'Ruiru',
                'Kamakis',
                'Two Rivers',
                'Ngoigwa',
                'Jomoko',
                'Tatu City',
            ],
        ];

        $updated = 0;
        $created = 0;

        foreach ($catchment as $countyName => $areaNames) {
            $county = County::where('name', $countyName)->first();

            if (! $county) {
                $this->command->warn("  ⚠ County not found: {$countyName} — skipping");

                continue;
            }

            foreach ($areaNames as $areaName) {
                $area = Area::where('county_id', $county->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($areaName)])
                    ->first();

                if ($area) {
                    $area->update(['shipping_zone_id' => $nairobiZone->id]);
                    $updated++;
                    $this->command->info("  ✓ {$county->name} → {$area->name} (zone overridden)");
                } else {
                    Area::create([
                        'name' => $areaName,
                        'county_id' => $county->id,
                        'shipping_zone_id' => $nairobiZone->id,
                    ]);
                    $created++;
                    $this->command->info("  + {$county->name} → {$areaName} (created & assigned)");
                }
            }
        }

        $this->command->info("📊 Greater Nairobi: {$updated} areas updated, {$created} areas created");
    }

    private function createFreeShippingRules(array $zones, array $methods): void
    {
        // Standard delivery — free for all Nairobi orders, always on
        FreeShippingRule::create([
            'name' => 'Nairobi Free Delivery — Standard',
            'shipping_zone_id' => $zones['NAIROBI']->id,
            'shipping_method_id' => $methods['standard']->id,
            'min_order_amount' => 0,
            'max_weight' => null,
            'starts_at' => null,
            'ends_at' => null,
            'status' => FreeShippingRuleStatus::ACTIVE->value,
        ]);

        // Pickup station — free for all Nairobi orders, always on
        FreeShippingRule::create([
            'name' => 'Nairobi Free Delivery — Pickup Station',
            'shipping_zone_id' => $zones['NAIROBI']->id,
            'shipping_method_id' => $methods['pickup']->id,
            'min_order_amount' => 0,
            'max_weight' => null,
            'starts_at' => null,
            'ends_at' => null,
            'status' => FreeShippingRuleStatus::ACTIVE->value,
        ]);

        // Placeholder — activate when expanding beyond Nairobi
        FreeShippingRule::create([
            'name' => 'Nationwide Free Shipping (placeholder)',
            'shipping_zone_id' => null,
            'shipping_method_id' => null,
            'min_order_amount' => 10000,
            'max_weight' => 20,
            'starts_at' => null,
            'ends_at' => null,
            'status' => FreeShippingRuleStatus::INACTIVE->value,
        ]);

        $this->command->info('  ✓ Created free shipping rules (Nairobi standard + pickup = active)');
    }
}
