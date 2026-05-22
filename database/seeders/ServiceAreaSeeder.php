<?php

namespace Database\Seeders;

use App\Enums\PickupStationStatus;
use App\Models\County;
use App\Models\LogisticsProvider;
use App\Models\PickupStation;
use App\Models\ShippingZone;
use App\Models\SubCounty;
use App\Models\Town;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Pulls satellite sub-counties into the within_nairobi zone via the
 * sub_county.shipping_zone_id override layer.
 *
 * By default ShippingSeeder assigns all non-Nairobi counties to upcountry.
 * This seeder overrides the sub-counties that are geographically part of the
 * Nairobi delivery ring even though they sit in neighbouring counties.
 *
 * Resolution at checkout: town → sub_county → county (each tier independent).
 *
 * To add a new satellite area:
 *   1. Add the sub-county name under $satelliteSubCounties (keyed by county).
 *   2. If only specific wards qualify, list them under $townOverrides.
 *   3. Re-run: php artisan db:seed --class=ServiceAreaSeeder
 */
class ServiceAreaSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Sub-counties that belong to the Within Nairobi delivery ring,
     * grouped by their parent county.
     *
     * Names must match the geoBoundaries ADM2 shapeName values seeded by
     * CountyCoordinatesSeeder (case-insensitive lookup used).
     *
     * @var array<string, list<string>>
     */
    private array $satelliteSubCounties = [
        'Kiambu' => [
            'Thika Town',  // Thika Town
            'Ruiru',       // Ruiru, Kamakis
            'Juja',        // Juja Farm, Kalimoni
            'Kikuyu',      // Kikuyu, Ondiri
            'Kiambu',      // Kiambu Town, Two Rivers area
        ],
        'Kajiado' => [
            'Kajiado North',  // Ongata Rongai, Ngong, Olekasasi
            'Kajiado East',   // Kitengela, Mlolongo (border), Kaputiei North
        ],
        'Machakos' => [
            // geoBoundaries uses "Mavoko" (historical IEBC name) for the
            // area locals know as Athi River — covers Syokimau, Mlolongo, EPZ.
            'Mavoko',
        ],
    ];

    /**
     * Ward-level overrides. Use when a sub-county straddles the boundary and
     * only specific wards qualify for the within_nairobi rate.
     *
     * Format: [county => [sub_county => [ward_name => zone_code]]]
     *
     * Example:
     *   'Kajiado' => [
     *       'Kajiado East' => [
     *           'Imaroro' => 'upcountry',  // too far south
     *       ],
     *   ],
     *
     * @var array<string, array<string, array<string, string>>>
     */
    private array $townOverrides = [
        // Add ward-level exceptions here as the operation tightens.
    ];

    public function run(): void
    {
        $withinNairobiZone = ShippingZone::where('code', 'within_nairobi')->first();

        if (! $withinNairobiZone) {
            $this->command->error('❌ within_nairobi zone not found. Run ShippingSeeder first.');

            return;
        }

        $this->command->info('🗺️  Assigning satellite sub-counties to Within Nairobi...');

        $subCountyCount = $this->assignSubCounties($withinNairobiZone->id);
        $townCount = $this->applyTownOverrides();
        $this->createPickupStation();

        $this->command->info("📊 Within Nairobi ring: {$subCountyCount} sub-counties + {$townCount} ward overrides");
    }

    /**
     * Single primary station — Syokimau (off Mombasa Road, near SGR terminus).
     * Created here (not in ShippingSeeder) because it references the Mavoko
     * sub-county, which is seeded by CountyCoordinatesSeeder.
     */
    private function createPickupStation(): void
    {
        $provider = LogisticsProvider::where('code', 'sheffield')->first();

        if (! $provider) {
            $this->command->warn('  ⚠ Logistics provider not found — skipping pickup station');

            return;
        }

        $machakos = County::whereRaw('LOWER(name) = ?', ['machakos'])->first();
        $mavoko = $machakos
            ? SubCounty::where('county_id', $machakos->id)
                ->whereRaw('LOWER(name) = ?', ['mavoko'])
                ->first()
            : null;

        if (! $machakos) {
            $this->command->warn('  ⚠ Machakos county not found — skipping pickup station');

            return;
        }

        $station = PickupStation::updateOrCreate(
            ['code' => 'syokimau'],
            [
                'name' => 'Sheffield Pickup — Syokimau',
                'logistics_provider_id' => $provider->id,
                'county_id' => $machakos->id,
                'sub_county_id' => $mavoko?->id,
                'address' => 'Off Mombasa Road, Syokimau (near SGR Terminus)',
                'phone' => '+254712345678',
                'operating_hours' => 'Mon–Fri: 8:00 AM – 8:00 PM · Sat: 8:00 AM – 1:00 PM · Sun: Closed',
                'holding_days' => 7,
                'latitude' => -1.3645,
                'longitude' => 36.9358,
                'status' => PickupStationStatus::ACTIVE->value,
                'is_primary' => true,
            ]
        );

        $this->command->info("  ✓ Station: {$station->name}");
    }

    private function assignSubCounties(int $zoneId): int
    {
        $assigned = 0;

        foreach ($this->satelliteSubCounties as $countyName => $subCountyNames) {
            $county = County::whereRaw('LOWER(name) = ?', [strtolower($countyName)])->first();

            if (! $county) {
                $this->command->warn("  ⚠ County not found: {$countyName}");

                continue;
            }

            foreach ($subCountyNames as $subCountyName) {
                $subCounty = SubCounty::where('county_id', $county->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($subCountyName)])
                    ->first();

                if (! $subCounty) {
                    $this->command->warn("  ⚠ Sub-county not found: {$subCountyName} in {$countyName}");

                    continue;
                }

                $subCounty->update(['shipping_zone_id' => $zoneId]);
                $assigned++;
                $this->command->info("  ✓ {$countyName} / {$subCountyName}");
            }
        }

        return $assigned;
    }

    private function applyTownOverrides(): int
    {
        if (empty($this->townOverrides)) {
            return 0;
        }

        $zoneIdByCode = ShippingZone::pluck('id', 'code')->all();
        $applied = 0;

        foreach ($this->townOverrides as $countyName => $subCountyMap) {
            $county = County::whereRaw('LOWER(name) = ?', [strtolower($countyName)])->first();

            if (! $county) {
                continue;
            }

            foreach ($subCountyMap as $subCountyName => $townMap) {
                $subCounty = SubCounty::where('county_id', $county->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($subCountyName)])
                    ->first();

                if (! $subCounty) {
                    continue;
                }

                foreach ($townMap as $townName => $zoneCode) {
                    $zoneId = $zoneIdByCode[$zoneCode] ?? null;

                    if (! $zoneId) {
                        $this->command->warn("  ⚠ Unknown zone code: {$zoneCode}");

                        continue;
                    }

                    $town = Town::where('sub_county_id', $subCounty->id)
                        ->whereRaw('LOWER(name) = ?', [strtolower($townName)])
                        ->first();

                    if (! $town) {
                        $this->command->warn("  ⚠ Ward not found: {$townName} in {$subCountyName}");

                        continue;
                    }

                    $town->update(['shipping_zone_id' => $zoneId]);
                    $applied++;
                    $this->command->info("  ✓ Override: {$townName} → {$zoneCode}");
                }
            }
        }

        return $applied;
    }
}
