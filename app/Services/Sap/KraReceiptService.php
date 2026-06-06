<?php

namespace App\Services\Sap;

use App\Models\Order;
use App\Models\Showroom;
use App\Settings\PaymentSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

class KraReceiptService
{
    private const DISK = 'local';

    private const DIR = 'kra-receipts';

    public function __construct(private readonly SapConfig $config) {}

    /**
     * Generate the KRA tax receipt PDF, store it, and update kra_receipt_path.
     * Returns the storage path on success, null on failure.
     * Never throws — receipt failure must never cascade to the caller.
     */
    public function generate(Order $order): ?string
    {
        if (! $order->kra_cu_number) {
            Log::warning('KRA receipt: cannot generate — no CU number on order.', [
                'order_id' => $order->id,
            ]);

            return null;
        }

        try {
            $filename = $order->order_number.'-receipt.pdf';
            $path = self::DIR.'/'.$filename;

            $showrooms = Showroom::orderByDesc('is_hq')->orderBy('sort_order')->limit(3)->get();
            $banking = app(PaymentSettings::class)->bank_details;

            $content = Pdf::view('pdf.kra-receipt', [
                'order' => $order->loadMissing(['items', 'user', 'address']),
                'businessPin' => $this->config->businessPin(),
            ])
                ->format('a4')
                ->footerView('pdf.footer', [
                    'showrooms' => $showrooms,
                    'banking' => $banking,
                    'appUrl' => config('app.url'),
                ])
                ->margins(top: 0, right: 0, bottom: 38, left: 0)
                ->generatePdfContent();

            Storage::disk(self::DISK)->put($path, $content);

            $order->update(['kra_receipt_path' => $path]);

            Log::info('KRA receipt generated.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'path' => $path,
            ]);

            return $path;
        } catch (\Throwable $e) {
            Log::error('KRA receipt generation failed.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
