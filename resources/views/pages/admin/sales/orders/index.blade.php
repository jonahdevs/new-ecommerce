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
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Orders</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="mb-1">Orders</flux:heading>
            <flux:subheading>Manage customer orders, track shipments, and process payments.</flux:subheading>
        </div>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="flex gap-2 mb-6 overflow-x-auto">
        @foreach ($this->statusOptions as $status => $label)
            <flux:button wire:click="$set('statusFilter', '{{ $status }}')" class="cursor-pointer"
                :loading="false" variant="{{ $statusFilter === $status ? 'primary' : 'ghost' }}" size="sm">
                {{ $label }}
                <flux:badge size="sm" :color="$statusFilter === $status ? 'white' : 'zinc'">
                    {{ $this->statusCounts[$status] }}
                </flux:badge>
            </flux:button>
        @endforeach
    </div>


    {{-- Orders Table --}}
    <flux:card class="p-0 mt-6 **:data-flux-columns:bg-zinc-50">
        {{-- Search --}}
        <div class="flex items-center gap-4 px-5 py-3 border-b">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Search by order reference, customer name or email..." class="max-w-md" />
        </div>

        <flux:table :paginate="$this->orders">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Order</flux:table.column>
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column>Items</flux:table.column>
                <flux:table.column>Total</flux:table.column>
                <flux:table.column>Payment</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->orders as $order)
                    <flux:table.row :key="$order->id">
                        {{-- Order Reference --}}
                        <flux:table.cell class="ps-4!">
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
                                <flux:badge size="sm" variant="flat" :color="$order->payment->status->color()">
                                    {{ $order->payment->status?->label() }}
                                </flux:badge>
                            @else
                                <flux:badge size="sm" color="gray">No Payment</flux:badge>
                            @endif
                        </flux:table.cell>

                        {{-- Order Status --}}
                        <flux:table.cell>
                            <flux:badge size="sm" variant="flat" :color="$order->status->color()">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Actions --}}
                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="eye" icon-variant="outline"
                                href="{{ route('admin.orders.show', $order) }}" wire:navigate />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center py-12">
                            <div class="flex flex-col items-center justify-center text-zinc-500">
                                <flux:icon.inbox class="w-12 h-12 mb-3 text-zinc-400" />
                                <p class="text-lg font-medium">No orders found</p>
                                <p class="text-sm mt-1">Orders will appear here once customers place them.</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
