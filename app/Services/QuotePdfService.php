<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Showroom;
use App\Settings\PaymentSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuotePdfService
{
    private const DISK = 'local';

    private const DIR = 'quotations';

    /**
     * Generate the quotation PDF, store it to disk and update document_path.
     * Returns the storage path on success, null on failure.
     * Failure is logged but never throws — the quote is already sent even if
     * PDF generation fails.
     */
    public function generate(Quote $quote): ?string
    {
        try {
            $filename = $quote->quote_number.'.pdf';
            $path = self::DIR.'/'.$filename;

            $showrooms = Showroom::orderByDesc('is_hq')->orderBy('sort_order')->limit(3)->get();
            $banking = app(PaymentSettings::class)->bank_details;

            $content = Pdf::view('pdf.quote', ['quote' => $quote->load('items')])
                ->format('a4')
                ->footerView('pdf.footer', [
                    'showrooms' => $showrooms,
                    'banking' => $banking,
                    'appUrl' => config('app.url'),
                ])
                ->margins(top: 0, right: 0, bottom: 38, left: 0)
                ->generatePdfContent();

            Storage::disk(self::DISK)->put($path, $content);

            $quote->update(['document_path' => $path]);

            Log::info('Quote PDF generated.', ['quote' => $quote->quote_number, 'path' => $path]);

            return $path;

        } catch (\Throwable $e) {
            Log::error('Failed to generate quote PDF.', [
                'quote' => $quote->quote_number,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Return a download response for a stored quotation PDF.
     * Falls back to on-the-fly generation if the file is missing.
     */
    public function download(Quote $quote): ?StreamedResponse
    {
        $path = $this->ensureExists($quote);

        if (! $path) {
            return null;
        }

        return Storage::disk(self::DISK)->download($path, $quote->quote_number.'.pdf');
    }

    /**
     * Return an inline-preview response (opens in browser tab).
     */
    public function inline(Quote $quote): ?StreamedResponse
    {
        $path = $this->ensureExists($quote);

        if (! $path) {
            return null;
        }

        return Storage::disk(self::DISK)->response($path, $quote->quote_number.'.pdf', [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$quote->quote_number.'.pdf"',
        ]);
    }

    /**
     * Return the raw PDF bytes for attaching to an email.
     * Reads from disk if already generated, otherwise generates on the fly.
     */
    public function bytes(Quote $quote): ?string
    {
        $path = $this->ensureExists($quote);

        if (! $path) {
            return null;
        }

        return Storage::disk(self::DISK)->get($path);
    }

    /**
     * Delete the stored PDF so a fresh one is generated on the next send.
     */
    public function delete(Quote $quote): void
    {
        if ($quote->document_path && Storage::disk(self::DISK)->exists($quote->document_path)) {
            Storage::disk(self::DISK)->delete($quote->document_path);
            $quote->update(['document_path' => null]);
        }
    }

    /**
     * Ensure the PDF exists on disk, generating it if missing.
     */
    private function ensureExists(Quote $quote): ?string
    {
        if ($quote->document_path && Storage::disk(self::DISK)->exists($quote->document_path)) {
            return $quote->document_path;
        }

        return $this->generate($quote);
    }
}
