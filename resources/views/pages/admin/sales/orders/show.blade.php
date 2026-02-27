<?php

use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\{Title, Computed};
use App\Enums\OrdersStatus;
use Illuminate\Validation\Rule;

new #[Title('Order Details')] class extends Component {
    public Order $order;
    public string $status = '';
    public string $note = '';

    public array $timelineStatuses = ['pending', 'processing', 'shipped', 'delivered'];

    public function mount(Order $order): void
    {
        $this->order = $order->load(['payment', 'user', 'statusHistories.changedBy', 'items' => ['product']]);

        $this->status = $order->status->value;
    }

    #[Computed(persist: true)]
    public function orderStatuses()
    {
        return OrdersStatus::cases();
    }

    public function updateStatus(): void
    {
        $this->validate([
            'status' => ['required', Rule::enum(OrdersStatus::class)],
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $this->order->statusHistories()->create([
                'from_status' => $this->order->status,
                'to_status' => $this->status,
                'notes' => $this->note ?: null,
                'changed_by_user_id' => auth()->id(),
                'changed_by_type' => 'user',
            ]);

            $this->order->update(['status' => $this->status]);
            $this->order->refresh();
            $this->note = '';

            $this->dispatch('notify', variant: 'success', message: 'Order status updated.');
            $this->modal('edit-order')->close();
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
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.orders.index')" wire:navigate>Orders</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Order Details</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="grid grid-cols-4 gap-5 mt-6">

        {{-- Left: Main Content --}}
        <div class="col-span-3 space-y-5">

            {{-- Order Header --}}
            <flux:card class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <flux:text class="font-medium">#{{ $order->reference ?? $order->id }}</flux:text>

                        <flux:tooltip content="Order Status">
                            <flux:badge :color="$order->status?->color()" :icon="$order->status?->icon()" size="sm"
                                class="capitalize">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </flux:tooltip>

                        <flux:tooltip content="Payment Status">
                            <flux:badge :color="$order->payment?->status->color()" size="sm" variant="soft"
                                class="capitalize">
                                {{ $order->payment?->status->label() ?? '—' }}
                            </flux:badge>
                        </flux:tooltip>
                    </div>

                    <div class="flex items-center gap-2 text-zinc-400 text-sm mt-2">
                        <flux:icon name="calendar" class="size-4" />
                        {{ ($order->placed_at ?? $order->created_at)->format('M d, Y') }}
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
                            <flux:table.column>SKU</flux:table.column>
                            <flux:table.column>Quantity</flux:table.column>
                            <flux:table.column>Unit Price</flux:table.column>
                            <flux:table.column>Discount</flux:table.column>
                            <flux:table.column>Total</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @forelse ($order->items as $item)
                                <flux:table.row :key="$item->id">
                                    <flux:table.cell class="ps-0!">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="shrink-0 w-12 h-12 rounded border overflow-hidden bg-zinc-50 dark:bg-zinc-900">
                                                @if ($item->product?->image_path)
                                                    <img src="{{ $item->product?->image_url }}"
                                                        alt="{{ $item->name }}" class="w-full h-full object-cover" />
                                                @else
                                                    <flux:icon name="photo" class="w-full h-full p-2 text-zinc-300" />
                                                @endif
                                            </div>
                                            <div>
                                                <flux:text class="text-sm">{{ $item->product?->name }}</flux:text>
                                                @if ($item->productVariant)
                                                    <flux:text class="text-xs text-zinc-400">
                                                        {{ $item->productVariant->name }}</flux:text>
                                                @endif
                                            </div>
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:text class="text-xs text-zinc-400">{{ $item->sku ?? '—' }}</flux:text>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $item->quantity }}</flux:table.cell>
                                    <flux:table.cell>{{ number_format($item->unit_price, 2) }}</flux:table.cell>
                                    <flux:table.cell>{{ number_format($item->discount_cents / 100, 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell>{{ number_format($item->total_cents / 100, 2) }}</flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="6" class="text-center py-8">
                                        <flux:text class="text-zinc-400">No items found.</flux:text>
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
                                $history = $order->statusHistories->firstWhere('to_status', $step);
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
                                <div class="absolute left-0 -translate-x-1/2 rounded-full p-2 top-0 z-10"
                                    @class([
                                        'bg-zinc-200 dark:bg-zinc-700 text-zinc-500' => !$reached,
                                        'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900' => $reached,
                                    ])>
                                    <flux:icon name="{{ $stepIcon }}" class="size-4" />
                                </div>

                                <div class="absolute left-0 -translate-x-1/2 top-0 h-full w-0.5 z-0"
                                    @class([
                                        'bg-zinc-200 dark:bg-zinc-700' => !$reached,
                                        'bg-zinc-900 dark:bg-white' => $reached,
                                    ])></div>

                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <flux:text class="font-medium">{{ $label }}</flux:text>
                                        @if ($history?->notes)
                                            <flux:text class="text-xs text-zinc-400 mt-1">{{ $history->notes }}
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

        {{-- Right: Sidebar --}}
        <div class="col-span-1 space-y-5">

            {{-- Order Summary --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                    <flux:heading>Order Summary</flux:heading>
                </div>
                <div class="px-5 divide-y divide-zinc-100 dark:divide-zinc-700 text-sm text-zinc-500">
                    @foreach ([['icon' => 'receipt-text', 'label' => 'Subtotal', 'value' => format_currency($order->subtotal)], ['icon' => 'tag', 'label' => 'Discount', 'value' => format_currency($order->discount)], ['icon' => 'truck', 'label' => 'Shipping', 'value' => format_currency($order->shipping)], ['icon' => 'receipt-percent', 'label' => 'Tax', 'value' => format_currency($order->tax_cents / 100)]] as $row)
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center gap-2">
                                <flux:icon name="{{ $row['icon'] }}" class="size-4" />
                                <span>{{ $row['label'] }}:</span>
                            </div>
                            <flux:heading>{{ $row['value'] }}</flux:headi>
                        </div>
                    @endforeach
                </div>
                <div class="px-5 py-3 flex items-center justify-between border-t border-zinc-200 dark:border-zinc-700">
                    <flux:text class="font-medium">Total</flux:text>
                    <flux:text class="font-semibold">{{ format_currency($order->total) }}</flux:text>
                </div>
            </flux:card>

            {{-- Payment Information --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                    <flux:heading>Payment Information</flux:heading>
                </div>
                <div class="p-5 text-sm space-y-2 text-zinc-500">
                    <p>Transaction ID: <span class="text-zinc-700">{{ $order->payment?->transaction_id ?? '—' }}</span>
                    </p>
                    <p>Method: <span class="text-zinc-700">{{ $order->payment?->method ?? '—' }}</span></p>
                    <p>Amount Paid: <span
                            class="text-zinc-700">{{ format_currency($order->payment?->amount ?? 0) }}</span></p>
                    <p>Paid At: <span
                            class="text-zinc-700">{{ $order->payment?->paid_at?->format('M d, Y H:i') ?? '—' }}</span>
                    </p>
                    <p>Status: <span class="text-zinc-700"
                            class="capitalize">{{ $order->payment?->status ?? '—' }}</span></p>
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
                            @if ($order->user?->avatar)
                                <img src="{{ asset('storage/' . $order->user->avatar) }}"
                                    class="w-full h-full object-cover" alt="{{ $order->user->name }}" />
                            @else
                                <div class="w-full h-full grid place-items-center font-semibold text-zinc-500">
                                    {{ strtoupper(substr($order->user?->name ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <flux:text class="font-medium">{{ $order->user?->name }}</flux:text>
                            <flux:link href="mailto:{{ $order->user?->email }}" class="text-xs">
                                {{ $order->user?->email }}
                            </flux:link>
                        </div>
                    </div>

                    <div>
                        <flux:text class="font-medium">Contact Number</flux:text>
                        <flux:text class="text-zinc-400 mt-1">{{ $order->user?->phone_number ?? '—' }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="font-medium">Shipping Address</flux:text>
                        @if ($order->shipping_address)
                            <flux:text class="text-zinc-400 mt-1">
                                {{ $order->shipping_address['address'] ?? '' }}
                            </flux:text>
                            <flux:text class="text-zinc-400">{{ $order->shipping_address['area'] ?? '' }}
                                {{ $order->shipping_address['county'] ?? '' }}
                            </flux:text>
                        @else
                            <flux:text class="text-zinc-400 mt-1">—</flux:text>
                        @endif
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
                <flux:subheading>Update the status for order #{{ $order->reference ?? $order->id }}</flux:subheading>
            </div>

            <form wire:submit="updateStatus" class="space-y-4">
                <flux:select wire:model="status" label="Status">
                    @foreach ($this->orderStatuses as $s)
                        <flux:select.option :value="$s->value" class="capitalize">{{ $s->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="note" label="Note (optional)"
                    placeholder="Note about this status change..." rows="3" />

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
