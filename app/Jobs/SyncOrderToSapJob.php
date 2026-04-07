<?php

namespace App\Jobs;

use App\Enums\SapSyncStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\SapSyncFailedNotification;
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
     * Each attempt uses the corresponding backoff interval below.
     */
    public int $tries = 3;

    /**
     * Backoff intervals in seconds between retry attempts.
     * Attempt 1 failure → wait 60s → attempt 2
     * Attempt 2 failure → wait 300s → attempt 3
     * Attempt 3 failure → failed() is called
     */
    public array $backoff = [60, 300, 900];

    /**
     * Prevent the job from running longer than 2 minutes.
     * SAP Service Layer calls should resolve well within this window.
     */
    public int $timeout = 120;

    public function __construct(
        public Order $order
    ) {}

    // -------------------------------------------------------
    // Main execution
    // Runs the full three-step SAP sync:
    //   Step 1: Create Sales Order   → stores sap_order_number
    //   Step 2: Create A/R Invoice   → stores sap_invoice_number
    //   Step 3: Record Payment       → stores sap_payment_number
    //
    // On success, status moves to cu_pending — we now wait for
    // the eTIMS webhook to deliver the CU number.
    //
    // On any exception the job throws so Laravel's retry
    // mechanism picks it up at the next backoff interval.
    // The failed() method below handles permanent failure.
    // -------------------------------------------------------
    public function handle(SapIntegrationService $sap): void
    {
        // Idempotency guard — if the job was dispatched more than once (e.g.
        // from both a webhook and a 3DS redirect), skip if already completed.
        $fresh = $this->order->fresh();
        $completedStatuses = [SapSyncStatus::CU_PENDING, SapSyncStatus::CU_RECEIVED, SapSyncStatus::SYNCED];
        if (in_array($fresh->sap_sync_status, $completedStatuses)) {
            Log::info('SAP sync skipped — already completed', [
                'order_id' => $this->order->id,
                'sap_sync_status' => $fresh->sap_sync_status->value,
            ]);

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

        // Single API call — the middleware handles Sales Order,
        // A/R Invoice and Incoming Payment creation internally.
        $result = $sap->syncOrder($this->order);

        // Move to CU_PENDING — SAP documents are created, now waiting for
        // the eTIMS/KRA webhook to deliver the CU number and generate the invoice.
        $this->order->update([
            'sap_order_number' => $result->documentEntry ?: $result->documentNumber,
            'sap_invoice_number' => $result->documentNumber,
            'sap_sync_status' => SapSyncStatus::CU_PENDING,
            'sap_synced_at' => now(),
            'sap_sync_attempts' => $this->attempts(),
            'sap_sync_error' => null,
        ]);

        Log::info('SAP sync completed', [
            'order_id' => $this->order->id,
            'sap_document' => $result->documentNumber,
        ]);
    }

    // -------------------------------------------------------
    // Permanent failure handler
    // Called by Laravel after all $tries are exhausted.
    // Marks the order as failed and alerts administrators.
    // -------------------------------------------------------
    public function failed(\Throwable $exception): void
    {
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

        // Alert all admin users so they can manually investigate
        Notification::send(
            User::role('admin')->get(),
            new SapSyncFailedNotification($this->order, $exception)
        );
    }
}
