<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PackingListService;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

class PackingSlipController extends Controller
{
    public function __construct(private readonly PackingListService $packingListService) {}

    public function __invoke(Order $order)
    {
        $order->load(['items.product', 'user', 'deliveryOrder.shippingMethod', 'deliveryOrder.pickupStation']);

        // Serve the stored PDF when it exists (generated at PROCESSING)
        if ($this->packingListService->exists($order)) {
            return Storage::disk('local')->response(
                $order->packing_list_path,
                "PackingSlip-{$order->reference}.pdf",
                ['Content-Type' => 'application/pdf'],
            );
        }

        // Fallback: generate on the fly (order not yet at PROCESSING, or generation failed)
        $driver = config('laravel-pdf.driver', 'browsershot');
        $isChromium = in_array($driver, ['browsershot', 'cloudflare', 'gotenberg']);

        $pdf = Pdf::view('pdf.packing-slip', ['order' => $order])
            ->format('a4')
            ->name("PackingSlip-{$order->reference}.pdf");

        if ($isChromium) {
            $pdf->footerView('pdf.packing-slip-footer')
                ->margins(10, 10, 25, 10);
        }

        return $pdf;
    }
}
