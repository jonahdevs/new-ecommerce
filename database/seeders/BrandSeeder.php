<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('data/brands.json');

        if (! File::exists($jsonPath)) {
            $this->command->error('brands.json not found at '.$jsonPath);

            return;
        }

        $decoded = json_decode(File::get($jsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('JSON parse error: '.json_last_error_msg());

            return;
        }

        // The export wraps the rows under a "data" key inside the table entry.
        $rows = collect($decoded)
            ->firstWhere('type', 'table')['data'] ?? [];

        if (empty($rows)) {
            $this->command->error('No brand rows found in brands.json.');

            return;
        }

        $sortOrder = 0;

        foreach ($rows as $row) {
            $website = $this->presence($row['website'] ?? null);
            $logo = $this->presence($row['main_image_path'] ?? null);

            Brand::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $this->presence($row['description'] ?? null),
                    'logo' => $logo,
                    'website_url' => $website,
                    'is_active' => (bool) ($row['is_published'] ?? true),
                    'sort_order' => ++$sortOrder,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ]
            );
        }

        $this->command->info("Seeded {$sortOrder} brands from brands.json.");
    }

    /** Return null for empty/whitespace strings so nullable columns stay clean. */
    private function presence(?string $value): ?string
    {
        if ($value === null || $value === 'null') {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
