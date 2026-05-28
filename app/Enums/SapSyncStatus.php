<?php

namespace App\Enums;

enum SapSyncStatus: string
{
    case PENDING = 'pending';          // Order paid, job not yet dispatched or queued
    case SYNCING = 'syncing';          // Create invoice job is actively running
    case VALIDATING = 'validating';    // Invoice created in SAP, validate job running to fetch cuNumber
    case FAILED = 'failed';            // Exhausted all retries — admin alert sent
    case CU_PENDING = 'cu_pending';    // Legacy: SAP done, waiting for KRA webhook
    case CU_RECEIVED = 'cu_received';  // CU number received, receipt generated
    case RETURNED = 'returned';        // SAP notified us the order was returned

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending sync',
            self::SYNCING => 'Syncing with ERP',
            self::VALIDATING => 'Validating with KRA',
            self::FAILED => 'Sync failed',
            self::CU_PENDING => 'Awaiting KRA validation',
            self::CU_RECEIVED => 'KRA validated',
            self::RETURNED => 'Returned in SAP',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::SYNCING => 'blue',
            self::VALIDATING => 'violet',
            self::FAILED => 'red',
            self::CU_PENDING => 'purple',
            self::CU_RECEIVED => 'emerald',
            self::RETURNED => 'zinc',
        };
    }
}
