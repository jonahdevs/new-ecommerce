<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Events\QuoteUpdated;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevertAbandonedQuotePayments extends Command
{
    // =========================================================================
    //  Artisan command: php artisan quotations:revert-abandoned-payments
    //
    //  Finds quote-originated orders that are still PENDING payment after their
    //  payment window (expires_at) has passed, then:
    //    1. Cancels the order and its payment record
    //    2. Reverts the quote back to SENT so the customer can retry payment
    //
    //  The payment window is set to 30 minutes when the order is created in
    //  QuotationService::convertToOrder(). This command runs every 5 minutes
    //  so the revert happens promptly after abandonment.
    //
    //  Exit codes:
    //    0 → success (even if zero orders were reverted)
    //    1 → unexpected exception
    // =========================================================================

    protected $signature = 'quotations:revert-abandoned-payments
                            {--dry-run : List eligible orders without reverting them}';

    protected $description = 'Cancel expired unpaid quote orders and revert their quotes to SENT so customers can retry';

    public function handle(): int
    {
        $query = Order::whereNotNull('quote_id')
            ->where('status', OrderStatus::PENDING)
            ->where('payment_status', PaymentStatus::PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with(['quote', 'payment']);

        if ($this->option('dry-run')) {
            return $this->runDryRun($query);
        }

        try {
            $orders = $query->get();

            if ($orders->isEmpty()) {
                $this->info('No abandoned quote payments found.');

                return self::SUCCESS;
            }

            $reverted = 0;
            $failed = 0;

            foreach ($orders as $order) {
                try {
                    DB::transaction(function () use ($order) {
                        // Cancel the payment record
                        $order->payment?->update(['status' => PaymentStatus::CANCELLED]);

                        // Cancel the order
                        $order->update([
                            'status' => OrderStatus::CANCELLED,
                            'payment_status' => PaymentStatus::CANCELLED,
                        ]);

                        $order->statusHistories()->create([
                            'from_status' => OrderStatus::PENDING->value,
                            'to_status' => OrderStatus::CANCELLED->value,
                            'changed_by_user_id' => null,
                            'changed_by_type' => 'system',
                            'notes' => 'Payment window expired without completion. Order cancelled; quote reverted to allow customer to retry.',
                        ]);

                        // Revert the quote back to SENT so the customer can pay again
                        $quote = $order->quote;

                        $quote->update([
                            'status' => QuoteStatus::SENT,
                            'accepted_at' => null,
                        ]);

                        $quote->statusHistories()->create([
                            'from_status' => QuoteStatus::ACCEPTED->value,
                            'to_status' => QuoteStatus::SENT->value,
                            'changed_by_user_id' => null,
                            'changed_by_type' => 'system',
                            'notes' => 'Payment window expired. Quote reverted to SENT so customer can retry payment.',
                        ]);

                        // Notify the customer in real time if they are on the quote page
                        QuoteUpdated::dispatch($quote, 'status');
                    });

                    $reverted++;

                    Log::info('Abandoned quote payment reverted.', [
                        'order_id' => $order->id,
                        'order_reference' => $order->reference,
                        'quote_id' => $order->quote_id,
                        'quote_reference' => $order->quote?->reference,
                    ]);

                } catch (\Throwable $e) {
                    $failed++;

                    Log::error('Failed to revert abandoned quote payment.', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Reverted {$reverted} abandoned quote payment(s).".($failed ? " {$failed} failed — see logs." : ''));

            return $failed > 0 ? self::FAILURE : self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Command failed: {$e->getMessage()}");

            Log::error('RevertAbandonedQuotePayments command failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    private function runDryRun(Builder $query): int
    {
        $this->warn('DRY RUN — no changes will be made.');

        $orders = $query->with('user')->get();

        if ($orders->isEmpty()) {
            $this->info('No abandoned quote payments found.');

            return self::SUCCESS;
        }

        $this->warn("Found {$orders->count()} order(s) that would be reverted:");

        $this->table(
            ['Order', 'Quote', 'Customer', 'Expired At'],
            $orders->map(fn ($o) => [
                $o->reference,
                $o->quote?->reference ?? '—',
                $o->user?->name ?? 'Guest',
                $o->expires_at->format('M d, Y H:i'),
            ])
        );

        return self::SUCCESS;
    }
}
