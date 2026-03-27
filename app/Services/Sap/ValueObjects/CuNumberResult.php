<?php

namespace App\Services\Sap\ValueObjects;

/**
 * Returned by SapIntegrationService::pollCuNumber() when the CU number
 * is available. Also used by SapWebhookHandler to pass structured data
 * to the order update logic.
 */
readonly class CuNumberResult
{
    public function __construct(
        public string $cuNumber,
        public ?string $kraInvoiceNumber,
        public \Carbon\Carbon $validatedAt,
    ) {}
}
