<?php

namespace Database\Seeders;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributes = [
            [
                'name' => 'Color',
                'slug' => 'color',
                'type' => AttributeType::COLOR,
                'values' => [
                    ['value' => 'red', 'label' => 'Red', 'color_code' => '#EF4444'],
                    ['value' => 'blue', 'label' => 'Blue', 'color_code' => '#3B82F6'],
                    ['value' => 'green', 'label' => 'Green', 'color_code' => '#22C55E'],
                ],
            ],
            [
                'name' => 'Size',
                'slug' => 'size',
                'type' => AttributeType::SELECT,
                'values' => [
                    ['value' => 's', 'label' => 'Small'],
                    ['value' => 'm', 'label' => 'Medium'],
                    ['value' => 'l', 'label' => 'Large'],
                ],
            ],
        ];

        foreach ($attributes as $attrIndex => $attr) {
            $attribute = Attribute::create([
                'name' => $attr['name'],
                'slug' => $attr['slug'],
                'type' => $attr['type'],
                'is_active' => true,
                'sort_order' => $attrIndex + 1,
            ]);

            foreach ($attr['values'] as $valueIndex => $value) {
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value' => $value['value'],
                    'label' => $value['label'],
                    'slug' => Str::slug($value['value']),
                    'color_code' => $value['color_code'] ?? null,
                    'is_active' => true,
                    'sort_order' => $valueIndex + 1,
                ]);
            }
        }
    }
}
