<?php

namespace App\Observers;

use App\Models\Product;
use App\Settings\LocalizationSettings;

class ProductObserver
{
    /**
     * Snapshot the current store-wide weight/dimension units onto the product
     * so later changes to those settings never reinterpret stored measurements.
     * Only stamps units left unset, and never runs on update — preserving the
     * units a product was created under.
     */
    public function creating(Product $product): void
    {
        $settings = app(LocalizationSettings::class);

        $product->weight_unit ??= $settings->weight_unit;
        $product->dimension_unit ??= $settings->dimension_unit;
    }

    public function saved(Product $product): void
    {
        $this->syncPrimaryCategoryIntoPivot($product);
    }

    /**
     * Ensure the product's primary_category_id is one of its attached
     * categories. Without this the FK can point at a category the product
     * does not actually belong to, breaking breadcrumbs and faceted nav.
     */
    private function syncPrimaryCategoryIntoPivot(Product $product): void
    {
        if ($product->primary_category_id === null) {
            return;
        }

        $product->categories()->syncWithoutDetaching([
            $product->primary_category_id => ['sort_order' => 0],
        ]);
    }
}
