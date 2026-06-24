<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('db:monitor')]
#[Description('Display active transactions, lock waits, slow queries, and InnoDB health counters.')]
class DbMonitorCommand extends Command
{
    public function handle(): int
    {
        $this->showLockStats();
        $this->newLine();
        $this->showActiveTransactions();
        $this->newLine();
        $this->showLockWaits();
        $this->newLine();
        $this->showSlowStatements();

        return self::SUCCESS;
    }

    private function showLockStats(): void
    {
        $this->components->twoColumnDetail('<fg=yellow;options=bold>InnoDB Health</>');

        $raw = DB::select("SHOW GLOBAL STATUS WHERE Variable_name IN (
            'Innodb_row_lock_waits',
            'Innodb_row_lock_time_avg',
            'Innodb_row_lock_time_max',
            'Slow_queries',
            'Uptime',
            'Threads_connected'
        )");

        $stats = collect($raw)->mapWithKeys(fn ($s) => [$s->Variable_name => $s->Value]);
        $uptimeMins = round((int) $stats->get('Uptime', 0) / 60);

        $this->table(['Metric', 'Value'], [
            ['Uptime', "{$uptimeMins} min"],
            ['Active connections', $stats->get('Threads_connected', '—')],
            ['Row lock waits (lifetime)', $stats->get('Innodb_row_lock_waits', '—')],
            ['Avg lock wait (ms)', $stats->get('Innodb_row_lock_time_avg', '—')],
            ['Max lock wait (ms)', $stats->get('Innodb_row_lock_time_max', '—')],
            ['Slow queries (> long_query_time)', $stats->get('Slow_queries', '—')],
        ]);
    }

    private function showActiveTransactions(): void
    {
        $this->components->twoColumnDetail('<fg=yellow;options=bold>Active Transactions</>');

        $txns = DB::select('
            SELECT
                trx_id,
                trx_state,
                TIMESTAMPDIFF(SECOND, trx_started, NOW()) AS age_s,
                trx_rows_locked,
                trx_rows_modified,
                LEFT(trx_query, 80) AS query_snippet
            FROM information_schema.INNODB_TRX
            ORDER BY trx_started ASC
        ');

        if (empty($txns)) {
            $this->line('  <fg=green>No active transactions.</>');

            return;
        }

        $this->table(
            ['TRX ID', 'State', 'Age (s)', 'Locked', 'Modified', 'Query'],
            collect($txns)->map(fn ($t) => [
                $t->trx_id,
                $t->trx_state,
                $t->age_s,
                $t->trx_rows_locked,
                $t->trx_rows_modified,
                $t->query_snippet ?? '—',
            ])
        );
    }

    private function showLockWaits(): void
    {
        $this->components->twoColumnDetail('<fg=yellow;options=bold>Current Lock Waits</>');

        try {
            $waits = DB::select('
                SELECT
                    w.REQUESTING_ENGINE_TRANSACTION_ID AS waiting_trx,
                    w.BLOCKING_ENGINE_TRANSACTION_ID   AS blocking_trx,
                    wl.OBJECT_NAME                     AS table_name,
                    wl.INDEX_NAME                      AS index_name,
                    wl.LOCK_TYPE,
                    wl.LOCK_MODE
                FROM performance_schema.data_lock_waits w
                JOIN performance_schema.data_locks wl
                    ON w.REQUESTING_ENGINE_LOCK_ID = wl.ENGINE_LOCK_ID
                LIMIT 20
            ');

            if (empty($waits)) {
                $this->line('  <fg=green>No active lock waits.</>');

                return;
            }

            $this->table(
                ['Waiting TRX', 'Blocking TRX', 'Table', 'Index', 'Type', 'Mode'],
                collect($waits)->map(fn ($w) => [
                    $w->waiting_trx,
                    $w->blocking_trx,
                    $w->table_name,
                    $w->index_name ?? '—',
                    $w->LOCK_TYPE,
                    $w->LOCK_MODE,
                ])
            );
        } catch (\Throwable $e) {
            $this->line('  <comment>'.$e->getMessage().'</comment>');
        }
    }

    private function showSlowStatements(): void
    {
        $this->components->twoColumnDetail('<fg=yellow;options=bold>Slowest Query Patterns (avg > 100ms)</>');

        try {
            $slow = DB::select('
                SELECT
                    DIGEST_TEXT                       AS query,
                    COUNT_STAR                        AS executions,
                    ROUND(SUM_TIMER_WAIT  / 1e12, 3) AS total_s,
                    ROUND(AVG_TIMER_WAIT  / 1e12, 3) AS avg_s,
                    ROUND(MAX_TIMER_WAIT  / 1e12, 3) AS max_s,
                    SUM_ROWS_EXAMINED                 AS rows_examined,
                    SUM_NO_INDEX_USED                 AS no_index_count
                FROM performance_schema.events_statements_summary_by_digest
                WHERE SCHEMA_NAME = DATABASE()
                    AND AVG_TIMER_WAIT > 100000000000
                ORDER BY SUM_TIMER_WAIT DESC
                LIMIT 10
            ');

            if (empty($slow)) {
                $this->line('  <fg=green>No query patterns averaging > 100ms.</>');

                return;
            }

            $this->table(
                ['Query', 'Runs', 'Total (s)', 'Avg (s)', 'Max (s)', 'Rows Examined', 'No-Index'],
                collect($slow)->map(fn ($s) => [
                    str($s->query ?? '—')->limit(70),
                    $s->executions,
                    $s->total_s,
                    $s->avg_s,
                    $s->max_s,
                    number_format($s->rows_examined),
                    $s->no_index_count > 0 ? "{$s->no_index_count} !" : '0',
                ])
            );
        } catch (\Throwable $e) {
            $this->line('  <comment>'.$e->getMessage().'</comment>');
        }
    }
}
