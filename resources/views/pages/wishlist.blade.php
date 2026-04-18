<?php

use App\Models\Product;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.guest')] class extends Component {
    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');
    }

    #[Computed]
    #[On('wishlist-updated')]
    public function products()
    {
        $columns = ['products.id', 'products.name', 'products.slug', 'products.brand_id', 'products.price', 'products.sale_price', 'products.image_path', 'products.short_description', 'products.type', 'products.requires_quotation', 'products.reviews_enabled', 'products.stock_status', 'products.manage_stock', 'products.stock_quantity', 'products.average_rating', 'products.reviews_count'];

        $with = [
            'brand:id,name,slug',
            'images' => fn($q) => $q->select(['id', 'product_id', 'image_path', 'alt_text', 'sort_order'])->limit(1),
            'variants' => fn($q) => $q
                ->where('is_active', true)
                ->whereNotNull('price')
                ->select(['id', 'product_id', 'price', 'sale_price', 'is_active']),
        ];

        if (auth()->check()) {
            return auth()->user()->wishlistProducts()->select($columns)->with($with)->active()->get();
        }

        $wishlistIds = request()->session()->get('wishlist', []);

        return Product::select($columns)->with($with)->whereIn('id', $wishlistIds)->get();
    }
};
?>

@placeholder
    <div>
        <div class="bg-zinc-100">
            <div class="flex items-center gap-3 container mx-auto py-2.5 px-4">
                <flux:skeleton animate="shimmer" class="w-32 h-4" />
                <flux:skeleton animate="shimmer" class="w-8 h-4" />
                <flux:skeleton animate="shimmer" class="w-32 h-4" />
                <flux:skeleton animate="shimmer" class="w-8 h-4" />
                <flux:skeleton animate="shimmer" class="w-44 h-4" />
            </div>
        </div>

        <section class="container mx-auto px-4 py-4 min-h-[80svh]">
            <!-- Wishlist Header -->
            <flux:skeleton class="w-48 h-4 mb-6" animate="shimmer" />

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @for ($i = 0; $i < 6; $i++)
                    <x-product-card-placeholder />
                @endfor
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

            <flux:breadcrumbs.item>Wishlist</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <section class="container mx-auto px-4 py-4 min-h-[80svh]">
        <!-- Wishlist Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <flux:heading level="1"
                    class="text-xl! sm:text-2xl! lg:text-3xl! font-bold! text-zinc-900 dark:text-zinc-100">
                    Wishlist
                </flux:heading>
            </div>
        </div>

        <div @class([
            'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4' => !$this->products->isEmpty(),
        ])>
            @forelse ($this->products as $product)
                <livewire:product-card :product="$product" />
            @empty
                <!-- Empty State -->
                <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
                    <!-- Illustration -->
                    <div class="mb-8">
                        <img src="{{ asset('images/empty-states/wishlist.svg') }}" alt="No Products to Compare"
                            class="w-72 h-72 mx-auto" />
                    </div>

                    <!-- Heading -->
                    <flux:heading size="xl" class="mb-3 text-lg! sm:text-xl! md:text-2xl!">
                        Your wishlist is empty
                    </flux:heading>

                    <!-- Description -->
                    <flux:text class="mb-8 max-w-md text-xs! sm:text-sm!">
                        Save your favorite products here to keep track of items you love. Start browsing and add
                        products to your wishlist!
                    </flux:text>

                    <!-- Primary CTA -->
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <flux:button href="{{ route('shop.index') }}" wire:navigate variant="primary"
                            class="w-full sm:w-auto">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Browse Products
                        </flux:button>

                        <flux:button href="{{ route('home') }}" wire:navigate variant="ghost" class="w-full sm:w-auto">
                            Back to Home
                        </flux:button>
                    </div>
                </div>
            @endforelse
        </div>

        <livewire:product-recommendations type="recently_viewed" />
    </section>
</div>
