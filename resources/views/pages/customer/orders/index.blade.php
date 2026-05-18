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
            ->whereIn('status', [OrderStatus::PENDING, OrderStatus::CONFIRMED, OrderStatus::PROCESSING, OrderStatus::SHIPPED, OrderStatus::DELIVERED])
            ->with(['items' => fn($q) => $q->select(['id', 'order_id', 'product_id', 'product_snapshot'])->with(['product' => fn($q) => $q->select(['id', 'image_path'])])->limit(1)])
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
            ->with(['items' => fn($q) => $q->select(['id', 'order_id', 'product_id', 'product_snapshot'])->with(['product' => fn($q) => $q->select(['id', 'image_path'])])->limit(1)])
            ->withCount('items')
            ->latest()
            ->paginate(5);
    }
};
?>

@placeholder
    <div>
        {{-- Filter tabs skeleton --}}
        <div class="flex border-[1.5px] border-zinc-200 bg-white overflow-x-auto mb-5 rounded-sm overflow-hidden">
            <flux:skeleton animate="shimmer" class="h-10 w-64" />
            <flux:skeleton animate="shimmer" class="h-10 w-64" />
        </div>

        {{-- Orders list skeleton --}}
        <div class="flex flex-col bg-white border border-zinc-200">
            @for ($i = 0; $i < 5; $i++)
                <div class="p-4.5 border-b border-zinc-200 last:border-b-0 flex items-center gap-4">
                    {{-- Product images skeleton --}}
                    <div class="hidden md:flex -space-x-2">
                        @for ($j = 0; $j < 3; $j++)
                            <flux:skeleton animate="shimmer" class="w-12 h-12 border-2 border-white" />
                        @endfor
                    </div>

                    {{-- Order info skeleton --}}
                    <div class="flex-1 min-w-0 space-y-1.5">
                        <flux:skeleton animate="shimmer" class="w-32 h-4" />
                        <flux:skeleton animate="shimmer" class="w-24 h-3" />
                        <flux:skeleton animate="shimmer" class="w-40 h-3" />
                    </div>

                    {{-- Status badge skeleton --}}
                    <flux:skeleton animate="shimmer" class="w-20 h-6 rounded-full" />

                    {{-- Price skeleton --}}
                    <flux:skeleton animate="shimmer" class="w-24 h-6 shrink-0" />

                    {{-- Chevron skeleton --}}
                    <flux:skeleton animate="shimmer" class="w-4 h-4 shrink-0" />
                </div>
            @endfor
        </div>

        {{-- Pagination skeleton --}}
        <div class="mt-6 flex justify-center">
            <div class="flex items-center gap-2">
                @for ($i = 0; $i < 4; $i++)
                    <flux:skeleton animate="shimmer" class="w-8 h-8" />
                @endfor
            </div>
        </div>
    </div>
@endplaceholder

@php
    $tabClass =
        'px-[18px] py-2.5 text-[11px] font-bold tracking-[0.08em] uppercase cursor-pointer border-r border-zinc-200 last:border-r-0 whitespace-nowrap font-serif transition-all';
    $tabActive = 'bg-primary text-white';
    $tabInactive = 'bg-transparent text-zinc-500 hover:bg-zinc-50 hover:text-zinc-950';
@endphp

<div>
    {{-- Filter tabs --}}
    <div class="flex border-[1.5px] border-zinc-200 bg-white overflow-x-auto mb-5 rounded-sm overflow-hidden">
        <button wire:click="$set('selectedTab', 'ongoing')"
            class="{{ $tabClass }} {{ $selectedTab === 'ongoing' ? $tabActive : $tabInactive }}">
            Ongoing / Delivered ({{ $this->ongoingOrders->total() }})
        </button>
        <button wire:click="$set('selectedTab', 'cancelled')"
            class="{{ $tabClass }} {{ $selectedTab === 'cancelled' ? $tabActive : $tabInactive }}">
            Cancelled / Returned ({{ $this->cancelledOrders->total() }})
        </button>
    </div>

    {{-- Orders list --}}
    <div class="flex flex-col bg-white border border-zinc-200">
        @php $orders = $selectedTab === 'ongoing' ? $this->ongoingOrders : $this->cancelledOrders; @endphp

        @forelse ($orders as $order)
            <a href="{{ route('customer.orders.show', $order) }}" wire:navigate
                class="p-4.5 border-b border-zinc-200 last:border-b-0 flex items-center gap-4 transition-colors hover:bg-zinc-50 cursor-pointer rounded-sm">
                <div class="hidden md:flex -space-x-2">
                    @foreach ($order->items->take(3) as $item)
                        <div
                            class="w-12 h-12 bg-zinc-50 flex items-center justify-center shrink-0 overflow-hidden border-2 border-white">
                            @php $img = $item->product_image_url ?? $item->product?->image_url; @endphp
                            @if ($img)
                                <img src="{{ asset($img) }}" alt="{{ $item->product_snapshot['name'] ?? '' }}"
                                    class="w-[85%] h-[85%] object-contain" />
                            @else
                                <flux:icon.photo class="w-full h-full p-2 text-zinc-200" />
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-[13px] font-bold text-zinc-950 mb-0.5">#{{ $order->reference }}</div>
                    <div class="text-[11px] text-zinc-500">{{ $order->created_at->format('d M Y') }}</div>
                    <div class="text-[11px] text-zinc-500 mt-0.5">
                        {{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}
                        @if ($order->payment_method)
                            · {{ str_replace('_', ' ', strtoupper($order->payment_method)) }}
                        @endif
                    </div>
                </div>
                <flux:badge size="sm" :color="$order->status->color()">
                    {{ $order->status->label() }}
                </flux:badge>
                <div class="font-sans text-base font-semibold text-primary shrink-0">
                    {{ format_currency($order->total) }}</div>
                <div class="text-zinc-400 shrink-0">
                    <flux:icon.chevron-right class="w-4 h-4" />
                </div>
            </a>
        @empty
            <div class="p-12 text-center flex flex-col items-center justify-center">
                <flux:icon.shopping-bag class="w-12 h-12 text-zinc-300 mb-3" />
                <h4 class="text-lg font-medium text-zinc-900">No orders found</h4>
                <p class="text-sm text-zinc-500 mt-1">
                    {{ $selectedTab === 'ongoing' ? "You don't have any ongoing or delivered orders yet." : "You don't have any cancelled or returned orders." }}
                </p>
            </div>
        @endforelse
    </div>

    @if ($orders->hasPages())
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif
</div>

@script
<script>
    if (window.Echo) {
        window.Echo.private('App.Models.User.{{ auth()->id() }}')
            .listen('.order.updated', (e) => {
                $wire.$refresh();
            });
    }
</script>
@endscript
