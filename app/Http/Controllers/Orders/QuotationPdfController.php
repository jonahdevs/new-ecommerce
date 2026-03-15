<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
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
    //    - Must be a quotation document (not a sales order)
    //    - Available from QUOTE_SENT onwards — the quote must have been priced
    //      before a PDF is available
    //
    //  Serve strategy (mirrors OrderReceiptController):
    //    1. If quotation_pdf_path is set and file exists → serve directly
    //    2. If missing → generate on the fly, store, then serve
    //    3. If generation fails → 500 with friendly message
    // =========================================================================

    public function __invoke(Order $order)
    {
        // Only the quotation owner can download
        abort_if($order->user_id !== auth()->id(), 403);

        // Sales orders don't have quotation PDFs
        abort_if(
            !$order->isQuotation(),
            403,
            'This document is a sales order, not a quotation.'
        );

        // PDF only available once admin has priced and sent the quote
        abort_if(
            !$order->quoted_at,
            403,
            'Quotation PDF is not yet available. Please wait for our team to price your request.'
        );

        $order->load(['items.product', 'user']);

        // ── Serve from stored file if it exists ───────────────────────────────

        if ($order->quotation_pdf_path && Storage::disk('local')->exists($order->quotation_pdf_path)) {
            return Storage::disk('local')->download(
                $order->quotation_pdf_path,
                "Quotation-{$order->reference}.pdf"
            );
        }

        // ── Fallback: generate, store, then serve ─────────────────────────────
        //
        // This path fires when:
        //   - QuotationService::send() ran but generateQuotation() failed silently
        //   - The PDF was deleted from storage manually
        //   - This is a legacy quotation before DocumentService was introduced

        $path = $this->documents->generateQuotation($order);

        if (!$path) {
            abort(500, 'Unable to generate quotation PDF. Please contact support.');
        }

        return Storage::disk('local')->download(
            $path,
            "Quotation-{$order->reference}.pdf"
        );
    }
}
