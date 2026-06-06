<?php

namespace App\Services\Sap;

use RuntimeException;

class SapApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $sapError
     */
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly string $endpoint = '',
        public readonly array $sapError = [],
    ) {
        parent::__construct($message);
    }

    /**
     * Retryable errors: rate-limit, all 5xx, and connection failures (status 0).
     * Non-retryable: client errors (4xx except 429) — retrying won't change the outcome.
     */
    public function isRetryable(): bool
    {
        return $this->httpStatus === 0
            || $this->httpStatus === 429
            || $this->httpStatus >= 500;
    }

    public function isAuthError(): bool
    {
        return $this->httpStatus === 401;
    }
}
