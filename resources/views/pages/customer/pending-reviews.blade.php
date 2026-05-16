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
            ->select(['order_items.id', 'order_items.order_id', 'order_items.product_id', 'order_items.product_snapshot'])
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

@placeholder
    <div class="bg-white rounded-lg border">
        {{-- Card header --}}
        <div class="px-6 py-4 border-b border-zinc-200 flex items-center gap-3">
            <flux:skeleton animate="shimmer" class="w-5 h-5" />
            <flux:skeleton animate="shimmer" class="w-20 h-6" />
            <flux:skeleton animate="shimmer" class="w-16 h-6" />
        </div>

        {{-- Card content --}}
        <div class="flex flex-col bg-white">
            @for ($i = 0; $i < 5; $i++)
                <div class="p-4.5 border-b border-zinc-200 last:border-b-0 flex items-center gap-4">
                    {{-- Product Image skeleton --}}
                    <flux:skeleton animate="shimmer" class="w-16 h-16 rounded-sm shrink-0" />

                    {{-- Product Info skeleton --}}
                    <div class="flex-1 min-w-0 space-y-2">
                        <flux:skeleton animate="shimmer" class="w-48 h-4" />
                        <flux:skeleton animate="shimmer" class="w-32 h-3" />
                    </div>

                    {{-- Action button skeleton --}}
                    <flux:skeleton animate="shimmer" class="w-24 h-8 shrink-0" />
                </div>
            @endfor
        </div>
    </div>
@endplaceholder

<x-customer.card title="Pending" titleEm="Reviews" bodyClass="p-0">
    <x-slot:icon>
        <flux:icon.star />
    </x-slot:icon>

    @if ($this->pendingProducts->isEmpty())
        <div class="text-center py-16 flex flex-col items-center justify-center">
            <flux:icon.star class="w-12 h-12 text-zinc-300 mb-4" />
            <h4 class="text-lg font-medium text-zinc-900">{{ __('No pending reviews') }}</h4>
            <p class="text-sm text-zinc-500 mt-1">{{ __('You have reviewed all your purchased products.') }}</p>
            <flux:button variant="customer-primary" href="{{ route('customer.orders.index') }}" wire:navigate
                class="mt-6">
                {{ __('View Orders') }}
            </flux:button>
        </div>
    @else
        <div class="flex flex-col bg-white">
            @foreach ($this->pendingProducts as $item)
                <div
                    class="p-4.5 border-b border-zinc-200 last:border-b-0 flex items-center gap-4 transition-colors hover:bg-zinc-50">
                    {{-- Product Image --}}
                    <div
                        class="w-16 h-16 bg-zinc-50 flex items-center justify-center shrink-0 overflow-hidden border border-zinc-200 rounded-sm">
                        @if ($item->product?->image_path)
                            <img src="{{ asset('storage/' . $item->product->image_path) }}"
                                alt="{{ $item->product->name }}" class="w-[85%] h-[85%] object-contain" />
                        @else
                            <flux:icon.photo class="w-8 h-8 text-zinc-300" />
                        @endif
                    </div>

                    {{-- Product Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="text-[13px] font-bold text-zinc-950 mb-0.5 truncate">
                            {{ $item->product?->name ?? ($item->product_snapshot['name'] ?? 'Product') }}
                        </div>
                        <div class="text-[11px] text-zinc-500 mt-0.5">
                            {{ __('Order') }}: #{{ $item->order->reference }}
                        </div>
                    </div>

                    {{-- Action --}}
                    <div class="shrink-0 flex items-center">
                        @if ($item->product)
                            <flux:button href="{{ route('products.reviews', $item->product->slug) }}" wire:navigate
                                size="sm" variant="customer-primary">
                                {{ __('Write Review') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-customer.card>
