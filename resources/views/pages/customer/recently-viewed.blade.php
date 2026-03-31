<?php

use App\Services\ProductService;
use Livewire\Attributes\{Layout, Computed};
use Livewire\Component;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.customer')] class extends Component {
    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');
    }

    #[Computed]
    public function products()
    {
        return app(ProductService::class)->recentlyViewed(24);
    }
}; ?>

<div class="space-y-4">
    <flux:card class="p-0 rounded-md">
        <div class="border-b px-4 py-3">
            <flux:heading size="lg">{{ __('Recently Viewed') }}</flux:heading>
        </div>

        <div class="p-4">
            @if ($this->products->isEmpty())
                <div class="text-center py-12">
                    <flux:icon.eye class="w-12 h-12 mx-auto text-zinc-300 mb-4" />
                    <flux:heading size="lg">{{ __('No recently viewed products') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Products you view will appear here.') }}</flux:text>
                    <flux:button href="{{ route('shop.index') }}" wire:navigate variant="primary" class="mt-4">
                        {{ __('Start Shopping') }}
                    </flux:button>
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($this->products as $product)
                        <livewire:product-card :product="$product" :key="$product->id" />
                    @endforeach
                </div>
            @endif
        </div>
    </flux:card>
</div>
