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
use Livewire\Attributes\Defer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Defer] #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public Product $product;

    // ── Status flags
    public bool $wishlisted = false;

    public bool $inCompare = false;

    public bool $inCart = false;

    // ── UI state
    public int $reviewsToShow = 5;

    // ── Cart state ──
    public int $cartQuantity = 1;

    public ?int $cartItemId = null;

    public bool $inQuoteBasket = false;

    // ── Variant state ─
    // selectedAttributeValues: ['Color' => 'Red', 'Size' => 'Large']
    public array $selectedAttributeValues = [];

    public ?int $selectedVariantId = null;

    // Grouped products - quantities (0 means not selected)
    public array $groupedQuantities = [];

    // Grouped products - per-item cart item IDs (null = not in cart)
    public array $groupedCartItemIds = [];

    // Accessory products - quantities (0 means not selected)
    public array $accessoryQuantities = [];

    // =========================================================================
    // MOUNT
    // =========================================================================

    public function mount(Product $product): void
    {
        $wishlist = app(WishlistService::class);
        $compareService = app(CompareService::class);
        $cartService = app(CartService::class);
        $productService = app(ProductService::class);
        $productService->recordView($product);
        $productService->rememberRecentlyViewed($product);

        // Base eager loads for all product types
        $product->load([
            'images' => fn ($q) => $q->select(['id', 'product_id', 'image_path', 'webp_path', 'alt_text']),
            'brand:id,name',
            'categories' => fn ($q) => $q->select(['categories.id', 'categories.name', 'categories.slug', 'categories.parent_id']),
            'accessories' => fn ($q) => $q
                ->select(['products.id', 'products.name', 'products.slug', 'products.image_path', 'products.image_webp', 'products.price', 'products.sale_price', 'products.manage_stock', 'products.stock_quantity', 'products.allow_backorder', 'products.stock_status'])
                ->active()->visible()->withPivot('sort_order', 'quantity'),
        ]);

        if ($product->type->value === 'grouped') {
            $product->load([
                'groupedProducts' => fn ($q) => $q
                    ->select(['products.id', 'products.name', 'products.slug', 'products.image_path', 'products.image_webp', 'products.price', 'products.sale_price', 'products.manage_stock', 'products.stock_quantity', 'products.allow_backorder', 'products.stock_status'])
                    ->active()->visible()->withPivot('sort_order', 'quantity'),
            ]);
        }

        if ($product->type->value === 'bundle') {
            $product->load([
                'bundleProducts' => fn ($q) => $q
                    ->select(['products.id', 'products.name', 'products.slug', 'products.image_path', 'products.image_webp', 'products.price', 'products.sale_price', 'products.manage_stock', 'products.stock_quantity', 'products.allow_backorder', 'products.stock_status'])
                    ->active()->visible()->withPivot('sort_order', 'quantity'),
            ]);
        }

        $product->loadAvg('reviews', 'rating');

        // Variable product — load all variants (active AND inactive/out-of-stock)
        // so we can show greyed-out out-of-stock buttons on the storefront
        if ($product->type->value === 'variable') {
            $product->load([
                // Load ALL variants, not just active — we filter display in the view
                'variants' => fn ($q) => $q
                    ->select(['id', 'product_id', 'is_active', 'sort_order', 'image_path', 'attributes', 'is_default', 'price', 'manage_stock', 'stock_quantity', 'allow_backorders', 'stock_status', 'backorder_message', 'expected_restock_date', 'sku'])
                    ->orderBy('sort_order'),
                'variants.attributeValues' => fn ($q) => $q->select(['id', 'attribute_id', 'value']),
                'variants.attributeValues.attribute' => fn ($q) => $q->select(['id', 'name']),
                // Only load variation attributes (not display-only attributes)
                'attributes' => fn ($q) => $q
                    ->select(['attributes.id', 'attributes.name', 'attributes.watch_type'])
                    ->wherePivot('is_variation_attribute', true),
            ]);

            // Pre-select the default variant or first available variant
            $defaultVariant = $product->variants->where('is_active', true)->firstWhere('is_default', true) ?? $product->variants->where('is_active', true)->first();

            if ($defaultVariant) {
                $this->selectedVariantId = $defaultVariant->id;

                // Try to get attribute values from relationship first
                $this->selectedAttributeValues = $defaultVariant->attributeValues
                    ->mapWithKeys(fn ($av) => [$av->attribute->name => $av->value])
                    ->toArray();

                // Fallback: if relationship is empty, build from attributes JSON
                if (empty($this->selectedAttributeValues) && ! empty($defaultVariant->attributes)) {
                    $variantAttrIds = collect($defaultVariant->attributes)->map(fn ($id) => (int) $id)->toArray();

                    // Load attribute values and map them to attribute names
                    $attrValues = AttributeValue::with(['attribute' => fn ($q) => $q->select(['id', 'name'])])
                        ->whereIn('id', $variantAttrIds)
                        ->get(['id', 'attribute_id', 'value']);

                    $this->selectedAttributeValues = $attrValues
                        ->mapWithKeys(fn ($av) => [$av->attribute->name => $av->value])
                        ->toArray();
                }
            }
        }

        $this->product = $product;

        // Grouped product — load items and set default quantities from pivot
        if ($product->type->value === 'grouped') {
            $product->load([
                'groupedProducts' => fn ($q) => $q
                    ->select(['products.id', 'products.name', 'products.slug', 'products.image_path', 'products.image_webp', 'products.price', 'products.sale_price', 'products.manage_stock', 'products.stock_quantity', 'products.allow_backorder', 'products.stock_status'])
                    ->active()->visible()->withPivot('sort_order', 'quantity'),
            ]);

            // Set default quantities from pivot (all items pre-selected with their default qty)
            foreach ($product->groupedProducts as $item) {
                $this->groupedQuantities[$item->id] = $item->pivot->quantity ?? 1;
            }
        }

        // Accessories — set default quantities from pivot
        if ($product->accessories->count() > 0) {
            foreach ($product->accessories as $accessory) {
                $this->accessoryQuantities[$accessory->id] = $accessory->pivot->quantity ?? 1;
            }
        }

        // Grouped — init per-item quantities, check if any already in cart
        if ($product->type->value === 'grouped' && $product->relationLoaded('groupedProducts')) {
            foreach ($product->groupedProducts as $item) {
                $inCart = $cartService->has($item->id);
                if ($inCart) {
                    $cartItem = $cartService->getCartItem($item->id);
                    $this->groupedQuantities[$item->id] = $cartItem?->quantity ?? 0;
                    $this->groupedCartItemIds[$item->id] = $cartItem?->id;
                } else {
                    // Default to 1 when none in cart (add-all mode), 0 when some are in cart
                    $this->groupedQuantities[$item->id] = 0; // will be set below after full loop
                    $this->groupedCartItemIds[$item->id] = null;
                }
            }

            // If any item is in cart, items not in cart start at 0; otherwise all start at 1
            $anyInCart = collect($this->groupedCartItemIds)->filter(fn ($id) => $id !== null)->isNotEmpty();
            if (! $anyInCart) {
                foreach ($product->groupedProducts as $item) {
                    $this->groupedQuantities[$item->id] = $item->pivot->quantity ?? 1;
                }
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
        return $this->product->groupedProducts()
            ->select(['products.id', 'products.name', 'products.slug', 'products.image_path', 'products.image_webp', 'products.price', 'products.sale_price', 'products.manage_stock', 'products.stock_quantity', 'products.allow_backorder', 'products.stock_status'])
            ->active()->visible()->withPivot('sort_order', 'quantity')->orderByPivot('sort_order')->get();
    }

    #[Computed]
    public function groupedTotal(): float
    {
        return $this->groupedProducts->sum(function ($item) {
            $qty = $this->groupedQuantities[$item->id] ?? 0;

            return ($item->final_price ?? 0) * $qty;
        });
    }

    #[Computed]
    public function groupedPriceRange(): array
    {
        $items = $this->groupedProducts;
        if ($items->isEmpty()) {
            return ['min' => 0, 'max' => 0];
        }

        $prices = $items->map(fn ($p) => $p->final_price ?? $p->price ?? 0)->filter(fn ($p) => $p > 0);

        if ($prices->isEmpty()) {
            return ['min' => 0, 'max' => 0];
        }

        return [
            'min' => $prices->min(),
            'max' => $prices->max(),
        ];
    }

    #[Computed]
    public function selectedItemsCount(): int
    {
        return collect($this->groupedQuantities)->filter(fn ($qty) => $qty > 0)->count();
    }

    // Bundle products
    #[Computed(persist: true)]
    public function bundleProducts()
    {
        return $this->product->bundleProducts()
            ->select(['products.id', 'products.name', 'products.slug', 'products.image_path', 'products.image_webp', 'products.price', 'products.sale_price', 'products.manage_stock', 'products.stock_quantity', 'products.allow_backorder', 'products.stock_status'])
            ->active()->visible()->withPivot('sort_order', 'quantity')->orderByPivot('sort_order')->get();
    }

    #[Computed]
    public function bundleValue(): float
    {
        return $this->bundleProducts->sum(function ($item) {
            $qty = $item->pivot->quantity ?? 1;

            return ($item->final_price ?? 0) * $qty;
        });
    }

    #[Computed]
    public function bundlePriceRange(): array
    {
        $items = $this->bundleProducts;
        if ($items->isEmpty()) {
            return ['min' => 0, 'max' => 0];
        }

        $prices = $items->map(fn ($p) => $p->final_price ?? $p->price ?? 0)->filter(fn ($p) => $p > 0);

        if ($prices->isEmpty()) {
            return ['min' => 0, 'max' => 0];
        }

        return [
            'min' => $prices->min(),
            'max' => $prices->max(),
        ];
    }

    #[Computed]
    public function bundleSavingsPercent(): ?float
    {
        $bundlePrice = $this->product->sale_price ?? $this->product->price;
        $value = $this->bundleValue;

        if (! $bundlePrice || ! $value || $value <= $bundlePrice) {
            return null;
        }

        return round((($value - $bundlePrice) / $value) * 100, 1);
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
    #[Computed(persist: true)]
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

        $allValues = AttributeValue::whereIn('id', $allValueIds)->get(['id', 'value', 'label', 'color_code', 'image_path'])->keyBy('id');

        // Build a map of attribute value ID => stock state
        // by checking which variants contain each value
        $valueStateMap = [];

        foreach ($this->product->variants as $variant) {
            if (! $variant->is_active) {
                continue;
            }

            $state = $this->resolveVariantStockState($variant);

            // First try to get attribute values from the relationship
            $variantAttributeValueIds = $variant->attributeValues->pluck('id')->toArray();

            // Fallback: if relationship is empty, try the attributes JSON field
            if (empty($variantAttributeValueIds) && ! empty($variant->attributes)) {
                $variantAttributeValueIds = collect($variant->attributes)
                    ->map(fn ($id) => (int) $id)
                    ->toArray();
            }

            foreach ($variantAttributeValueIds as $avId) {
                $existing = $valueStateMap[$avId] ?? 'out_of_stock';

                // Upgrade state — available beats backorder beats out_of_stock
                $priority = ['available' => 3, 'backorder' => 2, 'out_of_stock' => 1];
                if (($priority[$state] ?? 0) > ($priority[$existing] ?? 0)) {
                    $valueStateMap[$avId] = $state;
                }
            }
        }

        return $this->product->attributes
            ->map(
                fn ($attr) => [
                    'name' => $attr->name,
                    'watch_type' => $attr->watch_type ?? 'label', // select, label, color, image
                    'values' => collect(json_decode($attr->pivot->values ?? '[]', true) ?? [])
                        ->map(fn ($id) => $allValues->get($id))
                        ->filter()
                        ->map(
                            fn ($v) => [
                                'id' => $v->id,
                                'value' => $v->value,
                                'label' => $v->label ?: $v->value,
                                'state' => $valueStateMap[$v->id] ?? 'out_of_stock',
                                'color_code' => $v->color_code,
                                'image_path' => $v->image_path,
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

        // Build a map of attribute name => selected value ID for matching
        // We need to find the attribute value IDs for the selected values
        $selectedValueIds = [];
        foreach ($this->selectedAttributeValues as $attrName => $attrValue) {
            // Find the attribute value ID from the variationAttributes
            foreach ($this->variationAttributes as $attr) {
                if ($attr['name'] === $attrName) {
                    foreach ($attr['values'] as $v) {
                        if ($v['value'] === $attrValue) {
                            $selectedValueIds[$attrName] = $v['id'];
                            break;
                        }
                    }
                    break;
                }
            }
        }

        // Search ALL active variants — including out-of-stock and backorder
        $matched = $this->product->variants->where('is_active', true)->first(function ($variant) use ($selectedValueIds) {
            // First try to match using attributeValues relationship
            $variantAttrs = $variant->attributeValues->mapWithKeys(fn ($av) => [$av->attribute->name => $av->value])->toArray();

            if (! empty($variantAttrs)) {
                foreach ($this->selectedAttributeValues as $attrName => $attrValue) {
                    if (($variantAttrs[$attrName] ?? null) !== $attrValue) {
                        return false;
                    }
                }

                return true;
            }

            // Fallback: match using attributes JSON field
            if (! empty($variant->attributes) && ! empty($selectedValueIds)) {
                $variantAttrIds = collect($variant->attributes)->map(fn ($id) => (int) $id)->toArray();

                // Check if all selected value IDs are in the variant's attributes
                foreach ($selectedValueIds as $attrName => $valueId) {
                    if (! in_array($valueId, $variantAttrIds)) {
                        return false;
                    }
                }

                return true;
            }

            return false;
        });

        $this->selectedVariantId = $matched?->id;

        // Reset cart state when variant changes
        $this->cartQuantity = 1;
        $this->inCart = false;
        $this->cartItemId = null;

        if ($matched) {
            // Flush the CartService in-memory cache so we get fresh DB state
            // (the singleton retains cached item keys from mount() otherwise)
            $cartService = app(CartService::class);
            $cartService->refresh();

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
     * Order: main product image → variant images (for variable) → child product images (for grouped/bundle) → gallery images.
     * Each slide carries: url, alt, variantId (null for non-variant slides), childProductId (for grouped/bundle).
     */
    #[Computed(persist: true)]
    public function imageSlides(): array
    {
        $slides = [];

        // Shared dedup tracker — spans all sections below
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

        // 2. Variant images — for variable products only
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

        // 3. Child product images — for grouped and bundle products
        if ($this->product->type->value === 'grouped' && $this->product->relationLoaded('groupedProducts')) {
            foreach ($this->product->groupedProducts as $child) {
                // Add child's main image
                if ($child->image_path && ! in_array($child->image_path, $seenPaths, true)) {
                    $seenPaths[] = $child->image_path;
                    $slides[] = [
                        'url' => $child->image_url,
                        'webp' => $child->webp_image_url,
                        'alt' => $child->name,
                        'variantId' => null,
                    ];
                }
            }
        }

        if ($this->product->type->value === 'bundle' && $this->product->relationLoaded('bundleProducts')) {
            foreach ($this->product->bundleProducts as $child) {
                // Add child's main image
                if ($child->image_path && ! in_array($child->image_path, $seenPaths, true)) {
                    $seenPaths[] = $child->image_path;
                    $slides[] = [
                        'url' => $child->image_url,
                        'webp' => $child->webp_image_url,
                        'alt' => $child->name,
                        'variantId' => null,
                    ];
                }
            }
        }

        // 4. Gallery images — skip any path already used
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

            // If product has accessories and none have been added to cart yet, prompt the customer
            if ($this->product->accessories->isNotEmpty()) {
                $anyAccessoryInCart = $this->product->accessories->contains(
                    fn ($acc) => $cartService->has($acc->id)
                );

                if (! $anyAccessoryInCart) {
                    $this->dispatch(
                        'notify-action',
                        variant: 'info',
                        message: 'This product has accessories that go well with it.',
                        action: [
                            'label' => 'View Accessories',
                            'js' => '$flux.modal("accessories-modal").show()',
                        ]
                    );
                }
            }
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Add to Cart Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add product to cart');
        }
    }

    public function increaseCartQuantity(CartService $cartService): void
    {
        try {
            $newQuantity = $this->cartQuantity + 1;

            // Check stock against the variant (if variable) or the product
            if ($this->selectedVariantId) {
                $source = ProductVariant::find($this->selectedVariantId);
            }

            $source ??= $this->product;

            if ($source->manage_stock && $newQuantity > $source->stock_quantity) {
                $this->dispatch('notify', variant: 'warning', message: 'Maximum stock quantity reached');

                return;
            }

            if ($this->inCart && $this->cartItemId !== null) {
                $cartService->updateItemQuantity($this->cartItemId, $newQuantity);
                $this->dispatch('cart-updated');
            }

            $this->cartQuantity = $newQuantity;
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update cart quantity');
        }
    }

    public function decreaseCartQuantity(CartService $cartService): void
    {
        try {
            $newQuantity = $this->cartQuantity - 1;

            if ($newQuantity < 1) {
                if ($this->inCart) {
                    $this->removeFromCart($cartService);
                }

                // Not in cart — floor at 1
                return;
            }

            if ($this->inCart && $this->cartItemId !== null) {
                $cartService->updateItemQuantity($this->cartItemId, $newQuantity);
                $this->dispatch('cart-updated');
            }

            $this->cartQuantity = $newQuantity;
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update cart quantity');
        }
    }

    public function increaseGroupedQuantity(int $productId, CartService $cartService): void
    {
        try {
            $newQty = ($this->groupedQuantities[$productId] ?? 1) + 1;
            $cartItemId = $this->groupedCartItemIds[$productId] ?? null;

            if ($this->anyGroupedItemInCart && $cartItemId !== null) {
                // In cart mode — update quantity silently
                $cartService->updateItemQuantity($cartItemId, $newQty);
                $this->dispatch('cart-updated');
            } elseif ($this->anyGroupedItemInCart && $cartItemId === null) {
                // Some items in cart but this one isn't — add it and notify
                $cartService->addItem(productId: $productId, quantity: $newQty);
                $cartItem = $cartService->getCartItem($productId);
                if ($cartItem) {
                    $this->groupedCartItemIds[$productId] = $cartItem->id;
                    $newQty = $cartItem->quantity;
                }
                $this->dispatch('cart-updated');
                $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: 'Item added to your cart');
            }
            // else: none in cart — local counter only, no notify

            $this->groupedQuantities[$productId] = $newQty;
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update cart');
        }
    }

    public function decreaseGroupedQuantity(int $productId, CartService $cartService): void
    {
        try {
            $current = $this->groupedQuantities[$productId] ?? 1;
            $cartItemId = $this->groupedCartItemIds[$productId] ?? null;

            if ($this->anyGroupedItemInCart) {
                // In cart mode
                if ($current <= 1 && $cartItemId !== null) {
                    // Remove from cart and notify
                    $cartService->removeItem($cartItemId);
                    $this->groupedQuantities[$productId] = 0;
                    $this->groupedCartItemIds[$productId] = null;
                    $this->dispatch('cart-updated');
                    $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: 'Item removed from your cart');

                    return;
                }
                if ($current <= 0) {
                    return;
                }

                // Decrease silently
                $newQty = $current - 1;
                if ($cartItemId !== null) {
                    $cartService->updateItemQuantity($cartItemId, $newQty);
                    $this->dispatch('cart-updated');
                }
                $this->groupedQuantities[$productId] = $newQty;
            } else {
                // Local mode — floor at 1, no notify
                $this->groupedQuantities[$productId] = max(1, $current - 1);
            }
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update cart');
        }
    }

    public function addGroupedToCart(CartService $cartService): void
    {
        try {
            $addedCount = 0;

            foreach ($this->groupedProducts as $item) {
                $qty = $this->groupedQuantities[$item->id] ?? 1;
                if ($qty > 0) {
                    $cartService->addItem(productId: $item->id, quantity: $qty);
                    $cartItem = $cartService->getCartItem($item->id);
                    if ($cartItem) {
                        $this->groupedCartItemIds[$item->id] = $cartItem->id;
                        $this->groupedQuantities[$item->id] = $cartItem->quantity;
                    }
                    $addedCount++;
                }
            }

            if ($addedCount === 0) {
                $this->dispatch('notify', variant: 'warning', message: 'Please select at least one item.');

                return;
            }

            $this->dispatch('cart-updated');
            $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: "{$addedCount} ".Str::plural('item', $addedCount).' added to your cart');
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Add to Cart Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add items to cart');
        }
    }

    #[Computed]
    public function anyGroupedItemInCart(): bool
    {
        return collect($this->groupedCartItemIds)->filter(fn ($id) => $id !== null)->isNotEmpty();
    }

    public function addBundleToCart(CartService $cartService): void
    {
        try {
            if ($this->inCart) {
                $this->dispatch('notify', variant: 'info', message: 'Bundle is already in your cart');
                $this->js('$flux.modal("bundle-contents-modal").close()');

                return;
            }

            $cartService->addItem(productId: $this->product->id, quantity: 1);

            $this->inCart = true;
            $cartItem = $cartService->getCartItem($this->product->id);
            if ($cartItem) {
                $this->cartItemId = $cartItem->id;
                $this->cartQuantity = $cartItem->quantity;
            }

            $this->dispatch('cart-updated');
            $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: 'Bundle added to your cart');
            $this->js('$flux.modal("bundle-contents-modal").close()');
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Add to Cart Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add bundle to cart');
        }
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

    public function increaseAccessoryQuantity(int $productId): void
    {
        $current = $this->accessoryQuantities[$productId] ?? 0;
        $this->accessoryQuantities[$productId] = $current + 1;
    }

    public function decreaseAccessoryQuantity(int $productId): void
    {
        $current = $this->accessoryQuantities[$productId] ?? 0;
        $this->accessoryQuantities[$productId] = max(0, $current - 1);
    }

    public function addAccessoriesToCart(CartService $cartService): void
    {
        try {
            $addedCount = 0;

            foreach ($this->accessories as $accessory) {
                $qty = $this->accessoryQuantities[$accessory->id] ?? 0;
                if ($qty > 0) {
                    $cartService->addItem(productId: $accessory->id, quantity: $qty);
                    $addedCount++;
                }
            }

            if ($addedCount === 0) {
                $this->dispatch('notify', variant: 'warning', message: 'Please select at least one accessory to add to cart.');

                return;
            }

            $this->dispatch('cart-updated');
            $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: "{$addedCount} ".Str::plural('accessory', $addedCount).' added to your cart');

            $this->js('$flux.modal("accessories-modal").close()');
        } catch (Throwable $th) {
            $this->dispatch('notify', title: 'Add to Cart Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add accessories to cart');
        }
    }

    #[Computed]
    public function selectedAccessoriesCount(): int
    {
        return collect($this->accessoryQuantities)->filter(fn ($qty) => $qty > 0)->count();
    }

    #[Computed]
    public function accessoryPriceRange(): array
    {
        $accessories = $this->accessories;
        if ($accessories->isEmpty()) {
            return ['min' => 0, 'max' => 0];
        }

        $prices = $accessories->map(fn ($a) => $a->final_price ?? $a->price ?? 0)->filter(fn ($p) => $p > 0);

        if ($prices->isEmpty()) {
            return ['min' => 0, 'max' => 0];
        }

        return [
            'min' => $prices->min(),
            'max' => $prices->max(),
        ];
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

        return ReviewHelpfulness::whereIn('review_id', $reviewIds)->where('user_id', Auth::id())->get(['review_id', 'is_helpful'])->keyBy('review_id')->map(fn ($vote) => $vote->is_helpful);
    }

    // =========================================================================
    // ACCESSORIES
    // =========================================================================

    #[Computed(persist: true)]
    public function accessories()
    {
        return $this->product->accessories()
            ->select(['products.id', 'products.name', 'products.slug', 'products.image_path', 'products.image_webp', 'products.price', 'products.sale_price', 'products.manage_stock', 'products.stock_quantity', 'products.allow_backorder', 'products.stock_status'])
            ->active()->withPivot('sort_order', 'quantity')->orderByPivot('sort_order')->get();
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
