<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class CheckoutService.
 */
class CheckoutService
{
    public function initiateCheckout()
    {
        $cart = app(CartService::class)->getCart();
        $correlationId = uniqid('checkout_', true);

        // Prevent double submission with distributed lock
        $lockKey = 'checkout:' . ($cart->user_id ?? $cart->session_id ?? session()->getId());
        $lock = Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            throw new \Exception('Checkout already in progress. Please wait.');
        }

        try {
            // 1. Validate cart is not empty
            if ($cart->items->isEmpty()) {
                throw new \Exception('Your cart is empty');
            }

            // 2. Check inventory availability BEFORE creating order
            $unavailable = app(InventoryService::class)->checkAvailability($cart);
            if (!empty($unavailable)) {
                $message = $this->formatInventoryError($unavailable);
                throw new \Exception($message);
            }

            // 3. Validate user has shipping address (for logged in users)
            if (auth()->check() && !auth()->user()->defaultAddress) {
                throw new \Exception('Please add a shipping address before checkout');
            }

            // 4. Validate minimum order value (if applicable)
            $summary = app(OrderSummaryService::class)->summary($cart);
            $minOrderValue = config('checkout.minimum_order_value', 0);
            if ($summary['total'] < $minOrderValue) {
                throw new \Exception("Minimum order value is " . format_currency($minOrderValue));
            }

            // 5. Check for existing reusable order
            $existingOrder = $this->findReusableOrder();

            if ($existingOrder) {
                return $this->reuseExistingOrder($existingOrder, $correlationId);
            }

            return $this->createNewOrder($cart, $correlationId);
        } catch (\Throwable $th) {
            throw $th;
        } finally {
            $lock->release();
        }
    }

    /**
     * Find an existing order that can be reused (within payment validity window)
     */
    private function findReusableOrder(): ?Order
    {
        if (!auth()->check()) {
            return null;
        }

        return Order::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->whereHas('payment', function ($query) {
                $query->where('status', 'pending')
                    ->where('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Reuse existing order instead of creating duplicate
     */
    private function reuseExistingOrder(Order $existingOrder, string $correlationId)
    {
        // Check if payment link is still valid
        if ($existingOrder->payment->expires_at < now()) {
            // Payment expired, we could create a new payment session
            // but it's safer to let it expire and create fresh order
            throw new \Exception('Previous order expired. Please try again.');
        }

        // Use existing payment link from meta
        $loadUrl = $existingOrder->payment->meta['load_url'] ?? null;

        if (!$loadUrl) {
            // This shouldn't happen, but handle gracefully
            throw new \Exception('Payment link not found. Please try again.');
        }

        return redirect()->away($loadUrl);
    }

    private function createNewOrder(Cart $cart)
    {
        try {
            return DB::transaction(function () use ($cart) {
                // 1. Create Order
                $order = app(OrderService::class)->createFromCart($cart);

                // 2. Reserve inventory (prevent overselling)
                app(InventoryService::class)->reserveStock($order);

                // 3. Create Payment session
                $response = app(PesawiseService::class)->createPaymentOrder($order);

                return redirect()->away($response['createdPaymentOrder']['loadUrl']);
            });
        } catch (\Throwable $th) {
            Log::error('Order creation failed', [
                'error' => $th->getMessage(),
            ]);

            if (str_contains($th->getMessage(), 'Insufficient stock')) {
                throw new \Exception('Some items became unavailable. Please refresh and try again.');
            }

            if (str_contains($th->getMessage(), 'payment gateway')) {
                throw new \Exception('Unable to connect to payment service. Please try again in a moment.');
            }

            throw new \Exception('Unable to process checkout. Please try again.');
        }
    }

    /**
     * Format inventory error message for user
     */
    private function formatInventoryError(array $unavailable): string
    {
        if (count($unavailable) === 1) {
            $item = $unavailable[0];
            return "{$item['product']} is out of stock. Only {$item['available']} available.";
        }

        $products = array_column($unavailable, 'product');
        $list = implode(', ', array_slice($products, 0, 3));

        if (count($products) > 3) {
            $list .= ' and ' . (count($products) - 3) . ' more';
        }

        return "Some items are out of stock: {$list}. Please update your cart.";
    }
}
