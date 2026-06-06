<?php

namespace App\Jobs;

use App\Enums\SapSyncStatus;
use App\Models\Order;
use App\Notifications\SapSyncFailedNotification;
use App\Services\Sap\SapApiException;
use App\Services\Sap\SapConfig;
use App\Services\Sap\SapIntegrationService;
use App\Support\StaffRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SyncOrderToSapJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $timeout = 120;

    public function __construct(public readonly Order $order)
    {
        $this->onQueue('sap');
    }

    /**
     * Phase 1: POST /api/invoice/create
     *
     * Saves sap_doc_entry before dispatching RecoverSapInvoiceJob so that if
     * this job crashes after the SAP call but before the dispatch, a retry
     * will skip the create and go straight to re-dispatching the recovery job.
     */
    public function handle(SapIntegrationService $sap): void
    {
        $order = $this->order->fresh();

        if ($order->sap_sync_status === SapSyncStatus::COMPLETED) {
            return;
        }

        // Create already succeeded on a previous attempt but the dispatch was lost.
        if ($order->sap_doc_entry && $order->sap_sync_status === SapSyncStatus::AWAITING_CU) {
            Log::info('SAP: create already done — re-dispatching recovery job.', [
                'order_id' => $order->id,
                'sap_doc_entry' => $order->sap_doc_entry,
            ]);

            RecoverSapInvoiceJob::dispatch($order)
                ->delay(now()->addMinutes(app(SapConfig::class)->recoveryDelayMinutes()));

            return;
        }

        Log::info('SAP: sync started.', ['order_id' => $order->id, 'attempt' => $this->attempts()]);

        $order->update(['sap_sync_status' => SapSyncStatus::SYNCING]);

        try {
            $result = $sap->syncOrder($order);
        } catch (SapApiException $e) {
            if (! $e->isRetryable()) {
                $this->fail($e);

                return;
            }

            throw $e;
        }

        // Persist doc refs before dispatching recovery — guards against partial failures.
        $order->update([
            'sap_doc_entry' => $result->docEntry,
            'sap_doc_number' => $result->docNumber,
            'sap_sync_status' => SapSyncStatus::AWAITING_CU,
            'sap_synced_at' => now(),
            'sap_sync_attempts' => $this->attempts(),
            'sap_sync_error' => null,
        ]);

        activity()->performedOn($order)
            ->withProperties(['sap_doc_entry' => $result->docEntry, 'attempt' => $this->attempts()])
            ->log('sap_sync_completed');

        // The webhook is the primary path for the CU number.
        // RecoverSapInvoiceJob fires after the delay as a safety net if no webhook arrives.
        RecoverSapInvoiceJob::dispatch($order->fresh())
            ->delay(now()->addMinutes(app(SapConfig::class)->recoveryDelayMinutes()));
    }

    public function failed(\Throwable $exception): void
    {
        $order = $this->order->fresh();

        if ($order->sap_sync_status === SapSyncStatus::FAILED) {
            return;
        }

        Log::error('SAP: sync permanently failed.', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'error' => $exception->getMessage(),
        ]);

        $order->update([
            'sap_sync_status' => SapSyncStatus::FAILED,
            'sap_sync_attempts' => $this->tries,
            'sap_sync_error' => $exception->getMessage(),
        ]);

        activity()->performedOn($order)
            ->withProperties(['error' => $exception->getMessage()])
            ->log('sap_sync_failed');

        Notification::send(
            StaffRecipients::for('orders.manage'),
            new SapSyncFailedNotification($order, $exception),
        );
    }
}
