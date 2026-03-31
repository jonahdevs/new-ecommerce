<?php

namespace App\Services;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    // =========================================================================
    //  Storage disk and directory constants
    //
    //  All PDFs are stored on the local disk under storage/app/.
    //  They are served via controllers using a signed URL or a direct storage
    //  response — never publicly accessible by path.
    // =========================================================================

    private const DISK = 'local';
    private const QUOTATION_DIR = 'quotations';

    // =========================================================================
    //  GENERATE QUOTATION PDF
    //
    //  Called inside QuotationService::send() after the SENT transition.
    //  Generates a quotation PDF, stores it to storage/app/quotations/,
    //  and updates quote.document_path.
    //
    //  Returns the storage path on success, null on failure.
    //  Failure is logged but never throws — quote is already sent to customer
    //  even if PDF generation fails (they can still see details on the portal).
    // =========================================================================

    public function generateQuotation(Quote $quote): ?string
    {
        try {
            $pdf = Pdf::loadView('pdf.quotation', ['quote' => $quote->load(['items', 'user'])])
                ->setPaper('a4', 'portrait')
                ->setOption('dpi', 150)
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', false);

            $filename = "{$quote->reference}.pdf";
            $path = self::QUOTATION_DIR . '/' . $filename;

            Storage::disk(self::DISK)->put($path, $pdf->output());

            $quote->update(['document_path' => $path]);

            Log::info('Quotation PDF generated.', [
                'quote_id' => $quote->id,
                'reference' => $quote->reference,
                'path' => $path,
            ]);

            return $path;

        } catch (\Throwable $e) {
            Log::error('Failed to generate quotation PDF.', [
                'quote_id' => $quote->id,
                'reference' => $quote->reference,
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
    //    return app(DocumentService::class)->serve($quote->document_path, 'Quotation');
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
    //  STREAM PDF (inline preview in browser)
    //
    //  Returns a response that displays the PDF inline in the browser.
    //  Used for preview functionality instead of forcing download.
    //
    //  Usage:
    //    return app(DocumentService::class)->stream($quote->document_path, 'Quotation');
    //
    //  Returns null if the file doesn't exist — caller should handle gracefully.
    // =========================================================================

    public function stream(string $path, string $label = 'Document'): ?\Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!Storage::disk(self::DISK)->exists($path)) {
            Log::warning('PDF stream requested but file not found.', ['path' => $path]);
            return null;
        }

        $filename = basename($path);

        return Storage::disk(self::DISK)->response($path, "{$label}-{$filename}", [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$label}-{$filename}\"",
        ]);
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
