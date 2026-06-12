<?php

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\RefundService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Payment — Admin')] class extends Component {
    #[Locked]
    public Payment $payment;

    public string $refundAmount = '';

    public string $refundReason = '';

    public bool $showRefundModal = false;

    public function mount(Payment $payment): void
    {
        $this->payment = $payment->load('order.user');
        $this->refundAmount = number_format($this->remainingRefundableCents() / 100, 2, '.', '');
    }

    /** Cents still available to refund on this payment. */
    public function remainingRefundableCents(): int
    {
        return max(0, (int) $this->payment->amount_cents - (int) $this->payment->refund_cents);
    }

    /** Whether a refund can be issued against this payment right now. */
    public function canRefund(): bool
    {
        return $this->payment->status === PaymentStatus::SUCCESS
            && $this->remainingRefundableCents() > 0
            && (bool) auth()->user()?->can('orders.manage');
    }

    public function refund(): void
    {
        abort_unless(auth()->user()?->can('orders.manage'), 403);

        $this->validate([
            'refundAmount' => ['required', 'numeric', 'min:0.01'],
            'refundReason' => ['nullable', 'string', 'max:500'],
        ]);

        $cents = (int) round(((float) $this->refundAmount) * 100);

        try {
            app(RefundService::class)->refund($this->payment, $cents, $this->refundReason ?: null, auth()->id());
        } catch (\InvalidArgumentException $e) {
            $this->addError('refundAmount', $e->getMessage());

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('refundAmount', 'The refund could not be completed at the payment gateway. Check the gateway dashboard and try again.');

            return;
        }

        $this->payment->refresh()->load('order.user');
        $this->reset('refundReason');
        $this->refundAmount = number_format($this->remainingRefundableCents() / 100, 2, '.', '');
        $this->showRefundModal = false;

        Flux::toast(heading: 'Refund processed', text: 'The customer has been notified.', variant: 'success');
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

<div>
    @push('breadcrumbs')
<flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.payments.index')" wire:navigate>Payments</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>#{{ $payment->id }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
@endpush

    <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" class="tabular-nums">{!! money($payment->amount_cents) !!}</flux:heading>
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

            {{-- Refund --}}
            @if ($payment->refund_cents > 0 || $this->canRefund())
                <flux:card class="p-0 overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Refund</flux:heading>
                        @if ($payment->refund_cents > 0)
                            <flux:badge size="sm" color="purple">
                                {{ money($payment->refund_cents) }} refunded
                            </flux:badge>
                        @endif
                    </div>
                    <div class="space-y-4 p-6">
                        @if ($payment->refund_cents > 0)
                            <div class="grid grid-cols-[1fr_1.4fr] gap-4 text-sm">
                                <span class="text-zinc-500">Refunded on</span>
                                <span class="font-medium dark:text-white">{{ $payment->refunded_at?->format('d F Y, g:i A') }}</span>
                            </div>
                        @endif

                        @if ($this->canRefund())
                            <p class="text-sm text-zinc-500">
                                {!! money($this->remainingRefundableCents()) !!} is available to refund.
                                @if ($payment->provider === 'mpesa')
                                    <span class="text-amber-600 dark:text-amber-400">M-Pesa reversals are processed manually via Safaricom — this records the refund and notifies the customer.</span>
                                @endif
                            </p>
                            <flux:button variant="danger" icon="receipt-refund" wire:click="$set('showRefundModal', true)">
                                Issue refund
                            </flux:button>
                        @elseif ($payment->refund_cents >= $payment->amount_cents)
                            <p class="text-sm text-zinc-500">This payment has been fully refunded.</p>
                        @endif
                    </div>
                </flux:card>
            @endif

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
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Order</flux:heading>
                </div>
                <div class="p-6">
                @if ($payment->order)
                    <div class="space-y-2 text-sm">
                        <a href="{{ route('admin.orders.show', $payment->order) }}" wire:navigate
                            class="font-mono font-medium hover:text-brand-500 dark:text-white">
                            {{ $payment->order->order_number }}
                        </a>
                        <div class="flex items-center justify-between text-zinc-500">
                            <span>Order total</span>
                            <span class="tabular-nums">{!! money($payment->order->total_cents) !!}</span>
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
                    <flux:text size="sm">No order linked.</flux:text>
                @endif
                </div>
            </flux:card>
        </aside>
    </div>

    {{-- Refund modal --}}
    <flux:modal wire:model.self="showRefundModal" class="md:w-[440px]">
        <form wire:submit="refund" class="space-y-5">
            <div>
                <flux:heading size="lg">Issue a refund</flux:heading>
                <flux:subheading>
                    Refunding payment for order
                    <span class="font-mono">{{ $payment->order?->order_number }}</span>.
                    @if ($payment->provider === 'stripe')
                        This reverses the charge through Stripe immediately.
                    @else
                        This records the refund and notifies the customer — reverse the M-Pesa transaction manually via Safaricom.
                    @endif
                </flux:subheading>
            </div>

            <flux:input
                wire:model="refundAmount"
                type="number" step="0.01" min="0.01"
                label="Amount (KES)"
                description="Up to {{ money($this->remainingRefundableCents()) }} available." />

            <flux:textarea wire:model="refundReason" label="Reason (optional)" rows="3"
                placeholder="Shown to the customer in the refund email." />

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="ghost" wire:click="$set('showRefundModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="danger" icon="receipt-refund"
                    wire:loading.attr="disabled" wire:target="refund">
                    Refund {{ $refundAmount !== '' ? 'KES '.number_format((float) $refundAmount, 2) : '' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
