<?php

use App\Models\Order;
use Artesaos\SEOTools\Facades\SEOMeta;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account')] #[Title('Order Tracking')] class extends Component {
    #[Locked]
    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 403);
        SEOMeta::setTitle('Tracking — ' . $order->order_number);
        SEOMeta::setRobots('noindex,follow');
        $this->order = $order->load(['statusHistories', 'quote']);
    }
}; ?>

<div class="page-fade space-y-5">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('account.orders.index')" wire:navigate>Orders</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('account.orders.show', $order)" wire:navigate>{{ $order->order_number }}
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Tracking</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-5 py-3">
            <h1 class="font-serif text-lg font-black leading-tight text-ink">
                Order <em class="not-italic text-brand-500">Tracking</em>
            </h1>
            <flux:button size="sm" variant="ghost" icon="chevron-left" :href="route('account.orders.show', $order)"
                wire:navigate>
                Back to details
            </flux:button>
        </div>

        <div class="p-6">

            {{-- Order meta --}}
            <div class="mb-5">
                <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.15em] text-ink-3">Order Reference</p>
                <h2 class="font-serif text-2xl font-black leading-tight text-ink break-words">
                    #{{ $order->order_number }}
                </h2>
                <p class="mt-1 text-[13px] text-ink-3">
                    Placed on {{ $order->created_at->format('d M Y') }} at {{ $order->created_at->format('g:i A') }}
                </p>
            </div>

            {{-- Quote callout --}}
            @if ($order->quote)
                <flux:callout icon="tag" color="blue" class="mb-6">
                    <flux:callout.heading>Converted from a quotation</flux:callout.heading>
                    <flux:callout.text>
                        This order was created from quote
                        <flux:callout.link :href="route('account.quotes.show', $order->quote)" wire:navigate>
                            {{ $order->quote->quote_number }}
                        </flux:callout.link>.
                    </flux:callout.text>
                </flux:callout>
            @endif

            {{-- Timeline --}}
            @php
                $mainPath = [
                    [
                        'value' => 'pending',
                        'label' => 'Order Placed',
                        'icon' => 'clipboard-document-check',
                        'desc' => 'Your order has been placed successfully.',
                    ],
                    [
                        'value' => 'processing',
                        'label' => 'Being Prepared',
                        'icon' => 'cog-6-tooth',
                        'desc' => 'Your payment was received and your items are being packed.',
                    ],
                    [
                        'value' => 'out_for_delivery',
                        'label' => 'Out for Delivery',
                        'icon' => 'truck',
                        'desc' => 'Your order is on its way to you.',
                    ],
                    [
                        'value' => 'completed',
                        'label' => 'Delivered',
                        'icon' => 'check-badge',
                        'desc' => 'Your order was delivered successfully. Enjoy your purchase!',
                    ],
                ];

                $isCancelled = $order->status->value === 'cancelled';
                $histories = $order->statusHistories->keyBy('to_status');

                // Pending always has an implicit history record (order creation time)
                if (!$histories->has('pending')) {
                    $histories->put('pending', (object) ['created_at' => $order->created_at, 'note' => null]);
                }

                // Find the highest reached index
                $maxReachedIndex = 0;
                foreach ($mainPath as $i => $step) {
                    if ($histories->has($step['value'])) {
                        $maxReachedIndex = $i;
                    }
                }
            @endphp

            <div class="relative mt-6 px-1">
                @foreach ($mainPath as $index => $step)
                    @php
                        $history = $histories->get($step['value']);
                        $reached = $index <= $maxReachedIndex;
                        $isCurrent = !$isCancelled && $index === $maxReachedIndex;
                        $isLast = $index === count($mainPath) - 1;

                        // If cancelled, inject the terminal step right after the last reached
                        $injectTerminal = $isCancelled && $index === $maxReachedIndex;
                    @endphp

                    <div class="relative flex gap-6 {{ $isLast && !$injectTerminal ? 'pb-0' : 'pb-10' }}">

                        {{-- Connector line --}}
                        @if (!$isLast || $injectTerminal)
                            @php $nextReached = ($index + 1) <= $maxReachedIndex; @endphp
                            <div @class([
                                'absolute left-[17px] top-9 bottom-0 w-0.5 z-0',
                                'bg-brand-500' => $nextReached || $injectTerminal,
                                'bg-zinc-100' => !$nextReached && !$injectTerminal,
                            ])></div>
                        @endif

                        {{-- Dot --}}
                        <div @class([
                            'relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full transition-all',
                            'bg-brand-500 text-white ring-4 ring-brand-500/20' => $isCurrent,
                            'bg-brand-500 text-white' => $reached && ! $isCurrent,
                            'bg-zinc-50 border border-zinc-100 text-zinc-300' => ! $reached,
                        ])>
                            <flux:icon :name="$step['icon']" variant="mini" class="size-4.5" />
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 pt-0.5">
                            <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-start sm:gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p @class([
                                            'text-[14px] font-bold',
                                            'text-ink' => $reached,
                                            'text-ink-4' => !$reached,
                                        ])>{{ $step['label'] }}</p>

                                        {{-- "Current" pulse badge --}}
                                        @if ($isCurrent)
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-sm border border-brand-200 bg-brand-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-widest text-brand-500">
                                                <span class="relative flex size-1.5">
                                                    <span
                                                        class="absolute inline-flex size-full animate-ping rounded-full bg-brand-500 opacity-75"></span>
                                                    <span
                                                        class="relative inline-flex size-1.5 rounded-full bg-brand-500"></span>
                                                </span>
                                                Current
                                            </span>
                                        @endif
                                    </div>
                                    <p @class([
                                        'mt-1 text-[12px] leading-relaxed',
                                        'font-medium text-ink-2' => $reached,
                                        'text-zinc-300' => !$reached,
                                    ])>
                                        {{ $reached ? $step['desc'] : 'Pending…' }}
                                    </p>
                                    @if ($history?->note)
                                        <p class="mt-1 text-[11px] italic text-ink-3">{{ $history->note }}</p>
                                    @endif
                                </div>

                                {{-- Date/time --}}
                                @if ($history)
                                    <div class="shrink-0 sm:text-right">
                                        <p class="text-[12px] font-bold text-ink">
                                            {{ $history->created_at->format('M j, Y') }}</p>
                                        <p class="mt-0.5 text-[11px] font-medium text-ink-3">
                                            {{ $history->created_at->format('g:i A') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Terminal: inject Cancelled step right after last reached --}}
                    @if ($injectTerminal)
                        @php $cancelHistory = $histories->get('cancelled'); @endphp
                        <div class="relative flex gap-6 pb-0">
                            <div
                                class="relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500 ring-4 ring-red-50/50">
                                <flux:icon.x-circle variant="mini" class="size-4.5" />
                            </div>
                            <div class="flex-1 pt-0.5">
                                <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-start sm:gap-4">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-[14px] font-bold text-red-500">Order Cancelled</p>
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-sm border border-red-100 bg-red-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-widest text-red-500">
                                                <span class="relative flex size-1.5">
                                                    <span
                                                        class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                                                    <span
                                                        class="relative inline-flex size-1.5 rounded-full bg-red-500"></span>
                                                </span>
                                                Current
                                            </span>
                                        </div>
                                        <p class="mt-1 text-[12px] font-medium leading-relaxed text-ink-2">
                                            This order has been cancelled and will not be processed further.
                                        </p>
                                        @if ($cancelHistory?->note)
                                            <p class="mt-1 text-[11px] italic text-ink-3">{{ $cancelHistory->note }}
                                            </p>
                                        @endif
                                    </div>
                                    @if ($cancelHistory)
                                        <div class="shrink-0 sm:text-right">
                                            <p class="text-[12px] font-bold text-ink">
                                                {{ $cancelHistory->created_at->format('M j, Y') }}</p>
                                            <p class="mt-0.5 text-[11px] font-medium text-ink-3">
                                                {{ $cancelHistory->created_at->format('g:i A') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @break
                    @endif
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="mt-10 border-t border-zinc-100 pt-6 text-center">
                <p class="text-[13px] text-ink-3">
                    Have questions about your order status?
                    <a class="ml-1 font-bold text-ink hover:text-brand-500 transition-colors"
                        :href="'mailto:orders@sheffieldsteelsystems.com?subject=Order%20'.urlencode($order->order_number).'%20tracking%20enquiry'">
                        Contact Support
                    </a>
                </p>
            </div>

        </div>
    </div>

</div>
