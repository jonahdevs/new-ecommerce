<?php

namespace App\Observers;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Settings\LocalizationSettings;
use Illuminate\Support\Str;

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

        if (empty($product->slug)) {
            $product->slug = $this->uniqueSlug($product->name);
        }

        $this->resolvePublishedAt($product);
    }

    public function updating(Product $product): void
    {
        if ($product->isDirty('status')) {
            $this->resolvePublishedAt($product);
        }
    }

    private function uniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (Product::where('slug', $slug)->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function resolvePublishedAt(Product $product): void
    {
        match ($product->status) {
            ProductStatus::PUBLISHED => $product->published_at ??= now(),
            ProductStatus::DRAFT,
            ProductStatus::ARCHIVED => $product->published_at = null,
            default => null, // SCHEDULED: leave published_at as set by the user
        };
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
