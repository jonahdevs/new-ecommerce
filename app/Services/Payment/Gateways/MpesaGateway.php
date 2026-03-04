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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaGateway implements PaymentGateway
{
    private bool   $isProduction;
    private string $consumerKey;
    private string $consumerSecret;
    private string $shortcode;
    private string $passkey;
    private string $callbackUrl;
    private string $baseUrl;

    public function __construct(PaymentSettings $settings)
    {
        $this->isProduction   = ($settings->mpesa_env
            ?: config('services.mpesa.environment')) === 'production';

        $this->consumerKey    = $settings->mpesa_consumer_key    ?: config('services.mpesa.consumer_key');
        $this->consumerSecret = $settings->mpesa_consumer_secret ?: config('services.mpesa.consumer_secret');
        $this->shortcode      = $settings->mpesa_shortcode       ?: config('services.mpesa.shortcode');
        $this->passkey        = $settings->mpesa_passkey         ?: config('services.mpesa.passkey');
        $this->callbackUrl    = $settings->mpesa_callback_url    ?: config('services.mpesa.callback_url');

        $this->baseUrl = $this->isProduction
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    //  Interface implementation 

    public function initiate(Order $order, Payment $payment): PaymentResponse
    {
        try {
            $phone     = $this->normalisePhone($this->resolvePhone($order));
            $amount    = (int) ceil($order->total_cents / 100); // M-Pesa needs whole KES
            $timestamp = now()->format('YmdHis');
            $password  = base64_encode($this->shortcode . $this->passkey . $timestamp);

            $token    = $this->getAccessToken();
            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/mpesa/stkpush/v1/processrequest", [
                    'BusinessShortCode' => $this->shortcode,
                    'Password'          => $password,
                    'Timestamp'         => $timestamp,
                    'TransactionType'   => 'CustomerPayBillOnline',
                    'Amount'            => $amount,
                    'PartyA'            => $phone,
                    'PartyB'            => $this->shortcode,
                    'PhoneNumber'       => $phone,
                    'CallBackURL'       => $this->callbackUrl,
                    'AccountReference'  => $order->reference,
                    'TransactionDesc'   => "Order #{$order->reference}",
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
                'status'         => PaymentStatus::PROCESSING->value,
                'meta'           => [
                    'checkout_request_id' => $checkoutRequestId,
                    'merchant_request_id' => $data['MerchantRequestID'],
                    'customer_message'    => $data['CustomerMessage'],
                    'phone'               => $phone,
                    'initiated_at'        => now()->toISOString(),
                ],
            ]);

            Log::info('M-Pesa STK push initiated', [
                'order_id'            => $order->id,
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return PaymentResponse::stkPush($checkoutRequestId);
        } catch (\Throwable $e) {
            Log::error('M-Pesa initiation failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function verify(string $reference): PaymentStatusVO
    {
        // M-Pesa confirms via callback only — read from payment record
        $payment = Payment::where('transaction_id', $reference)->first();

        if (!$payment) return PaymentStatusVO::pending();

        return match ($payment->status) {
            PaymentStatus::PAID->value       => PaymentStatusVO::paid($payment->transaction_id),
            PaymentStatus::FAILED->value     => PaymentStatusVO::failed(),
            PaymentStatus::CANCELLED->value  => PaymentStatusVO::cancelled(),
            PaymentStatus::PROCESSING->value => PaymentStatusVO::processing(),
            default                          => PaymentStatusVO::pending(),
        };
    }

    public function handleWebhook(Request $request): void
    {
        $data   = $request->json()->all();
        $result = $data['Body']['stkCallback'] ?? null;

        if (!$result) return;

        $checkoutRequestId = $result['CheckoutRequestID'];
        $resultCode        = $result['ResultCode'];

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

    //  Private helpers 

    private function markPaid(Payment $payment, ?Order $order, array $result): void
    {
        // Extract M-Pesa receipt from callback metadata
        $items   = collect($result['CallbackMetadata']['Item'] ?? []);
        $receipt = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;

        // 1. Update payment record
        $payment->update([
            'status'         => PaymentStatus::PAID->value,
            'transaction_id' => $receipt ?? $payment->transaction_id,
            'paid_at'        => now(),
            'meta'           => array_merge($payment->meta ?? [], $result),
        ]);

        if (!$order) return;

        // 2. Transition order status — records history automatically
        $order->transitionTo(
            OrdersStatus::CONFIRMED,
            notes: 'Payment confirmed via M-Pesa webhook. Receipt: ' . ($receipt ?? 'N/A'),
            changedByType: 'system'
        );
        $order->update(['payment_status' => PaymentStatus::PAID->value]);

        // 3. Clear cart — payment is confirmed
        app(CartService::class)->clear(
            User::find($order->user_id)
        );

        // 4. Clear checkout session
        app(CheckoutSession::class)->clear();

        Log::info('M-Pesa payment confirmed', [
            'order_id' => $order->id,
            'receipt'  => $receipt,
        ]);
    }

    private function markFailed(Payment $payment, ?Order $order, array $result, int $resultCode): void
    {
        // 1. Update payment record
        $payment->update([
            'status' => PaymentStatus::FAILED->value,
            'meta'   => array_merge($payment->meta ?? [], $result),
        ]);

        if (!$order) return;

        // 2. Transition order status
        $order->transitionTo(
            OrdersStatus::CANCELLED,
            notes: "M-Pesa payment failed. Result code: {$resultCode}",
            changedByType: 'system'
        );
        $order->update(['payment_status' => PaymentStatus::FAILED->value]);

        // 3. Restore stock
        $this->restoreStock($order);

        Log::info('M-Pesa payment failed', [
            'order_id'    => $order->id,
            'result_code' => $resultCode,
        ]);
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items()->with('product')->get() as $item) {
            $item->product?->increment('stock_quantity', $item->quantity);
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

    /**
     * Normalise phone to 254XXXXXXXXX format for Daraja API.
     */
    private function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $digits = preg_replace('/^(254|0)/', '', $digits);
        return '254' . $digits;
    }

    private function resolvePhone(Order $order): string
    {
        return $order->user?->phone_number
            ?? $order->shipping_address['phone_number']
            ?? '';
    }
}
