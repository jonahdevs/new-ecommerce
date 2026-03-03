<?php

namespace App\Services\Product;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductAttributeService
{
    public function save(Product $product, array $selectedAttributes): void
    {
        $syncData = [];

        foreach ($selectedAttributes as $index => $attr) {
            $attribute = $this->resolveAttribute($attr);

            if (!$attribute) continue;

            $valueIds = $this->resolveAttributeValues($attribute, $attr);

            $syncData[$attribute->id] = [
                'is_variation_attribute' => $attr['is_variation_attribute'] ?? false,
                'is_visible'             => $attr['is_visible'] ?? true,
                'sort_order'             => $index,
                'values'                 => json_encode($valueIds),
            ];

            $this->syncProductAttributeValues($product, $attribute, $valueIds);
        }

        $product->attributes()->sync($syncData);
    }

    // -----------------------------------------------
    // Resolve or create the attribute
    // -----------------------------------------------

    private function resolveAttribute(array $attr): ?Attribute
    {
        // Existing global attribute
        if (!empty($attr['attribute_id'])) {
            return Attribute::find($attr['attribute_id']);
        }

        // New attribute being created inline
        if (!empty($attr['name'])) {
            return Attribute::firstOrCreate(
                ['slug' => Str::slug($attr['name'])],
                ['name' => $attr['name'], 'is_active' => true]
            );
        }

        return null;
    }

    // -----------------------------------------------
    // Resolve or create attribute values
    // -----------------------------------------------

    private function resolveAttributeValues(Attribute $attribute, array $attr): array
    {
        // New attribute — values come as pipe-separated string
        if ($attr['is_new'] ?? false) {
            return $this->resolveNewAttributeValues($attribute, $attr['values'] ?? '');
        }

        // Existing attribute — values come as array of IDs
        return is_array($attr['values']) ? $attr['values'] : [];
    }

    private function resolveNewAttributeValues(Attribute $attribute, string $rawValues): array
    {
        if (empty($rawValues)) return [];

        $valueIds = [];

        foreach (explode('|', $rawValues) as $val) {
            $val = trim($val);
            if (empty($val)) continue;

            $attrValue = AttributeValue::firstOrCreate(
                [
                    'attribute_id' => $attribute->id,
                    'slug'         => Str::slug($val),
                ],
                [
                    'value'  => $val,
                    'label'  => $val,
                    'slug'   => Str::slug($val),
                ]
            );

            $valueIds[] = $attrValue->id;
        }

        return $valueIds;
    }

    // -----------------------------------------------
    // Sync product_attribute_values pivot
    // -----------------------------------------------

    private function syncProductAttributeValues(Product $product, Attribute $attribute, array $valueIds): void
    {
        // Remove old values for this attribute
        $product->attributeValues()
            ->wherePivotIn(
                'attribute_value_id',
                AttributeValue::where('attribute_id', $attribute->id)->pluck('id')
            )
            ->detach();

        // Attach new values
        if (!empty($valueIds)) {
            $product->attributeValues()->attach($valueIds);
        }
    }
}
