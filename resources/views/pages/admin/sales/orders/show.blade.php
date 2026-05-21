<?php

use App\Enums\DeliveryOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\SapSyncStatus;
use App\Jobs\SyncOrderToSapJob;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderNote;
use App\Models\OrderTag;
use App\Settings\TaxSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Order Details')] class extends Component
{
    public Order $order;

    public string $status = '';

    public string $note = '';

    // Internal notes
    public string $newNote = '';

    // Tags
    public string $newTagName = '';

    public string $newTagColor = 'blue';

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'payment',
            'user',
            'statusHistories.changedBy',
            'items.product',
            'quote',
            'deliveryOrder',
            'sapSyncLogs',
            'notes.user',
            'tags',
        ]);

        $allowed = $order->status->allowedTransitions();
        $this->status = ! empty($allowed) ? $allowed[0]->value : '';
    }

    // =========================================================================
    //  REAL-TIME UPDATES
    // =========================================================================

    #[On('echo-private:admin.orders,.order.updated')]
    public function handleOrderUpdate(array $data): void
    {
        if ((int) $data['order_id'] !== $this->order->id) {
            return;
        }

        if (isset($data['updated_by']) && (int) $data['updated_by'] !== auth()->id()) {
            $this->dispatch('notify',
                title: 'Order Updated',
                variant: 'info',
                message: "This order was updated by another user. Status: {$data['status_label']}",
            );
        }

        $this->order->refresh()->load([
            'payment', 'user', 'statusHistories.changedBy',
            'items.product', 'quote', 'sapSyncLogs', 'notes.user', 'tags',
        ]);
    }

    // =========================================================================
    //  INTERNAL NOTES
    // =========================================================================

    #[Computed]
    public function notes()
    {
        return $this->order->notes()->with('user')->latest()->get();
    }

    public function addNote(): void
    {
        $this->validate([
            'newNote' => 'required|string|min:2|max:2000',
        ]);

        $this->order->notes()->create([
            'user_id' => auth()->id(),
            'content' => $this->newNote,
        ]);

        $this->newNote = '';
        unset($this->notes);

        $this->dispatch('notify', title: 'Note Added', variant: 'success', message: 'Internal note has been added.');
    }

    public function togglePinNote(int $noteId): void
    {
        $note = OrderNote::where('order_id', $this->order->id)->find($noteId);

        if ($note) {
            $note->update(['is_pinned' => ! $note->is_pinned]);
            unset($this->notes);
        }
    }

    public function deleteNote(int $noteId): void
    {
        $note = OrderNote::where('order_id', $this->order->id)->find($noteId);

        if ($note) {
            $note->delete();
            unset($this->notes);
            $this->dispatch('notify', title: 'Note Deleted', variant: 'success', message: 'Internal note has been deleted.');
        }
    }

    // =========================================================================
    //  ORDER TAGS
    // =========================================================================

    #[Computed]
    public function availableTags()
    {
        return OrderTag::orderBy('name')->get();
    }

    #[Computed]
    public function orderTags()
    {
        return $this->order->tags;
    }

    public function addTag(int $tagId): void
    {
        if ($this->order->tags()->where('order_tag_id', $tagId)->exists()) {
            return;
        }

        $this->order->tags()->attach($tagId, ['added_by_user_id' => auth()->id()]);
        $this->order->load('tags');
        unset($this->orderTags);

        $this->dispatch('notify', title: 'Tag Added', variant: 'success', message: 'Tag has been added to the order.');
    }

    public function removeTag(int $tagId): void
    {
        $this->order->tags()->detach($tagId);
        $this->order->load('tags');
        unset($this->orderTags);

        $this->dispatch('notify', title: 'Tag Removed', variant: 'success', message: 'Tag has been removed from the order.');
    }

    public function createAndAddTag(): void
    {
        $this->validate([
            'newTagName' => 'required|string|min:2|max:50|unique:order_tags,name',
            'newTagColor' => 'required|string|in:'.implode(',', array_keys(OrderTag::COLORS)),
        ]);

        $tag = OrderTag::create([
            'name' => $this->newTagName,
            'color' => $this->newTagColor,
        ]);

        $this->order->tags()->attach($tag->id, ['added_by_user_id' => auth()->id()]);
        $this->order->load('tags');

        $this->newTagName = '';
        $this->newTagColor = 'blue';

        unset($this->availableTags, $this->orderTags);

        $this->dispatch('notify', title: 'Tag Created', variant: 'success', message: "Tag '{$tag->name}' created and added.");
        $this->modal('create-tag')->close();
    }

    #[Computed]
    public function allowedTransitions(): array
    {
        return $this->order->status->allowedTransitions();
    }

    #[Computed]
    public function taxSettings(): TaxSettings
    {
        return app(TaxSettings::class);
    }

    public function updateStatus(): void
    {
        if (empty($this->order->status->allowedTransitions())) {
            $this->dispatch('notify', title: 'Action Not Allowed', variant: 'danger', message: 'This order cannot be updated further.');

            return;
        }

        $newStatus = OrderStatus::from($this->status);

        $this->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => 'nullable|string|max:1000',
        ]);

        if (! $this->order->status->canTransitionTo($newStatus)) {
            $this->addError('status', "Cannot transition from {$this->order->status->label()} to {$newStatus->label()}.");

            return;
        }

        try {
            DB::transaction(function () use ($newStatus) {
                $this->order->transitionTo($newStatus, notes: $this->note ?: null, changedByType: 'user');
            });

            $this->order->refresh()->load('deliveryOrder');
            $this->note = '';

            $this->dispatch('notify', title: 'Status Updated', variant: 'success', message: 'Order status updated.');
            $this->modal('edit-order')->close();
        } catch (Throwable $e) {
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
        if (! in_array($this->order->sap_sync_status, [SapSyncStatus::FAILED, SapSyncStatus::PENDING])) {
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
        // Quote-converted orders have no real shipping method or zone in their snapshot
        // (method_type = 'quote', method_id = null). Delivery order creation is skipped;
        // the admin arranges logistics separately for these orders.
        if ($this->order->wasConvertedFromQuote()) {
            return;
        }

        $snapshot = $this->order->shipping_snapshot;

        if (! $snapshot) {
            throw new RuntimeException('Cannot process order — shipping snapshot is missing.');
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
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('admin.orders.index')" wire:navigate>
                        Orders
                    </flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>#{{ $order->reference }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush

            <div class="flex items-center gap-3">
                <flux:heading size="xl" class="font-bold! tracking-tight">
                    Order #{{ $order->reference }}
                </flux:heading>
                <flux:badge :color="$order->status->color()" variant="solid" size="sm"
                    class="uppercase text-[10px] tracking-widest font-bold">
                    {{ $order->status->label() }}
                </flux:badge>
            </div>

            {{-- Order Tags --}}
            @if ($this->orderTags->isNotEmpty())
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    @foreach ($this->orderTags as $tag)
                        <flux:badge :color="$tag->color" size="sm" class="group cursor-default">
                            {{ $tag->name }}
                            <button wire:click="removeTag({{ $tag->id }})"
                                class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <flux:icon name="x-mark" class="size-3" />
                            </button>
                        </flux:badge>
                    @endforeach
                </div>
            @endif

            <flux:subheading class="mt-1 flex items-center gap-2">
                <flux:icon name="calendar" class="size-4" />
                Placed on {{ $order->created_at->format('M d, Y') }} at {{ $order->created_at->format('g:i A') }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Packing Slip — available once order moves to Processing or beyond --}}
            @if ($order->status !== OrderStatus::PENDING)
                <flux:button variant="ghost" icon="clipboard-document-list" size="sm"
                    :href="route('admin.orders.packing-slip', $order)" target="_blank" class="cursor-pointer">
                    Packing Slip
                </flux:button>
            @endif

            {{-- Print Invoice --}}
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

            {{-- Add Tag --}}
            <flux:dropdown>
                <flux:button size="sm" variant="ghost" icon="tag" class="cursor-pointer">
                    Add Tag
                </flux:button>
                <flux:menu class="w-48">
                    @forelse ($this->availableTags->whereNotIn('id', $this->orderTags->pluck('id')) as $tag)
                        <flux:menu.item wire:click="addTag({{ $tag->id }})">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-{{ $tag->color }}-500"></span>
                                {{ $tag->name }}
                            </div>
                        </flux:menu.item>
                    @empty
                        <flux:menu.item disabled>No more tags available</flux:menu.item>
                    @endforelse
                    <flux:menu.separator />
                    <flux:modal.trigger name="create-tag">
                        <flux:menu.item icon="plus">Create new tag</flux:menu.item>
                    </flux:modal.trigger>
                </flux:menu>
            </flux:dropdown>

            {{-- Manage Status --}}
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
                            @if ($this->taxSettings->tax_enabled && $this->taxSettings->tax_type !== 'inclusive' && $order->tax_cents > 0)
                                <div class="flex justify-between text-sm">
                                    <flux:text>{{ $this->taxSettings->tax_name }}</flux:text>
                                    <flux:heading size="sm" class="font-medium!">
                                        {{ format_currency($order->tax) }}
                                    </flux:heading>
                                </div>
                            @endif
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

            {{-- ============================================================ --}}
            {{-- SAP / ERP SYNC                                                --}}
            {{-- ============================================================ --}}
            <flux:card class="p-0" x-data="{ open: false }">
                <div class="flex items-center justify-between px-3 py-2 dark:border-zinc-600"
                    :class="{ 'border-b': open }">
                    <div class="flex items-center gap-3">
                        <flux:heading>SAP / ERP Sync</flux:heading>
                        @if ($order->sap_sync_status)
                            <flux:badge :color="$order->sap_sync_status->color()" size="sm">
                                {{ $order->sap_sync_status->label() }}
                            </flux:badge>
                        @endif
                    </div>
                    <flux:button size="xs" variant="ghost"
                        class="cursor-pointer transition-transform duration-300" @click="open = !open">
                        <x-slot name="icon">
                            <flux:icon.chevron-down variant="outline" class="size-4 text-zinc-400"
                                x-bind:class="{ 'rotate-180': open }" />
                        </x-slot>
                    </flux:button>
                </div>

                <div x-show="open" x-collapse>
                    <div class="p-6 max-h-[32rem] overflow-y-auto">
                        {{-- Key metrics grid --}}
                        <div class="grid grid-cols-3 gap-x-8 gap-y-4 mb-6">
                            <div class="space-y-0.5">
                                <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">SAP Doc Number</flux:text>
                                <flux:heading size="sm" class="font-mono font-medium!">{{ $order->sap_doc_number ?? '—' }}</flux:heading>
                            </div>
                            <div class="space-y-0.5">
                                <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">SAP Doc Entry</flux:text>
                                <flux:heading size="sm" class="font-mono font-medium!">{{ $order->sap_doc_entry ?? '—' }}</flux:heading>
                            </div>
                            <div class="space-y-0.5">
                                <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">Last Synced</flux:text>
                                <flux:heading size="sm" class="font-medium!">{{ $order->sap_synced_at?->format('M d, Y g:i A') ?? '—' }}</flux:heading>
                            </div>
                            <div class="space-y-0.5">
                                <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">Sync Attempts</flux:text>
                                <flux:heading size="sm" class="font-medium!">{{ $order->sap_sync_attempts ?? 0 }}</flux:heading>
                            </div>
                            <div class="space-y-0.5">
                                <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">KRA CU Number</flux:text>
                                <flux:heading size="sm" class="font-mono font-medium!">{{ $order->kra_cu_number ?? '—' }}</flux:heading>
                            </div>
                            <div class="space-y-0.5">
                                <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">KRA Validated</flux:text>
                                <flux:heading size="sm" class="font-medium!">{{ $order->kra_validated_at?->format('M d, Y g:i A') ?? '—' }}</flux:heading>
                            </div>
                        </div>

                        {{-- Retry button --}}
                        @if (in_array($order->sap_sync_status, [\App\Enums\SapSyncStatus::FAILED, \App\Enums\SapSyncStatus::PENDING]))
                            <flux:button wire:click="retrySapSync" wire:confirm="Re-queue SAP sync for this order?"
                                size="sm" variant="outline" icon="arrow-path" class="cursor-pointer mb-6">
                                Retry SAP Sync
                            </flux:button>
                        @endif

                        {{-- Sync log vertical timeline --}}
                        @php $logs = $order->sapSyncLogs->sortByDesc('created_at'); @endphp

                        @if ($logs->isNotEmpty())
                            <div class="border-t border-zinc-100 dark:border-zinc-700 pt-5">
                                <flux:text class="text-xs text-zinc-400 dark:text-zinc-500 mb-4">
                                    Sync history · {{ $logs->count() }} {{ Str::plural('entry', $logs->count()) }}
                                </flux:text>

                                <div>
                                    @foreach ($logs as $index => $log)
                                        @php
                                            $isLast = $index === $logs->count() - 1;
                                            $isSuccess = $log->status === 'success';
                                            $isFailed = $log->status === 'failed';
                                        @endphp
                                        <div class="relative flex gap-4">
                                            @if (!$isLast)
                                                <div class="absolute left-4 top-8 bottom-0 w-px bg-zinc-200 dark:bg-zinc-700 z-0"></div>
                                            @endif

                                            <div @class([
                                                'relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
                                                'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400' => $isSuccess,
                                                'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' => $isFailed,
                                                'bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400' => !$isSuccess && !$isFailed,
                                            ])>
                                                @if ($isSuccess)
                                                    <flux:icon name="check-circle" class="size-4" />
                                                @elseif ($isFailed)
                                                    <flux:icon name="x-circle" class="size-4" />
                                                @else
                                                    <flux:icon name="clock" class="size-4" />
                                                @endif
                                            </div>

                                            <div class="flex-1 pb-6 min-w-0">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <flux:text class="text-sm font-semibold capitalize">{{ $log->operation }}</flux:text>
                                                            <flux:badge size="sm" :color="$isSuccess ? 'green' : ($isFailed ? 'red' : 'amber')">
                                                                {{ $log->status }}
                                                            </flux:badge>
                                                            @if ($log->http_status_code)
                                                                <flux:badge size="sm" variant="outline">HTTP {{ $log->http_status_code }}</flux:badge>
                                                            @endif
                                                        </div>
                                                        @if ($log->error_message)
                                                            <flux:text class="text-xs text-red-600 dark:text-red-400 mt-1 break-words leading-relaxed">
                                                                {{ $log->error_message }}
                                                            </flux:text>
                                                        @endif
                                                        <div class="flex items-center gap-2 mt-1.5">
                                                            <flux:text class="text-xs text-zinc-400">{{ $log->created_at->format('M d, Y g:i A') }}</flux:text>
                                                            @if ($log->duration_ms)
                                                                <flux:text class="text-xs text-zinc-400">· {{ $log->duration_ms }}ms</flux:text>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <flux:modal.trigger name="sap-log-{{ $log->id }}">
                                                        <flux:button size="sm" variant="ghost" icon="code-bracket" class="shrink-0 cursor-pointer">
                                                            Payload
                                                        </flux:button>
                                                    </flux:modal.trigger>
                                                </div>
                                            </div>
                                        </div>

                                        <flux:modal name="sap-log-{{ $log->id }}" class="max-w-2xl">
                                            <flux:heading size="lg" class="mb-1">SAP Log — {{ $log->operation }}</flux:heading>
                                            <flux:subheading class="mb-4">{{ $log->created_at->format('M d, Y g:i A') }}</flux:subheading>
                                            <div class="space-y-4">
                                                <div>
                                                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-1">Request Payload</p>
                                                    <pre class="text-xs bg-zinc-100 dark:bg-zinc-800 p-3 rounded overflow-x-auto max-h-60">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                                @if ($log->response_payload)
                                                    <div>
                                                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-1">Response Payload</p>
                                                        <pre class="text-xs bg-zinc-100 dark:bg-zinc-800 p-3 rounded overflow-x-auto max-h-60">{{ json_encode($log->response_payload, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                        </flux:modal>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="border-t border-zinc-100 dark:border-zinc-700 pt-5 flex items-center gap-3 text-zinc-400 dark:text-zinc-500">
                                <flux:icon name="clock" class="size-4" />
                                <flux:text class="text-sm">No sync activity recorded yet.</flux:text>
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

            {{-- ============================================================ --}}
            {{-- INTERNAL NOTES                                                --}}
            {{-- ============================================================ --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 flex justify-between items-center">
                    <flux:heading>Internal Notes</flux:heading>
                    <flux:badge variant="outline" size="sm">{{ $this->notes->count() }}</flux:badge>
                </div>

                <div class="p-5 space-y-4">
                    {{-- Add new note form --}}
                    <form wire:submit="addNote" class="space-y-2">
                        <flux:textarea wire:model="newNote" placeholder="Add an internal note..." rows="2"
                            class="text-sm" />
                        @error('newNote')
                            <flux:text class="text-xs text-red-500">{{ $message }}</flux:text>
                        @enderror
                        <flux:button type="submit" size="sm" variant="primary" icon="plus"
                            class="w-full cursor-pointer">
                            Add Note
                        </flux:button>
                    </form>

                    {{-- Notes list --}}
                    @if ($this->notes->isNotEmpty())
                        <div class="space-y-3 max-h-80 overflow-y-auto">
                            @foreach ($this->notes as $orderNote)
                                <div @class([
                                    'rounded-lg border p-3 text-sm',
                                    'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30' =>
                                        $orderNote->is_pinned,
                                    'border-zinc-200 dark:border-zinc-700' => !$orderNote->is_pinned,
                                ])>
                                    {{-- Note header --}}
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            @if ($orderNote->is_pinned)
                                                <flux:icon name="bookmark" class="size-3.5 text-amber-500"
                                                    variant="solid" />
                                            @endif
                                            <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ $orderNote->user?->name ?? 'System' }}
                                            </flux:text>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <flux:button wire:click="togglePinNote({{ $orderNote->id }})"
                                                size="sm" variant="ghost" class="cursor-pointer !p-1"
                                                title="{{ $orderNote->is_pinned ? 'Unpin' : 'Pin' }}">
                                                <flux:icon name="bookmark" class="size-3.5"
                                                    :variant="$orderNote->is_pinned ? 'solid' : 'outline'" />
                                            </flux:button>
                                            <flux:button wire:click="deleteNote({{ $orderNote->id }})"
                                                wire:confirm="Delete this note?" size="sm" variant="ghost"
                                                class="cursor-pointer !p-1 text-red-500 hover:text-red-600"
                                                title="Delete">
                                                <flux:icon name="trash" class="size-3.5" />
                                            </flux:button>
                                        </div>
                                    </div>

                                    {{-- Note content --}}
                                    <flux:text
                                        class="text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap break-words">
                                        {{ $orderNote->content }}
                                    </flux:text>

                                    {{-- Note timestamp --}}
                                    <flux:subheading class="text-[10px]! mt-2">
                                        {{ $orderNote->created_at->diffForHumans() }}
                                    </flux:subheading>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <flux:icon name="chat-bubble-left-right"
                                class="size-8 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                            <flux:subheading class="text-xs!">No internal notes yet</flux:subheading>
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

    {{-- ================================================================== --}}
    {{-- MODAL: Create Tag                                                   --}}
    {{-- ================================================================== --}}
    <flux:modal name="create-tag" class="w-full max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Create New Tag</flux:heading>
                <flux:subheading>Create a tag to organize orders</flux:subheading>
            </div>

            <form wire:submit="createAndAddTag" class="space-y-4">
                <flux:input wire:model="newTagName" label="Tag Name" placeholder="e.g. VIP, Urgent, Wholesale" />
                @error('newTagName')
                    <flux:text class="text-xs text-red-500">{{ $message }}</flux:text>
                @enderror

                <flux:field>
                    <flux:label>Color</flux:label>
                    <div class="grid grid-cols-6 gap-2 mt-2">
                        @foreach (\App\Models\OrderTag::COLORS as $color => $label)
                            <button type="button" wire:click="$set('newTagColor', '{{ $color }}')"
                                class="w-8 h-8 rounded-full bg-{{ $color }}-500 hover:ring-2 hover:ring-offset-2 hover:ring-{{ $color }}-500 transition-all {{ $newTagColor === $color ? 'ring-2 ring-offset-2 ring-' . $color . '-500' : '' }}"
                                title="{{ $label }}">
                            </button>
                        @endforeach
                    </div>
                </flux:field>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" class="cursor-pointer">
                        Create & Add Tag
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

</div>

