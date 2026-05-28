<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Session-backed cart and wishlist for guests (and authed users until we move
 * them into the DB). Cart shape: ['slug' => qty]. Wishlist shape: ['slug', ...].
 */
final class StorefrontSession
{
    private const CART_KEY = 'cart';

    private const WISHLIST_KEY = 'wishlist';

    private const COMPARE_KEY = 'compare';

    // ─── Cart ────────────────────────────────────────────────────────────────

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
     * Eager-loaded line items with their Product. Lines are skipped if the
     * product was deleted/hidden since being added.
     *
     * @return Collection<int, array{slug: string, qty: int, product: Product, line_total_cents: int}>
     */
    public static function cartLines(): Collection
    {
        $cart = self::cart();
        if ($cart === []) {
            return collect();
        }

        $products = Product::query()
            ->with(['brand', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->whereIn('slug', array_keys($cart))
            ->where('visibility', 'visible')
            ->get()
            ->keyBy('slug');

        return collect($cart)
            ->map(fn ($qty, $slug) => $products->has($slug) ? [
                'slug' => $slug,
                'qty' => (int) $qty,
                'product' => $products[$slug],
                'line_total_cents' => ($products[$slug]->sale_price ?? $products[$slug]->price ?? 0) * $qty,
            ] : null)
            ->filter()
            ->values();
    }

    public static function cartSubtotalCents(): int
    {
        return self::cartLines()->sum('line_total_cents');
    }

    public static function addToCart(string $slug, int $qty = 1): void
    {
        $cart = self::cart();
        $cart[$slug] = ($cart[$slug] ?? 0) + max(1, $qty);
        Session::put(self::CART_KEY, $cart);
    }

    public static function setCartQty(string $slug, int $qty): void
    {
        $cart = self::cart();
        if ($qty <= 0) {
            unset($cart[$slug]);
        } else {
            $cart[$slug] = $qty;
        }
        Session::put(self::CART_KEY, $cart);
    }

    public static function removeFromCart(string $slug): void
    {
        $cart = self::cart();
        unset($cart[$slug]);
        Session::put(self::CART_KEY, $cart);
    }

    public static function clearCart(): void
    {
        Session::forget(self::CART_KEY);
    }

    // ─── Wishlist ────────────────────────────────────────────────────────────

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

    // ─── Compare ─────────────────────────────────────────────────────────────

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
