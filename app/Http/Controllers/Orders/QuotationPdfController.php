<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Storage;

class QuotationPdfController extends Controller
{
    public function __construct(private readonly DocumentService $documents)
    {
    }

    // =========================================================================
    //  Serve the quotation PDF for a customer.
    //
    //  Access rules:
    //    - Only the quotation owner can download
    //    - Available from SENT onwards — the quote must have been priced
    //      before a PDF is available
    //
    //  Serve strategy:
    //    1. If document_path is set and file exists → serve directly
    //    2. If missing → generate on the fly, store, then serve
    //    3. If generation fails → 500 with friendly message
    // =========================================================================

    public function __invoke(Quote $quote)
    {
        // Only the quotation owner can download
        abort_if($quote->user_id !== auth()->id(), 403);

        // PDF only available once admin has priced and sent the quote
        abort_if(
            !$quote->quoted_at,
            403,
            'Quotation PDF is not yet available. Please wait for our team to price your request.'
        );

        $quote->load(['items.product', 'user']);

        // ── Serve from stored file if it exists ───────────────────────────────

        if ($quote->document_path && Storage::disk('local')->exists($quote->document_path)) {
            return Storage::disk('local')->download(
                $quote->document_path,
                "Quotation-{$quote->reference}.pdf"
            );
        }

        // ── Fallback: generate, store, then serve ─────────────────────────────

        $path = $this->documents->generateQuotation($quote);

        if (!$path) {
            abort(500, 'Unable to generate quotation PDF. Please contact support.');
        }

        return Storage::disk('local')->download(
            $path,
            "Quotation-{$quote->reference}.pdf"
        );
    }
}
