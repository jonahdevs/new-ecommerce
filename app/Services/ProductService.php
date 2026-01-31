<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Class ProductService.
 */
class ProductService
{
    public function recommend(string $type, array $context = [], int $limit = 8)
    {
        return match ($type) {
            'similar' => $this->similarProducts($context['product'], $limit),
            // 'bought_together' => $this->boughtTogether($context['product'], $limit),
            'recently_viewed' => $this->recentlyViewed($limit),
            'cart_related' => $this->fromCart($limit),
            default => collect(),
        };
    }


    protected function similarProducts(Product $product, int $limit)
    {
        $relatedProducts = collect();

        // 1. Try same category products first
        if ($product->categories->isNotEmpty()) {
            $categoryIds = $product->categories->pluck('id')->toArray();

            $categoryProducts = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description'])
                ->withAvg('reviews', 'rating')
                ->with(['brand:id,name'])
                ->whereHas('categories', function ($query) use ($categoryIds) {
                    $query->whereIn('categories.id', $categoryIds);
                })
                ->where('id', '!=', $product->id)
                ->active()
                ->published()
                ->inRandomOrder()
                ->limit($limit * 2)
                ->get();

            $relatedProducts = $relatedProducts->merge($categoryProducts);
        }

        // 2. Add same brand products if needed
        if ($relatedProducts->count() < $limit && $product->brand_id) {
            $brandProducts = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description'])
                ->withAvg('reviews', 'rating')
                ->with(['brand:id,name'])
                ->where('brand_id', $product->brand_id)
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->active()
                ->published()
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

                $similarPriceProducts = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description'])
                    ->withAvg('reviews', 'rating')
                    ->with(['brand:id,name'])
                    ->where(function ($query) use ($priceMin, $priceMax) {
                        $query->whereBetween('sale_price', [$priceMin, $priceMax])
                            ->orWhereBetween('price', [$priceMin, $priceMax]);
                    })
                    ->where('id', '!=', $product->id)
                    ->whereNotIn('id', $relatedProducts->pluck('id'))
                    ->active()
                    ->published()
                    ->inRandomOrder()
                    ->limit($limit)
                    ->get();

                $relatedProducts = $relatedProducts->merge($similarPriceProducts);
            }
        }

        // 4. If still not enough, get any active published products
        if ($relatedProducts->count() < $limit) {
            $anyProducts = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description'])
                ->withAvg('reviews', 'rating')
                ->with(['brand:id,name'])
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->active()
                ->published()
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

    protected function fromCart(int $limit)
    {
        $cart = app(CartService::class)->getCart();
        $cartItems = $cart->items()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return collect();
        }

        $cartProductIds = $cartItems->pluck('product_id')->toArray();
        $relatedProducts = collect();

        // Get related products for each cart item
        foreach ($cartItems as $cartItem) {
            if ($cartItem->product) {
                $productRelated = $this->getRelatedProducts($cartItem->product, $limit);
                $relatedProducts = $relatedProducts->merge($productRelated);
            }
        }

        // Remove duplicates and products already in cart
        return $relatedProducts
            ->unique('id')
            ->whereNotIn('id', $cartProductIds)
            ->take($limit);
    }


    /**
     * Get related products using smart priority mix
     * Priority: Same category > Same brand > Similar price > Tag matching
     */
    protected function getRelatedProducts(Product $product, int $limit = 12)
    {
        $relatedProducts = collect();

        // 1. Try same category products first
        if ($product->categories->isNotEmpty()) {
            $categoryIds = $product->categories->pluck('id')->toArray();

            $categoryProducts = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description'])
                ->withAvg('reviews', 'rating')
                ->with(['brand:id,name'])
                ->whereHas('categories', function ($query) use ($categoryIds) {
                    $query->whereIn('categories.id', $categoryIds);
                })
                ->where('id', '!=', $product->id)
                ->active()
                ->published()
                ->inRandomOrder()
                ->limit($limit * 2)
                ->get();

            $relatedProducts = $relatedProducts->merge($categoryProducts);
        }

        // 2. Add same brand products if needed
        if ($relatedProducts->count() < $limit && $product->brand_id) {
            $brandProducts = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description'])
                ->withAvg('reviews', 'rating')
                ->with(['brand:id,name'])
                ->where('brand_id', $product->brand_id)
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->active()
                ->published()
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

                $similarPriceProducts = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description'])
                    ->withAvg('reviews', 'rating')
                    ->with(['brand:id,name'])
                    ->where(function ($query) use ($priceMin, $priceMax) {
                        $query->whereBetween('sale_price', [$priceMin, $priceMax])
                            ->orWhereBetween('price', [$priceMin, $priceMax]);
                    })
                    ->where('id', '!=', $product->id)
                    ->whereNotIn('id', $relatedProducts->pluck('id'))
                    ->active()
                    ->published()
                    ->inRandomOrder()
                    ->limit($limit)
                    ->get();

                $relatedProducts = $relatedProducts->merge($similarPriceProducts);
            }
        }

        // 4. If still not enough, get any active published products
        if ($relatedProducts->count() < $limit) {
            $anyProducts = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description'])
                ->withAvg('reviews', 'rating')
                ->with(['brand:id,name'])
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->active()
                ->published()
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

    public function recordView(Product $product): void
    {
        $viewed = session()->get('viewed_product_ids', []);

        if (in_array($product->id, $viewed)) {
            return;
        }

        // Record view
        $product->increment('views_count');

        // Mark as viewed
        session()->push('viewed_product_ids', $product->id);
    }


    public function rememberRecentlyViewed(Product $product, int $limit = 12)
    {
        if (!Auth::check()) {
            $viewed = collect(session()->get('recently_viewed_products', []));

            $viewed = $viewed->reject(fn($id) => $id === $product->id);
            $viewed->prepend($product->id);

            session()->put(
                'recently_viewed_products',
                $viewed->take($limit)->values()->toArray()
            );
            return;
        }

        $user = auth()->user();

        $user->recentlyViewedProducts()->syncWithoutDetaching([
            $product->id => ['viewed_at' => now()],
        ]);

        $totalCount = $user->recentlyViewedProducts()->count();

        if ($totalCount > $limit) {
            $idsToKeep = $user->recentlyViewedProducts()
                ->orderByDesc('recently_viewed_products.viewed_at')
                ->limit($limit)
                ->pluck('products.id');

            $user->recentlyViewedProducts()
                ->whereNotIn('product_id', $idsToKeep)
                ->detach();
        }
    }

    public function recentlyViewed(int $limit = 8)
    {
        if (auth()->check()) {
            $ids = auth()->user()
                ->recentlyViewedProducts()
                ->orderByDesc('recently_viewed_products.viewed_at')
                ->limit($limit)
                ->pluck('products.id')
                ->toArray();
        } else {
            $ids = session()->get('recently_viewed_products', []);
        }

        if (empty($ids)) {
            return collect();
        }

        return Product::select([
            'products.id',
            'products.name',
            'products.slug',
            'products.brand_id',
            'products.price',
            'products.sale_price',
            'products.image_path',
            'products.short_description',
        ])
            ->withAvg('reviews', 'rating')
            ->with(['brand:id,name'])
            ->active()
            ->whereIn('products.id', $ids)
            ->orderByRaw('FIELD(products.id, ' . implode(',', $ids) . ')')
            ->get();
    }

    public function syncRecentlyViewedOnLogin(int $limit = 12): void
    {
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Get session data
        $sessionIds = session()->get('recently_viewed_products', []);

        if (empty($sessionIds)) {
            return;
        }

        // Get existing database records with their timestamps
        $existingViews = $user->recentlyViewedProducts()
            ->get(['products.id', 'recently_viewed_products.viewed_at'])
            ->keyBy('id');

        // Prepare sync data
        $syncData = [];
        $timestamp = now();

        foreach ($sessionIds as $index => $productId) {
            // Use existing timestamp if already in DB, otherwise use descending timestamps
            // based on session order (most recent first)
            $syncData[$productId] = [
                'viewed_at' => $existingViews->has($productId)
                    ? $existingViews[$productId]->pivot->viewed_at
                    : $timestamp->copy()->subSeconds(count($sessionIds) - $index)
            ];
        }

        // Merge session data into database without detaching existing ones
        $user->recentlyViewedProducts()->syncWithoutDetaching($syncData);

        // Enforce limit
        $totalCount = $user->recentlyViewedProducts()->count();

        if ($totalCount > $limit) {
            $idsToKeep = $user->recentlyViewedProducts()
                ->orderByDesc('recently_viewed_products.viewed_at')
                ->limit($limit)
                ->pluck('products.id');

            $user->recentlyViewedProducts()
                ->whereNotIn('product_id', $idsToKeep)
                ->detach();
        }

        // Clear session data after successful sync
        session()->forget('recently_viewed_products');
    }
}
