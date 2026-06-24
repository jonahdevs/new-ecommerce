<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Atomic, gap-tolerant counters for human-facing reference numbers.
 *
 * Each key (e.g. "order:2026") maps to a row in `number_sequences`. {@see next()}
 * locks that row and increments it inside a transaction, so two concurrent
 * callers can never be handed the same value — unlike a `count() + 1` scan,
 * which races and silently reuses numbers after deletes.
 */
class NumberSequence
{
    /**
     * Increment and return the next value for the given sequence key. Gaps from
     * rolled-back transactions are acceptable — uniqueness, not contiguity, is
     * what reference numbers require.
     */
    public static function next(string $key): int
    {
        return DB::transaction(function () use ($key) {
            // Ensure the counter row exists without racing on its creation; the
            // primary key on `key` makes a concurrent duplicate insert a no-op.
            DB::table('number_sequences')->insertOrIgnore(['key' => $key, 'value' => 0]);

            $current = (int) DB::table('number_sequences')
                ->where('key', $key)
                ->lockForUpdate()
                ->value('value');

            $next = $current + 1;

            DB::table('number_sequences')
                ->where('key', $key)
                ->update(['value' => $next]);

            return $next;
        }, 3);
    }
}
