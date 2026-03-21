<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductService
{
    public function recommend(string $type, array $context = [], int $limit = 8)
    {
        return match ($type) {
            'similar'         => $this->similarProducts($context['product'], $limit),
            'cross_sell'      => $this->crossSells($context['product'], $limit),
            'up_sells'        => $this->upSells($context['product'], $limit),
            'recently_viewed' => $this->recentlyViewed($limit),
            'cart_related'    => $this->fromCart($limit),
            default           => collect(),
        };
    }

    // -----------------------------------------------------------------------
    // Cross-sells — shown on the cart page
    // Pulled from explicitly linked cross-sell relationships first,
    // then falls back to cart-related products if not enough
    // -----------------------------------------------------------------------

    protected function crossSells(Product $product, int $limit): \Illuminate\Support\Collection
    {
        $crossSells = $product->crossSells()
            ->select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description', 'type', 'requires_quotation', 'reviews_enabled'])
            ->with([
                'brand:id,name,slug',
                'images' => fn($q) => $q->limit(1),
                'variants' => fn($q) => $q
                    ->where('is_active', true)
                    ->whereNotNull('price')
                    ->select(['id', 'product_id', 'price', 'sale_price', 'is_active']),
            ])
            ->withAvg('reviews', 'rating')
            ->active()
            ->orderByPivot('sort_order')
            ->limit($limit)
            ->get();

        // Pad with similar products if not enough explicit cross-sells
        if ($crossSells->count() < $limit) {
            $pad = $this->similarProducts($product, $limit)
                ->whereNotIn('id', $crossSells->pluck('id'));

            $crossSells = $crossSells->merge($pad)->take($limit);
        }

        return $crossSells;
    }

    // -----------------------------------------------------------------------
    // Upsells — shown on the product details page
    // Pulled from explicitly linked upsell relationships first,
    // then falls back to similar products if not enough
    // -----------------------------------------------------------------------

    protected function upSells(Product $product, int $limit): \Illuminate\Support\Collection
    {
        $upSells = $product->upsells()
            ->select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description', 'type', 'requires_quotation', 'reviews_enabled'])
            ->with([
                'brand:id,name,slug',
                'images' => fn($q) => $q->limit(1),
                'variants' => fn($q) => $q
                    ->where('is_active', true)
                    ->whereNotNull('price')
                    ->select(['id', 'product_id', 'price', 'sale_price', 'is_active']),
            ])
            ->withAvg('reviews', 'rating')
            ->active()
            ->orderByPivot('sort_order')
            ->limit($limit)
            ->get();

        // Pad with similar products if not enough explicit upsells
        if ($upSells->count() < $limit) {
            $pad = $this->similarProducts($product, $limit)
                ->whereNotIn('id', $upSells->pluck('id'));

            $upSells = $upSells->merge($pad)->take($limit);
        }

        return $upSells;
    }

    // -----------------------------------------------------------------------
    // Similar products — algorithmic fallback used by cross-sells + upsells
    // -----------------------------------------------------------------------

    protected function similarProducts(Product $product, int $limit): \Illuminate\Support\Collection
    {
        $relatedProducts = collect();

        // 1. Same category
        if ($product->categories->isNotEmpty()) {
            $categoryIds = $product->categories->pluck('id')->toArray();

            $categoryProducts = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description', 'type', 'requires_quotation', 'reviews_enabled'])
                ->with([
                    'brand:id,name,slug',
                    'images' => fn($q) => $q->limit(1),
                    'variants' => fn($q) => $q
                        ->where('is_active', true)
                        ->whereNotNull('price')
                        ->select(['id', 'product_id', 'price', 'sale_price', 'is_active']),
                ])
                ->withAvg('reviews', 'rating')
                ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
                ->where('id', '!=', $product->id)
                ->active()
                ->inRandomOrder()
                ->limit($limit * 2)
                ->get();

            $relatedProducts = $relatedProducts->merge($categoryProducts);
        }

        // 2. Same brand
        if ($relatedProducts->count() < $limit && $product->brand_id) {
            $brandProducts = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description', 'type', 'requires_quotation', 'reviews_enabled'])
                ->with([
                    'brand:id,name,slug',
                    'images' => fn($q) => $q->limit(1),
                    'variants' => fn($q) => $q
                        ->where('is_active', true)
                        ->whereNotNull('price')
                        ->select(['id', 'product_id', 'price', 'sale_price', 'is_active']),
                ])
                ->withAvg('reviews', 'rating')
                ->where('brand_id', $product->brand_id)
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->active()
                ->inRandomOrder()
                ->limit($limit)
                ->get();

            $relatedProducts = $relatedProducts->merge($brandProducts);
        }

        // 3. Similar price range (±30%)
        if ($relatedProducts->count() < $limit) {
            $basePrice = $product->sale_price ?? $product->price;

            if ($basePrice > 0) {
                $priceMin = $basePrice * 0.7;
                $priceMax = $basePrice * 1.3;

                $priceProducts = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description'])
                    ->withAvg('reviews', 'rating')
                    ->with(['brand:id,name'])
                    ->where(
                        fn($q) => $q
                            ->whereBetween('sale_price', [$priceMin, $priceMax])
                            ->orWhereBetween('price', [$priceMin, $priceMax])
                    )
                    ->where('id', '!=', $product->id)
                    ->whereNotIn('id', $relatedProducts->pluck('id'))
                    ->active()
                    ->inRandomOrder()
                    ->limit($limit)
                    ->get();

                $relatedProducts = $relatedProducts->merge($priceProducts);
            }
        }

        // 4. Any active products as last resort
        if ($relatedProducts->count() < $limit) {
            $anyProducts = Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path', 'short_description', 'type', 'requires_quotation', 'reviews_enabled'])
                ->with([
                    'brand:id,name,slug',
                    'images' => fn($q) => $q->limit(1),
                    'variants' => fn($q) => $q
                        ->where('is_active', true)
                        ->whereNotNull('price')
                        ->select(['id', 'product_id', 'price', 'sale_price', 'is_active']),
                ])
                ->withAvg('reviews', 'rating')
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->active()
                ->inRandomOrder()
                ->limit($limit)
                ->get();

            $relatedProducts = $relatedProducts->merge($anyProducts);
        }

        return $relatedProducts->unique('id')->take($limit);
    }

    // -----------------------------------------------------------------------
    // Cart-related — shown on cart page for items currently in cart
    // -----------------------------------------------------------------------

    protected function fromCart(int $limit): \Illuminate\Support\Collection
    {
        $cart      = app(CartService::class)->getCart();
        $cartItems = $cart->items()->with('product')->get();

        if ($cartItems->isEmpty()) return collect();

        $cartProductIds  = $cartItems->pluck('product_id')->toArray();
        $relatedProducts = collect();

        foreach ($cartItems as $cartItem) {
            if (!$cartItem->product) continue;

            // Explicit cross-sells first
            $crossSells = $cartItem->product->crossSells()
                ->select(['products.id', 'products.name', 'products.slug', 'products.brand_id', 'products.price', 'products.sale_price', 'products.image_path', 'products.short_description'])
                ->withAvg('reviews', 'rating')
                ->with(['brand:id,name'])
                ->active()
                ->orderByPivot('sort_order')
                ->get();

            $relatedProducts = $relatedProducts->merge($crossSells);

            // Algorithmic fallback per item
            if ($crossSells->isEmpty()) {
                $relatedProducts = $relatedProducts->merge(
                    $this->getRelatedProducts($cartItem->product, $limit)
                );
            }
        }

        return $relatedProducts
            ->unique('id')
            ->whereNotIn('id', $cartProductIds)
            ->take($limit);
    }

    // -----------------------------------------------------------------------
    // Generic related products — used by fromCart()
    // -----------------------------------------------------------------------

    protected function getRelatedProducts(Product $product, int $limit = 12): \Illuminate\Support\Collection
    {
        return $this->similarProducts($product, $limit);
    }

    // -----------------------------------------------------------------------
    // View tracking
    // -----------------------------------------------------------------------

    public function recordView(Product $product): void
    {
        $viewed = session()->get('viewed_product_ids', []);

        if (in_array($product->id, $viewed)) {
            return;
        }

        $product->increment('views_count');
        session()->push('viewed_product_ids', $product->id);
    }

    public function rememberRecentlyViewed(Product $product, int $limit = 12): void
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

    // -----------------------------------------------------------------------
    // Recently viewed
    // -----------------------------------------------------------------------

    public function recentlyViewed(int $limit = 8): \Illuminate\Support\Collection
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

    // -----------------------------------------------------------------------
    // Sync session recently viewed → DB on login
    // -----------------------------------------------------------------------

    public function syncRecentlyViewedOnLogin(int $limit = 12): void
    {
        if (!Auth::check()) {
            return;
        }

        $user       = Auth::user();
        $sessionIds = session()->get('recently_viewed_products', []);

        if (empty($sessionIds)) {
            return;
        }

        $existingViews = $user->recentlyViewedProducts()
            ->get(['products.id', 'recently_viewed_products.viewed_at'])
            ->keyBy('id');

        $syncData  = [];
        $timestamp = now();

        foreach ($sessionIds as $index => $productId) {
            $syncData[$productId] = [
                'viewed_at' => $existingViews->has($productId)
                    ? $existingViews[$productId]->pivot->viewed_at
                    : $timestamp->copy()->subSeconds(count($sessionIds) - $index),
            ];
        }

        $user->recentlyViewedProducts()->syncWithoutDetaching($syncData);

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

        session()->forget('recently_viewed_products');
    }
}
