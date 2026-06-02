<?php

namespace App\Logistics\DTOs;

final class BookingResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $bookingRef = null,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $trackingUrl = null,
        public readonly array $rawPayload = [],
        public readonly ?string $error = null,
    ) {}

    public static function failed(string $reason): self
    {
        return new self(success: false, error: $reason);
    }
}
