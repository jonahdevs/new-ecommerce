<?php

use App\Services\WishlistService;
use App\Services\CompareService;
use App\Models\Product;
use Livewire\Component;

new class extends Component {
    public Product $product;

    public bool $wishlisted = false;

    public bool $inCompare = false;

    public function mount(WishlistService $wishlist, CompareService $compareService)
    {
        $this->wishlisted = $wishlist->has($this->product?->id);
        $this->inCompare = $compareService->has($this->product->id);
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

    public function toggleCompare(CompareService $compareService): void
    {
        try {
            $added = $compareService->toggle($this->product->id);
            $this->inCompare = $added;

            // Dispatch events
            $this->dispatch('compare-updated');

            $this->dispatch('notify', variant: 'success', message: $added ? 'Added to comparison' : 'Removed from comparison');
        } catch (\Exception $e) {
            $this->dispatch('notify', variant: 'danger', message: $e->getMessage() ?: 'Unable to update comparison');
        } finally {
            $this->loading = false;
        }
    }
};
?>


<div
    {{ $attributes->class(['bg-white overflow-hidden h-full border hover:shadow-[0px_0px_6px_2px_rgba(0,_0,_0,_0.1)] transition-all duration-300 ease-in-out group relative rounded-sm']) }}>
    <div class="h-full flex flex-col">
        <a href="#" wire:navigate class="block">
            <figure
                class="w-full aspect-square overflow-hidden mb-2 relative bg-zinc-50 flex items-center justify-center">
                @if ($product->image_url)
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                        class="w-full h-full object-cover hover:scale-105 transition-transform duration-300 "
                        loading="lazy">
                @else
                    <flux:icon.photo class="w-16 h-16 text-zinc-400 stroke-1" />
                @endif
            </figure>

            {{-- Quick action buttons --}}
            <div class="absolute top-2 right-2 flex flex-col gap-2 ">
                <flux:button wire:click="toggleWishlist" icon="heart"
                    icon-variant="{{ $wishlisted ? 'solid' : 'outline' }}" @class([
                        'cursor-pointer',
                        'text-red-500! border-red-500!' => $wishlisted,
                    ]) size="sm">
                </flux:button>

                <flux:button icon="eye" size="sm" icon-variant="outline">

                </flux:button>

                <flux:button wire:click="toggleCompare" icon="scale" size="sm" icon-variant="outline">

                </flux:button>

                <flux:button icon="shopping-cart" size="sm" icon-variant="outline">

                </flux:button>
            </div>
        </a>

        <div class="p-4 flex flex-col gap-1 h-full">
            {{-- Brand --}}
            @if ($product->brand)
                <p class="text-zinc-400 text-xs uppercase tracking-wide">{{ $product->brand?->name }}</p>
            @endif

            {{-- Product Name --}}
            <a class="text-sm text-zinc-700 line-clamp-2 group-hover:underline group-hover:text-sheffield-blue">
                {{ $product->name }}
            </a>

            {{-- Star Rating - Always show 5 stars --}}
            <div class="flex items-center gap-1">
                <div class="flex gap-0.5">
                    @for ($i = 1; $i <= 5; $i++)
                        @if ($product->average_rating && $i <= floor($product->average_rating))
                            {{-- Full star --}}
                            <flux:icon.star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                        @elseif ($product->average_rating && $i - 0.5 <= $product->average_rating)
                            {{-- Half star --}}
                            <div class="relative w-4 h-4">
                                <flux:icon.star class="w-4 h-4 text-zinc-300" />
                                <div class="absolute inset-0 overflow-hidden" style="width: 50%;">
                                    <flux:icon.star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                                </div>
                            </div>
                        @else
                            {{-- Empty star --}}
                            <flux:icon.star class="w-4 h-4 text-zinc-300" />
                        @endif
                    @endfor
                </div>
                @if ($product->average_rating)
                    <span class="text-xs text-zinc-500">{{ number_format($product->average_rating, 1) }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
