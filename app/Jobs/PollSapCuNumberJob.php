<?php

namespace App\Jobs;

use App\Enums\SapSyncStatus;
use App\Models\Order;
use App\Services\Sap\SapIntegrationService;
use App\Services\Sap\KraReceiptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollSapCuNumberJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Poll up to 10 times before giving up.
     * 10 × 5-10 min intervals = roughly 1-2 hours of patience.
     */
    public int $tries = 10;

    public array $backoff = [300, 600, 900, 900, 900, 900, 900, 900, 900, 900];

    public int $timeout = 60;

    public function __construct(
        public Order $order
    ) {}

    public function handle(SapIntegrationService $sap, KraReceiptService $receipt): void
    {
        // If the webhook already delivered the CU number, nothing to do.
        if ($this->order->fresh()->sap_sync_status === SapSyncStatus::CU_RECEIVED) {
            Log::info('PollSapCuNumber: CU already received via webhook, skipping', [
                'order_id' => $this->order->id,
            ]);
            return;
        }

        if (!$this->order->sap_invoice_number) {
            Log::warning('PollSapCuNumber: no SAP invoice number on order, cannot poll', [
                'order_id' => $this->order->id,
            ]);
            return;
        }

        $result = $sap->pollCuNumber($this->order->sap_invoice_number);

        if (!$result) {
            // CU not ready yet — throw so Laravel retries at the next backoff interval
            throw new \RuntimeException(
                "CU number not yet available for invoice {$this->order->sap_invoice_number}"
            );
        }

        // CU number arrived — store it and generate the receipt
        $this->order->update([
            'kra_cu_number'      => $result->cuNumber,
            'kra_invoice_number' => $result->kraInvoiceNumber,
            'kra_validated_at'   => $result->validatedAt,
            'sap_sync_status'    => SapSyncStatus::CU_RECEIVED,
        ]);

        $receipt->generate($this->order);
        $receipt->sendToCustomer($this->order);

        Log::info('PollSapCuNumber: CU number retrieved via polling', [
            'order_id'      => $this->order->id,
            'kra_cu_number' => $result->cuNumber,
        ]);
    }
}
