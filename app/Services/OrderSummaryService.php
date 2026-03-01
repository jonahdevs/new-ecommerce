<?php

namespace App\Services;

use App\Models\Cart;

/**
 * Class OrderSummaryService.
 */
class OrderSummaryService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutSession $checkoutSession,
    ) {
    }

    /**
     * Build the complete summary array for the order-summary component.
     *
     * @return array{
     *     subtotal: float,
     *     discount: float,
     *     shipping_cost: float,
     *     shipping_method: string|null,
     *     shipping_window: string|null,
     *     station_name: string|null,
     *     total: float,
     *     shipping_selected: bool,
     * }
     */
    public function summary(): array
    {
        $cart = $this->cartService->getCart();
        $cartData = $this->cartService->summary($cart);

        $shippingCost = $this->checkoutSession->getShippingCost();
        $shippingMethod = $this->checkoutSession->getShippingMethodName();
        $shippingWindow = $this->checkoutSession->getDeliveryWindow();
        $stationName = $this->checkoutSession->isPus()
            ? session('checkout.shipping.station_name')
            : null;

        $subtotal = (float) ($cartData['subtotal'] ?? 0);
        $discount = (float) ($cartData['discount'] ?? 0);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_cost' => $shippingCost,
            'shipping_method' => $shippingMethod,
            'shipping_window' => $shippingWindow,
            'station_name' => $stationName,
            'total' => max(0, $subtotal - $discount + $shippingCost),
            'shipping_selected' => $this->checkoutSession->hasShipping(),
        ];
    }

    protected function calculateSubtotal(Cart $cart)
    {
        $subtotal = $cart->items->reduce(function ($carry, $item) {
            return $carry + ($item->product->final_price * $item->quantity);
        }, 0);

        return $subtotal;
    }

    public function calculateDiscount(Cart $cart)
    {
        $discount = $cart->items->reduce(function ($carry, $item) {
            $product = $item->product;

            if (!$product->sale_price) {
                return $carry;
            }

            return $carry + (($item->product->price - $item->product->sale_price) * $item->quantity);
        }, 0);

        return $discount;
    }

    public function calculateShippingCost(Cart $cart)
    {
        return app(ShippingCalculatorService::class)->calculate($cart);
    }

    public function calculateTax(Cart $cart)
    {
        return 0;
    }

    public function calculateTotal($subtotal, $discount, $tax, $shipping_cost)
    {
        return $subtotal + $shipping_cost + $tax;
    }
}
