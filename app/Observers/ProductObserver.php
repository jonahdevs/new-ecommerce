<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
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
