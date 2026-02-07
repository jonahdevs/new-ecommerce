<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class PaymentService.
 */
class PaymentService
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

    public function createPaymentOrder(Order $order): array
    {
        // Validate order has required data
        if (!$order->user && !$order->shipping_address) {
            throw new \Exception('Order must have user or shipping address');
        }

        $user = $order->user;
        $shippingAddress = $order->shipping_address;

        // Get balance ID based on currency
        $balanceId = $order->currency === 'USD' ? $this->balanceIdUsd : $this->balanceIdKes;

        // Build the payload
        $payload = [
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
                'email' => $user?->email ?? '',
                'phoneNumber' => $this->getPhoneNumber($order),
                'city' => $shippingAddress['area']['name'] ?? 'Nairobi',
                'state' => $shippingAddress['county']['name'] ?? 'Nairobi County',
                'address' => $shippingAddress['address'] ?? '',
                'countryCode' => 'KE',
            ],
        ];

        Log::info('Creating Pesawise payment order', [
            'order_id' => $order->id,
            'reference' => $order->reference,
            'amount' => $order->total,
            'currency' => $order->currency,
        ]);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'api-key' => $this->apiKey,
                    'api-secret' => $this->apiSecret,
                    'Content-Type' => 'application/json',
                    'Accept' => '*/*',
                ])
                ->post("{$this->apiUrl}/e-com/create-order", $payload);

            $responseData = $response->json();

            // Check if request was successful
            if (!$response->successful() || !($responseData['success'] ?? false)) {
                $errorDetail = $responseData['detail'] ?? 'Unknown error';
                $errorStatus = $responseData['status'] ?? 'UNKNOWN';

                Log::error('Pesawise payment creation failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'error_detail' => $errorDetail,
                    'error_status' => $errorStatus,
                    'response' => $responseData,
                ]);

                // User-friendly error messages
                $userMessage = match ($errorStatus) {
                    'API_KEYS_INVALID' => 'Payment gateway configuration error. Please contact support.',
                    'BALANCE_NOT_FOUND' => 'Payment account configuration error. Please contact support.',
                    'CURRENCY_NOT_SUPPORTED' => 'Currency not supported. Please contact support.',
                    default => "Payment initialization failed: {$errorDetail}"
                };

                throw new \Exception($userMessage);
            }

            $createdPaymentOrder = $responseData['createdPaymentOrder'] ?? null;

            if (!$createdPaymentOrder) {
                throw new \Exception('Invalid payment response from gateway');
            }

            Log::info('Pesawise payment order created successfully', [
                'order_id' => $order->id,
                'request_id' => $responseData['requestId'] ?? null,
                'pesawise_order_id' => $createdPaymentOrder['orderId'] ?? null,
                'load_url' => $createdPaymentOrder['loadUrl'] ?? null,
            ]);

            // Update payment record with Pesawise response
            app(OrderService::class)->updatePaymentWithGatewayResponse($order, [
                'pesawise_order_id' => $createdPaymentOrder['orderId'],
                'payment_url' => $createdPaymentOrder['loadUrl'],
                'order_request_id' => $createdPaymentOrder['orderRequestId'],
                'request_id' => $responseData['requestId'],
            ]);

            return $responseData;
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

    /**
     * Get customer name from order
     */
    private function getCustomerName(Order $order): string
    {
        // Try from user first
        if ($order->user && $order->user->name) {
            return $order->user->name;
        }

        // Try from shipping address
        $shippingAddress = $order->shipping_address;

        if (!empty($shippingAddress['first_name'])) {
            $lastName = $shippingAddress['last_name'] ?? '';
            return trim($shippingAddress['first_name'] . ' ' . $lastName);
        }

        return 'Guest Customer';
    }

    /**
     * Get phone number from order
     */
    private function getPhoneNumber(Order $order): string
    {
        $phone = null;

        // Try from user first
        if ($order->user && $order->user->phone_number) {
            $phone = $order->user->phone_number;
        }

        // Fallback to shipping address
        if (!$phone) {
            $shippingAddress = $order->shipping_address;
            $phone = $shippingAddress['phone_number'] ?? '';
        }

        // Should already be normalized by PhoneNormalizationService
        return $phone;
    }
}
