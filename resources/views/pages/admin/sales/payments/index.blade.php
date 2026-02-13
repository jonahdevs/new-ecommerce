<?php
use App\Models\Payment;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};

new #[Title('Payments')] class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $gatewayFilter = 'all';

    #[Computed]
    public function payments()
    {
        return Payment::query()
            ->with(['order', 'order.user'])
            ->when($this->search, function ($q) {
                $q->where('transaction_id', 'like', "%{$this->search}%")
                    ->orWhereHas('order', function ($query) {
                        $query->where('reference', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('order.user', function ($query) {
                        $query->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%");
                    });
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->gatewayFilter !== 'all', function ($q) {
                $q->where('gateway', $this->gatewayFilter);
            })
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function statusOptions()
    {
        return [
            'all' => 'All Payments',
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];
    }

    #[Computed]
    public function gatewayOptions()
    {
        return [
            'all' => 'All Gateways',
            'pesawise' => 'Pesawise',
            'mpesa' => 'M-Pesa',
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
        ];
    }

    #[Computed]
    public function statusCounts()
    {
        return [
            'all' => Payment::count(),
            'pending' => Payment::where('status', 'pending')->count(),
            'processing' => Payment::where('status', 'processing')->count(),
            'completed' => Payment::where('status', 'completed')->count(),
            'failed' => Payment::where('status', 'failed')->count(),
            'refunded' => Payment::where('status', 'refunded')->count(),
        ];
    }

    #[Computed]
    public function totalRevenue()
    {
        return Payment::where('status', 'completed')->sum('amount_cents') / 100;
    }

    #[Computed]
    public function totalRefunded()
    {
        return Payment::where('status', 'refunded')->sum('amount_cents') / 100;
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="mb-1">Payments</flux:heading>
            <flux:subheading>Monitor payment transactions, track revenue, and manage refunds.</flux:subheading>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <flux:card>
            <div class="text-sm text-zinc-600 mb-1">Total Revenue</div>
            <div class="text-2xl font-bold text-green-600">{{ format_currency($this->totalRevenue) }}</div>
            <div class="text-xs text-zinc-500 mt-1">Completed payments</div>
        </flux:card>

        <flux:card>
            <div class="text-sm text-zinc-600 mb-1">Total Refunded</div>
            <div class="text-2xl font-bold text-red-600">{{ format_currency($this->totalRefunded) }}</div>
            <div class="text-xs text-zinc-500 mt-1">Refunded payments</div>
        </flux:card>

        <flux:card>
            <div class="text-sm text-zinc-600 mb-1">Total Transactions</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->statusCounts['all'] }}</div>
            <div class="text-xs text-zinc-500 mt-1">All time</div>
        </flux:card>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="flex gap-2 mb-6 overflow-x-auto">
        @foreach ($this->statusOptions as $status => $label)
            <flux:button wire:click="$set('statusFilter', '{{ $status }}')"
                variant="{{ $statusFilter === $status ? 'primary' : 'ghost' }}" size="sm">
                {{ $label }}
                <flux:badge size="sm" :color="$statusFilter === $status ? 'white' : 'zinc'">
                    {{ $this->statusCounts[$status] }}
                </flux:badge>
            </flux:button>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-4 mb-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
            placeholder="Search by transaction ID, order reference, or customer..." class="flex-1 max-w-md" />

        <flux:select wire:model.live="gatewayFilter" class="w-48">
            @foreach ($this->gatewayOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </flux:select>
    </div>

    {{-- Payments Table --}}
    <flux:table :paginate="$this->payments">
        <flux:table.columns>
            <flux:table.column>Transaction</flux:table.column>
            <flux:table.column>Order</flux:table.column>
            <flux:table.column>Customer</flux:table.column>
            <flux:table.column>Gateway</flux:table.column>
            <flux:table.column>Payment Method</flux:table.column>
            <flux:table.column>Amount</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->payments as $payment)
                <flux:table.row :key="$payment->id">
                    {{-- Transaction ID --}}
                    <flux:table.cell>
                        <div class="font-mono text-sm text-zinc-800 dark:text-white">
                            {{ Str::limit($payment->transaction_id, 20) ?? 'N/A' }}
                        </div>
                    </flux:table.cell>

                    {{-- Order Reference --}}
                    <flux:table.cell>
                        @if ($payment->order)
                            <a href="{{ route('admin.orders.show', $payment->order) }}" wire:navigate
                                class="font-medium text-blue-600 hover:text-blue-800">
                                #{{ $payment->order->reference }}
                            </a>
                        @else
                            <span class="text-zinc-500">N/A</span>
                        @endif
                    </flux:table.cell>

                    {{-- Customer --}}
                    <flux:table.cell>
                        @if ($payment->order?->user)
                            <div class="font-medium">{{ $payment->order->user->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $payment->order->user->email }}</div>
                        @else
                            <span class="text-zinc-500">N/A</span>
                        @endif
                    </flux:table.cell>

                    {{-- Gateway --}}
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue">
                            {{ ucfirst($payment->gateway) }}
                        </flux:badge>
                    </flux:table.cell>

                    {{-- Payment Method --}}
                    <flux:table.cell>
                        @if ($payment->card_brand && $payment->card_last4)
                            <div class="text-sm">
                                {{ ucfirst($payment->card_brand) }} •••• {{ $payment->card_last4 }}
                            </div>
                        @else
                            <span class="text-zinc-500 text-sm">N/A</span>
                        @endif
                    </flux:table.cell>

                    {{-- Amount --}}
                    <flux:table.cell>
                        <div class="font-semibold text-zinc-900 dark:text-white">
                            {{ format_currency($payment->amount) }}
                        </div>
                    </flux:table.cell>

                    {{-- Status --}}
                    <flux:table.cell>
                        <flux:badge size="sm" variant="flat"
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
                    </flux:table.cell>

                    {{-- Date --}}
                    <flux:table.cell>
                        <div>{{ $payment->created_at->format('M d, Y') }}</div>
                        <div class="text-xs text-zinc-500">{{ $payment->created_at->format('h:i A') }}</div>
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell align="end">
                        <flux:button variant="ghost" size="sm" icon="eye"
                            href="{{ route('admin.payments.show', $payment) }}" wire:navigate />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="9" class="text-center text-zinc-500 py-8">
                        No payments found
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
