<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Session-backed cart and wishlist for guests (and authed users until we move
 * them into the DB).
 *
 * Cart shape: ['lineKey' => qty]. A line key is the product slug for a simple
 * line, or "{slug}|{variantId}" when a specific variant was chosen (the pipe
 * never appears in a kebab-case slug). Wishlist/compare stay slug-only.
 */
final class StorefrontSession
{
    private const CART_KEY = 'cart';

    private const VARIANT_SEPARATOR = '|';

    private const WISHLIST_KEY = 'wishlist';

    private const COMPARE_KEY = 'compare';

    // ==================================================
    // CART
    // ==================================================

    /** @return array<string, int> */
    public static function cart(): array
    {
        return Session::get(self::CART_KEY, []);
    }

    public static function cartCount(): int
    {
        return array_sum(self::cart());
    }

    /**
     * Split a cart line key into its product slug and optional variant id.
     *
     * @return array{slug: string, variantId: ?int}
     */
    public static function splitKey(string $key): array
    {
        if (! str_contains($key, self::VARIANT_SEPARATOR)) {
            return ['slug' => $key, 'variantId' => null];
        }

        [$slug, $variantId] = explode(self::VARIANT_SEPARATOR, $key, 2);

        return ['slug' => $slug, 'variantId' => is_numeric($variantId) ? (int) $variantId : null];
    }

    /** Build the cart line key for a slug (+ optional variant). */
    public static function lineKey(string $slug, ?int $variantId = null): string
    {
        return $variantId ? $slug.self::VARIANT_SEPARATOR.$variantId : $slug;
    }

    /**
     * Eager-loaded line items with their Product (and variant, when chosen).
     * Lines are skipped if the product/variant was deleted or hidden since
     * being added.
     *
     * @return Collection<int, array{key: string, slug: string, qty: int, product: Product, variant: ?ProductVariant, label: ?string, unit_price_cents: int, line_total_cents: int}>
     */
    public static function cartLines(): Collection
    {
        $cart = self::cart();
        if ($cart === []) {
            return collect();
        }

        $keys = collect($cart)->keys()->map(fn ($key) => self::splitKey($key));

        $products = Product::query()
            ->with(['brand', 'taxClass', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->whereIn('slug', $keys->pluck('slug')->unique()->all())
            ->where('visibility', 'visible')
            ->get()
            ->keyBy('slug');

        $variants = ProductVariant::query()
            ->with('attributeValues')
            ->whereIn('id', $keys->pluck('variantId')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        return collect($cart)
            ->map(function ($qty, $key) use ($products, $variants) {
                ['slug' => $slug, 'variantId' => $variantId] = self::splitKey($key);

                $product = $products->get($slug);
                if (! $product) {
                    return null;
                }

                $variant = $variantId ? $variants->get($variantId) : null;
                if ($variantId && ! $variant) {
                    return null;
                }

                $unit = $variant
                    ? (int) ($variant->compare_at_price ?? $variant->price ?? 0)
                    : (int) ($product->sale_price ?? $product->price ?? 0);

                $label = $variant
                    ? $variant->attributeValues->map(fn ($v) => $v->label ?: $v->value)->filter()->implode(' / ')
                    : null;

                return [
                    'key' => $key,
                    'slug' => $slug,
                    'qty' => (int) $qty,
                    'product' => $product,
                    'variant' => $variant,
                    'label' => $label ?: null,
                    'unit_price_cents' => $unit,
                    'line_total_cents' => $unit * (int) $qty,
                ];
            })
            ->filter()
            ->values();
    }

    public static function cartSubtotalCents(): int
    {
        return self::cartLines()->sum('line_total_cents');
    }

    public static function cartQuantity(string $key): int
    {
        return (int) (self::cart()[$key] ?? 0);
    }

    public static function addToCart(string $slug, int $qty = 1, ?int $variantId = null): void
    {
        $key = self::lineKey($slug, $variantId);
        $cart = self::cart();
        $cart[$key] = ($cart[$key] ?? 0) + max(1, $qty);
        Session::put(self::CART_KEY, $cart);
    }

    public static function setCartQty(string $key, int $qty): void
    {
        $cart = self::cart();
        if ($qty <= 0) {
            unset($cart[$key]);
        } else {
            $cart[$key] = $qty;
        }
        Session::put(self::CART_KEY, $cart);
    }

    public static function removeFromCart(string $key): void
    {
        $cart = self::cart();
        unset($cart[$key]);
        Session::put(self::CART_KEY, $cart);
    }

    public static function clearCart(): void
    {
        Session::forget(self::CART_KEY);
    }

    // ==================================================
    // WISHLIST
    // ==================================================

    /** @return array<int, string> */
    public static function wishlist(): array
    {
        return Session::get(self::WISHLIST_KEY, []);
    }

    public static function wishlistCount(): int
    {
        return count(self::wishlist());
    }

    public static function isWishlisted(string $slug): bool
    {
        return in_array($slug, self::wishlist(), true);
    }

    /** @return EloquentCollection<int, Product> Products preserved in saved-order. */
    public static function wishlistProducts(): EloquentCollection
    {
        $slugs = self::wishlist();
        if ($slugs === []) {
            /** @var EloquentCollection<int, Product> */
            return new EloquentCollection;
        }

        $products = Product::query()
            ->with(['brand', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->whereIn('slug', $slugs)
            ->where('visibility', 'visible')
            ->get()
            ->keyBy('slug');

        /** @var EloquentCollection<int, Product> */
        return new EloquentCollection(
            collect($slugs)
                ->map(fn ($slug) => $products->get($slug))
                ->filter()
                ->values()
                ->all()
        );
    }

    /** Returns whether the slug is now in the wishlist after toggling. */
    public static function toggleWishlist(string $slug): bool
    {
        $list = self::wishlist();
        if (in_array($slug, $list, true)) {
            $list = array_values(array_diff($list, [$slug]));
            Session::put(self::WISHLIST_KEY, $list);

            return false;
        }
        $list[] = $slug;
        Session::put(self::WISHLIST_KEY, $list);

        return true;
    }

    public static function removeFromWishlist(string $slug): void
    {
        $list = array_values(array_diff(self::wishlist(), [$slug]));
        Session::put(self::WISHLIST_KEY, $list);
    }

    public static function clearWishlist(): void
    {
        Session::forget(self::WISHLIST_KEY);
    }

    // ==================================================
    // COMPARE
    // ==================================================

    private const COMPARE_MAX = 4;

    /** @return array<int, string> */
    public static function compare(): array
    {
        return Session::get(self::COMPARE_KEY, []);
    }

    public static function compareCount(): int
    {
        return count(self::compare());
    }

    public static function isCompared(string $slug): bool
    {
        return in_array($slug, self::compare(), true);
    }

    /** Returns whether the slug is now in the compare list after toggling. */
    public static function toggleCompare(string $slug): bool
    {
        $list = self::compare();
        if (in_array($slug, $list, true)) {
            $list = array_values(array_diff($list, [$slug]));
            Session::put(self::COMPARE_KEY, $list);

            return false;
        }
        // Hard cap at 4 — silently drop the oldest if we'd exceed it.
        $list[] = $slug;
        if (count($list) > self::COMPARE_MAX) {
            $list = array_slice($list, -self::COMPARE_MAX);
        }
        Session::put(self::COMPARE_KEY, $list);

        return true;
    }

    public static function removeFromCompare(string $slug): void
    {
        $list = array_values(array_diff(self::compare(), [$slug]));
        Session::put(self::COMPARE_KEY, $list);
    }

    public static function clearCompare(): void
    {
        Session::forget(self::COMPARE_KEY);
    }

    /** @return EloquentCollection<int, Product> Products preserved in saved-order, eager-loaded for the compare table. */
    public static function compareProducts(): EloquentCollection
    {
        $slugs = self::compare();
        if ($slugs === []) {
            /** @var EloquentCollection<int, Product> */
            return new EloquentCollection;
        }

        $products = Product::query()
            ->with([
                'brand',
                'primaryCategory',
                'images' => fn ($q) => $q->where('is_cover', true)->limit(1),
                'productAttributes' => fn ($q) => $q->where('is_visible', true)->orderBy('sort_order'),
                'productAttributes.attribute',
            ])
            ->whereIn('slug', $slugs)
            ->where('visibility', 'visible')
            ->get()
            ->keyBy('slug');

        /** @var EloquentCollection<int, Product> */
        return new EloquentCollection(
            collect($slugs)
                ->map(fn ($slug) => $products->get($slug))
                ->filter()
                ->values()
                ->all()
        );
    }
}
