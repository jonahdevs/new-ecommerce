<?php

use App\Enums\{OrdersStatus, DeliveryOrderStatus, PaymentStatus};
use App\Models\{Order, DeliveryOrder};
use Illuminate\Validation\Rule;
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;

new #[Title('Order Details')] class extends Component {
    public Order $order;
    public string $status = '';
    public string $note = '';

    public array $timelineStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];

    public function mount(Order $order): void
    {
        $this->order = $order->load(['payment', 'user', 'statusHistories.changedBy', 'items.product']);

        $allowed = $order->status->allowedTransitions();
        $this->status = !empty($allowed) ? $allowed[0]->value : '';
    }

    #[Computed]
    public function allowedTransitions(): array
    {
        return $this->order->status->allowedTransitions();
    }

    public function updateStatus(): void
    {
        if (empty($this->order->status->allowedTransitions())) {
            $this->dispatch('notify', variant: 'danger', message: 'This order cannot be updated further.');
            return;
        }

        $this->validate([
            'status' => ['required', Rule::enum(OrdersStatus::class)],
            'note' => 'nullable|string|max:1000',
        ]);

        $newStatus = OrdersStatus::from($this->status);

        if (!$this->order->status->canTransitionTo($newStatus)) {
            $this->addError('status', "Cannot transition from {$this->order->status->label()} to {$newStatus->label()}.");
            return;
        }

        try {
            // transitionTo() creates the history record automatically — don't duplicate
            $this->order->transitionTo($newStatus, notes: $this->note ?: null, changedByType: 'user');
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

    public function processOrder(): void
    {
        if (!$this->order->status->canTransitionTo(OrdersStatus::PROCESSING)) {
            $this->dispatch('notify', variant: 'danger', message: 'Order cannot be processed.');
            return;
        }

        try {
            DB::transaction(function () {
                // 1. Create DeliveryOrder from shipping_snapshot
                $snapshot = $this->order->shipping_snapshot;

                DeliveryOrder::create([
                    'order_id' => $this->order->id,
                    'shipping_method_id' => $snapshot['method_id'],
                    'shipping_rate_id' => $snapshot['rate_id'],
                    'pickup_station_id' => $snapshot['station_id'],
                    'status' => DeliveryOrderStatus::PENDING,
                    'estimated_delivery_at' => now()
                        ->addDays
                        // parse from delivery_window e.g. "1-2 days"
                        (),
                ]);

                // 2. Transition order
                $this->order->transitionTo(OrdersStatus::PROCESSING, notes: 'Order processed by admin', changedByType: 'user');
            });

            $this->order->refresh();
            $this->dispatch('notify', variant: 'success', message: 'Order is now being processed.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Failed to process order.');
            logger()->error('processOrder failed', ['order_id' => $this->order->id, 'error' => $e->getMessage()]);
        }
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.orders.index')" wire:navigate>Orders</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Order Details</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="grid grid-cols-4 gap-5 mt-6">

        {{-- ── Left: Main content ── --}}
        <div class="col-span-3 space-y-5">

            {{-- Order header --}}
            <flux:card class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <flux:text class="font-medium">#{{ $order->reference }}</flux:text>

                        <flux:tooltip content="Order Status">
                            <flux:badge :color="$order->status->color()" :icon="$order->status->icon()" size="sm">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </flux:tooltip>

                        <flux:tooltip content="Payment Status">
                            <flux:badge :color="$order->payment?->status?->color() ?? 'zinc'" size="sm"
                                variant="soft">
                                {{ $order->payment?->status?->label() ?? '—' }}
                            </flux:badge>
                        </flux:tooltip>
                    </div>

                    <div class="flex items-center gap-2 text-zinc-400 text-sm mt-2">
                        <flux:icon name="calendar" class="size-4" />
                        {{ $order->created_at->format('M d, Y · g:i A') }}
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <flux:modal.trigger name="edit-order">
                        <flux:button size="sm" variant="primary" class="cursor-pointer">
                            Edit Order
                        </flux:button>
                    </flux:modal.trigger>

                    @if ($order->status === OrdersStatus::CONFIRMED)
                        <flux:button wire:click="processOrder" variant="primary" icon="arrow-path"
                            class="cursor-pointer">
                            Process Order
                        </flux:button>
                    @endif
                </div>
            </flux:card>

            {{-- Products --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b">
                    <flux:heading>Products</flux:heading>
                </div>
                <div class="p-5">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column class="ps-0!">Product</flux:table.column>
                            <flux:table.column>SKU</flux:table.column>
                            <flux:table.column>Qty</flux:table.column>
                            <flux:table.column>Unit Price</flux:table.column>
                            <flux:table.column>Discount</flux:table.column>
                            <flux:table.column>Total</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @forelse ($order->items as $item)
                                @php
                                    $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                                    $sku = $item->product_snapshot['sku'] ?? '—';
                                    $imagePath = $item->product_snapshot['image_path'] ?? $item->product?->image_path;
                                @endphp

                                <flux:table.row :key="$item->id">
                                    <flux:table.cell class="ps-0!">
                                        <div class="flex items-center gap-3">
                                            <div class="shrink-0 w-12 h-12 rounded border overflow-hidden bg-zinc-50">
                                                @if ($imagePath)
                                                    <img src="{{ asset($imagePath) }}" alt="{{ $name }}"
                                                        class="w-full h-full object-cover" />
                                                @else
                                                    <flux:icon name="photo" class="w-full h-full p-2 text-zinc-300" />
                                                @endif
                                            </div>
                                            <div>
                                                <flux:text class="text-sm font-medium">{{ $name }}</flux:text>
                                                @if ($item->productVariant)
                                                    <flux:text class="text-xs text-zinc-400">
                                                        {{ $item->productVariant->name }}
                                                    </flux:text>
                                                @endif
                                            </div>
                                        </div>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:text class="text-xs text-zinc-400">{{ $sku }}</flux:text>
                                    </flux:table.cell>

                                    <flux:table.cell>{{ $item->quantity }}</flux:table.cell>

                                    <flux:table.cell>
                                        {{ format_currency($item->unit_price_cents / 100) }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ format_currency($item->discount_cents / 100) }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ format_currency($item->total_cents / 100) }}
                                    </flux:table.cell>
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
                <div class="px-5 py-3 border-b">
                    <flux:heading>Order Timeline</flux:heading>
                </div>

                <div class="p-5">
                    {{-- Single vertical line behind all steps --}}
                    <div class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-px bg-zinc-200 dark:bg-zinc-700 z-0"></div>

                        <div class="space-y-0">
                            @foreach ($timelineStatuses as $index => $step)
                                @php
                                    $history = $order->statusHistories->firstWhere('to_status', $step);
                                    $reached = (bool) $history;
                                    $isLast = $index === count($timelineStatuses) - 1;
                                    $stepIcon = match ($step) {
                                        'pending' => 'document-check',
                                        'confirmed' => 'check-circle',
                                        'processing' => 'arrow-path',
                                        'shipped' => 'truck',
                                        'delivered' => 'archive-box',
                                        default => 'clock',
                                    };
                                    $label = match ($step) {
                                        'pending' => 'Order Placed',
                                        'confirmed' => 'Payment Confirmed',
                                        'processing' => 'Order Processing',
                                        'shipped' => 'Order Shipped',
                                        'delivered' => 'Order Delivered',
                                        default => ucfirst($step),
                                    };
                                @endphp

                                <div class="relative flex gap-4 {{ $isLast ? 'pb-0' : 'pb-8' }}">

                                    {{-- Dot --}}
                                    <div
                                        class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                        {{ $reached ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' }}">
                                        <flux:icon name="{{ $stepIcon }}" class="size-4" />
                                    </div>

                                    {{-- Content --}}
                                    <div class="flex-1 flex items-start justify-between gap-4 pt-1">
                                        <div>
                                            <flux:text class="{{ $reached ? 'font-medium' : 'text-zinc-400' }}">
                                                {{ $label }}
                                            </flux:text>
                                            @if ($history?->notes)
                                                <flux:text class="text-xs text-zinc-400 mt-0.5">
                                                    {{ $history->notes }}
                                                </flux:text>
                                            @endif
                                        </div>

                                        @if ($history)
                                            <div class="text-right shrink-0">
                                                <flux:text class="text-sm">
                                                    {{ $history->created_at->format('M d, Y') }}
                                                </flux:text>
                                                <flux:text class="text-xs text-zinc-400 italic mt-0.5">
                                                    {{ $history->changedBy?->name ?? 'System' }}
                                                </flux:text>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </flux:card>

        </div>

        {{-- ── Right: Sidebar ── --}}
        <div class="col-span-1 space-y-5">

            {{-- Order Summary --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b">
                    <flux:heading>Order Summary</flux:heading>
                </div>
                <div class="px-5 divide-y text-sm text-zinc-500">
                    @foreach ([['icon' => 'receipt-text', 'label' => 'Subtotal', 'value' => format_currency($order->subtotal)], ['icon' => 'tag', 'label' => 'Discount', 'value' => '− ' . format_currency($order->discount)], ['icon' => 'truck', 'label' => 'Shipping', 'value' => $order->shipping == 0 ? 'Free' : format_currency($order->shipping)], ['icon' => 'receipt-percent', 'label' => 'Tax', 'value' => format_currency($order->tax_cents / 100)]] as $row)
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center gap-2">
                                <flux:icon name="{{ $row['icon'] }}" class="size-4" />
                                <span>{{ $row['label'] }}</span>
                            </div>
                            <flux:text>{{ $row['value'] }}</flux:text>
                        </div>
                    @endforeach
                </div>
                <div class="px-5 py-3 flex items-center justify-between border-t">
                    <flux:text class="font-semibold">Total</flux:text>
                    <flux:text class="font-semibold">{{ format_currency($order->total) }}</flux:text>
                </div>
            </flux:card>

            {{-- Payment Information --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b">
                    <flux:heading>Payment Information</flux:heading>
                </div>
                <div class="p-5 text-sm space-y-2 text-zinc-500">

                    <div class="flex justify-between">
                        <span>Gateway</span>
                        <span class="text-zinc-700 font-medium">
                            {{ match ($order->payment?->gateway) {
                                'mpesa' => 'M-Pesa',
                                'stripe' => 'Card',
                                'pesawise' => 'Pesawise',
                                'custom' => match ($order->payment?->meta['payment_method'] ?? null) {
                                    'card' => 'Card',
                                    'mpesa' => 'M-Pesa',
                                    default => 'Custom',
                                },
                                default => ucfirst($order->payment?->gateway ?? '—'),
                            } }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span>Status</span>
                        <flux:badge size="sm" :color="$order->payment?->status?->color() ?? 'zinc'">
                            {{ $order->payment?->status?->label() ?? '—' }}
                        </flux:badge>
                    </div>

                    <div class="flex justify-between">
                        <span>Amount</span>
                        <span class="text-zinc-700">
                            {{ format_currency(($order->payment?->amount_cents ?? 0) / 100) }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span>Paid At</span>
                        <span class="text-zinc-700">
                            {{ $order->payment?->paid_at?->format('M d, Y H:i') ?? '—' }}
                        </span>
                    </div>

                    @if ($order->payment?->transaction_id)
                        <div class="pt-1 border-t">
                            <span class="block text-zinc-400 text-xs mb-1">Transaction ID</span>
                            <span class="text-zinc-700 font-mono text-xs break-all">
                                {{ $order->payment->transaction_id }}
                            </span>
                        </div>
                    @endif

                </div>
            </flux:card>

            {{-- Customer Details --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b">
                    <flux:heading>Customer Details</flux:heading>
                </div>
                <div class="p-5 text-sm space-y-4">

                    <div class="flex items-center gap-3">
                        <div class="size-12 rounded-lg overflow-hidden bg-zinc-100 shrink-0">
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
                        <flux:text class="text-zinc-400 mt-1">
                            {{ format_phone($order->user?->phone_number ?? '') ?: '—' }}
                        </flux:text>
                    </div>

                    <div>
                        <flux:text class="font-medium">Shipping Address</flux:text>
                        @if ($order->shipping_address)
                            <flux:text class="text-zinc-400 mt-1">
                                {{ $order->shipping_address['address'] ?? '' }}
                            </flux:text>
                            <flux:text class="text-zinc-400">
                                {{ implode(
                                    ', ',
                                    array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null]),
                                ) }}
                            </flux:text>
                        @else
                            <flux:text class="text-zinc-400 mt-1">—</flux:text>
                        @endif

                        @if ($order->shipping_snapshot['method_name'] ?? null)
                            <flux:text class="text-zinc-400 text-xs mt-1">
                                via {{ $order->shipping_snapshot['method_name'] }}
                            </flux:text>
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
                <flux:subheading>
                    Update the status for order #{{ $order->reference }}
                </flux:subheading>
            </div>

            <form wire:submit="updateStatus" class="space-y-4">
                <flux:select wire:model="status" label="Status">
                    <flux:select.option value="">Select Status</flux:select.option>
                    @foreach ($this->allowedTransitions as $s)
                        <flux:select.option :value="$s->value">
                            {{ $s->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="note" label="Note (optional)"
                    placeholder="Note about this status change..." rows="3" />

                <div class="flex justify-end gap-3 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" class="min-w-36 cursor-pointer"
                        :disabled="empty($this->allowedTransitions)">
                        Save Changes
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

</div>
