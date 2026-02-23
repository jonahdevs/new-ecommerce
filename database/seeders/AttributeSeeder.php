<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Color Attribute
        $color = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'watch_type' => 'color',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $colorValues = [
            ['value' => 'Black', 'slug' => 'black', 'label' => 'Black', 'color_code' => '#000000', 'sort_order' => 1],
            ['value' => 'White', 'slug' => 'white', 'label' => 'White', 'color_code' => '#FFFFFF', 'sort_order' => 2],
            ['value' => 'Red', 'slug' => 'red', 'label' => 'Red', 'color_code' => '#EF4444', 'sort_order' => 3],
            ['value' => 'Blue', 'slug' => 'blue', 'label' => 'Blue', 'color_code' => '#3B82F6', 'sort_order' => 4],
            ['value' => 'Green', 'slug' => 'green', 'label' => 'Green', 'color_code' => '#10B981', 'sort_order' => 5],
            ['value' => 'Yellow', 'slug' => 'yellow', 'label' => 'Yellow', 'color_code' => '#F59E0B', 'sort_order' => 6],
            ['value' => 'Purple', 'slug' => 'purple', 'label' => 'Purple', 'color_code' => '#8B5CF6', 'sort_order' => 7],
            ['value' => 'Pink', 'slug' => 'pink', 'label' => 'Pink', 'color_code' => '#EC4899', 'sort_order' => 8],
            ['value' => 'Gray', 'slug' => 'gray', 'label' => 'Gray', 'color_code' => '#6B7280', 'sort_order' => 9],
            ['value' => 'Brown', 'slug' => 'brown', 'label' => 'Brown', 'color_code' => '#92400E', 'sort_order' => 10],
        ];

        foreach ($colorValues as $value) {
            AttributeValue::create(array_merge($value, ['attribute_id' => $color->id]));
        }

        // Size Attribute
        $size = Attribute::create([
            'name' => 'Size',
            'slug' => 'size',
            'watch_type' => 'select',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $sizeValues = [
            ['value' => 'XS', 'slug' => 'xs', 'label' => 'Extra Small', 'sort_order' => 1],
            ['value' => 'S', 'slug' => 's', 'label' => 'Small', 'sort_order' => 2],
            ['value' => 'M', 'slug' => 'm', 'label' => 'Medium', 'sort_order' => 3],
            ['value' => 'L', 'slug' => 'l', 'label' => 'Large', 'sort_order' => 4],
            ['value' => 'XL', 'slug' => 'xl', 'label' => 'Extra Large', 'sort_order' => 5],
            ['value' => 'XXL', 'slug' => 'xxl', 'label' => '2XL', 'sort_order' => 6],
            ['value' => 'XXXL', 'slug' => 'xxxl', 'label' => '3XL', 'sort_order' => 7],
        ];

        foreach ($sizeValues as $value) {
            AttributeValue::create(array_merge($value, ['attribute_id' => $size->id]));
        }

        // Material Attribute
        $material = Attribute::create([
            'name' => 'Material',
            'slug' => 'material',
            'watch_type' => 'checkbox',
            'is_active' => false,
            'sort_order' => 3,
        ]);

        $materialValues = [
            ['value' => 'Cotton', 'slug' => 'cotton', 'label' => '100% Cotton', 'sort_order' => 1],
            ['value' => 'Polyester', 'slug' => 'polyester', 'label' => 'Polyester', 'sort_order' => 2],
            ['value' => 'Wool', 'slug' => 'wool', 'label' => 'Wool', 'sort_order' => 3],
            ['value' => 'Silk', 'slug' => 'silk', 'label' => 'Silk', 'sort_order' => 4],
            ['value' => 'Leather', 'slug' => 'leather', 'label' => 'Genuine Leather', 'sort_order' => 5],
            ['value' => 'Denim', 'slug' => 'denim', 'label' => 'Denim', 'sort_order' => 6],
            ['value' => 'Linen', 'slug' => 'linen', 'label' => 'Linen', 'sort_order' => 7],
            ['value' => 'Synthetic', 'slug' => 'synthetic', 'label' => 'Synthetic Blend', 'sort_order' => 8],
        ];

        foreach ($materialValues as $value) {
            AttributeValue::create(array_merge($value, ['attribute_id' => $material->id]));
        }

        // Style Attribute
        $style = Attribute::create([
            'name' => 'Style',
            'slug' => 'style',
            'watch_type' => 'radio',
            'is_active' => true,
            'sort_order' => 4,
        ]);

        $styleValues = [
            ['value' => 'Casual', 'slug' => 'casual', 'label' => 'Casual', 'sort_order' => 1],
            ['value' => 'Formal', 'slug' => 'formal', 'label' => 'Formal', 'sort_order' => 2],
            ['value' => 'Sport', 'slug' => 'sport', 'label' => 'Sport/Athletic', 'sort_order' => 3],
            ['value' => 'Vintage', 'slug' => 'vintage', 'label' => 'Vintage', 'sort_order' => 4],
            ['value' => 'Modern', 'slug' => 'modern', 'label' => 'Modern', 'sort_order' => 5],
            ['value' => 'Classic', 'slug' => 'classic', 'label' => 'Classic', 'sort_order' => 6],
        ];

        foreach ($styleValues as $value) {
            AttributeValue::create(array_merge($value, ['attribute_id' => $style->id]));
        }

        // Brand Origin Attribute
        $origin = Attribute::create([
            'name' => 'Brand Origin',
            'slug' => 'brand-origin',
            'watch_type' => 'select',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $originValues = [
            ['value' => 'USA', 'slug' => 'usa', 'label' => 'United States', 'sort_order' => 1],
            ['value' => 'UK', 'slug' => 'uk', 'label' => 'United Kingdom', 'sort_order' => 2],
            ['value' => 'Italy', 'slug' => 'italy', 'label' => 'Italy', 'sort_order' => 3],
            ['value' => 'France', 'slug' => 'france', 'label' => 'France', 'sort_order' => 4],
            ['value' => 'Germany', 'slug' => 'germany', 'label' => 'Germany', 'sort_order' => 5],
            ['value' => 'Japan', 'slug' => 'japan', 'label' => 'Japan', 'sort_order' => 6],
            ['value' => 'China', 'slug' => 'china', 'label' => 'China', 'sort_order' => 7],
            ['value' => 'Other', 'slug' => 'other', 'label' => 'Other', 'sort_order' => 8],
        ];

        foreach ($originValues as $value) {
            AttributeValue::create(array_merge($value, ['attribute_id' => $origin->id]));
        }

        // Condition Attribute
        $condition = Attribute::create([
            'name' => 'Condition',
            'slug' => 'condition',
            'watch_type' => 'radio',
            'is_active' => true,
            'sort_order' => 6,
        ]);

        $conditionValues = [
            ['value' => 'New', 'slug' => 'new', 'label' => 'Brand New', 'sort_order' => 1],
            ['value' => 'Like New', 'slug' => 'like-new', 'label' => 'Like New', 'sort_order' => 2],
            ['value' => 'Good', 'slug' => 'good', 'label' => 'Good Condition', 'sort_order' => 3],
            ['value' => 'Fair', 'slug' => 'fair', 'label' => 'Fair Condition', 'sort_order' => 4],
            ['value' => 'Refurbished', 'slug' => 'refurbished', 'label' => 'Refurbished', 'sort_order' => 5],
        ];

        foreach ($conditionValues as $value) {
            AttributeValue::create(array_merge($value, ['attribute_id' => $condition->id]));
        }
    }
}
