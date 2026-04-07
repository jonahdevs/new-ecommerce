<?php

use App\Enums\{OrderStatus, DeliveryOrderStatus, PaymentStatus, SapSyncStatus};
use App\Jobs\SyncOrderToSapJob;
use App\Models\{Order, DeliveryOrder};
use Illuminate\Validation\Rule;
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;
use Illuminate\Support\Facades\DB;

new #[Title('Order Details')] class extends Component {
    public Order $order;
    public string $status = '';
    public string $note = '';
    public string $trackingNumber = '';
    public string $courierName = '';

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'payment',
            'user',
            'statusHistories.changedBy',
            'items.product',
            'quote', // loaded to show "converted from quote" notice
            'sapSyncLogs',
        ]);

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
            $this->dispatch('notify', title: 'Action Not Allowed', variant: 'danger', message: 'This order cannot be updated further.');
            return;
        }

        $newStatus = OrderStatus::from($this->status);

        $rules = [
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => 'nullable|string|max:1000',
        ];

        if ($newStatus === OrderStatus::SHIPPED) {
            $rules['trackingNumber'] = 'nullable|string|max:255';
            $rules['courierName'] = 'nullable|string|max:255';
        }

        $this->validate($rules);

        if (!$this->order->status->canTransitionTo($newStatus)) {
            $this->addError('status', "Cannot transition from {$this->order->status->label()} to {$newStatus->label()}.");
            return;
        }

        try {
            DB::transaction(function () use ($newStatus) {
                // Create the delivery order when transitioning to PROCESSING.
                // The guard inside createDeliveryOrder() ensures this only fires
                // on sales orders — quotations are already redirected away in mount().
                if ($newStatus === OrderStatus::PROCESSING) {
                    $this->createDeliveryOrder();
                }

                // Save tracking info when shipping the order
                if ($newStatus === OrderStatus::SHIPPED) {
                    $this->order->update([
                        'tracking_number' => $this->trackingNumber ?: null,
                        'courier_name' => $this->courierName ?: null,
                    ]);
                }

                $this->order->transitionTo($newStatus, notes: $this->note ?: null, changedByType: 'user');
            });

            $this->order->refresh();
            $this->note = '';
            $this->trackingNumber = '';
            $this->courierName = '';

            $this->dispatch('notify', title: 'Status Updated', variant: 'success', message: 'Order status updated.');
            $this->modal('edit-order')->close();
        } catch (\Throwable $e) {
            logger()->error('Failed to update order status.', [
                'order_id' => $this->order->id,
                'user_id' => auth()->id(),
                'exception_message' => $e->getMessage(),
            ]);
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function retrySapSync(): void
    {
        if (!in_array($this->order->sap_sync_status, [SapSyncStatus::FAILED, SapSyncStatus::PENDING])) {
            $this->dispatch('notify', title: 'Not Applicable', variant: 'warning', message: 'SAP sync retry is only available for failed or pending orders.');
            return;
        }

        $this->order->update([
            'sap_sync_status' => SapSyncStatus::PENDING,
            'sap_sync_error' => null,
            'sap_sync_attempts' => 0,
        ]);

        SyncOrderToSapJob::dispatch($this->order);

        $this->order->refresh()->load('sapSyncLogs');
        $this->dispatch('notify', title: 'Retry Queued', variant: 'success', message: 'SAP sync job has been re-queued.');
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
            'shipping_zone_id' => $snapshot['zone_id'],
            'shipping_cost' => $snapshot['cost'],
            'pickup_station_id' => $snapshot['station_id'] ?? null,
            'status' => DeliveryOrderStatus::PENDING,
        ]);
    }
};
?>

<div>
    {{-- ================================================================== --}}
    {{-- PAGE HEADER                                                         --}}
    {{-- ================================================================== --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <flux:breadcrumbs class="mb-2">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" wire:navigate
                    icon-variant="outline" />
                <flux:breadcrumbs.item :href="route('admin.orders.index')" wire:navigate>
                    Orders
                </flux:breadcrumbs.item>
                <flux:breadcrumbs.item>#{{ $order->reference }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex items-center gap-3">
                <flux:heading size="xl" class="font-bold! tracking-tight">
                    Order #{{ $order->reference }}
                </flux:heading>
                <flux:badge :color="$order->status->color()" variant="solid" size="sm"
                    class="uppercase text-[10px] tracking-widest font-bold">
                    {{ $order->status->label() }}
                </flux:badge>
            </div>

            <flux:subheading class="mt-1 flex items-center gap-2">
                <flux:icon name="calendar" class="size-4" />
                Placed on {{ $order->created_at->format('M d, Y') }} at {{ $order->created_at->format('g:i A') }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-3">
            @if ($order->hasKraReceipt())
                <flux:button variant="outline" icon="printer" size="sm"
                    :href="route('customer.orders.receipt', $order)" target="_blank">
                    Print Invoice
                </flux:button>
            @else
                <flux:button variant="outline" icon="printer" size="sm" disabled>
                    Invoice Pending
                </flux:button>
            @endif
            <flux:modal.trigger name="edit-order">
                <flux:button size="sm" variant="primary" icon="pencil-square" class="cursor-pointer">
                    Manage Status
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- CONVERTED FROM QUOTATION NOTICE                                     --}}
    {{-- Shown when this sales order was converted from a quotation.         --}}
    {{-- Gives admin a direct link back to the originating quotation.        --}}
    {{-- ================================================================== --}}
    @if ($order->wasConvertedFromQuote() && $order->quote)
        <flux:callout icon="tag" icon-variant="outline" color="purple" class="mb-5">
            <flux:callout.heading>
                This order was converted from quotation
                <flux:link :href="route('admin.quotations.show', $order->quote)" wire:navigate class="font-medium">
                    {{ $order->quote->reference }}
                </flux:link>
            </flux:callout.heading>
        </flux:callout>
    @endif

    {{-- ================================================================== --}}
    {{-- SAP SYNC FAILURE ALERT                                             --}}
    {{-- Shown when the ERP sync failed — customer order was placed but     --}}
    {{-- the SAP posting did not go through.                                --}}
    {{-- ================================================================== --}}
    @if ($order->sap_sync_status === \App\Enums\SapSyncStatus::FAILED)
        <flux:callout icon="exclamation-triangle" variant="danger" inline>
            <flux:callout.heading>ERP sync failed — order not posted to SAP</flux:callout.heading>
            <x-slot name="actions">
                <flux:button wire:click="retrySapSync" wire:confirm="Re-queue the SAP sync for this order?"
                    variant="danger" icon="arrow-path" class="cursor-pointer"> Retry ERP
                    Sync</flux:button>
            </x-slot>
        </flux:callout>
    @endif

    {{-- ================================================================== --}}
    {{-- MAIN LAYOUT                                                         --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-4 gap-5 mt-6">

        {{-- ── Left: Main content (3 cols) ── --}}
        <div class="col-span-3 space-y-5">

            {{-- ============================================================ --}}
            {{-- ITEMS & INVENTORY                                             --}}
            {{-- ============================================================ --}}
            <flux:card class="p-0">
                <div class="px-6 py-2 border-b border-zinc-200 dark:border-zinc-600 flex justify-between items-center">
                    <flux:heading size="lg" class="font-semibold!">Items & Inventory</flux:heading>
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
                                $imagePath = $item->product_image_url ?? $item->product?->image_url;
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
                                            <flux:heading size="sm" class="font-medium!">{{ $name }}
                                            </flux:heading>
                                            @if ($item->productVariant)
                                                <flux:subheading class="text-xs!">
                                                    {{ $item->productVariant->name }}
                                                </flux:subheading>
                                            @endif
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:subheading class="text-xs!">{{ $sku }}</flux:subheading>
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
                                    <flux:subheading>No items found.</flux:subheading>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>

                {{-- Totals panel --}}
                <div class="bg-zinc-50/50 dark:bg-white/2 border-t border-zinc-100 dark:border-zinc-800 p-6">
                    <div class="flex flex-col items-end space-y-2">
                        <div class="w-full max-w-xs space-y-2">
                            <div class="flex justify-between text-sm">
                                <flux:text>Subtotal</flux:text>
                                <flux:heading size="sm" class="font-medium!">
                                    {{ format_currency($order->subtotal) }}</flux:heading>
                            </div>
                            @if ($order->discount > 0)
                                <div class="flex justify-between text-sm">
                                    <flux:text>Discount</flux:text>
                                    <flux:heading size="sm" class="font-medium! text-green-600">
                                        − {{ format_currency($order->discount) }}
                                    </flux:heading>
                                </div>
                            @endif
                            <div class="flex justify-between text-sm">
                                <flux:text>Shipping</flux:text>
                                <flux:heading size="sm" class="font-medium! text-green-600">
                                    @if ($order->shipping == 0)
                                        Free
                                    @else
                                        {{ format_currency($order->shipping) }}
                                    @endif
                                </flux:heading>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-600">
                                <flux:heading size="lg">Total Amount</flux:heading>
                                <flux:heading size="lg" class="font-bold!">
                                    {{ format_currency($order->total) }}
                                </flux:heading>
                            </div>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- ============================================================ --}}
            {{-- ORDER TIMELINE                                                --}}
            {{-- Sales orders only — always uses the standard 5-step path.    --}}
            {{-- Quotation timeline lives on admin.quotations.show.           --}}
            {{-- ============================================================ --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 flex items-center justify-between">
                    <flux:heading>Order Timeline</flux:heading>
                    <flux:badge :color="$order->status->color()" variant="solid" size="sm">
                        {{ $order->status->label() }}
                    </flux:badge>
                </div>

                <div class="p-5">
                    @php
                        // Standard sales order path — always 5 steps.
                        // The PENDING_QUOTE branch has been removed since quotations
                        // are redirected to their own show page in mount().
                        $mainPath = [
                            OrderStatus::PENDING,
                            OrderStatus::CONFIRMED,
                            OrderStatus::PROCESSING,
                            OrderStatus::SHIPPED,
                            OrderStatus::DELIVERED,
                        ];

                        $isCancelled = $order->status === OrderStatus::CANCELLED;
                        $isReturned = $order->status === OrderStatus::RETURNED;
                        $isTerminal = $isCancelled || $isReturned;

                        // Histories keyed by to_status for quick lookup
                        $histories = $order->statusHistories->keyBy('to_status');

                        // Find the current active step (last reached step)
                        $currentStepIndex = -1;
                        foreach ($mainPath as $idx => $step) {
                            if ($histories->has($step->value)) {
                                $currentStepIndex = $idx;
                            }
                        }
                    @endphp

                    <div class="relative">

                        {{-- Main path --}}
                        @foreach ($mainPath as $index => $step)
                            @php
                                $history = $histories->get($step->value);
                                $reached = (bool) $history;
                                $isActive = $index === $currentStepIndex && !$isTerminal;
                                $isLast = $index === count($mainPath) - 1;
                                $next = $mainPath[$index + 1] ?? null;
                                $nextReached = $next && $histories->has($next->value);
                                $dimmed = $isTerminal && !$reached;
                            @endphp

                            <div class="relative flex gap-4 {{ $isLast && !$isTerminal ? 'pb-0' : 'pb-6' }}">

                                {{-- Connector line --}}
                                @if (!$isLast)
                                    <div @class([
                                        'absolute left-4 top-8 bottom-0 w-px z-0',
                                        'bg-green-500' => $nextReached,
                                        'bg-zinc-200 dark:bg-zinc-600' => !$nextReached,
                                    ])></div>
                                @endif

                                {{-- Step dot --}}
                                <div @class([
                                    'relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-colors',
                                    'bg-green-500 text-white ring-4 ring-green-100 dark:ring-green-900' => $isActive,
                                    'bg-green-500 text-white' => $reached && !$isActive,
                                    'bg-zinc-100 dark:bg-zinc-800 text-zinc-300 dark:text-zinc-600' => $dimmed,
                                    'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' => !$reached && !$dimmed,
                                ])>
                                    <flux:icon name="{{ $step->icon() }}" class="size-4" />
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 flex items-start justify-between gap-4 pt-1 min-w-0">
                                    <div class="min-w-0">
                                        <flux:text @class([
                                            'text-sm',
                                            'font-semibold text-green-600 dark:text-green-400' => $isActive,
                                            'font-medium text-zinc-900 dark:text-white' => $reached && !$isActive,
                                            'text-zinc-300 dark:text-zinc-600' => $dimmed,
                                            'text-zinc-400' => !$reached && !$dimmed,
                                        ])>
                                            {{ $step->label() }}
                                        </flux:text>
                                        @if ($history?->notes)
                                            <flux:subheading class="text-xs! mt-0.5 leading-relaxed">
                                                {{ $history->notes }}
                                            </flux:subheading>
                                        @endif
                                        @if ($history?->changed_by_type === 'system')
                                            <flux:subheading class="text-xs! mt-0.5 italic">
                                                Automatic
                                            </flux:subheading>
                                        @endif
                                    </div>

                                    @if ($history)
                                        <div class="text-right shrink-0">
                                            <flux:heading size="sm" class="text-xs! font-medium!">
                                                {{ $history->created_at->format('M d, Y') }}
                                            </flux:heading>
                                            <flux:subheading class="text-xs! mt-0.5">
                                                {{ $history->created_at->format('g:i A') }}
                                            </flux:subheading>
                                            <flux:subheading class="text-xs! italic mt-0.5">
                                                {{ $history->changedBy?->name ?? 'System' }}
                                            </flux:subheading>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        {{-- Branch: Cancelled --}}
                        @if ($isCancelled)
                            @php $cancelHistory = $histories->get('cancelled'); @endphp
                            <div class="relative flex gap-4 pt-2">
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                    bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400">
                                    <flux:icon name="{{ OrderStatus::CANCELLED->icon() }}" class="size-4" />
                                </div>
                                <div class="flex-1 flex items-start justify-between gap-4 pt-1">
                                    <div>
                                        <flux:heading size="sm"
                                            class="font-medium! text-rose-600 dark:text-rose-400">
                                            Order Cancelled
                                        </flux:heading>
                                        @if ($cancelHistory?->notes)
                                            <flux:subheading class="text-xs! mt-0.5">
                                                {{ $cancelHistory->notes }}
                                            </flux:subheading>
                                        @endif
                                    </div>
                                    @if ($cancelHistory)
                                        <div class="text-right shrink-0">
                                            <flux:heading size="sm" class="text-xs! font-medium!">
                                                {{ $cancelHistory->created_at->format('M d, Y') }}
                                            </flux:heading>
                                            <flux:subheading class="text-xs! mt-0.5">
                                                {{ $cancelHistory->created_at->format('g:i A') }}
                                            </flux:subheading>
                                            <flux:subheading class="text-xs! italic mt-0.5">
                                                {{ $cancelHistory->changedBy?->name ?? 'System' }}
                                            </flux:subheading>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Branch: Returned --}}
                        @if ($isReturned)
                            @php $returnHistory = $histories->get('returned'); @endphp
                            <div class="relative flex gap-4 pt-2">
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                    bg-orange-100 dark:bg-orange-950 text-orange-600 dark:text-orange-400">
                                    <flux:icon name="{{ OrderStatus::RETURNED->icon() }}" class="size-4" />
                                </div>
                                <div class="flex-1 flex items-start justify-between gap-4 pt-1">
                                    <div>
                                        <flux:heading size="sm"
                                            class="font-medium! text-orange-600 dark:text-orange-400">
                                            Order Returned
                                        </flux:heading>
                                        @if ($returnHistory?->notes)
                                            <flux:subheading class="text-xs! mt-0.5">
                                                {{ $returnHistory->notes }}
                                            </flux:subheading>
                                        @endif
                                    </div>
                                    @if ($returnHistory)
                                        <div class="text-right shrink-0">
                                            <flux:heading size="sm" class="text-xs! font-medium!">
                                                {{ $returnHistory->created_at->format('M d, Y') }}
                                            </flux:heading>
                                            <flux:subheading class="text-xs! mt-0.5">
                                                {{ $returnHistory->created_at->format('g:i A') }}
                                            </flux:subheading>
                                            <flux:subheading class="text-xs! italic mt-0.5">
                                                {{ $returnHistory->changedBy?->name ?? 'System' }}
                                            </flux:subheading>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </flux:card>

        </div>

        {{-- ── Right: Sidebar (1 col) ── --}}
        <div class="col-span-1 space-y-5">

            {{-- Customer Details --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">
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
                            <flux:heading size="sm" class="font-medium!">{{ $order->user?->name }}
                            </flux:heading>
                            <flux:link href="mailto:{{ $order->user?->email }}" class="text-xs">
                                {{ $order->user?->email }}
                            </flux:link>
                        </div>
                    </flux:card>

                    <div class="flex items-start gap-3">
                        <flux:icon name="phone" class="size-4 mt-0.5 text-zinc-400" />
                        <flux:text size="sm">
                            {{ format_phone($order->user?->phone_number) ?? 'No phone' }}
                        </flux:text>
                    </div>

                    <div class="flex items-start gap-3">
                        <flux:icon name="map-pin" class="size-4 mt-0.5 text-zinc-400" />
                        <div>
                            <flux:heading size="sm" class="font-medium!">Shipping Address</flux:heading>
                            <flux:subheading class="text-xs! leading-relaxed">
                                {{ $order->shipping_address['address'] ?? 'N/A' }}<br>
                                {{ $order->shipping_address['area'] ?? '' }},
                                {{ $order->shipping_address['county'] ?? '' }}
                                </flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Tracking Information --}}
            @if ($order->tracking_number)
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">
                        <flux:heading>Tracking</flux:heading>
                    </div>
                    <div class="p-5 text-sm space-y-2 text-zinc-500">
                        @if ($order->courier_name)
                            <div class="flex justify-between">
                                <flux:text>Courier</flux:text>
                                <flux:heading size="sm" class="font-medium!">{{ $order->courier_name }}
                                </flux:heading>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <flux:text>Tracking #</flux:text>
                            <flux:text class="font-mono text-xs">{{ $order->tracking_number }}</flux:text>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- Payment Information --}}
            {{-- Sales orders always have a payment record — no quote checks needed. --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 flex justify-between items-center">
                    <flux:heading>Payment</flux:heading>
                    <flux:badge :color="$order->payment?->status?->color()" size="sm">
                        {{ $order->payment?->status?->label() ?? 'No payment' }}
                    </flux:badge>
                </div>
                <div class="p-5 text-sm space-y-2 text-zinc-500">
                    <div class="flex justify-between">
                        <flux:text>Method</flux:text>
                        <flux:heading size="sm" class="font-medium! uppercase">
                            {{ $order->payment?->gateway ?? 'N/A' }}
                        </flux:heading>
                    </div>
                    <div class="flex justify-between">
                        <flux:text>Amount</flux:text>
                        <flux:text class="font-medium">
                            {{ format_currency(($order->payment?->amount_cents ?? 0) / 100) }}
                        </flux:text>
                    </div>
                    <div class="flex justify-between">
                        <flux:text>Transaction ID</flux:text>
                        <flux:text class="font-mono text-[10px]">
                            {{ $order->payment?->transaction_id ?? '—' }}
                        </flux:text>
                    </div>
                    @if ($order->payment?->paid_at)
                        <div class="flex justify-between">
                            <flux:text>Paid at</flux:text>
                            <flux:text class="font-medium">
                                {{ $order->payment->paid_at->format('M d, Y g:i A') }}
                            </flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- SAP / ERP Sync --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 flex justify-between items-center">
                    <flux:heading>SAP / ERP Sync</flux:heading>
                    @if ($order->sap_sync_status)
                        <flux:badge :color="$order->sap_sync_status->color()" size="sm">
                            {{ $order->sap_sync_status->label() }}
                        </flux:badge>
                    @endif
                </div>
                <div class="p-5 space-y-3">
                    @if ($order->sap_order_number)
                        <div class="flex justify-between text-sm">
                            <flux:text>SAP Doc #</flux:text>
                            <flux:text class="font-mono text-xs">{{ $order->sap_order_number }}</flux:text>
                        </div>
                    @endif
                    @if ($order->sap_synced_at)
                        <div class="flex justify-between text-sm">
                            <flux:text>Synced at</flux:text>
                            <flux:text class="text-xs">{{ $order->sap_synced_at->format('M d, Y g:i A') }}</flux:text>
                        </div>
                    @endif
                    @if ($order->kra_cu_number)
                        <div class="flex justify-between text-sm">
                            <flux:text>CU Number</flux:text>
                            <flux:text class="font-mono text-xs">{{ $order->kra_cu_number }}</flux:text>
                        </div>
                    @endif
                    @if ($order->sap_sync_error)
                        <div
                            class="rounded-md bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 p-3">
                            <flux:text class="text-xs text-red-700 dark:text-red-400 break-words">
                                {{ $order->sap_sync_error }}
                            </flux:text>
                        </div>
                    @endif
                    @if (in_array($order->sap_sync_status, [\App\Enums\SapSyncStatus::FAILED, \App\Enums\SapSyncStatus::PENDING]))
                        <flux:button wire:click="retrySapSync" wire:confirm="Re-queue SAP sync for this order?"
                            size="sm" variant="outline" icon="arrow-path" class="w-full cursor-pointer">
                            Retry SAP Sync
                        </flux:button>
                    @endif

                    {{-- Sync log history --}}
                    @if ($order->sapSyncLogs->isNotEmpty())
                        <div x-data="{ open: false }">
                            <button @click="open = !open"
                                class="w-full flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 pt-2 border-t border-zinc-100 dark:border-zinc-700 mt-2 transition-colors">
                                <span>Sync history ({{ $order->sapSyncLogs->count() }})</span>
                                <flux:icon name="chevron-down" class="size-3.5 transition-transform"
                                    x-bind:class="open && 'rotate-180'" />
                            </button>
                            <div x-show="open" x-collapse class="mt-3 space-y-2">
                                @foreach ($order->sapSyncLogs->sortByDesc('created_at') as $log)
                                    <div
                                        class="rounded border border-zinc-100 dark:border-zinc-700 text-xs p-2.5 space-y-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <span @class([
                                                'font-medium capitalize',
                                                'text-green-600 dark:text-green-400' => $log->status === 'success',
                                                'text-red-600 dark:text-red-400' => $log->status === 'failed',
                                                'text-amber-600 dark:text-amber-400' => $log->status === 'pending',
                                            ])>{{ $log->operation }}</span>
                                            <flux:badge size="sm"
                                                :color="match($log->status) { 'success' => 'green', 'failed' => 'red', default => 'amber' }">
                                                {{ $log->http_status_code ?? '—' }}
                                            </flux:badge>
                                        </div>
                                        @if ($log->error_message)
                                            <p class="text-red-600 dark:text-red-400 break-words">
                                                {{ $log->error_message }}</p>
                                        @endif
                                        <div class="flex justify-between text-zinc-400">
                                            <span>{{ $log->created_at->format('M d, g:i A') }}</span>
                                            @if ($log->duration_ms)
                                                <span>{{ $log->duration_ms }}ms</span>
                                            @endif
                                        </div>
                                        <flux:modal.trigger name="sap-log-{{ $log->id }}">
                                            <button class="text-blue-500 hover:underline text-[11px]">View
                                                payload</button>
                                        </flux:modal.trigger>
                                    </div>

                                    <flux:modal name="sap-log-{{ $log->id }}" class="max-w-2xl">
                                        <flux:heading size="lg" class="mb-4">SAP Log — {{ $log->operation }}
                                        </flux:heading>
                                        <div class="space-y-4">
                                            <div>
                                                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-1">
                                                    Request Payload</p>
                                                <pre class="text-xs bg-zinc-100 dark:bg-zinc-800 p-3 rounded overflow-x-auto max-h-60">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                            @if ($log->response_payload)
                                                <div>
                                                    <p
                                                        class="text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-1">
                                                        Response Payload</p>
                                                    <pre class="text-xs bg-zinc-100 dark:bg-zinc-800 p-3 rounded overflow-x-auto max-h-60">{{ json_encode($log->response_payload, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="mt-4 flex justify-end">
                                            <flux:button x-on:click="$dispatch('close-modal')">Close</flux:button>
                                        </div>
                                    </flux:modal>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>

        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- MODAL: Manage Status                                                --}}
    {{-- ================================================================== --}}
    <flux:modal name="edit-order" class="w-full max-w-md">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Update Order Status</flux:heading>
                <flux:subheading>
                    Update the status for order #{{ $order->reference }}
                </flux:subheading>
            </div>

            <form wire:submit="updateStatus" class="space-y-4">
                <flux:select wire:model.live="status" label="Status">
                    <flux:select.option value="">Select Status</flux:select.option>
                    @foreach ($this->allowedTransitions as $s)
                        <flux:select.option :value="$s->value">
                            {{ $s->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                @if ($status === \App\Enums\OrderStatus::SHIPPED->value)
                    <flux:input wire:model="courierName" label="Courier / Logistics Provider"
                        placeholder="e.g. DHL, G4S, Sendy" />
                    <flux:input wire:model="trackingNumber" label="Tracking Number"
                        placeholder="e.g. 1Z999AA10123456784" />
                @endif

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
