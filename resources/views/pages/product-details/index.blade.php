<?php

use App\Services\WishlistService;
use App\Services\CompareService;
use App\Services\CartService;
use App\Services\ReviewService;
use App\Services\ProductService;
use App\Services\ShippingCalculatorService;
use App\Models\Product;
use App\Models\Area;
use App\Models\County;
use App\Models\AttributeValue;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\ReviewHelpfulness;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.guest')] class extends Component {
    public Product $product;

    // Status flags
    public bool $wishlisted = false;
    public bool $inCompare = false;
    public bool $inCart = false;

    // Location state
    public $selectedCounty = '';
    public $selectedArea = '';

    // UI State
    public string $selectedTab = 'description';
    public int $reviewsToShow = 5;

    // Cart State
    public int $cartQuantity = 1;
    public ?int $cartItemId = null;

    // Variant state
    public array $selectedAttributeValues = [];
    public ?int $selectedVariantId = null;

    public function mount(Product $product, WishlistService $wishlist, CompareService $compareService, CartService $cartService): void
    {
        $productService = app(ProductService::class);
        $productService->recordView($product);
        $productService->rememberRecentlyViewed($product);

        $product->load(['images', 'brand', 'crossSells' => fn($q) => $q->active(), 'accessories' => fn($q) => $q->active()]);
        $product->loadAvg('reviews', 'rating');

        // Load variant data for variable products
        if ($product->type === 'variable') {
            $product->load([
                'variants' => fn($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'variants.attributeValues.attribute',
                'attributes' => fn($q) => $q->wherePivot('is_variation_attribute', true),
            ]);

            $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();

            if ($defaultVariant) {
                $this->selectedVariantId = $defaultVariant->id;
                $this->selectedAttributeValues = $defaultVariant->attributeValues->mapWithKeys(fn($av) => [$av->attribute->name => $av->value])->toArray();
            }
        }

        $this->product = $product;

        $this->wishlisted = $wishlist->has($this->product->id);
        $this->inCompare = $compareService->has($this->product->id);
        $this->inCart = $cartService->has($this->product->id);

        if ($this->inCart) {
            $cartItem = $cartService->getCartItem($this->product->id);
            if ($cartItem) {
                $this->cartItemId = $cartItem->id;
                $this->cartQuantity = $cartItem->quantity;
            }
        }

        $this->initializeLocation();
    }

    // -----------------------------------------------------------------------
    // Variant computed properties
    // -----------------------------------------------------------------------

    #[Computed]
    public function selectedVariant(): ?object
    {
        if ($this->product->type !== 'variable' || !$this->selectedVariantId) {
            return null;
        }

        return $this->product->variants->firstWhere('id', $this->selectedVariantId);
    }

    #[Computed]
    public function variationAttributes(): array
    {
        if ($this->product->type !== 'variable') {
            return [];
        }

        // Collect all value IDs across all variation attributes in one query
        $allValueIds = $this->product->attributes->flatMap(fn($attr) => json_decode($attr->pivot->values ?? '[]', true) ?? [])->filter()->unique()->values()->toArray();

        $allValues = AttributeValue::whereIn('id', $allValueIds)->get()->keyBy('id');

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
                            ],
                        )
                        ->toArray(),
                ],
            )
            ->toArray();
    }

    #[Computed]
    public function selectedVariantValueIds(): array
    {
        return $this->selectedVariant?->attributeValues->pluck('id')->toArray() ?? [];
    }

    // -----------------------------------------------------------------------
    // Variant selection
    // -----------------------------------------------------------------------

    public function selectAttributeValue(string $attributeName, string $value): void
    {
        $this->selectedAttributeValues[$attributeName] = $value;

        // Try to find a matching active variant
        $matched = $this->product->variants->filter(fn($variant) => $variant->is_active)->first(function ($variant) {
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

        // Check if this variant is already in cart
        if ($matched) {
            $cartService = app(CartService::class);
            $this->inCart = $cartService->has($matched->id, isVariant: true);

            if ($this->inCart) {
                $cartItem = $cartService->getCartItem($matched->id, isVariant: true);
                if ($cartItem) {
                    $this->cartItemId = $cartItem->id;
                    $this->cartQuantity = $cartItem->quantity;
                }
            }

            // Dispatch variant image swap event if variant has an image
            if ($matched->image_path) {
                $this->dispatch('variant-image-selected', url: Storage::url($matched->image_path));
            }
        }

        // Bust computed caches
        unset($this->selectedVariant, $this->selectedVariantValueIds);
    }

    // -----------------------------------------------------------------------
    // Location
    // -----------------------------------------------------------------------

    protected function initializeLocation(): void
    {
        $user = auth()->user();

        if ($user?->defaultAddress) {
            $user->defaultAddress->loadMissing(['county', 'area']);
            $this->selectedCounty = $user->defaultAddress->county_id;
            $this->selectedArea = $user->defaultAddress->area_id;
            return;
        }

        $nairobi = County::where('name', 'Nairobi')->first();
        $this->selectedCounty = $nairobi?->id;
        $this->selectedArea = null;
    }

    public function updatedSelectedCounty(): void
    {
        $this->selectedArea = null;
        unset($this->areas);
    }

    #[Computed(persist: true)]
    public function counties()
    {
        return County::orderBy('name')->get();
    }

    #[Computed]
    public function areas()
    {
        if (!$this->selectedCounty) {
            return collect();
        }
        return Area::where('county_id', $this->selectedCounty)->orderBy('name')->get();
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

    // -----------------------------------------------------------------------
    // Wishlist
    // -----------------------------------------------------------------------

    public function toggleWishlist(WishlistService $wishlistService): void
    {
        try {
            $added = $wishlistService->toggle($this->product->id);
            $this->wishlisted = $added;
            $this->dispatch('wishlist-updated');
            $this->dispatch('notify', variant: 'success', message: $added ? 'Added to wishlist' : 'Removed from wishlist');
        } catch (\Throwable $th) {
            logger()->error('Wishlist toggle failed', [
                'product_id' => $this->product->id ?? null,
                'user_id' => auth()->id(),
                'component' => static::class,
            ]);
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update wishlist');
        }
    }

    // -----------------------------------------------------------------------
    // Compare
    // -----------------------------------------------------------------------

    public function toggleCompare(CompareService $compareService): void
    {
        try {
            $added = $compareService->toggle($this->product->id);
            $this->inCompare = $added;
            $this->dispatch('compare-updated');
            $this->dispatch('notify', variant: 'success', title: 'Compare Updated!', message: $added ? 'Product added to your comparison list.' : 'Product removed from your comparison list.');
        } catch (\Exception $e) {
            $this->dispatch('notify', variant: 'danger', message: $e->getMessage() ?: 'Unable to update comparison');
        }
    }

    // -----------------------------------------------------------------------
    // Cart
    // -----------------------------------------------------------------------

    public function addToCart(CartService $cartService): void
    {
        try {
            $variantId = $this->selectedVariantId;

            if ($this->product->type === 'variable' && !$variantId) {
                $this->dispatch('notify', variant: 'warning', message: 'Please select a variation first.');
                return;
            }

            $cartService->addItem(productId: $this->product->id, quantity: $this->cartQuantity, variantId: $variantId);

            $this->inCart = true;
            $cartItem = $cartService->getCartItem($variantId ?? $this->product->id, isVariant: $variantId !== null);

            if ($cartItem) {
                $this->cartItemId = $cartItem->id;
                $this->cartQuantity = $cartItem->quantity;
            }

            $this->dispatch('cart-updated');
            $this->dispatch('notify', variant: 'success', message: 'Added to cart successfully');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to add to cart');
        }
    }

    public function increaseCartQuantity(CartService $cartService): void
    {
        try {
            $newQuantity = $this->cartQuantity + 1;
            $maxStock = $this->selectedVariant ? $this->selectedVariant->stock_quantity : $this->product->stock_quantity;

            if ($newQuantity > $maxStock) {
                $this->dispatch('notify', variant: 'warning', message: 'Maximum stock quantity reached');
                return;
            }

            if ($this->inCart && $this->cartItemId !== null) {
                $cartService->updateItemQuantity($this->cartItemId, $newQuantity);
            }

            $this->cartQuantity = $newQuantity;
            $this->dispatch('cart-updated');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update quantity');
        }
    }

    public function decreaseCartQuantity(CartService $cartService): void
    {
        try {
            $newQuantity = $this->cartQuantity - 1;

            if ($newQuantity < 1) {
                $this->dispatch('notify', variant: 'warning', message: 'Minimum quantity is 1');
                return;
            }

            if ($this->inCart && $this->cartItemId !== null) {
                $cartService->updateItemQuantity($this->cartItemId, $newQuantity);
            }

            $this->cartQuantity = $newQuantity;
            $this->dispatch('cart-updated');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update quantity');
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
                $this->dispatch('notify', variant: 'success', message: 'Removed from cart');
            }
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to remove from cart');
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
            $this->dispatch('notify', variant: 'success', message: 'All accessories added to cart!');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to add accessories to cart');
        }
    }

    // -----------------------------------------------------------------------
    // Reviews
    // -----------------------------------------------------------------------

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

    // -----------------------------------------------------------------------
    // Accessories
    // -----------------------------------------------------------------------

    #[Computed]
    public function accessories()
    {
        return $this->product->accessories;
    }

    #[Computed]
    public function accessoriesTotalPrice(): float
    {
        return $this->accessories->sum(fn($a) => ($a->final_price ?? 0) * ($a->pivot->quantity ?? 1));
    }

    public function render()
    {
        return $this->view()->title($this->product->name . ' | ' . config('app.name'));
    }
};
?>

<div>
    {{-- Breadcrumbs --}}
    <div class="bg-zinc-100">
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
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

    <div class="container mx-auto px-4 py-4">
        <div class="grid lg:grid-cols-4 gap-5">
            @include('pages.product-details.partials._hero')
            @include('pages.product-details.partials._delivery-sidebar')
        </div>

        @include('pages.product-details.partials._accessories')
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
