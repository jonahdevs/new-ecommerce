<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Spatie\LaravelPdf\Facades\Pdf;

class PackingSlipController extends Controller
{
    // =========================================================================
    //  Generate and serve a packing slip PDF for warehouse fulfillment.
    //
    //  Access rules:
    //    - Only staff members can access (admin routes)
    //    - Available for any order regardless of payment status
    //
    //  The packing slip includes:
    //    - Order reference and date
    //    - Customer shipping address
    //    - Item list with SKU, name, and quantity (no prices)
    //    - Barcode for scanning
    // =========================================================================

    public function __invoke(Order $order)
    {
        $order->load(['items.product', 'user', 'deliveryOrder.shippingMethod', 'deliveryOrder.pickupStation']);

        return Pdf::view('pdf.packing-slip', ['order' => $order])
            ->format('a4')
            ->margins(10, 10, 10, 10)
            ->name("PackingSlip-{$order->reference}.pdf");
    }
}
