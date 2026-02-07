<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Defer;
use App\Services\CartService;
use App\Services\WishlistService;
use App\Models\Cart;
use Flux\Flux;

new #[Defer] #[Layout('layouts.guest')] class extends Component {
    public array $cartSummary = [];
    public Cart $cart;

    public function mount()
    {
        $cartService = app(CartService::class);
        $this->cart = $cartService->getCart();

        $this->cartSummary = $cartService->summary($this->cart);
    }

    #[Computed]
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
};
?>

@placeholder
    <div>
        <div class="bg-zinc-100">
            <div class="flex items-center gap-3 container mx-auto py-4 px-4">
                <flux:skeleton animate="shimmer" class="w-32 h-4" />
                <flux:skeleton animate="shimmer" class="w-8 h-4" />
                <flux:skeleton animate="shimmer" class="w-32 h-4" />
                <flux:skeleton animate="shimmer" class="w-8 h-4" />
                <flux:skeleton animate="shimmer" class="w-44 h-4" />
            </div>
        </div>

        <div class="mx-auto container px-4 py-4 min-h-[80svh]">
            <!-- Cart Header -->
            <flux:skeleton class="w-48 h-4 mb-6" animate="shimmer" />

            <div class="mt-4 md:gap-6 lg:flex lg:items-start">
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

                <div class="w-full max-w-sm">
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
        <flux:breadcrumbs class="container mx-auto py-4 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item>Cart</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="mx-auto container px-4 py-4 min-h-[80svh]">

        <!-- Cart Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900">Cart</h1>
            </div>
        </div>

        <div class="mt-4 md:gap-6 lg:flex lg:items-start">
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
                                    <div class="rounded-sm bg-white overflow-hidden border">
                                        <div class="flex items-start gap-3 p-3 py-4">
                                            <div class="shrink-0 px-4">
                                                <img class="h-20 w-20" src="{{ $item->product->image_url }}"
                                                    alt="{{ $item->product->name }}" />

                                            </div>

                                            <div class="flex-1">
                                                <a href="{{ route('products.show', $item->product) }}" wire:navigate
                                                    class="text-base font-medium hover:underline">
                                                    {{ $item->product->name }}
                                                </a>

                                                <flux:input.group class="mt-2">
                                                    <flux:button icon="minus" size="sm"
                                                        class="cursor-pointer text-zinc-500!"
                                                        wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})">
                                                    </flux:button>
                                                    <flux:input value="{{ $item->quantity }}" disabled
                                                        class="max-w-8! outline-none! border-none! ring-0 focus:outline-none! focus:border-none!"
                                                        style="outline: none; padding-left: 0 !important; padding-right: 0 !important; text-align: center !important;"
                                                        size="sm" />
                                                    <flux:button icon="plus" size="sm"
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

                                        <div class="bg-zinc-50 px-3 py-2 flex items-center">
                                            <div class="flex items-center gap-4">
                                                <flux:modal.trigger name="remove-item-{{ $item->id }}">
                                                    <flux:button variant="ghost" size="xs" icon="trash"
                                                        icon-variant="outline" class="cursor-pointer">Remove
                                                    </flux:button>
                                                </flux:modal.trigger>

                                                <flux:modal name="remove-item-{{ $item->id }}" variant="floating"
                                                    class="min-w-[22rem] rounded-xs!">
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
                                    </div>
                                @endforeach
                            </section>
                        </div>
                    </div>
                @endif
            </div>

            @if ($this->cartItems->isNotEmpty())
                <div class="w-full max-w-sm sticky top-44">
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
                                <flux:button :href="route('checkout.summary')" wire:navigate
                                    class="w-full group cursor-pointer" variant="primary">Proceed
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
</div>
