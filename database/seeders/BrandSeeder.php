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

        $rows = json_decode(File::get($jsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('JSON parse error: '.json_last_error_msg());

            return;
        }

        $sortOrder = 0;

        foreach ($rows as $row) {
            Brand::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                    'logo' => $row['logo'] ?? null,
                    'website_url' => $row['website_url'] ?? null,
                    'is_active' => $row['is_active'] ?? true,
                    'sort_order' => ++$sortOrder,
                ]
            );
        }

        $this->command->info("Seeded {$sortOrder} brands from brands.json.");
    }
}
