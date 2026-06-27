<?php

use App\Enums\OrderStatus;
use App\Enums\SapSyncStatus;
use App\Enums\ShipmentStatus;
use App\Jobs\SyncOrderToSapJob;
use App\Models\Order;
use App\Models\ShippingCarrier;
use App\Models\Warehouse;
use App\Notifications\Orders\OrderStatusChanged;
use App\Services\OrderDocumentService;
use App\Services\Sap\SapConfig;
use Flux\Flux;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Order | Admin')] class extends Component
{
    #[Locked]
    public Order $order;

    // Status modal
    public string $status = '';

    public string $statusNote = '';

    public bool $showStatusModal = false;

    // Staff notes
    public string $staffNotes = '';

    // Create shipment modal
    public ?int $carrierId = null;

    public ?int $warehouseId = null;

    public string $trackingNumber = '';

    public string $estimatedDeliveryAt = '';

    public string $shipmentNotes = '';

    public bool $showShipmentModal = false;

    // Update shipment modal
    public string $shipmentStatus = '';

    public bool $showUpdateShipmentModal = false;

    // KRA receipt preview modal
    public bool $showKraPreviewModal = false;

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'items.product.media', 'address', 'user', 'deliveryZone',
            'payments', 'shipment.carrier', 'shipment.warehouse', 'shippingMethod',
            'sapSyncLogs', 'statusHistories.changedBy',
        ]);
        $this->status = $order->status->value;
        $this->staffNotes = $order->staff_notes ?? '';
        $this->shipmentStatus = $order->shipment?->status->value ?? ShipmentStatus::PENDING->value;
    }

    /**
     * Guard every order mutation. The route only enforces `orders.view`, so
     * read-only staff can open this page; any write requires `orders.manage`.
     */
    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('orders.manage'), 403);
    }

    // ==================================================
    // STATUS
    // ==================================================

    public function updateStatus(): void
    {
        $this->authorizeManage();

        if (in_array($this->order->status, [OrderStatus::COMPLETED, OrderStatus::CANCELLED])) {
            Flux::toast(heading: 'Cannot update', text: 'Order is already '.$this->order->status->label().'.', variant: 'danger');

            return;
        }

        $this->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'statusNote' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStatus = OrderStatus::from($this->status);
        $oldStatus = $this->order->status;
        $changed = $oldStatus !== $newStatus;

        $timestamps = match ($newStatus) {
            OrderStatus::PROCESSING => ['confirmed_at' => $this->order->confirmed_at ?? now()],
            OrderStatus::OUT_FOR_DELIVERY => ['shipped_at' => $this->order->shipped_at ?? now()],
            OrderStatus::COMPLETED => ['delivered_at' => $this->order->delivered_at ?? now()],
            OrderStatus::CANCELLED => ['cancelled_at' => $this->order->cancelled_at ?? now()],
            default => [],
        };

        // Suppress the auto-log so we can attach the note in a single activity entry.
        $this->order->disableLogging();
        $this->order->update(array_merge(['status' => $newStatus], $timestamps));
        $this->order->enableLogging();

        $this->order->recordStatusChange($oldStatus, $newStatus, $this->statusNote ?: null, auth()->id());

        $attributes = ['status' => $newStatus->value];
        if ($this->statusNote) {
            $attributes['note'] = $this->statusNote;
        }

        activity('order')
            ->causedBy(auth()->user())
            ->performedOn($this->order)
            ->withProperties([
                'attributes' => $attributes,
                'old' => ['status' => $oldStatus->value],
            ])
            ->event('updated')
            ->log('Status updated');

        $this->order->refresh()->load('statusHistories.changedBy');

        if ($newStatus === OrderStatus::OUT_FOR_DELIVERY) {
            app(OrderDocumentService::class)->generateDispatchDocuments($this->order);
        }

        if ($changed) {
            $this->order->user?->notify(new OrderStatusChanged($this->order));
        }

        $this->statusNote = '';
        $this->showStatusModal = false;
        Flux::toast(heading: 'Status updated', text: 'Order is now '.$this->order->status->label().'.', variant: 'success');
    }

    /** @return array<int, OrderStatus> */
    public function allowedStatuses(): array
    {
        return match ($this->order->status) {
            OrderStatus::PENDING          => [OrderStatus::PROCESSING, OrderStatus::CANCELLED],
            OrderStatus::PROCESSING       => [OrderStatus::CANCELLED],
            OrderStatus::OUT_FOR_DELIVERY => [OrderStatus::COMPLETED, OrderStatus::CANCELLED],
            default                       => [],
        };
    }

    // ==================================================
    // STAFF NOTES
    // ==================================================

    public function saveStaffNotes(): void
    {
        $this->authorizeManage();

        $this->validate(['staffNotes' => ['nullable', 'string', 'max:5000']]);

        $this->order->update(['staff_notes' => $this->staffNotes ?: null]);

        Flux::toast(heading: 'Saved', text: 'Notes updated.', variant: 'success');
    }

    // ==================================================
    // SHIPMENT
    // ==================================================

    public function createShipment(): void
    {
        $this->authorizeManage();

        if (! in_array($this->order->status, [OrderStatus::PROCESSING, OrderStatus::OUT_FOR_DELIVERY])) {
            Flux::toast(heading: 'Cannot create shipment', text: 'A shipment can only be created for orders that are processing or out for delivery.', variant: 'danger');

            return;
        }

        $this->validate([
            'carrierId' => ['nullable', 'exists:shipping_carriers,id'],
            'warehouseId' => ['nullable', 'exists:warehouses,id'],
            'trackingNumber' => ['nullable', 'string', 'max:255'],
            'estimatedDeliveryAt' => ['nullable', 'date', 'after:today'],
            'shipmentNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $carrier = $this->carrierId ? ShippingCarrier::find($this->carrierId) : null;
        $trackingUrl = ($carrier && $this->trackingNumber)
            ? $carrier->trackingUrlFor($this->trackingNumber)
            : null;

        $shipment = $this->order->shipment()->create([
            'shipping_method_id' => $this->order->shipping_method_id,
            'carrier_id' => $this->carrierId,
            'warehouse_id' => $this->warehouseId,
            'tracking_number' => $this->trackingNumber ?: null,
            'tracking_url' => $trackingUrl,
            'status' => ShipmentStatus::PENDING,
            'estimated_delivery_at' => $this->estimatedDeliveryAt ?: null,
            'notes' => $this->shipmentNotes ?: null,
        ]);

        // Creating a shipment is the trigger for "out for delivery" — advance the status automatically.
        if ($this->order->status === OrderStatus::PROCESSING) {
            $this->order->update(['status' => OrderStatus::OUT_FOR_DELIVERY, 'shipped_at' => now()]);
            $this->order->recordStatusChange(OrderStatus::PROCESSING, OrderStatus::OUT_FOR_DELIVERY, null, auth()->id());
            $this->order->user?->notify(new OrderStatusChanged($this->order->fresh()));
        }

        $this->order->refresh()->load(['shipment.carrier', 'shipment.warehouse', 'statusHistories.changedBy']);
        $this->shipmentStatus = $shipment->status->value;
        $this->showShipmentModal = false;

        Flux::toast(heading: 'Shipment created', text: 'Shipment created and order marked as out for delivery.', variant: 'success');
    }

    public function updateShipmentStatus(): void
    {
        $this->authorizeManage();

        $this->validate(['shipmentStatus' => ['required', Rule::enum(ShipmentStatus::class)]]);

        $newStatus = ShipmentStatus::from($this->shipmentStatus);

        if ($this->order->shipment->status->isTerminal()) {
            Flux::toast(heading: 'Cannot update', text: 'Shipment is already '.$this->order->shipment->status->label().'.', variant: 'danger');

            return;
        }

        $this->order->shipment->transitionTo($newStatus);

        if (in_array($newStatus, [ShipmentStatus::PICKED_UP, ShipmentStatus::IN_TRANSIT, ShipmentStatus::OUT_FOR_DELIVERY])
            && $this->order->status === OrderStatus::PROCESSING) {
            $oldOrderStatus = $this->order->status;
            $this->order->disableLogging();
            $this->order->update(['status' => OrderStatus::OUT_FOR_DELIVERY, 'shipped_at' => now()]);
            $this->order->enableLogging();
            $this->order->recordStatusChange($oldOrderStatus, OrderStatus::OUT_FOR_DELIVERY, null, auth()->id());
            $this->status = OrderStatus::OUT_FOR_DELIVERY->value;

            activity('order')
                ->causedBy(auth()->user())
                ->performedOn($this->order)
                ->withProperties([
                    'attributes' => ['status' => OrderStatus::OUT_FOR_DELIVERY->value],
                    'old' => ['status' => $oldOrderStatus->value],
                ])
                ->event('updated')
                ->log('Status updated via shipment');
        }

        if ($newStatus === ShipmentStatus::DELIVERED && $this->order->status !== OrderStatus::COMPLETED) {
            $oldOrderStatus = $this->order->status;
            $this->order->disableLogging();
            $this->order->update(['status' => OrderStatus::COMPLETED, 'delivered_at' => now()]);
            $this->order->enableLogging();
            $this->order->recordStatusChange($oldOrderStatus, OrderStatus::COMPLETED, null, auth()->id());
            $this->order->user?->notify(new OrderStatusChanged($this->order->refresh()));
            $this->status = OrderStatus::COMPLETED->value;

            activity('order')
                ->causedBy(auth()->user())
                ->performedOn($this->order)
                ->withProperties([
                    'attributes' => ['status' => OrderStatus::COMPLETED->value],
                    'old' => ['status' => $oldOrderStatus->value],
                ])
                ->event('updated')
                ->log('Status updated via shipment');
        }

        $this->order->refresh()->load(['shipment.carrier', 'shipment.warehouse', 'statusHistories.changedBy']);
        $this->showUpdateShipmentModal = false;

        Flux::toast(heading: 'Shipment updated', text: 'Shipment is now '.$newStatus->label().'.', variant: 'success');
    }

    // ==================================================
    // SAP
    // ==================================================

    public function resyncSap(): void
    {
        $this->authorizeManage();

        $this->order->update([
            'sap_sync_status' => SapSyncStatus::PENDING,
            'sap_sync_attempts' => 0,
            'sap_sync_error' => null,
        ]);

        SyncOrderToSapJob::dispatch($this->order);
        $this->order->refresh();

        Flux::toast(heading: 'Queued', text: 'SAP sync job dispatched.', variant: 'success');
    }

    public function downloadReceipt(): mixed
    {
        if (! $this->order->receipt_path) {
            return null;
        }

        return Storage::disk('local')->download(
            $this->order->receipt_path,
            $this->order->order_number.'-receipt.pdf',
        );
    }

    // ==================================================
    // COMPUTED
    // ==================================================

    #[Computed]
    public function carriers(): Collection
    {
        return ShippingCarrier::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'sort_order']);
    }

    #[Computed]
    public function warehouses(): Collection
    {
        return Warehouse::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'sort_order']);
    }

    #[Computed]
    public function statusActivities(): \Illuminate\Support\Collection
    {
        return Activity::with('causer')
            ->where('log_name', 'order')
            ->where('subject_type', (new Order)->getMorphClass())
            ->where('subject_id', $this->order->id)
            ->where('event', 'updated')
            ->orderBy('created_at')
            ->get()
            ->keyBy(fn ($a) => $a->getProperty('attributes.status'));
    }

    #[Computed]
    public function showSapCard(): bool
    {
        return app(SapConfig::class)->isEnabled() || $this->order->sap_doc_entry !== null;
    }

    /** @return array<int, ShipmentStatus> */
    public function shipmentStatuses(): array
    {
        return ShipmentStatus::cases();
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

    {{-- ============================================================ --}}
    {{-- PAGE HEADER                                                   --}}
    {{-- ============================================================ --}}
    <div class="mt-2 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl" class="font-mono uppercase">{{ $order->order_number }}</flux:heading>
                <flux:badge size="lg" :color="$order->status->badgeColor()">{{ $order->status->label() }}</flux:badge>
            </div>
            <flux:subheading>Placed {{ $order->created_at->format('d F Y, g:i A') }}</flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Update status --}}
            @if (! in_array($order->status, [OrderStatus::COMPLETED, OrderStatus::CANCELLED]))
                <flux:button wire:click="$set('showStatusModal', true)" variant="primary" size="sm" icon="pencil-square">
                    Update status
                </flux:button>
            @endif

            {{-- Shipment actions --}}
            @if (! $order->shipment && in_array($order->status, [OrderStatus::PROCESSING, OrderStatus::OUT_FOR_DELIVERY]))
                <flux:button wire:click="$set('showShipmentModal', true)" size="sm" icon="truck">
                    Create shipment
                </flux:button>
            @elseif ($order->shipment && ! $order->shipment->status->isTerminal())
                <flux:button wire:click="$set('showUpdateShipmentModal', true)" size="sm" icon="truck">
                    Update shipment
                </flux:button>
            @endif

            @if (in_array($order->status, [OrderStatus::OUT_FOR_DELIVERY, OrderStatus::COMPLETED]))
                <flux:dropdown>
                    <flux:button size="sm" icon="document-text" icon-trailing="chevron-down">
                        Documents
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item icon="clipboard-document-list" target="_blank"
                            :href="route('admin.orders.packing-list', $order)">
                            Packing list
                        </flux:menu.item>
                        <flux:menu.item icon="document-text" target="_blank"
                            :href="route('admin.orders.delivery-note', $order)">
                            Delivery note
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endif
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MAIN GRID                                                     --}}
    {{-- ============================================================ --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ======================================================== --}}
        {{-- LEFT COLUMN                                               --}}
        {{-- ======================================================== --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Items --}}
            <flux:card class="overflow-hidden p-0">
                <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm" class="uppercase tracking-wide">Items</flux:heading>
                </div>
                <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                    <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                        <flux:table.column>Product</flux:table.column>
                        <flux:table.column class="w-32">SKU</flux:table.column>
                        <flux:table.column class="w-36" align="end">Unit price</flux:table.column>
                        <flux:table.column class="w-20" align="end">Qty</flux:table.column>
                        <flux:table.column class="w-36" align="end">Line total</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($order->items as $item)
                            <flux:table.row :key="$item->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        @if ($item->product?->cover_url)
                                            <img src="{{ $item->product->cover_url }}" alt="{{ $item->product_name }}"
                                                class="size-10 shrink-0 rounded object-contain bg-zinc-50 dark:bg-zinc-800">
                                        @else
                                            <div class="flex size-10 shrink-0 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                                <flux:icon.photo class="size-5 text-zinc-400" />
                                            </div>
                                        @endif
                                        <div>
                                            <span class="font-medium dark:text-white">{{ $item->product_name }}</span>
                                            @if ($item->product_model_number)
                                                <span class="block font-mono text-xs font-normal text-zinc-400">Model: {{ $item->product_model_number }}</span>
                                            @endif
                                            @if ($order->hasMixedTaxRates())
                                                <span class="block text-xs text-zinc-400">
                                                    {{ (float) $item->tax_rate > 0 ? 'VAT '.rtrim(rtrim(number_format((float) $item->tax_rate, 2), '0'), '.').'%' : 'VAT exempt' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="font-mono text-xs text-zinc-400">{{ $item->product_sku ?: '—' }}</span>
                                </flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums text-zinc-500">{!! money($item->unit_price_cents) !!}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $item->quantity }}</flux:table.cell>
                                <flux:table.cell align="end" class="font-semibold tabular-nums">{!! money($item->line_total_cents) !!}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="flex justify-end border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <div class="w-72 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                            <span class="font-medium tabular-nums dark:text-white">{!! money($order->subtotal_cents) !!}</span>
                        </div>
                        @if ($order->discount_cents > 0)
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">
                                    Discount
                                    @if ($order->coupon_code)
                                        <span class="ml-1 rounded bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">{{ $order->coupon_code }}</span>
                                    @endif
                                </span>
                                <span class="font-medium tabular-nums text-emerald-600 dark:text-emerald-400">− {!! money($order->discount_cents) !!}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Delivery</span>
                            @if ($order->delivery_cents > 0)
                                <span class="font-medium tabular-nums dark:text-white">{!! money($order->delivery_cents) !!}</span>
                            @else
                                <span class="font-medium text-emerald-600">Free</span>
                            @endif
                        </div>
                        @if ($order->installation_cents > 0)
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Installation</span>
                                <span class="font-medium tabular-nums dark:text-white">{!! money($order->installation_cents) !!}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">{!! $order->vatLabel() !!}</span>
                            <span class="font-medium tabular-nums dark:text-white">{!! money($order->vat_cents) !!}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
                            <span class="font-semibold dark:text-white">Total</span>
                            <span class="text-lg font-bold text-brand-500 tabular-nums">{!! money($order->total_cents) !!}</span>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Notes --}}
            <flux:card class="overflow-hidden p-0">
                <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm" class="uppercase tracking-wide">Notes</flux:heading>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @if ($order->notes)
                        <div class="px-6 py-3">
                            <div class="mb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400">Customer note</div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $order->notes }}</p>
                        </div>
                    @endif
                    <div class="px-6 py-3">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-zinc-400">Staff notes <span class="normal-case font-normal">(internal only)</span></div>
                        <form wire:submit="saveStaffNotes" class="space-y-3">
                            <flux:textarea wire:model="staffNotes" rows="3" placeholder="Add internal notes about this order…" />
                            <div class="flex justify-end">
                                <flux:button type="submit" size="sm" variant="primary">Save notes</flux:button>
                            </div>
                        </form>
                    </div>
                </div>
            </flux:card>

            {{-- Fulfilment timeline --}}
            <flux:card class="overflow-hidden p-0">
                <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm" class="uppercase tracking-wide">Status history</flux:heading>
                </div>
                <div class="p-6">
                    @php
                        // Fixed pipeline — always rendered in full.
                        $pipeline = [
                            ['status' => OrderStatus::PENDING,          'icon' => 'shopping-bag'],
                            ['status' => OrderStatus::PROCESSING,        'icon' => 'cog-6-tooth'],
                            ['status' => OrderStatus::OUT_FOR_DELIVERY,  'icon' => 'truck'],
                            ['status' => OrderStatus::COMPLETED,         'icon' => 'home'],
                        ];

                        // Index history by to_status for O(1) lookup.
                        $historyByStatus = $order->statusHistories->keyBy('to_status');

                        $isCancelledOrder = $order->status === OrderStatus::CANCELLED;
                        $cancelEntry      = $historyByStatus->get(OrderStatus::CANCELLED->value);
                    @endphp

                    <ol>
                        @foreach ($pipeline as $step)
                            @php
                                $entry  = $historyByStatus->get($step['status']->value);
                                $isDone = $entry !== null;

                                // On a cancelled order, stop rendering pipeline steps
                                // once we reach a step that never happened.
                                $skipRest = $isCancelledOrder && ! $isDone;
                            @endphp

                            {{-- Inject the CANCELLED row just before the first skipped step --}}
                            @if ($skipRest && $cancelEntry && $loop->first === false && ! isset($cancelledInjected))
                                @php $cancelledInjected = true; @endphp
                                <li class="relative flex items-start gap-4 pb-8">
                                    <div class="absolute left-4 top-8 bottom-0 w-0.5 -translate-x-1/2 bg-zinc-200 dark:bg-zinc-700"></div>
                                    <div class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-red-500 ring-4 ring-white dark:ring-zinc-900">
                                        <flux:icon.x-mark variant="solid" class="size-4 text-white" />
                                    </div>
                                    <div class="flex flex-1 flex-wrap items-start justify-between gap-x-4 pt-1">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-red-600 dark:text-red-400">Cancelled</p>
                                            @if ($cancelEntry->changedBy)
                                                <p class="mt-0.5 text-xs text-zinc-400">by {{ $cancelEntry->changedBy->name }}</p>
                                            @endif
                                            @if ($cancelEntry->note)
                                                <p class="mt-1 text-xs italic text-zinc-500 dark:text-zinc-400">{{ $cancelEntry->note }}</p>
                                            @endif
                                        </div>
                                        <time class="shrink-0 pt-0.5 text-xs text-zinc-400 dark:text-zinc-500">
                                            {{ $cancelEntry->created_at->format('d M Y, H:i') }}
                                        </time>
                                    </div>
                                </li>
                            @endif

                            <li class="relative flex items-start gap-4 pb-8 last:pb-0">
                                {{-- Connector --}}
                                @if (! $loop->last)
                                    <div @class([
                                        'absolute left-4 top-8 bottom-0 w-0.5 -translate-x-1/2',
                                        'bg-emerald-400 dark:bg-emerald-500' => $isDone && ! $isCancelledOrder,
                                        'bg-zinc-200 dark:bg-zinc-700'       => ! $isDone || $isCancelledOrder,
                                    ])></div>
                                @endif

                                {{-- Icon --}}
                                @if ($isDone && ! $isCancelledOrder)
                                    <div class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-500 ring-4 ring-white dark:ring-zinc-900">
                                        <flux:icon.check variant="solid" class="size-4 text-white" />
                                    </div>
                                @elseif ($isDone && $isCancelledOrder)
                                    {{-- Completed before cancellation --}}
                                    <div class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-500 ring-4 ring-white dark:ring-zinc-900">
                                        <flux:icon.check variant="solid" class="size-4 text-white" />
                                    </div>
                                @else
                                    <div class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 ring-4 ring-white dark:bg-zinc-800 dark:ring-zinc-900">
                                        <flux:icon :icon="$step['icon']" variant="solid" class="size-4 text-zinc-400 dark:text-zinc-500" />
                                    </div>
                                @endif

                                {{-- Content --}}
                                <div class="flex flex-1 flex-wrap items-start justify-between gap-x-4 pt-1">
                                    <div class="min-w-0">
                                        <p @class([
                                            'text-sm font-semibold',
                                            'text-zinc-900 dark:text-white'    => $isDone,
                                            'text-zinc-400 dark:text-zinc-500' => ! $isDone,
                                        ])>{{ $step['status']->label() }}</p>
                                        @if ($isDone)
                                            @if ($entry->changedBy)
                                                <p class="mt-0.5 text-xs text-zinc-400">by {{ $entry->changedBy->name }}</p>
                                            @elseif ($entry->from_status === null)
                                                <p class="mt-0.5 text-xs text-zinc-400">by {{ $order->user?->name ?? 'Customer' }}</p>
                                            @else
                                                <p class="mt-0.5 text-xs text-zinc-400">System</p>
                                            @endif
                                            @if ($entry->note)
                                                <p class="mt-1 text-xs italic text-zinc-500 dark:text-zinc-400">{{ $entry->note }}</p>
                                            @endif
                                        @endif
                                    </div>
                                    @if ($isDone)
                                        <time class="shrink-0 pt-0.5 text-xs text-zinc-400 dark:text-zinc-500">
                                            {{ $entry->created_at->format('d M Y, H:i') }}
                                        </time>
                                    @endif
                                </div>
                            </li>
                        @endforeach

                        {{-- Cancelled at the very end if the order never progressed past PENDING --}}
                        @if ($isCancelledOrder && $cancelEntry && ! isset($cancelledInjected))
                            <li class="relative flex items-start gap-4 pb-8 last:pb-0">
                                <div class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-red-500 ring-4 ring-white dark:ring-zinc-900">
                                    <flux:icon.x-mark variant="solid" class="size-4 text-white" />
                                </div>
                                <div class="flex flex-1 flex-wrap items-start justify-between gap-x-4 pt-1">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-red-600 dark:text-red-400">Cancelled</p>
                                        @if ($cancelEntry->changedBy)
                                            <p class="mt-0.5 text-xs text-zinc-400">by {{ $cancelEntry->changedBy->name }}</p>
                                        @endif
                                        @if ($cancelEntry->note)
                                            <p class="mt-1 text-xs italic text-zinc-500 dark:text-zinc-400">{{ $cancelEntry->note }}</p>
                                        @endif
                                    </div>
                                    <time class="shrink-0 pt-0.5 text-xs text-zinc-400 dark:text-zinc-500">
                                        {{ $cancelEntry->created_at->format('d M Y, H:i') }}
                                    </time>
                                </div>
                            </li>
                        @endif
                    </ol>
                </div>
            </flux:card>

            {{-- Documents --}}
            @php
                $hasPackingList  = (bool) $order->packing_list_path;
                $hasDeliveryNote = (bool) $order->delivery_note_path;
                $hasKraReceipt   = (bool) $order->receipt_path;
                $hasAnyDocument  = $hasPackingList || $hasDeliveryNote || $hasKraReceipt;
            @endphp
            @if ($hasAnyDocument)
                <flux:card class="overflow-hidden p-0">
                    <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:heading size="sm" class="uppercase tracking-wide">Documents</flux:heading>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @if ($hasPackingList)
                            <div class="flex items-center justify-between px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.clipboard-document-list class="size-4 text-zinc-500" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium dark:text-white">Packing List</div>
                                        <div class="text-xs text-zinc-400">Internal use only</div>
                                    </div>
                                </div>
                                <flux:button size="xs" variant="ghost" icon="arrow-down-tray"
                                    :href="route('admin.orders.packing-list', $order)" target="_blank">
                                    Download
                                </flux:button>
                            </div>
                        @endif
                        @if ($hasDeliveryNote)
                            <div class="flex items-center justify-between px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.document-text class="size-4 text-zinc-500" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium dark:text-white">Delivery Note</div>
                                        <div class="text-xs text-zinc-400">Goes with the shipment</div>
                                    </div>
                                </div>
                                <flux:button size="xs" variant="ghost" icon="arrow-down-tray"
                                    :href="route('admin.orders.delivery-note', $order)" target="_blank">
                                    Download
                                </flux:button>
                            </div>
                        @endif
                        @if ($hasKraReceipt)
                            <div class="flex items-center justify-between px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-950/40">
                                        <flux:icon.receipt-percent class="size-4 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium dark:text-white">KRA Tax Receipt</div>
                                        <div class="text-xs text-zinc-400">
                                            @if ($order->cu_number)
                                                CU: {{ $order->cu_number }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <flux:button size="xs" variant="ghost" icon="eye"
                                    wire:click="$set('showKraPreviewModal', true)">
                                    Preview
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif

            {{-- SAP Sync Logs --}}
            @if ($this->showSapCard && $order->sapSyncLogs->isNotEmpty())
                <div x-data="{ open: false }">
                    <flux:card class="overflow-hidden p-0">
                        <div class="flex w-full cursor-pointer items-center justify-between border-b border-zinc-200 px-6 py-3 text-left dark:border-zinc-700"
                            @click="open = !open">
                            <flux:heading size="sm" class="uppercase tracking-wide">SAP Sync Logs</flux:heading>
                            <div class="flex items-center gap-2">
                                @if ($order->sap_sync_status === SapSyncStatus::FAILED)
                                    <flux:button size="xs" variant="ghost" icon="arrow-path"
                                        wire:click.stop="resyncSap"
                                        wire:loading.attr="disabled">
                                        Retry
                                    </flux:button>
                                @endif
                                <flux:icon.chevron-down class="size-4 text-zinc-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                            </div>
                        </div>
                        <div x-show="open" x-collapse>
                            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($order->sapSyncLogs->sortByDesc('created_at') as $log)
                                    <div class="px-6 py-3 text-sm">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-center gap-2">
                                                @php
                                                    $logColor = match ($log->status) {
                                                        'success' => 'text-emerald-600 dark:text-emerald-400',
                                                        'failed'  => 'text-red-500 dark:text-red-400',
                                                        'pending', 'syncing' => 'text-amber-500 dark:text-amber-400',
                                                        default   => 'text-zinc-400',
                                                    };
                                                @endphp
                                                <span class="font-mono text-xs {{ $logColor }} font-semibold uppercase">
                                                    {{ $log->status }}
                                                </span>
                                                <span class="font-medium text-zinc-700 dark:text-zinc-200">
                                                    {{ str_replace('_', ' ', $log->operation) }}
                                                </span>
                                            </div>
                                            <div class="shrink-0 text-right">
                                                @if ($log->http_status_code)
                                                    <span @class([
                                                        'font-mono text-xs px-1.5 py-0.5 rounded',
                                                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400' => $log->http_status_code < 300,
                                                        'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400' => $log->http_status_code >= 400,
                                                    ])>HTTP {{ $log->http_status_code }}</span>
                                                @endif
                                                @if ($log->duration_ms)
                                                    <div class="mt-0.5 text-xs text-zinc-400">{{ number_format($log->duration_ms) }}ms</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mt-1 text-xs text-zinc-500">
                                            <span class="font-mono">{{ $log->http_method }} {{ $log->endpoint }}</span>
                                        </div>
                                        @if ($log->sap_document_number)
                                            <div class="mt-1 text-xs text-zinc-500">
                                                Doc: <span class="font-mono font-semibold text-zinc-700 dark:text-zinc-300">{{ $log->sap_document_number }}</span>
                                            </div>
                                        @endif
                                        @if ($log->error_message)
                                            <div class="mt-1.5 rounded bg-red-50 px-2 py-1.5 text-xs text-red-700 dark:bg-red-950/40 dark:text-red-400">
                                                {{ $log->error_message }}
                                            </div>
                                        @endif
                                        <div class="mt-1 text-xs text-zinc-400">{{ $log->created_at->format('d M Y, H:i') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </flux:card>
                </div>
            @endif

        </div>

        {{-- ======================================================== --}}
        {{-- SIDEBAR                                                   --}}
        {{-- ======================================================== --}}
        <aside class="space-y-6">

            {{-- Payments --}}
            <flux:card class="overflow-hidden p-0">
                <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm" class="uppercase tracking-wide">Payments</flux:heading>
                </div>
                @if ($order->payments->isNotEmpty())
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($order->payments as $payment)
                            <div class="space-y-2 p-6 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium dark:text-white">{{ $payment->methodLabel() }}</span>
                                    <flux:badge size="sm" inset="top bottom" :color="$payment->status->badgeColor()">
                                        {{ $payment->status->label() }}
                                    </flux:badge>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Amount</span>
                                    <span class="font-semibold tabular-nums dark:text-white">{!! money($payment->amount_cents) !!}</span>
                                </div>
                                @php
                                    $ref = $payment->mpesa_receipt ?? $payment->stripe_payment_intent_id ?? $payment->checkout_request_id;
                                @endphp
                                @if ($ref)
                                    <div class="flex justify-between gap-2">
                                        <span class="shrink-0 text-zinc-500">Reference</span>
                                        <span class="truncate text-right font-mono text-xs dark:text-white">{{ $ref }}</span>
                                    </div>
                                @endif
                                @if ($payment->paid_at)
                                    <div class="flex justify-between gap-2">
                                        <span class="shrink-0 text-zinc-500">Paid at</span>
                                        <span class="dark:text-white">{{ $payment->paid_at->format('d M Y, H:i') }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-8 text-center text-sm text-zinc-400">No payments recorded.</div>
                @endif
            </flux:card>

            {{-- Shipment --}}
            <flux:card class="overflow-hidden p-0">
                <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm" class="uppercase tracking-wide">Shipment</flux:heading>
                </div>

                @if ($order->shipment)
                    <div class="space-y-4 p-6">
                        {{-- Status + track link --}}
                        <div class="flex items-center justify-between">
                            <flux:badge :color="$order->shipment->status->color()">{{ $order->shipment->status->label() }}</flux:badge>
                            @if ($order->shipment->tracking_url)
                                <flux:button size="xs" variant="ghost" icon="arrow-top-right-on-square"
                                    :href="$order->shipment->tracking_url" target="_blank">
                                    Track
                                </flux:button>
                            @endif
                        </div>

                        {{-- Key details --}}
                        <div class="space-y-1.5 text-sm">
                            @if ($order->shipment->carrier)
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Carrier</span>
                                    <span class="truncate text-right font-medium dark:text-white">{{ $order->shipment->carrier->name }}</span>
                                </div>
                            @endif
                            @if ($order->shipment->warehouse)
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Pickup</span>
                                    <span class="truncate text-right font-medium dark:text-white">{{ $order->shipment->warehouse->name }}</span>
                                </div>
                            @endif
                            @if ($order->shipment->tracking_number)
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Tracking #</span>
                                    <span class="font-mono text-xs dark:text-white">{{ $order->shipment->tracking_number }}</span>
                                </div>
                            @endif
                            @if ($order->shipment->estimated_delivery_at)
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Est. delivery</span>
                                    <span class="dark:text-white">{{ $order->shipment->estimated_delivery_at->format('d M Y') }}</span>
                                </div>
                            @endif
                            @if ($order->shipment->booked_at)
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Booked</span>
                                    <span class="dark:text-white">{{ $order->shipment->booked_at->format('d M, H:i') }}</span>
                                </div>
                            @endif
                            @if ($order->shipment->picked_up_at)
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Picked up</span>
                                    <span class="dark:text-white">{{ $order->shipment->picked_up_at->format('d M, H:i') }}</span>
                                </div>
                            @endif
                            @if ($order->shipment->delivered_at)
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Delivered</span>
                                    <span class="dark:text-white">{{ $order->shipment->delivered_at->format('d M, H:i') }}</span>
                                </div>
                            @endif
                            @if ($order->shipment->customer_confirmed_at)
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Customer confirmed</span>
                                    <span class="text-emerald-600 dark:text-emerald-400">{{ $order->shipment->customer_confirmed_at->format('d M, H:i') }}</span>
                                </div>
                            @elseif ($order->shipment->customer_disputed_at)
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Customer disputed</span>
                                    <span class="text-amber-600 dark:text-amber-400">{{ $order->shipment->customer_disputed_at->format('d M, H:i') }}</span>
                                </div>
                            @endif
                        </div>

                        @if ($order->shipment->customer_disputed_at && $order->shipment->customer_notes)
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 dark:border-amber-800 dark:bg-amber-900/20">
                                <p class="text-xs font-semibold text-amber-700 dark:text-amber-400">Customer dispute</p>
                                <p class="mt-1 text-xs text-amber-600 dark:text-amber-300">{{ $order->shipment->customer_notes }}</p>
                            </div>
                        @endif

                        @if ($order->shipment->notes)
                            <p class="text-xs italic text-zinc-500">{{ $order->shipment->notes }}</p>
                        @endif
                    </div>
                @else
                    <div class="px-6 py-8 text-center text-sm text-zinc-400">
                        No shipment created yet.
                        @if (! in_array($order->status, [OrderStatus::COMPLETED, OrderStatus::CANCELLED]))
                            <button type="button" wire:click="$set('showShipmentModal', true)"
                                class="mt-2 block w-full text-brand-500 hover:underline text-sm">
                                Create shipment <flux:icon.arrow-right class="inline size-3.5" />
                            </button>
                        @endif
                    </div>
                @endif
            </flux:card>

            {{-- SAP / KRA --}}
            @if ($this->showSapCard)
                <flux:card class="overflow-hidden p-0">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:heading size="sm" class="uppercase tracking-wide">SAP / KRA</flux:heading>
                        @if ($order->sap_sync_status !== SapSyncStatus::COMPLETED)
                            <flux:button size="xs" variant="ghost" icon="arrow-path"
                                wire:click="resyncSap"
                                wire:loading.attr="disabled"
                                tooltip="Re-dispatch sync job">
                                Resync
                            </flux:button>
                        @endif
                    </div>
                    <div class="space-y-4 p-6">
                        <flux:badge :color="$order->sap_sync_status?->badgeColor() ?? 'zinc'">
                            {{ $order->sap_sync_status?->label() ?? 'Not synced' }}
                        </flux:badge>

                        @if ($order->sap_sync_error)
                            <div class="rounded bg-red-50 px-3 py-2 text-xs text-red-700 dark:bg-red-950/40 dark:text-red-400">
                                {{ $order->sap_sync_error }}
                            </div>
                        @endif

                        <div class="space-y-1.5 text-sm">
                            @if ($order->sap_doc_entry)
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Doc entry</span>
                                    <span class="font-mono text-xs dark:text-white">{{ $order->sap_doc_entry }}</span>
                                </div>
                            @endif
                            @if ($order->sap_doc_number)
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Doc number</span>
                                    <span class="font-mono text-xs dark:text-white">{{ $order->sap_doc_number }}</span>
                                </div>
                            @endif
                            @if ($order->sap_synced_at)
                                <div class="flex justify-between gap-2">
                                    <span class="shrink-0 text-zinc-500">Synced at</span>
                                    <span class="dark:text-white">{{ $order->sap_synced_at->format('d M, H:i') }}</span>
                                </div>
                            @endif
                        </div>

                        @if ($order->cu_number)
                            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2.5 dark:border-emerald-800 dark:bg-emerald-950/30">
                                <div class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">KRA CU Number</div>
                                <div class="mt-0.5 font-mono text-sm font-semibold text-emerald-900 dark:text-emerald-300">{{ $order->cu_number }}</div>
                                @if ($order->sap_synced_at)
                                    <div class="mt-0.5 text-xs text-emerald-600">Synced {{ $order->sap_synced_at->format('d M Y, H:i') }}</div>
                                @endif
                            </div>

                            @if ($order->receipt_path)
                                <flux:button size="sm" variant="ghost" icon="eye" class="w-full"
                                    :href="route('admin.orders.kra-receipt', $order)" target="_blank">
                                    View KRA receipt
                                </flux:button>
                            @endif
                        @endif
                    </div>
                </flux:card>
            @endif

            {{-- Customer --}}
            <flux:card class="overflow-hidden p-0">
                <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm" class="uppercase tracking-wide">Customer</flux:heading>
                </div>
                <div class="p-6">
                    @if ($order->user)
                        <div class="flex items-center gap-3">
                            <flux:avatar :name="$order->user->name" size="sm" />
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
                        <flux:heading size="sm" class="uppercase tracking-wide text-zinc-500">Delivery address</flux:heading>
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

                    @if ($order->shippingMethod)
                        <flux:separator class="my-4" />
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500">Shipping method</span>
                            <span class="font-medium dark:text-white">{{ $order->shippingMethod->name }}</span>
                        </div>
                    @endif
                </div>
            </flux:card>

        </aside>
    </div>

    {{-- ============================================================ --}}
    {{-- MODALS                                                        --}}
    {{-- ============================================================ --}}

    {{-- Update order status --}}
    <flux:modal wire:model.self="showStatusModal" class="w-full max-w-sm" :dismissible="true">
        <form wire:submit="updateStatus" class="space-y-5">
            <div>
                <flux:heading size="lg" class="uppercase tracking-wide">Update order status</flux:heading>
                <flux:subheading>Current status: {{ $order->status->label() }}</flux:subheading>
            </div>

            <flux:select wire:model.live="status" label="New status">
                <flux:select.option value="{{ $order->status->value }}" disabled>
                    {{ $order->status->label() }} (current)
                </flux:select.option>
                @foreach ($this->allowedStatuses() as $s)
                    <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea
                wire:model="statusNote"
                label="Note (optional)"
                placeholder="Reason for the status change…"
                rows="3" />

@if ($status === OrderStatus::CANCELLED->value)
                <flux:callout color="red" icon="exclamation-triangle">
                    <flux:callout.text>Cancelling this order cannot be undone and will notify the customer.</flux:callout.text>
                </flux:callout>
            @endif

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary" class="flex-1">Update status</flux:button>
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </form>
    </flux:modal>

    {{-- Create shipment --}}
    <flux:modal wire:model.self="showShipmentModal" class="w-full max-w-lg" :dismissible="true">
        <form wire:submit="createShipment" class="space-y-5">
            <div>
                <flux:heading size="lg" class="uppercase tracking-wide">Create shipment</flux:heading>
                <flux:subheading>Assign a carrier and tracking details for this order.</flux:subheading>
            </div>

            <flux:select wire:model="carrierId" label="Carrier">
                <flux:select.option :value="null">No carrier (manual)</flux:select.option>
                @foreach ($this->carriers as $carrier)
                    <flux:select.option :value="$carrier->id">{{ $carrier->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="trackingNumber" label="Tracking number" placeholder="e.g. SHF-TRK-XXXX" />

            <flux:select wire:model="warehouseId" label="Pickup warehouse">
                <flux:select.option :value="null">None — delivery to customer</flux:select.option>
                @foreach ($this->warehouses as $wh)
                    <flux:select.option :value="$wh->id">{{ $wh->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="estimatedDeliveryAt" type="date" label="Estimated delivery date" />

            <flux:textarea wire:model="shipmentNotes" label="Notes" rows="2" placeholder="Optional dispatch notes…" />

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary" class="flex-1">Create shipment</flux:button>
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </form>
    </flux:modal>

    {{-- Update shipment status --}}
    <flux:modal wire:model.self="showUpdateShipmentModal" class="w-full max-w-sm" :dismissible="true">
        <form wire:submit="updateShipmentStatus" class="space-y-5">
            <div>
                <flux:heading size="lg" class="uppercase tracking-wide">Update shipment</flux:heading>
                @if ($order->shipment)
                    <flux:subheading>Current status: {{ $order->shipment->status->label() }}</flux:subheading>
                @endif
            </div>

            <flux:select wire:model="shipmentStatus" label="New status">
                @foreach ($this->shipmentStatuses() as $s)
                    <flux:select.option value="{{ $s->value }}"
                        :disabled="$s === $order->shipment?->status">
                        {{ $s->label() }}{{ $s === $order->shipment?->status ? ' (current)' : '' }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary" class="flex-1">Update shipment</flux:button>
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </form>
    </flux:modal>

    {{-- KRA Receipt Preview --}}
    <flux:modal wire:model.self="showKraPreviewModal" class="w-full max-w-4xl" :dismissible="true">
        <div class="flex items-center justify-between border-b border-zinc-200 pb-4 dark:border-zinc-700">
            <div>
                <flux:heading size="lg" class="uppercase tracking-wide">KRA Tax Receipt</flux:heading>
                <flux:subheading>{{ $order->order_number }}</flux:subheading>
            </div>
            <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square"
                :href="route('admin.orders.kra-receipt', $order)" target="_blank">
                Open in new tab
            </flux:button>
        </div>
        <div class="mt-4">
            <iframe
                src="{{ route('admin.orders.kra-receipt', $order) }}"
                class="h-[75vh] w-full rounded border border-zinc-200 dark:border-zinc-700"
                type="application/pdf">
            </iframe>
        </div>
    </flux:modal>

</div>
