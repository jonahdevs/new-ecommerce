<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('quotes:expire')]
#[Description('Mark sent quotes whose validity window has lapsed as expired.')]
class ExpireQuotes extends Command
{
    public function handle(): int
    {
        $quotes = Quote::query()
            ->whereIn('status', [QuoteStatus::SENT, QuoteStatus::AWAITING_APPROVAL])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($quotes as $quote) {
            $from = $quote->status;
            $quote->update(['status' => QuoteStatus::EXPIRED]);
            $quote->recordStatusChange($from, QuoteStatus::EXPIRED, 'Auto-expired: validity window lapsed.');
        }

        $count = $quotes->count();

        $this->info($count === 0
            ? 'No quotes were due to expire.'
            : "Expired {$count} quote(s).");

        return self::SUCCESS;
    }
}
