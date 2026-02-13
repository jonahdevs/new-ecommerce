<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Order;

new #[Layout('layouts.customer')] class extends Component {
    public Order $order;

    public function mount($order)
    {
        // Ensure the order belongs to the authenticated user
        if ($this->order->user_id !== auth()->id()) {
            abort(403);
        }

        // Ensure the order is actually paid
        if (!$this->order->is_paid) {
            return redirect()->route('home')->with('error', 'Order not found or payment pending');
        }
    }

    #[Computed]
    public function orderItems()
    {
        return $this->order->items()->with('product')->get();
    }

    #[Computed]
    public function subtotal()
    {
        return $this->orderItems->sum(fn($item) => $item->price * $item->quantity);
    }
};
?>

<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Success Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Confirmed!</h1>
            <p class="text-gray-600">Thank you for your purchase. We've sent a confirmation email to
                {{ auth()->user()->email }}</p>
        </div>

        {{-- Order Details Card --}}
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="bg-gray-50 px-6 py-4 border-b">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-600">Order Number</p>
                        <p class="text-lg font-semibold text-gray-900">#{{ $order->order_number }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Order Date</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $order->created_at->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- Order Items --}}
            <div class="px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h3>
                <div class="space-y-4">
                    @foreach ($this->orderItems as $item)
                        <div class="flex items-center gap-4">
                            @if ($item->product->image)
                                <img src="{{ $item->product->image }}" alt="{{ $item->product->name }}"
                                    class="w-16 h-16 object-cover rounded-md">
                            @endif
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900">{{ $item->product->name }}</h4>
                                <p class="text-sm text-gray-600">Quantity: {{ $item->quantity }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">KES
                                    {{ number_format($item->price * $item->quantity, 2) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Order Summary --}}
                <div class="border-t mt-6 pt-4 space-y-2">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span>KES {{ number_format($this->subtotal, 2) }}</span>
                    </div>
                    @if ($order->shipping_cost > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span>KES {{ number_format($order->shipping_cost, 2) }}</span>
                        </div>
                    @endif
                    @if ($order->tax > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>Tax</span>
                            <span>KES {{ number_format($order->tax, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t">
                        <span>Total</span>
                        <span>KES {{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Shipping Address --}}
            @if ($order->shipping_address)
                <div class="px-6 py-4 bg-gray-50 border-t">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Shipping Address</h3>
                    <p class="text-sm text-gray-600">
                        {{ $order->shipping_address['name'] ?? '' }}<br>
                        {{ $order->shipping_address['address'] ?? '' }}<br>
                        {{ $order->shipping_address['city'] ?? '' }},
                        {{ $order->shipping_address['postal_code'] ?? '' }}<br>
                        {{ $order->shipping_address['phone'] ?? '' }}
                    </p>
                </div>
            @endif

            {{-- Payment Info --}}
            <div class="px-6 py-4 bg-green-50 border-t">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm font-medium text-green-800">Payment Confirmed</span>
                </div>
                @if ($order->transaction_id)
                    <p class="text-xs text-green-700 mt-1">Transaction ID: {{ $order->transaction_id }}</p>
                @endif
            </div>
        </div>

        {{-- Next Steps --}}
        <div class="bg-blue-50 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">What's Next?</h3>
            <ul class="space-y-2 text-sm text-gray-700">
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>You'll receive an email confirmation shortly with your order details</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>We'll notify you when your order ships with tracking information</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Expected delivery: {{ now()->addDays(3)->format('M d, Y') }} -
                        {{ now()->addDays(7)->format('M d, Y') }}</span>
                </li>
            </ul>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="{{ route('orders.show', $order) }}"
                class="flex-1 bg-gray-900 text-white text-center py-3 px-6 rounded-lg font-medium hover:bg-gray-800 transition">
                View Order Details
            </a>
            <a href="{{ route('home') }}"
                class="flex-1 bg-white border border-gray-300 text-gray-700 text-center py-3 px-6 rounded-lg font-medium hover:bg-gray-50 transition">
                Continue Shopping
            </a>
        </div>
    </div>
</div>
