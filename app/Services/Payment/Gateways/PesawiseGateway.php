<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\ValueObjects\PaymentResponse;
use App\Services\Payment\ValueObjects\PaymentStatus;
use App\Settings\PaymentSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PesawiseGateway implements PaymentGateway
{
    private string $apiUrl;
    private string $apiKey;
    private string $apiSecret;
    private string $balanceId;
    private bool   $isProduction;

    public function __construct(PaymentSettings $settings)
    {
        // Credentials now come from Spatie settings, not config
        $this->isProduction = $settings->pesawise_mode_production ?? config('services.pesawise_mode_production');
        $this->apiKey       = $settings->pesawise_api_key ?? config('services.pesawise.api_key');
        $this->apiSecret    = $settings->pesawise_api_secret ?? config('services.pesawise.api_secret');
        $this->balanceId    = $settings->pesawise_account_number ?? config('services.pesawise.balance_id_kes');

        $this->apiUrl = $this->isProduction
            ? 'https://api.pesawise.xyz/api'
            : 'https://api.pesawise.xyz/api'; // Update sandbox URL when available
    }

    //  Interface implementation

    public function initiate(Order $order, Payment $payment): PaymentResponse
    {
        try {
            $payload  = $this->buildPayload($order);
            $response = $this->makeRequest('/e-com/create-order', $payload);
            $data     = $response->json();

            $this->updatePaymentRecord($payment, $data);

            $loadUrl = $data['createdPaymentOrder']['loadUrl'] ?? null;

            if (! $loadUrl) {
                throw new \RuntimeException('Pesawise did not return a payment URL.');
            }

            Log::info('Pesawise payment initiated', [
                'order_id'  => $order->id,
                'reference' => $order->reference,
            ]);

            // Return iframe type — your existing implementation used iframe
            // return PaymentResponse::iframe($loadUrl);
            return PaymentResponse::redirect($loadUrl);
        } catch (\Throwable $e) {
            Log::error('Pesawise initiation failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function verify(string $reference): PaymentStatus
    {
        try {
            $response = $this->makeRequest('/e-com/verify', [
                'externalId' => $reference,
            ], 'get');

            $data   = $response->json();
            $status = $data['status'] ?? 'unknown';

            return match ($status) {
                'PAID', 'SUCCESS', 'COMPLETED' => PaymentStatus::paid(
                    transactionId: $data['transactionId'] ?? $reference,
                    gatewayStatus: $status,
                    meta: $data,
                ),
                'PENDING'    => PaymentStatus::pending(),
                'PROCESSING' => PaymentStatus::processing(),
                'FAILED'     => PaymentStatus::failed($status),
                'CANCELLED'  => PaymentStatus::cancelled(),
                default      => PaymentStatus::failed($status),
            };
        } catch (\Throwable $e) {
            Log::error('Pesawise verification failed', ['reference' => $reference, 'error' => $e->getMessage()]);
            return PaymentStatus::failed($e->getMessage());
        }
    }

    public function handleWebhook(Request $request): void
    {
        // Verify webhook signature
        $secret    = config('services.pesawise.webhook_secret');
        $signature = $request->header('X-Pesawise-Signature');

        if ($secret && $signature !== hash_hmac('sha256', $request->getContent(), $secret)) {
            Log::warning('Pesawise webhook signature mismatch');
            abort(401);
        }

        $data      = $request->json()->all();
        $reference = $data['externalId'] ?? null;

        if (! $reference) {
            return;
        }

        $order = \App\Models\Order::where('reference', $reference)->first();

        if (! $order) {
            Log::warning('Pesawise webhook: order not found', ['reference' => $reference]);
            return;
        }

        $gatewayStatus = $data['status'] ?? null;

        match ($gatewayStatus) {
            'PAID', 'SUCCESS', 'COMPLETED' => $this->markPaid($order, $data),
            'FAILED'                        => $this->markFailed($order, $data),
            'CANCELLED'                     => $this->markCancelled($order, $data),
            default                         => Log::info('Pesawise webhook: unhandled status', ['status' => $gatewayStatus]),
        };
    }

    //  Private helpers

    private function buildPayload(Order $order): array
    {
        return [
            'amount'              => $order->total_cents / 100,
            'customerName'        => $this->resolveCustomerName($order),
            'currency'            => 'KES',
            'externalId'          => $order->reference,
            'description'         => "Payment for Order #{$order->reference}",
            'balanceId'           => $this->balanceId,
            'callbackUrl'         => route('payment.callback.success'),
            'cancellationUrl'     => route('payment.callback.cancel'),
            'notificationId'      => (string) $order->id,
            'timeValidityMinutes' => 30,
            'customerData'        => [
                'email'       => $order->user?->email ?? '',
                'phoneNumber' => $this->resolvePhone($order),
                'city'        => $order->shipping_address['area'] ?? 'Nairobi',
                'state'       => $order->shipping_address['county'] ?? 'Nairobi County',
                'address'     => $order->shipping_address['address'] ?? '',
                'countryCode' => 'KE',
            ],
        ];
    }

    private function makeRequest(string $endpoint, array $payload, string $method = 'post')
    {
        $http = Http::withHeaders([
            'api-key'    => $this->apiKey,
            'api-secret' => $this->apiSecret,
        ]);

        $response = $method === 'get'
            ? $http->get("{$this->apiUrl}{$endpoint}", $payload)
            : $http->post("{$this->apiUrl}{$endpoint}", $payload);

        if ($response->failed()) {
            Log::error('Pesawise API request failed', [
                'endpoint' => $endpoint,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            throw new \RuntimeException('Payment gateway request failed. Please try again.');
        }

        return $response;
    }

    private function updatePaymentRecord(Payment $payment, array $response): void
    {
        $created = $response['createdPaymentOrder'];

        $payment->update([
            'gateway_order_id' => $created['orderId'],
            'transaction_id'   => $created['orderRequestId'],
            'payment_url'      => $created['loadUrl'],
            'status'           => 'processing',
            'meta'             => [
                'request_id'       => $response['requestId'],
                'load_url'         => $created['loadUrl'],
                'order_request_id' => $created['orderRequestId'],
                'initiated_at'     => now()->toISOString(),
            ],
        ]);
    }

    private function markPaid(Order $order, array $data): void
    {
        $order->payment?->update([
            'status'         => 'paid',
            'transaction_id' => $data['transactionId'] ?? null,
            'paid_at'        => now(),
            'meta'           => array_merge($order->payment->meta ?? [], $data),
        ]);

        $order->update([
            'status'         => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    private function markFailed(Order $order, array $data): void
    {
        $order->payment?->update(['status' => 'failed', 'meta' => $data]);
        $order->update(['payment_status' => 'failed']);
    }

    private function markCancelled(Order $order, array $data): void
    {
        $order->payment?->update(['status' => 'cancelled', 'meta' => $data]);
        $order->update(['status' => 'cancelled', 'payment_status' => 'cancelled']);
    }

    private function resolveCustomerName(Order $order): string
    {
        if ($order->user?->name) return $order->user->name;

        $first = $order->shipping_address['first_name'] ?? '';
        $last  = $order->shipping_address['last_name'] ?? '';

        return trim("$first $last") ?: 'Customer';
    }

    private function resolvePhone(Order $order): string
    {
        return $order->user?->phone_number
            ?? $order->shipping_address['phone_number']
            ?? '';
    }
}
