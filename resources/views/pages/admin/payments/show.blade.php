<?php

use App\Models\Payment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Payment — Admin')] class extends Component {
    #[Locked]
    public Payment $payment;

    public function mount(Payment $payment): void
    {
        $this->payment = $payment->load('order.user');
    }

    /** @return array<int, array{label: string, value: ?string}> */
    public function details(): array
    {
        $p = $this->payment;

        return array_values(array_filter([
            ['label' => 'Provider', 'value' => ucfirst(str_replace('_', ' ', (string) $p->provider))],
            ['label' => 'Account reference', 'value' => $p->account_reference],
            ['label' => 'Phone', 'value' => $p->phone],
            ['label' => 'M-Pesa receipt', 'value' => $p->mpesa_receipt],
            ['label' => 'Merchant request', 'value' => $p->merchant_request_id],
            ['label' => 'Checkout request', 'value' => $p->checkout_request_id],
            ['label' => 'Stripe session', 'value' => $p->stripe_session_id],
            ['label' => 'Stripe payment intent', 'value' => $p->stripe_payment_intent_id],
            ['label' => 'Result code', 'value' => $p->result_code !== null ? (string) $p->result_code : null],
            ['label' => 'Result description', 'value' => $p->result_desc],
        ], fn ($row) => ! empty($row['value'])));
    }
}; ?>

@php
    $kes = fn ($cents) => 'KES&nbsp;'.number_format(intdiv((int) $cents, 100), 0, '.', ',');
@endphp

<div>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.payments.index')" wire:navigate>Payments</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>#{{ $payment->id }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" class="tabular-nums">{!! $kes($payment->amount_cents) !!}</flux:heading>
            <flux:subheading>{{ ($payment->paid_at ?? $payment->created_at)->format('d F Y, g:i A') }}</flux:subheading>
        </div>
        <flux:badge size="lg" :color="$payment->status->badgeColor()">{{ $payment->status->label() }}</flux:badge>
    </div>

    <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start">

        <div class="min-w-0 flex-1 space-y-6">
            {{-- Details --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Transaction details</flux:heading>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->details() as $row)
                        <div class="grid grid-cols-[1fr_1.4fr] gap-4 px-6 py-3 text-sm">
                            <span class="text-zinc-500">{{ $row['label'] }}</span>
                            <span class="font-medium break-all dark:text-white">{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </flux:card>

            {{-- Raw payload --}}
            @if ($payment->payload)
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Gateway payload</flux:heading>
                    </div>
                    <pre class="overflow-x-auto px-6 py-4 text-xs text-zinc-600 dark:text-zinc-300">{{ json_encode($payment->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </flux:card>
            @endif
        </div>

        {{-- Order sidebar --}}
        <aside class="w-full shrink-0 lg:w-80">
            <flux:card>
                <flux:heading size="sm">Order</flux:heading>
                @if ($payment->order)
                    <div class="mt-3 space-y-2 text-sm">
                        <a href="{{ route('admin.orders.show', $payment->order) }}" wire:navigate
                            class="font-mono font-medium hover:text-brand-500 dark:text-white">
                            {{ $payment->order->order_number }}
                        </a>
                        <div class="flex items-center justify-between text-zinc-500">
                            <span>Order total</span>
                            <span class="tabular-nums">{!! $kes($payment->order->total_cents) !!}</span>
                        </div>
                        <flux:badge size="sm" inset="top bottom" :color="$payment->order->status->badgeColor()">
                            {{ $payment->order->status->label() }}
                        </flux:badge>
                    </div>

                    @if ($payment->order->user)
                        <flux:separator class="my-4" />
                        <flux:heading size="sm" class="text-zinc-500">Customer</flux:heading>
                        <div class="mt-2 flex items-center gap-3">
                            <flux:avatar :name="$payment->order->user->name" :initials="$payment->order->user->initials()" size="sm" />
                            <div class="min-w-0">
                                <a href="{{ route('admin.customers.show', $payment->order->user) }}" wire:navigate
                                    class="block truncate text-sm font-medium hover:text-brand-500 dark:text-white">
                                    {{ $payment->order->user->name }}
                                </a>
                                <div class="truncate text-xs text-zinc-500">{{ $payment->order->user->email }}</div>
                            </div>
                        </div>
                    @endif
                @else
                    <flux:text class="mt-3" size="sm">No order linked.</flux:text>
                @endif
            </flux:card>
        </aside>
    </div>
</div>
