<?php

namespace App\Logistics\DTOs;

final class QuoteResult
{
    public function __construct(
        public readonly bool $available,
        public readonly int $amountCents,
        public readonly ?string $etaLabel = null,
        public readonly ?string $currency = 'KES',
        public readonly ?string $error = null,
    ) {}

    public static function unavailable(string $reason): self
    {
        return new self(available: false, amountCents: 0, error: $reason);
    }
}
