<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\{Layout, Defer, On, Title};
use App\Services\CartService;
use App\Services\WishlistService;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Defer] #[Title('Cart')] #[Layout('layouts.guest')] class extends Component {
    public function mount(): void
    {
        // Cart is a private page - should not be indexed
        SEOMeta::setRobots('noindex,nofollow');
    }

    // -----------------------------------------------------------------------
    // Computed
    // -----------------------------------------------------------------------

    #[Computed]
    public function cartItems()
    {
        return app(CartService::class)
            ->getCart()
            ->items()
            ->with([
                'product' => fn($q) => $q->with(['brand']),
                'variant' => fn($q) => $q->with(['attributeValues:id,attribute_id,value,label', 'attributeValues.attribute:id,name']),
            ])
            ->get();
    }

    #[Computed]
    public function cartSummary(): array
    {
        $cartService = app(CartService::class);
        return $cartService->summary($cartService->getCart());
    }

    #[Computed]
    public function wishlistProductIds(): array
    {
        return app(WishlistService::class)->ids();
    }

    // -----------------------------------------------------------------------
    // Event Listeners
    // -----------------------------------------------------------------------

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        unset($this->cartItems, $this->cartSummary);
    }

    // -----------------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------------

    public function clearCart(): void
    {
        app(CartService::class)->clear();
        unset($this->cartItems, $this->cartSummary);
        $this->dispatch('cart-updated');
    }

    public function removeItem(int $itemId): void
    {
        try {
            app(CartService::class)->removeItem($itemId);
            unset($this->cartItems, $this->cartSummary);
            $this->dispatch('cart-updated');
            $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: 'Item removed from your cart');
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Remove Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to remove item from cart');
        }
    }

    public function updateQuantity(int $itemId, int $quantity): void
    {
        // Directly remove item if quantity is 0 or less
        if ($quantity < 1) {
            $this->removeItem($itemId);
            return;
        }

        try {
            app(CartService::class)->updateItemQuantity($itemId, $quantity);
            unset($this->cartItems, $this->cartSummary);
            $this->dispatch('cart-updated');
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update cart quantity');
        }
    }

    public function toggleWishlist(int $productId): void
    {
        try {
            app(WishlistService::class)->toggle($productId);
            unset($this->wishlistProductIds);
            $this->dispatch('wishlist-updated');
            $this->dispatch('notify', title: 'Wishlist Updated', variant: 'success', message: 'Your wishlist has been updated');
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Wishlist Update Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update wishlist');
        }
    }

    public function proceedToCheckout(): void
    {
        $this->redirect(route('checkout.summary'), navigate: true);
    }

    public function render()
    {
        return $this->view()->title('Cart');
    }
};
?>

@placeholder
    <div>
        {{-- Breadcrumb placeholder --}}
        <div class="bg-white border-b border-zinc-200 py-3">
            <div class="container mx-auto px-4">
                <div class="flex items-center gap-3">
                    <flux:skeleton animate="shimmer" class="w-12 h-4" />
                    <flux:skeleton animate="shimmer" class="w-3 h-4" />
                    <flux:skeleton animate="shimmer" class="w-10 h-4" />
                </div>
            </div>
        </div>

        <div class="mx-auto container px-4 py-4 min-h-[80svh]">
            {{-- Header placeholder --}}
            <div class="flex items-center justify-between mb-4 gap-4">
                <flux:skeleton class="w-20 h-8" animate="shimmer" />
                <flux:skeleton class="w-24 h-10 rounded-md" animate="shimmer" />
            </div>

            <div class="flex flex-col lg:flex-row lg:items-start lg:gap-6">
                {{-- Items placeholder --}}
                <div class="lg:flex-1 min-w-0">
                    <div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">
                        {{-- Table header --}}
                        <div class="bg-zinc-50 border-b border-zinc-200">
                            <div class="flex items-center px-6 py-4">
                                <flux:skeleton animate="shimmer" class="w-20 h-4" />
                                <flux:skeleton animate="shimmer" class="w-16 h-4 ml-auto" />
                                <flux:skeleton animate="shimmer" class="w-20 h-4 ml-16" />
                                <flux:skeleton animate="shimmer" class="w-20 h-4 ml-16" />
                            </div>
                        </div>
                        {{-- Table rows --}}
                        @for ($i = 0; $i < 2; $i++)
                            <div class="flex items-center gap-4 px-6 py-6 border-b border-zinc-200 last:border-b-0">
                                <flux:skeleton animate="shimmer" class="w-16 h-16 rounded shrink-0" />
                                <div class="flex-1 space-y-2">
                                    <flux:skeleton animate="shimmer" class="w-24 h-3" />
                                    <flux:skeleton animate="shimmer" class="w-3/4 h-4" />
                                    <flux:skeleton animate="shimmer" class="w-32 h-3" />
                                </div>
                                <flux:skeleton animate="shimmer" class="w-20 h-4" />
                                <flux:skeleton animate="shimmer" class="w-24 h-8 rounded" />
                                <flux:skeleton animate="shimmer" class="w-20 h-4" />
                            </div>
                        @endfor
                    </div>
                    {{-- Continue shopping button --}}
                    <div class="mt-6">
                        <flux:skeleton animate="shimmer" class="w-40 h-10 rounded-md" />
                    </div>
                </div>

                {{-- Summary placeholder --}}
                <div class="w-full lg:w-96 shrink-0 mt-4 lg:mt-0">
                    <div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">
                        {{-- Header --}}
                        <div class="px-5 py-4 border-b border-zinc-200">
                            <flux:skeleton animate="shimmer" class="w-32 h-4" />
                        </div>
                        {{-- Totals --}}
                        <div class="space-y-3 px-5 py-4">
                            <div class="flex items-center justify-between">
                                <flux:skeleton animate="shimmer" class="w-20 h-4" />
                                <flux:skeleton animate="shimmer" class="w-24 h-4" />
                            </div>
                            <div class="flex items-center justify-between">
                                <flux:skeleton animate="shimmer" class="w-20 h-4" />
                                <flux:skeleton animate="shimmer" class="w-32 h-3" />
                            </div>
                            <div class="pt-3 border-t border-zinc-200 flex items-center justify-between">
                                <flux:skeleton animate="shimmer" class="w-16 h-4" />
                                <flux:skeleton animate="shimmer" class="w-28 h-6" />
                            </div>
                        </div>
                        {{-- Checkout button --}}
                        <div class="p-4 border-t border-zinc-200">
                            <flux:skeleton animate="shimmer" class="w-full h-12 rounded-md" />
                            <div class="mt-3 flex items-center justify-center">
                                <flux:skeleton animate="shimmer" class="w-40 h-3" />
                            </div>
                        </div>
                        {{-- Payment methods --}}
                        <div class="py-4 px-5 border-t border-zinc-100">
                            <flux:skeleton animate="shimmer" class="w-24 h-3 mb-3" />
                            <div class="flex flex-wrap gap-1.5 mb-6">
                                @for ($i = 0; $i < 4; $i++)
                                    <flux:skeleton animate="shimmer" class="w-20 h-6 rounded" />
                                @endfor
                            </div>
                            <div class="space-y-3">
                                <flux:skeleton animate="shimmer" class="w-full h-4" />
                                <flux:skeleton animate="shimmer" class="w-full h-4" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recommendations placeholder --}}
            <div class="mt-10">
                <flux:skeleton animate="shimmer" class="w-44 h-5 mb-4" />
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @for ($i = 1; $i <= 6; $i++)
                        <x-product-card-placeholder />
                    @endfor
                </div>
            </div>
        </div>
    </div>
@endplaceholder

<div>
    {{-- Breadcrumb --}}
    <div class="bg-white border-b border-zinc-200 py-3">
        <flux:breadcrumbs class="container mx-auto px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Cart</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="mx-auto container px-4 py-4 min-h-[80svh]">

        {{-- Cart Header --}}
        <div class="flex items-center justify-between mb-4 gap-4">
            <flux:heading level="1" class="font-semibold! text-xl! sm:text-2xl! lg:text-3xl! font-serif!">Cart
            </flux:heading>
            @if ($this->cartItems->isNotEmpty())
                <flux:button variant="customer-outline" wire:click="clearCart" class="cursor-pointer" size="customer">
                    Clear Cart
                </flux:button>
            @endif
        </div>

        <div class="flex flex-col lg:flex-row lg:items-start lg:gap-6">

            {{-- Items --}}
            <div class="lg:flex-1 min-w-0">
                @if ($this->cartItems->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
                        <div class="mb-8">
                            <img src="{{ asset('images/empty-states/empty-cart.svg') }}" alt="Empty Cart"
                                class="w-72 h-72 mx-auto" />
                        </div>

                        <flux:heading size="xl" class="mb-3 text-lg! sm:text-xl! md:text-2xl!">Your cart is empty
                        </flux:heading>

                        <flux:text class="mb-8 max-w-md text-xs! sm:text-sm!">
                            Looks like you haven't added anything to your cart yet.
                        </flux:text>

                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <flux:button href="{{ route('shop.index') }}" wire:navigate variant="customer-primary"
                                size="customer-lg" class="w-full sm:w-auto cursor-pointer">
                                <flux:icon.shopping-bag class="w-3.5 h-3.5" />
                                Start Shopping
                            </flux:button>
                            <flux:button href="{{ route('home') }}" wire:navigate variant="customer-outline"
                                size="customer-lg" class="w-full sm:w-auto cursor-pointer">
                                Back to Home
                            </flux:button>
                        </div>
                    </div>
                @else
                    {{-- Cart Items Table --}}
                    <div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-zinc-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-[11px] font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-200">
                                        Product
                                    </th>
                                    <th
                                        class="px-4 py-4 text-center text-[11px] font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-200">
                                        Price
                                    </th>
                                    <th
                                        class="px-4 py-4 text-center text-[11px] font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-200">
                                        Quantity
                                    </th>
                                    <th
                                        class="px-6 py-4 text-right text-[11px] font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-200">
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200">
                                @foreach ($this->cartItems as $item)
                                    @php
                                        $wishlisted = in_array($item->product->id, $this->wishlistProductIds);
                                        $variant = $item->variant;

                                        // Use variant price/image when available, fall back to product
                                        $unitPrice = $variant?->final_price ?? $item->product->final_price;
                                        $lineTotal = $unitPrice * $item->quantity;
                                        $sku = $variant?->sku ?? $item->product->sku;
                                        $imageUrl = $variant?->image_path
                                            ? Storage::url($variant->image_path)
                                            : $item->product->image_url;

                                        // Variant attribute pills e.g. ['Color' => 'Red', 'Size' => 'Large']
                                        $variantAttrs = $variant
                                            ? $variant->attributeValues->mapWithKeys(
                                                fn($av) => [$av->attribute->name => $av->label ?: $av->value],
                                            )
                                            : collect();
                                    @endphp

                                    <tr wire:key="cart-item-{{ $item->id }}" class="">
                                        {{-- Product Column --}}
                                        <td class="px-6 py-6">
                                            <div class="flex items-center gap-4">
                                                {{-- Product Image --}}
                                                <div
                                                    class="w-16 h-16 rounded border border-zinc-200 bg-zinc-50 overflow-hidden shrink-0">
                                                    @if ($imageUrl)
                                                        <img class="object-cover w-full h-full"
                                                            src="{{ $imageUrl }}"
                                                            alt="{{ $item->product->name }}" />
                                                    @else
                                                        <flux:icon.photo
                                                            class="w-full h-full p-2 text-zinc-300 stroke-1" />
                                                    @endif
                                                </div>

                                                {{-- Product Details --}}
                                                <div class="flex-1 min-w-0">
                                                    {{-- Brand/Category --}}
                                                    @if ($item->product->brand)
                                                        <p
                                                            class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 mb-1">
                                                            {{ $item->product->brand->name }}
                                                        </p>
                                                    @endif

                                                    {{-- Product Name --}}
                                                    <a href="{{ route('products.show', $item->product) }}"
                                                        wire:navigate
                                                        class="font-medium hover:underline block text-sm text-zinc-950 mb-1">
                                                        {{ $item->product->name }}
                                                    </a>

                                                    {{-- Variant Attributes --}}
                                                    @if ($variantAttrs->isNotEmpty())
                                                        <div class="flex flex-wrap gap-1 mb-2">
                                                            @foreach ($variantAttrs as $attrName => $attrValue)
                                                                <span class="text-[10px] text-zinc-500">
                                                                    {{ $attrName }}: {{ $attrValue }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    {{-- Actions --}}
                                                    <div class="flex items-center gap-3 mt-2">
                                                        <button wire:click="toggleWishlist({{ $item->product->id }})"
                                                            class="text-[11px] text-zinc-500 hover:text-zinc-700 transition-colors cursor-pointer">
                                                            <flux:icon.heart
                                                                variant="{{ $wishlisted ? 'solid' : 'outline' }}"
                                                                @class(['size-3 inline mr-1', 'text-red-500' => $wishlisted]) />
                                                            {{ $wishlisted ? 'Wishlisted' : 'Save for later' }}
                                                        </button>

                                                        <button wire:click="removeItem({{ $item->id }})"
                                                            class="text-[11px] text-zinc-500 hover:text-red-500 transition-colors cursor-pointer">
                                                            <flux:icon.trash class="size-3 inline mr-1" />
                                                            Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Price Column --}}
                                        <td class="px-4 py-6 text-center">
                                            @php
                                                $regularPrice = $variant?->price ?? $item->product->price;
                                                $salePrice = $variant?->sale_price ?? $item->product->sale_price;
                                                $hasDiscount = $salePrice && $salePrice < $regularPrice;
                                            @endphp

                                            @if ($hasDiscount)
                                                <p class="text-sm font-semibold text-zinc-950">
                                                    {{ format_currency($salePrice) }}
                                                </p>
                                                <p class="text-xs text-zinc-400 line-through">
                                                    {{ format_currency($regularPrice) }}
                                                </p>
                                            @else
                                                <p class="text-sm font-semibold text-zinc-950">
                                                    {{ format_currency($unitPrice) }}
                                                </p>
                                            @endif
                                        </td>

                                        {{-- Quantity Column --}}
                                        <td class="px-4 py-6 text-center">
                                            <div class="flex items-center justify-center">
                                                <div
                                                    class="flex items-center border border-zinc-200 rounded overflow-hidden">
                                                    <button
                                                        wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                                                        class="w-8 h-8 flex items-center justify-center text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50 transition-colors border-r border-zinc-200 cursor-pointer">
                                                        <flux:icon.minus class="size-3" />
                                                    </button>
                                                    <span
                                                        class="w-12 h-8 flex items-center justify-center text-sm font-medium bg-white">
                                                        {{ $item->quantity }}
                                                    </span>
                                                    <button
                                                        wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                                                        class="w-8 h-8 flex items-center justify-center text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50 transition-colors border-l border-zinc-200 cursor-pointer">
                                                        <flux:icon.plus class="size-3" />
                                                    </button>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Subtotal Column --}}
                                        <td class="px-6 py-6 text-right">
                                            <p class="text-sm font-semibold text-zinc-950">
                                                {{ format_currency($lineTotal) }}
                                            </p>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Continue Shopping Button --}}
                    <div class="mt-6">
                        <flux:button href="{{ route('shop.index') }}" wire:navigate variant="customer-outline"
                            size="customer" class="cursor-pointer">
                            <flux:icon.chevron-left class="size-3" />
                            Continue Shopping
                        </flux:button>
                    </div>
                @endif
            </div>

            {{-- Cart Summary --}}
            @if ($this->cartItems->isNotEmpty())
                <div class="w-full lg:w-96 shrink-0 mt-4 lg:mt-0 lg:sticky lg:top-34">
                    <div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">

                        {{-- Header --}}
                        <div class="px-5 py-4 border-b border-zinc-200 bg-white">
                            <h3 class="text-[13px] font-bold uppercase tracking-widest text-zinc-950 font-serif">Cart
                                Summary</h3>
                        </div>

                        {{-- Totals --}}
                        <div class="px-5 py-4 space-y-3">
                            <div class="flex justify-between text-[13px]">
                                <span class="text-zinc-500 font-medium">Subtotal</span>
                                <span
                                    class="text-zinc-950 font-bold">{{ format_currency($this->cartSummary['subtotal']) }}</span>
                            </div>

                            @if ($this->cartSummary['discount'] > 0)
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-green-600 font-medium">Discount</span>
                                    <span class="text-green-600 font-bold">−
                                        {{ format_currency($this->cartSummary['discount']) }}</span>
                                </div>
                            @endif

                            <div class="flex justify-between text-[13px]">
                                <span class="text-zinc-500 font-medium">Shipping</span>
                                <span class="text-zinc-500 text-[11px] font-medium">Calculated at checkout</span>
                            </div>

                            @if ($this->cartSummary['tax_enabled'] && !$this->cartSummary['tax_inclusive'] && $this->cartSummary['tax'] > 0)
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-zinc-500 font-medium">
                                        {{ $this->cartSummary['tax_name'] }} ({{ $this->cartSummary['tax_rate'] }})
                                    </span>
                                    <span
                                        class="text-zinc-950 font-bold">{{ format_currency($this->cartSummary['tax']) }}</span>
                                </div>
                            @endif

                            <div class="pt-3 border-t border-zinc-200 flex justify-between items-baseline">
                                <span
                                    class="text-[14px] font-bold uppercase tracking-widest text-zinc-950">Total</span>
                                <span class="text-[24px] font-black text-primary font-barlow-condensed leading-none">
                                    {{ format_currency($this->cartSummary['subtotal'] - $this->cartSummary['discount'] + ($this->cartSummary['tax_enabled'] && !$this->cartSummary['tax_inclusive'] ? $this->cartSummary['tax'] : 0)) }}
                                </span>
                            </div>
                        </div>

                        {{-- Checkout button --}}
                        <div class="p-4 border-t border-zinc-200 bg-white">
                            <flux:button wire:click="proceedToCheckout" class="w-full group cursor-pointer"
                                variant="customer-primary" size="customer-lg">
                                <span>Proceed to Checkout</span>
                                <x-slot name="iconTrailing">
                                    <flux:icon.chevron-right
                                        class="size-3.5 group-hover:translate-x-1 transition-transform" />
                                </x-slot>
                            </flux:button>

                            <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-400">
                                <flux:icon.shield-check class="size-3" />
                                <span class="uppercase tracking-widest">SSL Encrypted & Secure</span>
                            </div>
                        </div>

                        {{-- We Accept & Trust --}}
                        <div class="py-4 px-5 border-t border-zinc-100">
                            <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-3">We accept
                            </div>
                            <div class="flex flex-wrap gap-1.5 mb-6">
                                @foreach (['VISA', 'MPESA', 'MASTERCARD', 'PAYPAL'] as $pay)
                                    <span
                                        class="inline-block px-2 py-1 bg-zinc-100 border border-zinc-200 rounded text-[9px] font-bold text-zinc-600 tracking-wider">{{ $pay }}</span>
                                @endforeach
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center gap-2 text-xs text-zinc-500">
                                    <flux:icon.arrow-path class="size-3.5 text-zinc-400" />
                                    <span>30-Day Easy Returns Policy</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-zinc-500">
                                    <flux:icon.truck class="size-3.5 text-zinc-400" />
                                    <span>Free delivery on orders over KES 5,000</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>


        @if ($this->cartItems->isNotEmpty())
            <livewire:product-recommendations type="cart_related" />
        @endif

        <livewire:product-recommendations type="recently_viewed" />
    </div>
</div>
