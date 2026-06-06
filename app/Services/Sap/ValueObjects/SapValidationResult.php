<?php

namespace App\Services\Sap\ValueObjects;

final readonly class SapValidationResult
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public string $cuNumber,
        public string $docEntry,
        public array $rawResponse = [],
    ) {}
}
