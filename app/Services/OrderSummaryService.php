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
        private readonly TaxService $taxService,
    ) {}

    /**
     * @return array{
     *     subtotal: float,
     *     discount: float,
     *     shipping_cost: float,
     *     shipping_method: string|null,
     *     shipping_window: string|null,
     *     station_name: string|null,
     *     tax: float,
     *     tax_name: string,
     *     tax_rate: string,
     *     tax_enabled: bool,
     *     tax_inclusive: bool,
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

        // Calculate tax
        $taxableSubtotal = (int) round(($subtotal - $discount) * 100);
        $shippingCents = (int) round($shippingCost * 100);
        $taxBreakdown = $this->taxService->calculateOrderTax($taxableSubtotal, $shippingCents);
        $taxCents = $taxBreakdown['total_tax'];

        // For exclusive tax, add to total; for inclusive, total stays the same
        $baseTotal = $subtotal - $discount + $shippingCost;
        $total = $this->taxService->isInclusive()
            ? max(0, $baseTotal)
            : max(0, $baseTotal + ($taxCents / 100));

        return [
            'subtotal'         => $subtotal,
            'discount'         => $discount,
            'shipping_cost'    => $shippingCost,
            'shipping_method'  => $this->checkoutSession->getShippingMethodName(),
            'shipping_window'  => $this->checkoutSession->getDeliveryWindow(),
            'station_name'     => $stationName,
            'tax'              => $taxCents / 100,
            'tax_name'         => $this->taxService->name(),
            'tax_rate'         => $this->taxService->rateLabel(),
            'tax_enabled'      => $this->taxService->isEnabled(),
            'tax_inclusive'    => $this->taxService->isInclusive(),
            'total'            => $total,
            'shipping_selected' => $this->checkoutSession->hasShipping(),
        ];
    }
}
