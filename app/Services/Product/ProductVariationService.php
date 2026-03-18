<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductVariationService
{
    public function save(Product $product, array $variants, array $variantsToDelete = []): void
    {
        $this->deleteRemovedVariants($variantsToDelete);

        foreach ($variants as $index => $variant) {
            $savedVariant = $this->saveVariant($product, $variant, $index);

            // Sync variant downloads if product is downloadable
            if ($product->is_downloadable && $savedVariant) {
                app(ProductDownloadService::class)->sync(
                    product: $product,
                    downloads: $variant['downloads'] ?? [],
                    downloadsToDelete: $variant['downloads_to_delete'] ?? [],
                    variantId: $savedVariant->id,
                );
            }
        }
    }

    // -----------------------------------------------
    // Delete removed variants + their images
    // -----------------------------------------------

    private function deleteRemovedVariants(array $variantsToDelete): void
    {
        if (empty($variantsToDelete))
            return;

        ProductVariant::whereIn('id', $variantsToDelete)
            ->get(['id', 'image_path'])
            ->each(function (ProductVariant $variant) {
                if ($variant->image_path) {
                    Storage::disk('public')->delete($variant->image_path);
                }
            });

        ProductVariant::whereIn('id', $variantsToDelete)->delete();
    }

    // -----------------------------------------------
    // Save individual variant
    // -----------------------------------------------

    private function saveVariant(Product $product, array $variant, int $index): ?ProductVariant
    {
        $variantData = [
            'name'                   => $variant['name'],
            'sku'                    => !empty($variant['sku']) ? $variant['sku'] : null,
            'price'                  => $variant['price'],
            'sale_price'             => $variant['sale_price'],
            'manage_stock'           => (bool) $variant['manage_stock'],
            'stock_quantity'         => $variant['stock_quantity'],
            'stock_status'           => $variant['stock_status'],
            'allow_backorders'       => $variant['allow_backorders'] === '1'
                ? true
                : ($variant['allow_backorders'] === '' ? null : (bool) $variant['allow_backorders']),
            'max_backorder_quantity' => $variant['max_backorder_quantity'],
            'expected_restock_date'  => $variant['expected_restock_date'],
            'backorder_message'      => $variant['backorder_message'],
            'low_stock_threshold'    => $variant['low_stock_threshold'],
            'weight'                 => $variant['weight'],
            'length'                 => $variant['length'],
            'width'                  => $variant['width'],
            'height'                 => $variant['height'],
            'description'            => $variant['description'],
            'is_active'              => (bool) $variant['is_active'],
            'is_default'             => (bool) $variant['is_default'],
            'sort_order'             => $index,
            'attributes'             => $variant['attributes'],
        ];

        // Handle image — store new file first, delete old only on success
        if (!empty($variant['image'])) {
            $newPath = $variant['image']->store('products/variants', 'public');

            if ($newPath) {
                if (!empty($variant['image_path'])) {
                    Storage::disk('public')->delete($variant['image_path']);
                }
                $variantData['image_path'] = $newPath;
            }
        }

        // Resolve existing variant using three strategies in priority order:
        //
        // 1. Explicit ID from Livewire state — most reliable when state is intact.
        //
        // 2. attribute_hash lookup — fallback when the ID was lost from Livewire's
        //    snapshot. Matches on product_id + attribute_hash which is unique per
        //    variant combination. Prevents duplicate rows on repeated saves.
        //    Skipped for manually-added variants (hash prefixed with 'manual_')
        //    since those have no stable attribute combination to match against.
        //
        // 3. No match — genuinely new variant, insert a fresh row.
        $savedVariant = null;

        if (!empty($variant['id'])) {
            $savedVariant = ProductVariant::find($variant['id']);
        }

        if (
            !$savedVariant
            && !empty($variant['attribute_hash'])
            && !str_starts_with((string) $variant['attribute_hash'], 'manual_')
        ) {
            $savedVariant = ProductVariant::where('product_id', $product->id)
                ->where('attribute_hash', $variant['attribute_hash'])
                ->first();
        }

        if ($savedVariant) {
            $savedVariant->update($variantData);
        } else {
            $variantData['product_id']      = $product->id;
            $variantData['attribute_hash']  = !str_starts_with((string) ($variant['attribute_hash'] ?? ''), 'manual_')
                ? ($variant['attribute_hash'] ?? null)
                : null; // manual_ hashes are UI-only, never persisted
            $savedVariant = ProductVariant::create($variantData);
        }

        if ($savedVariant && !empty($variant['attribute_value_ids'])) {
            $savedVariant->attributeValues()->sync($variant['attribute_value_ids']);
        }

        return $savedVariant;
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

    private function generateSku(Product $product): string
    {
        $base = strtoupper(
            ($product->sku ?: 'VAR') . '-' . Str::random(6)
        );

        $sku = $base;
        $counter = 1;

        while (ProductVariant::where('sku', $sku)->exists()) {
            $sku = $base . '-' . $counter++;
        }

        return $sku;
    }
}
