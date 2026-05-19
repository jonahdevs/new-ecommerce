<?php

use App\Services\CartService;
use App\Services\WishlistService;
use Livewire\Component;
use App\Models\Product;

new class extends Component {
    public Product $product;
    public int $recommendedQuantity = 1;

    public bool $wishlisted = false;
    public int $cartQuantity = 1;

    public function mount(WishlistService $wishlist): void
    {
        $this->cartQuantity = $this->recommendedQuantity;
        $this->wishlisted = $wishlist->has($this->product->id);
    }

    public function increaseQuantity(): void
    {
        $this->cartQuantity++;
    }

    public function decreaseQuantity(): void
    {
        if ($this->cartQuantity > 1) {
            $this->cartQuantity--;
        }
    }

    public function addToCart(CartService $cartService): void
    {
        try {
            $cartService->addItem($this->product->id, $this->cartQuantity);
            $this->dispatch('cart-updated');
            $this->dispatch('notify', title: 'Cart Updated', variant: 'success', message: 'Accessory added to your cart');
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Add to Cart Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to add to cart');
        }
    }

    public function toggleWishlist(WishlistService $wishlistService): void
    {
        try {
            $added = $wishlistService->toggle($this->product->id);
            $this->wishlisted = $added;
            $this->dispatch('wishlist-updated');
            $this->dispatch('notify', title: 'Wishlist Updated', variant: 'success', message: $added ? 'Added to wishlist' : 'Removed from wishlist');
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Wishlist Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update wishlist');
        }
    }
};
?>

<flux:card class="p-0 flex flex-col overflow-hidden">

    {{-- Image + details row --}}
    <div class="grid grid-cols-[100px_1fr] flex-1">

        {{-- Image --}}
        <a wire:navigate href="{{ route('products.show', $product) }}"
            class="bg-zinc-50 flex items-center justify-center overflow-hidden">
            @if ($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" loading="lazy" />
            @else
                <flux:icon.photo class="w-10 h-10 text-zinc-300 stroke-1" />
            @endif
        </a>

        {{-- Details --}}
        <div class="p-3 flex flex-col min-w-0">

            {{-- Name --}}
            <a wire:navigate href="{{ route('products.show', $product) }}"
                class="text-sm font-medium text-zinc-800 leading-snug line-clamp-2 hover:text-secondary transition-colors mn-1.5">
                {{ $product->name }}
            </a>

            {{-- Price --}}
            <div class="flex items-baseline gap-2 mb-1.5">
                <span class="text-base font-semibold text-secondary">
                    {{ $product->formatted_final_price }}
                </span>
                @if ($product->hasDiscount())
                    <span class="text-xs text-zinc-400 line-through">
                        {{ $product->formatted_price }}
                    </span>
                @endif
            </div>

            {{-- Stock status --}}
            @php
                $inStock = $product->manage_stock
                    ? $product->stock_quantity > 0
                    : $product->stock_status === 'in_stock';

                $isBackorder =
                    $product->stock_status === 'backorder' ||
                    (!$product->manage_stock && $product->stock_status === 'backorder');
            @endphp

            @if ($inStock)
                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-green-700 w-fit">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                    In stock
                </span>
            @elseif ($isBackorder)
                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-700 w-fit">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>
                    Backorder
                </span>
            @else
                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-red-600 w-fit">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span>
                    Out of stock
                </span>
            @endif

            {{-- Recommended qty --}}
            <span wire:cloak wire:show="recommendedQuantity > 0" class="text-[11px] text-zinc-400">
                Recommended qty: {{ $recommendedQuantity }}
            </span>

        </div>
    </div>

    {{-- Actions bar --}}
    <div class="border-t border-zinc-200 px-3 py-2 flex items-center gap-2">

        {{-- Qty stepper --}}
        <div class="flex items-center border border-zinc-300 rounded-md overflow-hidden">
            <button wire:click="decreaseQuantity"
                class="w-7 h-7 flex items-center justify-center text-zinc-500 hover:bg-zinc-100 transition-colors cursor-pointer border-r border-zinc-200"
                aria-label="Decrease quantity">−</button>

            <span class="w-7 text-center text-sm font-medium text-zinc-800 bg-white">
                {{ $cartQuantity }}
            </span>

            <button wire:click="increaseQuantity"
                class="w-7 h-7 flex items-center justify-center text-zinc-500 hover:bg-zinc-100 transition-colors cursor-pointer border-l border-zinc-200"
                aria-label="Increase quantity">+</button>
        </div>

        {{-- Add to cart --}}
        <flux:button wire:click="addToCart" wire:loading.attr="disabled" wire:target="addToCart" size="sm"
            icon-variant="outline" icon="shopping-cart" class="cursor-pointer">
            Add to Cart
        </flux:button>

        {{-- Wishlist --}}
        <flux:button wire:click.stop="toggleWishlist" size="sm"
            title="{{ $wishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}" class="cursor-pointer ml-auto">
            <x-slot name="icon">
                <flux:icon.heart variant="{{ $wishlisted ? 'solid' : 'outline' }}" @class(['size-4', 'text-red-500' => $wishlisted]) />
            </x-slot>
        </flux:button>

        {{-- View product --}}
        <flux:button size="sm" href="{{ route('products.show', $product) }}" target="_blank"
            rel="noopener noreferrer" title="View product" icon="arrow-top-right-on-square" class="cursor-pointer" />
    </div>
</flux:card>
