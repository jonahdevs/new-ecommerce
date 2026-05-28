<?php

namespace App\Jobs;

use App\Enums\SapSyncStatus;
use App\Models\Order;
use App\Notifications\SapSyncFailedNotification;
use App\Services\Sap\KraReceiptService;
use App\Services\Sap\SapApiException;
use App\Services\Sap\SapIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ValidateSapInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts before the job is marked as permanently failed.
     */
    public int $tries = 3;

    /**
     * Backoff intervals in seconds between retry attempts.
     */
    public array $backoff = [60, 300, 900];

    /**
     * Allow up to 5 minutes — the validate call blocks until SAP finishes
     * KRA validation, which can take significantly longer than a normal API call.
     */
    public int $timeout = 300;

    public function __construct(
        public Order $order
    ) {
        $this->onQueue('sap');
    }

    // -------------------------------------------------------
    // Phase 2 of 2: POST /api/invoice/validate/{docEntry}
    //
    // Blocks until SAP returns the cuNumber from KRA validation.
    // On success, stores the cuNumber, moves order to CU_RECEIVED,
    // and generates the KRA tax receipt PDF immediately.
    //
    // The KRA webhook remains a fallback — if it arrives while this job
    // is running, the idempotency check below prevents double-processing.
    // -------------------------------------------------------
    public function handle(SapIntegrationService $sap, KraReceiptService $receipts): void
    {
        $fresh = $this->order->fresh();

        // Idempotency — the KRA webhook may have already delivered the cuNumber
        // before this job ran (or between retries). Nothing left to do.
        if ($fresh->sap_sync_status === SapSyncStatus::CU_RECEIVED) {
            Log::info('SAP validate skipped — already CU_RECEIVED', [
                'order_id' => $this->order->id,
            ]);

            return;
        }

        Log::info('SAP invoice validation started', [
            'order_id' => $this->order->id,
            'sap_doc_entry' => $fresh->sap_doc_entry,
            'attempt' => $this->attempts(),
        ]);

        try {
            $result = $sap->validateInvoice($fresh);
        } catch (SapApiException $e) {
            if (! $e->isRetryable()) {
                Log::error('SAP validate aborted — non-retryable error', [
                    'order_id' => $this->order->id,
                    'http_status' => $e->httpStatus,
                    'error' => $e->getMessage(),
                ]);
                $this->fail($e);

                return;
            }

            throw $e;
        }

        $fresh->update([
            'kra_cu_number' => $result->cuNumber,
            'kra_validated_at' => now(),
            'sap_sync_status' => SapSyncStatus::CU_RECEIVED,
        ]);

        activity()
            ->performedOn($fresh)
            ->withProperties([
                'kra_cu_number' => $result->cuNumber,
                'sap_doc_entry' => $result->docEntry,
                'attempt' => $this->attempts(),
            ])
            ->log('sap_kra_validated');

        Log::info('SAP invoice validated — cuNumber stored', [
            'order_id' => $this->order->id,
            'kra_cu_number' => $result->cuNumber,
        ]);

        try {
            $receipts->generate($fresh->fresh());
        } catch (\Throwable $e) {
            // Receipt failure must not fail the job — the order is already
            // fully validated. The admin can regenerate the receipt manually.
            Log::error('KRA receipt generation failed after validate', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------
    // Permanent failure handler — called after all $tries exhausted.
    // The KRA webhook is still active and may deliver the cuNumber later.
    // -------------------------------------------------------
    public function failed(\Throwable $exception): void
    {
        $fresh = $this->order->fresh();

        // Webhook may have resolved this between retries
        if ($fresh->sap_sync_status === SapSyncStatus::CU_RECEIVED) {
            return;
        }

        if ($fresh->sap_sync_status === SapSyncStatus::FAILED) {
            return;
        }

        Log::error('SAP invoice validation permanently failed', [
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'error' => $exception->getMessage(),
        ]);

        $this->order->update([
            'sap_sync_status' => SapSyncStatus::FAILED,
            'sap_sync_error' => $exception->getMessage(),
        ]);

        activity()
            ->performedOn($this->order)
            ->withProperties([
                'error' => $exception->getMessage(),
                'attempts' => $this->tries,
            ])
            ->log('sap_validate_failed');

        Notification::route('mail', config('mail.from.address'))
            ->notify(new SapSyncFailedNotification($this->order, $exception));
    }
}
