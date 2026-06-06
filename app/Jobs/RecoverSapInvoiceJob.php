<?php

namespace App\Jobs;

use App\Enums\SapSyncStatus;
use App\Models\Order;
use App\Notifications\SapSyncFailedNotification;
use App\Services\Sap\KraReceiptService;
use App\Services\Sap\SapApiException;
use App\Services\Sap\SapIntegrationService;
use App\Support\StaffRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Safety-net job dispatched with a 30-minute delay after SyncOrderToSapJob succeeds.
 * If SAP's webhook delivered the CU number before this job runs, it exits immediately.
 * Otherwise it polls the validate endpoint once to fetch the CU number directly.
 *
 * This replaces the old ValidateSapInvoiceJob which blocked a worker for up to 5 minutes.
 * Here we use a 4-minute HTTP timeout — the job either completes or fails cleanly,
 * never holding a worker open indefinitely.
 */
class RecoverSapInvoiceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public array $backoff = [300, 900];

    public int $timeout = 300;

    public function __construct(public readonly Order $order)
    {
        $this->onQueue('sap');
    }

    public function handle(SapIntegrationService $sap, KraReceiptService $receipts): void
    {
        $order = $this->order->fresh();

        // Webhook already delivered the CU number — nothing to do.
        if ($order->sap_sync_status === SapSyncStatus::COMPLETED) {
            Log::info('SAP recovery: CU number already received via webhook — skipping.', [
                'order_id' => $order->id,
            ]);

            return;
        }

        if (! $order->sap_doc_entry) {
            Log::warning('SAP recovery: no sap_doc_entry — cannot validate.', [
                'order_id' => $order->id,
            ]);

            return;
        }

        Log::info('SAP recovery: polling validate endpoint.', [
            'order_id' => $order->id,
            'sap_doc_entry' => $order->sap_doc_entry,
            'attempt' => $this->attempts(),
        ]);

        try {
            $result = $sap->validateInvoice($order);
        } catch (SapApiException $e) {
            if (! $e->isRetryable()) {
                $this->fail($e);

                return;
            }

            throw $e;
        }

        $order->update([
            'kra_cu_number' => $result->cuNumber,
            'kra_validated_at' => now(),
            'sap_sync_status' => SapSyncStatus::COMPLETED,
        ]);

        activity()->performedOn($order)
            ->withProperties(['kra_cu_number' => $result->cuNumber])
            ->log('sap_kra_validated');

        Log::info('SAP recovery: CU number stored.', [
            'order_id' => $order->id,
            'cu_number' => $result->cuNumber,
        ]);

        $receipts->generate($order->fresh());
    }

    public function failed(\Throwable $exception): void
    {
        $order = $this->order->fresh();

        // Webhook may have resolved this between retries.
        if ($order->sap_sync_status === SapSyncStatus::COMPLETED) {
            return;
        }

        if ($order->sap_sync_status === SapSyncStatus::FAILED) {
            return;
        }

        Log::error('SAP recovery: permanently failed.', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'error' => $exception->getMessage(),
        ]);

        $order->update([
            'sap_sync_status' => SapSyncStatus::FAILED,
            'sap_sync_error' => $exception->getMessage(),
        ]);

        activity()->performedOn($order)
            ->withProperties(['error' => $exception->getMessage()])
            ->log('sap_validate_failed');

        Notification::send(
            StaffRecipients::for('orders.manage'),
            new SapSyncFailedNotification($order, $exception),
        );
    }
}
