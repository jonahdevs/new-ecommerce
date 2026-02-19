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
            ->latest('placed_at')
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
            ->latest('placed_at')
            ->paginate(5);
    }
};
?>

<div>
    <flux:card class="p-0 rounded-md">
        {{-- Page Header --}}
        <div class="px-3 py-2 border-b">
            <flux:heading size="lg">Orders</flux:heading>
        </div>

        <div class="px-4 py-4">
            @if ($this->ongoingOrders->isEmpty() && $this->cancelledOrders->isEmpty())
                {{-- Empty State --}}
                <div class="min-h-[50svh] flex flex-col items-center gap-2 justify-center">
                    <flux:icon.shopping-bag class="size-12 text-zinc-400" />
                    <flux:heading>No orders yet</flux:heading>
                    <flux:text> When you place your order, it will appear here. Start shopping to discover amazing
                        products!
                    </flux:text>

                    <flux:button :href="route('products')" variant="primary" icon="shopping-bag" wire:navigate>Start
                        Shopping
                    </flux:button>
                </div>
            @else
                <x-my-tabs wire:model="selectedTab">
                    <x-my-tab name="ongoing-delivered-tab" label="Ongoing/Delivered">
                        <div class="space-y-3">
                            @foreach ($this->ongoingOrders as $order)
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
                                            <flux:badge size="sm">{{ ucfirst($order->status) }}</flux:badge>
                                        </div>
                                    </div>

                                    <flux:link :href="route('customer.orders.show', $order)" class="text-sm!">See
                                        details</flux:link>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <flux:pagination :paginator="$this->ongoingOrders" />
                        </div>
                    </x-my-tab>
                    <x-my-tab name="cancelled-returned-tab" label="Cancelled/Returned">
                        <div class="space-y-3">
                            @foreach ($this->cancelledOrders as $order)
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
                                            <flux:badge size="sm">{{ ucfirst($order->status) }}</flux:badge>
                                        </div>
                                    </div>

                                    <flux:link :href="route('customer.orders.show', $order)" class="text-sm!">See
                                        details</flux:link>
                                </div>
                            @endforeach
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
