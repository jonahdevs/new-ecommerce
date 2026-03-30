<?php

namespace App\Services\Sap;

use App\Models\Order;
use App\Notifications\KraReceiptNotification;
use App\Services\TaxService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KraReceiptService
{
    public function __construct(
        private readonly TaxService $taxService,
    ) {}

class KraReceiptService
{
    // ================================================================
    // Public API
    // ================================================================

    /**
     * Generates the KRA-compliant receipt PDF and stores it in the
     * default filesystem disk. Updates order.kra_receipt_path on success.
     *
     * Property 8: the generated PDF must contain CU number, KRA invoice
     * number, order reference, line items, VAT breakdown, total, and
     * the business PIN.
     *
     * @return string Storage path of the generated PDF
     */
    public function generate(Order $order): string
    {
        if (!$this->canGenerate($order)) {
            throw new \LogicException(
                "Cannot generate KRA receipt for order {$order->reference}: CU number not yet available."
            );
        }

        $order->loadMissing('items', 'user');

        $pdf = Pdf::loadView('pdf.kra', [
            'order'       => $order,
            'businessPin' => config('sap.business_pin'),
            'lineItems'   => $this->buildLineItems($order),
            'vatBreakdown' => $this->buildVatBreakdown($order),
        ]);

        $path = "receipts/kra/{$order->reference}.pdf";

        Storage::put($path, $pdf->output());

        $order->update(['kra_receipt_path' => $path]);

        Log::info('KRA receipt generated', [
            'order_id' => $order->id,
            'path'     => $path,
        ]);

        return $path;
    }

    /**
     * True when all required KRA data is present on the order.
     * Gate this before calling generate() or sendToCustomer().
     */
    public function canGenerate(Order $order): bool
    {
        return !is_null($order->kra_cu_number)
            && !is_null($order->kra_invoice_number)
            && !is_null($order->kra_validated_at);
    }

    /**
     * Emails the generated receipt PDF to the customer.
     * Silently skips if no email address is available (guest with no email).
     */
    public function sendToCustomer(Order $order): void
    {
        $email = $order->customerEmail();

        if (!$email) {
            Log::warning('KRA receipt: no customer email, skipping send', [
                'order_id' => $order->id,
            ]);
            return;
        }

        if (!$order->kra_receipt_path || !Storage::exists($order->kra_receipt_path)) {
            Log::warning('KRA receipt: PDF not found, skipping send', [
                'order_id' => $order->id,
                'path'     => $order->kra_receipt_path,
            ]);
            return;
        }

        $order->user
            ? $order->user->notify(new KraReceiptNotification($order))
            : \Illuminate\Support\Facades\Notification::route('mail', $email)
            ->notify(new KraReceiptNotification($order));

        Log::info('KRA receipt emailed to customer', [
            'order_id' => $order->id,
            'email'    => $email,
        ]);
    }

    // ================================================================
    // Private helpers
    // ================================================================

    private function buildLineItems(Order $order): array
    {
        return $order->items->map(function ($item) {
            $snapshot = $item->product_snapshot ?? [];
            return [
                'name'       => $snapshot['name'] ?? 'Product',
                'sku'        => $snapshot['sku'] ?? '',
                'quantity'   => $item->quantity,
                'unit_price' => $item->unit_price_cents / 100,
                'total'      => $item->total_cents / 100,
                'tax'        => $item->unit_tax_cents / 100 * $item->quantity,
            ];
        })->toArray();
    }

    private function buildVatBreakdown(Order $order): array
    {
        $taxableAmount = $order->subtotal_cents / 100;
        $vatAmount     = $order->tax_cents / 100;

        return [
            'taxable_amount' => $taxableAmount,
            'vat_rate'       => $this->taxService->rateLabel(),
            'vat_amount'     => $vatAmount,
            'total'          => $order->total_cents / 100,
        ];
    }
}
