<?php

namespace App\Services\Sap;

/**
 * Thrown by SapIntegrationService on any non-2xx SAP API response.
 *
 * isRetryable() tells SyncOrderToSapJob whether to let Laravel retry
 * the job or bail out immediately (e.g. 400 validation errors won't
 * fix themselves on retry).
 *
 * isAuthError() tells the service to re-authenticate before retrying.
 */
class SapApiException extends \Exception
{
    public function __construct(
        string $message,
        public readonly int $httpStatus,
        public readonly ?array $sapError = null,
        public readonly ?string $endpoint = null,
    ) {
        parent::__construct($message);
    }

    public function isRetryable(): bool
    {
        return in_array($this->httpStatus, [429, 500, 502, 503, 504])
            || $this->httpStatus === 0; // connection error / timeout
    }

    public function isAuthError(): bool
    {
        return $this->httpStatus === 401;
    }
}
