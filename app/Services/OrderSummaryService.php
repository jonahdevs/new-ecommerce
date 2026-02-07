<?php

namespace App\Services;

use App\Models\Cart;

/**
 * Class OrderSummaryService.
 */
class OrderSummaryService
{

    public function summary()
    {
        $cart = auth()->user()->cart;

        $subtotal = $this->calculateSubtotal($cart);
        $discount = $this->calculateDiscount($cart);
        $shipping_cost = $this->calculateShippingCost($cart);
        $tax = $this->calculateTax($cart);

        $total = $this->calculateTotal($subtotal, $discount, $tax, $shipping_cost);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_cost' => $shipping_cost,
            'tax' => $tax,
            'total' => $total,
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
