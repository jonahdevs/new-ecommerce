<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, Computed};
use Livewire\WithPagination;

new #[Layout('layouts.customer')] class extends Component {
    use WithPagination;

    public string $selectedTab = 'ongoing';

    #[Computed]
    public function hasOrders(): bool
    {
        return auth()->user()->orders()->exists();
    }

    #[Computed]
    public function ongoingOrders()
    {
        return auth()
            ->user()
            ->orders()
            ->whereIn('status', ['pending', 'processing', 'shipped', 'delivered'])
            ->with(['items' => fn($q) => $q->with('product')->limit(1)])
            ->withCount('items')
            ->latest()
            ->paginate(5);
    }

    #[Computed]
    public function cancelledOrders()
    {
        return auth()
            ->user()
            ->orders()
            ->whereIn('status', ['cancelled', 'returned'])
            ->with(['items' => fn($q) => $q->with('product')->limit(1)])
            ->withCount('items')
            ->latest()
            ->paginate(5);
    }
};
?>

<div>
    <flux:card class="p-0 rounded-md">
        {{-- Page Header --}}
        <div class="px-4 py-3 border-b">
            <flux:heading size="lg" level="1">My Orders</flux:heading>
        </div>

        <div class="px-4 py-4">
            @if (!$this->hasOrders)
                {{-- Empty State --}}
                <div class="min-h-[50svh] flex flex-col items-center gap-2 justify-center text-center">
                    <flux:icon.shopping-bag class="size-12 text-zinc-300" />
                    <flux:heading>No orders yet</flux:heading>
                    <flux:text class="text-zinc-500 max-w-sm">
                        When you place an order, it will appear here. Start shopping to discover amazing products!
                    </flux:text>
                    <flux:button :href="route('products')" variant="primary" icon="shopping-bag" wire:navigate
                        class="mt-2">
                        Start Shopping
                    </flux:button>
                </div>
            @else
                <x-my-tabs wire:model="selectedTab">

                    {{-- Ongoing / Delivered --}}
                    <x-my-tab name="ongoing" label="Ongoing / Delivered">
                        <div class="space-y-3">
                            @forelse ($this->ongoingOrders as $order)
                                @php
                                    $firstName = $order->items->first();
                                    $firstProductName =
                                        $firstName?->product_snapshot['name'] ??
                                        ($firstName?->product?->name ?? 'Product');
                                    $extraCount = $order->items_count - 1;
                                @endphp

                                <div wire:key="ongoing-{{ $order->id }}"
                                    class="border rounded-md p-4 hover:bg-zinc-50 transition-colors">
                                    <div class="flex items-center justify-between gap-4">

                                        {{-- Stacked Images --}}
                                        <div class="flex -space-x-3 shrink-0">
                                            <div
                                                class="w-12 h-12 rounded-md border bg-zinc-100 overflow-hidden shrink-0">
                                                @php $img = $order->items->first()?->product_image_url ?? $order->items->first()?->product?->image_url; @endphp
                                                @if ($img)
                                                    <img src="{{ asset($img) }}" alt="{{ $firstProductName }}"
                                                        class="w-full h-full object-cover" />
                                                @else
                                                    <flux:icon.photo class="w-full h-full p-2 text-zinc-300" />
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Order Info --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-zinc-800 truncate">
                                                {{ $firstProductName }}
                                                @if ($extraCount > 0)
                                                    <span class="text-zinc-400 font-normal">+ {{ $extraCount }}
                                                        more</span>
                                                @endif
                                            </p>
                                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                <flux:text class="text-xs text-zinc-400">{{ $order->reference }}
                                                </flux:text>
                                                <span class="text-zinc-200">·</span>
                                                <flux:text class="text-xs text-zinc-400">
                                                    {{ $order->created_at->format('M j, Y') }}</flux:text>
                                                <flux:badge size="sm" :color="$order->status->color()">
                                                    {{ $order->status->label() }}
                                                </flux:badge>
                                            </div>
                                        </div>

                                        {{-- Action --}}
                                        <flux:button :href="route('customer.orders.show', $order)" wire:navigate
                                            variant="ghost" size="sm" class="shrink-0">
                                            See details
                                        </flux:button>
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-16 text-center">
                                    <flux:icon.shopping-bag class="w-12 h-12 text-zinc-300 mb-3" />
                                    <flux:heading size="sm">No ongoing orders</flux:heading>
                                    <flux:text class="text-zinc-500 mt-1 text-sm">
                                        You don't have any ongoing or delivered orders yet.
                                    </flux:text>
                                    <flux:button :href="route('products')" wire:navigate variant="primary"
                                        class="mt-4">
                                        Start Shopping
                                    </flux:button>
                                </div>
                            @endforelse
                        </div>

                        @if ($this->ongoingOrders->hasPages())
                            <div class="mt-4">
                                <flux:pagination :paginator="$this->ongoingOrders" />
                            </div>
                        @endif
                    </x-my-tab>

                    {{-- Cancelled / Returned --}}
                    <x-my-tab name="cancelled" label="Cancelled / Returned">
                        <div class="space-y-3">
                            @forelse ($this->cancelledOrders as $order)
                                @php
                                    $firstName = $order->items->first();
                                    $firstProductName =
                                        $firstName?->product_snapshot['name'] ??
                                        ($firstName?->product?->name ?? 'Product');
                                    $extraCount = $order->items_count - 1;
                                @endphp

                                <div wire:key="cancelled-{{ $order->id }}"
                                    class="border rounded-md p-4 hover:bg-zinc-50 transition-colors">
                                    <div class="flex items-center justify-between gap-4">

                                        {{-- Stacked Images --}}
                                        <div class="flex -space-x-3 shrink-0">
                                            @foreach ($order->items->take(3) as $item)
                                                @php $img = $item->product_snapshot['image_path'] ?? $item->product?->image_path; @endphp
                                                <div
                                                    class="w-12 h-12 rounded-md border-2 border-white bg-zinc-100 overflow-hidden shadow-sm opacity-60">
                                                    @if ($img)
                                                        <img src="{{ asset($img) }}"
                                                            alt="{{ $item->product_snapshot['name'] ?? '' }}"
                                                            class="w-full h-full object-cover" />
                                                    @else
                                                        <flux:icon.photo class="w-full h-full p-2 text-zinc-300" />
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        {{-- Order Info --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-zinc-500 truncate">
                                                {{ $firstProductName }}
                                                @if ($extraCount > 0)
                                                    <span class="text-zinc-400 font-normal">+ {{ $extraCount }}
                                                        more</span>
                                                @endif
                                            </p>
                                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                <flux:text class="text-xs text-zinc-400">{{ $order->reference }}
                                                </flux:text>
                                                <span class="text-zinc-200">·</span>
                                                <flux:text class="text-xs text-zinc-400">
                                                    {{ $order->created_at->format('M j, Y') }}</flux:text>
                                                <flux:badge size="sm" :color="$order->status->color()">
                                                    {{ $order->status->label() }}
                                                </flux:badge>
                                            </div>
                                        </div>

                                        {{-- Action --}}
                                        <flux:button :href="route('customer.orders.show', $order)" wire:navigate
                                            variant="ghost" size="sm" class="shrink-0">
                                            See details
                                        </flux:button>
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-16 text-center">
                                    <flux:icon.check-circle class="w-12 h-12 text-zinc-300 mb-3" />
                                    <flux:heading size="sm">No cancelled or returned orders</flux:heading>
                                    <flux:text class="text-zinc-500 mt-1 text-sm">
                                        Great news — you have no cancelled or returned orders.
                                    </flux:text>
                                </div>
                            @endforelse
                        </div>

                        @if ($this->cancelledOrders->hasPages())
                            <div class="mt-4">
                                <flux:pagination :paginator="$this->cancelledOrders" />
                            </div>
                        @endif
                    </x-my-tab>

                </x-my-tabs>
            @endif
        </div>
    </flux:card>
</div>
