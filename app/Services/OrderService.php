<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class OrderService.
 */
class OrderService
{
    public function createFromCart(?Cart $cart = null): Order
    {
        $cart = $cart ?? app(CartService::class)->getCart();
        $cart->load('items.product');

        if (!$cart || $cart->items->isEmpty()) {
            throw new \Exception('Cart is empty');
        }

        return DB::transaction(function () use ($cart) {
            $user = auth()->user();
            $summary = app(OrderSummaryService::class)->summary();
            $shippingAddress = $user?->defaultAddress;

            if (!$shippingAddress && $user) {
                throw new \Exception('Please add a shipping address before placing order');
            }

            // Create the order
            $order = Order::create([
                'user_id' => $user?->id,
                'reference' => $this->generateOrderReference(),
                'status' => 'pending',
                'currency' => 'KES',
                'subtotal_cents' => $summary['subtotal'] * 100,
                'discount_cents' => $summary['discount'] * 100,
                'shipping_cents' => $summary['shipping_cost'] * 100,
                'tax_cents' => ($summary['tax'] ?? 0) * 100,
                'total_cents' => $summary['total'] * 100,
                'shipping_address' => $shippingAddress?->toArray(),
                'billing_address' => $shippingAddress?->toArray(),
                'placed_at' => now(),
            ]);

            // Create order items
            foreach ($cart->items as $cartItem) {
                $this->createOrderItem($order, $cartItem);
            }

            // Create payment record
            $order->payment()->create([
                'amount_cents' => $summary['total'] * 100,
                'currency' => 'KES',
                'status' => 'pending',
                'gateway' => 'pesawise',
                'expires_at' => now()->addMinutes(30), // Pesawise default
            ]);

            // Record status history
            $order->statusHistories()->create([
                'to_status' => 'pending',
                'changed_by_user_id' => $user?->id,
                'changed_by_type' => $user ? 'user' : 'system',
                'notes' => 'Order created from cart',
                'metadata' => [
                    'cart_id' => $cart->id,
                    'items_count' => $cart->items->count(),
                ],
            ]);

            // Log activity
            activity()
                ->performedOn($order)
                ->causedBy($user)
                ->withProperties([
                    'cart_id' => $cart->id,
                    'items_count' => $cart->items->count(),
                    'subtotal' => $summary['subtotal'],
                    'shipping' => $summary['shipping_cost'],
                    'tax' => $summary['tax'] ?? 0,
                    'total' => $summary['total'],
                    'shipping_method' => $summary['shipping_method'] ?? null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('order_created');

            return $order->fresh(['items', 'statusHistories', 'payment']);
        });
    }

    private function createOrderItem(Order $order, $cartItem): void
    {
        $product = $cartItem->product;
        $variant = null;
        $variantName = '';

        if ($cartItem->variant_id) {
            $variant = $product->variants()->where('id', $cartItem->variant_id)->first();
            $variantName = $variant ? " - {$variant->name}" : '';
        }

        $unitPrice = $variant?->price ?? $product->final_price;

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $cartItem->quantity,
            'unit_price_cents' => $unitPrice * 100,
            'unit_tax_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => ($unitPrice * $cartItem->quantity) * 100,
            'product_snapshot' => [
                'id' => $product->id,
                'name' => $product->name . $variantName,
                'sku' => $variant?->sku ?? $product->sku,
                'image_path' => $product->image_path,
                'price' => $unitPrice,
                'variant_name' => $variant?->name,
            ],
        ]);
    }

    private function generateOrderReference(): string
    {
        do {
            $reference = 'ORD-' . strtoupper(Str::random(10));
        } while (Order::where('reference', $reference)->exists());

        return $reference;
    }


    /**
     * Update payment with Pesawise response data
     */
    public function updatePaymentWithGatewayResponse(Order $order, array $gatewayResponse): void
    {
        $payment = $order->payment;

        if (!$payment) {
            throw new \Exception('Payment record not found for order');
        }

        $payment->update([
            'transaction_id' => $gatewayResponse['orderId'] ?? $gatewayResponse['transactionId'] ?? null,
            'meta' => $gatewayResponse,
        ]);
    }

    /**
     * Mark order and payment as paid
     */
    public function markAsPaid(Order $order, array $paymentData = []): void
    {
        DB::transaction(function () use ($order, $paymentData) {
            $previousStatus = $order->status;

            // Update order status
            $order->update([
                'status' => 'paid',
            ]);

            // Update payment status
            if ($order->payment) {
                $order->payment->update([
                    'status' => 'completed',
                    'transaction_id' => $paymentData['transactionId'] ?? $order->payment->transaction_id,
                    'payment_method_token' => $paymentData['paymentMethodToken'] ?? null,
                    'meta' => array_merge($order->payment->meta ?? [], $paymentData),
                ]);
            }

            // Record status change
            $order->statusHistories()->create([
                'from_status' => $previousStatus,
                'to_status' => 'paid',
                'changed_by_type' => 'system',
                'notes' => 'Payment confirmed via Pesawise',
                'metadata' => $paymentData,
            ]);

            // Log activity
            activity()
                ->performedOn($order)
                ->withProperties([
                    'previous_status' => $previousStatus,
                    'new_status' => 'paid',
                    'payment_data' => $paymentData,
                    'transaction_id' => $paymentData['transactionId'] ?? $order->payment->transaction_id,
                    'amount' => $order->total,
                ])
                ->log('order_marked_paid');

            // TODO: Deduct inventory here after payment confirmation
            // foreach ($order->items as $item) {
            //     if ($item->product) {
            //         $item->product->decrement('stock_quantity', $item->quantity);
            //     }
            // }

            // Clear cart ONLY after successful payment
            if ($order->user_id) {
                Cart::where('user_id', $order->user_id)->delete();
            }
        });
    }

    /**
     * Mark payment as failed
     */
    public function markPaymentAsFailed(Order $order, string $reason = 'Payment failed'): void
    {
        DB::transaction(function () use ($order, $reason) {
            if ($order->payment) {
                $order->payment->update([
                    'status' => 'failed',
                    'meta' => array_merge($order->payment->meta ?? [], [
                        'failure_reason' => $reason,
                        'failed_at' => now()->toISOString(),
                    ]),
                ]);
            }

            $order->statusHistories()->create([
                'from_status' => $order->status,
                'to_status' => 'payment_failed',
                'changed_by_type' => 'system',
                'notes' => $reason,
            ]);

            // Log activity
            activity()
                ->performedOn($order)
                ->withProperties([
                    'reason' => $reason,
                    'payment_gateway' => $order->payment?->gateway,
                    'amount' => $order->total,
                ])
                ->log('payment_failed');

            // Optionally update order status
            // $order->update(['status' => 'payment_failed']);
        });
    }

    /**
     * Cancel order
     */
    public function cancelOrder(Order $order, string $reason = 'Cancelled by user'): void
    {
        DB::transaction(function () use ($order, $reason) {
            $previousStatus = $order->status;

            $order->update([
                'status' => 'cancelled',
            ]);

            if ($order->payment && $order->payment->status === 'pending') {
                $order->payment->update([
                    'status' => 'cancelled',
                ]);
            }

            $order->statusHistories()->create([
                'from_status' => $previousStatus,
                'to_status' => 'cancelled',
                'changed_by_user_id' => auth()->id(),
                'changed_by_type' => auth()->check() ? 'user' : 'system',
                'notes' => $reason,
            ]);

            // Log activity
            activity()
                ->performedOn($order)
                ->causedBy(auth()->user())
                ->withProperties([
                    'previous_status' => $previousStatus,
                    'reason' => $reason,
                    'refund_initiated' => false,
                ])
                ->log('order_cancelled');

            // If order was paid, you might want to initiate refund here
        });
    }
}
