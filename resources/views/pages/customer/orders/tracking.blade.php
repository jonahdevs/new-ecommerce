<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Attributes\{Layout, Title, On, Locked};
use Livewire\Component;

new #[Title('Order Tracking')] #[Layout('layouts.customer')] class extends Component {
    public Order $order;

    #[Locked]
    public int $orderId;

    public function mount(Order $order): void
    {
        // Guard: order must belong to the authenticated customer
        if ($order->user_id !== auth()->id()) {
            $this->redirectRoute('customer.orders.index', navigate: true);
            return;
        }

        $this->orderId = $order->id;
        $this->order = $order->load([
            'statusHistories' => fn($q) => $q->select(['id', 'order_id', 'to_status', 'created_at', 'changed_by_user_id']),
            'statusHistories.changedBy' => fn($q) => $q->select(['id', 'name']),
            'quote' => fn($q) => $q->select(['id', 'reference']),
        ]);
    }

    // =====================================================
    // Real-time Updates
    // =====================================================

    #[On('echo-private:order.{orderId},.order.updated')]
    public function handleOrderUpdate(array $data): void
    {
        // Refresh the order from database with status histories
        $this->order = $this->order->fresh([
            'statusHistories' => fn($q) => $q->select(['id', 'order_id', 'to_status', 'created_at', 'changed_by_user_id']),
            'statusHistories.changedBy' => fn($q) => $q->select(['id', 'name']),
            'quote' => fn($q) => $q->select(['id', 'reference']),
        ]);

        // Show toast notification
        $this->dispatch('notify', title: 'Status Updated', variant: 'success', message: "Your order is now {$data['status_label']}");
    }
};
?>

<div>
    <x-customer.card title="Order" titleEm="Tracking">
        <x-slot:icon>
            <flux:icon.truck />
        </x-slot:icon>
        <x-slot:action>
            <a href="{{ route('customer.orders.show', $order) }}" wire:navigate
                class="flex items-center gap-1.5 text-xs font-bold tracking-wider uppercase text-on-surface-variant hover:text-primary transition-colors">
                <flux:icon.chevron-left class="w-3.5 h-3.5 stroke-2" />
                Back to Details
            </a>
        </x-slot:action>

        <div class="py-3">
            <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-on-surface-variant mb-1">Order Reference</div>
            <h2 class="font-barlow-condensed text-[24px] font-black text-on-surface leading-tight">#{{ $order->reference }}
            </h2>
            <div class="text-[13px] text-on-surface-variant mt-1">
                Placed on {{ $order->created_at->format('d M Y') }} at {{ $order->created_at->format('g:i A') }}
            </div>

            @if ($order->wasConvertedFromQuote() && $order->quote)
                <div class="flex items-center gap-2 mt-3 p-2 bg-blue-50 border border-blue-100 rounded-sm">
                    <flux:icon.tag class="size-3.5 text-blue-500" />
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800">
                        Converted from quote
                        <a href="{{ route('customer.quotations.show', $order->quote) }}" wire:navigate
                            class="underline ml-1">
                            {{ $order->quote->reference }}
                        </a>
                    </span>
                </div>
            @endif
        </div>

        {{-- Timeline --}}
        <div class="relative px-2">
            @php
                $mainPath = [
                    OrderStatus::PENDING,
                    OrderStatus::PROCESSING,
                    OrderStatus::SHIPPED,
                    OrderStatus::DELIVERED,
                ];

                $currentStatus = $order->status;
                $currentStatusIndex = array_search($currentStatus, $mainPath);
                $isTerminal = in_array($currentStatus, [OrderStatus::CANCELLED, OrderStatus::RETURNED]);
                $histories = $order->statusHistories->keyBy('to_status');

                $maxReachedIndex = 0;
                if ($currentStatusIndex !== false) {
                    $maxReachedIndex = $currentStatusIndex;
                }
                foreach ($histories as $to_status => $history) {
                    foreach ($mainPath as $i => $step) {
                        if ($step->value === $to_status && $i > $maxReachedIndex) {
                            $maxReachedIndex = $i;
                        }
                    }
                }

                $stepMeta = [
                    'pending' => [
                        'label' => 'Order Placed',
                        'desc' => 'Your order has been placed successfully.',
                    ],
                    'processing' => [
                        'label' => 'Being Prepared',
                        'desc' => 'Your payment was received and your items are being packed.',
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
                        $reached = $index <= $maxReachedIndex;
                        $isCurrent = $currentStatusIndex !== false && $index === $currentStatusIndex;

                        $history = $histories->get($step->value);
                        if (!$history && $step === OrderStatus::PENDING) {
                            $history = (object) ['created_at' => $order->created_at];
                        }

                        $isLast = $index === count($mainPath) - 1;
                        $injectTerminalHere = $isTerminal && $index === $maxReachedIndex;
                        $meta = $stepMeta[$step->value];
                    @endphp

                    <div class="relative flex gap-6 {{ $isLast && !$injectTerminalHere ? 'pb-0' : 'pb-10' }}">

                        {{-- Connector line --}}
                        @if (!$isLast && !$injectTerminalHere)
                            @php
                                $nextStepIndex = $index + 1;
                                $nextReached = $nextStepIndex <= $maxReachedIndex;
                            @endphp
                            <div
                                class="absolute left-4.5 top-9 bottom-0 w-0.75 z-0
                                {{ $nextReached ? 'bg-primary' : 'bg-zinc-100' }}">
                            </div>
                        @elseif ($injectTerminalHere)
                            <div class="absolute left-4.5 top-9 bottom-0 w-0.75 z-0 bg-primary">
                            </div>
                        @endif

                        {{-- Step dot --}}
                        <div
                            class="relative z-10 shrink-0 w-9 h-9 rounded-full flex items-center justify-center transition-all duration-300
                            {{ $reached ? 'bg-primary text-white ring-4 ring-[#fff8f6]' : 'bg-zinc-50 text-zinc-300 border border-zinc-100' }}">
                            <flux:icon name="{{ $step->icon() }}" class="size-4.5" />
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 pt-0.5">
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div
                                        class="text-[14px] font-bold
                                        {{ $reached ? 'text-on-surface' : 'text-on-surface-variant' }}">
                                        {{ $meta['label'] }}

                                        {{-- Current step indicator --}}
                                        @if ($isCurrent)
                                            <span
                                                class="ml-2 inline-flex items-center gap-1.5 text-[10px] font-extrabold tracking-widest uppercase text-primary bg-[#fff4f0] px-2 py-0.5 border border-[#ffe4da] rounded-sm">
                                                <span class="relative flex h-1.5 w-1.5">
                                                    <span
                                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                                                    <span
                                                        class="relative inline-flex rounded-full h-1.5 w-1.5 bg-primary"></span>
                                                </span>
                                                Current
                                            </span>
                                        @endif
                                    </div>

                                    <div
                                        class="text-[12px] mt-1 leading-relaxed
                                        {{ $reached ? 'text-on-surface-variant font-medium' : 'text-zinc-300' }}">
                                        {{ $history ? $meta['desc'] : ($reached ? $meta['desc'] : 'Pending...') }}
                                    </div>
                                </div>

                                {{-- Date / time --}}
                                @if ($history)
                                    <div class="sm:text-right shrink-0">
                                        <div class="text-[12px] font-bold text-on-surface">
                                            {{ $history->created_at->format('M j, Y') }}
                                        </div>
                                        <div class="text-[11px] text-on-surface-variant font-medium mt-0.5">
                                            {{ $history->created_at->format('g:i A') }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($injectTerminalHere)
                        {{-- Render Terminal Step --}}
                        <div class="relative flex gap-6 pb-0">
                            <div
                                class="relative z-10 shrink-0 w-9 h-9 rounded-full flex items-center justify-center
                                {{ $currentStatus === OrderStatus::CANCELLED ? 'bg-red-50 text-red-600 border border-red-100 ring-4 ring-red-50/50' : 'bg-orange-50 text-brand-primary border border-orange-100 ring-4 ring-orange-50/50' }}">
                                <flux:icon name="{{ $currentStatus->icon() }}" class="size-4.5" />
                            </div>
                            <div class="flex-1 pt-0.5">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                    <div>
                                        <div
                                            class="text-[14px] font-bold {{ $currentStatus === OrderStatus::CANCELLED ? 'text-red-600' : 'text-brand-primary' }}">
                                            {{ $currentStatus === OrderStatus::CANCELLED ? 'Order Cancelled' : 'Order Returned' }}

                                            <span
                                                class="ml-2 inline-flex items-center gap-1.5 text-[10px] font-extrabold tracking-widest uppercase {{ $currentStatus === OrderStatus::CANCELLED ? 'text-red-600 bg-red-50 border-red-100' : 'text-brand-primary bg-orange-50 border-orange-100' }} px-2 py-0.5 border rounded-sm">
                                                <span class="relative flex h-1.5 w-1.5">
                                                    <span
                                                        class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $currentStatus === OrderStatus::CANCELLED ? 'bg-red-600' : 'bg-brand-primary' }} opacity-75"></span>
                                                    <span
                                                        class="relative inline-flex rounded-full h-1.5 w-1.5 {{ $currentStatus === OrderStatus::CANCELLED ? 'bg-red-600' : 'bg-brand-primary' }}"></span>
                                                </span>
                                                Current
                                            </span>
                                        </div>
                                        <div class="text-[12px] text-on-surface-variant mt-1 font-medium">
                                            {{ $currentStatus === OrderStatus::CANCELLED ? 'This order has been cancelled and will not be processed further.' : 'This order was returned by the customer.' }}
                                        </div>
                                    </div>
                                    @php $terminalHistory = $histories->get($currentStatus->value); @endphp
                                    @if ($terminalHistory)
                                        <div class="sm:text-right shrink-0">
                                            <div class="text-[12px] font-bold text-on-surface">
                                                {{ $terminalHistory->created_at->format('M j, Y') }}
                                            </div>
                                            <div class="text-[11px] text-on-surface-variant font-medium mt-0.5">
                                                {{ $terminalHistory->created_at->format('g:i A') }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @break
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Footer Help --}}
        <div class="mt-12 pt-8 border-t border-zinc-200 text-center">
            <div class="text-[13px] text-on-surface-variant">
                Have questions about your order status?
                <flux:link href="#" class="font-bold text-on-surface hover:text-primary ml-1">Contact Support
                </flux:link>
            </div>
        </div>
    </x-customer.card>
</div>
