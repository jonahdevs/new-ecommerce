<?php

namespace Database\Seeders;

use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Models\CategoryPlacement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/categories.json');

        if (! File::exists($jsonPath)) {
            $this->command->error('categories.json file not found at '.$jsonPath);

            return;
        }

        $data = json_decode(File::get($jsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Error parsing JSON: '.json_last_error_msg());

            return;
        }

        // Track sort_order independently per placement location.
        $placementOrders = [];

        foreach ($data as $item) {
            $this->createCategory($item, null, $placementOrders);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $placementOrders
     */
    private function createCategory(array $data, ?int $parentId, array &$placementOrders): Category
    {
        $category = Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'parent_id' => $parentId,
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
            'icon' => $data['icon'] ?? null,
            'status' => CategoryStatus::ACTIVE,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        foreach ($data['placements'] ?? [] as $location) {
            $placementOrders[$location] = ($placementOrders[$location] ?? 0) + 1;

            CategoryPlacement::create([
                'category_id' => $category->id,
                'location' => $location,
                'sort_order' => $placementOrders[$location],
                'status' => CategoryStatus::ACTIVE,
            ]);
        }

        foreach ($data['children'] ?? [] as $child) {
            $this->createCategory($child, $category->id, $placementOrders);
        }

        return $category;
    }
}
