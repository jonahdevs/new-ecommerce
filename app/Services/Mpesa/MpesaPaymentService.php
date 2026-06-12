<?php

namespace App\Services\Mpesa;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentCredentials;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates M-Pesa STK Push payments for orders: initiating the prompt,
 * reconciling status (via STK Query polling or the async callback), and
 * advancing the order once paid.
 */
class MpesaPaymentService
{
    public function __construct(
        private DarajaClient $daraja,
        private PaymentCredentials $credentials,
    ) {}

    /**
     * Normalize a Kenyan mobile number to the 2547……/2541…… MSISDN format.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '254')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '254'.substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '254'.$digits;
        }

        return $digits;
    }

    public static function isValidKenyanMobile(string $phone): bool
    {
        return (bool) preg_match('/^254(7|1)\d{8}$/', self::normalizePhone($phone));
    }

    /**
     * Create a pending Payment and fire the STK Push prompt for an order.
     */
    public function initiate(Order $order, string $phone): Payment
    {
        $payment = $order->payments()->create([
            'provider' => 'mpesa',
            'status' => PaymentStatus::PENDING,
            'amount_cents' => $order->total_cents,
            'currency' => 'KES',
            'phone' => self::normalizePhone($phone),
            'account_reference' => $order->order_number,
        ]);

        $response = $this->daraja->stkPush(
            amount: (int) round($order->total_cents / 100),
            phone: $payment->phone,
            accountReference: $payment->account_reference,
            callbackUrl: $this->callbackUrl(),
        );

        if (Arr::get($response, 'ResponseCode') === '0') {
            $payment->update([
                'merchant_request_id' => Arr::get($response, 'MerchantRequestID'),
                'checkout_request_id' => Arr::get($response, 'CheckoutRequestID'),
            ]);
        } else {
            $payment->update([
                'status' => PaymentStatus::FAILED,
                'result_desc' => Arr::get($response, 'errorMessage', Arr::get($response, 'ResponseDescription', 'STK push failed')),
                'payload' => $response,
            ]);
        }

        return $payment;
    }

    /**
     * Poll Daraja for the outcome of a pending payment and persist any result.
     */
    public function syncFromQuery(Payment $payment): PaymentStatus
    {
        if ($payment->status->isFinal() || ! $payment->checkout_request_id) {
            return $payment->status;
        }

        $response = $this->daraja->stkQuery($payment->checkout_request_id);

        // Still awaiting the customer — Daraja returns this until they act.
        if (Arr::get($response, 'errorCode') === '500.001.1001') {
            return PaymentStatus::PENDING;
        }

        if (! array_key_exists('ResultCode', $response)) {
            return PaymentStatus::PENDING;
        }

        $this->finalize(
            payment: $payment,
            resultCode: (int) $response['ResultCode'],
            resultDesc: (string) Arr::get($response, 'ResultDesc', ''),
            receipt: null,
            payload: $response,
        );

        return $payment->fresh()->status;
    }

    /**
     * Handle the async STK callback POSTed by Safaricom (production path).
     *
     * @param  array<string, mixed>  $payload
     */
    public function applyCallback(array $payload): void
    {
        $callback = Arr::get($payload, 'Body.stkCallback', []);
        $checkoutRequestId = Arr::get($callback, 'CheckoutRequestID');

        if (! $checkoutRequestId) {
            return;
        }

        $payment = Payment::where('checkout_request_id', $checkoutRequestId)->first();

        if (! $payment || $payment->status->isFinal()) {
            return;
        }

        $metadata = collect(Arr::get($callback, 'CallbackMetadata.Item', []))
            ->keyBy('Name');

        $paidAmount = Arr::get($metadata, 'Amount.Value');

        $this->finalize(
            payment: $payment,
            resultCode: (int) Arr::get($callback, 'ResultCode'),
            resultDesc: (string) Arr::get($callback, 'ResultDesc', ''),
            receipt: Arr::get($metadata, 'MpesaReceiptNumber.Value'),
            payload: $payload,
            paidAmount: $paidAmount !== null ? (int) round((float) $paidAmount) : null,
        );
    }

    /**
     * Persist the outcome of a payment and advance the order when it succeeds.
     *
     * @param  array<string, mixed>  $payload
     * @param  int|null  $paidAmount  Whole shillings Safaricom reports collecting (callback only;
     *                                the STK query response carries no amount, so it is null there).
     */
    private function finalize(Payment $payment, int $resultCode, string $resultDesc, ?string $receipt, array $payload, ?int $paidAmount = null): void
    {
        $status = match (true) {
            $resultCode === 0 => PaymentStatus::SUCCESS,
            $resultCode === 1032 => PaymentStatus::CANCELLED,
            default => PaymentStatus::FAILED,
        };

        // The callback endpoint is public and unauthenticated, so never trust a
        // "success" whose amount doesn't match what we billed. Reject the payment
        // and leave the order unpaid rather than advancing it on a wrong amount.
        if ($status === PaymentStatus::SUCCESS && $paidAmount !== null) {
            $expectedAmount = (int) round($payment->amount_cents / 100);

            if ($paidAmount !== $expectedAmount) {
                Log::critical('M-Pesa callback amount mismatch — payment rejected.', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'expected_kes' => $expectedAmount,
                    'paid_kes' => $paidAmount,
                ]);

                $payment->update([
                    'status' => PaymentStatus::FAILED,
                    'result_code' => $resultCode,
                    'result_desc' => "Amount mismatch: expected KES {$expectedAmount}, received KES {$paidAmount}.",
                    'mpesa_receipt' => $receipt ?? $payment->mpesa_receipt,
                    'payload' => $payload,
                    'paid_at' => null,
                ]);

                return;
            }
        }

        $payment->update([
            'status' => $status,
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
            'mpesa_receipt' => $receipt ?? $payment->mpesa_receipt,
            'payload' => $payload,
            'paid_at' => $status === PaymentStatus::SUCCESS ? now() : null,
        ]);

        if ($status === PaymentStatus::SUCCESS) {
            $payment->order->markConfirmed();
        }
    }

    private function callbackUrl(): string
    {
        return $this->credentials->mpesaCallbackUrl() ?: route('payments.mpesa.callback');
    }
}
