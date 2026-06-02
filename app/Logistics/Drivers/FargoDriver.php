<?php

namespace App\Logistics\Drivers;

use App\Enums\ShipmentStatus;
use App\Logistics\Contracts\LogisticsDriver;
use App\Logistics\DTOs\BookingResult;
use App\Logistics\DTOs\QuoteResult;
use App\Logistics\DTOs\TrackingResult;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingCarrier;

/**
 * Fargo Courier does not expose a public REST API.
 * Bookings are handled by submitting a waybill request via their web portal
 * or over the phone. This driver manages the manual workflow — staff record
 * the waybill number in the shipment after booking.
 */
class FargoDriver implements LogisticsDriver
{
    public function __construct(ShippingCarrier $carrier)
    {
        // No credentials needed for manual workflow.
    }

    public function getQuote(Order $order): QuoteResult
    {
        $method = $order->shippingMethod;

        // Fargo uses a fixed rate configured on the shipping method.
        return new QuoteResult(
            available: true,
            amountCents: $method?->base_rate_cents ?? 0,
            etaLabel: $method?->eta_label ?? '2–5 business days',
        );
    }

    public function book(Order $order): BookingResult
    {
        // Staff must create the waybill on the Fargo portal and then
        // paste the waybill number into the shipment record.
        return new BookingResult(
            success: true,
            bookingRef: null, // filled in manually by staff
        );
    }

    public function track(Shipment $shipment): TrackingResult
    {
        // Fargo does not have a tracking API; status is updated manually.
        return new TrackingResult(
            success: true,
            status: ShipmentStatus::from($shipment->status),
            statusDescription: 'Track on https://www.fargocourier.co.ke using waybill '.$shipment->tracking_number,
        );
    }

    public function cancel(Shipment $shipment): bool
    {
        // Cancellation is handled by calling Fargo directly.
        return ! ShipmentStatus::from($shipment->status)->isTerminal();
    }
}
