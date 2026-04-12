<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\InventoryService;
use App\Settings\OrderSettings;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class CancelUnpaidOrders extends Command
{
    // =========================================================================
    //  Artisan command: php artisan orders:cancel-unpaid
    //
    //  Cancels PENDING orders that have not been paid within the configured
    //  auto_cancel_hours window (from OrderSettings).
    //
    //  Only runs when auto_cancel_unpaid is enabled in OrderSettings.
    //  Designed to run hourly via the scheduler in routes/console.php.
    //
    //  Exit codes:
    //    0 → success (even if zero orders were cancelled)
    //    1 → unexpected exception
    // =========================================================================

    protected $signature = 'orders:cancel-unpaid
                            {--dry-run : List eligible orders without cancelling them}';

    protected $description = 'Cancel unpaid PENDING orders that exceed the configured auto-cancel window';

    public function handle(OrderSettings $settings, InventoryService $inventory): int
    {
        if (! $settings->auto_cancel_unpaid) {
            $this->info('Auto-cancel is disabled in OrderSettings. Skipping.');

            return self::SUCCESS;
        }

        $hours = $settings->auto_cancel_hours;
        $cutoff = now()->subHours($hours);

        $query = Order::where('status', OrderStatus::PENDING)
            ->where('payment_status', PaymentStatus::PENDING)
            ->where('created_at', '<=', $cutoff);

        if ($this->option('dry-run')) {
            return $this->runDryRun($query, $hours);
        }

        try {
            $orders = $query->get();

            if ($orders->isEmpty()) {
                $this->info('No unpaid orders found within the cancellation window.');

                return self::SUCCESS;
            }

            $count = 0;
            foreach ($orders as $order) {
                // Release any held stock before cancelling — covers M-Pesa orders
                // where the STK push was sent but the user never completed payment.
                $inventory->releaseReservation($order);

                $order->update(['status' => OrderStatus::CANCELLED]);

                $order->statusHistories()->create([
                    'from_status' => OrderStatus::PENDING->value,
                    'to_status' => OrderStatus::CANCELLED->value,
                    'changed_by_user_id' => null,
                    'changed_by_type' => 'system',
                    'notes' => "Auto-cancelled after {$hours} hours without payment.",
                ]);

                $count++;
            }

            $this->info("Cancelled {$count} unpaid order(s).");

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Failed to cancel unpaid orders: {$e->getMessage()}");

            logger()->error('CancelUnpaidOrders command failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    private function runDryRun(Builder $query, int $hours): int
    {
        $this->warn('DRY RUN — no changes will be made.');

        $orders = $query->with('user')->get();

        if ($orders->isEmpty()) {
            $this->info("No unpaid orders older than {$hours} hour(s) found.");

            return self::SUCCESS;
        }

        $this->warn("Found {$orders->count()} order(s) that would be cancelled:");

        $this->table(
            ['Reference', 'Customer', 'Created At'],
            $orders->map(fn ($o) => [
                $o->reference,
                $o->user?->name ?? 'Guest',
                $o->created_at->format('M d, Y H:i'),
            ])
        );

        return self::SUCCESS;
    }
}
