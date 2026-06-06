<?php

namespace App\Console\Commands;

use App\Enums\SapSyncStatus;
use App\Jobs\SyncOrderToSapJob;
use App\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('sap:resync {order? : Order ID or order number} {--failed : Resync all failed orders} {--stuck : Resync orders stuck in SYNCING/AWAITING_CU for over an hour}')]
#[Description('Manually re-trigger SAP sync for one or more orders')]
class SapResyncCommand extends Command
{
    public function handle(): int
    {
        if ($this->option('failed')) {
            return $this->resyncFailed();
        }

        if ($this->option('stuck')) {
            return $this->resyncStuck();
        }

        $identifier = $this->argument('order');

        if (! $identifier) {
            $this->error('Provide an order ID/number, or use --failed / --stuck.');

            return self::FAILURE;
        }

        $order = Order::where('id', $identifier)
            ->orWhere('order_number', $identifier)
            ->first();

        if (! $order) {
            $this->error("Order [{$identifier}] not found.");

            return self::FAILURE;
        }

        $this->dispatchAndReport(collect([$order]));

        return self::SUCCESS;
    }

    private function resyncFailed(): int
    {
        $orders = Order::where('sap_sync_status', SapSyncStatus::FAILED)->get();

        if ($orders->isEmpty()) {
            $this->info('No failed SAP orders found.');

            return self::SUCCESS;
        }

        $this->dispatchAndReport($orders);

        return self::SUCCESS;
    }

    private function resyncStuck(): int
    {
        $orders = Order::whereIn('sap_sync_status', [SapSyncStatus::SYNCING, SapSyncStatus::AWAITING_CU])
            ->where('updated_at', '<', now()->subHour())
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No stuck SAP orders found.');

            return self::SUCCESS;
        }

        $this->dispatchAndReport($orders);

        return self::SUCCESS;
    }

    /** @param Collection<int, Order> $orders */
    private function dispatchAndReport(Collection $orders): void
    {
        foreach ($orders as $order) {
            $order->update(['sap_sync_status' => SapSyncStatus::PENDING, 'sap_sync_attempts' => 0]);
            SyncOrderToSapJob::dispatch($order);
            $this->line("Queued: {$order->order_number}");
        }

        $this->info("Dispatched {$orders->count()} job(s) to the [sap] queue.");
    }
}
