<?php

namespace App\Services\Payment\Gateways;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
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
use App\Settings\MpesaSettings;
use App\Settings\NotificationSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class MpesaGateway implements PaymentGateway
{
    private ?bool $isProduction;
    private ?string $consumerKey;
    private ?string $consumerSecret;
    private string $shortcode;
    private ?string $passkey;
    private string $callbackUrl;
    private string $baseUrl;

    public function __construct(
        MpesaSettings $settings,
        private readonly NotificationSettings $notificationSettings
    ) {
        // Use settings with config fallback
        $this->isProduction = ($settings->environment ?: config('services.mpesa.environment', 'sandbox')) === 'live';
        $this->consumerKey = $settings->consumer_key ?: config('services.mpesa.consumer_key');
        $this->consumerSecret = $settings->consumer_secret ?: config('services.mpesa.consumer_secret');
        $this->shortcode = $settings->shortcode ?: config('services.mpesa.shortcode', '');
        $this->passkey = $settings->passkey ?: config('services.mpesa.passkey');
        $this->callbackUrl = $settings->callback_url ?: config('services.mpesa.callback_url', '');

        $this->baseUrl = $this->isProduction
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function initiate(Order $order, Payment $payment, ?string $phone = null): PaymentResponse
    {
        if (!$phone) {
            return PaymentResponse::failed('M-Pesa phone number is required.');
        }

        try {
            $phone = $this->normalisePhone($phone);
            $amount = (int) ceil($order->total_cents / 100);
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/mpesa/stkpush/v1/processrequest", [
                    'BusinessShortCode' => $this->shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => $amount,
                    'PartyA' => $phone,
                    'PartyB' => $this->shortcode,
                    'PhoneNumber' => $phone,
                    'CallBackURL' => $this->callbackUrl,
                    'AccountReference' => $order->reference,
                    'TransactionDesc' => "Order #{$order->reference}",
                ]);

            $data = $response->json();

            if (($data['ResponseCode'] ?? '') !== '0') {
                throw new \RuntimeException(
                    $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'STK push failed.'
                );
            }

            $checkoutRequestId = $data['CheckoutRequestID'];

            $payment->update([
                'transaction_id' => $checkoutRequestId,
                'status' => PaymentStatus::PROCESSING->value,
                'meta' => array_merge($payment->meta ?? [], [
                    'checkout_request_id' => $checkoutRequestId,
                    'merchant_request_id' => $data['MerchantRequestID'],
                    'customer_message' => $data['CustomerMessage'],
                    'phone' => $phone,
                    'initiated_at' => now()->toISOString(),
                ]),
            ]);

            Log::info('M-Pesa STK push initiated', [
                'order_id' => $order->id,
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return PaymentResponse::stkPush($checkoutRequestId);

        } catch (\Throwable $e) {
            Log::error('M-Pesa initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function verify(string $reference): PaymentStatusVO
    {
        $payment = Payment::where('transaction_id', $reference)->first();

        if (!$payment)
            return PaymentStatusVO::pending();

        return match ($payment->status) {
            PaymentStatus::PAID->value => PaymentStatusVO::paid($payment->transaction_id),
            PaymentStatus::FAILED->value => PaymentStatusVO::failed(),
            PaymentStatus::CANCELLED->value => PaymentStatusVO::cancelled(),
            PaymentStatus::PROCESSING->value => PaymentStatusVO::processing(),
            default => PaymentStatusVO::pending(),
        };
    }

    public function handleWebhook(Request $request): void
    {
        $data = $request->json()->all();
        $result = $data['Body']['stkCallback'] ?? null;

        if (!$result)
            return;

        $checkoutRequestId = $result['CheckoutRequestID'];
        $resultCode = $result['ResultCode'];

        $payment = Payment::where('transaction_id', $checkoutRequestId)->first();

        if (!$payment) {
            Log::warning('M-Pesa webhook: payment not found', [
                'checkout_request_id' => $checkoutRequestId,
            ]);
            return;
        }

        $order = $payment->order;

        if ($resultCode === 0) {
            $this->markPaid($payment, $order, $result);
        } else {
            $this->markFailed($payment, $order, $result, $resultCode);
        }
    }

    private function markPaid(Payment $payment, ?Order $order, array $result): void
    {
        // Idempotency guard — Safaricom can deliver the callback more than once
        if ($payment->status === PaymentStatus::PAID->value) {
            Log::info('M-Pesa webhook already processed, skipping', [
                'transaction_id' => $payment->transaction_id,
            ]);
            return;
        }

        $items = collect($result['CallbackMetadata']['Item'] ?? []);
        $receipt = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;

        $payment->update([
            'status' => PaymentStatus::PAID->value,
            'transaction_id' => $receipt ?? $payment->transaction_id,
            'paid_at' => now(),
            'meta' => array_merge($payment->meta ?? [], $result),
        ]);

        if (!$order)
            return;

        $order->transitionTo(
            OrderStatus::CONFIRMED,
            notes: 'Payment confirmed via M-Pesa webhook. Receipt: ' . ($receipt ?? 'N/A'),
            changedByType: 'system',
        );
        $order->update(['payment_status' => PaymentStatus::PAID->value]);

        // Invoice is generated later when SAP webhook returns KRA data
        app(CartService::class)->clear(User::find($order->user_id));
        app(CheckoutSession::class)->clear();

        // Deduct stock — reservation is converted to a real deduction
        try {
            app(InventoryService::class)->deductStock($order);
        } catch (\Exception $e) {
            Log::error('Failed to deduct stock after M-Pesa payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // -------------------------------------------------------
        // Dispatch SAP sync — fires after M-Pesa confirms payment.
        // Order is CONFIRMED, payment_status = paid.
        // -------------------------------------------------------
        SyncOrderToSapJob::dispatch($order->fresh());

        Log::info('M-Pesa payment confirmed, SAP sync dispatched', [
            'order_id' => $order->id,
            'receipt' => $receipt,
        ]);
    }

    private function markFailed(Payment $payment, ?Order $order, array $result, int $resultCode): void
    {
        // Idempotency guard
        if ($payment->status === PaymentStatus::FAILED->value) {
            Log::info('M-Pesa failure webhook already processed, skipping', [
                'transaction_id' => $payment->transaction_id,
            ]);
            return;
        }

        $payment->update([
            'status' => PaymentStatus::FAILED->value,
            'meta' => array_merge($payment->meta ?? [], $result),
        ]);

        if (!$order)
            return;

        $order->transitionTo(
            OrderStatus::CANCELLED,
            notes: "M-Pesa payment failed. Result code: {$resultCode}",
            changedByType: 'system',
        );
        $order->update(['payment_status' => PaymentStatus::FAILED->value]);

        $this->restoreStock($order);

        // Send admin notification if enabled
        $this->sendFailedPaymentNotification($order, $payment, "M-Pesa payment failed. Result code: {$resultCode}");

        Log::info('M-Pesa payment failed', [
            'order_id' => $order->id,
            'result_code' => $resultCode,
        ]);
    }

    private function restoreStock(Order $order): void
    {
        app(InventoryService::class)->releaseReservation($order);
    }

    private function sendFailedPaymentNotification(Order $order, Payment $payment, string $reason): void
    {
        if (!$this->notificationSettings->notify_failed_payment) {
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

    private function getAccessToken(): string
    {
        return Cache::remember('mpesa_access_token', 3500, function () {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get("{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials");

            if ($response->failed()) {
                throw new \RuntimeException('Failed to get M-Pesa access token.');
            }

            return $response->json('access_token');
        });
    }

    private function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $digits = preg_replace('/^(254|0)/', '', $digits);
        return '254' . $digits;
    }

    public function initiateWithPhone(Order $order, Payment $payment, string $phone): PaymentResponse
    {
        return $this->initiate($order, $payment, $phone);
    }
}
