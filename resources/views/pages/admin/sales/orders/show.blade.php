<?php

use App\Enums\{OrdersStatus, DeliveryOrderStatus, PaymentStatus};
use App\Models\{Order, DeliveryOrder};
use Illuminate\Validation\Rule;
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;
use Illuminate\Support\Facades\DB;

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
            DB::transaction(function () use ($newStatus) {
                if ($newStatus === OrdersStatus::PROCESSING) {
                    $this->createDeliveryOrder();
                }

                $this->order->transitionTo($newStatus, notes: $this->note ?: null, changedByType: 'user');
            });

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

    private function createDeliveryOrder(): void
    {
        $snapshot = $this->order->shipping_snapshot;

        if (!$snapshot) {
            throw new \RuntimeException('Cannot process order — shipping snapshot is missing.');
        }

        DeliveryOrder::create([
            'order_id' => $this->order->id,
            'shipping_method_id' => $snapshot['method_id'],
            'shipping_rate_id' => $snapshot['rate_id'],
            'pickup_station_id' => $snapshot['station_id'] ?? null,
            'status' => DeliveryOrderStatus::PENDING,
        ]);
    }
};
?>

<div>
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <flux:breadcrumbs class="mb-2">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" wire:navigate
                    icon-variant="outline" />
                <flux:breadcrumbs.item :href="route('admin.orders.index')" wire:navigate>Orders</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>#{{ $order->reference }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <div class="flex items-center gap-3">
                <flux:heading size="xl" class="font-bold tracking-tight">Order #{{ $order->reference }}
                </flux:heading>
                <flux:badge :color="$order->status->color()" variant="solid" size="sm"
                    class="uppercase text-[10px] tracking-widest font-bold">
                    {{ $order->status->label() }}
                </flux:badge>
            </div>
            <flux:text class="mt-1 flex items-center gap-2">
                <flux:icon name="calendar" class="size-4 text-zinc-400" />
                Placed on {{ $order->created_at->format('M d, Y') }} at {{ $order->created_at->format('g:i A') }}
            </flux:text>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="outline" icon="printer" size="sm">Print Invoice</flux:button>
            <flux:modal.trigger name="edit-order">
                <flux:button size="sm" variant="primary" icon="pencil-square" class="cursor-pointer">
                    Manage Status
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <div class="grid grid-cols-4 gap-5 mt-6">

        {{-- ── Left: Main content ── --}}
        <div class="col-span-3 space-y-5">
            {{-- Products --}}
            <flux:card class="p-0">
                <div class="px-6 py-2 border-b flex justify-between items-center">
                    <flux:heading level="3" class="font-semibold">Items & Inventory</flux:heading>
                    <flux:badge variant="outline">{{ $order->items->sum('quantity') }} Items</flux:badge>
                </div>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="ps-6!">Product</flux:table.column>
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
                                <flux:table.cell class="ps-6!">
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

                                <flux:table.cell class="pe-6! font-medium">
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

                {{-- Totals Mini-Panel --}}
                <div class="bg-zinc-50/50 dark:bg-white/2 border-t border-zinc-100 dark:border-zinc-800 p-6">
                    <div class="flex flex-col items-end space-y-2">
                        <div class="w-full max-w-xs space-y-2">
                            <div class="flex justify-between text-sm">
                                <flux:text>Subtotal</flux:text>
                                <flux:text class="font-medium">{{ format_currency($order->subtotal) }}</flux:text>
                            </div>
                            <div class="flex justify-between text-sm">
                                <flux:text>Shipping</flux:text>
                                <flux:text class="font-medium text-green-600">
                                    {{ $order->shipping == 0 ? 'Free' : format_currency($order->shipping) }}
                                </flux:text>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                <flux:heading size="lg">Total Amount</flux:heading>
                                <flux:heading size="lg" class="text-zinc-900 dark:text-white font-bold">
                                    {{ format_currency($order->total) }}</flux:heading>
                            </div>
                        </div>
                    </div>
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
                                    $nextStep = $timelineStatuses[$index + 1] ?? null;
                                    $nextReached = $nextStep
                                        ? (bool) $order->statusHistories->firstWhere('to_status', $nextStep)
                                        : false;
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
                                    <div class="relative shrink-0 flex flex-col items-center ">
                                        <div
                                            class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                        {{ $reached ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' }}">
                                            <flux:icon name="{{ $stepIcon }}" class="size-4" />
                                        </div>

                                        @if (!$isLast)
                                            <div
                                                class="absolute left-1/2 -translate-x-1/2 w-px h-full  transition-colors {{ $nextReached ? 'bg-zinc-900 dark:bg-white' : 'bg-zinc-200 dark:bg-zinc-700' }}">
                                            </div>
                                        @endif
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

            {{-- Customer Details --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b">
                    <flux:heading>Customer</flux:heading>
                </div>
                <div class="p-5 text-sm space-y-4">

                    <flux:card class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-800/50">
                        <div class="shrink-0">
                            @if ($order->user?->avatar)
                                <flux:avatar circle class="size-10" src="{{ $order->user->avatar }}" />
                            @else
                                <flux:avatar circle class="size-10" name="{{ $order->user?->name ?? 'U' }}" />
                            @endif
                        </div>
                        <div>
                            <flux:text class="font-medium">{{ $order->user?->name }}</flux:text>
                            <flux:link href="mailto:{{ $order->user?->email }}" class="text-xs">
                                {{ $order->user?->email }}
                            </flux:link>
                        </div>
                    </flux:card>

                    <div class="flex items-start gap-3">
                        <flux:icon name="phone" class="size-4 mt-0.5 text-zinc-400" />
                        <flux:text size="sm">{{ format_phone($order->user?->phone_number) ?? 'No phone' }}
                        </flux:text>
                    </div>


                    <div class="flex items-start gap-3">
                        <flux:icon name="map-pin" class="size-4 mt-0.5 text-zinc-400" />
                        <div>
                            <flux:text size="sm" class="font-medium block">Shipping Address</flux:text>
                            <flux:text size="xs" class="leading-relaxed">
                                {{ $order->shipping_address['address'] ?? 'N/A' }}<br>
                                {{ $order->shipping_address['area'] ?? '' }},
                                {{ $order->shipping_address['county'] ?? '' }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Payment Information --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b flex justify-between items-center">
                    <flux:heading>Payment</flux:heading>

                    <flux:badge :color="$order->payment?->status?->color()" size="sm">
                        {{ $order->payment?->status?->label() }}
                    </flux:badge>
                </div>
                <div class="p-5 text-sm space-y-2 text-zinc-500">

                    <div class="flex justify-between">
                        <flux:text>Method</flux:text>
                        <flux:text class="font-medium uppercase">{{ $order->payment?->gateway ?? 'N/A' }}</flux:text>
                    </div>

                    <div class="flex justify-between">
                        <flux:text>Transaction ID</flux:text>
                        <flux:text class="font-mono text-[10px]">{{ $order->payment?->transaction_id ?? '—' }}
                        </flux:text>
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
