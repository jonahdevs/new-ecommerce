<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, Computed};
use Livewire\WithPagination;

new #[Layout('layouts.customer')] class extends Component {
    use WithPagination;

    public string $selectedTab = 'ongoing-delivered-tab';

    #[Computed]
    public function ongoingOrders()
    {
        return auth()
            ->user()
            ->orders()
            ->whereIn('status', ['pending', 'processing', 'shipped', 'delivered'])
            ->with('items.product')
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
            ->with('items.product')
            ->latest()
            ->paginate(5);
    }
};
?>

<div>
    <flux:card class="p-0 rounded-md">
        {{-- Page Header --}}
        <div class="px-3 py-2 border-b">
            <flux:heading size="lg" level="1">Orders</flux:heading>
        </div>

        <div class="px-4 py-4">
            @if ($this->ongoingOrders->isEmpty() && $this->cancelledOrders->isEmpty())
                {{-- Empty State --}}
                <div class="min-h-[50svh] flex flex-col items-center gap-2 justify-center">
                    <flux:icon.shopping-bag class="size-12 text-zinc-400" />
                    <flux:heading>No orders yet</flux:heading>
                    <flux:text>
                        When you place your order, it will appear here. Start shopping to discover amazing products!
                    </flux:text>

                    <flux:button :href="route('products')" variant="primary" icon="shopping-bag" wire:navigate>
                        Start Shopping
                    </flux:button>
                </div>
            @else
                <x-my-tabs wire:model="selectedTab">
                    <x-my-tab name="ongoing-delivered-tab" label="Ongoing/Delivered">
                        <div class="space-y-3">
                            @forelse ($this->ongoingOrders as $order)
                                <div :key="$order->id"
                                    class="border rounded-md p-5 hover:bg-zinc-50 transition-colors flex items-center justify-between gap-4">
                                    {{-- Product Image --}}
                                    <div class="shrink-0">
                                        @if ($order->items->first()?->product?->image_path)
                                            <img src="{{ $order->items->first()->product->image_url }}"
                                                alt="{{ $order->items->first()->name }}"
                                                class="w-16 h-16 rounded object-contain">
                                        @else
                                            <div
                                                class="w-16 h-16 rounded bg-zinc-100 border flex items-center justify-center">
                                                <flux:icon.photo class="w-8 h-8 text-zinc-300" />
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        {{-- Product Name --}}
                                        <flux:heading size="sm" class="truncate">
                                            {{ $order->items->first()?->name }}</flux:heading>

                                        {{-- Order Info --}}
                                        <div class="flex items-center gap-4 mt-1 text-xs text-zinc-600">
                                            <flux:text class="text-sm!">Order n° {{ $order->reference }}</flux:text>
                                            <flux:badge size="sm" :color="$order->status->color()">
                                                {{ $order->status->label() }}</flux:badge>
                                        </div>
                                    </div>

                                    <flux:link :href="route('customer.orders.show', $order)" wire:navigate
                                        class="text-sm!">See
                                        details</flux:link>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-16 text-center">
                                    <flux:icon.shopping-bag class="w-12 h-12 text-zinc-300 mb-3" />
                                    <flux:heading size="sm">No ongoing orders</flux:heading>
                                    <flux:text class="text-zinc-500 mt-1 text-sm">You don't have any ongoing or
                                        delivered orders yet.</flux:text>
                                    <flux:button href="{{ route('products') }}" wire:navigate variant="primary"
                                        class="mt-4">
                                        Start Shopping
                                    </flux:button>
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-3">
                            <flux:pagination :paginator="$this->ongoingOrders" />
                        </div>
                    </x-my-tab>
                    <x-my-tab name="cancelled-returned-tab" label="Cancelled/Returned">
                        <div class="space-y-3">
                            @forelse ($this->cancelledOrders as $order)
                                <div :key="$order->id"
                                    class="border rounded-md p-5 hover:bg-zinc-50 transition-colors flex items-center justify-between gap-4">
                                    {{-- Product Image --}}
                                    <div class="shrink-0">
                                        @if ($order->items->first()?->product?->image_path)
                                            <img src="{{ $order->items->first()->product->image_url }}"
                                                alt="{{ $order->items->first()->name }}"
                                                class="w-16 h-16 rounded object-cover">
                                        @else
                                            <div
                                                class="w-16 h-16 rounded bg-zinc-100 border flex items-center justify-center">
                                                <flux:icon.photo class="w-8 h-8 text-zinc-300" />
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        {{-- Product Name --}}
                                        <flux:heading size="sm" class="truncate">
                                            {{ $order->items->first()?->name }}</flux:heading>

                                        {{-- Order Info --}}
                                        <div class="flex items-center gap-4 mt-1 text-xs text-zinc-600">
                                            <flux:text>Order {{ $order->reference }}</flux:text>
                                            <flux:badge size="sm" :color="$order->status->color()">
                                                {{ $order->status->label() }}</flux:badge>
                                        </div>
                                    </div>

                                    <flux:link :href="route('customer.orders.show', $order)" wire:navigate
                                        class="text-xs!">See
                                        details</flux:link>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-16 text-center">
                                    <flux:icon.check-circle class="w-12 h-12 text-zinc-300 mb-3" />
                                    <flux:heading size="sm">No cancelled or returned orders</flux:heading>
                                    <flux:text class="text-zinc-500 mt-1 text-sm">Great news — you have no cancelled or
                                        returned orders.</flux:text>
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-3">
                            <flux:pagination :paginator="$this->cancelledOrders" />
                        </div>
                    </x-my-tab>
                </x-my-tabs>
            @endif
        </div>
    </flux:card>
</div>
