<?php

namespace App\Logistics\DTOs;

use App\Enums\ShipmentStatus;

final class TrackingResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?ShipmentStatus $status = null,
        public readonly ?string $statusDescription = null,
        public readonly ?\DateTimeInterface $estimatedDeliveryAt = null,
        public readonly array $events = [],  // [{timestamp, description, location}]
        public readonly array $rawPayload = [],
        public readonly ?string $error = null,
    ) {}

    public static function failed(string $reason): self
    {
        return new self(success: false, error: $reason);
    }
}
