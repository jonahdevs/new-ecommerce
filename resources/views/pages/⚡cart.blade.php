<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use App\Services\CartService;

new #[Layout('layouts.guest')] class extends Component {
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
};
?>

@placeholder
    <div>
        <div class="mx-auto container px-4 py-4">
            {{-- Breadcrumb --}}
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                    <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                    Home
                </flux:breadcrumbs.item>

                <flux:breadcrumbs.item>Cart</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>
@endplaceholder

<div>
    <div class="mx-auto container px-4 py-4">
        {{-- Breadcrumb --}}
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item>Cart</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="mt-4 md:gap-6 lg:flex lg:items-start xl:gap-8">
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
                            <flux:button href="{{ route('products') }}" variant="primary" class="w-full sm:w-auto">
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
                            <div class="px-3 py-2 flex items-center justify-between">
                                <h1 class="font-medium text-lg">Cart <span
                                        class="text-sm text-zinc-600 tracking-wider">({{ $this->cartItems->count() }})</span>
                                </h1>

                                {{-- Clear All button --}}
                                <button wire:click="clearCart"
                                    class="flex items-center text-sm font-medium hover:text-red-500 hover:underline">
                                    <flux:icon.trash class="text-red-500 size-4 me-2" />
                                    Clear All
                                </button>
                            </div>

                            <section class="space-y-2">
                                @foreach ($this->cartItems as $item)
                                    <div class="rounded-sm bg-white overflow-hidden border">
                                        <div class="flex items-start gap-3 p-3 py-4">
                                            <div class="shrink-0 px-4">
                                                <img class="h-20 w-20" src="{{ $item->product->image_url }}"
                                                    alt="{{ $item->product->name }}" />

                                            </div>

                                            <div class="flex-1">
                                                <a href="{{ route('products.show', $item->product) }}"
                                                    class="text-base font-medium hover:underline">
                                                    {{ $item->product->name }}
                                                </a>

                                                <flux:input.group class="mt-2">
                                                    <flux:button icon="minus" size="sm"></flux:button>
                                                    <flux:input value="{{ $item->quantity }}"
                                                        class="max-w-8! outline-none! border-none! ring-0 focus:outline-none! focus:border-none!"
                                                        style="outline: none; padding-left: 0 !important; padding-right: 0 !important; text-align: center !important;"
                                                        size="sm" />
                                                    <flux:button icon="plus" size="sm"></flux:button>
                                                </flux:input.group>
                                            </div>
                                            <div>
                                                @if ($item->product->hasDiscount())
                                                    <p class="font-semibold text-sheffield-blue text-right">
                                                        {{ $item->product->formatted_final_price }}</p>
                                                    <div class="flex items-center flex-wrap gap-x-2  text-right">
                                                        <p class="text-sm text-zinc-500 line-through">
                                                            {{ $item->product->formatted_sale_price }}</p>
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
                                                <button type="button"
                                                    class="flex items-center gap-2 text-xs font-medium text-zinc-600">
                                                    <flux:icon.trash class="size-4" />
                                                    Remove
                                                </button>

                                                <button type="button"
                                                    class="flex items-center gap-2 text-xs text-zinc-600 font-medium">
                                                    <flux:icon.heart class="size-4" />
                                                    Add Wishlist
                                                </button>
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

            <div class="w-full max-w-sm"></div>
        </div>
    </div>
</div>
