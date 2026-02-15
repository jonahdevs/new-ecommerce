<?php

namespace App\Services;

use App\Models\Order;
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
     * Create payment order with Pesawise
     */
    public function createPaymentOrder(Order $order)
    {
        $payload = $this->buildPayload($order);

        try {
            $response = $this->initiatePayment($payload);

            $this->updatePaymentRecord($order, $response);

            session([
                'pesawise_payment_order_id' => $order->id,
                'pesawise_payment_reference' => $order->reference,
                'pesawise_payment_started_at' => now()->toISOString(),
            ]);

            return $response;
        } catch (\Throwable $th) {
            throw new \Exception('Payment gateway request failed. Please try again.');
        }
    }

    private function buildPayload(Order $order): array
    {
        // Append order data directly to callback URLs as query parameters
        // This ensures we get the data back even if Pesawise doesn't pass notificationId
        $callbackUrl = route('payment.callback.success', [
            'order_id' => $order->id,
            'reference' => $order->reference
        ]);
        $cancellationUrl = route('payment.callback.cancel', [
            'order_id' => $order->id,
            'reference' => $order->reference
        ]);

        $payload = [
            'amount' => $order->total,
            'customerName' => $this->getCustomerName($order),
            'currency' => "KES",
            'externalId' => $order->reference,
            'description' => "Payment for Order #{$order->reference}",
            'balanceId' => $this->balanceId,
            'callbackUrl' => $callbackUrl,
            'cancellationUrl' => $cancellationUrl,
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

        return $payload;
    }

    private function initiatePayment(array $payload)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl . "/e-com/create-order",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: */*',
                'api-key: ' . $this->apiKey,
                'api-secret: ' . $this->apiSecret
            ),
        ));

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            Log::error('Pesawise cURL Error: ' . $error);
            throw new \Exception('Payment gateway connection failed');
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $decodedResponse = json_decode($response, true);

        return $decodedResponse;
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
