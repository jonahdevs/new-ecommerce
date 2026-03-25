<?php

use App\Enums\OrdersStatus;
use App\Models\Order;
use Livewire\Attributes\{Layout, Title};
use Livewire\Component;

new #[Title('Order Tracking')] #[Layout('layouts.customer')] class extends Component {
    public Order $order;

    public function mount(Order $order): void
    {
        // Guard: tracking is only for sales orders.
        // Quotations have their own dedicated page with a different timeline.
        if ($order->isQuotation()) {
            $this->redirectRoute('customer.quotations.show', $order, navigate: true);
            return;
        }

        // Guard: order must belong to the authenticated customer
        if ($order->user_id !== auth()->id()) {
            $this->redirectRoute('customer.orders.index', navigate: true);
            return;
        }

        $this->order = $order->load([
            'statusHistories.changedBy',
            'parentQuotation', // loaded so we can show "converted from quote" notice
        ]);
    }
};
?>

<div>
    <flux:card class="rounded-md p-0">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-3 py-2 border-b">
            <flux:button size="xs" icon="arrow-long-left" variant="ghost" class="cursor-pointer"
                :href="route('customer.orders.show', $order)" wire:navigate />
            <flux:heading size="lg">Order Tracking</flux:heading>
        </div>

        {{-- Order reference --}}
        <div class="px-6 py-4 border-b bg-zinc-50 dark:bg-zinc-800/30">
            <flux:text class="text-xs text-zinc-400 uppercase tracking-wide mb-1">Order Reference</flux:text>
            <flux:heading>{{ $order->reference }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 mt-0.5">
                Placed on {{ $order->created_at->format('M j, Y') }}
            </flux:text>

            {{-- Show quotation origin when this sales order was converted from a quote.
                 Gives the customer a clear link back to the original quotation document. --}}
            @if ($order->wasConverted() && $order->parentQuotation)
                <div class="flex items-center gap-2 mt-2">
                    <flux:icon.tag class="size-3.5 text-zinc-400" />
                    <flux:text class="text-xs text-zinc-400">
                        Converted from quotation
                        <flux:link :href="route('customer.quotations.show', $order->parentQuotation)" wire:navigate
                            class="text-xs!">
                            {{ $order->parentQuotation->reference }}
                        </flux:link>
                    </flux:text>
                </div>
            @endif
        </div>

        {{-- Timeline --}}
        <div class="p-6">
            @php
                // Standard sales order path — always the same regardless of
                // how the order was created (direct checkout or converted from quote).
                // The PENDING_QUOTE path has been removed — quotations have their
                // own timeline on the customer.quotations.show page.
                $mainPath = [
                    OrdersStatus::PENDING,
                    OrdersStatus::CONFIRMED,
                    OrdersStatus::PROCESSING,
                    OrdersStatus::SHIPPED,
                    OrdersStatus::DELIVERED,
                ];

                $isCancelled = $order->status === OrdersStatus::CANCELLED;
                $isReturned = $order->status === OrdersStatus::RETURNED;
                $isTerminal = $isCancelled || $isReturned;

                $histories = $order->statusHistories->keyBy('to_status');

                // Customer-friendly labels and descriptions for each step.
                // These are intentionally plain and reassuring — no technical jargon.
                $stepMeta = [
                    'pending' => [
                        'label' => 'Order Placed',
                        'desc' => 'Your order has been placed successfully.',
                    ],
                    'confirmed' => [
                        'label' => 'Payment Confirmed',
                        'desc' => 'Your payment was received and your order is confirmed.',
                    ],
                    'processing' => [
                        'label' => 'Being Prepared',
                        'desc' => 'Your items are being packed and getting ready.',
                    ],
                    'shipped' => [
                        'label' => 'Out for Delivery',
                        'desc' => 'Your order is on its way to you.',
                    ],
                    'delivered' => [
                        'label' => 'Delivered',
                        'desc' => 'Your order was delivered. Enjoy your purchase!',
                    ],
                ];
            @endphp

            <div class="relative">

                {{-- Main path --}}
                @foreach ($mainPath as $index => $step)
                    @php
                        $history = $histories->get($step->value);
                        $reached = (bool) $history;
                        $isLast = $index === count($mainPath) - 1;
                        $next = $mainPath[$index + 1] ?? null;
                        $nextReached = $next && $histories->has($next->value);
                        $dimmed = $isTerminal && !$reached;
                        $meta = $stepMeta[$step->value];
                        $isCurrent = $order->status === $step && !$isTerminal;
                    @endphp

                    <div class="relative flex gap-5 {{ $isLast && !$isTerminal ? 'pb-0' : 'pb-8' }}">

                        {{-- Connector line --}}
                        @if (!$isLast)
                            <div
                                class="absolute left-4 top-8 bottom-0 w-0.5 z-0
                                {{ $nextReached ? 'bg-green-500' : 'bg-zinc-200 dark:bg-zinc-700' }}">
                            </div>
                        @endif

                        {{-- Step dot --}}
                        <div
                            class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-colors
                            {{ $reached
                                ? 'bg-green-500 dark:bg-white text-white'
                                : ($isCurrent
                                    ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 ring-4 ring-zinc-200 dark:ring-zinc-700'
                                    : ($dimmed
                                        ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-300 dark:text-zinc-600'
                                        : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400')) }}">
                            <flux:icon name="{{ $step->icon() }}" class="size-4" />
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 pt-1 pb-1">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <flux:text
                                        class="text-sm font-semibold
                                        {{ $reached || $isCurrent
                                            ? 'text-zinc-900 dark:text-white'
                                            : ($dimmed
                                                ? 'text-zinc-300 dark:text-zinc-600'
                                                : 'text-zinc-400') }}">
                                        {{ $meta['label'] }}

                                        {{-- Pulsing "Current" indicator --}}
                                        @if ($isCurrent)
                                            <span
                                                class="ml-2 inline-flex items-center gap-1 text-xs font-normal text-zinc-500">
                                                <span class="relative flex h-2 w-2">
                                                    <span
                                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                                    <span
                                                        class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                                </span>
                                                Current
                                            </span>
                                        @endif
                                    </flux:text>

                                    <flux:text
                                        class="text-xs mt-0.5
                                        {{ $reached || $isCurrent ? 'text-zinc-500' : 'text-zinc-300 dark:text-zinc-600' }}">
                                        {{ $history ? $meta['desc'] : ($dimmed ? '—' : 'Pending') }}
                                    </flux:text>
                                </div>

                                {{-- Date / time --}}
                                @if ($history)
                                    <div class="text-right shrink-0">
                                        <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ $history->created_at->format('M j, Y') }}
                                        </flux:text>
                                        <flux:text class="text-xs text-zinc-400 mt-0.5">
                                            {{ $history->created_at->format('g:i A') }}
                                        </flux:text>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Branch: Cancelled --}}
                @if ($isCancelled)
                    @php $cancelHistory = $histories->get('cancelled'); @endphp
                    <div class="relative flex gap-5 pt-2">
                        <div
                            class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                            bg-rose-100 dark:bg-rose-950 text-rose-500 dark:text-rose-400">
                            <flux:icon name="{{ OrdersStatus::CANCELLED->icon() }}" class="size-4" />
                        </div>
                        <div class="flex-1 pt-1">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <flux:text class="text-sm font-semibold text-rose-600 dark:text-rose-400">
                                        Order Cancelled
                                    </flux:text>
                                    <flux:text class="text-xs text-zinc-500 mt-0.5">
                                        This order has been cancelled.
                                    </flux:text>

                                </div>
                                @if ($cancelHistory)
                                    <div class="text-right shrink-0">
                                        <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ $cancelHistory->created_at->format('M j, Y') }}
                                        </flux:text>
                                        <flux:text class="text-xs text-zinc-400 mt-0.5">
                                            {{ $cancelHistory->created_at->format('g:i A') }}
                                        </flux:text>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Branch: Returned --}}
                @if ($isReturned)
                    @php $returnHistory = $histories->get('returned'); @endphp
                    <div class="relative flex gap-5 pt-2">
                        <div
                            class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                            bg-orange-100 dark:bg-orange-950 text-orange-500 dark:text-orange-400">
                            <flux:icon name="{{ OrdersStatus::RETURNED->icon() }}" class="size-4" />
                        </div>
                        <div class="flex-1 pt-1">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <flux:text class="text-sm font-semibold text-orange-600 dark:text-orange-400">
                                        Order Returned
                                    </flux:text>
                                    <flux:text class="text-xs text-zinc-500 mt-0.5">
                                        This order has been returned.
                                    </flux:text>
                                </div>
                                @if ($returnHistory)
                                    <div class="text-right shrink-0">
                                        <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ $returnHistory->created_at->format('M j, Y') }}
                                        </flux:text>
                                        <flux:text class="text-xs text-zinc-400 mt-0.5">
                                            {{ $returnHistory->created_at->format('g:i A') }}
                                        </flux:text>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>

    </flux:card>
</div>
