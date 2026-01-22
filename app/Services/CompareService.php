<?php

namespace App\Services;

use App\Models\Product;

/**
 * Class CompareService.
 */
class CompareService
{
        /**
     * Maximum number of products that can be compared
     */
    private const MAX_COMPARE_ITEMS = 3;

  /**
     * Check if product is in comparison
     */
    public function has(int $productId): bool
    {
        $compare = session('compare', []);

        return in_array($productId, $compare);
    }

        /**
     * Add product to comparison
     */
    public function add(int $productId): array
    {
        try {
            // Verify product exists
            $product = Product::findOrFail($productId);

            $compare = session('compare', []);

            // Check if already in comparison
            if (\in_array($productId, $compare, true)) {
                return [
                    'success' => false,
                    'message' => 'Product is already in comparison',
                    'count' => \count($compare),
                ];
            }

            // Check limit
            if (\count($compare) >= self::MAX_COMPARE_ITEMS) {
                throw new \RuntimeException('You can only compare up to 3 products at once. Please remove one to add another.');
            }

            // Check category restriction
            if (! empty($compare)) {
                $firstProductId = $compare[0];
                $firstProduct = Product::with('categories')->find($firstProductId);

                if ($firstProduct) {
                    $firstCategories = $firstProduct->categories->pluck('id')->toArray();
                    $newCategories = $product->categories->pluck('id')->toArray();

                    $hasCommonCategory = ! empty(array_intersect($firstCategories, $newCategories));

                    if (! $hasCommonCategory) {
                        throw new \RuntimeException('Products must be from the same category to compare. This product is from a different category.');
                    }
                }
            }

            // Add to comparison
            $compare[] = $productId;
            session()->put('compare', $compare);

            return [
                'success' => true,
                'message' => 'Added to comparison',
                'count' => \count($compare),
            ];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new \RuntimeException('Product not found');
        } catch (\RuntimeException $e) {
            // Re-throw runtime exceptions (limit, category mismatch)
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error adding to comparison', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Unable to add to comparison. Please try again.');
        }
    }

    /**
     * Remove product from comparison
     */
    public function remove(int $productId): bool
    {
        try {
            $compare = session('compare', []);
            if (\in_array($productId, $compare, true)) {
                $compare = array_values(array_diff($compare, [$productId]));
                session()->put('compare', $compare);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            \Log::error('Error removing from comparison', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Unable to remove from comparison. Please try again.');
        }
    }

    /**
     * Toggle product in comparison (add if not present, remove if present)
     * Returns true if added, false if removed
     */
    public function toggle(int $productId): bool
    {
        try {
            if ($this->has($productId)) {
                $this->remove($productId);

                return false; // Removed
            }

            $result = $this->add($productId);

            return $result['success']; // Added

        } catch (\Exception $e) {
            // Re-throw with context
            throw $e;
        }
    }
}
