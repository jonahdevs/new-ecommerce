<?php

namespace App\Enums;

enum SapSyncStatus: string
{
    case PENDING = 'pending';      // Order paid, job not yet dispatched or queued
    case SYNCING = 'syncing';      // Job is actively running
    case SYNCED = 'synced';       // All three SAP documents created successfully
    case FAILED = 'failed';       // Exhausted all retries — admin alert sent
    case CU_PENDING = 'cu_pending';   // SAP done, waiting for eTIMS/KRA webhook
    case CU_RECEIVED = 'cu_received';  // CU number received, receipt generated

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending sync',
            self::SYNCING => 'Syncing with ERP',
            self::SYNCED => 'Synced',
            self::FAILED => 'Sync failed',
            self::CU_PENDING => 'Awaiting KRA validation',
            self::CU_RECEIVED => 'KRA validated',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::SYNCING => 'blue',
            self::SYNCED => 'cyan',
            self::FAILED => 'red',
            self::CU_PENDING => 'purple',
            self::CU_RECEIVED => 'emerald',
        };
    }
}
