<?php

namespace App\Services\Sap\ValueObjects;

final readonly class SapSyncResult
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public string $docEntry,
        public ?string $docNumber,
        public array $rawResponse = [],
    ) {}
}
