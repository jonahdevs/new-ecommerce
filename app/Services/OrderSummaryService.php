<?php

namespace App\Services;

/**
 * OrderSummaryService
 *
 * Builds the summary array consumed by the order-summary Livewire component.
 * Returns totals, shipping details, and selection state from the active
 * cart and checkout session.
 */
class OrderSummaryService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutSession $checkoutSession,
    ) {}

    /**
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
        $cart     = $this->cartService->getCart();
        $cartData = $this->cartService->summary($cart);

        $subtotal = (float) ($cartData['subtotal'] ?? 0);
        $discount = (float) ($cartData['discount'] ?? 0);
        $shippingCost = $this->checkoutSession->getShippingCost();

        $stationName = $this->checkoutSession->isPus()
            ? ($this->checkoutSession->getShipping()['station_name'] ?? null)
            : null;

        return [
            'subtotal'         => $subtotal,
            'discount'         => $discount,
            'shipping_cost'    => $shippingCost,
            'shipping_method'  => $this->checkoutSession->getShippingMethodName(),
            'shipping_window'  => $this->checkoutSession->getDeliveryWindow(),
            'station_name'     => $stationName,
            'total'            => max(0, $subtotal - $discount + $shippingCost),
            'shipping_selected' => $this->checkoutSession->hasShipping(),
        ];
    }
}
