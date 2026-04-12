<?php

namespace App\Console\Commands;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class PublishScheduledProducts extends Command
{
    // =========================================================================
    //  Artisan command: php artisan products:publish-scheduled
    //
    //  Finds all SCHEDULED products whose published_at is in the past (or now)
    //  and transitions them to PUBLISHED.
    //
    //  Designed to run every minute via the scheduler in routes/console.php
    //  so that publish times are respected within a ~1-minute window.
    //
    //  Exit codes:
    //    0 → success (even if zero products were published)
    //    1 → unexpected exception
    // =========================================================================

    protected $signature = 'products:publish-scheduled
                            {--dry-run : List eligible products without publishing them}';

    protected $description = 'Publish scheduled products whose publish time has arrived';

    public function handle(): int
    {
        $query = Product::where('status', ProductStatus::SCHEDULED)
            ->where('published_at', '<=', now())
            ->whereNotNull('published_at');

        if ($this->option('dry-run')) {
            return $this->runDryRun($query);
        }

        try {
            $products = $query->get();

            if ($products->isEmpty()) {
                $this->info('No scheduled products ready to publish.');

                return self::SUCCESS;
            }

            $count = 0;
            foreach ($products as $product) {
                $product->update(['status' => ProductStatus::PUBLISHED]);
                $count++;
            }

            $this->info("Published {$count} scheduled product(s).");

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Failed to publish scheduled products: {$e->getMessage()}");

            logger()->error('PublishScheduledProducts command failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    private function runDryRun(Builder $query): int
    {
        $this->warn('DRY RUN — no changes will be made.');

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->info('No scheduled products are ready to publish.');

            return self::SUCCESS;
        }

        $this->warn("Found {$products->count()} product(s) that would be published:");

        $this->table(
            ['ID', 'Name', 'SKU', 'Scheduled For'],
            $products->map(fn ($p) => [
                $p->id,
                $p->name,
                $p->sku ?? '—',
                $p->published_at->format('M d, Y H:i'),
            ])
        );

        return self::SUCCESS;
    }
}
