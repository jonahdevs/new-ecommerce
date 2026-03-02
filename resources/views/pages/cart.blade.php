<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\{Layout, Defer};
use App\Services\CartService;
use App\Services\WishlistService;
use App\Models\Cart;
use Flux\Flux;

new #[Defer] #[Layout('layouts.guest')] class extends Component {
    public array $cartSummary = [];
    public Cart $cart;

    public function render()
    {
        return $this->view()->title('Cart' . ' — ' . config('app.name'));
    }

    public function mount()
    {
        $cartService = app(CartService::class);
        $this->cart = $cartService->getCart();

        $this->cartSummary = $cartService->summary($this->cart);
    }

    #[Computed(persist: true)]
    public function cartItems()
    {
        $cartService = app(CartService::class);
        $cart = $cartService->getCart();
        return $cart->items()->with('product')->get();
    }

    public function clearCart(CartService $cartService)
    {
        $cartService->clear();
    }

    public function removeItem($itemId)
    {
        try {
            $cartService = app(CartService::class);
            $cartService->removeItem($itemId);

            Flux::modal('remove-item-' . $itemId)->close();

            $this->dispatch('notify', variant: 'success', message: 'Item removed from cart');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to remove item');
        }
    }

    public function toggleWishlist($productId)
    {
        try {
            $wishlistService = app(WishlistService::class);
            $wishlistService->toggle($productId);

            $this->dispatch('wishlist-updated');
            $this->dispatch('notify', variant: 'success', message: 'Wishlist updated');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update wishlist');
        }
    }

    public function inWishlist($productId)
    {
        $wishlistService = app(WishlistService::class);
        return $wishlistService->has($productId);
    }

    public function updateQuantity($productId, $quantity)
    {
        try {
            $cartService = app(CartService::class);
            $cartService->updateItemQuantity($productId, $quantity);
            $this->dispatch('notify', variant: 'success', message: 'Cart updated');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update cart');
        }
    }

    #[Computed]
    public function hasAvailableAccessories()
    {
        foreach ($this->cartItems as $item) {
            // Get cross-sells that are not already in cart
            $crossSells = $item->product
                ->crossSells()
                ->whereNotIn('products.id', $this->cartItems->pluck('product_id'))
                ->get();

            if ($crossSells->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }

    #[Computed]
    public function productsWithMissingAccessories()
    {
        $products = [];

        foreach ($this->cartItems as $item) {
            $crossSellsCount = $item->product
                ->crossSells()
                ->whereNotIn('products.id', $this->cartItems->pluck('product_id'))
                ->count();

            if ($crossSellsCount > 0) {
                $products[] = [
                    'id' => $item->product->id,
                    'slug' => $item->product->slug,
                    'name' => $item->product->name,
                    'image' => $item->product->image_url,
                    'accessories_count' => $crossSellsCount,
                ];
            }
        }

        return $products;
    }

    public function proceedToCheckout()
    {
        // Check if there are products with missing accessories
        if ($this->hasAvailableAccessories) {
            // Open the accessories confirmation modal
            Flux::modal('accessories-confirmation')->show();
            return;
        }

        // If no accessories, proceed directly to checkout
        $this->redirect(route('checkout.shipping'), navigate: true);
    }
};
?>

@placeholder
    <div>
        <div class="bg-zinc-100">
            <div class="flex items-center gap-3 container mx-auto py-3 px-4">
                <flux:skeleton animate="shimmer" class="w-4 h-4" />
                <flux:skeleton animate="shimmer" class="w-14 h-4" />
                <flux:skeleton animate="shimmer" class="w-3 h-4" />
                <flux:skeleton animate="shimmer" class="w-14 h-4" />
            </div>
        </div>

        <div class="mx-auto container px-4 py-4 min-h-[80svh]">
            <!-- Cart Header -->
            <div class="flex items-center justify-between">
                <flux:skeleton class="w-32 h-6 mb-4" animate="shimmer" />
                <flux:skeleton class="w-24 h-6 mb-4" animate="shimmer" />
            </div>

            <div class="space-y-4 lg:gap-4 lg:flex lg:items-start">
                <div class="lg:flex-1">
                    <div class="space-y-4">
                        @for ($i = 0; $i < 2; $i++)
                            <div class="bg-white rounded-sm overflow-hidden border">
                                <div class="flex items-start gap-3 p-3 py-4">
                                    <div class="shrink-0 px-4">
                                        <flux:skeleton animate="shimmer" class="w-20 h-20 rounded-sm" />
                                    </div>

                                    <div class="flex-1 space-y-2">
                                        <flux:skeleton animate="shimmer" class="h-5 w-3/4 rounded-sm " />
                                        <flux:skeleton animate="shimmer" class="h-4 w-1/4 rounded-sm " />
                                    </div>
                                    <div>
                                        <flux:skeleton animate="shimmer" class="h-5 w-24 rounded-sm" />
                                    </div>

                                </div>
                                <div class="bg-zinc-50 px-3 py-2 flex items-center">
                                    <div class="flex items-center gap-4">
                                        <flux:skeleton animate="shimmer" class="h-4 w-20 rounded-sm" />
                                        <flux:skeleton animate="shimmer" class="h-4 w-24 rounded-sm" />

                                    </div>

                                    <div class="ms-auto flex items-center gap-1">
                                        <flux:skeleton animate="shimmer" class="h-4 w-16 rounded-sm" />
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="w-full lg:max-w-md">
                    <div class="bg-white rounded-sm border">
                        <div class="px-3 py-2 border-b">
                            <flux:skeleton animate="shimmer" class="h-6 w-24 px-3 py-2 rounded-sm" />
                        </div>
                        <div class="space-y-2 p-3">
                            <div class="flex items-center justify-between">
                                <flux:skeleton animate="shimmer" class="h-4 w-24 rounded-sm" />
                                <flux:skeleton animate="shimmer" class="h-4 w-16 rounded-sm" />
                            </div>
                            <div class="flex items-center justify-between">
                                <flux:skeleton animate="shimmer" class="h-4 w-24 rounded-sm" />
                                <flux:skeleton animate="shimmer" class="h-4 w-16 rounded-sm" />
                            </div>
                        </div>
                        <div class="border-t p-3">
                            <flux:skeleton animate="shimmer" class="h-10 w-full rounded-sm" />
                        </div>
                    </div>
                </div>
            </div>

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
    <div class="bg-zinc-100">
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item>Cart</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="mx-auto container px-4 py-4 min-h-[80svh]">
        <!-- Cart Header -->
        <div class="flex items-center justify-between mb-4 gap-4">
            <flux:heading level="1" class="font-bold! text-2xl!">Cart</flux:heading>

            @if ($this->cartItems->isNotEmpty())
                <flux:button variant="filled" wire:click="clearCart" class="cursor-pointer" size="sm">Clear Cart
                </flux:button>
            @endif
        </div>

        <div class="space-y-4 lg:gap-4 lg:flex lg:items-start">
            <div class="lg:flex-1">
                @if ($this->cartItems->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
                        <!-- Illustration -->
                        <div class="mb-8">
                            <img src="{{ asset('images/empty-states/empty-cart.svg') }}" alt="Empty Cart"
                                class="w-72 h-72 mx-auto" />
                        </div>

                        <!-- Heading -->
                        <h2 class="text-2xl font-bold text-zinc-900 mb-3">
                            Your cart is empty
                        </h2>

                        <!-- Description -->
                        <p class="text-zinc-600 mb-8 max-w-md">
                            Looks like you haven't added anything to your cart yet. Start shopping to find
                            amazing products!
                        </p>

                        <!-- Primary CTA -->
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <flux:button href="{{ route('products') }}" wire:navigate variant="primary"
                                class="w-full sm:w-auto">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                Start Shopping
                            </flux:button>

                            <flux:button href="{{ route('home') }}" variant="ghost" class="w-full sm:w-auto">
                                Back to Home
                            </flux:button>
                        </div>
                    </div>
                @else
                    <div class="space-y-4">
                        <div>
                            <section class="space-y-2">
                                @foreach ($this->cartItems as $item)
                                    <flux:card class="p-0 overflow-hidden">
                                        <div class="flex items-start gap-3 p-3 py-4">
                                            <div class="shrink-0 w-20 h-20 rounded border bg-zinc-50 overflow-hidden">
                                                @if ($item->product->image_path)
                                                    <img class="object-cover w-full h-full"
                                                        src="{{ $item->product->image_url }}"
                                                        alt="{{ $item->product->name }}" />
                                                @else
                                                    <flux:icon.photo
                                                        class="w-full h-full p-2 text-zinc-200 stroke-1" />
                                                @endif
                                            </div>

                                            <div class="flex-1">
                                                <a href="{{ route('products.show', $item->product) }}" wire:navigate
                                                    class="font-medium hover:underline">
                                                    {{ $item->product->name }}
                                                </a>

                                                <flux:text class="text-xs"><span class="text-zinc-600">Item no:
                                                    </span>
                                                    {{ $item->product->sku }}
                                                </flux:text>

                                                <flux:input.group class="mt-2">
                                                    <flux:button icon="minus" size="xs"
                                                        class="cursor-pointer text-zinc-500!"
                                                        wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})">
                                                    </flux:button>
                                                    <flux:input value="{{ $item->quantity }}" disabled
                                                        class="max-w-8! outline-none! border-none! ring-0 focus:outline-none! focus:border-none!"
                                                        style="outline: none; padding-left: 0 !important; padding-right: 0 !important; text-align: center !important;"
                                                        size="xs" />
                                                    <flux:button icon="plus" size="xs"
                                                        class="cursor-pointer text-zinc-500!"
                                                        wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})">
                                                    </flux:button>
                                                </flux:input.group>
                                            </div>
                                            <div>
                                                @if ($item->product->hasDiscount())
                                                    <p class="font-semibold text-sheffield-blue text-right">
                                                        {{ $item->product->formatted_final_price }}</p>
                                                    <div class="flex items-center flex-wrap gap-x-2  text-right">
                                                        <p class="text-sm text-zinc-500 line-through">
                                                            {{ $item->product->formatted_price }}</p>
                                                        <flux:badge color="amber" size="sm">
                                                            -{{ $item->product->discountPercentage() }}
                                                        </flux:badge>
                                                    </div>
                                                @else
                                                    <p class="font-semibold text-sheffield-blue">
                                                        {{ $item->product->formatted_final_price }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="bg-zinc-50 px-3 py-2 flex items-center border-t border-zinc-100">
                                            <div class="flex items-center gap-4">
                                                <flux:modal.trigger name="remove-item-{{ $item->id }}">
                                                    <flux:button variant="ghost" size="xs" icon="trash"
                                                        icon-variant="outline" class="cursor-pointer">Remove
                                                    </flux:button>
                                                </flux:modal.trigger>

                                                <flux:modal name="remove-item-{{ $item->id }}" variant="floating"
                                                    class="min-w-88 rounded-xs!">
                                                    <div class="space-y-6">
                                                        <div>
                                                            <flux:heading size="lg">Remove from Cart
                                                            </flux:heading>

                                                            <flux:text class="mt-2">
                                                                Do you really want to remove this item from cart?
                                                            </flux:text>
                                                        </div>

                                                        <div class="flex gap-2">
                                                            <flux:modal.close>
                                                                <flux:button
                                                                    wire:click="toggleWishlist({{ $item->product->id }})"
                                                                    class="cursor-pointer">
                                                                    <x-slot name="icon">
                                                                        <flux:icon.heart
                                                                            variant="{{ $this->inWishlist($item->product->id) ? 'solid' : 'outline' }}"
                                                                            @class([
                                                                                'size-4',
                                                                                'text-red-500' => $this->inWishlist($item->product->id),
                                                                            ]) />
                                                                    </x-slot>
                                                                    {{ $this->inWishlist($item->product->id) ? 'Remove Wishlist' : 'Save for later' }}
                                                                </flux:button>
                                                            </flux:modal.close>

                                                            <flux:spacer />

                                                            <flux:button type="submit" variant="danger"
                                                                class="cursor-pointer" icon="trash"
                                                                wire:click="removeItem({{ $item->id }})">Remove
                                                                Item
                                                            </flux:button>
                                                        </div>
                                                    </div>
                                                </flux:modal>

                                                <flux:button wire:click="toggleWishlist({{ $item->product->id }})"
                                                    variant="ghost" size="xs" class="cursor-pointer">
                                                    <x-slot name="icon">
                                                        <flux:icon.heart
                                                            variant="{{ $this->inWishlist($item->product->id) ? 'solid' : 'outline' }}"
                                                            @class([
                                                                'size-4',
                                                                'text-red-500' => $this->inWishlist($item->product->id),
                                                            ]) />
                                                    </x-slot>
                                                    {{ $this->inWishlist($item->product->id) ? 'Remove Wishlist' : 'Add Wishlist' }}
                                                </flux:button>
                                            </div>

                                            <div class="ms-auto flex items-center gap-1">
                                                <p class="text-zinc-600 text-xs font-medium">Total:</p>
                                                <span
                                                    class="font-medium text-sm">{{ format_currency($item->product->finalPrice * $item->quantity) }}</span>
                                            </div>
                                        </div>
                                    </flux:card>
                                @endforeach
                            </section>
                        </div>
                    </div>
                @endif
            </div>

            @if ($this->cartItems->isNotEmpty())
                <div class="w-full lg:max-w-sm sticky top-44">
                    <div class="bg-white rounded-sm border">
                        <div>
                            <h3 class="font-medium text-sm uppercase px-3 py-2 border-b">
                                Cart Summary
                            </h3>
                            <div class="p-3 py-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-zinc-600 text-sm font-medium">Subtotal:</p>
                                    <span
                                        class="font-semibold text-right">{{ format_currency($cartSummary['subtotal']) }}</span>
                                </div>
                            </div>

                            <div class="border-t p-3">
                                <flux:button wire:click="proceedToCheckout" class="w-full group cursor-pointer"
                                    variant="primary">Proceed
                                    to Checkout
                                    <x-slot name="iconTrailing">
                                        <flux:icon.chevron-right
                                            class="size-4 ms-3 group-hover:translate-x-1 transition-transform" />
                                    </x-slot>
                                </flux:button>
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

    {{-- Accessories Confirmation Modal --}}
    <flux:modal name="accessories-confirmation" variant="flyout" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Missing Accessories</flux:heading>
                <flux:subheading class="mt-2">
                    Some items in your cart have recommended accessories
                </flux:subheading>
            </div>

            <div class="space-y-3">
                @foreach ($this->productsWithMissingAccessories as $product)
                    <div class="border rounded-sm p-3 flex items-start gap-3">
                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}"
                            class="w-16 h-16 object-cover rounded-sm">

                        <div class="flex-1">
                            <p class="font-medium text-sm">{{ $product['name'] }}</p>
                            <p class="text-xs text-zinc-600 mt-1">
                                Missing {{ $product['accessories_count'] }}
                                {{ Str::plural('accessory', $product['accessories_count']) }}
                            </p>

                            <flux:button size="xs" variant="ghost" class="mt-2 cursor-pointer"
                                href="{{ route('products.show', $product['slug']) }}#accessories">
                                View accessories
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex gap-2 pt-4 border-t">
                <flux:modal.close>
                    <flux:button variant="primary" class="cursor-pointer flex-1" :href="route('checkout.shipping')"
                        wire:navigate>
                        Continue without accessories
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
