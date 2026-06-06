<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Showroom;
use App\Settings\BrandingSettings;
use App\Settings\PaymentSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderDocumentService
{
    private const DISK = 'local';

    private const PACKING_LIST_DIR = 'packing-lists';

    private const DELIVERY_NOTE_DIR = 'delivery-notes';

    /**
     * Generate both dispatch documents in one call.
     * Safe to call multiple times — regenerates and overwrites.
     */
    public function generateDispatchDocuments(Order $order): void
    {
        $this->generatePackingList($order);
        $this->generateDeliveryNote($order);
    }

    public function generatePackingList(Order $order): ?string
    {
        try {
            $filename = $order->order_number.'-packing-list.pdf';
            $path = self::PACKING_LIST_DIR.'/'.$filename;

            $storeName = app(BrandingSettings::class)->store_name ?: config('app.name');

            $content = Pdf::view('pdf.packing-list', [
                'order' => $order->loadMissing(['items', 'user', 'address', 'shippingMethod']),
            ])
                ->format('a4')
                ->footerView('pdf.packing-list-footer', [
                    'storeName' => $storeName,
                    'generatedAt' => now()->format('d M Y, H:i'),
                ])
                ->margins(top: 0, right: 0, bottom: 22, left: 0)
                ->generatePdfContent();

            Storage::disk(self::DISK)->put($path, $content);
            $order->update(['packing_list_path' => $path]);

            Log::info('Packing list generated.', ['order' => $order->order_number]);

            return $path;
        } catch (\Throwable $e) {
            Log::error('Packing list generation failed.', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function generateDeliveryNote(Order $order): ?string
    {
        try {
            $filename = $order->order_number.'-delivery-note.pdf';
            $path = self::DELIVERY_NOTE_DIR.'/'.$filename;

            $showrooms = Showroom::orderByDesc('is_hq')->orderBy('sort_order')->limit(3)->get();
            $banking = app(PaymentSettings::class)->bank_details;

            $content = Pdf::view('pdf.delivery-note', [
                'order' => $order->loadMissing(['items', 'user', 'address', 'shippingMethod']),
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
            $order->update(['delivery_note_path' => $path]);

            Log::info('Delivery note generated.', ['order' => $order->order_number]);

            return $path;
        } catch (\Throwable $e) {
            Log::error('Delivery note generation failed.', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function downloadPackingList(Order $order): ?StreamedResponse
    {
        $path = $this->ensurePackingList($order);

        if (! $path) {
            return null;
        }

        return Storage::disk(self::DISK)->download($path, $order->order_number.'-packing-list.pdf');
    }

    public function downloadDeliveryNote(Order $order): ?StreamedResponse
    {
        $path = $this->ensureDeliveryNote($order);

        if (! $path) {
            return null;
        }

        return Storage::disk(self::DISK)->download($path, $order->order_number.'-delivery-note.pdf');
    }

    private function ensurePackingList(Order $order): ?string
    {
        if ($order->packing_list_path && Storage::disk(self::DISK)->exists($order->packing_list_path)) {
            return $order->packing_list_path;
        }

        return $this->generatePackingList($order);
    }

    private function ensureDeliveryNote(Order $order): ?string
    {
        if ($order->delivery_note_path && Storage::disk(self::DISK)->exists($order->delivery_note_path)) {
            return $order->delivery_note_path;
        }

        return $this->generateDeliveryNote($order);
    }
}
