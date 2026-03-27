<?php

namespace App\Services\Sap\ValueObjects;

/**
 * Returned by each of the three SAP write operations.
 * Carries the SAP DocNum (human-readable) and DocEntry (internal PK).
 */
readonly class SapSyncResult
{
    public function __construct(
        public string $documentNumber, // DocNum  — stored on the order (e.g. sap_order_number)
        public string $documentEntry,  // DocEntry — SAP internal PK, used when linking documents
        public array  $rawResponse,    // Full SAP response payload for debugging
    ) {}
}
