<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payments:prune-payloads {--years=5 : Retention window in years}')]
#[Description('Clear raw gateway payloads on payments past the statutory retention window (Kenya DPA storage limitation).')]
class PrunePaymentPayloads extends Command
{
    /**
     * The raw gateway payload holds PII and is kept only for reconciliation and
     * dispute evidence. Kenya's DPA 2019 storage-limitation rule requires erasure
     * once the purpose lapses; the KRA 5-year tax-record floor is the default
     * window. Structured columns (reference, amount, status…) are retained.
     */
    public function handle(): int
    {
        $cutoff = now()->subYears((int) $this->option('years'));

        $pruned = Payment::query()
            ->whereNotNull('payload')
            ->where('created_at', '<', $cutoff)
            ->update(['payload' => null]);

        $this->info($pruned === 0
            ? 'No payment payloads were due for pruning.'
            : "Pruned the raw payload from {$pruned} payment(s) older than {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
