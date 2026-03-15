<?php

namespace App\Console\Commands;

use App\Services\QuotationService;
use Illuminate\Console\Command;

class ExpireQuotations extends Command
{
    // =========================================================================
    //  Artisan command: php artisan quotations:expire
    //
    //  Finds all QUOTE_SENT quotations whose expires_at is in the past
    //  and transitions them to QUOTE_EXPIRED.
    //
    //  Designed to run hourly via the scheduler in routes/console.php.
    //  All transition logic and error handling lives in
    //  QuotationService::expireOverdue() — this command is just the entry point.
    //
    //  Exit codes:
    //    0 → success (even if zero quotations were expired)
    //    1 → unexpected exception
    // =========================================================================

    protected $signature = 'quotations:expire
                            {--dry-run : List expired quotations without transitioning them}';

    protected $description = 'Expire QUOTE_SENT quotations whose validity period has passed';

    public function handle(QuotationService $service): int
    {
        $this->info('Checking for expired quotations...');

        //  Dry run mode
        //
        // Run with --dry-run to preview which quotations would be expired
        // without actually transitioning them. Useful for testing and auditing.
        //
        // Example: php artisan quotations:expire --dry-run

        if ($this->option('dry-run')) {
            return $this->runDryRun();
        }

        //  Live run

        try {
            $count = $service->expireOverdue();

            if ($count === 0) {
                $this->info('No expired quotations found.');
            } else {
                $this->info("Expired {$count} quotation(s) successfully.");
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Failed to expire quotations: {$e->getMessage()}");

            logger()->error('ExpireQuotations command failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    // =========================================================================
    //  Dry run — preview without transitioning
    //
    //  Queries the same conditions as QuotationService::expireOverdue()
    //  and outputs a table of what would be expired.
    // =========================================================================

    private function runDryRun(): int
    {
        $this->warn('DRY RUN — no changes will be made.');

        $quotations = \App\Models\Order::where('document_type', 'quotation')
            ->where('status', \App\Enums\OrdersStatus::QUOTE_SENT->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with('user')
            ->get();

        if ($quotations->isEmpty()) {
            $this->info('No expired quotations found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$quotations->count()} quotation(s) that would be expired:");

        $this->table(
            ['Reference', 'Customer', 'Type', 'Expired At'],
            $quotations->map(fn($q) => [
                $q->reference,
                $q->user?->name ?? '—',
                ucfirst($q->quotation_type ?? '—'),
                $q->expires_at->format('M d, Y H:i'),
            ])
        );

        return self::SUCCESS;
    }
}
