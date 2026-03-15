<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    // =========================================================================
    //  Storage disk and directory constants
    //
    //  All PDFs are stored on the local disk under storage/app/.
    //  They are served via the OrderReceiptController using a signed URL
    //  or a direct storage response — never publicly accessible by path.
    // =========================================================================

    private const DISK = 'local';
    private const INVOICE_DIR = 'invoices';
    private const QUOTATION_DIR = 'quotations';

    // =========================================================================
    //  GENERATE TAX INVOICE
    //
    //  Called when an order's payment is confirmed (payment webhook fires).
    //  Generates a tax invoice PDF, stores it to storage/app/invoices/,
    //  and updates order.invoice_path.
    //
    //  Returns the storage path on success, null on failure.
    //  Failure is logged but never throws — a PDF generation failure must
    //  never block the payment confirmation flow.
    //
    //  Wiring — add to your payment webhook handlers after payment confirmed:
    //    app(DocumentService::class)->generateInvoice($order);
    //
    //  Or listen on the OrderStatusUpdated / PaymentConfirmed event if you have one.
    // =========================================================================

    public function generateInvoice(Order $order): ?string
    {
        // Only generate for confirmed sales orders that have been paid
        if (!$order->isSalesOrder()) {
            Log::warning('generateInvoice called on a non-sales-order.', [
                'order_id' => $order->id,
                'document_type' => $order->document_type,
            ]);
            return null;
        }

        try {
            $pdf = Pdf::loadView('pdf.invoice', ['order' => $order->load(['items', 'payment', 'user'])])
                ->setPaper('a4', 'portrait')
                ->setOption('dpi', 150)
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', false); // local images only

            $filename = "{$order->reference}.pdf";
            $path = self::INVOICE_DIR . '/' . $filename;

            Storage::disk(self::DISK)->put($path, $pdf->output());

            // Store the path on the order so download buttons can serve it
            $order->update(['invoice_path' => $path]);

            Log::info('Tax invoice generated.', [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'path' => $path,
            ]);

            return $path;

        } catch (\Throwable $e) {
            Log::error('Failed to generate tax invoice.', [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // =========================================================================
    //  GENERATE QUOTATION PDF
    //
    //  Called inside QuotationService::send() after the QUOTE_SENT transition.
    //  Generates a quotation PDF, stores it to storage/app/quotations/,
    //  and updates order.quotation_pdf_path.
    //
    //  Returns the storage path on success, null on failure.
    //  Failure is logged but never throws — quote is already sent to customer
    //  even if PDF generation fails (they can still see details on the portal).
    // =========================================================================

    public function generateQuotation(Order $order): ?string
    {
        if (!$order->isQuotation()) {
            Log::warning('generateQuotation called on a non-quotation.', [
                'order_id' => $order->id,
                'document_type' => $order->document_type,
            ]);
            return null;
        }

        try {
            $pdf = Pdf::loadView('pdf.quotation', ['order' => $order->load(['items', 'user'])])
                ->setPaper('a4', 'portrait')
                ->setOption('dpi', 150)
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', false);

            $filename = "{$order->reference}.pdf";
            $path = self::QUOTATION_DIR . '/' . $filename;

            Storage::disk(self::DISK)->put($path, $pdf->output());

            $order->update(['quotation_pdf_path' => $path]);

            Log::info('Quotation PDF generated.', [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'path' => $path,
            ]);

            return $path;

        } catch (\Throwable $e) {
            Log::error('Failed to generate quotation PDF.', [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // =========================================================================
    //  SERVE PDF
    //
    //  Returns a download response for a stored PDF file.
    //  Used in controllers and Livewire components to serve the file.
    //
    //  Usage:
    //    return app(DocumentService::class)->serve($order->invoice_path, 'Invoice');
    //
    //  Returns null if the file doesn't exist — caller should handle gracefully.
    // =========================================================================

    public function serve(string $path, string $label = 'Document'): ?\Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!Storage::disk(self::DISK)->exists($path)) {
            Log::warning('PDF serve requested but file not found.', ['path' => $path]);
            return null;
        }

        $filename = basename($path);

        return Storage::disk(self::DISK)->download($path, "{$label}-{$filename}");
    }

    // =========================================================================
    //  DELETE PDF
    //
    //  Removes a stored PDF from disk.
    //  Call if an order is cancelled and you want to clean up old documents.
    //  Optional — not required for core functionality.
    // =========================================================================

    public function delete(string $path): void
    {
        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
