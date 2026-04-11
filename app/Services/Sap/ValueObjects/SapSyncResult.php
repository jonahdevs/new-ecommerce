<?php

namespace App\Services\Sap\ValueObjects;

/**
 * Returned by SapIntegrationService::syncOrder().
 * Field names mirror SAP's actual response keys.
 */
readonly class SapSyncResult
{
    public function __construct(
        public string $docEntry,    // SAP DocEntry — internal SAP primary key (always present)
        public ?string $docNumber,   // SAP DocNum   — human-readable number (may be absent)
        public array $rawResponse, // Full SAP response payload for debugging
    ) {}
}
