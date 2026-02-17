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
    private string $balanceId;

    public function __construct()
    {
        $this->apiUrl = config('services.pesawise.api_url');
        $this->apiKey = config('services.pesawise.api_key');
        $this->apiSecret = config('services.pesawise.api_secret');
        $this->balanceId = config('services.pesawise.balance_id_kes');
    }


    /**
     * Create payment order with Pesawise and return the response
     */
    public function createPaymentOrder(Order $order)
    {
        $payload =  $this->buildPayload($order);

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'api-secret' => $this->apiSecret,
        ])->post("{$this->apiUrl}/e-com/create-order", $payload);

        if ($response->failed()) {
            Log::error('Pesawise payment creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'order_id' => $order->id,
            ]);
            throw new \Exception('Payment gateway request failed. Please try again.');
        }

        $data = $response->json();

        Log::info('Pesawise payment order created', [
            'order_id' => $order->id,
            'reference' => $order->reference,
            'response' => $data,
        ]);

        $this->updatePaymentRecord($order, $data);

        return $data;
    }


    private function buildPayload(Order $order): array
    {
        return [
            'amount' => $order->total,
            'customerName' => $this->getCustomerName($order),
            'currency' => 'KES',
            'externalId' => $order->reference,
            'description' => "Payment for Order #{$order->reference}",
            'balanceId' => $this->balanceId,
            'callbackUrl' => url('payment/callback/success'),
            'cancellationUrl' => url('payment/callback/cancel'),
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



    private function updatePaymentRecord(Order $order, array $response): void
    {
        $createdPaymentOrder = $response['createdPaymentOrder'];

        $order->payment->update([
            'gateway_order_id' => $createdPaymentOrder['orderId'],
            'transaction_id' => $createdPaymentOrder['orderRequestId'],
            'status' => 'processing',
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
        if ($order->user?->name) {
            return $order->user->name;
        }

        $first = $order->shipping_address['first_name'] ?? '';
        $last = $order->shipping_address['last_name'] ?? '';

        return trim("$first $last") ?: 'Guest Customer';
    }

    private function getPhoneNumber(Order $order): string
    {
        return $order->user?->phone_number
            ?? $order->shipping_address['phone_number']
            ?? '';
    }
}
