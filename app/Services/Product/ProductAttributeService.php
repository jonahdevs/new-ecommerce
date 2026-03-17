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
        if (!empty($attr['attribute_id'])) {
            return Attribute::find($attr['attribute_id']);
        }

        if (!empty($attr['name'])) {
            $baseSlug = Str::slug($attr['name']);
            $slug     = $baseSlug;
            $counter  = 1;

            while (Attribute::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            return Attribute::firstOrCreate(
                ['slug' => $slug],
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

            $baseSlug = Str::slug($val);
            $slug     = $baseSlug;
            $counter  = 1;

            while (AttributeValue::where('attribute_id', $attribute->id)
                ->where('slug', $slug)
                ->exists()
            ) {
                $slug = $baseSlug . '-' . $counter++;
            }

            $attrValue = AttributeValue::firstOrCreate(
                [
                    'attribute_id' => $attribute->id,
                    'slug'         => $slug,
                ],
                [
                    'value' => $val,
                    'label' => $val,
                    'slug'  => $slug,
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
        // Get only the IDs currently attached to this product for this attribute
        $existingIds = $product->attributeValues()
            ->where('attribute_id', $attribute->id)
            ->pluck('attribute_values.id')
            ->toArray();

        if (!empty($existingIds)) {
            $product->attributeValues()->detach($existingIds);
        }

        if (!empty($valueIds)) {
            $product->attributeValues()->attach($valueIds);
        }
    }
}
