<?php

namespace App\Services\Payment\Gateways;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentConfirmed;
use App\Jobs\SyncOrderToSapJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\FailedPaymentNotification;
use App\Services\CartService;
use App\Services\CheckoutSession;
use App\Services\InventoryService;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\ValueObjects\PaymentResponse;
use App\Services\Payment\ValueObjects\PaymentStatus as PaymentStatusVO;
use App\Settings\LocalizationSettings;
use App\Settings\NotificationSettings;
use App\Settings\PaymentSettings;
use App\Settings\PesawiseSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PesawiseGateway implements PaymentGateway
{
    private string $apiUrl;
    private string $apiKey;
    private string $apiSecret;
    private string $balanceId;
    private bool $isProduction;
    private string $currency;

    public function __construct(
        PesawiseSettings $settings,
        LocalizationSettings $localization,
        private readonly NotificationSettings $notificationSettings
    ) {
        // Use settings with config fallback
        $this->isProduction = ($settings->environment ?: config('services.pesawise.environment', 'sandbox')) === 'live';
        $this->apiKey = $settings->api_key ?: config('services.pesawise.api_key', '');
        $this->apiSecret = $settings->api_secret ?: config('services.pesawise.api_secret', '');
        $this->balanceId = $settings->account_number ?: config('services.pesawise.balance_id_kes', '');
        $this->apiUrl = config('services.pesawise.api_url', 'https://api.pesawise.xyz/api');
        $this->currency = $localization->currency;
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

            // Log activity
            activity()
                ->performedOn($payment)
                ->causedBy(auth()->user())
                ->withProperties([
                    'order_id' => $order->id,
                    'order_reference' => $order->reference,
                    'amount' => $order->total,
                    'currency' => $this->currency,
                    'gateway' => 'pesawise',
                    'payment_url' => $loadUrl,
                ])
                ->log('payment_initiated');

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

        // Deduct stock — reservation is converted to a real deduction
        try {
            app(InventoryService::class)->deductStock($order);
        } catch (\Exception $e) {
            Log::error('Failed to deduct stock after payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Invoice is generated later when SAP webhook returns KRA data
        app(CartService::class)->clear(User::find($order->user_id));
        app(CheckoutSession::class)->clear();

        // -------------------------------------------------------
        // Dispatch SAP sync — fires after Pesawise confirms payment.
        // Order is CONFIRMED, payment_status = paid.
        // -------------------------------------------------------
        SyncOrderToSapJob::dispatch($order->fresh());

        PaymentConfirmed::dispatch($order->fresh(['payment']));

        // Log activity
        activity()
            ->performedOn($order->payment)
            ->withProperties([
                'order_id' => $order->id,
                'order_reference' => $order->reference,
                'transaction_id' => $data['transactionId'] ?? null,
                'gateway_status' => $data['status'] ?? null,
                'amount' => $order->total,
                'gateway' => 'pesawise',
            ])
            ->log('payment_confirmed');

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

        // Send admin notification if enabled
        $this->sendFailedPaymentNotification($order, $order->payment, $data['message'] ?? 'Payment failed');

        // Log activity
        activity()
            ->performedOn($order->payment)
            ->withProperties([
                'order_id' => $order->id,
                'order_reference' => $order->reference,
                'reason' => $data['message'] ?? 'Payment failed',
                'gateway' => 'pesawise',
                'gateway_response' => $data,
            ])
            ->log('payment_failed');

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
        app(InventoryService::class)->releaseReservation($order);
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

    private function sendFailedPaymentNotification(Order $order, ?Payment $payment, string $reason): void
    {
        if (!$payment || !$this->notificationSettings->notify_failed_payment) {
            return;
        }

        try {
            $adminEmail = $this->notificationSettings->admin_notification_email
                ?? config('mail.from.address');

            Notification::route('mail', $adminEmail)
                ->notify(new FailedPaymentNotification($order, $payment, $reason));

            Log::info('Failed payment notification sent to admin', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'admin_email' => $adminEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send payment failure notification to admin', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
