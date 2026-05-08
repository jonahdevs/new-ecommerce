<?php

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReviewHelpfulness;
use App\Services\CartService;
use App\Services\CompareService;
use App\Services\ProductService;
use App\Services\QuoteBasketService;
use App\Services\ReviewService;
use App\Services\WishlistService;
use Artesaos\SEOTools\Facades\JsonLd;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public Product $product;

    // ── Status flags
    public bool $wishlisted = false;

    public bool $inCompare = false;

    public bool $inCart = false;

    // ── UI state
    public string $accessoriesTab = 'accessories';

    public string $selectedTab = 'description';

    public int $reviewsToShow = 5;

    // ── Cart state ──
    public int $cartQuantity = 1;

    public ?int $cartItemId = null;

    public bool $inQuoteBasket = false;

    // ── Variant state ─
    // selectedAttributeValues: ['Color' => 'Red', 'Size' => 'Large']
    public array $selectedAttributeValues = [];

    public ?int $selectedVariantId = null;

    /** IDs of selected grouped items — all pre-selected by default */
    public array $selectedGroupedItems = [];

    // Grouped products
    public array $groupedQuantities = [];

    // =========================================================================
    // MOUNT
    // =========================================================================

    public function mount(Product $product, WishlistService $wishlist, CompareService $compareService, CartService $cartService): void
    {
        $productService = app(ProductService::class);
        $productService->recordView($product);
        $productService->rememberRecentlyViewed($product);

        // Base eager loads for all product types
        $product->load(['images', 'brand', 'crossSells' => fn ($q) => $q->active()->visible(), 'accessories' => fn ($q) => $q->active()->visible()->withPivot('sort_order', 'quantity')]);

        if ($product->type->value === 'grouped') {
            $product->load([
                'groupedProducts' => fn ($q) => $q->active()->visible()->withPivot('sort_order', 'quantity'),
            ]);
        }

        $product->loadAvg('reviews', 'rating');

        // Variable product — load all variants (active AND inactive/out-of-stock)
        // so we can show greyed-out out-of-stock buttons on the storefront
        if ($product->type->value === 'variable') {
            $product->load([
                // Load ALL variants, not just active — we filter display in the view
                'variants' => fn ($q) => $q->orderBy('sort_order'),
                'variants.attributeValues.attribute',
                // Only load variation attributes (not display-only attributes)
                'attributes' => fn ($q) => $q->wherePivot('is_variation_attribute', true),
            ]);

            // Pre-select the default variant or first available variant
            $defaultVariant = $product->variants->where('is_active', true)->firstWhere('is_default', true) ?? $product->variants->where('is_active', true)->first();

            if ($defaultVariant) {
                $this->selectedVariantId = $defaultVariant->id;
                $this->selectedAttributeValues = $defaultVariant->attributeValues->mapWithKeys(fn ($av) => [$av->attribute->name => $av->value])->toArray();
            }
        }

        $this->product = $product;

        // Grouped product — load items and pre-select all
        if ($product->type->value === 'grouped') {
            $product->load([
                'groupedProducts' => fn ($q) => $q->active()->visible()->withPivot('sort_order', 'quantity'),
            ]);

            // Pre-select all items and set default quantities from pivot
            foreach ($product->groupedProducts as $item) {
                $this->selectedGroupedItems[] = $item->id;
                $this->groupedQuantities[$item->id] = $item->pivot->quantity ?? 1;
            }
        }

        // Cart state
        $this->wishlisted = $wishlist->has($this->product->id);
        $this->inCompare = $compareService->has($this->product->id);

        $this->inQuoteBasket = app(QuoteBasketService::class)->has($product->id, $this->selectedVariantId);

        // Check cart state — for variable products check by variant ID
        if ($product->type->value === 'variable' && $this->selectedVariantId) {
            $this->inCart = $cartService->has($this->product->id, $this->selectedVariantId);
            if ($this->inCart) {
                $cartItem = $cartService->getCartItem($this->product->id, $this->selectedVariantId);
                if ($cartItem) {
                    $this->cartItemId = $cartItem->id;
                    $this->cartQuantity = $cartItem->quantity;
                }
            }
        } else {
            $this->inCart = $cartService->has($this->product->id);
            if ($this->inCart) {
                $cartItem = $cartService->getCartItem($this->product->id);
                if ($cartItem) {
                    $this->cartItemId = $cartItem->id;
                    $this->cartQuantity = $cartItem->quantity;
                }
            }
        }

        // SEO Implementation
        $this->setupSEO($product);
    }

    private function setupSEO(Product $product): void
    {
        $description = Str::limit(strip_tags($product->short_description ?? $product->name), 155);
        $keywords = [$product->name, 'commercial kitchen equipment'];

        if ($product->brand) {
            $keywords[] = $product->brand->name;
        }

        // Basic Meta
        SEOMeta::setTitle($product->name);
        SEOMeta::setDescription($description);
        SEOMeta::addKeyword($keywords);
        SEOMeta::setCanonical(route('products.show', $product->slug));

        // OpenGraph
        OpenGraph::setTitle($product->name);
        OpenGraph::setDescription($description);
        OpenGraph::setType('product');
        OpenGraph::setUrl(route('products.show', $product->slug));
        OpenGraph::addImage($product->image_url);
        OpenGraph::addProperty('product:price:amount', $product->final_price);
        OpenGraph::addProperty('product:price:currency', 'KES');

        if ($product->brand) {
            OpenGraph::addProperty('product:brand', $product->brand->name);
        }

        // Twitter Card
        TwitterCard::setType('summary_large_image');
        TwitterCard::setTitle($product->name);
        TwitterCard::setDescription($description);
        TwitterCard::setImage($product->image_url);

        // JSON-LD Product Schema
        JsonLd::setType('Product');
        JsonLd::setTitle($product->name);
        JsonLd::setDescription($description);
        JsonLd::setImage($product->image_url);

        if ($product->brand) {
            JsonLd::addValue('brand', [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ]);
        }

        JsonLd::addValue('offers', [
            '@type' => 'Offer',
            'price' => $product->final_price,
            'priceCurrency' => 'KES',
            'availability' => $product->stock_status === 'in_stock' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => route('products.show', $product->slug),
            'seller' => ['@type' => 'Organization', 'name' => config('app.name')],
        ]);

        if ($product->average_rating && $product->reviews_count > 0) {
            JsonLd::addValue('aggregateRating', [
                '@type' => 'AggregateRating',
                'ratingValue' => $product->average_rating,
                'reviewCount' => $product->reviews_count,
                'bestRating' => 5,
                'worstRating' => 1,
            ]);
        }

        if ($product->sku) {
            JsonLd::addValue('sku', $product->sku);
        }
    }

    // Grouped products
    #[Computed(persist: true)]
    public function groupedProducts()
    {
        return $this->product->groupedProducts()->active()->visible()->withPivot('sort_order', 'quantity')->orderByPivot('sort_order')->get();
    }

    #[Computed]
    public function groupedTotal(): float
    {
        return $this->groupedProducts->filter(fn ($item) => in_array($item->id, $this->selectedGroupedItems))->sum(function ($item) {
            $qty = $this->groupedQuantities[$item->id] ?? ($item->pivot->quantity ?? 1);

            return ($item->final_price ?? 0) * $qty;
        });
    }

    // =========================================================================
    // VARIANT COMPUTED PROPERTIES
    // =========================================================================

    /**
     * The currently selected variant model, or null for simple products.
     */
    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        if ($this->product->type->value !== 'variable' || ! $this->selectedVariantId) {
            return null;
        }

        return $this->product->variants->firstWhere('id', $this->selectedVariantId);
    }

    /**
     * Variation attributes with their values, including stock state per value.
     * Used to render the attribute selector buttons with correct states.
     *
     * Each value entry includes:
     *   - id, value, label
     *   - state: 'available' | 'out_of_stock' | 'backorder' | 'unavailable'
     */
    #[Computed]
    public function variationAttributes(): array
    {
        if ($this->product->type->value !== 'variable') {
            return [];
        }

        // Load all attribute value IDs from pivot in a single query
        $allValueIds = $this->product->attributes->flatMap(fn ($attr) => json_decode($attr->pivot->values ?? '[]', true) ?? [])->filter()->unique()->values()->toArray();

        if (empty($allValueIds)) {
            return [];
        }

        $allValues = AttributeValue::whereIn('id', $allValueIds)->get()->keyBy('id');

        // Build a map of attribute value ID => stock state
        // by checking which variants contain each value
        $valueStateMap = [];

        foreach ($this->product->variants as $variant) {
            foreach ($variant->attributeValues as $av) {
                $existing = $valueStateMap[$av->id] ?? 'out_of_stock';

                if (! $variant->is_active) {
                    continue;
                }

                $state = $this->resolveVariantStockState($variant);

                // Upgrade state — available beats backorder beats out_of_stock
                $priority = ['available' => 3, 'backorder' => 2, 'out_of_stock' => 1];
                if (($priority[$state] ?? 0) > ($priority[$existing] ?? 0)) {
                    $valueStateMap[$av->id] = $state;
                }
            }
        }

        return $this->product->attributes
            ->map(
                fn ($attr) => [
                    'name' => $attr->name,
                    'values' => collect(json_decode($attr->pivot->values ?? '[]', true) ?? [])
                        ->map(fn ($id) => $allValues->get($id))
                        ->filter()
                        ->map(
                            fn ($v) => [
                                'id' => $v->id,
                                'value' => $v->value,
                                'label' => $v->label ?: $v->value,
                                'state' => $valueStateMap[$v->id] ?? 'out_of_stock',
                            ],
                        )
                        ->toArray(),
                ],
            )
            ->toArray();
    }

    /**
     * Resolves the stock state of a variant into one of four states:
     * - available:   in stock, can add to cart
     * - backorder:   allow_backorders = true, can pre-order
     * - out_of_stock: cannot order
     * - unavailable:  no price set — never show
     */
    private function resolveVariantStockState(ProductVariant $variant): string
    {
        // No price = unavailable (not shown in store)
        if (is_null($variant->price)) {
            return 'unavailable';
        }

        if ($variant->manage_stock) {
            if ($variant->stock_quantity > 0) {
                return 'available';
            }
            if ($variant->allow_backorders) {
                return 'backorder';
            }

            return 'out_of_stock';
        }

        return match ($variant->stock_status) {
            'in_stock' => 'available',
            'backorder' => 'backorder',
            default => 'out_of_stock',
        };
    }

    /**
     * The stock state of the currently selected variant.
     * Used to control cart button state and backorder notice display.
     */
    #[Computed]
    public function selectedVariantState(): string
    {
        if (! $this->selectedVariant) {
            return 'none';
        }

        return $this->resolveVariantStockState($this->selectedVariant);
    }

    /**
     * The stock state of the simple product.
     * Used to control cart button state and backorder notice display.
     */
    #[Computed]
    public function simpleProductState(): string
    {
        if ($this->product->type->value === 'variable') {
            return 'none';
        }

        if ($this->product->manage_stock) {
            if ($this->product->stock_quantity > 0) {
                return 'available';
            }
            if ($this->product->allow_backorder !== 'no') {
                return 'backorder';
            }

            return 'out_of_stock';
        }

        return match ($this->product->stock_status) {
            'in_stock' => 'available',
            'backorder' => 'backorder',
            default => 'out_of_stock',
        };
    }

    // =========================================================================
    // VARIANT SELECTION
    // =========================================================================

    /**
     * Fires when the customer clicks an attribute value button.
     * Finds the matching variant (including out-of-stock ones),
     * updates cart state, and dispatches the image swap event.
     */
    public function selectAttributeValue(string $attributeName, string $value): void
    {
        $this->selectedAttributeValues[$attributeName] = $value;

        // Search ALL active variants — including out-of-stock and backorder
        $matched = $this->product->variants->where('is_active', true)->first(function ($variant) {
            $variantAttrs = $variant->attributeValues->mapWithKeys(fn ($av) => [$av->attribute->name => $av->value])->toArray();

            foreach ($this->selectedAttributeValues as $attrName => $attrValue) {
                if (($variantAttrs[$attrName] ?? null) !== $attrValue) {
                    return false;
                }
            }

            return true;
        });

        $this->selectedVariantId = $matched?->id;

        // Reset cart state when variant changes
        $this->cartQuantity = 1;
        $this->inCart = false;
        $this->cartItemId = null;

        if ($matched) {
            // Check if already in cart
            $cartService = app(CartService::class);
            $this->inCart = $cartService->has($this->product->id, $matched->id);

            if ($this->inCart) {
                $cartItem = $cartService->getCartItem($this->product->id, $matched->id);
                if ($cartItem) {
                    $this->cartItemId = $cartItem->id;
                    $this->cartQuantity = $cartItem->quantity;
                }
            }

            $slides = $this->imageSlides;
            $slideIndex = 0; // default: main image

            foreach ($slides as $i => $slide) {
                if ($slide['variantId'] === $matched->id) {
                    $slideIndex = $i;
                    break;
                }
            }

            $this->dispatch('variant-image-selected', index: $slideIndex);
        }

        // Bust computed caches
        unset($this->selectedVariant, $this->selectedVariantState);
    }

    /**
     * Ordered flat list of all image slides for the gallery.
     * Order: main product image → variant images (deduped) → gallery images.
     * Each slide carries: url, alt, variantId (null for non-variant slides).
     */
    #[Computed]
    public function imageSlides(): array
    {
        $slides = [];

        // Shared dedup tracker — spans all three sections below
        $seenPaths = [];

        // 1. Main product image (always slot 0)
        if ($this->product->image_path) {
            $seenPaths[] = $this->product->image_path;
            $slides[] = [
                'url' => $this->product->image_url,
                'webp' => $this->product->webp_image_url,
                'alt' => $this->product->name,
                'variantId' => null,
            ];
        }

        // 2. Variant images — skip any path already seen
        if ($this->product->type->value === 'variable') {
            foreach ($this->product->variants->where('is_active', true)->sortBy('sort_order') as $variant) {
                if ($variant->image_path && ! in_array($variant->image_path, $seenPaths, true)) {
                    $seenPaths[] = $variant->image_path;
                    $slides[] = [
                        'url' => Storage::url($variant->image_path),
                        'webp' => null,
                        'alt' => $variant->attributeValues->map(fn ($av) => $av->value)->join(', ') ?: $this->product->name,
                        'variantId' => $variant->id,
                    ];
                }
            }
        }

        // 3. Gallery images — skip any path already used by main image or a variant
        foreach ($this->product->images as $image) {
            if (! in_array($image->image_path, $seenPaths, true)) {
                $seenPaths[] = $image->image_path;
                $slides[] = [
                    'url' => Storage::url($image->image_path),
                    'webp' => $image->webp_url,
                    'alt' => $image->alt_text ?? $this->product->name,
                    'variantId' => null,
                ];
            }
        }

        return $slides;
    }

    #[Computed(persist: true)]
    public function primaryCategory()
    {
        return $this->product->primaryCategory();
    }

    // =========================================================================
    // WISHLIST
    // =========================================================================

    public function toggleWishlist(WishlistService $wishlistService): void
    {
        try {
            $added = $wishlistService->toggle($this->product->id);
            $this->wishlisted = $added;
            $this->dispatch('wishlist-updated');
            $this->dispatch('notify', variant: 'success', title: $added ? 'Wishlist Updated' : 'Wishlist Updated', message: $added ? 'Product added to your wishlist' : 'Product removed from your wishlist');
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Action Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update wishlist');
        }
    }

    // =========================================================================
    // COMPARE
    // =========================================================================

    public function toggleCompare(CompareService $compareService): void
    {
        try {
            $added = $compareService->toggle($this->product->id);
            $this->inCompare = $added;
            $this->dispatch('compare-updated');

            $this->dispatch('notify', title: $added ? 'Comparison Updated' : 'Comparison Updated', variant: 'success', message: $added ? 'Product added to comparison list' : 'Product removed from comparison list');
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Action Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update comparison');
        }
    }

    // =========================================================================
    // CART
    // =========================================================================

    public function addToCart(CartService $cartService): void
    {
        try {
            $variantId = $this->selectedVariantId;

            // Variable product requires a variant selection
            if ($this->product->type->value === 'variable' && ! $variantId) {
                $this->dispatch('notify', variant: 'warning', message: 'Please select a variation first.');

                return;
            }

            // Block if out of stock (backorder is allowed through)
            $state = $this->product->type->value === 'variable' ? $this->selectedVariantState : $this->simpleProductState;

            if ($state === 'out_of_stock') {
                $this->dispatch('notify', variant: 'warning', message: 'This product is currently out of stock.');

                return;
            }

            $cartService->addItem(productId: $this->product->id, quantity: $this->cartQuantity, variantId: $variantId);

            $this->inCart = true;
            $cartItem = $cartService->getCartItem($this->product->id, $variantId);

            if ($cartItem) {
                $this->cartItemId = $cartItem->id;
                $this->cartQuantity = $cartItem->quantity;
            }

            $this->dispatch('cart-updated');
            $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: 'Product added to your cart');
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Add to Cart Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add product to cart');
        }
    }

    public function increaseCartQuantity(CartService $cartService): void
    {
        try {
            $newQuantity = $this->cartQuantity + 1;

            // Determine max stock from selected variant or product
            $source = $this->selectedVariant ?? $this->product;
            $maxStock = $source->manage_stock ? $source->stock_quantity : PHP_INT_MAX;

            if ($source->manage_stock && $newQuantity > $maxStock) {
                $this->dispatch('notify', variant: 'warning', message: 'Maximum stock quantity reached');

                return;
            }

            if ($this->inCart && $this->cartItemId !== null) {
                $cartService->updateItemQuantity($this->cartItemId, $newQuantity);
            }

            $this->cartQuantity = $newQuantity;
            $this->dispatch('cart-updated');
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update cart quantity');
        }
    }

    public function decreaseCartQuantity(CartService $cartService): void
    {
        try {
            $newQuantity = $this->cartQuantity - 1;

            if ($newQuantity < 1) {
                $this->removeFromCart($cartService);

                return;
            }

            if ($this->inCart && $this->cartItemId !== null) {
                $cartService->updateItemQuantity($this->cartItemId, $newQuantity);
            }

            $this->cartQuantity = $newQuantity;
            $this->dispatch('cart-updated');
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update cart quantity');
        }
    }

    public function increaseGroupedQuantity(int $productId): void
    {
        $current = $this->groupedQuantities[$productId] ?? 1;
        $this->groupedQuantities[$productId] = $current + 1;
    }

    public function decreaseGroupedQuantity(int $productId): void
    {
        $current = $this->groupedQuantities[$productId] ?? 1;
        $this->groupedQuantities[$productId] = max(1, $current - 1);
    }

    public function removeFromCart(CartService $cartService): void
    {
        try {
            if ($this->cartItemId !== null) {
                $cartService->removeItem($this->cartItemId);
                $this->inCart = false;
                $this->cartItemId = null;
                $this->cartQuantity = 1;
                $this->dispatch('cart-updated');
                $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: 'Product removed from your cart');
            }
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Remove Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to remove item from cart');
        }
    }

    public function addAllAccessoriesToCart(CartService $cartService): void
    {
        try {
            $accessories = $this->accessories;
            if ($accessories->isEmpty()) {
                return;
            }

            foreach ($accessories as $accessory) {
                $cartService->addItem($accessory->id, $accessory->pivot->quantity ?? 1);
            }

            $this->dispatch('cart-updated');
            $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: 'All accessories have been added to your cart');
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Add Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add accessories to cart');
        }
    }

    public function addFullKitToCart(CartService $cartService): void
    {
        try {
            foreach ($this->groupedProducts as $item) {
                $qty = $this->groupedQuantities[$item->id] ?? ($item->pivot->quantity ?? 1);
                $cartService->addItem(productId: $item->id, quantity: $qty);
            }

            $this->dispatch('cart-updated');
            $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: 'Full kit has been added to your cart');
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Add Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add full kit to cart');
        }
    }

    public function addSelectedGroupedToCart(CartService $cartService): void
    {
        try {
            if (empty($this->selectedGroupedItems)) {
                $this->dispatch('notify', title: 'No Items Selected', variant: 'warning', message: 'Please select at least one item to add to cart');

                return;
            }

            foreach ($this->selectedGroupedItems as $productId) {
                $item = $this->groupedProducts->firstWhere('id', $productId);
                if ($item) {
                    $qty = $this->groupedQuantities[$productId] ?? ($item->pivot->quantity ?? 1);
                    $cartService->addItem(productId: $item->id, quantity: $qty);
                }
            }

            $this->dispatch('cart-updated');
            $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: count($this->selectedGroupedItems).' item(s) added to your cart');
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Add Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add selected items to cart');
        }
    }

    // =========================================================================
    // REVIEWS
    // =========================================================================

    #[Computed(persist: true)]
    public function reviewStats(): array
    {
        return app(ReviewService::class)->getStatistics($this->product);
    }

    #[Computed(persist: true)]
    public function reviews()
    {
        return app(ReviewService::class)->forProductPage($this->product, $this->reviewsToShow);
    }

    #[Computed]
    public function hasMoreReviews(): bool
    {
        return $this->reviewStats['total'] > $this->reviewsToShow;
    }

    #[Computed]
    public function userVotes()
    {
        if (! Auth::check()) {
            return collect();
        }

        $reviewIds = $this->reviews->pluck('id');
        if ($reviewIds->isEmpty()) {
            return collect();
        }

        return ReviewHelpfulness::whereIn('review_id', $reviewIds)->where('user_id', Auth::id())->get()->keyBy('review_id')->map(fn ($vote) => $vote->is_helpful);
    }

    // =========================================================================
    // ACCESSORIES
    // =========================================================================

    #[Computed(persist: true)]
    public function accessories()
    {
        return $this->product->accessories()->active()->withPivot('sort_order', 'quantity')->orderByPivot('sort_order')->get();
    }

    #[Computed(persist: true)]
    public function accessoriesTotalPrice(): float
    {
        return $this->accessories->sum(fn ($a) => ($a->final_price ?? 0) * ($a->pivot->quantity ?? 1));
    }

    public function render()
    {
        return $this->view()->title($this->product->name);
    }

    public function addToQuoteBasket(QuoteBasketService $quoteBasket): void
    {
        try {
            $quoteBasket->add(productId: $this->product->id, quantity: $this->cartQuantity, variantId: $this->selectedVariantId);

            $this->inQuoteBasket = true;

            $this->dispatch('quote-basket-updated');

            $this->dispatch('notify', title: 'Quote Basket Updated', variant: 'success', message: 'Product has been added to your quote basket');
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Add Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add product to quote basket');
        }
    }
};
?>

<div>
    <div class="bg-zinc-100">
        <div class="container mx-auto py-2.5 px-4 overflow-x-auto scrollbar-none">
            <?php if (isset($component)) { $__componentOriginalbbbea167ab072e3e3621cf7b736152aa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbbbea167ab072e3e3621cf7b736152aa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::breadcrumbs.index','data' => ['class' => 'flex-nowrap whitespace-nowrap min-w-max']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::breadcrumbs'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'flex-nowrap whitespace-nowrap min-w-max']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <?php if (isset($component)) { $__componentOriginalced986e8ff6641d3797206c3198c2b83 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalced986e8ff6641d3797206c3198c2b83 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::breadcrumbs.item','data' => ['href' => ''.e(route('home')).'','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::breadcrumbs.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('home')).'','wire:navigate' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    <?php if (isset($component)) { $__componentOriginal9f5e9841a29fcda640625c969c766980 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f5e9841a29fcda640625c969c766980 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.home','data' => ['class' => 'w-4 h-4 me-1.5 inline-block']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.home'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4 h-4 me-1.5 inline-block']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f5e9841a29fcda640625c969c766980)): ?>
<?php $attributes = $__attributesOriginal9f5e9841a29fcda640625c969c766980; ?>
<?php unset($__attributesOriginal9f5e9841a29fcda640625c969c766980); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f5e9841a29fcda640625c969c766980)): ?>
<?php $component = $__componentOriginal9f5e9841a29fcda640625c969c766980; ?>
<?php unset($__componentOriginal9f5e9841a29fcda640625c969c766980); ?>
<?php endif; ?>
                    Home
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalced986e8ff6641d3797206c3198c2b83)): ?>
<?php $attributes = $__attributesOriginalced986e8ff6641d3797206c3198c2b83; ?>
<?php unset($__attributesOriginalced986e8ff6641d3797206c3198c2b83); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalced986e8ff6641d3797206c3198c2b83)): ?>
<?php $component = $__componentOriginalced986e8ff6641d3797206c3198c2b83; ?>
<?php unset($__componentOriginalced986e8ff6641d3797206c3198c2b83); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginalced986e8ff6641d3797206c3198c2b83 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalced986e8ff6641d3797206c3198c2b83 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::breadcrumbs.item','data' => ['href' => ''.e(route('shop.index')).'','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::breadcrumbs.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('shop.index')).'','wire:navigate' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    Shop
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalced986e8ff6641d3797206c3198c2b83)): ?>
<?php $attributes = $__attributesOriginalced986e8ff6641d3797206c3198c2b83; ?>
<?php unset($__attributesOriginalced986e8ff6641d3797206c3198c2b83); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalced986e8ff6641d3797206c3198c2b83)): ?>
<?php $component = $__componentOriginalced986e8ff6641d3797206c3198c2b83; ?>
<?php unset($__componentOriginalced986e8ff6641d3797206c3198c2b83); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginalced986e8ff6641d3797206c3198c2b83 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalced986e8ff6641d3797206c3198c2b83 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::breadcrumbs.item','data' => ['href' => ''.e(route('shop.category', ['category' => $this->primaryCategory->slug])).'','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::breadcrumbs.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('shop.category', ['category' => $this->primaryCategory->slug])).'','wire:navigate' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    <?php echo e($this->primaryCategory->name); ?>

                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalced986e8ff6641d3797206c3198c2b83)): ?>
<?php $attributes = $__attributesOriginalced986e8ff6641d3797206c3198c2b83; ?>
<?php unset($__attributesOriginalced986e8ff6641d3797206c3198c2b83); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalced986e8ff6641d3797206c3198c2b83)): ?>
<?php $component = $__componentOriginalced986e8ff6641d3797206c3198c2b83; ?>
<?php unset($__componentOriginalced986e8ff6641d3797206c3198c2b83); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginalced986e8ff6641d3797206c3198c2b83 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalced986e8ff6641d3797206c3198c2b83 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::breadcrumbs.item','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::breadcrumbs.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e($product->name); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalced986e8ff6641d3797206c3198c2b83)): ?>
<?php $attributes = $__attributesOriginalced986e8ff6641d3797206c3198c2b83; ?>
<?php unset($__attributesOriginalced986e8ff6641d3797206c3198c2b83); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalced986e8ff6641d3797206c3198c2b83)): ?>
<?php $component = $__componentOriginalced986e8ff6641d3797206c3198c2b83; ?>
<?php unset($__componentOriginalced986e8ff6641d3797206c3198c2b83); ?>
<?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbbbea167ab072e3e3621cf7b736152aa)): ?>
<?php $attributes = $__attributesOriginalbbbea167ab072e3e3621cf7b736152aa; ?>
<?php unset($__attributesOriginalbbbea167ab072e3e3621cf7b736152aa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbbbea167ab072e3e3621cf7b736152aa)): ?>
<?php $component = $__componentOriginalbbbea167ab072e3e3621cf7b736152aa; ?>
<?php unset($__componentOriginalbbbea167ab072e3e3621cf7b736152aa); ?>
<?php endif; ?>
        </div>
    </div>

    <div class="container mx-auto px-4 py-4">
        <div class="grid lg:grid-cols-4 gap-5">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->type->value === 'grouped'): ?>
                <?php echo $__env->make('pages.product-details.partials._grouped-hero', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php else: ?>
                <?php echo $__env->make('pages.product-details.partials._hero', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php echo $__env->make('pages.product-details.partials._delivery-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->accessories->count() > 0): ?>
            <?php if (isset($component)) { $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::card.index','data' => ['class' => 'pb-6 relative pt-10 px-6 mt-10']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'pb-6 relative pt-10 px-6 mt-10']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>


                
                <div class="flex items-center gap-2 absolute top-0 left-0 -translate-y-1/2 rounded-b-sm rounded-tr-sm">

                    
                    <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['xShow' => '$wire.accessoriesTab == \'accessories\'','@click' => '$wire.accessoriesTab = \'accessories\'','variant' => 'primary','class' => 'rounded-none cursor-pointer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['x-show' => '$wire.accessoriesTab == \'accessories\'','@click' => '$wire.accessoriesTab = \'accessories\'','variant' => 'primary','class' => 'rounded-none cursor-pointer']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        Accessories

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->accessories->count() > 0): ?>
                            <?php if (isset($component)) { $__componentOriginal4cc377eda9b63b796b6668ee7832d023 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4cc377eda9b63b796b6668ee7832d023 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::badge.index','data' => ['size' => 'sm','class' => 'ml-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','class' => 'ml-1']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e($this->accessories->count()); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $attributes = $__attributesOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $component = $__componentOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__componentOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580)): ?>
<?php $attributes = $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580; ?>
<?php unset($__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc04b147acd0e65cc1a77f86fb0e81580)): ?>
<?php $component = $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580; ?>
<?php unset($__componentOriginalc04b147acd0e65cc1a77f86fb0e81580); ?>
<?php endif; ?>

                    <?php if (isset($component)) { $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button.index','data' => ['xCloak' => true,'xShow' => '$wire.accessoriesTab !== \'accessories\'','@click' => '$wire.accessoriesTab = \'accessories\'','class' => 'rounded-none cursor-pointer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['x-cloak' => true,'x-show' => '$wire.accessoriesTab !== \'accessories\'','@click' => '$wire.accessoriesTab = \'accessories\'','class' => 'rounded-none cursor-pointer']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        Accessories

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->accessories->count() > 0): ?>
                            <?php if (isset($component)) { $__componentOriginal4cc377eda9b63b796b6668ee7832d023 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4cc377eda9b63b796b6668ee7832d023 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::badge.index','data' => ['size' => 'sm','class' => 'ml-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','class' => 'ml-1']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e($this->accessories->count()); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $attributes = $__attributesOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $component = $__componentOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__componentOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580)): ?>
<?php $attributes = $__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580; ?>
<?php unset($__attributesOriginalc04b147acd0e65cc1a77f86fb0e81580); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc04b147acd0e65cc1a77f86fb0e81580)): ?>
<?php $component = $__componentOriginalc04b147acd0e65cc1a77f86fb0e81580; ?>
<?php unset($__componentOriginalc04b147acd0e65cc1a77f86fb0e81580); ?>
<?php endif; ?>

                    
                    
                </div>

                
                <?php echo $__env->make('pages.product-details.partials._accessories', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $attributes = $__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__attributesOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec)): ?>
<?php $component = $__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec; ?>
<?php unset($__componentOriginalc4bce27d2c09d2f98a63d67977c1c3ec); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php echo $__env->make('pages.product-details.partials._tabs', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('product-recommendations', ['type' => 'similar','context' => ['product' => $product]]);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-4130402242-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split);
?>
        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('product-recommendations', ['type' => 'recently_viewed']);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-4130402242-1', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split);
?>
    </div>
</div>

<style>
    .swiper-button-next,
    .swiper-button-prev {
        color: #fff;
        background: rgba(0, 0, 0, 0.5);
        padding: 20px;
        border-radius: 50%;
        width: 40px;
        height: 40px;
    }

    .swiper-button-next:after,
    .swiper-button-prev:after {
        font-size: 20px;
    }

    .swiper-button-next:hover,
    .swiper-button-prev:hover {
        background: rgba(0, 0, 0, 0.7);
    }

    .thumbSwiper .swiper-slide {
        opacity: 0.6;
        transition: opacity 0.3s;
    }

    .thumbSwiper .swiper-slide-thumb-active {
        opacity: 1;
    }
</style>
<?php /**PATH C:\Users\jonah\Herd\sheffield_ecommerce\resources\views\pages\product-details\index.blade.php ENDPATH**/ ?>