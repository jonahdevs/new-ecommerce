<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\County;
use App\Models\CountyBoundary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CountyCoordinatesSeeder extends Seeder
{
    // =========================================================================
    // Kenya county GeoJSON — public dataset from the Kenya Data Portal.
    // Each feature's geometry is a Polygon or MultiPolygon for one county.
    // The "COUNTY_NAM" property holds the county name in uppercase.
    // =========================================================================
    const GEOJSON_URL = 'https://raw.githubusercontent.com/mikelmaron/kenya-election-data/master/data/counties.geojson';

    // =========================================================================
    // Static center coordinates for known Kenyan towns/areas.
    // Keyed by lowercase town name for fuzzy matching.
    // Source: manually curated from OpenStreetMap data.
    // Add more entries as you expand your areas list.
    // =========================================================================
    private array $townCoordinates = [
        // Nairobi areas
        'nairobi cbd'       => [-1.2921,  36.8219],
        'westlands'         => [-1.2637,  36.8025],
        'eastleigh'         => [-1.2748,  36.8509],
        'kasarani'          => [-1.2215,  36.8975],
        'embakasi'          => [-1.3192,  36.8906],
        'kibera'            => [-1.3133,  36.7839],
        'langata'           => [-1.3340,  36.7465],
        'dagoretti'         => [-1.2996,  36.7382],
        'karen'             => [-1.3185,  36.7017],
        'runda'             => [-1.2107,  36.7955],
        'kilimani'          => [-1.2891,  36.7847],
        'lavington'         => [-1.2832,  36.7732],
        'parklands'         => [-1.2613,  36.8171],
        'south b'           => [-1.3106,  36.8322],
        'south c'           => [-1.3171,  36.8223],
        'donholm'           => [-1.2964,  36.8804],
        'umoja'             => [-1.2831,  36.8918],
        'kayole'            => [-1.2700,  36.9106],
        'dandora'           => [-1.2498,  36.9007],
        'mathare'           => [-1.2606,  36.8596],

        // Mombasa
        'mombasa cbd'       => [-4.0435,  39.6682],
        'nyali'             => [-4.0168,  39.7108],
        'bamburi'           => [-3.9833,  39.7167],
        'likoni'            => [-4.0833,  39.6667],
        'changamwe'         => [-4.0333,  39.6500],
        'kisauni'           => [-4.0000,  39.7167],
        'port reitz'        => [-4.0500,  39.6167],

        // Nakuru
        'nakuru town'       => [-0.3031,  36.0800],
        'naivasha'          => [-0.7167,  36.4333],
        'gilgil'            => [-0.4945,  36.3225],
        'molo'              => [-0.2500,  35.7333],
        'njoro'             => [-0.3333,  35.9500],
        'rongai'            => [-0.1667,  35.8500],

        // Kisumu
        'kisumu city'       => [-0.1022,  34.7617],
        'ahero'             => [-0.1667,  34.9167],
        'maseno'            => [-0.0000,  34.6000],
        'kondele'           => [-0.1167,  34.7667],

        // Eldoret / Uasin Gishu
        'eldoret'           => [0.5200,   35.2697],
        'burnt forest'      => [0.6333,   35.3833],
        'turbo'             => [0.6167,   35.0500],
        'ziwa'              => [0.7000,   35.1833],

        // Meru
        'meru town'         => [0.0470,   37.6490],
        'maua'              => [0.2333,   37.9333],
        'mikinduri'         => [0.2000,   37.7333],
        'nkubu'             => [0.0167,   37.7000],

        // Machakos
        'machakos town'     => [-1.5167,  37.2667],
        'athi river'        => [-1.4564,  36.9822],
        'kangundo'          => [-1.3833,  37.2333],
        'mlolongo'          => [-1.4000,  36.9500],
        'tala'              => [-1.3333,  37.3667],

        // Kiambu
        'thika'             => [-1.0396,  37.0900],
        'kikuyu'            => [-1.2500,  36.6667],
        'ruiru'             => [-1.1461,  36.9603],
        'limuru'            => [-1.1079,  36.6420],
        'kiambu town'       => [-1.1717,  36.8350],
        'juja'              => [-1.1014,  37.0147],
        'karuri'            => [-1.1833,  36.7667],
        'githunguri'        => [-1.0667,  36.7167],
        'gatundu'           => [-0.9996,  36.9230],

        // Kajiado
        'kajiado town'      => [-1.8500,  36.7833],
        'ngong'             => [-1.3667,  36.6333],
        'kiserian'          => [-1.4000,  36.6667],
        'ongata rongai'     => [-1.3939,  36.7444],
        'kitengela'         => [-1.4762,  36.9608],
        'namanga'           => [-2.5500,  36.7833],

        // Nyeri
        'nyeri town'        => [-0.4167,  36.9500],
        'karatina'          => [-0.4833,  37.1167],
        'othaya'            => [-0.5833,  36.9167],
        'mukurweini'        => [-0.7167,  37.0500],

        // Kakamega
        'kakamega town'     => [0.2833,   34.7500],
        'mumias'            => [0.3333,   34.4833],
        'butere'            => [0.2000,   34.4833],
        'malava'            => [0.4500,   34.8667],
        'lurambi'           => [0.3167,   34.7500],

        // Garissa
        'garissa town'      => [-0.4532,  39.6461],
        'dadaab'            => [0.0667,   40.3167],
        'modogashe'         => [0.9833,   38.5667],

        // Kisii
        'kisii town'        => [-0.6817,  34.7667],
        'ogembo'            => [-0.8000,  34.7167],
        'keroka'            => [-0.8167,  34.9667],
        'suneka'            => [-0.7500,  34.8167],

        // Nakuru (additional)
        'narok town'        => [-1.0833,  35.8667],

        // Bungoma
        'bungoma town'      => [0.5667,   34.5603],
        'webuye'            => [0.6167,   34.7667],
        'kimilili'          => [0.7833,   34.7167],
        'chwele'            => [0.7167,   34.5667],

        // Kwale
        'kwale'             => [-4.1740,  39.4524],
        'ukunda'            => [-4.2833,  39.5667],
        'msambweni'         => [-4.4700,  39.4800],
        'kinango'           => [-4.1333,  39.3167],
        'diani'             => [-4.2833,  39.5833],

        // Kilifi
        'kilifi'            => [-3.6305,  39.8499],
        'malindi'           => [-3.2175,  40.1169],
        'watamu'            => [-3.3667,  40.0167],
        'mtwapa'            => [-3.9500,  39.7333],
        'mariakani'         => [-3.8667,  39.4667],

        // Embu
        'embu town'         => [-0.5333,  37.4500],
        'siakago'           => [-0.7000,  37.6333],
        'runyenjes'         => [-0.4167,  37.5667],

        // Kitui
        'kitui town'        => [-1.3667,  38.0167],
        'mwingi'            => [-0.9333,  38.0667],
        'mutomo'            => [-1.8500,  38.2000],

        // Turkana
        'lodwar'            => [3.1197,   35.5973],
        'kakuma'            => [3.7167,   34.8500],
        'lokichoggio'       => [4.2167,   34.3500],

        // Marsabit
        'marsabit town'     => [2.3333,   37.9833],
        'moyale'            => [3.5333,   39.0500],
        'laisamis'          => [1.5833,   37.8000],

        // Lodwar / Nanyuki / others
        'nanyuki'           => [0.0167,   37.0667],
        'nyahururu'         => [-0.0333,  36.3667],
        'rumuruti'          => [0.2667,   36.5333],

        // Kericho
        'kericho town'      => [-0.3667,  35.2833],
        'litein'            => [-0.5833,  35.2500],
        'londiani'          => [-0.1500,  35.6000],
        'kipkelion'         => [-0.2000,  35.4500],

        // Bomet
        'bomet town'        => [-0.7833,  35.3500],
        'sotik'             => [-0.6833,  35.1167],
        'longisa'           => [-0.7000,  35.2167],

        // Homa Bay
        'homa bay town'     => [-0.5167,  34.4500],
        'ndhiwa'            => [-0.7167,  34.5833],
        'oyugis'            => [-0.5500,  34.7500],
        'kendu bay'         => [-0.3667,  34.6333],

        // Migori
        'migori town'       => [-1.0636,  34.4731],
        'rongo'             => [-0.9833,  34.5833],
        'awendo'            => [-0.8500,  34.6167],
        'kehancha'          => [-1.2500,  34.8833],

        // Siaya
        'siaya town'        => [0.0608,   34.2883],
        'bondo'             => [-0.3333,  34.2667],
        'ugunja'            => [0.0667,   34.3000],
        'yala'              => [0.1000,   34.5333],

        // Nyamira
        'nyamira town'      => [-0.5667,  34.9333],
        'kebirigo'          => [-0.6667,  34.9833],
        'ekerenyo'          => [-0.6333,  35.0167],

        // Busia
        'busia town'        => [0.4608,   34.1108],
        'malaba'            => [0.6333,   34.2833],
        'bumala'            => [0.4167,   34.2333],

        // Vihiga
        'mbale'             => [0.1667,   34.6833],
        'hamisi'            => [0.1167,   34.7667],
        'chavakali'         => [0.1833,   34.8000],

        // Trans Nzoia
        'kitale'            => [1.0153,   35.0062],
        'endebess'          => [1.1500,   35.0667],
        'kwanza'            => [0.9833,   35.2667],

        // West Pokot
        'kapenguria'        => [1.2333,   35.1167],
        'makutano'          => [1.2000,   35.1833],
        'chepareria'        => [1.4167,   35.2167],

        // Samburu
        'maralal'           => [1.1000,   36.7000],
        'baragoi'           => [1.7833,   36.7833],
        'wamba'             => [0.9667,   37.3167],

        // Baringo
        'kabarnet'          => [0.4833,   35.7500],
        'marigat'           => [0.4667,   35.9833],
        'eldama ravine'     => [0.0500,   35.7167],

        // Laikipia
        'rumuruti'          => [0.2667,   36.5333],

        // Tana River
        'hola'              => [-1.5000,  40.0333],
        'garsen'            => [-2.2667,  40.1167],
        'bura'              => [-1.1000,  39.9500],

        // Lamu
        'lamu town'         => [-2.2694,  40.9022],
        'mpeketoni'         => [-2.0500,  40.7000],
        'witu'              => [-2.3833,  40.4500],

        // Taita Taveta
        'voi'               => [-3.3961,  38.5564],
        'wundanyi'          => [-3.3833,  38.3500],
        'mwatate'           => [-3.5000,  38.3667],
        'taveta'            => [-3.3897,  37.6814],

        // Wajir
        'wajir town'        => [1.7500,   40.0667],
        'habaswein'         => [1.0167,   39.5000],
        'buna'              => [2.8000,   39.5333],

        // Mandera
        'mandera town'      => [3.9372,   41.8669],
        'elwak'             => [2.6833,   40.9333],
        'rhamu'             => [3.9333,   41.1333],

        // Isiolo
        'isiolo town'       => [0.3542,   37.5822],
        'garbatulla'        => [0.4667,   38.5333],
        'merti'             => [1.0833,   38.7000],

        // Tharaka Nithi
        'chuka'             => [-0.3333,  37.6500],
        'kathwana'          => [-0.2167,  37.8167],
        'marimanti'         => [0.1333,   37.8667],

        // Nyandarua
        'ol kalou'          => [-0.2667,  36.3833],
        'engineer'          => [-0.8833,  36.6333],

        // Makueni
        'wote'              => [-1.7833,  37.6333],
        'makindu'           => [-2.2833,  37.8333],
        'emali'             => [-2.0833,  37.4667],
        'sultan hamud'      => [-2.0500,  37.3500],

        // Murang'a
        "murang'a town"     => [-0.7167,  37.1500],
        'kenol'             => [-0.9167,  37.2000],
        'kangema'           => [-0.7500,  36.9833],
        'kandara'           => [-0.8833,  37.0000],

        // Kirinyaga
        'kerugoya'          => [-0.5000,  37.2833],
        'kutus'             => [-0.5167,  37.4667],
        'sagana'            => [-0.6667,  37.2167],
        'baricho'           => [-0.6333,  37.4000],

        // Elgeyo Marakwet
        'iten'              => [0.6717,   35.5086],
        'kapcherop'         => [0.7833,   35.5167],
        'chesoi'            => [0.9167,   35.5667],

        // Nandi
        'kapsabet'          => [0.2014,   35.0997],
        'mosoriot'          => [0.3000,   35.1167],
        'nandi hills'       => [0.1000,   35.1833],
    ];

    public function run(): void
    {
        $this->command->info('🗺️  Starting County Coordinates Seeder...');

        DB::beginTransaction();

        try {
            $this->command->info('⬇️  Downloading Kenya county GeoJSON...');
            $geojson = $this->fetchGeoJson();

            if (!$geojson) {
                $this->command->error('❌ Failed to download GeoJSON. Aborting.');
                return;
            }

            $this->command->info('📍 Seeding county centers and boundaries...');
            $this->seedCountyBoundaries($geojson);

            $this->command->info('🏘️  Seeding area center coordinates...');
            $this->seedAreaCoordinates();

            DB::commit();
            $this->command->info('✅ County coordinates seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    // =========================================================================
    // GEOJSON FETCH
    // =========================================================================

    private function fetchGeoJson(): ?array
    {
        try {
            $response = Http::timeout(30)->get(self::GEOJSON_URL);

            if (!$response->successful()) {
                $this->command->error("  HTTP {$response->status()} fetching GeoJSON");
                return null;
            }

            $data = $response->json();

            if (!isset($data['features']) || !is_array($data['features'])) {
                $this->command->error('  GeoJSON missing features array');
                return null;
            }

            $this->command->info('  ✓ Downloaded ' . count($data['features']) . ' features');
            return $data;
        } catch (\Exception $e) {
            $this->command->error('  Exception: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // COUNTY BOUNDARIES + CENTERS
    // =========================================================================

    private function seedCountyBoundaries(array $geojson): void
    {
        $matched = 0;
        $skipped = 0;

        foreach ($geojson['features'] as $feature) {
            // Try common property keys for county name
            $props     = $feature['properties'] ?? [];
            $rawName   = $props['COUNTY_NAM']
                ?? $props['county_name']
                ?? $props['NAME_1']
                ?? $props['name']
                ?? null;

            if (!$rawName) {
                $skipped++;
                continue;
            }

            // Normalise — GeoJSON names are often uppercase
            $normalised = $this->normaliseCountyName($rawName);

            $county = County::whereRaw('LOWER(name) = ?', [strtolower($normalised)])->first();

            if (!$county) {
                $this->command->warn("  ⚠ No county match for: {$rawName}");
                $skipped++;
                continue;
            }

            $geometry = $feature['geometry'] ?? null;

            if (!$geometry) {
                $skipped++;
                continue;
            }

            // Compute centroid and bounding box from the polygon
            [$centroidLat, $centroidLng] = $this->computeCentroid($geometry);
            [$minLat, $maxLat, $minLng, $maxLng] = $this->computeBoundingBox($geometry);

            // Update county with center coordinates
            $county->update([
                'lat_center' => $centroidLat,
                'lng_center' => $centroidLng,
            ]);

            // Upsert county boundary
            CountyBoundary::updateOrCreate(
                ['county_id' => $county->id],
                [
                    'geojson'      => json_encode($geometry),
                    'bbox_min_lat' => $minLat,
                    'bbox_max_lat' => $maxLat,
                    'bbox_min_lng' => $minLng,
                    'bbox_max_lng' => $maxLng,
                ]
            );

            $matched++;
            $this->command->info("  ✓ {$county->name} — center ({$centroidLat}, {$centroidLng})");
        }

        $this->command->info("📊 {$matched} counties matched, {$skipped} skipped");
    }

    // =========================================================================
    // AREA COORDINATES
    // =========================================================================

    private function seedAreaCoordinates(): void
    {
        $areas   = Area::whereNull('lat_center')->get();
        $seeded  = 0;
        $missing = 0;

        foreach ($areas as $area) {
            $key    = strtolower(trim($area->name));
            $coords = $this->townCoordinates[$key] ?? null;

            // Fallback: try partial match (e.g. "Nairobi CBD" → "nairobi cbd")
            if (!$coords) {
                foreach ($this->townCoordinates as $townKey => $townCoords) {
                    if (str_contains($key, $townKey) || str_contains($townKey, $key)) {
                        $coords = $townCoords;
                        break;
                    }
                }
            }

            // Fallback: use parent county center
            if (!$coords && $area->county?->lat_center) {
                $coords = [$area->county->lat_center, $area->county->lng_center];
                $this->command->warn("  ⚠ {$area->name} — using county center as fallback");
            }

            if ($coords) {
                $area->update([
                    'lat_center' => $coords[0],
                    'lng_center' => $coords[1],
                ]);
                $seeded++;
            } else {
                $this->command->warn("  ✗ No coordinates found for area: {$area->name}");
                $missing++;
            }
        }

        $this->command->info("📊 {$seeded} areas seeded, {$missing} missing coordinates");

        if ($missing > 0) {
            $this->command->warn("  → Add missing towns to \$townCoordinates in this seeder");
        }
    }

    // =========================================================================
    // GEOMETRY HELPERS
    // =========================================================================

    /**
     * Compute the centroid of a GeoJSON geometry (Polygon or MultiPolygon).
     * Returns [lat, lng].
     */
    private function computeCentroid(array $geometry): array
    {
        $coords = $this->extractCoordinates($geometry);

        if (empty($coords)) {
            return [0, 0];
        }

        $latSum = 0;
        $lngSum = 0;
        $count  = count($coords);

        foreach ($coords as [$lng, $lat]) {
            $latSum += $lat;
            $lngSum += $lng;
        }

        return [
            round($latSum / $count, 7),
            round($lngSum / $count, 7),
        ];
    }

    /**
     * Compute bounding box [minLat, maxLat, minLng, maxLng].
     */
    private function computeBoundingBox(array $geometry): array
    {
        $coords = $this->extractCoordinates($geometry);

        if (empty($coords)) {
            return [0, 0, 0, 0];
        }

        $lats = array_column($coords, 1);
        $lngs = array_column($coords, 0);

        return [
            round(min($lats), 7),
            round(max($lats), 7),
            round(min($lngs), 7),
            round(max($lngs), 7),
        ];
    }

    /**
     * Flatten all coordinate pairs from a Polygon or MultiPolygon geometry.
     * GeoJSON coordinates are [lng, lat] — we return them as-is for column extraction.
     */
    private function extractCoordinates(array $geometry): array
    {
        $type   = $geometry['type'] ?? '';
        $coords = $geometry['coordinates'] ?? [];
        $flat   = [];

        if ($type === 'Polygon') {
            // coordinates[0] = outer ring
            foreach ($coords[0] ?? [] as $point) {
                $flat[] = $point;
            }
        } elseif ($type === 'MultiPolygon') {
            foreach ($coords as $polygon) {
                foreach ($polygon[0] ?? [] as $point) {
                    $flat[] = $point;
                }
            }
        }

        return $flat;
    }

    /**
     * Normalise county names from GeoJSON to match your counties table.
     * GeoJSON sources often use ALL CAPS or different spellings.
     */
    private function normaliseCountyName(string $name): string
    {
        $name = ucwords(strtolower(trim($name)));

        // Known overrides for spelling differences between GeoJSON and your data
        $overrides = [
            "Murang'A"        => "Murang'a",
            'Tharaka-Nithi'   => 'Tharaka Nithi',
            'Elgeyo-Marakwet' => 'Elgeyo Marakwet',
            'Trans-Nzoia'     => 'Trans Nzoia',
            'Homa Bay'        => 'Homa Bay',
            'Taita Taveta'    => 'Taita Taveta',
        ];

        return $overrides[$name] ?? $name;
    }
}
