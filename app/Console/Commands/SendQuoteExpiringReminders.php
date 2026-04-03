<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\QuotationService;
use App\Settings\CustomerNotificationSettings;
use Illuminate\Console\Command;

class SendQuoteExpiringReminders extends Command
{
    // =========================================================================
    //  Artisan command: php artisan quotations:remind-expiring
    //
    //  Finds all SENT quotations expiring within the configured reminder
    //  window and sends reminder emails to customers.
    //
    //  Designed to run daily via the scheduler in routes/console.php.
    //  All logic and settings checks live in QuotationService::sendExpiringReminders()
    //
    //  Exit codes:
    //    0 → success (even if zero reminders were sent)
    //    1 → unexpected exception
    // =========================================================================

    protected $signature = 'quotations:remind-expiring
                            {--dry-run : List quotes that would receive reminders without sending}';

    protected $description = 'Send reminder emails for quotations expiring soon';

    public function handle(QuotationService $service): int
    {
        $this->info('Checking for expiring quotations...');

        if ($this->option('dry-run')) {
            return $this->runDryRun();
        }

        try {
            $count = $service->sendExpiringReminders();

            if ($count === 0) {
                $this->info('No expiring quotations need reminders.');
            } else {
                $this->info("Sent {$count} expiring reminder(s) successfully.");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to send reminders: {$e->getMessage()}");

            logger()->error('SendQuoteExpiringReminders command failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    private function runDryRun(): int
    {
        $this->warn('DRY RUN — no emails will be sent.');

        $settings = app(CustomerNotificationSettings::class);

        if (! $settings->quote_expiring_reminder) {
            $this->info('Quote expiring reminders are disabled in settings.');

            return self::SUCCESS;
        }

        $daysBeforeExpiry = $settings->quote_expiring_days;
        $reminderDate = now()->addDays($daysBeforeExpiry);

        $quotes = Quote::where('status', QuoteStatus::SENT)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $reminderDate)
            ->where('expires_at', '>', now())
            ->whereNull('reminder_sent_at')
            ->with('user')
            ->get();

        if ($quotes->isEmpty()) {
            $this->info('No quotations need expiring reminders.');

            return self::SUCCESS;
        }

        $this->warn("Found {$quotes->count()} quotation(s) that would receive reminders:");

        $this->table(
            ['Reference', 'Customer', 'Email', 'Expires At', 'Days Left'],
            $quotes->map(fn ($q) => [
                $q->reference,
                $q->customerName(),
                $q->customerEmail(),
                $q->expires_at->format('M d, Y'),
                $q->expires_at->diffInDays(now()),
            ])
        );

        return self::SUCCESS;
    }
}
