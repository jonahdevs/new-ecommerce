<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\ValueObjects\PaymentResponse;
use App\Services\Payment\ValueObjects\PaymentStatus;
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
        $this->isProduction   = ($settings->mpesa_env ?: config('services.mpesa.env')) === 'production';
        $this->consumerKey    = $settings->mpesa_consumer_key ?? config('services.mpesa.consumer_key');
        $this->consumerSecret = $settings->mpesa_consumer_secret ?? config('services.mpesa.consumer_secret');
        $this->shortcode      = $settings->mpesa_shortcode ?? config('services.mpesa.shortcode');
        $this->passkey        = $settings->mpesa_passkey ?? config('services.mpesa.passkey');
        $this->callbackUrl    = $settings->mpesa_callback_url ?? config('services.mpesa.callback_url');

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
                'status'         => 'processing',
                'meta'           => [
                    'checkout_request_id'  => $checkoutRequestId,
                    'merchant_request_id'  => $data['MerchantRequestID'],
                    'customer_message'     => $data['CustomerMessage'],
                    'phone'                => $phone,
                    'initiated_at'         => now()->toISOString(),
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

    public function verify(string $reference): PaymentStatus
    {
        // M-Pesa verification done via callback — use payment record status
        $payment = \App\Models\Payment::where('transaction_id', $reference)->first();

        if (! $payment) return PaymentStatus::pending();

        return match ($payment->status) {
            'paid'       => PaymentStatus::paid($payment->transaction_id),
            'failed'     => PaymentStatus::failed(),
            'cancelled'  => PaymentStatus::cancelled(),
            'processing' => PaymentStatus::processing(),
            default      => PaymentStatus::pending(),
        };
    }

    public function handleWebhook(Request $request): void
    {
        $data   = $request->json()->all();
        $result = $data['Body']['stkCallback'] ?? null;

        if (! $result) return;

        $checkoutRequestId = $result['CheckoutRequestID'];
        $resultCode        = $result['ResultCode'];

        $payment = \App\Models\Payment::where('transaction_id', $checkoutRequestId)->first();

        if (! $payment) {
            Log::warning('M-Pesa webhook: payment not found', ['checkout_request_id' => $checkoutRequestId]);
            return;
        }

        $order = $payment->order;

        if ($resultCode === 0) {
            // Extract M-Pesa receipt from callback metadata
            $items  = collect($result['CallbackMetadata']['Item'] ?? []);
            $receipt = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;

            $payment->update([
                'status'         => 'paid',
                'transaction_id' => $receipt ?? $checkoutRequestId,
                'paid_at'        => now(),
                'meta'           => array_merge($payment->meta ?? [], $result),
            ]);

            $order?->update([
                'status'         => 'confirmed',
                'payment_status' => 'paid',
            ]);

            Log::info('M-Pesa payment confirmed', ['receipt' => $receipt, 'order_id' => $order?->id]);
        } else {
            $payment->update(['status' => 'failed', 'meta' => array_merge($payment->meta ?? [], $result)]);
            $order?->update(['payment_status' => 'failed']);

            Log::info('M-Pesa payment failed', ['result_code' => $resultCode, 'order_id' => $order?->id]);
        }
    }

    //  Private helpers 

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
     * Normalise to 254XXXXXXXXX format for Daraja.
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
