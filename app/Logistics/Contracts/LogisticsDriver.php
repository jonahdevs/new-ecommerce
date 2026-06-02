<?php

namespace App\Logistics\Contracts;

use App\Logistics\DTOs\BookingResult;
use App\Logistics\DTOs\QuoteResult;
use App\Logistics\DTOs\TrackingResult;
use App\Models\Order;
use App\Models\Shipment;

interface LogisticsDriver
{
    /**
     * Return a delivery quote for the given order.
     * Called at checkout to show the customer a live price / ETA.
     */
    public function getQuote(Order $order): QuoteResult;

    /**
     * Book the delivery with the carrier and return the booking reference.
     * Called when the order is confirmed / payment received.
     */
    public function book(Order $order): BookingResult;

    /**
     * Fetch the current tracking status from the carrier.
     */
    public function track(Shipment $shipment): TrackingResult;

    /**
     * Cancel a booked shipment. Returns true on success.
     */
    public function cancel(Shipment $shipment): bool;
}
