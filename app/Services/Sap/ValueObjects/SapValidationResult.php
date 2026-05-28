<?php

namespace App\Services\Sap\ValueObjects;

/**
 * Returned by SapIntegrationService::validateInvoice().
 * Field names mirror SAP's actual response keys.
 */
readonly class SapValidationResult
{
    public function __construct(
        public string $cuNumber,   // KRA CU number — required for tax receipt
        public string $docEntry,   // SAP DocEntry echoed back by the validate endpoint
        public array $rawResponse, // Full SAP response payload for debugging
    ) {}
}
