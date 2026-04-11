<?php

namespace App\Services\Sap;

use App\Models\Order;
use App\Notifications\KraReceiptNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class KraReceiptService
{
    private const DISK = 'local';

    private const INVOICE_DIR = 'invoices';

    // ================================================================
    // Public API
    // ================================================================

    /**
     * Generates the tax invoice PDF with KRA compliance data and stores
     * it in storage/app/invoices/. Updates order.invoice_path on success.
     *
     * This is the single legal document — only generated when KRA data
     * (CU number, KRA invoice number, validated_at) is present.
     *
     * @return string Storage path of the generated PDF
     */
    public function generate(Order $order): string
    {
        if (! $this->canGenerate($order)) {
            throw new \LogicException(
                "Cannot generate invoice for order {$order->reference}: KRA validation not yet complete."
            );
        }

        $order->loadMissing('items.product', 'payment', 'user');

        $pdf = Pdf::loadView('pdf.invoice', ['order' => $order])
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 150)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        $filename = "{$order->reference}.pdf";
        $path = self::INVOICE_DIR.'/'.$filename;

        Storage::disk(self::DISK)->put($path, $pdf->output());

        $order->update(['invoice_path' => $path]);

        Log::info('Tax invoice generated (KRA validated)', [
            'order_id' => $order->id,
            'reference' => $order->reference,
            'kra_cu_number' => $order->kra_cu_number,
            'path' => $path,
        ]);

        return $path;
    }

    /**
     * True when all required KRA data is present on the order.
     * Gate this before calling generate() or sendToCustomer().
     */
    public function canGenerate(Order $order): bool
    {
        return ! is_null($order->kra_cu_number);
    }

    /**
     * Emails the generated invoice PDF to the customer.
     * Silently skips if no email address is available (guest with no email).
     */
    public function sendToCustomer(Order $order): void
    {
        $email = $order->customerEmail();

        if (! $email) {
            Log::warning('Invoice: no customer email, skipping send', [
                'order_id' => $order->id,
            ]);

            return;
        }

        if (! $order->invoice_path || ! Storage::disk(self::DISK)->exists($order->invoice_path)) {
            Log::warning('Invoice: PDF not found, skipping send', [
                'order_id' => $order->id,
                'path' => $order->invoice_path,
            ]);

            return;
        }

        $order->user
            ? $order->user->notify(new KraReceiptNotification($order))
            : Notification::route('mail', $email)
                ->notify(new KraReceiptNotification($order));

        Log::info('Invoice emailed to customer', [
            'order_id' => $order->id,
            'email' => $email,
        ]);
    }
}
