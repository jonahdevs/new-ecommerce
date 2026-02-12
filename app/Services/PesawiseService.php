<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class PesawiseService.
 */
class PesawiseService
{
    private string $apiUrl;
    private string $apiKey;
    private string $apiSecret;
    private int $balanceIdKes;
    private int $balanceIdUsd;

    public function __construct()
    {
        $this->apiUrl = config('services.pesawise.api_url');
        $this->apiKey = config('services.pesawise.api_key');
        $this->apiSecret = config('services.pesawise.api_secret');
        $this->balanceIdKes = config('services.pesawise.balance_id_kes');
        $this->balanceIdUsd = config('services.pesawise.balance_id_usd');
    }

    /**
     * Create payment order with Pesawise
     */
    public function createPaymentOrder(Order $order): array
    {
        $this->validateOrder($order);

        $payload = $this->buildPayload($order);

        Log::info('Creating Pesawise payment order', [
            'order_id' => $order->id,
            'reference' => $order->reference,
            'amount' => $order->total,
            'currency' => $order->currency,
        ]);

        try {
            $response = $this->makeApiRequest($payload);

            $this->updatePaymentRecord($order, $response);

            return $response;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Pesawise API connection failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Unable to connect to payment gateway. Please try again.');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Pesawise API request failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Payment gateway request failed. Please try again.');
        }
    }

    private function validateOrder(Order $order): void
    {
        if (!$order->user && !$order->shipping_address) {
            throw new \Exception('Order must have user or shipping address');
        }

        if (!$order->payment) {
            throw new \Exception('Payment record not found for order');
        }
    }

    private function buildPayload(Order $order): array
    {
        $balanceId = $order->currency === 'USD' ? $this->balanceIdUsd : $this->balanceIdKes;

        return [
            'amount' => $order->total,
            'customerName' => $this->getCustomerName($order),
            'currency' => $order->currency,
            'externalId' => $order->reference,
            'description' => "Payment for Order #{$order->reference}",
            'balanceId' => $balanceId,
            'callbackUrl' => route('payment.callback'),
            'cancellationUrl' => route('payment.cancel'),
            'notificationId' => (string) $order->id,
            'timeValidityMinutes' => 30,
            'customerData' => [
                'email' => $order->user?->email ?? '',
                'phoneNumber' => $this->getPhoneNumber($order),
                'city' => $order->shipping_address['area']['name'] ?? 'Nairobi',
                'state' => $order->shipping_address['county']['name'] ?? 'Nairobi County',
                'address' => $order->shipping_address['address'] ?? '',
                'countryCode' => 'KE',
            ],
        ];
    }

    private function makeApiRequest(array $payload): array
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'api-key' => $this->apiKey,
                'api-secret' => $this->apiSecret,
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
            ])
            ->post("{$this->apiUrl}/e-com/create-order", $payload);

        $responseData = $response->json();

        if (!$response->successful() || !($responseData['success'] ?? false)) {
            $this->handleApiError($response, $responseData);
        }

        $createdPaymentOrder = $responseData['createdPaymentOrder'] ?? null;

        if (!$createdPaymentOrder) {
            throw new \Exception('Invalid payment response from gateway');
        }

        Log::info('Pesawise payment order created successfully', [
            'request_id' => $responseData['requestId'] ?? null,
            'pesawise_order_id' => $createdPaymentOrder['orderId'] ?? null,
        ]);

        return $responseData;
    }

    private function handleApiError($response, array $responseData): void
    {
        $errorDetail = $responseData['detail'] ?? 'Unknown error';
        $errorStatus = $responseData['status'] ?? 'UNKNOWN';

        Log::error('Pesawise payment creation failed', [
            'status' => $response->status(),
            'error_detail' => $errorDetail,
            'error_status' => $errorStatus,
            'response' => $responseData,
        ]);

        $userMessage = match ($errorStatus) {
            'API_KEYS_INVALID' => 'Payment gateway configuration error. Please contact support.',
            'BALANCE_NOT_FOUND' => 'Payment account configuration error. Please contact support.',
            'CURRENCY_NOT_SUPPORTED' => 'Currency not supported. Please contact support.',
            default => "Payment initialization failed: {$errorDetail}"
        };

        throw new \Exception($userMessage);
    }

    private function updatePaymentRecord(Order $order, array $response): void
    {
        $createdPaymentOrder = $response['createdPaymentOrder'];

        $order->payment->update([
            'gateway_order_id' => $createdPaymentOrder['orderId'],
            'transaction_id' => $createdPaymentOrder['orderRequestId'],
            'status' => 'processing', // Changed from pending to processing
            'meta' => [
                'request_id' => $response['requestId'],
                'load_url' => $createdPaymentOrder['loadUrl'],
                'order_request_id' => $createdPaymentOrder['orderRequestId'],
                'created_at' => now()->toISOString(),
            ],
        ]);
    }

    private function getCustomerName(Order $order): string
    {
        if ($order->user && $order->user->name) {
            return $order->user->name;
        }

        $shippingAddress = $order->shipping_address;

        if (!empty($shippingAddress['first_name'])) {
            $lastName = $shippingAddress['last_name'] ?? '';
            return trim($shippingAddress['first_name'] . ' ' . $lastName);
        }

        return 'Guest Customer';
    }

    private function getPhoneNumber(Order $order): string
    {
        if ($order->user && $order->user->phone_number) {
            return $order->user->phone_number;
        }

        return $order->shipping_address['phone_number'] ?? '';
    }
}
