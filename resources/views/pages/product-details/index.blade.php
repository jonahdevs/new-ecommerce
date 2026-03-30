<?php

use App\Services\WishlistService;
use App\Services\CompareService;
use App\Services\CartService;
use App\Services\ReviewService;
use App\Services\ProductService;
use App\Services\ShippingCalculatorService;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\AttributeValue;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\ReviewHelpfulness;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\QuoteBasketService;

new #[Layout('layouts.guest')] class extends Component {
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
        $product->load(['images', 'brand', 'crossSells' => fn($q) => $q->active()->visible(), 'accessories' => fn($q) => $q->active()->visible()->withPivot('sort_order', 'quantity')]);

        if ($product->type->value === 'grouped') {
            $product->load([
                'groupedProducts' => fn($q) => $q->active()->visible()->withPivot('sort_order', 'quantity'),
            ]);
        }

        $product->loadAvg('reviews', 'rating');

        // Variable product — load all variants (active AND inactive/out-of-stock)
        // so we can show greyed-out out-of-stock buttons on the storefront
        if ($product->type->value === 'variable') {
            $product->load([
                // Load ALL variants, not just active — we filter display in the view
                'variants' => fn($q) => $q->orderBy('sort_order'),
                'variants.attributeValues.attribute',
                // Only load variation attributes (not display-only attributes)
                'attributes' => fn($q) => $q->wherePivot('is_variation_attribute', true),
            ]);

            // Pre-select the default variant or first available variant
            $defaultVariant = $product->variants->where('is_active', true)->firstWhere('is_default', true) ?? $product->variants->where('is_active', true)->first();

            if ($defaultVariant) {
                $this->selectedVariantId = $defaultVariant->id;
                $this->selectedAttributeValues = $defaultVariant->attributeValues->mapWithKeys(fn($av) => [$av->attribute->name => $av->value])->toArray();
            }
        }

        $this->product = $product;

        // Grouped product — load items and pre-select all
        if ($product->type->value === 'grouped') {
            $product->load([
                'groupedProducts' => fn($q) => $q->active()->visible()->withPivot('sort_order', 'quantity'),
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
    }

    // Grouped products
    #[Computed]
    public function groupedProducts()
    {
        return $this->product->groupedProducts()->active()->visible()->withPivot('sort_order', 'quantity')->orderByPivot('sort_order')->get();
    }

    #[Computed]
    public function groupedTotal(): float
    {
        return $this->groupedProducts->filter(fn($item) => in_array($item->id, $this->selectedGroupedItems))->sum(function ($item) {
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
        if ($this->product->type->value !== 'variable' || !$this->selectedVariantId) {
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
        $allValueIds = $this->product->attributes->flatMap(fn($attr) => json_decode($attr->pivot->values ?? '[]', true) ?? [])->filter()->unique()->values()->toArray();

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

                if (!$variant->is_active) {
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
                fn($attr) => [
                    'name' => $attr->name,
                    'values' => collect(json_decode($attr->pivot->values ?? '[]', true) ?? [])
                        ->map(fn($id) => $allValues->get($id))
                        ->filter()
                        ->map(
                            fn($v) => [
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
        if (!$this->selectedVariant) {
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
            $variantAttrs = $variant->attributeValues->mapWithKeys(fn($av) => [$av->attribute->name => $av->value])->toArray();

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
                'alt' => $this->product->name,
                'variantId' => null,
            ];
        }

        // 2. Variant images — skip any path already seen
        if ($this->product->type->value === 'variable') {
            foreach ($this->product->variants->where('is_active', true)->sortBy('sort_order') as $variant) {
                if ($variant->image_path && !in_array($variant->image_path, $seenPaths, true)) {
                    $seenPaths[] = $variant->image_path;
                    $slides[] = [
                        'url' => Storage::url($variant->image_path),
                        'alt' => $variant->attributeValues->map(fn($av) => $av->value)->join(', ') ?: $this->product->name,
                        'variantId' => $variant->id,
                    ];
                }
            }
        }

        // 3. Gallery images — skip any path already used by main image or a variant
        foreach ($this->product->images as $image) {
            if (!in_array($image->image_path, $seenPaths, true)) {
                $seenPaths[] = $image->image_path;
                $slides[] = [
                    'url' => Storage::url($image->image_path),
                    'alt' => $image->alt_text ?? $this->product->name,
                    'variantId' => null,
                ];
            }
        }

        return $slides;
    }

    #[Computed]
    public function primaryCategory()
    {
        return $this->product->primaryCategory();
    }

    #[Computed]
    public function estimatedShipping()
    {
        if (!$this->selectedCounty) {
            return null;
        }

        return app(ShippingCalculatorService::class)->calculateForProduct(product: $this->product, quantity: $this->cartQuantity, user: auth()->user(), countyId: $this->selectedCounty, areaId: $this->selectedArea, variantId: $this->selectedVariantId);
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
        } catch (\Throwable $th) {
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
        } catch (\Throwable $th) {
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
            if ($this->product->type->value === 'variable' && !$variantId) {
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
        } catch (\Throwable $th) {
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
        } catch (\Throwable $th) {
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
        } catch (\Throwable $th) {
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
        } catch (\Throwable $th) {
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
        } catch (\Throwable $th) {
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
        } catch (\Throwable $th) {
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
            $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: count($this->selectedGroupedItems) . ' item(s) added to your cart');
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Add Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add selected items to cart');
        }
    }

    // =========================================================================
    // REVIEWS
    // =========================================================================

    #[Computed]
    public function reviewStats(): array
    {
        return app(ReviewService::class)->getStatistics($this->product);
    }

    #[Computed]
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
        if (!Auth::check()) {
            return collect();
        }

        $reviewIds = $this->reviews->pluck('id');
        if ($reviewIds->isEmpty()) {
            return collect();
        }

        return ReviewHelpfulness::whereIn('review_id', $reviewIds)->where('user_id', Auth::id())->get()->keyBy('review_id')->map(fn($vote) => $vote->is_helpful);
    }

    // =========================================================================
    // ACCESSORIES
    // =========================================================================

    #[Computed]
    public function accessories()
    {
        return $this->product->accessories()->active()->withPivot('sort_order', 'quantity')->orderByPivot('sort_order')->get();
    }

    #[Computed]
    public function accessoriesTotalPrice(): float
    {
        return $this->accessories->sum(fn($a) => ($a->final_price ?? 0) * ($a->pivot->quantity ?? 1));
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
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Add Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add product to quote basket');
        }
    }
};
?>

<div>
    <div class="bg-zinc-100">
        <div class="container mx-auto py-2.5 px-4 overflow-x-auto scrollbar-none">
            <flux:breadcrumbs class="flex-nowrap whitespace-nowrap min-w-max">
                <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                    <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                    Home
                </flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('shop.category', ['category' => $this->primaryCategory->slug]) }}">
                    {{ $this->primaryCategory->name }}
                </flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $product->name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="container mx-auto px-4 py-4">
        <div class="grid lg:grid-cols-4 gap-5">
            @if ($product->type->value === 'grouped')
                @include('pages.product-details.partials._grouped-hero')
            @else
                @include('pages.product-details.partials._hero')
            @endif
            @include('pages.product-details.partials._delivery-sidebar')
        </div>

        @if ($this->accessories->count() > 0)
            <flux:card class="pb-6 relative pt-10 px-6 mt-10">

                {{-- Tab Buttons --}}
                <div class="flex items-center gap-2 absolute top-0 left-0 -translate-y-1/2 rounded-b-sm rounded-tr-sm">

                    {{-- Accessories --}}
                    <flux:button x-show="$wire.accessoriesTab == 'accessories'"
                        @click="$wire.accessoriesTab = 'accessories'" variant="primary"
                        class="rounded-none cursor-pointer">
                        Accessories

                        @if ($this->accessories->count() > 0)
                            <flux:badge size="sm" class="ml-1">{{ $this->accessories->count() }}</flux:badge>
                        @endif
                    </flux:button>

                    <flux:button x-cloak x-show="$wire.accessoriesTab !== 'accessories'"
                        @click="$wire.accessoriesTab = 'accessories'" class="rounded-none cursor-pointer">
                        Accessories

                        @if ($this->accessories->count() > 0)
                            <flux:badge size="sm" class="ml-1">{{ $this->accessories->count() }}</flux:badge>
                        @endif
                    </flux:button>

                    {{-- Spare Parts --}}
                    {{-- <flux:button x-cloak x-show="$wire.accessoriesTab == 'spare'" @click="$wire.accessoriesTab = 'spare'"
                    variant="primary" class="rounded-none cursor-pointer">
                    Spare
                </flux:button>
                <flux:button x-show="$wire.accessoriesTab !== 'spare'" @click="$wire.accessoriesTab = 'spare'"
                    class="rounded-none cursor-pointer">
                    Spare
                </flux:button> --}}
                </div>

                {{-- Tab Content --}}
                @include('pages.product-details.partials._accessories')
            </flux:card>
        @endif

        @include('pages.product-details.partials._tabs')

        <livewire:product-recommendations type="similar" :context="['product' => $product]" />
        <livewire:product-recommendations type="recently_viewed" />
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
