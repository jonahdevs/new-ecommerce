<?php

use Artesaos\SEOTools\Facades\SEOMeta;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::account')] #[Title('Orders')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'active';

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,follow');
    }

    #[Computed]
    public function orders()
    {
        return auth()->user()->orders()
            ->with('items')
            ->when($this->status === 'active', fn ($q) => $q->whereIn('status', ['pending', 'processing', 'out_for_delivery', 'completed']))
            ->when($this->status === 'cancelled', fn ($q) => $q->where('status', 'cancelled'))
            ->latest()
            ->paginate(10);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="page-fade space-y-6">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Orders</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    {{-- Header --}}
    <div>
        <flux:heading size="xl">Orders</flux:heading>
        <flux:text class="mt-1">All your Sheffield orders, invoices and delivery status.</flux:text>
    </div>

    {{-- Status filter --}}
    <div class="flex flex-wrap gap-2">
        @foreach (['active' => 'Ongoing / Delivered', 'cancelled' => 'Cancelled / Returned'] as $value => $label)
            <button type="button" wire:click="$set('status', '{{ $value }}')"
                    class="rounded-full border px-3.5 py-1 text-[12px] font-semibold transition
                        {{ $status === $value
                            ? 'border-ink bg-ink text-white'
                            : 'border-zinc-200 bg-white text-ink-2 hover:border-zinc-300' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Orders table --}}
    @if ($this->orders->isEmpty())
        <flux:card class="py-14 text-center">
            <flux:icon.shopping-bag variant="outline" class="mx-auto size-9 text-ink-4" />
            <flux:heading size="sm" class="mt-4">No orders found</flux:heading>
            <flux:text class="mt-1">
                {{ $status ? 'No orders match this status.' : "You haven't placed any orders yet." }}
            </flux:text>
            @if (!$status)
                <flux:button variant="customer-primary" size="customer" :href="route('catalog')" wire:navigate class="mt-5">
                    Shop the catalog
                </flux:button>
            @endif
        </flux:card>
    @else
        <flux:card class="p-0 overflow-hidden">
            <flux:table container:class="scrollbar-thin [&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                <flux:table.columns>
                    <flux:table.column>Order</flux:table.column>
                    <flux:table.column class="hidden sm:table-cell">Date</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column class="hidden md:table-cell" align="end">Total</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->orders as $order)
                        <flux:table.row wire:key="order-{{ $order->id }}">
                            <flux:table.cell>
                                <flux:text class="font-semibold text-ink">{{ $order->order_number }}</flux:text>
                                <flux:text size="sm" class="mt-0.5 text-ink-4">
                                    {{ $order->items->count() }} item{{ $order->items->count() === 1 ? '' : 's' }}
                                </flux:text>
                            </flux:table.cell>
                            <flux:table.cell class="hidden sm:table-cell">
                                <flux:text size="sm">{{ $order->created_at->format('d M Y') }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$order->status->badgeColor()" size="sm" inset="top bottom">
                                    {{ $order->status->label() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="hidden md:table-cell" align="end">
                                <flux:text size="sm" class="font-semibold tabular-nums">
                                    {!! money($order->total_cents) !!}
                                </flux:text>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="sm" variant="ghost"
                                             :href="route('account.orders.show', $order)" wire:navigate>
                                    View
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            @if ($this->orders->hasPages())
                <div class="border-t border-zinc-200 px-6 pb-3">
                    <flux:pagination :paginator="$this->orders" />
                </div>
            @endif
        </flux:card>
    @endif

</div>
