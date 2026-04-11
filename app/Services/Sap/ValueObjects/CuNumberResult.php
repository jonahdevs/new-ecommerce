<?php

namespace App\Services\Sap\ValueObjects;

use Carbon\Carbon;

/**
 * Carries the CU validation data delivered by the SAP webhook.
 */
readonly class CuNumberResult
{
    public function __construct(
        public string $cuNumber,
        public Carbon $validatedAt,
    ) {}
}
