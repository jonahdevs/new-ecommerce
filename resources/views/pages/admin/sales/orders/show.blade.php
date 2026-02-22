<?php
use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\{Title, Computed, On};

new class extends Component {
    public Order $order;

    public $showStatusModal = false;
    public $newStatus = '';

    public function mount(Order $order)
    {
        $this->order = $order->load(['user', 'payment', 'items.product', 'statusHistory']);
    }

    #[Computed]
    public function title()
    {
        return "Order #{$this->order->reference}";
    }

    public function openStatusModal()
    {
        $this->newStatus = $this->order->status;
        $this->showStatusModal = true;
    }

    public function updateStatus()
    {
        $this->validate([
            'newStatus' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $oldStatus = $this->order->status;

        $this->order->update([
            'status' => $this->newStatus,
        ]);

        // Record status change in history
        $this->order->statusHistory()->create([
            'from_status' => $oldStatus,
            'to_status' => $this->newStatus,
            'changed_by' => auth()->id(),
            'notes' => 'Status updated by admin',
        ]);

        $this->showStatusModal = false;

        session()->flash('status', 'Order status updated successfully.');

        $this->order->refresh();
    }

    #[Computed]
    public function statusOptions()
    {
        return [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.orders.index')" wire:navigate>Orders</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Details</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">{{ $this->title }}</flux:heading>

    {{-- Status Flash Message --}}
    @if (session('status'))
        <flux:callout variant="success" class="mb-6">
            {{ session('status') }}
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Order Items --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Order Items</flux:heading>

                <div class="space-y-4">
                    @foreach ($order->items as $item)
                        <div class="flex items-center gap-4 pb-4 border-b last:border-b-0">
                            <div class="w-16 h-16 rounded border bg-zinc-50 overflow-hidden flex-shrink-0">
                                @if ($item->product?->image_path)
                                    <img src="{{ $item->product->image_url }}" class="object-cover w-full h-full"
                                        alt="{{ $item->product_name }}">
                                @else
                                    <flux:icon name="photo" class="w-full h-full p-3 text-zinc-300" />
                                @endif
                            </div>

                            <div class="flex-1">
                                <div class="font-medium text-zinc-800 dark:text-white">
                                    {{ $item->product_name }}
                                </div>
                                @if ($item->product_sku)
                                    <div class="text-xs text-zinc-500">SKU: {{ $item->product_sku }}</div>
                                @endif
                                <div class="text-sm text-zinc-600 mt-1">
                                    Qty: {{ $item->quantity }} × {{ format_currency($item->price_cents / 100) }}
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="font-semibold text-zinc-900 dark:text-white">
                                    {{ format_currency(($item->price_cents * $item->quantity) / 100) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Order Summary --}}
                <div class="mt-6 pt-4 border-t space-y-2">
                    <div class="flex justify-between text-sm text-zinc-600">
                        <span>Subtotal</span>
                        <span>{{ format_currency($order->subtotal) }}</span>
                    </div>

                    @if ($order->discount_cents > 0)
                        <div class="flex justify-between text-sm text-zinc-600">
                            <span>Discount</span>
                            <span class="text-red-600">-{{ format_currency($order->discount) }}</span>
                        </div>
                    @endif

                    @if ($order->shipping_cents > 0)
                        <div class="flex justify-between text-sm text-zinc-600">
                            <span>Shipping</span>
                            <span>{{ format_currency($order->shipping) }}</span>
                        </div>
                    @endif

                    @if ($order->tax_cents > 0)
                        <div class="flex justify-between text-sm text-zinc-600">
                            <span>Tax</span>
                            <span>{{ format_currency($order->tax_cents / 100) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between text-lg font-bold text-zinc-900 dark:text-white pt-2 border-t">
                        <span>Total</span>
                        <span>{{ format_currency($order->total) }}</span>
                    </div>
                </div>
            </flux:card>

            {{-- Shipping/Pickup Address --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">
                    @if ($order->is_pickup)
                        Pickup Information
                    @else
                        Shipping Address
                    @endif
                </flux:heading>

                @if ($order->is_pickup)
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2 text-blue-600">
                            <flux:icon name="building-storefront" class="w-5 h-5" />
                            <span class="font-medium">Store Pickup</span>
                        </div>
                        @if ($order->pickup_ready_at)
                            <div class="text-zinc-600">
                                <strong>Ready for pickup:</strong>
                                {{ $order->pickup_ready_at->format('M d, Y h:i A') }}
                            </div>
                        @endif
                        @if ($order->pickup_collected_at)
                            <div class="text-green-600">
                                <strong>Collected:</strong> {{ $order->pickup_collected_at->format('M d, Y h:i A') }}
                            </div>
                        @endif
                    </div>
                @else
                    @if ($order->shipping_address)
                        <div class="text-sm text-zinc-700 dark:text-zinc-300">
                            @if (isset($order->shipping_address['name']))
                                <div class="font-medium">{{ $order->shipping_address['name'] }}</div>
                            @endif
                            @if (isset($order->shipping_address['address']))
                                <div>{{ $order->shipping_address['address'] }}</div>
                            @endif
                            @if (isset($order->shipping_address['city']) || isset($order->shipping_address['postal_code']))
                                <div>
                                    {{ $order->shipping_address['city'] ?? '' }}
                                    {{ $order->shipping_address['postal_code'] ?? '' }}
                                </div>
                            @endif
                            @if (isset($order->shipping_address['country']))
                                <div>{{ $order->shipping_address['country'] }}</div>
                            @endif
                            @if (isset($order->shipping_address['phone']))
                                <div class="mt-2">
                                    <strong>Phone:</strong> {{ $order->shipping_address['phone'] }}
                                </div>
                            @endif
                        </div>

                        @if ($order->estimated_delivery_from && $order->estimated_delivery_to)
                            <div class="mt-4 pt-4 border-t text-sm">
                                <strong>Estimated Delivery:</strong>
                                {{ $order->estimated_delivery_from->format('M d') }} -
                                {{ $order->estimated_delivery_to->format('M d, Y') }}
                            </div>
                        @endif

                        @if ($order->actual_delivery_date)
                            <div class="mt-2 text-sm text-green-600">
                                <strong>Delivered on:</strong> {{ $order->actual_delivery_date->format('M d, Y') }}
                            </div>
                        @endif
                    @else
                        <div class="text-sm text-zinc-500">No shipping address provided</div>
                    @endif
                @endif
            </flux:card>

            {{-- Status History --}}
            @if ($order->statusHistory->count() > 0)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Status History</flux:heading>

                    <div class="space-y-3">
                        @foreach ($order->statusHistory()->latest()->get() as $history)
                            <div class="flex items-start gap-3 text-sm">
                                <div class="w-2 h-2 rounded-full bg-zinc-400 mt-2"></div>
                                <div class="flex-1">
                                    <div class="font-medium text-zinc-800 dark:text-white">
                                        {{ ucfirst($history->from_status) }} → {{ ucfirst($history->to_status) }}
                                    </div>
                                    <div class="text-xs text-zinc-500">
                                        {{ $history->created_at->format('M d, Y h:i A') }}
                                    </div>
                                    @if ($history->notes)
                                        <div class="text-xs text-zinc-600 mt-1">{{ $history->notes }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Customer Info --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Customer</flux:heading>

                <div class="space-y-3 text-sm">
                    <div>
                        <div class="font-medium text-zinc-800 dark:text-white">{{ $order->user->name }}</div>
                        <div class="text-zinc-600">{{ $order->user->email }}</div>
                        @if ($order->user->phone)
                            <div class="text-zinc-600">{{ $order->user->phone }}</div>
                        @endif
                    </div>

                    <flux:separator />

                    <div>
                        <div class="text-xs text-zinc-500 mb-1">Customer Since</div>
                        <div class="text-zinc-700">{{ $order->user->created_at->format('M d, Y') }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-zinc-500 mb-1">Total Orders</div>
                        <div class="text-zinc-700">{{ $order->user->orders()->count() }}</div>
                    </div>
                </div>
            </flux:card>

            {{-- Order Status --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Order Status</flux:heading>

                <div class="space-y-4">
                    <div>
                        <div class="text-xs text-zinc-500 mb-2">Current Status</div>
                        <flux:badge size="lg" variant="flat"
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
                    </div>

                    <flux:button wire:click="openStatusModal" variant="primary" class="w-full">
                        Update Status
                    </flux:button>
                </div>
            </flux:card>

            {{-- Payment Info --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Payment</flux:heading>

                @if ($order->payment)
                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-xs text-zinc-500 mb-1">Status</div>
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
                        </div>

                        <div>
                            <div class="text-xs text-zinc-500 mb-1">Amount</div>
                            <div class="font-semibold text-zinc-900 dark:text-white">
                                {{ format_currency($order->payment->amount) }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-zinc-500 mb-1">Gateway</div>
                            <div class="text-zinc-700">{{ ucfirst($order->payment->gateway) }}</div>
                        </div>

                        @if ($order->payment->transaction_id)
                            <div>
                                <div class="text-xs text-zinc-500 mb-1">Transaction ID</div>
                                <div class="text-zinc-700 font-mono text-xs break-all">
                                    {{ $order->payment->transaction_id }}
                                </div>
                            </div>
                        @endif

                        @if ($order->payment->card_brand && $order->payment->card_last4)
                            <div>
                                <div class="text-xs text-zinc-500 mb-1">Payment Method</div>
                                <div class="text-zinc-700">
                                    {{ ucfirst($order->payment->card_brand) }} •••• {{ $order->payment->card_last4 }}
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-sm text-zinc-500">No payment information available</div>
                @endif
            </flux:card>

            {{-- Order Details --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Order Details</flux:heading>

                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-xs text-zinc-500 mb-1">Order Date</div>
                        <div class="text-zinc-700">
                            {{ $order->placed_at?->format('M d, Y h:i A') ?? 'N/A' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-zinc-500 mb-1">Currency</div>
                        <div class="text-zinc-700">{{ strtoupper($order->currency) }}</div>
                    </div>

                    @if ($order->warehouse_id)
                        <div>
                            <div class="text-xs text-zinc-500 mb-1">Warehouse</div>
                            <div class="text-zinc-700">Warehouse #{{ $order->warehouse_id }}</div>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Status Update Modal --}}
    <flux:modal wire:model="showStatusModal" class="max-w-md">
        <flux:heading size="lg" class="mb-4">Update Order Status</flux:heading>

        <form wire:submit="updateStatus" class="space-y-4">
            <flux:field>
                <flux:label>New Status</flux:label>
                <flux:select wire:model="newStatus">
                    @foreach ($this->statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="flex gap-2 justify-end">
                <flux:button type="button" variant="ghost" wire:click="$set('showStatusModal', false)">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Update Status
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
