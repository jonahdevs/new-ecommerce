<?php

namespace App\Services\Sap;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP wrapper for the SAP middleware.
 * Handles auth headers, SSL config, and maps HTTP failures to SapApiException
 * so callers never deal with raw Guzzle exceptions.
 */
class SapClient
{
    public function __construct(private readonly SapConfig $config) {}

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws SapApiException
     */
    public function post(string $path, array $payload = [], int $timeoutSeconds = 60): Response
    {
        try {
            $http = Http::withOptions(['verify' => $this->config->verifySsl()])
                ->timeout($timeoutSeconds);

            if ($apiKey = $this->config->apiKey()) {
                $http = $http->withHeaders(['x-api-key' => $apiKey]);
            }

            return $http->post("{$this->config->baseUrl()}{$path}", $payload);
        } catch (ConnectionException $e) {
            throw new SapApiException(
                message: 'Connection to SAP failed: '.$e->getMessage(),
                httpStatus: 0,
                endpoint: $path,
            );
        }
    }
}
