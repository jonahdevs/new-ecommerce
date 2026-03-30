<?php

use App\Enums\OrderStatus;
use Livewire\Component;
use Livewire\Attributes\{Layout, Computed};
use Livewire\WithPagination;

new #[Layout('layouts.customer')] class extends Component {
    use WithPagination;

    public string $selectedTab = 'ongoing';

    // =========================================================================
    //  COMPUTED — ORDER EXISTENCE CHECK
    // =========================================================================

    #[Computed]
    public function hasOrders(): bool
    {
        return auth()->user()->orders()->exists();
    }

    // =========================================================================
    //  COMPUTED — ONGOING ORDERS
    // =========================================================================

    #[Computed]
    public function ongoingOrders()
    {
        return auth()
            ->user()
            ->orders()
            ->whereIn('status', [
                OrderStatus::PENDING,
                OrderStatus::CONFIRMED,
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::DELIVERED,
            ])
            ->with(['items' => fn($q) => $q->with('product')->limit(1)])
            ->withCount('items')
            ->latest()
            ->paginate(5);
    }

    // =========================================================================
    //  COMPUTED — CANCELLED / RETURNED ORDERS
    // =========================================================================

    #[Computed]
    public function cancelledOrders()
    {
        return auth()
            ->user()
            ->orders()
            ->whereIn('status', [OrderStatus::CANCELLED, OrderStatus::RETURNED])
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
        <div class="px-4 py-3 border-b flex items-center justify-between">
            <flux:heading size="lg" level="1">My Orders</flux:heading>
            {{-- Quick link to quotations page --}}
            <flux:link :href="route('customer.quotations.index')" wire:navigate class="text-xs flex items-center gap-1">
                <flux:icon.tag class="size-3.5 inline-block me-2" />
                <span>
                    My Quotations
                </span>
            </flux:link>
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
                    <flux:button :href="route('shop.index')" variant="primary" icon="shopping-bag" wire:navigate
                        class="mt-2">
                        Start Shopping
                    </flux:button>
                </div>
            @else
                {{-- Status Tabs --}}
                <div class="border-b border-zinc-200 dark:border-zinc-600 mb-4">
                    <nav class="flex gap-1 overflow-x-auto">
                        <button 
                            wire:click="$set('selectedTab', 'ongoing')"
                            @class([
                                'inline-flex items-center gap-1.5 px-3 py-2 text-sm whitespace-nowrap transition-colors duration-150 cursor-pointer',
                                'bg-brand-secondary text-brand-secondary-content font-medium' => $selectedTab === 'ongoing',
                                'text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800' => $selectedTab !== 'ongoing',
                            ])
                        >
                            <flux:icon.truck class="size-4" />
                            Ongoing / Delivered
                            <span @class([
                                'text-xs px-1.5 py-0.5 rounded-full',
                                'bg-white/20' => $selectedTab === 'ongoing',
                                'bg-zinc-200 dark:bg-zinc-700' => $selectedTab !== 'ongoing',
                            ])>
                                {{ $this->ongoingOrders->total() }}
                            </span>
                        </button>
                        <button 
                            wire:click="$set('selectedTab', 'cancelled')"
                            @class([
                                'inline-flex items-center gap-1.5 px-3 py-2 text-sm whitespace-nowrap transition-colors duration-150 cursor-pointer',
                                'bg-brand-secondary text-brand-secondary-content font-medium' => $selectedTab === 'cancelled',
                                'text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800' => $selectedTab !== 'cancelled',
                            ])
                        >
                            <flux:icon.x-circle class="size-4" />
                            Cancelled / Returned
                            <span @class([
                                'text-xs px-1.5 py-0.5 rounded-full',
                                'bg-white/20' => $selectedTab === 'cancelled',
                                'bg-zinc-200 dark:bg-zinc-700' => $selectedTab !== 'cancelled',
                            ])>
                                {{ $this->cancelledOrders->total() }}
                            </span>
                        </button>
                    </nav>
                </div>

                {{-- Ongoing / Delivered Tab Content --}}
                @if ($selectedTab === 'ongoing')
                        <div class="space-y-3">
                            @forelse ($this->ongoingOrders as $order)
                                @php
                                    $firstItem = $order->items->first();
                                    $firstProductName =
                                        $firstItem?->product_snapshot['name'] ??
                                        ($firstItem?->product?->name ?? 'Product');
                                    $extraCount = $order->items_count - 1;
                                @endphp

                                <div wire:key="ongoing-{{ $order->id }}"
                                    class="border rounded-md p-4 hover:bg-zinc-50 transition-colors">

                                    <div class="flex items-start gap-4">

                                        {{-- Product image --}}
                                        <div class="w-12 h-12 rounded-md border bg-zinc-100 overflow-hidden shrink-0">
                                            @php
                                                $img =
                                                    $order->items->first()?->product_image_url ??
                                                    $order->items->first()?->product?->image_url;
                                            @endphp
                                            @if ($img)
                                                <img src="{{ asset($img) }}" alt="{{ $firstProductName }}"
                                                    class="w-full h-full object-cover" />
                                            @else
                                                <flux:icon.photo class="w-full h-full p-2 text-zinc-300" />
                                            @endif
                                        </div>

                                        {{-- Order info + action --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="text-sm font-medium text-zinc-800 truncate">
                                                    {{ $firstProductName }}
                                                    @if ($extraCount > 0)
                                                        <span class="text-zinc-400 font-normal">+ {{ $extraCount }}
                                                            more</span>
                                                    @endif
                                                </p>

                                                {{-- Action — stays top-right always --}}
                                                <flux:button :href="route('customer.orders.show', $order)" wire:navigate
                                                    variant="ghost" size="sm" class="shrink-0">
                                                    See details
                                                </flux:button>
                                            </div>

                                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                <flux:text class="text-xs text-zinc-400">{{ $order->reference }}
                                                </flux:text>
                                                <span class="text-zinc-200">·</span>
                                                <flux:text class="text-xs text-zinc-400">
                                                    {{ $order->created_at->format('M j, Y') }}
                                                </flux:text>
                                                <flux:badge size="sm" :color="$order->status->color()">
                                                    {{ $order->status->label() }}
                                                </flux:badge>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-16 text-center">
                                    <flux:icon.shopping-bag class="w-12 h-12 text-zinc-300 mb-3" />
                                    <flux:heading size="sm">No ongoing orders</flux:heading>
                                    <flux:text class="text-zinc-500 mt-1 text-sm">
                                        You don't have any ongoing or delivered orders yet.
                                    </flux:text>
                                    <flux:button :href="route('shop.index')" wire:navigate variant="primary"
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
                @endif

                {{-- Cancelled / Returned Tab Content --}}
                @if ($selectedTab === 'cancelled')
                        <div class="space-y-3">
                            @forelse ($this->cancelledOrders as $order)
                                @php
                                    $firstItem = $order->items->first();
                                    $firstProductName =
                                        $firstItem?->product_snapshot['name'] ??
                                        ($firstItem?->product?->name ?? 'Product');
                                    $extraCount = $order->items_count - 1;
                                @endphp
                                <div wire:key="cancelled-{{ $order->id }}"
                                    class="border rounded-md p-4 hover:bg-zinc-50 transition-colors">

                                    <div class="flex items-start gap-4">

                                        {{-- Stacked images --}}
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

                                        {{-- Order info + action --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="text-sm font-medium text-zinc-500 truncate">
                                                    {{ $firstProductName }}
                                                    @if ($extraCount > 0)
                                                        <span class="text-zinc-400 font-normal">+ {{ $extraCount }}
                                                            more</span>
                                                    @endif
                                                </p>

                                                <flux:button :href="route('customer.orders.show', $order)" wire:navigate
                                                    variant="ghost" size="sm" class="shrink-0">
                                                    See details
                                                </flux:button>
                                            </div>

                                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                <flux:text class="text-xs text-zinc-400">{{ $order->reference }}
                                                </flux:text>
                                                <span class="text-zinc-200">·</span>
                                                <flux:text class="text-xs text-zinc-400">
                                                    {{ $order->created_at->format('M j, Y') }}
                                                </flux:text>
                                                <flux:badge size="sm" :color="$order->status->color()">
                                                    {{ $order->status->label() }}
                                                </flux:badge>
                                            </div>
                                        </div>

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
                @endif
            @endif
        </div>
    </flux:card>
</div>
