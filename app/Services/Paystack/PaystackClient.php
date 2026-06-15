<?php

namespace App\Services\Paystack;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Paystack REST API (Initialize Transaction, Verify
 * Transaction, Create Refund). Authenticates with the secret key as a bearer
 * token. Amounts are exchanged in the currency subunit (cents for KES).
 */
class PaystackClient
{
    private const BASE_URL = 'https://api.paystack.co';

    public function __construct(private string $secretKey) {}

    /**
     * Initialize a transaction and return the decoded response. On success the
     * response carries data.access_code (used to resume the inline popup) and
     * data.reference.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initializeTransaction(array $payload): array
    {
        return $this->client()->post(self::BASE_URL.'/transaction/initialize', $payload)->json() ?? [];
    }

    /**
     * Verify a transaction by reference. This is the authoritative status check
     * before delivering value.
     *
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array
    {
        return $this->client()->get(self::BASE_URL.'/transaction/verify/'.$reference)->json() ?? [];
    }

    /**
     * Create a refund against a settled transaction. Returns the raw response so
     * the caller can distinguish a queued refund from a gateway rejection.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createRefund(array $payload): Response
    {
        return $this->client()->post(self::BASE_URL.'/refund', $payload);
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->secretKey)->acceptJson()->asJson();
    }
}
