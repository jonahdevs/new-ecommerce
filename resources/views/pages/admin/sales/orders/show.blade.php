<?php

use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\{Title, Computed};

new #[Title('Order Details')] class extends Component {
    public Order $order;
    public string $status = '';
    public string $note = '';

    public array $orderStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded', 'failed'];

    public array $timelineStatuses = ['pending', 'processing', 'shipped', 'delivered'];

    public function mount(Order $order): void
    {
        $this->order = $order->load(['products.images', 'payment', 'customer', 'statusHistories']);
        $this->status = $order->status;
    }

    public function updateStatus(): void
    {
        $this->validate([
            'status' => 'required|in:' . implode(',', $this->orderStatuses),
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $this->order->statusHistories()->create([
                'status' => $this->status,
                'note' => $this->note,
                'changed_by' => auth()->id(),
            ]);

            $this->order->update(['status' => $this->status]);
            $this->note = '';

            $this->dispatch('notify', variant: 'success', message: 'Order status updated.');
            $this->dispatch('close-modal', 'edit-order');
        } catch (\Throwable $e) {
            logger()->error('Failed to update order status.', [
                'order_id' => $this->order->id,
                'user_id' => auth()->id(),
                'exception_message' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.orders.index')" wire:navigate>Orders</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Order Details</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="grid grid-cols-4 gap-5 mt-6">

        {{-- ── Left: Main Content ── --}}
        <div class="col-span-3 space-y-5">

            {{-- Order Header --}}
            <flux:card class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <flux:text class="font-medium">#{{ $order->id }}</flux:text>

                        {{-- Order Status Badge --}}
                        @php
                            $statusIcon = match ($order->status) {
                                'pending' => 'clock',
                                'processing' => 'arrow-path',
                                'shipped' => 'truck',
                                'delivered' => 'package',
                                'cancelled' => 'ban',
                                'refunded' => 'arrow-uturn-left',
                                'failed' => 'x-circle',
                                default => 'clock',
                            };
                            $statusColor = match ($order->status) {
                                'pending' => 'yellow',
                                'processing' => 'blue',
                                'shipped' => 'indigo',
                                'delivered' => 'green',
                                'cancelled' => 'red',
                                'refunded' => 'teal',
                                'failed' => 'red',
                                default => 'zinc',
                            };
                            $paymentColor = match ($order->payment?->status) {
                                'success' => 'green',
                                'pending' => 'yellow',
                                'failed' => 'red',
                                default => 'zinc',
                            };
                        @endphp

                        <flux:tooltip content="Order Status">
                            <flux:badge :color="$statusColor" :icon="$statusIcon" size="sm" class="capitalize">
                                {{ $order->status }}
                            </flux:badge>
                        </flux:tooltip>

                        <flux:tooltip content="Payment Status">
                            <flux:badge :color="$paymentColor" size="sm" variant="soft" class="capitalize">
                                {{ $order->payment?->status }}
                            </flux:badge>
                        </flux:tooltip>
                    </div>

                    <div class="flex items-center gap-2 text-zinc-400 text-sm mt-2">
                        <flux:icon name="calendar" class="size-4" />
                        {{ $order->created_at->format('M d, Y') }}
                    </div>
                </div>

                <flux:modal.trigger name="edit-order">
                    <flux:button size="sm" variant="primary">Edit Order</flux:button>
                </flux:modal.trigger>
            </flux:card>

            {{-- Products --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                    <flux:heading>Products</flux:heading>
                </div>
                <div class="p-5">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column class="ps-0!">Product</flux:table.column>
                            <flux:table.column>Quantity</flux:table.column>
                            <flux:table.column>Price</flux:table.column>
                            <flux:table.column>Discount</flux:table.column>
                            <flux:table.column>Total</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @forelse ($order->products as $product)
                                <flux:table.row :key="$product->id">
                                    <flux:table.cell class="ps-0!">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="shrink-0 w-12 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                                                <img src="{{ $product->images->first()?->image_url }}"
                                                    alt="{{ $product->name }}"
                                                    class="aspect-square w-full object-cover" />
                                            </div>
                                            <flux:text class="text-sm">{{ $product->name }}</flux:text>
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $product->pivot->quantity }}</flux:table.cell>
                                    <flux:table.cell>{{ number_format($product->pivot->price, 2) }}</flux:table.cell>
                                    <flux:table.cell>{{ number_format($product->pivot->discount ?? 0, 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell>{{ number_format($product->pivot->total, 2) }}</flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="5" class="text-center py-8">
                                        <flux:text class="text-zinc-400">No products found.</flux:text>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>
            </flux:card>

            {{-- Order Timeline --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                    <flux:heading>Order Timeline</flux:heading>
                </div>
                <div class="p-5">
                    <div class="flex flex-col ps-5">
                        @foreach ($timelineStatuses as $step)
                            @php
                                $history = $order->statusHistories->firstWhere('status', $step);
                                $reached = (bool) $history;
                                $stepIcon = match ($step) {
                                    'pending' => 'document-check',
                                    'processing' => 'arrow-path',
                                    'shipped' => 'truck',
                                    'delivered' => 'package',
                                    default => 'clock',
                                };
                                $label = $step === 'pending' ? 'Order Placed' : 'Order ' . ucfirst($step);
                            @endphp

                            <div class="relative min-h-20 ps-10 text-sm">
                                {{-- Icon --}}
                                <div class="absolute left-0 -translate-x-1/2 rounded-full p-2 top-0 z-10"
                                    @class([
                                        'bg-zinc-200 dark:bg-zinc-700 text-zinc-500' => !$reached,
                                        'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900' => $reached,
                                    ])>
                                    <flux:icon name="{{ $stepIcon }}" class="size-4" />
                                </div>

                                {{-- Connector Line --}}
                                <div class="absolute left-0 -translate-x-1/2 top-0 h-full w-0.5 z-0"
                                    @class([
                                        'bg-zinc-200 dark:bg-zinc-700' => !$reached,
                                        'bg-zinc-900 dark:bg-white' => $reached,
                                    ])></div>

                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <flux:text class="font-medium">{{ $label }}</flux:text>
                                        @if ($history?->note)
                                            <flux:text class="text-xs text-zinc-400 mt-1">{{ $history->note }}
                                            </flux:text>
                                        @endif
                                    </div>

                                    @if ($history)
                                        <div class="text-right shrink-0">
                                            <flux:text class="text-sm">{{ $history->created_at->format('M d, Y') }}
                                            </flux:text>
                                            <flux:text class="text-xs text-zinc-400 italic mt-1">
                                                By: {{ $history->changedBy?->name ?? '—' }}
                                            </flux:text>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- ── Right: Sidebar ── --}}
        <div class="col-span-1 space-y-5">

            {{-- Order Summary --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                    <flux:heading>Order Summary</flux:heading>
                </div>
                <div class="px-5 divide-y divide-zinc-100 dark:divide-zinc-700 text-sm text-zinc-500">
                    @foreach ([['icon' => 'receipt-text', 'label' => 'Subtotal', 'value' => $order->subtotal], ['icon' => 'tag', 'label' => 'Discount', 'value' => $order->discount], ['icon' => 'truck', 'label' => 'Delivery Charge', 'value' => $order->delivery_charge], ['icon' => 'receipt-percent', 'label' => 'Tax', 'value' => $order->tax]] as $row)
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center gap-2">
                                <flux:icon name="{{ $row['icon'] }}" class="size-4" />
                                <span>{{ $row['label'] }}:</span>
                            </div>
                            <span>{{ number_format($row['value'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="px-5 py-3 flex items-center justify-between border-t border-zinc-200 dark:border-zinc-700">
                    <flux:text class="font-medium">Total Amount</flux:text>
                    <flux:text class="font-semibold">{{ number_format($order->total, 2) }}</flux:text>
                </div>
            </flux:card>

            {{-- Payment Information --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                    <flux:heading>Payment Information</flux:heading>
                </div>
                <div class="p-5 text-sm space-y-2 text-zinc-500">
                    <p>Transaction ID: <span>{{ $order->payment?->transaction_id ?? '—' }}</span></p>
                    <p>Method: <span>{{ $order->payment?->method ?? '—' }}</span></p>
                    <p>Amount Paid: <span>{{ number_format($order->payment?->amount ?? 0, 2) }}</span></p>
                    <p>Paid At: <span>{{ $order->payment?->paid_at?->format('M d, Y H:i') ?? '—' }}</span></p>
                    <p>Status: <span class="capitalize">{{ $order->payment?->status ?? '—' }}</span></p>
                </div>
            </flux:card>

            {{-- Customer Details --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                    <flux:heading>Customer Details</flux:heading>
                </div>
                <div class="p-5 text-sm space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="size-12 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-800 shrink-0">
                            @if ($order->customer?->avatar)
                                <img src="{{ asset('storage/' . $order->customer->avatar) }}"
                                    class="w-full h-full object-cover" alt="{{ $order->customer->name }}" />
                            @else
                                <div class="w-full h-full grid place-items-center font-semibold text-zinc-500">
                                    {{ strtoupper(substr($order->customer?->name ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <flux:text class="font-medium">{{ $order->customer?->name }}</flux:text>
                            <flux:link href="mailto:{{ $order->customer?->email }}" class="text-xs">
                                {{ $order->customer?->email }}
                            </flux:link>
                        </div>
                    </div>

                    <div>
                        <flux:text class="font-medium">Contact Number</flux:text>
                        <flux:text class="text-zinc-400 mt-1">{{ $order->customer?->phone_number ?? '—' }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="font-medium">Shipping Address</flux:text>
                        <flux:text class="text-zinc-400 mt-1">{{ $order->customer?->country }}</flux:text>
                        <flux:text class="text-zinc-400">{{ $order->customer?->address }}</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Edit Order Modal --}}
    <flux:modal name="edit-order" class="w-full max-w-md">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Edit Order</flux:heading>
                <flux:subheading>Update the status for order #{{ $order->id }}</flux:subheading>
            </div>

            <form wire:submit="updateStatus" class="space-y-4">
                <flux:select wire:model="status" label="Status">
                    @foreach ($orderStatuses as $s)
                        <flux:select.option :value="$s" class="capitalize">{{ ucfirst($s) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="note" label="Note" placeholder="Optional note about this status change..."
                    rows="3" />

                <div class="flex justify-end gap-3 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" class="min-w-36">
                        Save Changes
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
