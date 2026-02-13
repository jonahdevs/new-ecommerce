<?php
use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};

new #[Title('Orders')] class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';

    #[Computed]
    public function orders()
    {
        return Order::query()
            ->with(['user', 'payment', 'items'])
            ->when($this->search, function ($q) {
                $q->where('reference', 'like', "%{$this->search}%")->orWhereHas('user', function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->latest('placed_at')
            ->paginate(15);
    }

    #[Computed]
    public function statusOptions()
    {
        return [
            'all' => 'All Orders',
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];
    }

    #[Computed]
    public function statusCounts()
    {
        return [
            'all' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="mb-1">Orders</flux:heading>
            <flux:subheading>Manage customer orders, track shipments, and process payments.</flux:subheading>
        </div>
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

    {{-- Search --}}
    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
            placeholder="Search by order reference, customer name or email..." class="max-w-md" />
    </div>

    {{-- Orders Table --}}
    <flux:table :paginate="$this->orders">
        <flux:table.columns>
            <flux:table.column>Order</flux:table.column>
            <flux:table.column>Customer</flux:table.column>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column>Items</flux:table.column>
            <flux:table.column>Total</flux:table.column>
            <flux:table.column>Payment</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->orders as $order)
                <flux:table.row :key="$order->id">
                    {{-- Order Reference --}}
                    <flux:table.cell>
                        <div class="font-medium text-zinc-800 dark:text-white">
                            #{{ $order->reference }}
                        </div>
                        @if ($order->is_pickup)
                            <flux:badge size="sm" color="blue" class="mt-1">Pickup</flux:badge>
                        @endif
                    </flux:table.cell>

                    {{-- Customer --}}
                    <flux:table.cell>
                        <div class="font-medium">{{ $order->user->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $order->user->email }}</div>
                    </flux:table.cell>

                    {{-- Date --}}
                    <flux:table.cell>
                        <div>{{ $order->placed_at?->format('M d, Y') ?? 'N/A' }}</div>
                        <div class="text-xs text-zinc-500">{{ $order->placed_at?->format('h:i A') }}</div>
                    </flux:table.cell>

                    {{-- Items Count --}}
                    <flux:table.cell>
                        {{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}
                    </flux:table.cell>

                    {{-- Total --}}
                    <flux:table.cell>
                        <div class="font-medium">{{ format_currency($order->total) }}</div>
                    </flux:table.cell>

                    {{-- Payment Status --}}
                    <flux:table.cell>
                        @if ($order->payment)
                            <flux:badge size="sm" variant="flat"
                                :color="match($order->payment->status) {
                                                                                                                                                                                                                                    'pending' => 'amber',
                                                                                                                                                                                                                                    'processing' => 'blue',
                                                                                                                                                                                                                                    'completed' => 'green',
                                                                                                                                                                                                                                    'failed' => 'red',
                                                                                                                                                                                                                                    'refunded' => 'purple',
                                                                                                                                                                                                                                    default => 'gray',
                                                                                                                                                                                                                                }">
                                {{ ucfirst($order->payment->status) }}
                            </flux:badge>
                        @else
                            <flux:badge size="sm" color="gray">No Payment</flux:badge>
                        @endif
                    </flux:table.cell>

                    {{-- Order Status --}}
                    <flux:table.cell>
                        <flux:badge size="sm" variant="flat"
                            :color="match($order->status) {
                                                                                                                                                                                                        'pending' => 'amber',
                                                                                                                                                                                                        'processing' => 'blue',
                                                                                                                                                                                                        'shipped' => 'indigo',
                                                                                                                                                                                                        'delivered' => 'green',
                                                                                                                                                                                                        'cancelled' => 'red',
                                                                                                                                                                                                        default => 'gray',
                                                                                                                                                                                                    }">
                            {{ ucfirst($order->status) }}
                        </flux:badge>
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell align="end">
                        <flux:button variant="ghost" size="sm" icon="eye"
                            href="{{ route('admin.orders.show', $order) }}" wire:navigate />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
