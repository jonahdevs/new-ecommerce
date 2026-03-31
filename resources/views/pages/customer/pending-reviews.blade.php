<?php

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Review;
use Livewire\Attributes\{Layout, Computed};
use Livewire\Component;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.customer')] class extends Component {
    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');
    }

    #[Computed]
    public function pendingProducts()
    {
        $userId = auth()->id();

        // Get product IDs the user has already reviewed
        $reviewedProductIds = Review::where('user_id', $userId)->pluck('product_id');

        // Get products from delivered orders that haven't been reviewed
        return OrderItem::query()
            ->select('order_items.*')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $userId)
            ->where('orders.status', OrderStatus::DELIVERED)
            ->whereNotIn('order_items.product_id', $reviewedProductIds)
            ->with(['product:id,name,slug,image_path,price,sale_price', 'order:id,reference,created_at'])
            ->orderByDesc('orders.created_at')
            ->get()
            ->unique('product_id');
    }
}; ?>

<div class="space-y-4">
    <flux:card class="p-0 rounded-md">
        <div class="border-b px-4 py-3">
            <flux:heading size="lg">{{ __('Pending Reviews') }}</flux:heading>
        </div>

        <div class="p-4">
            @if ($this->pendingProducts->isEmpty())
                <div class="text-center py-12">
                    <flux:icon.star class="w-12 h-12 mx-auto text-zinc-300 mb-4" />
                    <flux:heading size="lg">{{ __('No pending reviews') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('You have reviewed all your purchased products.') }}</flux:text>
                    <flux:button href="{{ route('customer.orders.index') }}" wire:navigate variant="primary"
                        class="mt-4">
                        {{ __('View Orders') }}
                    </flux:button>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($this->pendingProducts as $item)
                        <div class="flex gap-4 p-4 border rounded-lg hover:bg-zinc-50 transition-colors">
                            {{-- Product Image --}}
                            <div class="w-20 h-20 shrink-0">
                                @if ($item->product?->image_path)
                                    <img src="{{ asset('storage/' . $item->product->image_path) }}"
                                        alt="{{ $item->product->name }}"
                                        class="w-full h-full object-cover rounded-md" />
                                @else
                                    <div class="w-full h-full bg-zinc-100 rounded-md flex items-center justify-center">
                                        <flux:icon.photo class="w-8 h-8 text-zinc-300" />
                                    </div>
                                @endif
                            </div>

                            {{-- Product Info --}}
                            <div class="flex-1 min-w-0">
                                <flux:heading size="base" class="truncate">
                                    {{ $item->product?->name ?? ($item->product_snapshot['name'] ?? 'Product') }}
                                </flux:heading>
                                <flux:text size="sm" class="text-zinc-500">
                                    {{ __('Order') }}: {{ $item->order->reference }}
                                </flux:text>
                            </div>

                            {{-- Action --}}
                            <div class="shrink-0 flex items-center">
                                @if ($item->product)
                                    <flux:button href="{{ route('products.reviews', $item->product->slug) }}"
                                        wire:navigate size="sm" variant="primary">
                                        {{ __('Write Review') }}
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </flux:card>
</div>
