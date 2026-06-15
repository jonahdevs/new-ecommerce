<?php

namespace App\Services\Paystack;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentCredentials;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Orchestrates Paystack payments for orders: initializing a transaction for the
 * inline popup, verifying it server-side after the customer pays (and via the
 * async webhook), refunding settled payments, and advancing the order once paid.
 *
 * Paystack fronts every channel — cards, M-Pesa, Airtel Money and bank transfer
 * (Pesalink) — through one integration, so the on-page method choice simply
 * scopes which channel(s) the popup offers. Amounts are exchanged in the KES
 * subunit (cents), which matches Order::$total_cents directly.
 */
class PaystackPaymentService
{
    /**
     * Paystack channel value(s) for each on-page payment method. In Kenya both
     * M-Pesa and Airtel Money settle through the single "mobile_money" channel;
     * the popup resolves the specific network from the customer's phone number.
     *
     * @var array<string, list<string>>
     */
    private const CHANNELS = [
        'card' => ['card'],
        'mpesa' => ['mobile_money'],
        'airtel' => ['mobile_money'],
        'bank_transfer' => ['bank_transfer'],
    ];

    private PaystackClient $client;

    public function __construct(private PaymentCredentials $credentials)
    {
        $this->client = new PaystackClient($credentials->paystackSecretKey());
    }

    /**
     * Initialize a Paystack transaction for the order and create the matching
     * pending Payment. By default no channel restriction is sent, so the inline
     * popup offers every method enabled on the Paystack account (cards, M-Pesa,
     * Airtel Money, bank transfer…). Pass a $method to scope it to one channel.
     * The access code (used to resume the inline popup) is carried transiently
     * on the returned model, never persisted.
     *
     * @throws RuntimeException when Paystack rejects the initialization
     */
    public function initialize(Order $order, ?string $method = null): Payment
    {
        $reference = $this->generateReference($order);

        $payload = [
            'email' => $order->user?->email,
            'amount' => $order->total_cents,
            'currency' => 'KES',
            'reference' => $reference,
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'method' => $method,
            ],
        ];

        // Only restrict channels when a specific method is requested; otherwise
        // let Paystack present whatever the merchant has activated.
        if ($method !== null && isset(self::CHANNELS[$method])) {
            $payload['channels'] = self::CHANNELS[$method];
        }

        $response = $this->client->initializeTransaction($payload);

        if (Arr::get($response, 'status') !== true || ! Arr::get($response, 'data.access_code')) {
            throw new RuntimeException((string) Arr::get($response, 'message', 'Unable to start the Paystack transaction.'));
        }

        $payment = $order->payments()->create([
            'provider' => 'paystack',
            'status' => PaymentStatus::PENDING,
            'amount_cents' => $order->total_cents,
            'currency' => 'KES',
            'account_reference' => $order->order_number,
            'paystack_reference' => $reference,
            'channel' => $method,
        ]);

        return $payment->withPaystackAccessCode((string) Arr::get($response, 'data.access_code'));
    }

    /**
     * Verify a transaction by reference and finalize the matching payment.
     * Returns the payment only when it actually settled successfully, so callers
     * never advance an order on a mismatched or failed verification.
     */
    public function verify(string $reference): ?Payment
    {
        $response = $this->client->verifyTransaction($reference);

        if (Arr::get($response, 'status') !== true) {
            return null;
        }

        $payment = Payment::where('paystack_reference', $reference)->first();

        if (! $payment) {
            return null;
        }

        if (! $payment->status->isFinal()) {
            $this->finalize($payment, Arr::get($response, 'data', []));
        }

        $payment = $payment->fresh();

        return $payment->status === PaymentStatus::SUCCESS ? $payment : null;
    }

    /**
     * Verify and process an incoming Paystack webhook. The signature is an
     * HMAC-SHA512 of the raw body keyed with the secret key; an invalid one is
     * rejected before any processing. The reference is re-verified through the
     * API so the API remains the single source of truth.
     *
     * @throws RuntimeException when the signature is invalid
     */
    public function handleWebhook(string $rawPayload, string $signature): void
    {
        $expected = hash_hmac('sha512', $rawPayload, $this->credentials->paystackSecretKey());

        if ($signature === '' || ! hash_equals($expected, $signature)) {
            throw new RuntimeException('Invalid Paystack webhook signature.');
        }

        $event = json_decode($rawPayload, true) ?: [];

        if (Arr::get($event, 'event') === 'charge.success') {
            $reference = Arr::get($event, 'data.reference');

            if ($reference) {
                $this->verify((string) $reference);
            }
        }
    }

    /**
     * Refund (fully or partially) a settled payment through Paystack. Throws on a
     * gateway rejection so the caller can abort without recording a refund that
     * never happened. Unlike direct Daraja, Paystack reverses mobile-money
     * payments automatically, so no manual step is required.
     *
     * @throws RuntimeException when the refund is rejected at the gateway
     */
    public function refund(Payment $payment, int $amountCents): void
    {
        $response = $this->client->createRefund([
            'transaction' => $payment->paystack_reference,
            'amount' => $amountCents,
            'currency' => $payment->currency,
        ]);

        if (! $response->successful() || $response->json('status') !== true) {
            throw new RuntimeException((string) ($response->json('message') ?? 'Paystack refund was rejected.'));
        }
    }

    /**
     * Persist the outcome of a verified transaction and advance the order when
     * it succeeded.
     *
     * @param  array<string, mixed>  $data  The verify endpoint's data object.
     */
    private function finalize(Payment $payment, array $data): void
    {
        $status = (string) Arr::get($data, 'status');

        if ($status !== 'success') {
            $payment->update([
                'status' => $status === 'abandoned' ? PaymentStatus::CANCELLED : PaymentStatus::FAILED,
                'result_desc' => Arr::get($data, 'gateway_response'),
                'payload' => $data,
            ]);

            return;
        }

        // Paystack's verify response is authoritative — confirm it charged the
        // exact amount and currency we initialized with before advancing the
        // order. This guards against a tampered/replayed reference.
        $amount = (int) Arr::get($data, 'amount');
        $currency = strtolower((string) Arr::get($data, 'currency'));

        if ($amount !== (int) $payment->amount_cents || $currency !== strtolower($payment->currency)) {
            Log::critical('Paystack amount/currency mismatch — payment rejected.', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'expected_cents' => $payment->amount_cents,
                'expected_currency' => $payment->currency,
                'paid_amount' => $amount,
                'paid_currency' => $currency,
            ]);

            $payment->update([
                'status' => PaymentStatus::FAILED,
                'result_desc' => 'Amount or currency mismatch on Paystack verification.',
                'payload' => $data,
            ]);

            return;
        }

        $authorization = Arr::get($data, 'authorization', []);

        $payment->update([
            'status' => PaymentStatus::SUCCESS,
            'channel' => Arr::get($data, 'channel', $payment->channel),
            'authorization_code' => Arr::get($authorization, 'authorization_code'),
            'card_brand' => Arr::get($authorization, 'card_type') ?: Arr::get($authorization, 'brand'),
            'card_last4' => Arr::get($authorization, 'last4'),
            'phone' => Arr::get($authorization, 'mobile_money_number') ?: $payment->phone,
            'paid_at' => now(),
            'payload' => $data,
        ]);

        $payment->order->markConfirmed();
    }

    private function generateReference(Order $order): string
    {
        return $order->order_number.'-'.Str::upper(Str::random(8));
    }
}
