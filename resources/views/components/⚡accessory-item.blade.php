<?php

use App\Services\WishlistService;
use Livewire\Component;
use App\Models\Product;
use App\Services\CartService;

new class extends Component {
    public Product $product;

    public bool $wishlisted = false;
    public int $cartQuantity = 1;

    public function mount(WishlistService $wishlist)
    {
        $this->wishlisted = $wishlist->has($this->product?->id);
    }

    public function toggleWishlist(WishlistService $wishlistService)
    {
        try {
            $added = $wishlistService->toggle($this->product?->id);
            $this->wishlisted = $added;

            $this->dispatch('wishlist-updated');

            $this->dispatch('notify', variant: 'success', message: $added ? 'Added to wishlist' : 'Removed from wishlist');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update wishlist');
        }
    }

    public function goToProduct()
    {
        return $this->redirect(route('products.show', $this->product), navigate: true);
    }

    public function addToCart(CartService $cartService)
    {
        try {
            $cartService->addItem($this->product->id, $this->cartQuantity);

            $this->inCart = true;
            $cartItem = $cartService->getCartItem($this->product->id);
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
};
?>

<div class="bg-white rounded-sm border p-2">
    <div class="grid grid-cols-3 gap-2">
        <figure
            class="col-span-1 w-full aspect-square overflow-hidden mb-2 relative bg-zinc-50 flex items-center justify-center">
            @if ($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-300 " loading="lazy">
            @else
                <flux:icon.photo class="w-16 h-16 text-zinc-400 stroke-1" />
            @endif
        </figure>

        <div class="col-span-2">
            <a wire:navigate href="{{ route('products.show', $product) }}"
                class="text-sm text-slate-500 line-clamp-2">{{ $product->name }}</a>
            @if ($product->hasDiscount())
                <div class="flex items-center flex-wrap gap-x-2">
                    <p class="font-semibold text-sheffield-blue">
                        {{ $product->formatted_final_price }}</p>
                    <p class="text-sm text-zinc-500 line-through">
                        {{ $product->formatted_price }}
                    </p>
                </div>
            @else
                <p class="font-semibold text-sheffield-blue">
                    {{ $product->formatted_final_price }}
                </p>
            @endif

        </div>
    </div>
    <flux:separator class="my-2" />

    <div class="flex items-center justify-between">
        <flux:button class="cursor-pointer" icon="shopping-cart" wire:click="addToCart" size="sm" variant="filled">
            Add to Cart
        </flux:button>

        <flux:button wire:click.stop="toggleWishlist" icon="heart" title="Wishlist"
            icon-variant="{{ $wishlisted ? 'solid' : 'outline' }}" @class([
                'cursor-pointer',
                'text-red-500! border-red-500!' => $wishlisted,
            ]) size="sm">
        </flux:button>
    </div>
</div>
