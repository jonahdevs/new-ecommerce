<?php

namespace App\Http\Controllers\Orders;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Storage;

class OrderReceiptController extends Controller
{
    public function __construct(private readonly DocumentService $documents)
    {
    }

    // =========================================================================
    //  Serve the tax invoice PDF for a paid sales order.
    //
    //  Access rules:
    //    - Only the order owner can download
    //    - Only available for paid orders
    //    - Quotations are not served here — they have their own download path
    //
    //  Serve strategy:
    //    1. If invoice_path is set and the file exists on disk → serve it directly
    //       (consistent document — always the same PDF that was generated at
    //       payment time, regardless of any data changes since)
    //
    //    2. If invoice_path is null or file is missing → generate on the fly,
    //       store it, then serve it. This handles edge cases where the webhook
    //       fired but PDF generation failed silently.
    //
    //    3. If generation fails → return a 500 with a friendly message rather
    //       than a blank download.
    // =========================================================================

    public function __invoke(Order $order)
    {
        // Only the order owner can download their invoice
        abort_if($order->user_id !== auth()->id(), 403);

        // Quotations are not invoices — serve quotation PDF separately
        abort_if(
            $order->isQuotation(),
            403,
            'This document is a quotation, not an invoice.'
        );

        // Invoice only available once payment is confirmed
        abort_if(
            $order->payment?->status?->value !== PaymentStatus::PAID->value,
            403,
            'Invoice is only available for paid orders.'
        );

        $order->load(['items.product', 'payment', 'user', 'parentQuotation']);

        //  Serve from stored file if it exists

        if ($order->invoice_path && Storage::disk('local')->exists($order->invoice_path)) {
            return Storage::disk('local')->download(
                $order->invoice_path,
                "Invoice-{$order->reference}.pdf"
            );
        }

        //  Fallback: generate, store, then serve
        //
        // This path fires when:
        //   - The webhook fired but generateInvoice() failed silently
        //   - The PDF was deleted from storage manually
        //   - This is a legacy order before DocumentService was introduced

        $path = $this->documents->generateInvoice($order);

        if (!$path) {
            abort(500, 'Unable to generate invoice. Please contact support.');
        }

        return Storage::disk('local')->download(
            $path,
            "Invoice-{$order->reference}.pdf"
        );
    }
}
