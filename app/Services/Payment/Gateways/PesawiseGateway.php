<?php

namespace App\Services\Payment\Gateways;

use App\Enums\OrdersStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutSession;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\ValueObjects\PaymentResponse;
use App\Services\Payment\ValueObjects\PaymentStatus as PaymentStatusVO;
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
        $this->isProduction = ($settings->pesawise_env
            ?: config('services.pesawise.pesawise_mode_production')) === 'production';

        $this->apiKey    = $settings->pesawise_api_key    ?: config('services.pesawise.api_key');
        $this->apiSecret = $settings->pesawise_api_secret ?: config('services.pesawise.api_secret');
        $this->balanceId = $settings->pesawise_account_number ?: config('services.pesawise.balance_id_kes');
        $this->apiUrl    = config('services.pesawise.api_url', 'https://api.pesawise.xyz/api');
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

            if (!$loadUrl) {
                throw new \RuntimeException('Pesawise did not return a payment URL.');
            }

            Log::info('Pesawise payment initiated', [
                'order_id'  => $order->id,
                'reference' => $order->reference,
            ]);

            return PaymentResponse::redirect($loadUrl);
        } catch (\Throwable $e) {
            Log::error('Pesawise initiation failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function verify(string $reference): PaymentStatusVO
    {
        try {
            $response = $this->makeRequest('/e-com/verify', [
                'externalId' => $reference,
            ], 'get');

            $data   = $response->json();
            $status = $data['status'] ?? 'unknown';

            return match ($status) {
                'PAID', 'SUCCESS', 'COMPLETED' => PaymentStatusVO::paid(
                    transactionId: $data['transactionId'] ?? $reference,
                    gatewayStatus: $status,
                    meta: $data,
                ),
                'PENDING'    => PaymentStatusVO::pending(),
                'PROCESSING' => PaymentStatusVO::processing(),
                'FAILED'     => PaymentStatusVO::failed($status),
                'CANCELLED'  => PaymentStatusVO::cancelled(),
                default      => PaymentStatusVO::failed($status),
            };
        } catch (\Throwable $e) {
            Log::error('Pesawise verification failed', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
            return PaymentStatusVO::failed($e->getMessage());
        }
    }

    public function handleWebhook(Request $request): void
    {
        // Verify webhook signature
        $secret    = app(PaymentSettings::class)->pesawise_webhook_secret;
        $signature = $request->header('X-Pesawise-Signature');

        if ($secret && $signature !== hash_hmac('sha256', $request->getContent(), $secret)) {
            Log::warning('Pesawise webhook signature mismatch');
            abort(401);
        }

        $data      = $request->json()->all();
        $reference = $data['externalId'] ?? null;

        if (!$reference) {
            return;
        }

        $order = Order::where('reference', $reference)->first();

        if (!$order) {
            Log::warning('Pesawise webhook: order not found', ['reference' => $reference]);
            return;
        }

        $gatewayStatus = $data['status'] ?? null;

        match ($gatewayStatus) {
            'PAID', 'SUCCESS', 'COMPLETED' => $this->markPaid($order, $data),
            'FAILED'                        => $this->markFailed($order, $data),
            'CANCELLED'                     => $this->markCancelled($order, $data),
            default => Log::info('Pesawise webhook: unhandled status', [
                'status'    => $gatewayStatus,
                'reference' => $reference,
            ]),
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
            'status'           => PaymentStatus::PROCESSING->value,
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
        // 1. Update payment record
        $order->payment?->update([
            'status'         => PaymentStatus::PAID->value,
            'transaction_id' => $data['transactionId'] ?? null,
            'paid_at'        => now(),
            'meta'           => array_merge($order->payment->meta ?? [], $data),
        ]);

        // 2. Transition order status — records history automatically
        $order->transitionTo(
            OrdersStatus::CONFIRMED,
            notes: 'Payment confirmed via Pesawise webhook',
            changedByType: 'system'
        );
        $order->update(['payment_status' => PaymentStatus::PAID->value]);

        // 3. Clear cart — payment is confirmed, cart no longer needed
        app(CartService::class)->clear(
            User::find($order->user_id)
        );

        // 4. Clear checkout session
        app(CheckoutSession::class)->clear();

        Log::info('Pesawise payment confirmed', [
            'order_id'       => $order->id,
            'transaction_id' => $data['transactionId'] ?? null,
        ]);
    }

    private function markFailed(Order $order, array $data): void
    {
        // 1. Update payment record
        $order->payment?->update([
            'status' => PaymentStatus::FAILED->value,
            'meta'   => $data,
        ]);

        // 2. Transition order status
        $order->transitionTo(
            OrdersStatus::CANCELLED,
            notes: 'Payment failed via Pesawise webhook',
            changedByType: 'system'
        );
        $order->update(['payment_status' => PaymentStatus::FAILED->value]);

        // 3. Restore stock
        $this->restoreStock($order);

        Log::info('Pesawise payment failed', [
            'order_id'  => $order->id,
            'reference' => $order->reference,
        ]);
    }

    private function markCancelled(Order $order, array $data): void
    {
        // 1. Update payment record
        $order->payment?->update([
            'status' => PaymentStatus::CANCELLED->value,
            'meta'   => $data,
        ]);

        // 2. Transition order status
        $order->transitionTo(
            OrdersStatus::CANCELLED,
            notes: 'Payment cancelled via Pesawise webhook',
            changedByType: 'system'
        );
        $order->update(['payment_status' => PaymentStatus::CANCELLED->value]);

        // 3. Restore stock
        $this->restoreStock($order);

        Log::info('Pesawise payment cancelled', [
            'order_id'  => $order->id,
            'reference' => $order->reference,
        ]);
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items()->with('product')->get() as $item) {
            $item->product?->increment('stock_quantity', $item->quantity);
        }
    }

    private function resolveCustomerName(Order $order): string
    {
        if ($order->user?->name) {
            return $order->user->name;
        }

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
