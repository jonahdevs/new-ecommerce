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

<x-customer.card title="Recently" titleEm="Viewed">
    <x-slot:icon>
        <flux:icon.eye />
    </x-slot:icon>

    @if ($this->products->isEmpty())
        <div class="text-center py-16 flex flex-col items-center justify-center">
            <flux:icon.eye class="w-12 h-12 text-zinc-300 mb-4" />
            <h4 class="text-lg font-medium text-zinc-900">{{ __('No recently viewed products') }}</h4>
            <p class="text-sm text-zinc-500 mt-1">{{ __('Products you view will appear here.') }}</p>
            <flux:button variant="customer-primary" href="{{ route('shop.index') }}" wire:navigate class="mt-6">
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
</x-customer.card>
