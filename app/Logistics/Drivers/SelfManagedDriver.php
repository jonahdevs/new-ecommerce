<?php

namespace App\Logistics\Drivers;

use App\Enums\ShipmentStatus;
use App\Logistics\Contracts\LogisticsDriver;
use App\Logistics\DTOs\BookingResult;
use App\Logistics\DTOs\QuoteResult;
use App\Logistics\DTOs\TrackingResult;
use App\Models\Order;
use App\Models\Shipment;

class SelfManagedDriver implements LogisticsDriver
{
    public function getQuote(Order $order): QuoteResult
    {
        $method = $order->shippingMethod;

        if (! $method) {
            return QuoteResult::unavailable('No shipping method assigned.');
        }

        $amountCents = match ($method->rate_type->value) {
            'free' => 0,
            default => $method->base_rate_cents,
        };

        if ($method->free_over_cents !== null && $order->subtotal_cents >= $method->free_over_cents) {
            $amountCents = 0;
        }

        return new QuoteResult(
            available: true,
            amountCents: $amountCents,
            etaLabel: $method->eta_label,
        );
    }

    public function book(Order $order): BookingResult
    {
        // Self-managed bookings are internal — no external API call.
        // The shipment is created and assigned to an internal dispatcher.
        return new BookingResult(
            success: true,
            bookingRef: 'SELF-'.$order->order_number,
        );
    }

    public function track(Shipment $shipment): TrackingResult
    {
        // Status is updated manually by staff, so we just reflect what's stored.
        return new TrackingResult(
            success: true,
            status: ShipmentStatus::from($shipment->status),
        );
    }

    public function cancel(Shipment $shipment): bool
    {
        return ! ShipmentStatus::from($shipment->status)->isTerminal();
    }
}
