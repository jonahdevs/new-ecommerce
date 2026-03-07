<?php

namespace App\Services\Product;

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductVariationService
{
    public function save(Product $product, array $variants, array $variantsToDelete = []): void
    {
        // Handle deletions first
        $this->deleteRemovedVariants($variantsToDelete);

        // Save each variant
        foreach ($variants as $index => $variant) {
            $this->saveVariant($product, $variant, $index);
        }
    }

    // -----------------------------------------------
    // Delete removed variants
    // -----------------------------------------------

    private function deleteRemovedVariants(array $variantsToDelete): void
    {
        if (empty($variantsToDelete)) return;

        ProductVariant::whereIn('id', $variantsToDelete)->delete();
    }

    // -----------------------------------------------
    // Save individual variant
    // -----------------------------------------------

    private function saveVariant(Product $product, array $variant, int $index): void
    {
        $variantData = [
            'product_id'          => $product->id,
            'name'                => $variant['name'],
            'sku'                 => $variant['sku'] ?: $this->generateSku($product->id, $index),
            'price'               => $variant['price'],
            'sale_price'          => $variant['sale_price'],
            'manage_stock'        => $variant['manage_stock'],
            'stock_quantity'      => $variant['stock_quantity'],
            'stock_status'        => $variant['stock_status'],
            'allow_backorders'    => $variant['allow_backorders'],
            'low_stock_threshold' => $variant['low_stock_threshold'],
            'weight'              => $variant['weight'],
            'length'              => $variant['length'],
            'width'               => $variant['width'],
            'height'              => $variant['height'],
            'description'         => $variant['description'],
            'is_active'           => $variant['is_active'],
            'is_default'          => $variant['is_default'],
            'sort_order'          => $index,
            'attributes'          => $variant['attributes'],
        ];

        // Handle image upload
        if (!empty($variant['image'])) {
            // Delete old image if exists
            if (!empty($variant['image_path'])) {
                Storage::disk('public')->delete($variant['image_path']);
            }
            $variantData['image_path'] = $variant['image']->store('products/variants', 'public');
        }

        if (!empty($variant['id'])) {
            $savedVariant = ProductVariant::find($variant['id']);
            $savedVariant?->update($variantData);
        } else {
            $savedVariant = ProductVariant::create($variantData);
        }

        if (!empty($variant['attribute_value_ids'])) {
            $savedVariant->attributeValues()->sync($variant['attribute_value_ids']);
        }
    }

    // -----------------------------------------------
    // Deactivate / Reactivate all variants
    // -----------------------------------------------

    public function deactivateAll(int $productId): void
    {
        ProductVariant::where('product_id', $productId)
            ->update(['is_active' => false]);
    }

    public function reactivateAll(int $productId): void
    {
        ProductVariant::where('product_id', $productId)
            ->update(['is_active' => true]);
    }

    public function hasActiveVariants(int $productId): bool
    {
        return ProductVariant::where('product_id', $productId)
            ->where('is_active', true)
            ->exists();
    }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------

    private function generateSku(int $productId, int $index): string
    {
        return strtoupper('VAR-' . $productId . '-' . ($index + 1) . '-' . Str::random(4));
    }
}
