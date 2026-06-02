<?php

namespace App\Logistics\Drivers;

use App\Logistics\Contracts\LogisticsDriver;
use App\Logistics\DTOs\BookingResult;
use App\Logistics\DTOs\QuoteResult;
use App\Logistics\DTOs\TrackingResult;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingCarrier;

class DhlDriver implements LogisticsDriver
{
    public function __construct(ShippingCarrier $carrier)
    {
        // DHL REST API credentials from $carrier->credentials
    }

    public function getQuote(Order $order): QuoteResult
    {
        return QuoteResult::unavailable('DHL integration not yet configured.');
    }

    public function book(Order $order): BookingResult
    {
        return BookingResult::failed('DHL integration not yet configured.');
    }

    public function track(Shipment $shipment): TrackingResult
    {
        return TrackingResult::failed('DHL integration not yet configured.');
    }

    public function cancel(Shipment $shipment): bool
    {
        return false;
    }
}
