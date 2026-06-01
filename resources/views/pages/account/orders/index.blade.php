<?php

use App\Enums\OrderStatus;
use Artesaos\SEOTools\Facades\SEOMeta;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::account')] #[Title('Orders — Sheffield')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,follow');
    }

    #[Computed]
    public function orders()
    {
        return auth()->user()->orders()
            ->with('items')
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(10);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="page-fade space-y-6">

    {{-- Header --}}
    <div>
        <flux:heading size="xl">Orders</flux:heading>
        <flux:text class="mt-1">All your Sheffield orders, invoices and delivery status.</flux:text>
    </div>

    {{-- Status filter --}}
    <div class="flex flex-wrap gap-2">
        @foreach (['' => 'All', ...collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()] as $value => $label)
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
        <flux:card class="p-0">
            <flux:table :paginate="$this->orders" container:class="px-6">
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
                                <flux:button variant="customer-outline" size="customer"
                                             :href="route('account.orders.show', $order)" wire:navigate>
                                    View
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

</div>
