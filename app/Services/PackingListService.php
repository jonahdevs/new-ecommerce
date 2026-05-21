<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

class PackingListService
{
    private const DISK = 'local';

    private const PACKING_LIST_DIR = 'packing-lists';

    /**
     * Generates the packing slip PDF and stores it in storage/app/packing-lists/.
     * Updates order.packing_list_path on success.
     * Idempotent — safe to call multiple times; regenerates if called again.
     *
     * @return string Storage path of the generated PDF
     */
    public function generate(Order $order): string
    {
        $order->loadMissing(['items.product', 'user']);

        $driver = config('laravel-pdf.driver', 'browsershot');
        $isChromium = in_array($driver, ['browsershot', 'cloudflare', 'gotenberg']);

        $pdf = Pdf::view('pdf.packing-slip', ['order' => $order])
            ->format('a4')
            ->name("PackingSlip-{$order->reference}.pdf");

        if ($isChromium) {
            $pdf->footerView('pdf.packing-slip-footer')
                ->margins(10, 10, 25, 10);
        }

        $filename = "PackingSlip-{$order->reference}.pdf";
        $path = self::PACKING_LIST_DIR.'/'.$filename;

        Storage::disk(self::DISK)->put($path, $pdf->generatePdfContent());

        $order->update(['packing_list_path' => $path]);

        Log::info('Packing slip generated', [
            'order_id' => $order->id,
            'reference' => $order->reference,
            'path' => $path,
        ]);

        return $path;
    }

    /**
     * True when a stored packing slip exists on disk for this order.
     */
    public function exists(Order $order): bool
    {
        return $order->packing_list_path !== null
            && Storage::disk(self::DISK)->exists($order->packing_list_path);
    }
}
