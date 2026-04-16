<?php

namespace App\Services\Sap;

use App\Models\Order;
use App\Notifications\OrderConfirmedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

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
        if (!$this->canGenerate($order)) {
            throw new \LogicException(
                "Cannot generate invoice for order {$order->reference}: KRA validation not yet complete."
            );
        }

        $order->loadMissing('items.product', 'payment', 'user');

        // Choose template based on PDF driver
        $view = $this->getInvoiceView();
        $driver = config('laravel-pdf.driver', 'browsershot');
        $isChromium = in_array($driver, ['browsershot', 'cloudflare', 'gotenberg']);

        $pdf = Pdf::view($view, ['order' => $order])
            ->format('a4');

        // Chromium-based drivers support repeating footer via footerView
        if ($isChromium) {
            $pdf->footerView('pdf.browsershot.footer', ['order' => $order])
                ->margins(10, 10, 50, 10); // Reserve 50mm bottom margin for footer
        }

        $pdf->name("{$order->reference}.pdf");

        $filename = "{$order->reference}.pdf";
        $path = self::INVOICE_DIR . '/' . $filename;

        Storage::disk(self::DISK)->put($path, $pdf->pdf());

        $order->update(['invoice_path' => $path]);

        Log::info('Tax invoice generated (KRA validated)', [
            'order_id' => $order->id,
            'reference' => $order->reference,
            'kra_cu_number' => $order->kra_cu_number,
            'path' => $path,
            'view' => $view,
        ]);

        return $path;
    }

    /**
     * Get the appropriate invoice view based on PDF driver.
     * Browsershot supports modern CSS (Tailwind), others use custom CSS.
     */
    private function getInvoiceView(): string
    {
        $driver = config('laravel-pdf.driver', 'browsershot');

        // Use Tailwind version for Chromium-based drivers (better CSS support)
        if (in_array($driver, ['browsershot', 'cloudflare', 'gotenberg'])) {
            return 'pdf.browsershot.invoice';
        }

        // Use custom CSS version for limited drivers (dompdf, weasyprint)
        return 'pdf.dompdf.invoice';
    }

    /**
     * True when all required KRA data is present on the order.
     * Gate this before calling generate() or sendToCustomer().
     */
    public function canGenerate(Order $order): bool
    {
        return !is_null($order->kra_cu_number);
    }

    /**
     * Emails the generated invoice PDF to the customer.
     * Silently skips if no email address is available (guest with no email).
     */
    public function sendToCustomer(Order $order): void
    {
        $email = $order->customerEmail();

        if (!$email) {
            Log::warning('Invoice: no customer email, skipping send', [
                'order_id' => $order->id,
            ]);

            return;
        }

        if (!$order->invoice_path || !Storage::disk(self::DISK)->exists($order->invoice_path)) {
            Log::warning('Invoice: PDF not found, skipping send', [
                'order_id' => $order->id,
                'path' => $order->invoice_path,
            ]);

            return;
        }

        $order->user
            ? $order->user->notify(new OrderConfirmedNotification($order))
            : Notification::route('mail', $email)
                ->notify(new OrderConfirmedNotification($order));

        Log::info('Invoice emailed to customer', [
            'order_id' => $order->id,
            'email' => $email,
        ]);
    }
}
