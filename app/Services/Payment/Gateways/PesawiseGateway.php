<?php

namespace App\Services\Payment\Gateways;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentConfirmed;
use App\Jobs\SyncOrderToSapJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutSession;
use App\Services\DocumentService;
use App\Services\InventoryService;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\ValueObjects\PaymentResponse;
use App\Services\Payment\ValueObjects\PaymentStatus as PaymentStatusVO;
use App\Settings\LocalizationSettings;
use App\Settings\OrderSettings;
use App\Settings\PesawiseSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PesawiseGateway implements PaymentGateway
{
    private string $apiUrl;
    private string $apiKey;
    private string $apiSecret;
    private string $balanceId;
    private bool $isProduction;
    private string $currency;
    private bool $stockReduceOnOrder;

    public function __construct(
        PesawiseSettings $settings,
        LocalizationSettings $localization,
        OrderSettings $orderSettings,
    ) {
        // Use settings with config fallback
        $this->isProduction = ($settings->environment ?: config('services.pesawise.environment', 'sandbox')) === 'live';
        $this->apiKey = $settings->api_key ?: config('services.pesawise.api_key', '');
        $this->apiSecret = $settings->api_secret ?: config('services.pesawise.api_secret', '');
        $this->balanceId = $settings->account_number ?: config('services.pesawise.balance_id_kes', '');
        $this->apiUrl = config('services.pesawise.api_url', 'https://api.pesawise.xyz/api');
        $this->currency = $localization->currency;
        $this->stockReduceOnOrder = $orderSettings->stock_reduce_on_order;
    }

    public function initiate(Order $order, Payment $payment): PaymentResponse
    {
        try {
            $payload = $this->buildPayload($order);
            $response = $this->makeRequest('/e-com/create-order', $payload);
            $data = $response->json();

            $this->updatePaymentRecord($payment, $data);

            $loadUrl = $data['createdPaymentOrder']['loadUrl'] ?? null;

            if (!$loadUrl) {
                throw new \RuntimeException('Pesawise did not return a payment URL.');
            }

            Log::info('Pesawise payment initiated', [
                'order_id' => $order->id,
                'reference' => $order->reference,
            ]);

            return PaymentResponse::redirect($loadUrl);

        } catch (\Throwable $e) {
            Log::error('Pesawise initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function verify(string $reference): PaymentStatusVO
    {
        try {
            $response = $this->makeRequest('/e-com/verify', ['externalId' => $reference], 'get');
            $data = $response->json();
            $status = $data['status'] ?? 'unknown';

            return match ($status) {
                'PAID', 'SUCCESS', 'COMPLETED' => PaymentStatusVO::paid(
                    transactionId: $data['transactionId'] ?? $reference,
                    gatewayStatus: $status,
                    meta: $data,
                ),
                'PENDING' => PaymentStatusVO::pending(),
                'PROCESSING' => PaymentStatusVO::processing(),
                'FAILED' => PaymentStatusVO::failed($status),
                'CANCELLED' => PaymentStatusVO::cancelled(),
                default => PaymentStatusVO::failed($status),
            };

        } catch (\Throwable $e) {
            Log::error('Pesawise verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);
            return PaymentStatusVO::failed($e->getMessage());
        }
    }

    public function handleWebhook(Request $request): void
    {
        $secret = app(PaymentSettings::class)->pesawise_webhook_secret;
        $signature = $request->header('X-Pesawise-Signature');

        if ($secret && $signature !== hash_hmac('sha256', $request->getContent(), $secret)) {
            Log::warning('Pesawise webhook signature mismatch');
            abort(401);
        }

        $data = $request->json()->all();
        $reference = $data['externalId'] ?? null;

        if (!$reference)
            return;

        $order = Order::where('reference', $reference)->first();

        if (!$order) {
            Log::warning('Pesawise webhook: order not found', ['reference' => $reference]);
            return;
        }

        $gatewayStatus = $data['status'] ?? null;

        match ($gatewayStatus) {
            'PAID', 'SUCCESS', 'COMPLETED' => $this->markPaid($order, $data),
            'FAILED' => $this->markFailed($order, $data),
            'CANCELLED' => $this->markCancelled($order, $data),
            default => Log::info('Pesawise webhook: unhandled status', [
                'status' => $gatewayStatus,
                'reference' => $reference,
            ]),
        };
    }

    private function markPaid(Order $order, array $data): void
    {
        // Idempotency guard
        if ($order->payment?->status === PaymentStatus::PAID->value) {
            Log::info('Pesawise webhook already processed, skipping', [
                'reference' => $order->reference,
            ]);
            return;
        }

        $order->payment?->update([
            'status' => PaymentStatus::PAID->value,
            'transaction_id' => $data['transactionId'] ?? null,
            'paid_at' => now(),
            'meta' => array_merge($order->payment->meta ?? [], $data),
        ]);

        $order->transitionTo(
            OrderStatus::CONFIRMED,
            notes: 'Payment confirmed via Pesawise webhook',
            changedByType: 'system',
        );
        $order->update(['payment_status' => PaymentStatus::PAID->value]);

        // Deduct stock if using reservation pattern (stock not deducted on order)
        if (!$this->stockReduceOnOrder) {
            try {
                app(InventoryService::class)->deductStock($order);
            } catch (\Exception $e) {
                Log::error('Failed to deduct stock after payment', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        app(DocumentService::class)->generateInvoice($order);
        app(CartService::class)->clear(User::find($order->user_id));
        app(CheckoutSession::class)->clear();

        // -------------------------------------------------------
        // Dispatch SAP sync — fires after Pesawise confirms payment.
        // Order is CONFIRMED, payment_status = paid.
        // -------------------------------------------------------
        SyncOrderToSapJob::dispatch($order->fresh());

        PaymentConfirmed::dispatch($order->fresh(['payment']));

        Log::info('Pesawise payment confirmed, SAP sync dispatched', [
            'order_id' => $order->id,
            'transaction_id' => $data['transactionId'] ?? null,
        ]);
    }

    private function markFailed(Order $order, array $data): void
    {
        if ($order->payment?->status === PaymentStatus::FAILED->value) {
            Log::info('Pesawise failure webhook already processed, skipping', [
                'reference' => $order->reference,
            ]);
            return;
        }

        $order->payment?->update([
            'status' => PaymentStatus::FAILED->value,
            'meta' => $data,
        ]);

        $order->transitionTo(
            OrderStatus::CANCELLED,
            notes: 'Payment failed via Pesawise webhook',
            changedByType: 'system',
        );
        $order->update(['payment_status' => PaymentStatus::FAILED->value]);

        $this->restoreStock($order);

        Log::info('Pesawise payment failed', [
            'order_id' => $order->id,
            'reference' => $order->reference,
        ]);
    }

    private function markCancelled(Order $order, array $data): void
    {
        if ($order->payment?->status === PaymentStatus::CANCELLED->value) {
            Log::info('Pesawise cancellation webhook already processed, skipping', [
                'reference' => $order->reference,
            ]);
            return;
        }

        $order->payment?->update([
            'status' => PaymentStatus::CANCELLED->value,
            'meta' => $data,
        ]);

        $order->transitionTo(
            OrderStatus::CANCELLED,
            notes: 'Payment cancelled via Pesawise webhook',
            changedByType: 'system',
        );
        $order->update(['payment_status' => PaymentStatus::CANCELLED->value]);

        $this->restoreStock($order);

        Log::info('Pesawise payment cancelled', [
            'order_id' => $order->id,
            'reference' => $order->reference,
        ]);
    }

    private function restoreStock(Order $order): void
    {
        $inventoryService = app(InventoryService::class);

        if ($this->stockReduceOnOrder) {
            // Stock was deducted on order, restore it
            foreach ($order->items()->with(['product', 'variant'])->get() as $item) {
                $stockItem = $item->product_variant_id
                    ? $item->variant
                    : $item->product;
                $stockItem?->increment('stock_quantity', $item->quantity);
            }
        } else {
            // Stock was reserved, release reservations
            $inventoryService->releaseReservation($order);
        }
    }

    private function buildPayload(Order $order): array
    {
        return [
            'amount' => $order->total_cents / 100,
            'customerName' => $this->resolveCustomerName($order),
            'currency' => $this->currency,
            'externalId' => $order->reference,
            'description' => "Payment for Order #{$order->reference}",
            'balanceId' => $this->balanceId,
            'callbackUrl' => route('payment.callback.success'),
            'cancellationUrl' => route('payment.callback.cancel'),
            'notificationId' => (string) $order->id,
            'timeValidityMinutes' => 30,
            'customerData' => [
                'email' => $order->user?->email ?? '',
                'phoneNumber' => $this->resolvePhone($order),
                'city' => $order->shipping_address['area'] ?? 'Nairobi',
                'state' => $order->shipping_address['county'] ?? 'Nairobi County',
                'address' => $order->shipping_address['address'] ?? '',
                'countryCode' => 'KE',
            ],
        ];
    }

    private function makeRequest(string $endpoint, array $payload, string $method = 'post')
    {
        $http = Http::withHeaders([
            'api-key' => $this->apiKey,
            'api-secret' => $this->apiSecret,
        ]);

        $response = $method === 'get'
            ? $http->get("{$this->apiUrl}{$endpoint}", $payload)
            : $http->post("{$this->apiUrl}{$endpoint}", $payload);

        if ($response->failed()) {
            Log::error('Pesawise API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
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
            'transaction_id' => $created['orderRequestId'],
            'payment_url' => $created['loadUrl'],
            'status' => PaymentStatus::PROCESSING->value,
            'meta' => [
                'request_id' => $response['requestId'],
                'load_url' => $created['loadUrl'],
                'order_request_id' => $created['orderRequestId'],
                'initiated_at' => now()->toISOString(),
            ],
        ]);
    }

    private function resolveCustomerName(Order $order): string
    {
        if ($order->user?->name)
            return $order->user->name;

        $first = $order->shipping_address['first_name'] ?? '';
        $last = $order->shipping_address['last_name'] ?? '';

        return trim("$first $last") ?: 'Customer';
    }

    private function resolvePhone(Order $order): string
    {
        return $order->user?->phone_number
            ?? $order->shipping_address['phone_number']
            ?? '';
    }
}
