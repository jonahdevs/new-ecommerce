<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        //    Load JSON file
        $jsonPath = database_path('seeders/data/categories.json');

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

        foreach ($data['categories'] as $categoryData) {
            $this->createCategory($categoryData);
        }
    }

    /**
     * Create a category with its subcategory
     */
    protected function createCategory(array $categoryData)
    {
        $slug = Str::slug($categoryData['name']);

        $category = Category::create([
            'name' => $categoryData['name'],
            'slug' => $slug,
            'description' => $categoryData['description'],
            'image_path' => $categoryData['image_path'],
            'image_icon' => $categoryData['image_icon'] ?? null,
            'is_active' => true,
            'is_featured' => $categoryData['is_featured'] ?? false,
            'show_in_navbar' => $categoryData['show_in_navbar'] ?? false,
            'sort_order' => $categoryData['sort_order'],
            'meta_title' => $categoryData['meta_title'],
            'meta_description' => $categoryData['meta_description'],
            'meta_keywords' => $categoryData['meta_keywords'],
        ]);

        // Create subcategories if they exist
        if (isset($categoryData['subcategories']) && is_array($categoryData['subcategories'])) {
            foreach ($categoryData['subcategories'] as $subData) {
                $this->createSubcategory($category, $subData);
            }
        }
    }

    /**
     * Create a subcategory
     */
    protected function createSubcategory(Category $parent, array $subData)
    {
        Category::create([
            'parent_id' => $parent->id,
            'name' => $subData['name'],
            'slug' => Str::slug($subData['name']),
            'image_path' => $subData['image_path'] ?? null,
            'image_icon' => $subData['image_icon'] ?? null,
            'description' => $subData['description'],
            'sort_order' => $subData['sort_order'],
            'is_active' => true,
            'is_featured' => $subData['is_featured'] ?? false,
            'show_in_navbar' => $subData['show_in_navbar'] ?? false,
        ]);
    }
}
