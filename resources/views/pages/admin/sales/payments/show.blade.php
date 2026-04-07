<?php
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Livewire\Component;
use Livewire\Attributes\{Title, Computed};
use Illuminate\Support\Facades\DB;

new class extends Component {
    public Payment $payment;

    public $showRefundModal = false;
    public $refundAmount = 0;
    public $refundReason = '';

    public function mount(Payment $payment): void
    {
        $this->payment = $payment->load(['order' => ['user', 'items']]);
        $this->refundAmount = $this->payment->amount;
    }

    public function openRefundModal(): void
    {
        if ($this->payment->status !== PaymentStatus::PAID) {
            $this->dispatch('notify', title: 'Action Not Allowed', variant: 'danger', message: 'Only completed payments can be refunded.');
            return;
        }

        $this->refundAmount = $this->payment->amount;
        $this->refundReason = '';
        $this->showRefundModal = true;
    }

    public function processRefund(): void
    {
        $this->validate([
            'refundAmount' => 'required|numeric|min:0.01|max:' . $this->payment->amount,
            'refundReason' => 'required|string|min:10',
        ]);

        DB::beginTransaction();
        try {
            // Update payment status
            $this->payment->update([
                'status' => 'refunded',
                'meta' => array_merge($this->payment->meta ?? [], [
                    'refund' => [
                        'amount' => $this->refundAmount,
                        'reason' => $this->refundReason,
                        'refunded_at' => now()->toDateTimeString(),
                        'refunded_by' => auth()->id(),
                    ],
                ]),
            ]);

            // Update order status if needed
            if ($this->payment->order) {
                $this->payment->order->update([
                    'status' => 'cancelled',
                ]);

                // Add to status history
                $this->payment->order->statusHistor()->create([
                    'from_status' => $this->payment->order->status,
                    'to_status' => 'cancelled',
                    'changed_by' => auth()->id(),
                    'notes' => "Order cancelled due to refund: {$this->refundReason}",
                ]);
            }

            DB::commit();

            $this->showRefundModal = false;
            $this->dispatch('notify', title: 'Refund Processed', variant: 'success', message: 'Refund processed successfully.');

            $this->payment->refresh();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', title: 'Refund Failed', variant: 'danger', message: 'Failed to process refund: ' . $e->getMessage());
        }
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.payments.index')" wire:navigate>Transactions</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Details</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6">
        <flux:heading size="xl">Payment Details</flux:heading>
        <flux:subheading>View transaction information and manage refunds.</flux:subheading>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Payment Details Card --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b dark:border-zinc-600">
                    <flux:heading>Payment Information</flux:heading>
                </div>

                <div class="grid grid-cols-2 gap-6 p-5">
                    <div>
                        <flux:subheading class="text-xs! mb-1">Transaction ID</flux:subheading>
                        <flux:text class="font-mono text-sm break-all">
                            {{ $payment->transaction_id ?? 'N/A' }}
                        </flux:text>
                    </div>

                    <div>
                        <flux:subheading class="text-xs! mb-1">Amount</flux:subheading>
                        <flux:heading size="xl" class="font-bold!">
                            {{ format_currency($payment->amount) }}
                        </flux:heading>
                    </div>

                    <div>
                        <flux:subheading class="text-xs! mb-1">Status</flux:subheading>
                        <flux:badge size="lg" variant="flat" :color="$payment->status->color()">
                            {{ $payment->status->label() }}
                        </flux:badge>
                    </div>

                    <div>
                        <flux:subheading class="text-xs! mb-1">Gateway</flux:subheading>
                        <flux:text>
                            {{ ucfirst($payment->gateway) }}
                        </flux:text>
                    </div>

                    <div>
                        <flux:subheading class="text-xs! mb-1">Currency</flux:subheading>
                        <flux:text>
                            {{ strtoupper($payment->currency) }}
                        </flux:text>
                    </div>

                    <div>
                        <flux:subheading class="text-xs! mb-1">Date</flux:subheading>
                        <flux:text>
                            {{ $payment->created_at?->format('M d, Y h:i A') }}
                        </flux:text>
                    </div>

                    @if ($payment->card_brand && $payment->card_last4)
                        <div>
                            <flux:subheading class="text-xs! mb-1">Payment Method</flux:subheading>
                            <flux:text>
                                {{ ucfirst($payment->card_brand) }} •••• {{ $payment->card_last4 }}
                            </flux:text>
                        </div>
                    @endif

                    @if ($payment->payment_method_token)
                        <div>
                            <flux:subheading class="text-xs! mb-1">Payment Token</flux:subheading>
                            <flux:text class="font-mono text-xs break-all">
                                {{ Str::limit($payment->payment_method_token, 30) }}
                            </flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Refund Information (if refunded) --}}
            @if ($payment->status === PaymentStatus::REFUNDED && isset($payment->meta['refund']))
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b dark:border-zinc-600">
                        <flux:heading size="lg" class="mb-4">Refund Information</flux:heading>
                    </div>

                    <div class="space-y-4 p-5">
                        <div>
                            <flux:subheading class="text-xs! mb-1">Refund Amount</flux:subheading>
                            <flux:heading size="xl" class="font-bold! text-red-600">
                                {{ format_currency($payment->meta['refund']['amount']) }}
                            </flux:heading>
                        </div>

                        <div>
                            <flux:subheading class="text-xs! mb-1">Refund Reason</flux:subheading>
                            <flux:text class="text-sm">
                                {{ $payment->meta['refund']['reason'] }}
                            </flux:text>
                        </div>

                        <div>
                            <flux:subheading class="text-xs! mb-1">Refunded At</flux:subheading>
                            <flux:text class="text-sm">
                                {{ \Carbon\Carbon::parse($payment->meta['refund']['refunded_at'])->format('M d, Y h:i A') }}
                            </flux:text>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- Related Order --}}
            @if ($payment->order)
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b dark:border-zinc-600">
                        <flux:heading size="lg" class="mb-4">Related Order</flux:heading>

                        <div class="flex items-center justify-between">
                            <div>
                                <flux:link href="{{ route('admin.orders.show', $payment->order) }}" wire:navigate
                                    class="font-medium text-lg">
                                    Order #{{ $payment->order->reference }}
                                </flux:link>
                                <flux:subheading class="text-sm mt-1">
                                    {{ $payment->order->items->count() }}
                                    {{ Str::plural('item', $payment->order->items->count()) }}
                                    • {{ $payment->order->placed_at?->format('M d, Y') }}
                                </flux:subheading>
                            </div>

                            <flux:badge size="sm" variant="flat" :color="$payment->order->status->color()">
                                {{ $payment->order->status->label() }}
                            </flux:badge>
                        </div>
                    </div>

                    <div class="space-y-3 p-5">
                        @foreach ($payment->order->items->take(3) as $item)
                            <div class="flex items-center gap-3 text-sm">
                                <div class="w-10 h-10 rounded border bg-zinc-50 overflow-hidden shrink-0">
                                    @if ($item->product?->image_path)
                                        <img src="{{ $item->product->image_url }}" class="object-cover w-full h-full">
                                    @else
                                        <flux:icon name="photo" class="w-full h-full p-2 text-zinc-300" />
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <flux:heading size="sm" class="font-medium!">
                                        {{ $item->product_name }}
                                    </flux:heading>
                                    <flux:subheading class="text-xs!">Qty: {{ $item->quantity }}</flux:subheading>
                                </div>
                            </div>
                        @endforeach

                        @if ($payment->order->items->count() > 3)
                            <flux:subheading class="text-sm! text-center pt-2">
                                +{{ $payment->order->items->count() - 3 }} more items
                            </flux:subheading>
                        @endif
                    </div>
                </flux:card>
            @endif

            {{-- Meta Data (if exists) --}}
            @if ($payment->meta && count($payment->meta) > 0)
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b dark:border-zinc-600">
                        <flux:heading>Additional Data</flux:heading>
                    </div>

                    <div class="p-5">
                        <pre class="text-xs dark:text-zinc-100 bg-zinc-50 dark:bg-zinc-900 p-4 rounded overflow-auto max-h-96">{{ json_encode($payment->meta, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </flux:card>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b dark:border-zinc-600">
                    <flux:heading>Actions</flux:heading>
                </div>

                <div class="space-y-3 p-5">
                    @if ($payment->status->value === 'paid')
                        <flux:button wire:click="openRefundModal" variant="danger" icon="arrow-uturn-left"
                            class="w-full">
                            Process Refund
                        </flux:button>
                    @endif

                    @if ($payment->order)
                        <flux:button href="{{ route('admin.orders.show', $payment->order) }}" variant="primary"
                            icon="eye" wire:navigate class="w-full">
                            View Order
                        </flux:button>
                    @endif
                </div>
            </flux:card>

            {{-- Customer Info --}}
            @if ($payment->order?->user)
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b dark:border-zinc-600">
                        <flux:heading>Customer</flux:heading>
                    </div>

                    <div class="space-y-3 text-sm p-5">
                        <div>
                            <flux:heading size="sm" class="font-medium!">
                                {{ $payment->order->user->name }}
                            </flux:heading>
                            <flux:subheading>{{ $payment->order->user->email }}</flux:subheading>
                            @if ($payment->order->user->phone)
                                <flux:subheading>{{ $payment->order->user->phone }}</flux:subheading>
                            @endif
                        </div>

                        <flux:separator />

                        <div>
                            <flux:subheading class="text-xs! mb-1">Total Orders</flux:subheading>
                            <flux:text>
                                {{ $payment->order->user->orders()->count() }}
                            </flux:text>
                        </div>
                    </div>
                </flux:card>
            @endif
        </div>
    </div>

    {{-- Refund Modal --}}
    <flux:modal wire:model="showRefundModal" class="max-w-md">
        <flux:heading size="lg" class="mb-4">Process Refund</flux:heading>

        <form wire:submit="processRefund" class="space-y-4">
            <flux:field>
                <flux:label>Refund Amount</flux:label>
                <flux:input wire:model="refundAmount" type="number" step="0.01" max="{{ $payment->amount }}"
                    variant="filled" />
                <flux:error name="refundAmount" />
                <flux:description>Maximum: {{ format_currency($payment->amount) }}</flux:description>
            </flux:field>

            <flux:field>
                <flux:label>Refund Reason</flux:label>
                <flux:textarea wire:model="refundReason" rows="4"
                    placeholder="Provide a reason for this refund..." variant="filled" />
                <flux:error name="refundReason" />
            </flux:field>

            <flux:callout variant="warning">
                This action will refund the payment and cancel the associated order. This cannot be undone.
            </flux:callout>

            <div class="flex gap-2 justify-end">
                <flux:button type="button" variant="ghost" wire:click="$set('showRefundModal', false)">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="danger">
                    Process Refund
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
