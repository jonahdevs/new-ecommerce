<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Extracts unique brand names from products.json (no separate brands.json
     * yet) and creates one Brand per name, slugged and active.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/products.json');

        if (! File::exists($jsonPath)) {
            $this->command->error('products.json file not found at '.$jsonPath);

            return;
        }

        $data = json_decode(File::get($jsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Error parsing JSON: '.json_last_error_msg());

            return;
        }

        $names = collect($data)
            ->pluck('brand')
            ->filter()
            ->map(fn (string $name) => trim($name))
            ->unique()
            ->sort()
            ->values();

        $sortOrder = 0;

        foreach ($names as $name) {
            Brand::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
                'sort_order' => ++$sortOrder,
            ]);
        }
    }
}
