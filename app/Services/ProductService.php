<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;

/**
 * Class ProductService.
 */
class ProductService
{
    public function recommendedProducts(string $type, array $context = [], int $limit = 8)
    {
        return match ($type) {
            'similar' => $this->similarProducts($context['product'], $limit),
            'bought_together' => $this->boughtTogether($context['product'], $limit),
            // 'recently_reviewed' => $this->recentlyReviewed($limit),
            // 'cart_related' => $this->fromCart($context['cart'], $limit),
            default => collect(),
        };
    }

    protected function similarProducts(Product $product, int $limit)
    {
        $relatedProducts = collect();

        // 1. Try same category products first
        if ($product->categories->isNotEmpty()) {
            $categoryIds = $product->categories->pluck('id')->toArray();

            $categoryProducts = Product::whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
                ->where('id', '!=', $product->id)
                ->where('is_active', true)
                ->where('status', 'published')
                ->inRandomOrder()
                ->limit($limit * 2)
                ->get();

            $relatedProducts = $relatedProducts->merge($categoryProducts);
        }

        // 2. Add same brand products if needed
        if ($relatedProducts->count() < $limit && $product->brand_id) {
            $brandProducts = Product::where('brand_id', $product->brand_id)
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->where('is_active', true)
                ->where('status', 'published')
                ->inRandomOrder()
                ->limit($limit)
                ->get();

            $relatedProducts = $relatedProducts->merge($brandProducts);
        }

        // 3. Add products with similar price range if still needed
        if ($relatedProducts->count() < $limit) {
            // Use sale_price if available, otherwise use regular price
            $basePrice = $product->sale_price ?? $product->price;

            if ($basePrice > 0) {
                $priceMin = $basePrice * 0.7; // -30%
                $priceMax = $basePrice * 1.3; // +30%

                $similarPriceProducts = Product::where(function ($query) use ($priceMin, $priceMax) {
                    $query->whereBetween('sale_price', [$priceMin, $priceMax])
                        ->orWhereBetween('price', [$priceMin, $priceMax]);
                })
                    ->where('id', '!=', $product->id)
                    ->whereNotIn('id', $relatedProducts->pluck('id'))
                    ->where('is_active', true)
                    ->where('status', 'published')
                    ->inRandomOrder()
                    ->limit($limit)
                    ->get();

                $relatedProducts = $relatedProducts->merge($similarPriceProducts);
            }
        }

        // 4. If still not enough, get any active published products
        if ($relatedProducts->count() < $limit) {
            $anyProducts = Product::where('id', '!=', $product->id)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->where('is_active', true)
                ->where('status', 'published')
                ->inRandomOrder()
                ->limit($limit)
                ->get();

            $relatedProducts = $relatedProducts->merge($anyProducts);
        }

        // 5. Apply tag-based scoring to prioritize products with matching tags
        if ($product->tags && is_array($product->tags)) {
            $relatedProducts = $relatedProducts->map(function ($product) {
                $matchingTags = 0;
                if ($product->tags && is_array($product->tags)) {
                    $matchingTags = count(array_intersect($product->tags, $product->tags));
                }
                $product->tag_match_score = $matchingTags;

                return $product;
            })->sortByDesc('tag_match_score');
        }

        // Return unique products up to the limit
        return $relatedProducts->unique('id')->take($limit);
    }

    protected function boughtTogether(CartItem $cartItems, int $limit)
    {
        if ($cartItems->isEmpty()) {
            return collect();
        }

        $cartProductIds = $cartItems->pluck('product_id')->toArray();
        $relatedProducts = collect();

        // Get related products for each cart item
        foreach ($cartItems as $cartItem) {
            if ($cartItem->product) {
                $productRelated = $cartItem->product->getRelatedProducts($limit);
                $relatedProducts = $relatedProducts->merge($productRelated);
            }
        }

        // Remove duplicates and products already in cart
        return $relatedProducts
            ->unique('id')
            ->whereNotIn('id', $cartProductIds)
            ->take($limit);
    }



}
