<?php

namespace Database\Seeders;

use App\Enums\CategorySection;
use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Models\CategoryPlacement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $jsonPath = database_path('seeders/data/categories.json');

        if (! File::exists($jsonPath)) {
            $this->command->error("❌ JSON file not found: {$jsonPath}");

            return;
        }

        $data = json_decode(File::get($jsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('❌ Invalid JSON: '.json_last_error_msg());

            return;
        }

        // Track placement sort orders per section independently
        $placementOrders = [];

        foreach ($data as $categoryData) {
            $this->createCategory($categoryData, null, $placementOrders);
        }

        $this->command->info('✅ '.count($data).' categories seeded successfully.');
    }

    protected function createCategory(array $data, ?int $parentId, array &$placementOrders): Category
    {
        $category = Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'parent_id' => $parentId,
            'description' => $data['description'] ?? null,
            'image_path' => $data['image_path'] ?? null,
            'image_icon' => $data['image_icon'] ?? null,
            'icon_svg' => $data['icon_svg'] ?? null,
            'status' => CategoryStatus::ACTIVE,
            'sort_order' => $data['sort_order'] ?? 0,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
        ]);

        // Create placements — each section tracks its own sort_order
        foreach ($data['placements'] ?? [] as $sectionValue) {
            $section = CategorySection::from($sectionValue);

            // Auto-increment sort order per section
            $placementOrders[$sectionValue] = ($placementOrders[$sectionValue] ?? 0) + 1;

            CategoryPlacement::create([
                'category_id' => $category->id,
                'section' => $section,
                'sort_order' => $placementOrders[$sectionValue],
            ]);
        }

        // Recurse into subcategories if present
        foreach ($data['subcategories'] ?? [] as $subData) {
            $this->createCategory($subData, $category->id, $placementOrders);
        }

        return $category;
    }
}
