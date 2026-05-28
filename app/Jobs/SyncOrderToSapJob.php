<?php

namespace App\Jobs;

use App\Enums\SapSyncStatus;
use App\Models\Order;
use App\Notifications\SapSyncFailedNotification;
use App\Services\Sap\SapApiException;
use App\Services\Sap\SapIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SyncOrderToSapJob implements ShouldQueue
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
     * Prevent the job from running longer than 2 minutes.
     * The create call should resolve well within this window.
     */
    public int $timeout = 120;

    public function __construct(
        public Order $order
    ) {
        $this->onQueue('sap');
    }

    // -------------------------------------------------------
    // Phase 1 of 2: POST /api/invoice/create
    //
    // On success, status moves to VALIDATING and ValidateSapInvoiceJob
    // is dispatched on the same queue to fetch the cuNumber.
    //
    // Saving sap_doc_entry before dispatching validate guards against
    // partial failure: if this job crashes after create but before the
    // dispatch, the idempotency check below catches it on retry and
    // skips straight to dispatching validate again.
    // -------------------------------------------------------
    public function handle(SapIntegrationService $sap): void
    {
        $fresh = $this->order->fresh();

        // Already fully complete (validate job finished or webhook arrived first)
        if ($fresh->sap_sync_status === SapSyncStatus::CU_RECEIVED) {
            Log::info('SAP sync skipped — already CU_RECEIVED', [
                'order_id' => $this->order->id,
            ]);

            return;
        }

        // Create already succeeded on a previous attempt but the validate dispatch
        // was lost — skip create and re-dispatch validate to avoid a duplicate invoice.
        if ($fresh->sap_doc_entry && $fresh->sap_sync_status === SapSyncStatus::VALIDATING) {
            Log::info('SAP create already done — re-dispatching validate job', [
                'order_id' => $this->order->id,
                'sap_doc_entry' => $fresh->sap_doc_entry,
            ]);

            ValidateSapInvoiceJob::dispatch($fresh);

            return;
        }

        Log::info('SAP sync started', [
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'attempt' => $this->attempts(),
        ]);

        $this->order->update([
            'sap_sync_status' => SapSyncStatus::SYNCING,
        ]);

        try {
            $result = $sap->syncOrder($this->order);
        } catch (SapApiException $e) {
            if (! $e->isRetryable()) {
                Log::error('SAP sync aborted — non-retryable error', [
                    'order_id' => $this->order->id,
                    'http_status' => $e->httpStatus,
                    'error' => $e->getMessage(),
                ]);
                $this->fail($e);

                return;
            }

            throw $e;
        }

        // Save doc refs before dispatching validate — if the dispatch below were to
        // fail, the stored sap_doc_entry + VALIDATING status lets the next retry
        // skip create entirely and go straight to re-dispatching validate.
        $this->order->update([
            'sap_doc_number' => $result->docNumber,
            'sap_doc_entry' => $result->docEntry,
            'sap_sync_status' => SapSyncStatus::VALIDATING,
            'sap_synced_at' => now(),
            'sap_sync_attempts' => $this->attempts(),
            'sap_sync_error' => null,
        ]);

        activity()
            ->performedOn($this->order)
            ->withProperties([
                'sap_doc_number' => $result->docNumber,
                'sap_doc_entry' => $result->docEntry,
                'attempt' => $this->attempts(),
            ])
            ->log('sap_sync_completed');

        Log::info('SAP invoice created — dispatching validate job', [
            'order_id' => $this->order->id,
            'sap_doc_entry' => $result->docEntry,
        ]);

        ValidateSapInvoiceJob::dispatch($this->order->fresh());
    }

    // -------------------------------------------------------
    // Permanent failure handler — called after all $tries exhausted.
    // Only covers create failures; validate failures have their own handler.
    // -------------------------------------------------------
    public function failed(\Throwable $exception): void
    {
        $fresh = $this->order->fresh();
        if ($fresh->sap_sync_status === SapSyncStatus::FAILED) {
            return;
        }

        Log::error('SAP sync permanently failed', [
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'error' => $exception->getMessage(),
        ]);

        $this->order->update([
            'sap_sync_status' => SapSyncStatus::FAILED,
            'sap_sync_attempts' => $this->tries,
            'sap_sync_error' => $exception->getMessage(),
        ]);

        activity()
            ->performedOn($this->order)
            ->withProperties([
                'error' => $exception->getMessage(),
                'attempts' => $this->tries,
            ])
            ->log('sap_sync_failed');

        Notification::route('mail', config('mail.from.address'))
            ->notify(new SapSyncFailedNotification($this->order, $exception));
    }
}
