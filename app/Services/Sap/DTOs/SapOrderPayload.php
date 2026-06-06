<?php

namespace App\Services\Sap\DTOs;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;

/**
 * Builds the SAP invoice creation payload from an Order.
 * Extracted from SapIntegrationService so it can be tested independently.
 */
final readonly class SapOrderPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function fromOrder(Order $order): array
    {
        $order->loadMissing(['items', 'payments', 'user', 'address']);

        $payment = $order->payments
            ->where('status', PaymentStatus::SUCCESS)
            ->sortByDesc('paid_at')
            ->first();

        return [
            'credit_guard_response' => self::paymentBlock($payment),
            'customer' => self::customerBlock($order),
            'order' => self::orderBlock($order),
        ];
    }

    /**
     * Maps our payment record to the SAP credit_guard_response shape.
     * Most fields are card-specific (Credit Guard gateway); we fill what we have
     * and leave the rest empty — SAP middleware accepts partial data.
     *
     * @return array<string, string>
     */
    private static function paymentBlock(?Payment $payment): array
    {
        return [
            'authNumber' => '',
            'cardBrand' => $payment?->card_brand ?? '',
            'cardExpiration' => '',
            'cardId' => '',
            'cardNo' => $payment?->card_last4 ?? '',
            'cgUid' => '',
            'creditCardToken' => $payment?->stripe_payment_intent_id ?? $payment?->mpesa_receipt ?? '',
            'numberOfPayments' => '0',
            'personalId' => '',
            'uid' => $payment?->mpesa_receipt ?? $payment?->stripe_payment_intent_id ?? '',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private static function customerBlock(Order $order): array
    {
        return [
            'created_at' => $order->user?->created_at?->toISOString() ?? $order->created_at->toISOString(),
            'email' => $order->user?->email ?? $order->shipping_email ?? '',
            'full_address' => $order->address?->line1 ?? $order->shipping_line1 ?? '',
            'full_name' => $order->address?->fullName() ?? $order->shipping_name ?? $order->user?->name ?? '',
            'mobile_phone' => $order->address?->phone ?? $order->shipping_phone ?? '',
            'note' => $order->notes,
            'updated_at' => $order->user?->updated_at?->toISOString() ?? $order->updated_at->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function orderBlock(Order $order): array
    {
        return [
            'Orderid' => $order->id,
            'name' => $order->address?->fullName() ?? $order->shipping_name ?? $order->user?->name ?? '',
            'phone' => $order->address?->phone ?? $order->shipping_phone ?? '',
            'payment_status' => 'Paid',
            'cart' => [
                'debit_total_price' => $order->total_cents / 100,
                'lines' => $order->items->map(fn ($item) => [
                    'code' => $item->product_snapshot['sku'] ?? '',
                    'item_id' => $item->product_id,
                    'line_item_id' => $item->id,
                    'price' => $item->unit_price_cents / 100,
                    'quantity' => $item->quantity,
                    'linetotal' => $item->line_total_cents,
                ])->values()->toArray(),
            ],
        ];
    }
}
