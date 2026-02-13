<?php
use App\Models\Payment;
use Livewire\Component;
use Livewire\Attributes\{Title, Computed};
use Illuminate\Support\Facades\DB;

new class extends Component {
    public Payment $payment;

    public $showRefundModal = false;
    public $refundAmount = 0;
    public $refundReason = '';

    public function mount(Payment $payment)
    {
        $this->payment = $payment->load(['order', 'order.user', 'order.items']);
        $this->refundAmount = $this->payment->amount;
    }

    #[Computed]
    public function title()
    {
        return 'Payment Details';
    }

    public function openRefundModal()
    {
        if ($this->payment->status !== 'completed') {
            session()->flash('error', 'Only completed payments can be refunded.');
            return;
        }

        $this->refundAmount = $this->payment->amount;
        $this->refundReason = '';
        $this->showRefundModal = true;
    }

    public function processRefund()
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
                $this->payment->order->statusHistory()->create([
                    'from_status' => $this->payment->order->status,
                    'to_status' => 'cancelled',
                    'changed_by' => auth()->id(),
                    'notes' => "Order cancelled due to refund: {$this->refundReason}",
                ]);
            }

            DB::commit();

            $this->showRefundModal = false;
            session()->flash('status', 'Refund processed successfully.');

            $this->payment->refresh();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to process refund: ' . $e->getMessage());
        }
    }
};
?>

<div>
    <flux:header>
        <flux:heading size="xl">{{ $this->title }}</flux:heading>

        <flux:button variant="ghost" href="{{ route('admin.payments') }}" icon="arrow-left" wire:navigate>
            Back to Payments
        </flux:button>
    </flux:header>

    {{-- Flash Messages --}}
    @if (session('status'))
        <flux:callout variant="success" class="mb-6">
            {{ session('status') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" class="mb-6">
            {{ session('error') }}
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Payment Details Card --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Payment Information</flux:heading>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <div class="text-xs text-zinc-500 mb-1">Transaction ID</div>
                        <div class="font-mono text-sm text-zinc-800 dark:text-white break-all">
                            {{ $payment->transaction_id ?? 'N/A' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-zinc-500 mb-1">Amount</div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ format_currency($payment->amount) }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-zinc-500 mb-1">Status</div>
                        <flux:badge size="lg" variant="flat"
                            :color="match($payment->status) {
                                                                                                                                                                                                                                    'pending' => 'amber',
                                                                                                                                                                                                                                    'processing' => 'blue',
                                                                                                                                                                                                                                    'completed' => 'green',
                                                                                                                                                                                                                                    'failed' => 'red',
                                                                                                                                                                                                                                    'refunded' => 'purple',
                                                                                                                                                                                                                                    default => 'gray',
                                                                                                                                                                                                                                }">
                            {{ ucfirst($payment->status) }}
                        </flux:badge>
                    </div>

                    <div>
                        <div class="text-xs text-zinc-500 mb-1">Gateway</div>
                        <div class="text-zinc-800 dark:text-white">
                            {{ ucfirst($payment->gateway) }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-zinc-500 mb-1">Currency</div>
                        <div class="text-zinc-800 dark:text-white">
                            {{ strtoupper($payment->currency) }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-zinc-500 mb-1">Date</div>
                        <div class="text-zinc-800 dark:text-white">
                            {{ $payment->created_at?->format('M d, Y h:i A') }}
                        </div>
                    </div>

                    @if ($payment->card_brand && $payment->card_last4)
                        <div>
                            <div class="text-xs text-zinc-500 mb-1">Payment Method</div>
                            <div class="text-zinc-800 dark:text-white">
                                {{ ucfirst($payment->card_brand) }} •••• {{ $payment->card_last4 }}
                            </div>
                        </div>
                    @endif

                    @if ($payment->payment_method_token)
                        <div>
                            <div class="text-xs text-zinc-500 mb-1">Payment Token</div>
                            <div class="font-mono text-xs text-zinc-600 break-all">
                                {{ Str::limit($payment->payment_method_token, 30) }}
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Refund Information (if refunded) --}}
            @if ($payment->status === 'refunded' && isset($payment->meta['refund']))
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Refund Information</flux:heading>

                    <div class="space-y-4">
                        <div>
                            <div class="text-xs text-zinc-500 mb-1">Refund Amount</div>
                            <div class="text-xl font-bold text-red-600">
                                {{ format_currency($payment->meta['refund']['amount']) }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-zinc-500 mb-1">Refund Reason</div>
                            <div class="text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $payment->meta['refund']['reason'] }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-zinc-500 mb-1">Refunded At</div>
                            <div class="text-sm text-zinc-700">
                                {{ \Carbon\Carbon::parse($payment->meta['refund']['refunded_at'])->format('M d, Y h:i A') }}
                            </div>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- Related Order --}}
            @if ($payment->order)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Related Order</flux:heading>

                    <div class="flex items-center justify-between">
                        <div>
                            <a href="{{ route('admin.orders.show', $payment->order) }}" wire:navigate
                                class="font-medium text-blue-600 hover:text-blue-800 text-lg">
                                Order #{{ $payment->order->reference }}
                            </a>
                            <div class="text-sm text-zinc-600 mt-1">
                                {{ $payment->order->items->count() }}
                                {{ Str::plural('item', $payment->order->items->count()) }}
                                • {{ $payment->order->placed_at?->format('M d, Y') }}
                            </div>
                        </div>

                        <flux:badge size="sm" variant="flat"
                            :color="match($payment->order->status) {
                                                                                                                                                                                                                                    'pending' => 'amber',
                                                                                                                                                                                                                                    'processing' => 'blue',
                                                                                                                                                                                                                                    'shipped' => 'indigo',
                                                                                                                                                                                                                                    'delivered' => 'green',
                                                                                                                                                                                                                                    'cancelled' => 'red',
                                                                                                                                                                                                                                    default => 'gray',
                                                                                                                                                                                                                                }">
                            {{ ucfirst($payment->order->status) }}
                        </flux:badge>
                    </div>

                    <flux:separator class="my-4" />

                    <div class="space-y-3">
                        @foreach ($payment->order->items->take(3) as $item)
                            <div class="flex items-center gap-3 text-sm">
                                <div class="w-10 h-10 rounded border bg-zinc-50 overflow-hidden flex-shrink-0">
                                    @if ($item->product?->image_path)
                                        <img src="{{ $item->product->image_url }}" class="object-cover w-full h-full">
                                    @else
                                        <flux:icon name="photo" class="w-full h-full p-2 text-zinc-300" />
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-zinc-800 dark:text-white">
                                        {{ $item->product_name }}
                                    </div>
                                    <div class="text-xs text-zinc-500">Qty: {{ $item->quantity }}</div>
                                </div>
                            </div>
                        @endforeach

                        @if ($payment->order->items->count() > 3)
                            <div class="text-sm text-zinc-500 text-center pt-2">
                                +{{ $payment->order->items->count() - 3 }} more items
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif

            {{-- Meta Data (if exists) --}}
            @if ($payment->meta && count($payment->meta) > 0)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Additional Data</flux:heading>

                    <pre class="text-xs bg-zinc-50 dark:bg-zinc-900 p-4 rounded overflow-auto max-h-96">{{ json_encode($payment->meta, JSON_PRETTY_PRINT) }}</pre>
                </flux:card>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Actions</flux:heading>

                <div class="space-y-3">
                    @if ($payment->status === 'completed')
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
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Customer</flux:heading>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="font-medium text-zinc-800 dark:text-white">
                                {{ $payment->order->user->name }}
                            </div>
                            <div class="text-zinc-600">{{ $payment->order->user->email }}</div>
                            @if ($payment->order->user->phone)
                                <div class="text-zinc-600">{{ $payment->order->user->phone }}</div>
                            @endif
                        </div>

                        <flux:separator />

                        <div>
                            <div class="text-xs text-zinc-500 mb-1">Total Orders</div>
                            <div class="text-zinc-700">
                                {{ $payment->order->user->orders()->count() }}
                            </div>
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
