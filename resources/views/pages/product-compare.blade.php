<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Defer;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Services\CompareService;
use App\Services\CartService;

new #[Defer] #[Layout('layouts.guest')] class extends Component {
    public int $cartQuantity = 1;
    public ?int $cartItemId = null;
    #[Computed]
    #[On('compare-updated')]
    public function products()
    {
        return app(CompareService::class)->items();
    }

    public function removeProduct($productId)
    {
        try {
            $added = app(CompareService::class)->toggle($productId);
            $this->inCompare = $added;

            // Dispatch events
            $this->dispatch('compare-updated');

            $this->dispatch('notify', title: $added ? 'Comparison Updated' : 'Comparison Updated', variant: 'success', message: $added ? 'Product added to comparison list' : 'Product removed from comparison list');
        } catch (\Exception $e) {
            $this->dispatch('notify', title: 'Action Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update comparison');
        }
    }

    public function addToCart($productId)
    {
        try {
            $cartService = app(CartService::class);
            $cartService->addItem($productId, $this->cartQuantity);

            $this->inCart = true;
            $cartItem = $cartService->getCartItem($productId);
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
};

?>

@placeholder
    <div>
        <div class="bg-zinc-100">
            <div class="flex items-center gap-3 container mx-auto py-2.5 px-4">
                <flux:skeleton animate="shimmer" class="w-4 h-4" />
                <flux:skeleton animate="shimmer" class="w-14 h-4" />
                <flux:skeleton animate="shimmer" class="w-3 h-4" />
                <flux:skeleton animate="shimmer" class="w-14 h-4" />
            </div>
        </div>

        <section class="container mx-auto px-4 py-4 min-h-[80svh]">
            <!-- Compare Header -->
            <div class="flex items-center justify-between mb-4">
                <flux:skeleton class="w-48 h-8" animate="shimmer" />
            </div>

            <!-- Comparison Table Placeholder -->
            <div class="border rounded-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b dark:border-zinc-700">
                                <th class="p-4 text-left w-48">
                                    <flux:skeleton animate="shimmer" class="w-24 h-4" />
                                </th>
                                @for ($i = 0; $i < 3; $i++)
                                    <th class="p-4 text-center border-l dark:border-zinc-700">
                                        <div class="space-y-3">
                                            <!-- Product Image Skeleton -->
                                            <flux:skeleton animate="shimmer" class="w-32 h-32 mx-auto" />
                                            <!-- Product Name Skeleton -->
                                            <flux:skeleton animate="shimmer" class="w-40 h-4 mx-auto" />
                                        </div>
                                    </th>
                                @endfor
                            </tr>
                        </thead>

                        <tbody class="divide-y dark:divide-zinc-700">
                            @foreach (['Review', 'Price', 'Brand', 'Categories', 'Description', 'Stock Status', 'Actions', 'Remove'] as $row)
                                <tr>
                                    <td class="p-4">
                                        <flux:skeleton animate="shimmer" class="w-32 h-4" />
                                    </td>
                                    @for ($i = 0; $i < 3; $i++)
                                        <td class="p-4 text-center border-l dark:border-zinc-700">
                                            @if ($row === 'Review')
                                                <flux:skeleton animate="shimmer" class="w-32 h-4 mx-auto" />
                                            @elseif ($row === 'Price')
                                                <flux:skeleton animate="shimmer" class="w-24 h-5 mx-auto" />
                                            @elseif ($row === 'Description')
                                                <div class="space-y-2">
                                                    <flux:skeleton animate="shimmer" class="w-full h-3 mx-auto" />
                                                    <flux:skeleton animate="shimmer" class="w-5/6 h-3 mx-auto" />
                                                    <flux:skeleton animate="shimmer" class="w-4/6 h-3 mx-auto" />
                                                </div>
                                            @elseif ($row === 'Actions')
                                                <flux:skeleton animate="shimmer" class="w-32 h-8 mx-auto" />
                                            @elseif ($row === 'Remove')
                                                <flux:skeleton animate="shimmer" class="w-8 h-8 mx-auto" />
                                            @else
                                                <flux:skeleton animate="shimmer" class="w-28 h-4 mx-auto" />
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Notice Placeholder -->
            <div class="mt-4 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg lg:hidden">
                <flux:skeleton animate="shimmer" class="w-full h-4" />
            </div>

            <div class="mt-10">
                <flux:skeleton animate="shimmer" class="w-44 h-5 mb-4" />
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @for ($i = 1; $i <= 6; $i++)
                        <x-product-card-placeholder />
                    @endfor
                </div>
            </div>
        </section>

    </div>
@endplaceholder

<div>
    {{-- Breadcrumb --}}
    <div class="bg-zinc-100">
        <flux:breadcrumbs class="container mx-auto px-4 py-2.5">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item>Compare</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <section class="container mx-auto px-4 py-4 min-h-[80svh]">
        <!-- Wishlist Header -->
        <div class="flex items-center justify-between mb-4">
            <flux:heading level="1" class="text-2xl! font-bold! text-zinc-900">Product Compare</flux:heading>
        </div>

        @if ($this->products->isEmpty())
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
                <!-- Illustration -->
                <div class="mb-8">
                    <img src="{{ asset('images/empty-states/product-comparison.svg') }}" alt="No Products to Compare"
                        class="w-72 h-72 mx-auto" />
                </div>

                <!-- Heading -->
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-3">
                    No products to compare
                </h2>

                <!-- Description -->
                <p class="text-zinc-600 dark:text-zinc-400 mb-8 max-w-md">
                    Start comparing products to make better purchasing decisions. Add products from any product page to
                    see them side by side.
                </p>

                <!-- Primary CTA -->
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <flux:button href="{{ route('shop.index') }}" wire:navigate variant="primary"
                        class="w-full sm:w-auto" icon="magnifying-glass">
                        Browse Products
                    </flux:button>

                    <flux:button href="{{ route('home') }}" variant="ghost" class="w-full sm:w-auto">
                        Back to Home
                    </flux:button>
                </div>
            </div>
        @else
            <!-- Comparison Table -->
            <div class="border rounded-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b dark:border-zinc-700">
                                <th class="p-4 text-left text-sm font-medium text-zinc-600 dark:text-zinc-400 w-48">
                                    Products</th>
                                @foreach ($this->products as $product)
                                    <th class="p-4 text-center border-l dark:border-zinc-700">
                                        <div class="space-y-3">
                                            <!-- Product Image -->
                                            <a href="{{ route('products.show', $product) }}" wire:navigate
                                                class="inline-block">
                                                <img src="{{ $product->imageUrl }}" alt="{{ $product->name }}"
                                                    class="w-32 h-32 object-cover mx-auto">
                                            </a>

                                            <!-- Product Name -->
                                            <a href="{{ route('products.show', $product) }}" wire:navigate
                                                class="block font-medium text-zinc-900 dark:text-white hover:text-brand-secondary hover:underline">
                                                {{ $product->name }}
                                            </a>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="divide-y dark:divide-zinc-700">
                            <!-- Star Review -->
                            <tr>
                                <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                    Review</td>
                                @foreach ($this->products as $product)
                                    <td class="p-4 text-center border-l dark:border-zinc-700">
                                        {{-- Star Rating - Always show 5 stars --}}
                                        <div class="flex items-center justify-center gap-1">
                                            <div class="flex gap-0.5">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if ($product->reviews_avg_rating && $i <= floor($product->reviews_avg_rating))
                                                        {{-- Full star --}}
                                                        <flux:icon.star variant="solid"
                                                            class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                                                    @elseif ($product->reviews_avg_rating && $i - 0.5 <= $product->reviews_avg_rating)
                                                        {{-- Half star --}}
                                                        <div class="relative w-4 h-4">
                                                            <flux:icon.star variant="solid"
                                                                class="w-4 h-4 text-zinc-300" />
                                                            <div class="absolute inset-0 overflow-hidden"
                                                                style="width: 50%;">
                                                                <flux:icon.star variant="solid"
                                                                    class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                                                            </div>
                                                        </div>
                                                    @else
                                                        {{-- Empty star --}}
                                                        <flux:icon.star variant="solid"
                                                            class="w-4 h-4 text-zinc-300" />
                                                    @endif
                                                @endfor
                                            </div>
                                            @if ($product->reviews_avg_rating)
                                                <span
                                                    class="text-xs text-zinc-500">{{ number_format($product->reviews_avg_rating, 1) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                            <!-- Pricing Row -->
                            <tr>
                                <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                    Price</td>
                                @foreach ($this->products as $product)
                                    <td class="p-4 text-center border-l dark:border-zinc-700">
                                        <div class="pt-2 mt-auto">
                                            @if ($product->hasDiscount())
                                                <div class="flex items-center justify-center flex-wrap gap-x-2">
                                                    <p class="font-semibold text-brand-secondary">
                                                        {{ $product->formatted_final_price }}</p>
                                                    <p class="text-sm text-zinc-500 line-through">
                                                        {{ $product->formatted_price }}</p>
                                                </div>
                                            @else
                                                <p class="font-semibold text-brand-secondary">
                                                    {{ $product->formatted_final_price }}</p>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Brand Row -->
                            <tr>
                                <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                    Brand</td>
                                @foreach ($this->products as $product)
                                    <td class="p-4 text-center border-l dark:border-zinc-700">
                                        <p class="text-zinc-600 text-sm uppercase tracking-wide">
                                            {{ $product->brand?->name }}</p>
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Categories Row -->
                            <tr>
                                <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                    Categories</td>
                                @foreach ($this->products as $product)
                                    <td
                                        class="p-4 text-center text-sm text-zinc-600 dark:text-zinc-400 border-l dark:border-zinc-700">
                                        @if ($product->categories->isNotEmpty())
                                            {{ $product->categories->pluck('name')->join(', ') }}
                                        @else
                                            <span class="text-zinc-400 dark:text-zinc-600">N/A</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>


                            <!-- Description Row -->
                            <tr>
                                <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                    Description</td>
                                @foreach ($this->products as $product)
                                    <td
                                        class="p-4 text-center text-sm text-zinc-600 dark:text-zinc-400 border-l dark:border-zinc-700">
                                        {{ Str::limit($product->short_description ?? $product->description, 200) }}
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Weight Row -->
                            @if ($this->products->pluck('weight')->filter()->isNotEmpty())
                                <tr>
                                    <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                        Weight</td>
                                    @foreach ($products as $product)
                                        <td
                                            class="p-4 text-center text-sm text-zinc-600 dark:text-zinc-400 border-l dark:border-zinc-700">
                                            {{ $product->weight ? $product->weight . ' kg' : 'N/A' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endif

                            <!-- Dimensions Row -->
                            @if (
                                $this->products->pluck('length')->filter()->isNotEmpty() ||
                                    $this->products->pluck('width')->filter()->isNotEmpty() ||
                                    $this->products->pluck('height')->filter()->isNotEmpty())
                                <tr>
                                    <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                        Dimensions (L x W x H)</td>
                                    @foreach ($this->products as $product)
                                        <td
                                            class="p-4 text-center text-sm text-zinc-600 dark:text-zinc-400 border-l dark:border-zinc-700">
                                            @if ($product->length || $product->width || $product->height)
                                                {{ $product->length ?? 'N/A' }} x {{ $product->width ?? 'N/A' }} x
                                                {{ $product->height ?? 'N/A' }} cm
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endif

                            <!-- Technical Specifications -->
                            @php
                                $allSpecs = collect();
                                foreach ($this->products as $product) {
                                    if (is_array($product->technical_specification)) {
                                        $allSpecs = $allSpecs->merge(array_keys($product->technical_specification));
                                    }
                                }
                                $allSpecs = $allSpecs->unique()->sort()->values();
                            @endphp

                            @foreach ($allSpecs as $specKey)
                                <tr>
                                    <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                        {{ Str::title(str_replace('_', ' ', $specKey)) }}
                                    </td>
                                    @foreach ($this->products as $product)
                                        <td
                                            class="p-4 text-center text-sm text-zinc-600 dark:text-zinc-400 border-l dark:border-zinc-700">
                                            @if (is_array($product->technical_specification) && isset($product->technical_specification[$specKey]))
                                                {{ $product->technical_specification[$specKey] }}
                                            @else
                                                <span class="text-zinc-400 dark:text-zinc-600">N/A</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach

                            <!-- Stock Status Row -->
                            <tr>
                                <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                    Stock Status</td>
                                @foreach ($this->products as $product)
                                    <td class="p-4 text-center border-l dark:border-zinc-700">
                                        @if ($product->stock_status === 'in_stock')
                                            <flux:badge color="green">In Stock</flux:badge>
                                        @elseif($product->stock_status === 'out_of_stock')
                                            <flux:badge color="red">Out of Stock</flux:badge>
                                        @else
                                            <flux:badge color="orange">
                                                {{ Str::title(str_replace('_', ' ', $product->stock_status)) }}
                                            </flux:badge>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            @island
                                <!-- Buy Now Row -->
                                <tr>
                                    <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                        Actions</td>
                                    @foreach ($this->products as $product)
                                        <td class="p-4 text-center border-l dark:border-zinc-700">
                                            <flux:button wire:click="addToCart({{ $product->id }})" variant="primary"
                                                size="sm" icon="shopping-cart" class="cursor-pointer">Add to Cart
                                            </flux:button>
                                        </td>
                                    @endforeach
                                </tr>

                                <!-- Remove Row -->
                                <tr>
                                    <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                        Remove</td>
                                    @foreach ($this->products as $product)
                                        <td class="p-4 text-center border-l dark:border-zinc-700">
                                            <flux:button wire:click="removeProduct({{ $product->id }})" icon="trash"
                                                icon-variant="outline" size="sm" variant="ghost"
                                                class="text-red-500! cursor-pointer">
                                            </flux:button>
                                        </td>
                                    @endforeach
                                </tr>
                            @endisland
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Notice -->
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg lg:hidden">
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    Tip: Scroll horizontally to see all product comparisons on mobile devices
                </p>
            </div>
        @endif

        <livewire:product-recommendations type="recently_viewed" />
    </section>

</div>
