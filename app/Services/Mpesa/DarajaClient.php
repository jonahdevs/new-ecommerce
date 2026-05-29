<?php

namespace App\Services\Mpesa;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around Safaricom's Daraja API (OAuth, STK Push, STK Query).
 * Reads credentials from config('services.mpesa').
 */
class DarajaClient
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private array $config) {}

    public static function fromConfig(): self
    {
        return new self(config('services.mpesa'));
    }

    public function baseUrl(): string
    {
        return ($this->config['env'] ?? 'sandbox') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Cached OAuth access token (Daraja tokens last ~3600s).
     */
    public function accessToken(): string
    {
        return Cache::remember('mpesa.access_token', now()->addMinutes(50), function (): string {
            $response = Http::withBasicAuth(
                (string) $this->config['consumer_key'],
                (string) $this->config['consumer_secret'],
            )->get($this->baseUrl().'/oauth/v1/generate', ['grant_type' => 'client_credentials']);

            $token = $response->json('access_token');

            if (! $response->successful() || ! $token) {
                throw new RuntimeException('Unable to obtain M-Pesa access token.');
            }

            return $token;
        });
    }

    /**
     * Initiate an STK Push (Lipa na M-Pesa Online) prompt.
     *
     * @return array<string, mixed>
     */
    public function stkPush(int $amount, string $phone, string $accountReference, string $callbackUrl): array
    {
        $timestamp = now()->format('YmdHis');

        return $this->client()->post($this->baseUrl().'/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $this->config['shortcode'],
            'Password' => $this->password($timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $this->config['shortcode'],
            'PhoneNumber' => $phone,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => 'Payment for '.$accountReference,
        ])->json();
    }

    /**
     * Query the status of a previously initiated STK Push.
     *
     * @return array<string, mixed>
     */
    public function stkQuery(string $checkoutRequestId): array
    {
        $timestamp = now()->format('YmdHis');

        return $this->client()->post($this->baseUrl().'/mpesa/stkpushquery/v1/query', [
            'BusinessShortCode' => $this->config['shortcode'],
            'Password' => $this->password($timestamp),
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ])->json();
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->accessToken())->acceptJson();
    }

    private function password(string $timestamp): string
    {
        return base64_encode($this->config['shortcode'].$this->config['passkey'].$timestamp);
    }
}
