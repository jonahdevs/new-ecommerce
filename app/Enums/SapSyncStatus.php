<?php

namespace App\Enums;

enum SapSyncStatus: string
{
    case PENDING = 'pending';        // Paid, job not yet dispatched
    case SYNCING = 'syncing';        // SyncOrderToSapJob running — POST /api/invoice/create
    case AWAITING_CU = 'awaiting_cu'; // Invoice created in SAP, waiting for KRA CU number
    case COMPLETED = 'completed';    // CU number received and receipt generated
    case FAILED = 'failed';          // Exhausted all retries — admin alert sent
    case RETURNED = 'returned';      // SAP notified us the order was returned

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending sync',
            self::SYNCING => 'Syncing with ERP',
            self::AWAITING_CU => 'Awaiting KRA validation',
            self::COMPLETED => 'KRA validated',
            self::FAILED => 'Sync failed',
            self::RETURNED => 'Returned in SAP',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::SYNCING => 'blue',
            self::AWAITING_CU => 'violet',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::RETURNED => 'zinc',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::RETURNED]);
    }
}
