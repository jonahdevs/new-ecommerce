<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Order — Admin')] class extends Component {
    #[Locked]
    public Order $order;

    public string $status = '';

    public function mount(Order $order): void
    {
        $this->order = $order->load(['items.product', 'address', 'user', 'deliveryZone', 'payments']);
        $this->status = $order->status->value;
    }

    public function updateStatus(): void
    {
        $this->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ]);

        $changed = $this->order->status->value !== $this->status;

        $this->order->update(['status' => $this->status]);
        $this->order->refresh();

        // The notification self-gates to fulfilment milestones (shipped/
        // delivered/cancelled) and the customer's preferences.
        if ($changed) {
            $this->order->user?->notify(new \App\Notifications\Orders\OrderStatusChanged($this->order));
        }

        Flux::toast(heading: 'Status updated', text: 'Order is now '.$this->order->status->label().'.', variant: 'success');
    }

    /** @return array<int, OrderStatus> */
    public function statuses(): array
    {
        return OrderStatus::cases();
    }
}; ?>

<div>
    @push('breadcrumbs')
<flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.orders.index')" wire:navigate>Orders</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $order->order_number }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
@endpush

    <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-mono">{{ $order->order_number }}</flux:heading>
            <flux:subheading>Placed {{ $order->created_at->format('d F Y, g:i A') }}</flux:subheading>
        </div>
        <flux:badge size="lg" :color="$order->status->badgeColor()">{{ $order->status->label() }}</flux:badge>
    </div>

    <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start">

        {{-- Main column --}}
        <div class="min-w-0 flex-1 space-y-6">

            {{-- Items --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Items</flux:heading>
                </div>
                <flux:table
                    container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                    <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                        <flux:table.column>Product</flux:table.column>
                        <flux:table.column align="end">Unit</flux:table.column>
                        <flux:table.column align="end">Qty</flux:table.column>
                        <flux:table.column align="end">Line total</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($order->items as $item)
                            <flux:table.row :key="$item->id">
                                <flux:table.cell variant="strong">
                                    {{ $item->product_name }}
                                    @if ($item->product_sku)
                                        <span class="block font-mono text-xs font-normal text-zinc-400">{{ $item->product_sku }}</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums text-zinc-500">{!! money($item->unit_price_cents) !!}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $item->quantity }}</flux:table.cell>
                                <flux:table.cell align="end" class="font-medium tabular-nums">{!! money($item->line_total_cents) !!}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>

            {{-- Payments --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Payments</flux:heading>
                </div>
                @if ($order->payments->isNotEmpty())
                    <flux:table
                        container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                        <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                            <flux:table.column>Provider</flux:table.column>
                            <flux:table.column>Reference</flux:table.column>
                            <flux:table.column align="end">Amount</flux:table.column>
                            <flux:table.column align="end">Status</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($order->payments as $payment)
                                <flux:table.row :key="$payment->id">
                                    <flux:table.cell class="capitalize">{{ str_replace('_', ' ', (string) $payment->provider) }}</flux:table.cell>
                                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                                        {{ $payment->mpesa_receipt ?? $payment->stripe_payment_intent_id ?? $payment->checkout_request_id ?? '—' }}
                                    </flux:table.cell>
                                    <flux:table.cell align="end" class="tabular-nums">{!! money($payment->amount_cents) !!}</flux:table.cell>
                                    <flux:table.cell align="end">
                                        <flux:badge size="sm" inset="top bottom" :color="$payment->status->badgeColor()">
                                            {{ $payment->status->label() }}
                                        </flux:badge>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <div class="px-6 py-8 text-center text-sm text-zinc-400">No payments recorded.</div>
                @endif
            </flux:card>
        </div>

        {{-- Sidebar --}}
        <aside class="w-full shrink-0 space-y-6 lg:w-80">

            {{-- Status control --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Fulfilment</flux:heading>
                </div>
                <div class="p-6">
                    <form wire:submit="updateStatus" class="space-y-3">
                        <flux:select wire:model="status">
                            @foreach ($this->statuses() as $statusOption)
                                <flux:select.option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:button type="submit" variant="primary" class="w-full">Update status</flux:button>
                    </form>
                </div>
            </flux:card>

            {{-- Customer --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Customer</flux:heading>
                </div>
                <div class="p-6">
                @if ($order->user)
                    <div class="flex items-center gap-3">
                        <flux:avatar :name="$order->user->name" :initials="$order->user->initials()" size="sm" />
                        <div class="min-w-0">
                            <a href="{{ route('admin.customers.show', $order->user) }}" wire:navigate
                                class="block truncate text-sm font-medium hover:text-brand-500 dark:text-white">
                                {{ $order->user->name }}
                            </a>
                            <div class="truncate text-xs text-zinc-500">{{ $order->user->email }}</div>
                        </div>
                    </div>
                @else
                    <flux:text size="sm">Guest checkout</flux:text>
                @endif

                @if ($order->address)
                    <flux:separator class="my-4" />
                    <flux:heading size="sm" class="text-zinc-500">Delivery address</flux:heading>
                    <div class="mt-2 space-y-0.5 text-sm text-zinc-600 dark:text-zinc-300">
                        <div class="font-medium">{{ $order->address->fullName() }}</div>
                        <div>{{ $order->address->oneLiner() }}</div>
                        @if ($order->address->phone)
                            <div class="text-zinc-500">{{ $order->address->phone }}</div>
                        @endif
                        @if ($order->deliveryZone)
                            <flux:badge size="sm" inset="top bottom" color="zinc" class="mt-1">{{ $order->deliveryZone->name }}</flux:badge>
                        @endif
                    </div>
                @endif
                </div>
            </flux:card>

            {{-- Totals --}}
            <flux:card class="p-0">
                <div class="space-y-3 px-5 py-4">
                    <div class="flex justify-between text-sm text-zinc-600 dark:text-zinc-300">
                        <span>Subtotal</span>
                        <span class="font-medium tabular-nums">{!! money($order->subtotal_cents) !!}</span>
                    </div>
                    <div class="flex justify-between text-sm text-zinc-600 dark:text-zinc-300">
                        <span>Delivery</span>
                        <span class="font-medium tabular-nums">{!! $order->delivery_cents > 0 ? money($order->delivery_cents) : 'Free' !!}</span>
                    </div>
                    @if ($order->installation_cents > 0)
                        <div class="flex justify-between text-sm text-zinc-600 dark:text-zinc-300">
                            <span>Installation</span>
                            <span class="font-medium tabular-nums">{!! money($order->installation_cents) !!}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm text-zinc-600 dark:text-zinc-300">
                        <span>{{ $order->vatLabel() }}</span>
                        <span class="font-medium tabular-nums">{!! money($order->vat_cents) !!}</span>
                    </div>
                </div>
                <flux:separator />
                <div class="flex items-baseline justify-between px-5 py-4">
                    <span class="text-xs font-bold uppercase tracking-wide">Total</span>
                    <span class="text-xl font-semibold text-brand-500 tabular-nums">{!! money($order->total_cents) !!}</span>
                </div>
                @if ($order->payment_method)
                    <flux:separator />
                    <div class="px-5 py-3 text-sm text-zinc-500">
                        Method: <span class="font-medium capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</span>
                    </div>
                @endif
            </flux:card>
        </aside>
    </div>
</div>
