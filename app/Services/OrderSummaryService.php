<?php

namespace App\Services;


/**
 * Class OrderSummaryService.
 */
class OrderSummaryService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutSession $checkoutSession,
    ) {}

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
            ? $this->checkoutSession->getShipping()['station_name'] ?? null
            : null;
        $subtotal = (float) ($cartData['subtotal'] ?? 0);
        $discount = (float) ($cartData['discount'] ?? 0);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_cost' => $shippingCost,
            'shipping_method' => $shippingMethod,
            'shipping_method_type' => $this->checkoutSession->getShipping()['method_type'] ?? null,
            'shipping_window' => $shippingWindow,
            'station_name' => $stationName,
            'total' => max(0, $subtotal - $discount + $shippingCost),
            'shipping_selected' => $this->checkoutSession->hasShipping(),
        ];
    }
}
