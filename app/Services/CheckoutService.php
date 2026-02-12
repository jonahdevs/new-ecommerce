<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Class CheckoutService.
 */
class CheckoutService
{

    public function __construct(
        private OrderService $orderService,
        private PesawiseService $pesawiseService
    ) {
    }

    /**
     * Start the Checkout process
     * This validates everything is ready before proceeding
     */
    public function initiateCheckout(?Cart $cart = null)
    {
        $cart = $cart ?? app(CartService::class)->getCart();

        // Check if user has a recent pending order
        if (auth()->check()) {
            $existingOrder = Order::where('user_id', auth()->id())
                ->where('status', 'pending')
                ->where('created_at', '>=', now()->subMinutes(30))
                ->first();

            if ($existingOrder && $existingOrder->payment) {
                // Reuse existing order
                $paymentResponse = $this->pesawiseService->createPaymentOrder($existingOrder);
                return redirect()->away($paymentResponse['createdPaymentOrder']['loadUrl']);
            }
        }

        // Create new order
        $result = DB::transaction(function () use ($cart) {
            $order = $this->orderService->createFromCart($cart);
            $paymentResponse = $this->pesawiseService->createPaymentOrder($order);
            return $paymentResponse['createdPaymentOrder']['loadUrl'];
        });

        return redirect()->away($result);
    }
}
