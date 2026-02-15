<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

new #[Layout('layouts.guest')] class extends Component {
    public ?Order $order = null;
    public bool $sessionExpired = false;
    public bool $tokenInvalid = false;

    public function mount()
    {
        $token = request()->query('token');

        // Validate token from URL matches session
        $sessionToken = session('payment_success_token');
        $orderId = session('payment_success_order_id');
        $expiresAt = session('payment_success_expires_at');

        // Check 1: Token must match
        if (!$token || $token !== $sessionToken) {
            $this->tokenInvalid = true;
            Log::warning('Invalid payment success token', [
                'provided_token' => $token ? 'present' : 'missing',
                'session_token' => $sessionToken ? 'present' : 'missing',
            ]);
            return;
        }

        // Check 2: Session must not be expired
        if (!$expiresAt || now()->timestamp > $expiresAt) {
            $this->sessionExpired = true;
            Log::warning('Payment success session expired', [
                'order_id' => $orderId,
            ]);
            return;
        }

        // Load order
        if ($orderId) {
            $this->order = Order::with(['payment', 'items.product'])->find($orderId);

            if ($this->order) {
                // Mark payment as completed (or pending verification)
                if ($this->order->payment->status === 'processing') {
                    $this->order->payment->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                    ]);

                    $this->order->update([
                        'status' => 'confirmed',
                    ]);
                }

                Log::info('Payment success page loaded', [
                    'order_id' => $this->order->id,
                ]);

                // Clear the success session after loading (one-time use)
                session()->forget(['payment_success_token', 'payment_success_order_id', 'payment_success_expires_at']);
            } else {
                $this->tokenInvalid = true;
            }
        } else {
            $this->tokenInvalid = true;
        }
    }
};
?>

<div class="container mx-auto px-4 py-8">
    @if ($tokenInvalid)
        <div class="max-w-md mx-auto text-center">
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
                <h2 class="text-xl font-bold text-red-800 mb-2">Invalid Access</h2>
                <p class="text-red-600 mb-4">This payment confirmation link is invalid or has already been used.</p>
                <a href="#" class="inline-block bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                    View My Orders
                </a>
            </div>
        </div>
    @elseif($sessionExpired)
        <div class="max-w-md mx-auto text-center">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <svg class="w-16 h-16 text-yellow-500 mx-auto mb-4" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h2 class="text-xl font-bold text-yellow-800 mb-2">Session Expired</h2>
                <p class="text-yellow-600 mb-4">This payment confirmation has expired. Please check your orders.</p>
                <a href="#"
                    class="inline-block bg-yellow-600 text-white px-6 py-2 rounded-lg hover:bg-yellow-700">
                    View My Orders
                </a>
            </div>
        </div>
    @elseif($order)
        <div class="max-w-2xl mx-auto">
            <!-- Success Message -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                <div class="flex items-center mb-4">
                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-green-800">Payment Successful!</h1>
                        <p class="text-green-600">Thank you for your order</p>
                    </div>
                </div>

                <div class="space-y-2 text-sm">
                    <p><strong>Order Reference:</strong> {{ $order->reference }}</p>
                    <p><strong>Amount:</strong> KES {{ number_format($order->total, 2) }}</p>
                    <p><strong>Status:</strong>
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            {{ ucfirst($order->status) }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Order Details -->
            <div class="bg-white border rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Order Details</h2>

                <div class="space-y-4">
                    @foreach ($order->items as $item)
                        <div class="flex items-center gap-4 pb-4 border-b last:border-b-0">
                            <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}"
                                class="w-16 h-16 object-cover rounded">
                            <div class="flex-1">
                                <p class="font-medium">{{ $item->product->name }}</p>
                                <p class="text-sm text-gray-600">Qty: {{ $item->quantity }}</p>
                            </div>
                            <p class="font-semibold">KES {{ number_format($item->price * $item->quantity, 2) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4">
                <a href="#"
                    class="flex-1 text-center bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    View Order Details
                </a>
                <a href="{{ route('home') }}"
                    class="flex-1 text-center bg-gray-200 text-gray-800 px-6 py-3 rounded-lg hover:bg-gray-300">
                    Continue Shopping
                </a>
            </div>
        </div>
    @else
        <div class="text-center">
            <p class="text-gray-600">Loading order information...</p>
        </div>
    @endif
</div>
