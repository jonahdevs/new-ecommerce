<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup expired orders and their inventory reservations
 * Should be scheduled to run every 5-10 minutes
 */
class CleanupExpiredOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(InventoryService $inventoryService): void
    {
        $startTime = now();
        $processedCount = 0;

        Log::info('Starting expired orders cleanup');

        // Find orders with expired payments
        Order::where('status', 'pending')
            ->whereHas('payment', function ($query) {
                $query->where('status', 'pending')
                    ->where('expires_at', '<', now());
            })
            ->chunk(100, function ($orders) use ($inventoryService, &$processedCount) {
                foreach ($orders as $order) {
                    try {
                        // Release inventory reservations
                        $inventoryService->releaseReservation($order);

                        // Mark payment as abandoned (expired is not a valid PaymentStatus)
                        if ($order->payment) {
                            $order->payment->update([
                                'status' => PaymentStatus::ABANDONED,
                                'meta' => array_merge($order->payment->meta ?? [], [
                                    'expired_at' => now()->toISOString(),
                                    'cleanup_by' => 'scheduled_job',
                                ]),
                            ]);
                        }

                        // Cancel the order and sync its payment_status
                        $order->update([
                            'status' => OrderStatus::CANCELLED,
                            'payment_status' => PaymentStatus::ABANDONED,
                        ]);

                        // Log status change
                        $order->statusHistories()->create([
                            'from_status' => OrderStatus::PENDING->value,
                            'to_status' => OrderStatus::CANCELLED->value,
                            'changed_by_type' => 'system',
                            'notes' => 'Payment link expired — order automatically cancelled.',
                            'metadata' => [
                                'job' => self::class,
                                'executed_at' => now()->toISOString(),
                            ],
                        ]);

                        $processedCount++;

                        Log::info('Order expired and cleaned up', [
                            'order_id' => $order->id,
                            'reference' => $order->reference,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to cleanup expired order', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            });

        // Also cleanup orphaned reservations (in case of edge cases)
        $orphanedReservations = $inventoryService->cleanupExpiredReservations();

        $duration = now()->diffInSeconds($startTime);

        Log::info('Expired orders cleanup completed', [
            'orders_processed' => $processedCount,
            'orphaned_reservations_cleaned' => $orphanedReservations,
            'duration_seconds' => $duration,
        ]);
    }
}
